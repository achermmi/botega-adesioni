<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_Frontend_Admin
 *
 * Espone le pagine amministrative del plugin come shortcode WordPress.
 * Solo gli utenti con la capability ba_gestione_adesioni possono visualizzarle.
 *
 * Shortcode disponibili:
 *  [ba_admin_lista]          – Elenco adesioni
 *  [ba_admin_form]           – Nuova adesione / modifica (accetta ?id=N)
 *  [ba_admin_campi]          – Campi personalizzati
 *  [ba_admin_impostazioni]   – Impostazioni plugin
 *  [ba_admin_ruoli]          – Gestione ruoli e accessi
 *
 * URL context:
 *  Le pagine frontend scoprono automaticamente l'URL corretto tramite un
 *  transient cache. Se uno shortcode non è ancora su nessuna pagina pubblica,
 *  il link punta alla corrispondente pagina WP-Admin.
 */
class BA_Frontend_Admin {

    /**
     * Mappa shortcode → slug pagina WP-Admin.
     *
     * @var array<string,string>
     */
    private static $page_map = [
        'ba_admin_lista'        => 'botega-adesioni',
        'ba_admin_form'         => 'botega-adesioni-new',
        'ba_admin_campi'        => 'botega-adesioni-fields',
        'ba_admin_impostazioni' => 'botega-adesioni-settings',
        'ba_admin_ruoli'        => 'botega-adesioni-roles',
    ];

    // ── Boot ─────────────────────────────────────────────────────────────────

    public static function init(): void {
        // Le shortcode vanno registrate sull'hook 'init' (requisito WordPress)
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ] );

        // Gestione form POST dal frontend (prima del rendering)
        add_action( 'init', [ __CLASS__, 'handle_actions' ], 5 );

        // Export e download PDF su frontend (hook 'wp', dopo query_vars)
        add_action( 'wp', [ __CLASS__, 'handle_early_output' ] );

        // Invalida la cache URL quando vengono salvate/eliminate pagine
        add_action( 'save_post_page', [ __CLASS__, 'invalidate_url_cache' ] );
        add_action( 'delete_post',    [ __CLASS__, 'invalidate_url_cache' ] );

        // Asset su pagine con shortcode admin
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_enqueue_assets' ] );
    }

    /** Registra le shortcode (richiamato sull'hook 'init'). */
    public static function register_shortcodes(): void {
        add_shortcode( 'ba_admin_lista',        [ __CLASS__, 'sc_lista' ] );
        add_shortcode( 'ba_admin_form',         [ __CLASS__, 'sc_form' ] );
        add_shortcode( 'ba_admin_campi',        [ __CLASS__, 'sc_campi' ] );
        add_shortcode( 'ba_admin_impostazioni', [ __CLASS__, 'sc_impostazioni' ] );
        add_shortcode( 'ba_admin_ruoli',        [ __CLASS__, 'sc_ruoli' ] );
    }

    // ── URL discovery ─────────────────────────────────────────────────────────

    /**
     * Restituisce l'URL corretto per lo shortcode indicato.
     * Se esiste una pagina pubblica con quello shortcode usa il suo permalink;
     * altrimenti usa la pagina WP-Admin corrispondente.
     *
     * @param string $shortcode  Chiave da self::$page_map (es. 'ba_admin_lista')
     * @param array  $args       Query args aggiuntivi
     */
    public static function get_url( string $shortcode, array $args = [] ): string {
        $urls       = self::discover_urls();
        $admin_slug = self::$page_map[ $shortcode ] ?? '';
        $base       = $urls[ $shortcode ] ?? ( $admin_slug ? admin_url( "admin.php?page={$admin_slug}" ) : home_url( '/' ) );

        return $args ? add_query_arg( $args, $base ) : $base;
    }

    /**
     * Scopre quali pagine WordPress contengono i nostri shortcode.
     * Il risultato è cachato per un'ora.
     *
     * @return array<string,string>  shortcode → permalink
     */
    private static function discover_urls(): array {
        $cached = get_transient( 'ba_fe_shortcode_urls' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $urls  = [];
        $pages = get_posts( [
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
        ] );

        foreach ( $pages as $page ) {
            foreach ( array_keys( self::$page_map ) as $sc ) {
                if ( ! isset( $urls[ $sc ] ) && has_shortcode( $page->post_content, $sc ) ) {
                    $urls[ $sc ] = get_permalink( $page->ID );
                }
            }
        }

        set_transient( 'ba_fe_shortcode_urls', $urls, HOUR_IN_SECONDS );
        return $urls;
    }

    public static function invalidate_url_cache(): void {
        delete_transient( 'ba_fe_shortcode_urls' );
    }

    // ── Asset ─────────────────────────────────────────────────────────────────

    public static function maybe_enqueue_assets(): void {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) return;

        $has = false;
        foreach ( array_keys( self::$page_map ) as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) {
                $has = true;
                break;
            }
        }
        if ( ! $has ) return;

        // Dashicons (usati da WP core sulla pagina, già presenti in admin)
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style(
            'ba-admin',
            BA_PLUGIN_URL . 'assets/css/admin.css',
            [ 'dashicons' ],
            BA_VERSION
        );
        wp_enqueue_script(
            'ba-admin',
            BA_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            BA_VERSION,
            true
        );
        wp_localize_script( 'ba-admin', 'BA', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ba_nonce' ),
            'i18n'    => [
                'confirm_delete'   => __( 'Sei sicuro di voler eliminare questo record? L\'operazione non può essere annullata.', 'botega-adesioni' ),
                'confirm_richiamo' => __( 'Inviare email di richiamo a tutti i pagamenti sospesi/inviati selezionati?', 'botega-adesioni' ),
                'deleting'         => __( 'Eliminazione in corso...', 'botega-adesioni' ),
                'sending'          => __( 'Invio email in corso...', 'botega-adesioni' ),
                'error'            => __( 'Si è verificato un errore. Riprovare.', 'botega-adesioni' ),
            ],
        ] );
    }

    // ── Handler early output (export / PDF download) ──────────────────────────

    /**
     * Intercetta le richieste di export e download PDF sulle pagine frontend
     * con i nostri shortcode. Queste operazioni devono girare prima dell'output.
     */
    public static function handle_early_output(): void {
        if ( is_admin() || ! is_page() ) return;
        if ( ! BA_Roles::current_user_can() ) return;

        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) return;

        // Controlla se la pagina contiene uno shortcode admin
        $has_sc = false;
        foreach ( array_keys( self::$page_map ) as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) {
                $has_sc = true;
                break;
            }
        }
        if ( ! $has_sc ) return;

        // Export (CSV / XLSX / PDF elenco)
        if ( ! empty( $_GET['ba_export'] ) ) {
            BA_Export::handle();
            exit;
        }

        // Download singolo PDF
        if ( ! empty( $_GET['ba_download_pdf'] ) ) {
            BA_Admin::serve_pdf();
            exit;
        }
    }

    // ── Handler form POST ─────────────────────────────────────────────────────

    /**
     * Gestisce i form POST inviati dalle pagine frontend con shortcode admin.
     * Gira sull'hook `init` (priority 5) prima del rendering.
     */
    public static function handle_actions(): void {
        if ( is_admin() ) return;
        if ( empty( $_POST['ba_action'] ) ) return;
        if ( ! BA_Roles::current_user_can() ) return;

        $action = $_POST['ba_action'];

        switch ( $action ) {
            case 'save_record':
                self::handle_save_record();
                break;

            case 'save_settings':
                // Impostazioni: solo amministratori possono salvarle
                if ( current_user_can( 'manage_options' ) ) {
                    BA_Settings::handle_save_frontend();
                }
                break;

            case 'crea_pagina':
                if ( check_admin_referer( 'ba_crea_pagina', 'ba_nonce' ) && BA_Roles::current_user_can() ) {
                    BA_Pages::setup();
                    wp_safe_redirect( self::get_url( 'ba_admin_impostazioni' ) );
                    exit;
                }
                break;
        }

        // Gestione POST ruoli (render_gestione_ruoli usa check_admin_referer internamente)
        // Non serve intercettare qui: viene gestito direttamente in render_gestione_ruoli
    }

    private static function handle_save_record(): void {
        if ( ! check_admin_referer( 'ba_save_record', 'ba_nonce' ) ) return;

        $id   = (int) ( $_POST['record_id'] ?? 0 );
        $data = BA_Admin::sanitize_record( $_POST );

        $stato_precedente = '';
        if ( $id > 0 ) {
            $old              = BA_Database::get( $id );
            $stato_precedente = $old ? $old->stato_pagamento : '';
        }

        if ( $id > 0 ) {
            $ok = BA_Database::update( $id, $data );
        } else {
            $id = BA_Database::insert( $data );
            $ok = (bool) $id;
        }

        if ( $ok && $id ) {
            // Salva campi custom
            if ( ! empty( $_POST['custom_fields'] ) && is_array( $_POST['custom_fields'] ) ) {
                $cv = array_map( 'sanitize_text_field', $_POST['custom_fields'] );
                BA_Custom_Fields::save_values( $id, $cv );
            }

            $record       = BA_Database::get( $id );
            $is_new       = ( $stato_precedente === '' );
            $nuovo_stato  = $data['stato_pagamento'];

            $diventa_pagato   = $nuovo_stato === 'pagato'         && $stato_precedente !== 'pagato';
            $diventa_onorario = $nuovo_stato === 'socio_onorario' && $stato_precedente !== 'socio_onorario';

            if ( $record ) {
                // Invia cedolino fattura
                if (
                    $data['metodo_pagamento'] === 'fattura' &&
                    in_array( $nuovo_stato, [ 'sospeso', 'inviato' ], true ) &&
                    ( $is_new || ( $stato_precedente === 'sospeso' && ! empty( $_POST['ba_invia_cedolino'] ) ) )
                ) {
                    $stato_email = BA_Email::invia_cedolino_fattura( $record );
                    BA_Database::update( $id, [ 'stato_pagamento' => $stato_email ] );
                    BA_Admin::set_notice(
                        $stato_email === 'errore_invio' ? 'error' : 'success',
                        $stato_email === 'errore_invio'
                            ? __( '⚠️ Record salvato ma l\'email con cedolino non è stata inviata.', 'botega-adesioni' )
                            : __( '✅ Record salvato e cedolino inviato via email.', 'botega-adesioni' )
                    );
                    wp_safe_redirect( self::get_url( 'ba_admin_form', [ 'id' => $id ] ) );
                    exit;
                }

                // Documenti post-pagamento
                if ( $diventa_pagato || $diventa_onorario ) {
                    BA_Email::invia_documenti_post_pagamento( $record, $nuovo_stato );
                }
            }

            BA_Admin::set_notice( 'success', __( 'Record salvato con successo.', 'botega-adesioni' ) );
            wp_safe_redirect( self::get_url( 'ba_admin_lista', [ 'ba_saved' => '1' ] ) );
            exit;
        }

        BA_Admin::set_notice( 'error', __( 'Errore durante il salvataggio.', 'botega-adesioni' ) );
        wp_safe_redirect( self::get_url( 'ba_admin_form', $id ? [ 'id' => $id ] : [] ) );
        exit;
    }

    // ── Messaggio di accesso negato ───────────────────────────────────────────

    private static function access_denied(): string {
        if ( ! is_user_logged_in() ) {
            return '<p class="ba-access-denied">' .
                sprintf(
                    /* translators: %s: login URL */
                    wp_kses(
                        __( 'Devi <a href="%s">effettuare il login</a> per accedere a questa pagina.', 'botega-adesioni' ),
                        [ 'a' => [ 'href' => [] ] ]
                    ),
                    esc_url( wp_login_url( get_permalink() ) )
                ) .
                '</p>';
        }
        return '<p class="ba-access-denied">' .
            esc_html__( 'Accesso riservato. Il tuo account non ha i permessi necessari.', 'botega-adesioni' ) .
            '</p>';
    }

    // ── Shortcode helpers ─────────────────────────────────────────────────────

    /** Visualizza un notice eventualmente salvato in transient */
    private static function render_notice(): string {
        $notice = get_transient( 'ba_notice_' . get_current_user_id() );
        if ( ! $notice ) return '';
        delete_transient( 'ba_notice_' . get_current_user_id() );
        return sprintf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $notice['type'] ),
            esc_html( $notice['msg'] )
        );
    }

    // ── Shortcode: Lista adesioni ─────────────────────────────────────────────

    public static function sc_lista( $atts = [] ): string {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '';
        }

        if ( ! BA_Roles::current_user_can() ) {
            return self::access_denied();
        }

        ob_start();
        echo self::render_notice(); // phpcs:ignore WordPress.Security.EscapeOutput
        BA_List_Table::render();
        return ob_get_clean();
    }

    // ── Shortcode: Form nuova / modifica adesione ─────────────────────────────

    public static function sc_form( $atts = [] ): string {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '';
        }

        if ( ! BA_Roles::current_user_can() ) {
            return self::access_denied();
        }

        $id = (int) ( $_GET['id'] ?? 0 );

        ob_start();
        echo self::render_notice(); // phpcs:ignore WordPress.Security.EscapeOutput
        BA_Record_Form::render( $id );
        return ob_get_clean();
    }

    // ── Shortcode: Campi personalizzati ──────────────────────────────────────

    public static function sc_campi( $atts = [] ): string {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '';
        }

        if ( ! BA_Roles::current_user_can() ) {
            return self::access_denied();
        }

        ob_start();
        include BA_PLUGIN_DIR . 'templates/page-fields.php';
        return ob_get_clean();
    }

    // ── Shortcode: Impostazioni ───────────────────────────────────────────────

    public static function sc_impostazioni( $atts = [] ): string {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '';
        }

        if ( ! BA_Roles::current_user_can() ) {
            return self::access_denied();
        }

        if ( ! function_exists( 'submit_button' ) ) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        ob_start();
        echo self::render_notice(); // phpcs:ignore WordPress.Security.EscapeOutput
        include BA_PLUGIN_DIR . 'templates/page-settings.php';
        return ob_get_clean();
    }

    // ── Shortcode: Ruoli e accessi ────────────────────────────────────────────

    public static function sc_ruoli( $atts = [] ): string {
        // Non renderizzare durante richieste REST API (salvataggio Gutenberg, Yoast SEO, ecc.)
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return '';
        }

        if ( ! BA_Roles::current_user_can() ) {
            return self::access_denied();
        }

        if ( ! function_exists( 'submit_button' ) ) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        ob_start();
        echo '<div class="wrap ba-wrap">';
        echo '<h1>' . esc_html__( 'Ruoli e accessi – Botega Adesioni', 'botega-adesioni' ) . '</h1>';
        BA_Roles::render_gestione_ruoli();
        echo '</div>';
        return ob_get_clean();
    }
}
