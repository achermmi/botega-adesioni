<?php
defined( 'ABSPATH' ) || exit;

class BA_Database {

    // ── Nomi tabelle ──────────────────────────────────────────────────────────
    public static function table_adesioni(): string   { global $wpdb; return $wpdb->prefix . 'ba_adesioni'; }
    public static function table_custom_fields(): string { global $wpdb; return $wpdb->prefix . 'ba_custom_fields'; }
    public static function table_custom_values(): string { global $wpdb; return $wpdb->prefix . 'ba_custom_values'; }
    public static function table_pdf(): string        { global $wpdb; return $wpdb->prefix . 'ba_pdf'; }

    // ── Installazione ─────────────────────────────────────────────────────────
    public static function install(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql_adesioni = "CREATE TABLE IF NOT EXISTS " . self::table_adesioni() . " (
            id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_membro        VARCHAR(20)     NOT NULL DEFAULT '',
            id_quota         VARCHAR(20)     NOT NULL DEFAULT '',
            cognome_nome     VARCHAR(200)    NOT NULL DEFAULT '',
            indirizzo        VARCHAR(300)    NOT NULL DEFAULT '',
            telefono         VARCHAR(50)     NOT NULL DEFAULT '',
            email            VARCHAR(200)    NOT NULL DEFAULT '',
            tipo_socio       VARCHAR(50)     NOT NULL DEFAULT 'socio_attivo',
            quota_250        TINYINT(1)      NOT NULL DEFAULT 0,
            donazione        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            importo_totale   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            cap              VARCHAR(20)     NOT NULL DEFAULT '',
            localita         VARCHAR(200)    NOT NULL DEFAULT '',
            data_adesione    DATE            NULL,
            metodo_pagamento VARCHAR(30)     NOT NULL DEFAULT 'fattura',
            stato_pagamento  VARCHAR(30)     NOT NULL DEFAULT 'sospeso',
            note             TEXT            NULL,
            nome_versamento  VARCHAR(200)    NOT NULL DEFAULT '',
            ind_versamento   VARCHAR(300)    NOT NULL DEFAULT '',
            cap_versamento   VARCHAR(100)    NOT NULL DEFAULT '',
            data_creazione   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            data_modifica    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            creato_da        BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY idx_id_membro (id_membro(20)),
            UNIQUE KEY idx_id_quota  (id_quota(20)),
            KEY idx_stato   (stato_pagamento),
            KEY idx_tipo    (tipo_socio),
            KEY idx_email   (email(100)),
            KEY idx_data    (data_adesione)
        ) $charset;";

        $sql_pdf = "CREATE TABLE IF NOT EXISTS " . self::table_pdf() . " (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            adesione_id  BIGINT UNSIGNED NOT NULL,
            tipo         VARCHAR(30)     NOT NULL DEFAULT 'attestato_quota',
            nome_file    VARCHAR(255)    NOT NULL DEFAULT '',
            percorso     VARCHAR(500)    NOT NULL DEFAULT '',
            url          VARCHAR(500)    NOT NULL DEFAULT '',
            dimensione   INT UNSIGNED    NOT NULL DEFAULT 0,
            data_gen     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            note         VARCHAR(255)    NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_adesione (adesione_id),
            KEY idx_tipo     (tipo)
        ) $charset;";

        $sql_custom_fields = "CREATE TABLE IF NOT EXISTS " . self::table_custom_fields() . " (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            field_key   VARCHAR(100)    NOT NULL UNIQUE,
            label       VARCHAR(200)    NOT NULL DEFAULT '',
            field_type  VARCHAR(30)     NOT NULL DEFAULT 'text',
            options     TEXT            NULL,
            posizione   INT             NOT NULL DEFAULT 0,
            attivo      TINYINT(1)      NOT NULL DEFAULT 1,
            data_creazione DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_key (field_key)
        ) $charset;";

        $sql_custom_values = "CREATE TABLE IF NOT EXISTS " . self::table_custom_values() . " (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            adesione_id BIGINT UNSIGNED NOT NULL,
            field_key   VARCHAR(100)    NOT NULL,
            valore      TEXT            NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_unique (adesione_id, field_key(100)),
            KEY idx_adesione (adesione_id),
            KEY idx_field    (field_key)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_adesioni );
        dbDelta( $sql_pdf );
        dbDelta( $sql_custom_fields );
        dbDelta( $sql_custom_values );

        self::maybe_migrate();
        update_option( 'ba_db_version', BA_VERSION );
    }

    private static function maybe_migrate(): void {
        global $wpdb;
        $t = self::table_adesioni();
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $t", 0 );
        if ( empty( $columns ) ) return;

        $to_add = [
            'id_membro' => "VARCHAR(20) NOT NULL DEFAULT '' AFTER `id`",
            'id_quota'  => "VARCHAR(20) NOT NULL DEFAULT '' AFTER `id_membro`",
            'cap'       => "VARCHAR(20) NOT NULL DEFAULT '' AFTER `indirizzo`",
            'localita'  => "VARCHAR(200) NOT NULL DEFAULT '' AFTER `cap`",
        ];

        // Rinomina luogo → localita se ancora presente
        $col_check = $wpdb->get_col("SHOW COLUMNS FROM $t", 0);
        if (in_array('localita', $col_check, true) && !in_array('localita', $col_check, true)) {
            $wpdb->query("ALTER TABLE $t CHANGE `luogo` `localita` VARCHAR(200) NOT NULL DEFAULT ''");
        }
        foreach ( $to_add as $col => $def ) {
            if ( ! in_array( $col, $columns, true ) ) {
                $wpdb->query( "ALTER TABLE $t ADD COLUMN `$col` $def" );
            }
        }

        // Rimuovi colonne attestato dalla tabella principale (ora in ba_pdf)
        $old_cols = [ 'attestato_html', 'attestato_pdf_url', 'attestato_data' ];
        foreach ( $old_cols as $col ) {
            if ( in_array( $col, $columns, true ) ) {
                $wpdb->query( "ALTER TABLE $t DROP COLUMN `$col`" );
            }
        }

        // Indici univoci
        $indexes   = $wpdb->get_results( "SHOW INDEX FROM $t", ARRAY_A );
        $idx_names = array_column( $indexes, 'Key_name' );
        if ( ! in_array( 'idx_id_membro', $idx_names, true ) )
            $wpdb->query( "ALTER TABLE $t ADD UNIQUE KEY `idx_id_membro` (`id_membro`(20))" );
        if ( ! in_array( 'idx_id_quota', $idx_names, true ) )
            $wpdb->query( "ALTER TABLE $t ADD UNIQUE KEY `idx_id_quota` (`id_quota`(20))" );

        self::backfill_ids();
    }

    private static function backfill_ids(): void {
        global $wpdb;
        $t    = self::table_adesioni();
        $rows = $wpdb->get_results( "SELECT id FROM $t WHERE id_membro = '' OR id_membro IS NULL" );
        foreach ( $rows as $r ) {
            $wpdb->update( $t, [
                'id_membro' => self::genera_id_membro(),
                'id_quota'  => self::genera_id_quota(),
            ], [ 'id' => $r->id ] );
        }
    }

    // ── ID univoci ────────────────────────────────────────────────────────────

    public static function genera_id_membro(): string {
        $anno = date( 'Y' );
        do {
            $rand = strtoupper( substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 4 ) );
            $id   = "BLM-$anno-$rand";
        } while ( self::id_membro_exists( $id ) );
        return $id;
    }

    public static function genera_id_quota(): string {
        $anno = date( 'Y' );
        do {
            $rand = strtoupper( substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 8 ) );
            $id   = "BLQ-$anno-$rand";
        } while ( self::id_quota_exists( $id ) );
        return $id;
    }

    private static function id_membro_exists( string $id ): bool {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM " . self::table_adesioni() . " WHERE id_membro = %s", $id
        ));
    }

    private static function id_quota_exists( string $id ): bool {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM " . self::table_adesioni() . " WHERE id_quota = %s", $id
        ));
    }

    public static function deactivate(): void {}

    // ── CRUD Adesioni ─────────────────────────────────────────────────────────

    public static function insert( array $data ): int|false {
        global $wpdb;
        $data['data_creazione'] = current_time( 'mysql' );
        $data['creato_da']      = get_current_user_id();
        $data['importo_totale'] = self::calcola_totale( $data );
        if ( empty( $data['id_membro'] ) ) $data['id_membro'] = self::genera_id_membro();
        if ( empty( $data['id_quota'] ) )  $data['id_quota']  = self::genera_id_quota();
        $result = $wpdb->insert( self::table_adesioni(), $data );
        return $result ? $wpdb->insert_id : false;
    }

    public static function update( int $id, array $data ): bool {
        global $wpdb;
        // Ricalcola importo_totale solo se i campi rilevanti sono presenti nell'aggiornamento
        if ( array_key_exists( 'quota_250', $data ) || array_key_exists( 'donazione', $data ) ) {
            // Legge i valori attuali per i campi mancanti
            $current = self::get( $id );
            if ( $current ) {
                if ( ! array_key_exists( 'quota_250', $data ) ) $data['quota_250'] = $current->quota_250;
                if ( ! array_key_exists( 'donazione', $data ) ) $data['donazione'] = $current->donazione;
            }
            $data['importo_totale'] = self::calcola_totale( $data );
        }
        $result = $wpdb->update( self::table_adesioni(), $data, [ 'id' => $id ] );
        return $result !== false;
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        // Elimina PDF fisici
        $pdfs = self::get_pdfs( $id );
        foreach ( $pdfs as $pdf ) BA_PDF_Manager::elimina_file( $pdf );
        $wpdb->delete( self::table_pdf(),          [ 'adesione_id' => $id ] );
        $wpdb->delete( self::table_custom_values(), [ 'adesione_id' => $id ] );
        return (bool) $wpdb->delete( self::table_adesioni(), [ 'id' => $id ] );
    }

    public static function get( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table_adesioni() . " WHERE id = %d", $id
        ));
    }

    // ── PDF ───────────────────────────────────────────────────────────────────

    public static function save_pdf( int $adesione_id, string $tipo, string $nome_file, string $percorso, string $url, int $dimensione ): int|false {
        global $wpdb;
        $result = $wpdb->insert( self::table_pdf(), [
            'adesione_id' => $adesione_id,
            'tipo'        => $tipo,
            'nome_file'   => $nome_file,
            'percorso'    => $percorso,
            'url'         => $url,
            'dimensione'  => $dimensione,
            'data_gen'    => current_time( 'mysql' ),
        ]);
        return $result ? $wpdb->insert_id : false;
    }

    public static function get_pdfs( int $adesione_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table_pdf() . " WHERE adesione_id = %d ORDER BY data_gen DESC",
            $adesione_id
        )) ?: [];
    }

    public static function get_pdf( int $pdf_id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table_pdf() . " WHERE id = %d", $pdf_id
        ));
    }

    public static function delete_pdf( int $pdf_id ): bool {
        global $wpdb;
        $pdf = self::get_pdf( $pdf_id );
        if ( ! $pdf ) return false;
        BA_PDF_Manager::elimina_file( $pdf );
        return (bool) $wpdb->delete( self::table_pdf(), [ 'id' => $pdf_id ] );
    }

    public static function rename_pdf( int $pdf_id, string $nuovo_nome ): bool {
        global $wpdb;
        $pdf = self::get_pdf( $pdf_id );
        if ( ! $pdf ) return false;

        $nuovo_nome = sanitize_file_name( $nuovo_nome );
        if ( ! ( substr( strtolower( $nuovo_nome ), -4 ) === '.pdf' ) ) $nuovo_nome .= '.pdf';

        $dir         = dirname( $pdf->percorso );
        $nuovo_path  = $dir . '/' . $nuovo_nome;
        $upload_dir  = wp_upload_dir();
        $nuovo_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $nuovo_path );

        if ( file_exists( $pdf->percorso ) ) {
            rename( $pdf->percorso, $nuovo_path );
        }

        return (bool) $wpdb->update( self::table_pdf(), [
            'nome_file' => $nuovo_nome,
            'percorso'  => $nuovo_path,
            'url'       => $nuovo_url,
        ], [ 'id' => $pdf_id ] );
    }

    // ── Query avanzata ────────────────────────────────────────────────────────

    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $defaults = [
            'page'         => 1,
            'per_page'     => 50,
            'orderby'      => 'data_creazione',
            'order'        => 'DESC',
            'filters'      => [],
            'filter_logic' => 'AND',
            'search'       => '',
        ];
        $args = wp_parse_args( $args, $defaults );

        $table  = self::table_adesioni();
        $where  = [];
        $params = [];

        $allowed_fields = [
            'cognome_nome', 'email', 'telefono', 'tipo_socio',
            'stato_pagamento', 'metodo_pagamento', 'data_adesione',
            'importo_totale', 'quota_250', 'cap', 'localita', 'id_membro', 'id_quota',
        ];

        foreach ( $args['filters'] as $f ) {
            if ( ! isset( $f['campo'], $f['operatore'], $f['valore'] ) ) continue;
            if ( ! in_array( $f['campo'], $allowed_fields, true ) ) continue;
            $campo = esc_sql( $f['campo'] );
            $val   = $f['valore'];
            switch ( $f['operatore'] ) {
                case '=':   $where[] = "$campo = %s";              $params[] = $val; break;
                case '!=':  $where[] = "$campo != %s";             $params[] = $val; break;
                case 'LIKE':$where[] = "$campo LIKE %s";           $params[] = '%' . $wpdb->esc_like( $val ) . '%'; break;
                case '>':   $where[] = "$campo > %s";              $params[] = $val; break;
                case '<':   $where[] = "$campo < %s";              $params[] = $val; break;
                case '>=':  $where[] = "$campo >= %s";             $params[] = $val; break;
                case '<=':  $where[] = "$campo <= %s";             $params[] = $val; break;
            }
        }

        if ( ! empty( $args['search'] ) ) {
            $s = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[]  = "(cognome_nome LIKE %s OR email LIKE %s OR telefono LIKE %s OR localita LIKE %s OR id_membro LIKE %s OR id_quota LIKE %s)";
            $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $logic     = $args['filter_logic'] === 'OR' ? ' OR ' : ' AND ';
        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( $logic, $where ) : '';

        $orderby = in_array( $args['orderby'], $allowed_fields, true ) ? $args['orderby'] : 'data_creazione';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $count_sql = "SELECT COUNT(*) FROM $table $where_sql";
        $total     = ! empty( $params )
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) )
            : (int) $wpdb->get_var( $count_sql );

        $per_page = max( 1, (int) $args['per_page'] );
        $offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

        $select_sql = "SELECT * FROM $table $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $all_params = array_merge( $params, [ $per_page, $offset ] );
        $rows       = $wpdb->get_results( $wpdb->prepare( $select_sql, ...$all_params ) );

        return [
            'rows'     => $rows ?: [],
            'total'    => $total,
            'per_page' => $per_page,
            'page'     => (int) $args['page'],
            'pages'    => (int) ceil( $total / $per_page ),
        ];
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    private static function calcola_totale( array $data ): float {
        $totale = 0.0;
        if ( ! empty( $data['quota_250'] ) ) $totale += 250.00;
        if ( ! empty( $data['donazione'] ) ) $totale += (float) $data['donazione'];
        return $totale;
    }

    public static function get_stati(): array {
        return [
            'sospeso'         => __( 'Sospeso',         'botega-adesioni' ),
            'inviato'         => __( 'Inviato',         'botega-adesioni' ),
            'errore_invio'    => __( 'Errore invio',    'botega-adesioni' ),
            'pagato'          => __( 'Pagato',          'botega-adesioni' ),
            'socio_onorario'  => __( 'Socio onorario',  'botega-adesioni' ),
            'stornato'        => __( 'Stornato',        'botega-adesioni' ),
            'annullato'       => __( 'Annullato',       'botega-adesioni' ),
        ];
    }

    public static function get_tipi_socio(): array {
        return [
            'socio_attivo'    => __( 'Socio attivo',       'botega-adesioni' ),
            'socio_passivo'   => __( 'Socio passivo',      'botega-adesioni' ),
            'socio_onorario'  => __( 'Socio onorario',     'botega-adesioni' ),
            'membro_comitato' => __( 'Membro di comitato', 'botega-adesioni' ),
            'aiuto_comitato'  => __( 'Aiuto comitato',     'botega-adesioni' ),
            'presidente'      => __( 'Presidente',         'botega-adesioni' ),
            'altro'           => __( 'Altro',              'botega-adesioni' ),
        ];
    }

    public static function get_metodi_pagamento(): array {
        return [
            'fattura' => __( 'Fattura',                 'botega-adesioni' ),
            'twint'   => __( 'TWINT',                   'botega-adesioni' ),
            'paypal'  => __( 'PayPal',                  'botega-adesioni' ),
            'carta'   => __( 'Carta di credito/debito', 'botega-adesioni' ),
        ];
    }

    public static function get_tipi_pdf(): array {
        return [
            'cedolino'        => __( 'Cedolino di pagamento',   'botega-adesioni' ),
            'attestato_quota' => __( 'Attestato quota sociale', 'botega-adesioni' ),
            'attestato_don'   => __( 'Attestato donazione',     'botega-adesioni' ),
            'conferma_pag'    => __( 'Conferma pagamento',      'botega-adesioni' ),
        ];
    }

    public static function get_stats(): array {
        global $wpdb;
        $t = self::table_adesioni();
        // Usa GREATEST per calcolare importo reale: se importo_totale>0 usa quello,
        // altrimenti somma quota_250*250 + donazione (record pre-fix o con totale non salvato)
        $imp_expr = "GREATEST(importo_totale, (quota_250 * 250.00) + donazione)";
        return [
            'totale'    => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $t" ),
            'pagati'    => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE stato_pagamento = 'pagato'" ),
            'sospesi'   => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE stato_pagamento IN ('sospeso','errore_invio')" ),
            'inviati'   => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE stato_pagamento = 'inviato'" ),
            'incasso'   => (float) $wpdb->get_var( "SELECT SUM($imp_expr) FROM $t WHERE stato_pagamento = 'pagato'" ),
            'in_attesa' => (float) $wpdb->get_var( "SELECT SUM($imp_expr) FROM $t WHERE stato_pagamento IN ('inviato','sospeso')" ),
        ];
    }

    public static function fmt_chf( float $amount ): string {
        if ( $amount <= 0 ) return '—';
        $parts   = number_format( $amount, 2, '.', '' );
        [$int, $dec] = explode( '.', $parts );
        $int_fmt = preg_replace( '/\B(?=(\d{3})+(?!\d))/', "'", $int );
        return "CHF $int_fmt.$dec";
    }
}
