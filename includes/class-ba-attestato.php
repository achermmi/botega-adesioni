<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_Attestato
 *
 * Genera l'attestato di membro in HTML/PDF,
 * lo salva nel database e lo invia via email come allegato.
 */
class BA_Attestato {

    // ── Genera e salva attestato ───────────────────────────────────────────────

    /**
     * Punto di ingresso principale.
     * Genera l'attestato, lo salva nel DB e invia l'email.
     *
     * @param  int  $adesione_id
     * @return bool
     */
    public static function genera_e_invia( int $adesione_id ): bool {
        $record = BA_Database::get( $adesione_id );
        if ( ! $record ) return false;

        // Genera HTML dell'attestato
        $html = self::build_html( $record );

        // Prova a generare un PDF vero con mPDF (se disponibile)
        $pdf_data = self::html_to_pdf( $html );

        // Salva nel database
        self::salva_in_db( $adesione_id, $pdf_data, $html );

        // Invia email con attestato allegato
        return BA_Email::invia_attestato( $record, $pdf_data, $html );
    }

    // ── Build HTML attestato ───────────────────────────────────────────────────

    public static function build_html( object $record ): string {
        $tipi  = BA_Database::get_tipi_socio();
        $anno  = $record->data_adesione
            ? date( 'Y', strtotime( $record->data_adesione ) )
            : date( 'Y' );
        $data_fmt = $record->data_adesione
            ? date_i18n( 'd.m.Y', strtotime( $record->data_adesione ) )
            : date_i18n( 'd.m.Y' );
        $tipo_label = $tipi[ $record->tipo_socio ] ?? $record->tipo_socio;

        // Logo base64
        $logo_path = BA_PLUGIN_DIR . 'assets/img/logo.png';
        $logo_b64  = '';
        if ( file_exists( $logo_path ) ) {
            $logo_b64 = 'data:image/png;base64,' . base64_encode( file_get_contents( $logo_path ) );
        }

        $importo_fmt = self::fmt_chf( (float) $record->importo_totale );

        ob_start();
        include BA_PLUGIN_DIR . 'templates/attestato.php';
        return ob_get_clean();
    }

    // ── HTML → PDF ────────────────────────────────────────────────────────────

    private static function html_to_pdf( string $html ): ?string {
        // Prova mPDF (se installato tramite Composer nella cartella del plugin)
        $mpdf_autoload = BA_PLUGIN_DIR . 'vendor/autoload.php';
        if ( file_exists( $mpdf_autoload ) ) {
            require_once $mpdf_autoload;
            if ( class_exists( 'Mpdf\Mpdf' ) ) {
                try {
                    $mpdf = new \Mpdf\Mpdf([
                        'format'         => 'A4',
                        'orientation'    => 'P',
                        'margin_top'     => 0,
                        'margin_bottom'  => 0,
                        'margin_left'    => 0,
                        'margin_right'   => 0,
                    ]);
                    $mpdf->WriteHTML( $html );
                    return $mpdf->Output( '', 'S' ); // Ritorna stringa binaria
                } catch ( \Exception $e ) {
                    // Fallback a HTML
                }
            }
        }

        // Fallback: l'HTML è già un documento valido stampabile
        return null; // null = usa HTML come fallback
    }

    // ── Salvataggio in DB ─────────────────────────────────────────────────────

    private static function salva_in_db( int $adesione_id, ?string $pdf_data, string $html ): void {
        global $wpdb;
        $t = BA_Database::table_adesioni();

        // Salva il percorso del file se riusciamo a scrivere su disco,
        // altrimenti salva l'HTML nel campo attestato_html
        $upload_dir = wp_upload_dir();
        $ba_dir     = $upload_dir['basedir'] . '/botega-adesioni/';
        $ba_url     = $upload_dir['baseurl'] . '/botega-adesioni/';

        if ( ! file_exists( $ba_dir ) ) {
            wp_mkdir_p( $ba_dir );
            // Protegge la cartella
            file_put_contents( $ba_dir . '.htaccess', "Options -Indexes\nDeny from all\n" );
            file_put_contents( $ba_dir . 'index.php', '<?php // silence' );
        }

        $filename  = 'attestato-' . $adesione_id . '-' . date( 'Ymd' ) . '.pdf';
        $filepath  = $ba_dir . $filename;
        $fileurl   = $ba_url . $filename;

        if ( $pdf_data ) {
            // Salva PDF binario su disco
            file_put_contents( $filepath, $pdf_data );
            $wpdb->update( $t, [
                'attestato_pdf_url'  => $fileurl,
                'attestato_html'     => $html,
                'attestato_data'     => current_time( 'mysql' ),
            ], [ 'id' => $adesione_id ] );
        } else {
            // Salva HTML (sarà usato per la stampa)
            $wpdb->update( $t, [
                'attestato_html'     => $html,
                'attestato_data'     => current_time( 'mysql' ),
                'attestato_pdf_url'  => '',
            ], [ 'id' => $adesione_id ] );
        }
    }

    // ── Recupera attestato ────────────────────────────────────────────────────

    public static function get_attestato( int $adesione_id ): ?object {
        $record = BA_Database::get( $adesione_id );
        if ( ! $record ) return null;
        if ( empty( $record->attestato_html ) ) return null;
        return $record;
    }

    /**
     * Serve l'attestato come download PDF o stampa HTML
     */
    public static function serve( int $adesione_id ): void {
        $record = BA_Database::get( $adesione_id );
        if ( ! $record ) wp_die( __( 'Attestato non trovato.', 'botega-adesioni' ) );

        // Se esiste il PDF su disco, servilo
        if ( ! empty( $record->attestato_pdf_url ) ) {
            $upload_dir = wp_upload_dir();
            $filepath   = str_replace(
                $upload_dir['baseurl'],
                $upload_dir['basedir'],
                $record->attestato_pdf_url
            );
            if ( file_exists( $filepath ) ) {
                $nome = 'Attestato-' . sanitize_title( $record->cognome_nome ) . '-' . $record->id_quota . '.pdf';
                header( 'Content-Type: application/pdf' );
                header( "Content-Disposition: attachment; filename=\"$nome\"" );
                header( 'Content-Length: ' . filesize( $filepath ) );
                readfile( $filepath );
                exit;
            }
        }

        // Fallback: genera e serve l'HTML come pagina stampabile
        if ( ! empty( $record->attestato_html ) ) {
            header( 'Content-Type: text/html; charset=utf-8' );
            echo $record->attestato_html;
            echo '<script>window.addEventListener("load",()=>window.print());</script>';
            exit;
        }

        // Rigenera al volo
        $html = self::build_html( $record );
        header( 'Content-Type: text/html; charset=utf-8' );
        echo $html;
        echo '<script>window.addEventListener("load",()=>window.print());</script>';
        exit;
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    public static function fmt_chf( float $amount ): string {
        if ( $amount <= 0 ) return 'CHF 0.00';
        $parts   = number_format( $amount, 2, '.', '' );
        [$int, $dec] = explode( '.', $parts );
        $int_fmt = preg_replace( '/\B(?=(\d{3})+(?!\d))/', "'", $int );
        return "CHF $int_fmt.$dec";
    }
}
