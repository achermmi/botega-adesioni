<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_Public
 * Gestisce il form pubblico di iscrizione/donazione tramite shortcode.
 * Shortcode: [ba_modulo_iscrizione]
 */
class BA_Public {

    public static function init(): void {
        add_shortcode( 'ba_modulo_iscrizione', [ __CLASS__, 'shortcode' ] );
        add_action( 'init',                    [ __CLASS__, 'handle_public_post' ] );
        add_action( 'wp_enqueue_scripts',      [ __CLASS__, 'enqueue_assets' ] );
    }

    // ── Enqueue assets pubblici ───────────────────────────────────────────────

    public static function enqueue_assets(): void {
        global $post;
        // Carica solo se la pagina contiene lo shortcode
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'ba_modulo_iscrizione' ) ) {
            return;
        }

        wp_enqueue_style(
            'ba-public',
            BA_PLUGIN_URL . 'assets/css/public.css',
            [],
            BA_VERSION
        );

        // Google Places Autocomplete (chiave API opzionale, funziona senza per CH)
        $maps_key = get_option( 'ba_google_maps_key', '' );
        // Non si carica più il Maps JavaScript SDK: si usa Places API (New) via fetch

        wp_enqueue_script(
            'ba-public',
            BA_PLUGIN_URL . 'assets/js/public.js',
            [ 'jquery' ],
            BA_VERSION,
            true
        );

        wp_localize_script( 'ba-public', 'BA_Public', [
            'nonce'       => wp_create_nonce( 'ba_public_form' ),
            'ajaxurl'     => admin_url( 'admin-ajax.php' ),
            'has_maps'    => ! empty( $maps_key ),
            'maps_key'    => $maps_key,
            'i18n'        => [
                'email_invalid'  => __( 'Inserire un indirizzo e-mail valido.', 'botega-adesioni' ),
                'nome_required'  => __( 'Inserire Cognome e Nome.', 'botega-adesioni' ),
                'quota_required' => __( 'Selezionare almeno la quota associativa o inserire un importo di donazione.', 'botega-adesioni' ),
                'email_required' => __( 'L\'indirizzo e-mail è obbligatorio.', 'botega-adesioni' ),
                'sending'        => __( 'Invio in corso…', 'botega-adesioni' ),
                'success'        => __( '✅ Iscrizione inviata! Controlla la tua email per il cedolino di pagamento.', 'botega-adesioni' ),
            ],
        ]);
    }

    // ── Shortcode ─────────────────────────────────────────────────────────────

    public static function shortcode( array $atts = [] ): string {
        $atts = shortcode_atts([
            'titolo'     => '',
            'sottotitolo'=> '',
        ], $atts );

        $success     = isset( $_GET['ba_iscritto'] ) && $_GET['ba_iscritto'] === '1';
        $email_stato = sanitize_key( $_GET['ba_email_stato'] ?? '' );
        $solo_don    = isset( $_GET['ba_quota'] ) && $_GET['ba_quota'] === '0';
        $error       = isset( $_GET['ba_errore'] ) ? urldecode( sanitize_text_field( $_GET['ba_errore'] ) ) : '';

        ob_start();
        include BA_PLUGIN_DIR . 'templates/public-form.php';
        return ob_get_clean();
    }

    // ── Handler POST pubblico ─────────────────────────────────────────────────

    public static function handle_public_post(): void {
        if ( empty( $_POST['ba_public_action'] ) || $_POST['ba_public_action'] !== 'iscriviti' ) return;
        if ( ! wp_verify_nonce( $_POST['ba_public_nonce'] ?? '', 'ba_public_form' ) ) {
            self::redirect_error( __( 'Sessione scaduta. Riprovare.', 'botega-adesioni' ) );
            return;
        }

        // ── Validazione ────────────────────────────────────────────────────
        $errors = [];

        $cognome_nome = sanitize_text_field( $_POST['cognome_nome'] ?? '' );
        if ( empty( $cognome_nome ) ) {
            $errors[] = __( 'Cognome e nome sono obbligatori.', 'botega-adesioni' );
        }

        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( empty( $email ) ) {
            $errors[] = __( 'L\'indirizzo e-mail è obbligatorio.', 'botega-adesioni' );
        } elseif ( ! is_email( $email ) ) {
            $errors[] = __( 'Indirizzo e-mail non valido.', 'botega-adesioni' );
        } elseif ( ! self::validate_email_domain( $email ) ) {
            $errors[] = __( 'Il dominio dell\'indirizzo e-mail non risulta attivo. Verificare l\'indirizzo inserito.', 'botega-adesioni' );
        }

        $quota_250 = ! empty( $_POST['quota_250'] ) ? 1 : 0;
        $donazione = round( (float) str_replace( "'", '', $_POST['donazione'] ?? '0' ), 2 );
        if ( ! $quota_250 && $donazione <= 0 ) {
            $errors[] = __( 'Selezionare la quota associativa e/o inserire un importo di donazione.', 'botega-adesioni' );
        }

        if ( ! empty( $errors ) ) {
            self::redirect_error( implode( ' | ', $errors ) );
            return;
        }

        // ── Dati record ────────────────────────────────────────────────────
        $data = [
            'cognome_nome'     => $cognome_nome,
            'indirizzo'        => sanitize_text_field( $_POST['indirizzo']   ?? '' ),
            'cap'              => sanitize_text_field( $_POST['cap']         ?? '' ),
            'localita'         => sanitize_text_field( $_POST['localita']    ?? '' ),
            'telefono'         => sanitize_text_field( $_POST['telefono']    ?? '' ),
            'email'            => $email,
            'tipo_socio'       => 'socio_attivo', // default pubblico
            'quota_250'        => $quota_250,
            'donazione'        => $donazione,
            'data_adesione'    => current_time( 'Y-m-d' ),
            'metodo_pagamento' => 'fattura',
            'stato_pagamento'  => 'sospeso',
            'note'             => sanitize_textarea_field( $_POST['note']    ?? '' ),
            'nome_versamento'  => $cognome_nome,
            'ind_versamento'   => sanitize_text_field( $_POST['indirizzo']   ?? '' ),
            'cap_versamento'   => sanitize_text_field( $_POST['cap']         ?? '' ),
        ];

        // ── Salva nel DB ───────────────────────────────────────────────────
        $id = BA_Database::insert( $data );
        if ( ! $id ) {
            self::redirect_error( __( 'Errore durante il salvataggio. Riprovare.', 'botega-adesioni' ) );
            return;
        }

        $record = BA_Database::get( $id );

        // ── Invia cedolino + email iscrizione ──────────────────────────────
        $email_stato = 'non_inviata';
        if ( $record ) {
            $email_stato = BA_Email::invia_cedolino_fattura( $record );
            BA_Database::update( $id, [ 'stato_pagamento' => $email_stato ] );
        }

        // ── Redirect successo ──────────────────────────────────────────────
        // Usa l'URL passato come campo nascosto nel form (più affidabile di wp_get_referer)
        $page_url = esc_url_raw( $_POST['ba_page_url'] ?? '' );
        if ( empty( $page_url ) || ! wp_validate_redirect( $page_url ) ) {
            $page_url = BA_Pages::get_url() ?: home_url();
        }

        $url = add_query_arg([
            'ba_iscritto'    => '1',
            'ba_email_stato' => $email_stato,
            'ba_quota'       => $quota_250 ? '1' : '0',
        ], remove_query_arg( 'ba_errore', $page_url ) );

        wp_safe_redirect( $url );
        exit;
    }

    // ── Validazione dominio email (MX record) ─────────────────────────────────

    private static function validate_email_domain( string $email ): bool {
        if ( ! function_exists( 'checkdnsrr' ) ) return true; // Se non disponibile, skip
        $domain = substr( strrchr( $email, '@' ), 1 );
        if ( empty( $domain ) ) return false;
        // Controlla MX oppure A record
        return checkdnsrr( $domain, 'MX' ) || checkdnsrr( $domain, 'A' );
    }

    private static function redirect_error( string $msg ): void {
        // Prima prova l'URL dalla sessione, poi dalla pagina pubblica
        $page_url = '';
        if ( ! empty( $_POST['ba_page_url'] ) ) {
            $page_url = esc_url_raw( $_POST['ba_page_url'] );
        }
        if ( empty( $page_url ) || ! wp_validate_redirect( $page_url ) ) {
            $page_url = BA_Pages::get_url() ?: home_url();
        }
        $url = add_query_arg( 'ba_errore', urlencode( $msg ), remove_query_arg( 'ba_iscritto', $page_url ) );
        wp_safe_redirect( $url );
        exit;
    }
}
