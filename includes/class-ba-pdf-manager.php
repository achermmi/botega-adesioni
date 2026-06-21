<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_PDF_Manager
 * Gestione completa dei PDF: generazione, salvataggio, naming, eliminazione, download.
 * Tipi: cedolino | attestato_quota | attestato_don | conferma_pag
 */
class BA_PDF_Manager {

    // ── Cartella upload ───────────────────────────────────────────────────────

    public static function upload_dir(): array {
        $base = wp_upload_dir();
        $dir  = $base['basedir'] . '/botega-adesioni/';
        $url  = $base['baseurl'] . '/botega-adesioni/';
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
            file_put_contents( $dir . '.htaccess', "Options -Indexes\nDeny from all\n" );
            file_put_contents( $dir . 'index.php', '<?php // silence' );
        }
        return [ 'dir' => $dir, 'url' => $url ];
    }

    // ── Nome file significativo ───────────────────────────────────────────────

    public static function nome_file( object $record, string $tipo ): string {
        $tipi_label = [
            'cedolino'        => 'Cedolino',
            'attestato_quota' => 'AttestatorQuota',
            'attestato_don'   => 'AttestatoDonazione',
            'conferma_pag'    => 'ConfermaPagemento',
        ];
        $label   = $tipi_label[ $tipo ] ?? 'Documento';
        $nome    = sanitize_title( $record->cognome_nome );
        $nome    = str_replace( '-', '', ucwords( str_replace( '-', ' ', $nome ) ) );
        $nome    = preg_replace( '/[^A-Za-z0-9]/', '', $nome );
        $dt      = date( 'Ymd_His' );
        return "{$nome}_{$label}_{$dt}.pdf";
    }

    // ── Genera + salva + ritorna oggetto PDF ─────────────────────────────────

    /**
     * Genera un PDF, lo salva su disco e nel DB.
     * @return object|false  oggetto PDF dal DB oppure false
     */
    public static function genera( int $adesione_id, string $tipo ): object|false {
        $record = BA_Database::get( $adesione_id );
        if ( ! $record ) return false;

        $html     = self::build_html( $record, $tipo );
        $pdf_data = self::html_to_pdf( $html );

        $dirs     = self::upload_dir();
        // Se mPDF non disponibile, salva come .html (non come .pdf corrotto)
        $nome     = self::nome_file( $record, $tipo );
        if ( $pdf_data === null ) {
            $nome = preg_replace( '/\.pdf$/i', '.html', $nome );
        }
        $percorso  = $dirs['dir'] . $nome;
        $url       = $dirs['url'] . $nome;

        file_put_contents( $percorso, $pdf_data ?? $html );
        $dim = (int) filesize( $percorso );

        $pdf_id = BA_Database::save_pdf( $adesione_id, $tipo, $nome, $percorso, $url, $dim );
        return $pdf_id ? BA_Database::get_pdf( $pdf_id ) : false;
    }

    // ── Build HTML per tipo ───────────────────────────────────────────────────

    public static function build_html( object $record, string $tipo ): string {
        ob_start();
        switch ( $tipo ) {
            case 'cedolino':
                include BA_PLUGIN_DIR . 'templates/pdf-cedolino.php';
                break;
            case 'attestato_quota':
            case 'attestato_don':
                include BA_PLUGIN_DIR . 'templates/pdf-attestato.php';
                break;
            case 'conferma_pag':
                include BA_PLUGIN_DIR . 'templates/pdf-conferma.php';
                break;
        }
        return ob_get_clean();
    }

    // ── Converti HTML → PDF (dompdf, fallback mPDF) ──────────────────────────

    public static function html_to_pdf( string $html ): ?string {
        $autoload = BA_PLUGIN_DIR . 'vendor/autoload.php';
        if ( ! file_exists( $autoload ) ) {
            error_log( '[BA PDF] vendor/autoload.php non trovato in ' . BA_PLUGIN_DIR . 'vendor/' );
            return null;
        }
        require_once $autoload;

        // Rimuove BOM UTF-8
        $html = ltrim( $html, "\xEF\xBB\xBF" );
        if ( ! mb_check_encoding( $html, 'UTF-8' ) ) {
            $html = mb_convert_encoding( $html, 'UTF-8', 'UTF-8' );
        }

        // ── Prova dompdf ─────────────────────────────────────────────────────
        if ( class_exists( 'Dompdf\Dompdf' ) ) {
            try {
                $font_dir = BA_PLUGIN_DIR . 'vendor/dompdf/lib/fonts/';
                $options  = new \Dompdf\Options();
                $options->set( 'defaultFont',     'Helvetica' );
                $options->set( 'isRemoteEnabled', false );
                $options->set( 'tempDir',         sys_get_temp_dir() );
                $options->set( 'fontDir',         $font_dir );
                $options->set( 'fontCache',       $font_dir );
                $options->set( 'chroot',          BA_PLUGIN_DIR );

                $dompdf = new \Dompdf\Dompdf( $options );
                $dompdf->loadHtml( $html, 'UTF-8' );
                $dompdf->setPaper( 'A4', 'portrait' );
                $dompdf->render();
                return $dompdf->output();
            } catch ( \Exception $e ) {
                error_log( '[BA PDF] dompdf eccezione: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
                // cade su mPDF
            }
        }

        // ── Fallback mPDF ─────────────────────────────────────────────────────
        if ( ! class_exists( 'Mpdf\Mpdf' ) ) {
            error_log( '[BA PDF] Nessun motore PDF disponibile (dompdf e mPDF mancanti).' );
            return null;
        }
        $tmp = self::upload_dir()['dir'] . 'tmp/';
        if ( ! file_exists( $tmp ) ) { wp_mkdir_p( $tmp ); }
        if ( ! is_writable( $tmp ) ) { $tmp = sys_get_temp_dir(); }
        try {
            $prev = error_reporting( E_ERROR | E_PARSE );
            $mpdf = new \Mpdf\Mpdf([
                'format'      => 'A4',
                'orientation' => 'P',
                'margin_top'  => 0, 'margin_bottom' => 0,
                'margin_left' => 0, 'margin_right'  => 0,
                'tempDir'     => $tmp,
            ]);
            $mpdf->WriteHTML( $html );
            $pdf = $mpdf->Output( '', 'S' );
            error_reporting( $prev );
            return $pdf;
        } catch ( \Exception $e ) {
            error_reporting( $prev ?? E_ALL );
            error_log( '[BA PDF] mPDF eccezione: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            return null;
        }
    }

    // ── Elimina file fisico ───────────────────────────────────────────────────

    public static function elimina_file( object $pdf ): void {
        if ( ! empty( $pdf->percorso ) && file_exists( $pdf->percorso ) ) {
            @unlink( $pdf->percorso );
        }
    }

    // ── Serve file per download ───────────────────────────────────────────────

    public static function serve( int $pdf_id ): void {
        $pdf = BA_Database::get_pdf( $pdf_id );
        if ( ! $pdf ) wp_die( __( 'File non trovato.', 'botega-adesioni' ) );

        if ( ! file_exists( $pdf->percorso ) ) {
            // Rigenera al volo
            $record = BA_Database::get( $pdf->adesione_id );
            if ( $record ) {
                $html = self::build_html( $record, $pdf->tipo );
                $data = self::html_to_pdf( $html ) ?? $html;
                file_put_contents( $pdf->percorso, $data );
            } else {
                wp_die( __( 'File non trovato e impossibile rigenerarlo.', 'botega-adesioni' ) );
            }
        }

        $mime = ( substr( strtolower( $pdf->nome_file ), -4 ) === '.pdf' ) ? 'application/pdf' : 'text/html';
        header( 'Content-Type: ' . $mime );
        header( 'Content-Disposition: attachment; filename="' . $pdf->nome_file . '"' );
        header( 'Content-Length: ' . filesize( $pdf->percorso ) );
        readfile( $pdf->percorso );
        exit;
    }

    // ── Helper logo ───────────────────────────────────────────────────────────

    public static function logo_b64(): string {
        $path = BA_PLUGIN_DIR . 'assets/img/logo.png';
        return file_exists( $path )
            ? 'data:image/png;base64,' . base64_encode( file_get_contents( $path ) )
            : '';
    }

    public static function fmt_chf( float $amount ): string {
        return BA_Database::fmt_chf( $amount );
    }

    // ── Genera QR code Swiss SPC come base64 PNG ─────────────────────────────

    public static function qr_code_b64( object $record ): string {
        $lib = BA_PLUGIN_DIR . 'vendor/phpqrcode/phpqrcode.php';
        if ( ! file_exists( $lib ) ) return '';

        require_once $lib;
        if ( ! class_exists( 'QRcode' ) ) return '';

        // Pulisce stringa per SPC: tronca a max N chars, rimuove solo \r \n \t
        $clean = fn( string $s, int $max = 70 ) =>
            mb_substr( preg_replace( '/[\r\n\t]/', ' ', trim( $s ) ), 0, $max );

        // Calcola importo (fallback da quota+donazione se importo_totale=0)
        $_totale = (float) $record->importo_totale;
        if ( $_totale <= 0 ) {
            $_totale = ( ! empty( $record->quota_250 ) ? 250.00 : 0.0 )
                      + (float) ( $record->donazione ?? 0 );
        }
        // Importo: vuoto se 0, altrimenti "250.00" (no separatore migliaia)
        $imp = $_totale > 0 ? number_format( $_totale, 2, '.', '' ) : '';

        // Dati creditore
        $iban = 'CH4880808003701046947';

        // Dati debitore (tipo K = Kombiniert: riga1=via+civico, riga2=CAP+città)
        $deb_nome = $clean( trim( $record->nome_versamento ?: $record->cognome_nome ), 70 );
        $deb_adr1 = $clean( trim( $record->ind_versamento  ?: ( $record->indirizzo ?? '' ) ), 70 );
        $deb_adr2 = $clean( trim(
            ( $record->cap_versamento
                ? $record->cap_versamento . ' ' . ( $record->localita ?? '' )
                : ( ( $record->cap ?? '' ) . ' ' . ( $record->localita ?? '' ) )
            )
        ), 70 );

        // Riferimento strutturato opzionale (max 35 chars, senza spazi)
        $rif = $clean( $record->id_quota ?? '', 35 );

        /*
         * Payload SPC Swiss QR Bill v0200 – SIX Group
         * Obbligatorio: esattamente 31 campi separati da CR+LF
         * Tipo indirizzo K (Kombiniert):
         *   campo addr1 = via + numero civico
         *   campo addr2 = CAP + città
         *   campi PstCd e TwnNm = vuoti
         */
        $lines = [
            'SPC',          // 1  Header
            '0200',         // 2  Version
            '1',            // 3  Coding UTF-8
            $iban,          // 4  IBAN creditore
            // Creditore (tipo K) – campi 5-11
            'K',            // 5  AdrTp
            $clean('Societa cooperativa La Botega da la Lavizzara'), // 6 Name
            $clean('Via Cantonale 6'),    // 7  StrtNmOrAdrLine1
            $clean('6694 Prato-Sornico'), // 8  BldgNbOrAdrLine2
            '',             // 9  PstCd (vuoto per K)
            '',             // 10 TwnNm (vuoto per K)
            'CH',           // 11 Ctry
            // Ultimate Creditor (campi 12-18, tutti vuoti – riservato SIX)
            '',             // 12 AdrTp
            '',             // 13 Name
            '',             // 14 StrtNmOrAdrLine1
            '',             // 15 BldgNbOrAdrLine2
            '',             // 16 PstCd
            '',             // 17 TwnNm
            '',             // 18 Ctry
            // Importo e valuta – campi 19-20
            $imp,           // 19 Amt
            'CHF',          // 20 Ccy
            // Debitore (tipo K) – campi 21-27
            'K',            // 21 AdrTp
            $deb_nome,      // 22 Name
            $deb_adr1,      // 23 StrtNmOrAdrLine1
            $deb_adr2,      // 24 BldgNbOrAdrLine2
            '',             // 25 PstCd (vuoto per K)
            '',             // 26 TwnNm (vuoto per K)
            'CH',           // 27 Ctry
            // Riferimento – campi 28-29
            'NON',          // 28 RmtInf (NON = nessun riferimento strutturato)
            '',             // 29 Ref (vuoto per NON)
            // Informazioni aggiuntive – campi 30-31
            $clean( 'Adesione - ' . $rif, 140 ), // 30 Ustrd (messaggio libero)
            'EPD',          // 31 Trailer (End Payment Data)
        ];

        // Il payload DEVE terminare con \r\n (requisito SIX)
        $spc = implode( "\r\n", $lines ) . "\r\n";

        try {
            ob_start();
            @QRcode::png( $spc, false, 'M', 10, 2 );
            $png = ob_get_clean();
            if ( ! $png ) return '';

            // Croce svizzera al centro (sfondo bianco + croce nera)
            if ( function_exists( 'imagecreatefromstring' ) ) {
                $img = @imagecreatefromstring( $png );
                if ( $img ) {
                    $w     = imagesx( $img );
                    $sq    = (int) ( $w * 0.11 );
                    $cx    = (int) ( $w / 2 );
                    $cy    = (int) ( $w / 2 );
                    $white = imagecolorallocate( $img, 255, 255, 255 );
                    $black = imagecolorallocate( $img, 0, 0, 0 );

                    imagefilledrectangle( $img, $cx - $sq, $cy - $sq, $cx + $sq, $cy + $sq, $white );

                    $hw = (int) ( $sq * 0.60 );
                    $hh = (int) ( $sq * 0.22 );
                    $vw = (int) ( $sq * 0.22 );
                    $vh = (int) ( $sq * 0.60 );
                    imagefilledrectangle( $img, $cx - $hw, $cy - $hh, $cx + $hw, $cy + $hh, $black );
                    imagefilledrectangle( $img, $cx - $vw, $cy - $vh, $cx + $vw, $cy + $vh, $black );

                    ob_start();
                    imagepng( $img );
                    $png = ob_get_clean();
                    imagedestroy( $img );
                }
            }

            return $png ? 'data:image/png;base64,' . base64_encode( $png ) : '';
        } catch ( \Exception $e ) {
            if ( ob_get_level() ) ob_end_clean();
            error_log( '[BA PDF] QR code error: ' . $e->getMessage() );
        }
        return '';
    }
}
