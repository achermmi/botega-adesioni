<?php
defined( 'ABSPATH' ) || exit;

class BA_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_init',            [ __CLASS__, 'handle_actions' ] );
        add_action( 'wp_ajax_ba_delete_record',    [ __CLASS__, 'ajax_delete' ] );
        add_action( 'wp_ajax_ba_add_custom_field', [ __CLASS__, 'ajax_add_custom_field' ] );
        add_action( 'wp_ajax_ba_del_custom_field', [ __CLASS__, 'ajax_del_custom_field' ] );
        add_action( 'wp_ajax_ba_richiamo_massivo', [ __CLASS__, 'ajax_richiamo_massivo' ] );
        add_action( 'wp_ajax_ba_genera_pdf',       [ __CLASS__, 'ajax_genera_pdf' ] );
        add_action( 'wp_ajax_ba_delete_pdf',       [ __CLASS__, 'ajax_delete_pdf' ] );
        add_action( 'wp_ajax_ba_rename_pdf',       [ __CLASS__, 'ajax_rename_pdf' ] );
        add_action( 'wp_ajax_ba_invia_cedolino',   [ __CLASS__, 'ajax_invia_cedolino' ] );
        add_action( 'admin_notices',               [ __CLASS__, 'show_notices' ] );

        // Serve PDF download
        if ( isset( $_GET['ba_download_pdf'] ) ) {
            add_action( 'admin_init', [ __CLASS__, 'serve_pdf' ] );
        }
        // Export handler
        if ( isset( $_GET['page'], $_GET['ba_export'] ) && strpos( $_GET['page'], 'botega' ) !== false ) {
            add_action( 'admin_init', [ 'BA_Export', 'handle' ] );
        }
    }

    // ── Menu ─────────────────────────────────────────────────────────────────

    public static function register_menus(): void {
        add_menu_page(
            __( 'Botega Adesioni', 'botega-adesioni' ),
            __( 'Adesioni',        'botega-adesioni' ),
            BA_Roles::cap(),
            'botega-adesioni',
            [ __CLASS__, 'page_list' ],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'botega-adesioni',
            __( 'Tutte le adesioni', 'botega-adesioni' ),
            __( 'Tutte le adesioni', 'botega-adesioni' ),
            BA_Roles::cap(),
            'botega-adesioni',
            [ __CLASS__, 'page_list' ]
        );

        add_submenu_page(
            'botega-adesioni',
            __( 'Nuova adesione', 'botega-adesioni' ),
            __( '+ Nuova adesione', 'botega-adesioni' ),
            BA_Roles::cap(),
            'botega-adesioni-new',
            [ __CLASS__, 'page_form' ]
        );

        add_submenu_page(
            'botega-adesioni',
            __( 'Campi personalizzati', 'botega-adesioni' ),
            __( 'Campi personalizzati', 'botega-adesioni' ),
            BA_Roles::cap(),
            'botega-adesioni-fields',
            [ __CLASS__, 'page_fields' ]
        );

        add_submenu_page(
            'botega-adesioni',
            __( 'Impostazioni', 'botega-adesioni' ),
            __( 'Impostazioni', 'botega-adesioni' ),
            BA_Roles::cap(),
            'botega-adesioni-settings',
            [ 'BA_Settings', 'page' ]
        );

        add_submenu_page(
            'botega-adesioni',
            __( 'Ruoli e accessi', 'botega-adesioni' ),
            __( '👥 Ruoli e accessi', 'botega-adesioni' ),
            BA_Roles::cap(),
            'botega-adesioni-roles',
            [ __CLASS__, 'page_roles' ]
        );
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'botega' ) === false ) return;

        wp_enqueue_style(
            'ba-admin',
            BA_PLUGIN_URL . 'assets/css/admin.css',
            [],
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
                'confirm_delete'    => __( 'Sei sicuro di voler eliminare questo record? L\'operazione non può essere annullata.', 'botega-adesioni' ),
                'confirm_richiamo'  => __( 'Inviare email di richiamo a tutti i pagamenti sospesi/inviati selezionati?', 'botega-adesioni' ),
                'deleting'          => __( 'Eliminazione in corso...', 'botega-adesioni' ),
                'sending'           => __( 'Invio email in corso...', 'botega-adesioni' ),
                'error'             => __( 'Si è verificato un errore. Riprovare.', 'botega-adesioni' ),
            ],
        ]);
    }

    // ── Action handler ────────────────────────────────────────────────────────

    public static function handle_actions(): void {
        if ( ! BA_Roles::current_user_can() ) return;

        $action = $_POST['ba_action'] ?? $_GET['ba_action'] ?? '';

        switch ( $action ) {
            case 'save_record':
                self::handle_save_record();
                break;
            case 'save_settings':
                BA_Settings::handle_save();
                break;
            case 'crea_pagina':
                if ( check_admin_referer( 'ba_crea_pagina', 'ba_nonce' ) && BA_Roles::current_user_can() ) {
                    BA_Pages::setup();
                    wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni-settings' ) );
                    exit;
                }
                break;
        }
    }

    private static function handle_save_record(): void {
        if ( ! check_admin_referer( 'ba_save_record', 'ba_nonce' ) ) return;

        $id   = (int) ( $_POST['record_id'] ?? 0 );
        $data = self::sanitize_record( $_POST );

        $stato_precedente = '';
        if ( $id > 0 ) {
            $old = BA_Database::get( $id );
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

            $record = BA_Database::get( $id );

            // ── Macchina a stati ────────────────────────────────────────────
            $is_new      = ( $stato_precedente === '' );
            $nuovo_stato = $data['stato_pagamento'];
            $era_pagato  = in_array( $stato_precedente, [ 'pagato', 'socio_onorario' ], true );

            // Transizioni rilevanti (evita di riinviare se già nello stesso stato)
            $diventa_pagato   = $nuovo_stato === 'pagato'         && $stato_precedente !== 'pagato';
            $diventa_onorario = $nuovo_stato === 'socio_onorario' && $stato_precedente !== 'socio_onorario';
            $diventa_pagato_o_onorario = $diventa_pagato || $diventa_onorario;

            if ( $record ) {

                // ── CASO 1: Nuova registrazione o invio cedolino fattura ────
                if (
                    $data['metodo_pagamento'] === 'fattura' &&
                    in_array( $nuovo_stato, [ 'sospeso', 'inviato' ], true ) &&
                    ( $is_new || ( $stato_precedente === 'sospeso' && ! empty( $_POST['ba_invia_cedolino'] ) ) )
                ) {
                    $stato_email = BA_Email::invia_cedolino_fattura( $record );
                    BA_Database::update( $id, [ 'stato_pagamento' => $stato_email ] );
                    if ( $stato_email === 'errore_invio' ) {
                        self::set_notice( 'error', __( '⚠️ Record salvato ma l\'email con cedolino non è stata inviata. Stato aggiornato a "Errore invio". Verificare la configurazione email.', 'botega-adesioni' ) );
                    } else {
                        self::set_notice( 'success', __( '✅ Record salvato e cedolino di pagamento inviato via email.', 'botega-adesioni' ) );
                    }
                    wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni-new&id=' . $id ) );
                    exit;
                }

                // ── CASO 2: Stato → Pagato OR Socio onorario ───────────────
                // Regole punto 5:
                //   • Conferma pagamento  → Pagato OR Socio onorario
                //   • Attestato quota     → Pagato OR Socio onorario (se quota_250 = 1)
                //   • Attestato donazione → solo Pagato (non Socio onorario)
                if ( $diventa_pagato_o_onorario ) {
                    BA_Email::invia_documenti_post_pagamento( $record, $nuovo_stato );
                }
            }

            self::set_notice( 'success', __( 'Record salvato con successo.', 'botega-adesioni' ) );
            wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni&ba_saved=1' ) );
            exit;
        }

        self::set_notice( 'error', __( 'Errore durante il salvataggio.', 'botega-adesioni' ) );
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function page_list(): void {
        BA_List_Table::render();
    }

    public static function page_form(): void {
        $id = (int) ( $_GET['id'] ?? 0 );
        BA_Record_Form::render( $id );
    }

    public static function page_fields(): void {
        include BA_PLUGIN_DIR . 'templates/page-fields.php';
    }

    public static function page_roles(): void {
        echo '<div class="wrap ba-wrap">';
        echo '<h1>' . esc_html__( 'Ruoli e accessi – Botega Adesioni', 'botega-adesioni' ) . '</h1>';
        BA_Roles::render_gestione_ruoli();
        echo '</div>';
    }

    // ── AJAX ─────────────────────────────────────────────────────────────────

    public static function ajax_delete(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error( 'Unauthorized' );

        $id = (int) ( $_POST['id'] ?? 0 );
        if ( BA_Database::delete( $id ) ) {
            wp_send_json_success( [ 'message' => __( 'Record eliminato.', 'botega-adesioni' ) ] );
        } else {
            wp_send_json_error( __( 'Errore durante l\'eliminazione.', 'botega-adesioni' ) );
        }
    }

    public static function ajax_add_custom_field(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();

        $data = [
            'label'      => sanitize_text_field( $_POST['label'] ?? '' ),
            'field_type' => sanitize_text_field( $_POST['field_type'] ?? 'text' ),
            'options'    => ! empty( $_POST['options'] ) ? array_map( 'sanitize_text_field', (array) $_POST['options'] ) : [],
            'posizione'  => (int) ( $_POST['posizione'] ?? 0 ),
        ];

        if ( empty( $data['label'] ) ) {
            wp_send_json_error( __( 'La label è obbligatoria.', 'botega-adesioni' ) );
        }

        $id = BA_Custom_Fields::add_field( $data );
        $id ? wp_send_json_success( [ 'id' => $id, 'message' => __( 'Campo aggiunto.', 'botega-adesioni' ) ] )
            : wp_send_json_error( __( 'Errore durante la creazione del campo.', 'botega-adesioni' ) );
    }

    public static function ajax_del_custom_field(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();
        $id = (int) ( $_POST['id'] ?? 0 );
        BA_Custom_Fields::delete_field( $id )
            ? wp_send_json_success()
            : wp_send_json_error();
    }

    public static function ajax_richiamo_massivo(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();
        $ids    = array_map( 'intval', (array) ( $_POST['ids'] ?? [] ) );
        $result = BA_Email::richiamo_massivo( $ids );
        wp_send_json_success( $result );
    }

    public static function ajax_genera_pdf(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();
        $id   = (int) ( $_POST['id']   ?? 0 );
        $tipo = sanitize_key( $_POST['tipo'] ?? 'cedolino' );
        $pdf  = BA_PDF_Manager::genera( $id, $tipo );
        if ( $pdf ) {
            wp_send_json_success([
                'pdf_id'    => $pdf->id,
                'nome_file' => $pdf->nome_file,
                'url'       => admin_url( 'admin.php?ba_download_pdf=' . $pdf->id ),
                'message'   => __( 'PDF generato con successo.', 'botega-adesioni' ),
            ]);
        } else {
            wp_send_json_error( __( 'Errore nella generazione del PDF.', 'botega-adesioni' ) );
        }
    }

    public static function ajax_delete_pdf(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();
        $pdf_id = (int) ( $_POST['pdf_id'] ?? 0 );
        BA_Database::delete_pdf( $pdf_id )
            ? wp_send_json_success( [ 'message' => __( 'PDF eliminato.', 'botega-adesioni' ) ] )
            : wp_send_json_error( __( 'Errore durante l\'eliminazione.', 'botega-adesioni' ) );
    }

    public static function ajax_rename_pdf(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();
        $pdf_id    = (int) ( $_POST['pdf_id']    ?? 0 );
        $nuovo_nome = sanitize_text_field( $_POST['nuovo_nome'] ?? '' );
        BA_Database::rename_pdf( $pdf_id, $nuovo_nome )
            ? wp_send_json_success( [ 'message' => __( 'File rinominato.', 'botega-adesioni' ) ] )
            : wp_send_json_error( __( 'Errore durante la rinominazione.', 'botega-adesioni' ) );
    }

    public static function ajax_invia_cedolino(): void {
        check_ajax_referer( 'ba_nonce', 'nonce' );
        if ( ! BA_Roles::current_user_can() ) wp_send_json_error();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $record = BA_Database::get( $id );
        if ( ! $record ) { wp_send_json_error( __( 'Record non trovato.', 'botega-adesioni' ) ); return; }
        $nuovo_stato = BA_Email::invia_cedolino_fattura( $record );
        BA_Database::update( $id, [ 'stato_pagamento' => $nuovo_stato ] );
        if ( $nuovo_stato === 'inviato' ) {
            wp_send_json_success( [ 'stato' => $nuovo_stato, 'message' => __( 'Cedolino inviato.', 'botega-adesioni' ) ] );
        } else {
            wp_send_json_error( [ 'stato' => $nuovo_stato, 'message' => __( 'Errore invio email. Stato aggiornato a "Errore invio".', 'botega-adesioni' ) ] );
        }
    }

    public static function serve_pdf(): void {
        if ( ! BA_Roles::current_user_can() ) wp_die( esc_html__( 'Accesso negato.', 'botega-adesioni' ) );
        $pdf_id = (int) ( $_GET['ba_download_pdf'] ?? 0 );
        if ( ! $pdf_id ) wp_die( 'ID non valido.' );
        BA_PDF_Manager::serve( $pdf_id );
    }

    // ── Notices ───────────────────────────────────────────────────────────────

    public static function set_notice( string $type, string $msg ): void {
        set_transient( 'ba_notice_' . get_current_user_id(), [ 'type' => $type, 'msg' => $msg ], 30 );
    }

    public static function show_notices(): void {
        $notice = get_transient( 'ba_notice_' . get_current_user_id() );
        if ( ! $notice ) return;
        delete_transient( 'ba_notice_' . get_current_user_id() );
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $notice['type'] ),
            esc_html( $notice['msg'] )
        );
    }

    // ── Sanitize ─────────────────────────────────────────────────────────────

    private static function sanitize_record( array $post, bool $is_admin = true ): array {
        $data = [
            'cognome_nome'     => sanitize_text_field( $post['cognome_nome']     ?? '' ),
            'indirizzo'        => sanitize_text_field( $post['indirizzo']        ?? '' ),
            'cap'              => sanitize_text_field( $post['cap']              ?? '' ),
            'localita'         => sanitize_text_field( $post['localita']         ?? '' ),
            'telefono'         => sanitize_text_field( $post['telefono']         ?? '' ),
            'email'            => sanitize_email( $post['email']                 ?? '' ),
            'quota_250'        => ! empty( $post['quota_250'] ) ? 1 : 0,
            'donazione'        => round( (float) str_replace( "'", '', $post['donazione'] ?? '0' ), 2 ),
            'data_adesione'    => sanitize_text_field( $post['data_adesione']    ?? '' ) ?: null,
            'metodo_pagamento' => sanitize_key( $post['metodo_pagamento']        ?? 'fattura' ),
            'stato_pagamento'  => sanitize_key( $post['stato_pagamento']         ?? 'sospeso' ),
            'note'             => sanitize_textarea_field( $post['note']         ?? '' ),
            'nome_versamento'  => sanitize_text_field( $post['nome_versamento']  ?? '' ),
            'ind_versamento'   => sanitize_text_field( $post['ind_versamento']   ?? '' ),
            'cap_versamento'   => sanitize_text_field( $post['cap_versamento']   ?? '' ),
        ];
        // tipo_socio solo dall'admin
        if ( $is_admin ) {
            $data['tipo_socio'] = sanitize_key( $post['tipo_socio'] ?? 'socio_attivo' );
        }
        return $data;
    }
}
