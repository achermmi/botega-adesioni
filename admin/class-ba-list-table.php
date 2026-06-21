<?php
defined( 'ABSPATH' ) || exit;

class BA_List_Table {

    public static function render(): void {
        // Leggi parametri
        $page         = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $per_page     = 25;
        $search       = sanitize_text_field( $_GET['s'] ?? '' );
        $orderby      = sanitize_key( $_GET['orderby'] ?? 'data_creazione' );
        $order        = strtoupper( sanitize_key( $_GET['order'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';
        $filter_logic = in_array( $_GET['filter_logic'] ?? '', [ 'AND', 'OR' ], true ) ? $_GET['filter_logic'] : 'AND';

        // Filtri avanzati
        $filters = [];
        if ( ! empty( $_GET['filters'] ) && is_array( $_GET['filters'] ) ) {
            foreach ( $_GET['filters'] as $f ) {
                if ( ! empty( $f['campo'] ) && ! empty( $f['valore'] ) ) {
                    $filters[] = [
                        'campo'     => sanitize_key( $f['campo'] ),
                        'operatore' => sanitize_text_field( $f['operatore'] ?? '=' ),
                        'valore'    => sanitize_text_field( $f['valore'] ),
                    ];
                }
            }
        }

        // Quick filter stato
        if ( ! empty( $_GET['stato'] ) ) {
            $filters[] = [
                'campo'     => 'stato_pagamento',
                'operatore' => '=',
                'valore'    => sanitize_key( $_GET['stato'] ),
            ];
        }

        $result = BA_Database::get_all([
            'page'         => $page,
            'per_page'     => $per_page,
            'orderby'      => $orderby,
            'order'        => $order,
            'search'       => $search,
            'filters'      => $filters,
            'filter_logic' => $filter_logic,
        ]);

        $stats  = BA_Database::get_stats();
        $stati  = BA_Database::get_stati();
        $tipi   = BA_Database::get_tipi_socio();
        $metodi = BA_Database::get_metodi_pagamento();

        // URL base per export (mantiene filtri correnti)
        $export_base = add_query_arg(
            array_merge( $_GET, [ 'page' => 'botega-adesioni' ] ),
            admin_url( 'admin.php' )
        );

        include BA_PLUGIN_DIR . 'templates/page-list.php';
    }

    public static function sort_url( string $field, string $current_orderby, string $current_order ): string {
        $new_order = ( $current_orderby === $field && $current_order === 'ASC' ) ? 'DESC' : 'ASC';
        return add_query_arg( [ 'orderby' => $field, 'order' => $new_order, 'paged' => 1 ] );
    }

    public static function sort_icon( string $field, string $current_orderby, string $current_order ): string {
        if ( $current_orderby !== $field ) return '<span class="sort-icon">⇅</span>';
        return $current_order === 'ASC'
            ? '<span class="sort-icon active">↑</span>'
            : '<span class="sort-icon active">↓</span>';
    }

    public static function sortable_header( string $field, string $label, string $current_orderby, string $current_order ): void {
        $url  = self::sort_url( $field, $current_orderby, $current_order );
        $icon = self::sort_icon( $field, $current_orderby, $current_order );
        echo '<a href="' . esc_url( $url ) . '" class="ba-sort-link">' . esc_html( $label ) . " $icon</a>";
    }

    public static function get_operators(): array {
        return [
            '='    => __( 'uguale a (=)',           'botega-adesioni' ),
            '!='   => __( 'diverso da (≠)',         'botega-adesioni' ),
            'LIKE' => __( 'contiene',               'botega-adesioni' ),
            '>'    => __( 'maggiore di (>)',        'botega-adesioni' ),
            '<'    => __( 'minore di (<)',          'botega-adesioni' ),
            '>='   => __( 'maggiore o uguale (≥)',  'botega-adesioni' ),
            '<='   => __( 'minore o uguale (≤)',    'botega-adesioni' ),
        ];
    }

    public static function stato_badge( string $stato ): string {
        $classes = [
            'inviato'        => 'ba-badge ba-badge-inviato',
            'sospeso'        => 'ba-badge ba-badge-sospeso',
            'pagato'         => 'ba-badge ba-badge-pagato',
            'socio_onorario' => 'ba-badge ba-badge-onorario',
            'stornato'       => 'ba-badge ba-badge-stornato',
            'annullato'      => 'ba-badge ba-badge-annullato',
            'errore_invio'   => 'ba-badge ba-badge-errore_invio',
        ];
        $label = BA_Database::get_stati()[ $stato ] ?? $stato;
        $class = $classes[ $stato ] ?? 'ba-badge';
        return "<span class=\"$class\">" . esc_html( $label ) . '</span>';
    }

    public static function fmt_chf( float $amount ): string {
        if ( $amount <= 0 ) return '—';
        $parts    = number_format( $amount, 2, '.', '' );
        [ $int, $dec ] = explode( '.', $parts );
        $int_fmt  = preg_replace( '/\B(?=(\d{3})+(?!\d))/', "'", $int );
        return "CHF $int_fmt.$dec";
    }
}
