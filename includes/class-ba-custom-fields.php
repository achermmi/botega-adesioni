<?php
defined( 'ABSPATH' ) || exit;

class BA_Custom_Fields {

    public static function get_all_fields(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . BA_Database::table_custom_fields() . " WHERE attivo = 1 ORDER BY posizione ASC, id ASC"
        ) ?: [];
    }

    public static function get_field( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . BA_Database::table_custom_fields() . " WHERE id = %d", $id
        ));
    }

    public static function add_field( array $data ): int|false {
        global $wpdb;
        // Genera field_key univoco dalla label
        $key = self::generate_key( $data['label'] );
        $data['field_key'] = $key;
        $result = $wpdb->insert( BA_Database::table_custom_fields(), [
            'field_key'  => $key,
            'label'      => sanitize_text_field( $data['label'] ),
            'field_type' => sanitize_text_field( $data['field_type'] ),
            'options'    => ! empty( $data['options'] ) ? wp_json_encode( $data['options'] ) : null,
            'posizione'  => (int) ( $data['posizione'] ?? 0 ),
            'attivo'     => 1,
        ]);
        return $result ? $wpdb->insert_id : false;
    }

    public static function update_field( int $id, array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            BA_Database::table_custom_fields(),
            [
                'label'      => sanitize_text_field( $data['label'] ),
                'field_type' => sanitize_text_field( $data['field_type'] ),
                'options'    => ! empty( $data['options'] ) ? wp_json_encode( $data['options'] ) : null,
                'posizione'  => (int) ( $data['posizione'] ?? 0 ),
            ],
            [ 'id' => $id ]
        );
    }

    public static function delete_field( int $id ): bool {
        global $wpdb;
        $field = self::get_field( $id );
        if ( ! $field ) return false;
        // Elimina i valori associati
        $wpdb->delete( BA_Database::table_custom_values(), [ 'field_key' => $field->field_key ] );
        return (bool) $wpdb->delete( BA_Database::table_custom_fields(), [ 'id' => $id ] );
    }

    // ── Valori per record ─────────────────────────────────────────────────────

    public static function get_values( int $adesione_id ): array {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT field_key, valore FROM " . BA_Database::table_custom_values() . " WHERE adesione_id = %d",
            $adesione_id
        ));
        $out = [];
        foreach ( $rows as $r ) $out[ $r->field_key ] = $r->valore;
        return $out;
    }

    public static function save_values( int $adesione_id, array $values ): void {
        global $wpdb;
        $t = BA_Database::table_custom_values();
        foreach ( $values as $key => $val ) {
            $key = sanitize_key( $key );
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO $t (adesione_id, field_key, valore)
                 VALUES (%d, %s, %s)
                 ON DUPLICATE KEY UPDATE valore = VALUES(valore)",
                $adesione_id, $key, $val
            ));
        }
    }

    // ── Tipi campi ────────────────────────────────────────────────────────────

    public static function get_field_types(): array {
        return [
            'text'          => __( 'Testo',                        'botega-adesioni' ),
            'date'          => __( 'Data',                         'botega-adesioni' ),
            'datetime'      => __( 'Data e ora',                   'botega-adesioni' ),
            'number_dec'    => __( 'Numerico con 2 decimali',      'botega-adesioni' ),
            'number_int'    => __( 'Numerico senza decimali',      'botega-adesioni' ),
            'checkbox'      => __( 'Casella di spunta (Checkbox)', 'botega-adesioni' ),
            'radio'         => __( 'Scelta multipla (Radio)',      'botega-adesioni' ),
        ];
    }

    // ── Render input HTML ─────────────────────────────────────────────────────

    public static function render_input( object $field, string $value = '' ): string {
        $name = 'custom_fields[' . esc_attr( $field->field_key ) . ']';
        $id   = 'cf_' . esc_attr( $field->field_key );

        switch ( $field->field_type ) {
            case 'date':
                return sprintf(
                    '<input type="date" name="%s" id="%s" value="%s" class="regular-text">',
                    $name, $id, esc_attr( $value )
                );

            case 'datetime':
                return sprintf(
                    '<input type="datetime-local" name="%s" id="%s" value="%s" class="regular-text">',
                    $name, $id, esc_attr( $value )
                );

            case 'number_dec':
                return sprintf(
                    '<input type="number" name="%s" id="%s" value="%s" step="0.01" class="small-text">',
                    $name, $id, esc_attr( $value )
                );

            case 'number_int':
                return sprintf(
                    '<input type="number" name="%s" id="%s" value="%s" step="1" class="small-text">',
                    $name, $id, esc_attr( $value )
                );

            case 'checkbox':
                return sprintf(
                    '<input type="checkbox" name="%s" id="%s" value="1" %s>',
                    $name, $id, checked( $value, '1', false )
                );

            case 'radio':
                $opts = json_decode( $field->options ?? '[]', true ) ?: [];
                $html = '';
                foreach ( $opts as $opt ) {
                    $opt = esc_attr( $opt );
                    $html .= sprintf(
                        '<label><input type="radio" name="%s" value="%s" %s> %s</label><br>',
                        $name, $opt, checked( $value, $opt, false ), $opt
                    );
                }
                return $html;

            default: // text
                return sprintf(
                    '<input type="text" name="%s" id="%s" value="%s" class="regular-text">',
                    $name, $id, esc_attr( $value )
                );
        }
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    private static function generate_key( string $label ): string {
        $key  = sanitize_key( str_replace( ' ', '_', strtolower( $label ) ) );
        $key  = preg_replace( '/[^a-z0-9_]/', '', $key );
        $key  = 'cf_' . $key;
        // Assicura unicità
        global $wpdb;
        $t    = BA_Database::table_custom_fields();
        $base = $key;
        $i    = 1;
        while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE field_key = %s", $key ) ) ) {
            $key = $base . '_' . $i++;
        }
        return $key;
    }
}
