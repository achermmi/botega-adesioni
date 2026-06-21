<?php
defined( 'ABSPATH' ) || exit;

class BA_Record_Form {

    public static function render( int $id = 0 ): void {
        // Carica funzioni admin (submit_button ecc.) se non siamo nell'area WP-Admin
        if ( ! function_exists( 'submit_button' ) ) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $record       = $id > 0 ? BA_Database::get( $id ) : null;
        $custom_fields = BA_Custom_Fields::get_all_fields();
        $custom_values = $id > 0 ? BA_Custom_Fields::get_values( $id ) : [];

        $stati  = BA_Database::get_stati();
        $tipi   = BA_Database::get_tipi_socio();
        $metodi = BA_Database::get_metodi_pagamento();

        include BA_PLUGIN_DIR . 'templates/page-form.php';
    }

    public static function field_val( ?object $record, string $field, $default = '' ) {
        if ( ! $record ) return $default;
        return $record->$field ?? $default;
    }

    public static function select_options( array $options, string $selected ): string {
        $html = '';
        foreach ( $options as $val => $label ) {
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $val ),
                selected( $selected, $val, false ),
                esc_html( $label )
            );
        }
        return $html;
    }
}
