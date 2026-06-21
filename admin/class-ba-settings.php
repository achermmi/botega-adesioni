<?php
defined( 'ABSPATH' ) || exit;

class BA_Settings {

    public static function page(): void {
        include BA_PLUGIN_DIR . 'templates/page-settings.php';
    }

    public static function handle_save(): void {
        if ( ! check_admin_referer( 'ba_save_settings', 'ba_nonce' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $fields = [
            'ba_google_maps_key',
            'ba_email_mittente_nome',
            'ba_email_mittente_email',
            'ba_email_conferma_oggetto',
            'ba_email_conferma_corpo',
            'ba_email_richiamo_oggetto',
            'ba_email_richiamo_corpo',
            'ba_smtp_abilitato',
            'ba_smtp_host',
            'ba_smtp_port',
            'ba_smtp_user',
            'ba_smtp_secure',
        ];

        foreach ( $fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                update_option( $f, sanitize_textarea_field( $_POST[ $f ] ) );
            } elseif ( $f === 'ba_smtp_abilitato' ) {
                update_option( $f, '0' ); // checkbox non spuntata
            }
        }

        // Password SMTP: salva solo se non vuota (evita di sovrascrivere con campo vuoto)
        if ( ! empty( $_POST['ba_smtp_pass'] ) ) {
            update_option( 'ba_smtp_pass', sanitize_text_field( $_POST['ba_smtp_pass'] ) );
        }

        BA_Admin::set_notice( 'success', __( 'Impostazioni salvate.', 'botega-adesioni' ) );
        wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni-settings' ) );
        exit;
    }

    /**
     * Salva le impostazioni dal form frontend (shortcode [ba_admin_impostazioni]).
     * Reindirizza alla pagina frontend delle impostazioni anziché all'admin.
     */
    public static function handle_save_frontend(): void {
        if ( ! check_admin_referer( 'ba_save_settings', 'ba_nonce' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $fields = [
            'ba_google_maps_key',
            'ba_email_mittente_nome',
            'ba_email_mittente_email',
            'ba_email_conferma_oggetto',
            'ba_email_conferma_corpo',
            'ba_email_richiamo_oggetto',
            'ba_email_richiamo_corpo',
            'ba_smtp_abilitato',
            'ba_smtp_host',
            'ba_smtp_port',
            'ba_smtp_user',
            'ba_smtp_secure',
        ];

        foreach ( $fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                update_option( $f, sanitize_textarea_field( $_POST[ $f ] ) );
            } elseif ( $f === 'ba_smtp_abilitato' ) {
                update_option( $f, '0' );
            }
        }

        if ( ! empty( $_POST['ba_smtp_pass'] ) ) {
            update_option( 'ba_smtp_pass', sanitize_text_field( $_POST['ba_smtp_pass'] ) );
        }

        BA_Admin::set_notice( 'success', __( 'Impostazioni salvate.', 'botega-adesioni' ) );
        wp_safe_redirect( BA_Frontend_Admin::get_url( 'ba_admin_impostazioni' ) );
        exit;
    }
}
