<?php
defined( 'ABSPATH' ) || exit;

class BA_Email {

    /**
     * Invia cedolino di pagamento per nuove registrazioni con Fattura.
     * Aggiorna stato: sospeso → inviato | errore_invio
     * @return string  nuovo stato: 'inviato' | 'errore_invio'
     */
    public static function invia_cedolino_fattura( object $record ): string {
        if ( empty( $record->email ) ) return 'errore_invio';

        // Genera PDF cedolino
        $pdf = BA_PDF_Manager::genera( $record->id, 'cedolino' );

        $subject = self::parse(
            get_option( 'ba_email_cedolino_oggetto',
                __( 'Domanda di adesione ricevuta – La Botega da la Lavizzara', 'botega-adesioni' )
            ), $record
        );
        $body = self::parse(
            get_option( 'ba_email_cedolino_corpo', self::default_cedolino_body() ),
            $record
        );

        $attachments = [];
        if ( $pdf && file_exists( $pdf->percorso ) ) {
            $attachments[] = $pdf->percorso;
        }

        $ok = self::send( $record->email, $subject, $body, $attachments );
        return $ok ? 'inviato' : 'errore_invio';
    }

    /**
     * Invia tutti i documenti post-pagamento in base allo stato.
     *
     * Regole:
     *   Conferma pagamento  → stato = pagato OR socio_onorario
     *   Attestato quota     → stato = pagato OR socio_onorario  (solo se quota_250 = 1)
     *   Attestato donazione → stato = pagato ONLY              (solo se donazione > 0)
     */
    public static function invia_documenti_post_pagamento( object $record, string $stato ): bool {
        if ( empty( $record->email ) ) return false;

        $is_pagato   = ( $stato === 'pagato' );
        $is_onorario = ( $stato === 'socio_onorario' );
        $attachments = [];

        // 1. Conferma pagamento → Pagato OR Socio onorario
        if ( $is_pagato || $is_onorario ) {
            $pdf = BA_PDF_Manager::genera( $record->id, 'conferma_pag' );
            if ( $pdf && file_exists( $pdf->percorso ) ) $attachments[] = $pdf->percorso;
        }

        // 2. Attestato quota → Pagato OR Socio onorario (solo se quota_250 = 1)
        if ( ( $is_pagato || $is_onorario ) && $record->quota_250 ) {
            $pdf = BA_PDF_Manager::genera( $record->id, 'attestato_quota' );
            if ( $pdf && file_exists( $pdf->percorso ) ) $attachments[] = $pdf->percorso;
        }

        // 3. Attestato donazione → SOLO Pagato (non Socio onorario)
        if ( $is_pagato && $record->donazione > 0 ) {
            $pdf = BA_PDF_Manager::genera( $record->id, 'attestato_don' );
            if ( $pdf && file_exists( $pdf->percorso ) ) $attachments[] = $pdf->percorso;
        }

        // Scegli oggetto/corpo email in base allo stato
        if ( $is_onorario ) {
            $subject = __( 'Benvenuto come Socio Onorario – La Botega da la Lavizzara', 'botega-adesioni' );
            $body    = self::parse( self::default_onorario_body(), $record );
        } else {
            $subject = self::parse(
                get_option( 'ba_email_conferma_oggetto',
                    __( 'Conferma pagamento adesione – La Botega da la Lavizzara', 'botega-adesioni' )
                ), $record
            );
            $body = self::parse(
                get_option( 'ba_email_conferma_corpo', self::default_conferma_body() ),
                $record
            );
        }

        return self::send( $record->email, $subject, $body, $attachments );
    }

    /**
     * @deprecated — sostituito da invia_documenti_post_pagamento()
     * Mantenuto per compatibilità con eventuali chiamate esterne.
     */
    public static function invia_conferma_pagamento( object $record ): bool {
        return self::invia_documenti_post_pagamento( $record, 'pagato' );
    }

    /**
     * @deprecated — sostituito da invia_documenti_post_pagamento()
     */
    public static function invia_attestato_onorario( object $record ): bool {
        return self::invia_documenti_post_pagamento( $record, 'socio_onorario' );
    }

    /**
     * Invia richiamo pagamento
     */
    public static function richiamo_pagamento( object $record ): bool {
        if ( empty( $record->email ) ) return false;
        $subject = self::parse( get_option( 'ba_email_richiamo_oggetto',
            __( 'Promemoria pagamento – La Botega da la Lavizzara', 'botega-adesioni' )
        ), $record );
        $body = self::parse( get_option( 'ba_email_richiamo_corpo', self::default_richiamo_body() ), $record );
        return self::send( $record->email, $subject, $body );
    }

    /**
     * Richiamo massivo
     */
    public static function richiamo_massivo( array $ids = [] ): array {
        $result = [ 'inviati' => 0, 'errori' => 0, 'saltati' => 0 ];
        if ( empty( $ids ) ) {
            $data    = BA_Database::get_all([ 'per_page' => 9999 ]);
            $records = array_filter( $data['rows'], fn( $r ) =>
                in_array( $r->stato_pagamento, [ 'inviato', 'sospeso', 'errore_invio' ], true )
            );
        } else {
            $records = array_filter( array_map( fn( $id ) => BA_Database::get( (int) $id ), $ids ) );
        }
        foreach ( $records as $r ) {
            if ( empty( $r->email ) ) { $result['saltati']++; continue; }
            self::richiamo_pagamento( $r ) ? $result['inviati']++ : $result['errori']++;
        }
        return $result;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private static function send( string $to, string $subject, string $body, array $attachments = [] ): bool {
        $from_name  = get_option( 'ba_email_mittente_nome',  get_bloginfo( 'name' ) );
        $from_email = get_option( 'ba_email_mittente_email', get_option( 'admin_email' ) );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: $from_name <$from_email>",
        ];

        // Corpo HTML: sostituisci newline con <br>, senza esc_html (il testo è già trusted)
        $html_body = nl2br( $body );

        // Abilita SMTP se configurato
        $use_smtp = get_option( 'ba_smtp_abilitato', '0' ) === '1';
        if ( $use_smtp ) {
            add_action( 'phpmailer_init', [ __CLASS__, 'configure_smtp' ] );
        }

        try {
            $result = wp_mail( $to, $subject, $html_body, $headers, $attachments );
        } catch ( \Throwable $e ) {
            // Cattura Fatal Error da mail() non disponibile o SMTP fallito
            error_log( 'BA_Email::send() error: ' . $e->getMessage() );
            $result = false;
        }

        if ( $use_smtp ) {
            remove_action( 'phpmailer_init', [ __CLASS__, 'configure_smtp' ] );
        }

        return $result;
    }

    /**
     * Configura PHPMailer per usare SMTP invece di mail()
     * Agganciato a phpmailer_init solo quando necessario.
     */
    public static function configure_smtp( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->isSMTP();
        $phpmailer->Host       = get_option( 'ba_smtp_host',     '' );
        $phpmailer->Port       = (int) get_option( 'ba_smtp_port', 587 );
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = get_option( 'ba_smtp_user',     '' );
        $phpmailer->Password   = get_option( 'ba_smtp_pass',     '' );
        $phpmailer->SMTPSecure = get_option( 'ba_smtp_secure',   'tls' ); // tls | ssl | ''
        $phpmailer->SMTPDebug  = 0;
    }

    private static function parse( string $tpl, object $record ): string {
        $tipi   = BA_Database::get_tipi_socio();
        $stati  = BA_Database::get_stati();
        $metodi = BA_Database::get_metodi_pagamento();
        $map = [
            '{{nome}}'          => $record->cognome_nome,
            '{{email}}'         => $record->email,
            '{{telefono}}'      => $record->telefono,
            '{{indirizzo}}'     => $record->indirizzo,
            '{{tipo_socio}}'    => $tipi[ $record->tipo_socio ] ?? $record->tipo_socio,
            '{{importo}}'       => BA_Database::fmt_chf( (float) $record->importo_totale ),
            '{{quota}}'         => $record->quota_250 ? 'CHF 250.00' : '—',
            '{{donazione}}'     => $record->donazione > 0 ? BA_Database::fmt_chf( (float) $record->donazione ) : '—',
            '{{metodo}}'        => $metodi[ $record->metodo_pagamento ] ?? $record->metodo_pagamento,
            '{{stato}}'         => $stati[ $record->stato_pagamento ] ?? $record->stato_pagamento,
            '{{data_adesione}}' => $record->data_adesione ? date_i18n( 'd.m.Y', strtotime( $record->data_adesione ) ) : '—',
            '{{localita}}'         => $record->localita,
            '{{iban}}'          => 'CH48 8080 8003 7010 4694 7',
            '{{associazione}}'  => 'La Botega da la Lavizzara',
            '{{data_oggi}}'     => date_i18n( 'd.m.Y' ),
            '{{id_membro}}'     => $record->id_membro ?? '—',
            '{{id_quota}}'      => $record->id_quota  ?? '—',
        ];
        return str_replace( array_keys( $map ), array_values( $map ), $tpl );
    }

    // ── Testi default ─────────────────────────────────────────────────────────

    private static function default_cedolino_body(): string {
        return __( "Gentile {{nome}},\n\nabbiamo ricevuto la sua domanda di adesione alla Cooperativa \"La Botega da la Lavizzara\" come {{tipo_socio}}.\n\nIn allegato trova il cedolino di pagamento con tutti i dati necessari per effettuare il versamento.\n\n⚠️ La sua adesione sarà attiva solo dopo la ricezione del pagamento della quota sociale di {{importo}}.\n\nID Membro: {{id_membro}}\nID Quota: {{id_quota}}\n\nPer qualsiasi informazione non esiti a contattarci.\n\nCordiali saluti,\nLa segreteria de La Botega da la Lavizzara\nlabotegalavizzara@gmail.com", 'botega-adesioni' );
    }

    private static function default_conferma_body(): string {
        return __( "Gentile {{nome}},\n\nConfermiamo la ricezione del pagamento per la sua adesione alla Cooperativa.\n\nID Membro: {{id_membro}}\nID Quota: {{id_quota}}\nImporto: {{importo}}\nData: {{data_adesione}}\n\nIn allegato trova la conferma di pagamento e gli attestati.\n\nGrazie per il suo sostegno!\nLa Botega da la Lavizzara", 'botega-adesioni' );
    }

    private static function default_onorario_body(): string {
        return __( "Gentile {{nome}},\n\nsiamo lieti di comunicarle che è stata designata/o come Socio Onorario della Cooperativa \"La Botega da la Lavizzara\".\n\nIn allegato trova il suo attestato di Socio Onorario.\n\nCon stima e cordiali saluti,\nIl Comitato de La Botega da la Lavizzara", 'botega-adesioni' );
    }

    private static function default_richiamo_body(): string {
        return __( "Gentile {{nome}},\n\nla ricordiamo che risulta ancora in sospeso il pagamento della sua quota di adesione di {{importo}}.\n\nIBAN: {{iban}}\nRiferimento: {{id_quota}}\n\nCordiali saluti,\nLa Botega da la Lavizzara\nlabotegalavizzara@gmail.com", 'botega-adesioni' );
    }
}
