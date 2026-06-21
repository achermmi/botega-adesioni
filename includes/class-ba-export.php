<?php
defined( 'ABSPATH' ) || exit;

class BA_Export {

    // ── Entry point ───────────────────────────────────────────────────────────

    public static function handle(): void {
        if ( ! BA_Roles::current_user_can() ) wp_die( __( 'Accesso negato.', 'botega-adesioni' ) );
        if ( ! isset( $_GET['ba_export'] ) ) return;

        $format = sanitize_text_field( $_GET['ba_export'] );

        // Ricostruisce i filtri dalla query string
        $args = self::build_query_args();
        $args['per_page'] = 99999;
        $data = BA_Database::get_all( $args );

        switch ( $format ) {
            case 'csv':  self::export_csv( $data['rows'] );  break;
            case 'xlsx': self::export_xlsx( $data['rows'] ); break;
            case 'pdf':  self::export_pdf( $data['rows'] );  break;
        }
        exit;
    }

    // ── CSV ───────────────────────────────────────────────────────────────────

    private static function export_csv( array $rows ): void {
        $filename = 'adesioni_' . date( 'Ymd_His' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( "Content-Disposition: attachment; filename=\"$filename\"" );
        header( 'Pragma: no-cache' );

        $out = fopen( 'php://output', 'w' );
        // BOM per Excel
        fputs( $out, "\xEF\xBB\xBF" );

        // Intestazioni
        fputcsv( $out, self::get_headers(), ';' );

        $stati  = BA_Database::get_stati();
        $tipi   = BA_Database::get_tipi_socio();
        $metodi = BA_Database::get_metodi_pagamento();

        foreach ( $rows as $r ) {
            fputcsv( $out, self::row_to_array( $r, $stati, $tipi, $metodi ), ';' );
        }
        fclose( $out );
    }

    // ── XLSX (senza librerie esterne, usa XML SpreadsheetML) ──────────────────

    private static function export_xlsx( array $rows ): void {
        $filename = 'adesioni_' . date( 'Ymd_His' ) . '.xlsx';
        header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        header( "Content-Disposition: attachment; filename=\"$filename\"" );
        header( 'Pragma: no-cache' );
        header( 'Cache-Control: must-revalidate' );

        $stati  = BA_Database::get_stati();
        $tipi   = BA_Database::get_tipi_socio();
        $metodi = BA_Database::get_metodi_pagamento();
        $headers = self::get_headers();

        echo self::build_xlsx( $headers, $rows, $stati, $tipi, $metodi );
    }

    private static function build_xlsx( array $headers, array $rows, array $stati, array $tipi, array $metodi ): string {
        $sheet_rows = '';

        // Header row
        $cells = '';
        $col   = 0;
        foreach ( $headers as $h ) {
            $cells .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . self::xe( $h ) . '</Data></Cell>';
            $col++;
        }
        $sheet_rows .= "<Row>$cells</Row>\n";

        // Data rows
        foreach ( $rows as $r ) {
            $arr   = self::row_to_array( $r, $stati, $tipi, $metodi );
            $cells = '';
            foreach ( $arr as $val ) {
                $type = is_numeric( $val ) ? 'Number' : 'String';
                $cells .= "<Cell><Data ss:Type=\"$type\">" . self::xe( (string) $val ) . '</Data></Cell>';
            }
            $sheet_rows .= "<Row>$cells</Row>\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel">
<Styles>
 <Style ss:ID="header">
  <Font ss:Bold="1"/>
  <Interior ss:Color="#2C3A00" ss:Pattern="Solid"/>
  <Font ss:Color="#D4E000" ss:Bold="1"/>
 </Style>
</Styles>
<Worksheet ss:Name="Adesioni">
<Table>
' . $sheet_rows . '
</Table>
</Worksheet>
</Workbook>';

        return $xml;
    }

    // ── PDF (HTML→CSS print, no librerie) ─────────────────────────────────────

    private static function export_pdf( array $rows ): void {
        $filename = 'adesioni_' . date( 'Ymd_His' );

        $stati  = BA_Database::get_stati();
        $tipi   = BA_Database::get_tipi_socio();
        $metodi = BA_Database::get_metodi_pagamento();
        $headers = self::get_headers();

        $html = self::build_pdf_html( $headers, $rows, $stati, $tipi, $metodi );

        // Se disponibile mPDF o TCPDF usa quello, altrimenti HTML stampabile
        if ( class_exists( 'Mpdf\Mpdf' ) ) {
            $mpdf = new \Mpdf\Mpdf([
                'orientation' => 'L',
                'format'      => 'A4',
                'margin_top'  => 10, 'margin_bottom' => 10,
                'margin_left' => 8,  'margin_right'  => 8,
            ]);
            $mpdf->WriteHTML( $html );
            $mpdf->Output( $filename . '.pdf', 'D' );
        } else {
            // Fallback: HTML con @media print
            header( 'Content-Type: text/html; charset=utf-8' );
            header( "Content-Disposition: inline; filename=\"$filename.html\"" );
            echo $html . '<script>window.onload=function(){window.print();}</script>';
        }
        exit;
    }

    private static function build_pdf_html( array $headers, array $rows, array $stati, array $tipi, array $metodi ): string {
        $date = date_i18n( 'd.m.Y H:i' );
        $th   = '';
        foreach ( $headers as $h ) $th .= '<th>' . esc_html( $h ) . '</th>';

        $tbody = '';
        foreach ( $rows as $r ) {
            $arr = self::row_to_array( $r, $stati, $tipi, $metodi );
            $tr  = '';
            foreach ( $arr as $v ) $tr .= '<td>' . esc_html( (string) $v ) . '</td>';
            $tbody .= "<tr>$tr</tr>";
        }

        return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">
<title>' . esc_html__( 'Elenco Adesioni', 'botega-adesioni' ) . '</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 7pt; color: #111; }
h1  { font-size: 11pt; margin-bottom: 4px; color: #2c3a00; }
.sub { font-size: 7pt; color: #666; margin-bottom: 8px; }
table { width: 100%; border-collapse: collapse; }
th { background: #2c3a00; color: #d4e000; font-size: 7pt; padding: 3px 4px; text-align: left; white-space: nowrap; }
td { border-bottom: 1px solid #e8e8e8; padding: 2px 4px; font-size: 6.5pt; }
tr:nth-child(even) td { background: #f8f8f8; }
@page { size: A4 landscape; margin: 1cm; }
@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style></head><body>
<h1>' . esc_html__( 'La Botega da la Lavizzara – Elenco Adesioni', 'botega-adesioni' ) . '</h1>
<div class="sub">' . sprintf( esc_html__( 'Stampato il %s – Totale record: %d', 'botega-adesioni' ), $date, count( $rows ) ) . '</div>
<table><thead><tr>' . $th . '</tr></thead><tbody>' . $tbody . '</tbody></table>
</body></html>';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function get_headers(): array {
        return [
            '#',
            __( 'Cognome e nome',       'botega-adesioni' ),
            __( 'Indirizzo',            'botega-adesioni' ),
            __( 'Telefono',             'botega-adesioni' ),
            __( 'E-mail',               'botega-adesioni' ),
            __( 'Tipo socio',           'botega-adesioni' ),
            __( 'Quota 250.00',         'botega-adesioni' ),
            __( 'Donazione',            'botega-adesioni' ),
            __( 'Importo totale',       'botega-adesioni' ),
            __( 'Metodo pagamento',     'botega-adesioni' ),
            __( 'Stato pagamento',      'botega-adesioni' ),
            __( 'Luogo',                'botega-adesioni' ),
            __( 'Data adesione',        'botega-adesioni' ),
            __( 'Data creazione',       'botega-adesioni' ),
        ];
    }

    private static function row_to_array( object $r, array $stati, array $tipi, array $metodi ): array {
        return [
            $r->id,
            $r->cognome_nome,
            $r->indirizzo,
            $r->telefono,
            $r->email,
            $tipi[ $r->tipo_socio ]           ?? $r->tipo_socio,
            $r->quota_250 ? 'CHF 250.00' : '',
            $r->donazione > 0 ? number_format( (float) $r->donazione, 2, '.', "'" ) : '',
            number_format( (float) $r->importo_totale, 2, '.', "'" ),
            $metodi[ $r->metodo_pagamento ]   ?? $r->metodo_pagamento,
            $stati[ $r->stato_pagamento ]     ?? $r->stato_pagamento,
            $r->luogo,
            $r->data_adesione ? date_i18n( 'd.m.Y', strtotime( $r->data_adesione ) ) : '',
            date_i18n( 'd.m.Y H:i', strtotime( $r->data_creazione ) ),
        ];
    }

    private static function xe( string $s ): string {
        return htmlspecialchars( $s, ENT_XML1, 'UTF-8' );
    }

    private static function build_query_args(): array {
        $args = [
            'page'         => max( 1, (int) ( $_GET['paged'] ?? 1 ) ),
            'per_page'     => 50,
            'orderby'      => sanitize_key( $_GET['orderby'] ?? 'data_creazione' ),
            'order'        => sanitize_key( $_GET['order'] ?? 'DESC' ),
            'search'       => sanitize_text_field( $_GET['s'] ?? '' ),
            'filter_logic' => $_GET['filter_logic'] ?? 'AND',
            'filters'      => [],
        ];
        if ( ! empty( $_GET['filters'] ) && is_array( $_GET['filters'] ) ) {
            foreach ( $_GET['filters'] as $f ) {
                $args['filters'][] = [
                    'campo'     => sanitize_key( $f['campo'] ?? '' ),
                    'operatore' => sanitize_text_field( $f['operatore'] ?? '=' ),
                    'valore'    => sanitize_text_field( $f['valore'] ?? '' ),
                ];
            }
        }
        return $args;
    }
}
