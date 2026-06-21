<?php
/**
 * Admin-area functionality.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BA_Admin
 */
class BA_Admin {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Valid application statuses.
	 *
	 * @var array
	 */
	private $statuses = array(
		'in_attesa'  => 'In attesa',
		'in_revisione' => 'In revisione',
		'approvata'  => 'Approvata',
		'rifiutata'  => 'Rifiutata',
	);

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register admin menu pages.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Botega Adesioni', 'botega-adesioni' ),
			__( 'Adesioni', 'botega-adesioni' ),
			'manage_options',
			'botega-adesioni',
			array( $this, 'render_admin_page' ),
			'dashicons-groups',
			30
		);

		add_submenu_page(
			'botega-adesioni',
			__( 'Elenco domande', 'botega-adesioni' ),
			__( 'Elenco domande', 'botega-adesioni' ),
			'manage_options',
			'botega-adesioni',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'botega-adesioni',
			__( 'Impostazioni', 'botega-adesioni' ),
			__( 'Impostazioni', 'botega-adesioni' ),
			'manage_options',
			'botega-adesioni-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$allowed_hooks = array(
			'toplevel_page_botega-adesioni',
			'adesioni_page_botega-adesioni-settings',
		);

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			BA_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			BA_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-admin',
			'baAdmin',
			array(
				'confirmDelete' => __( 'Sei sicuro di voler eliminare questa domanda? L\'operazione non è reversibile.', 'botega-adesioni' ),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'ba_admin_nonce' ),
			)
		);
	}

	/**
	 * Dispatch the main admin page to the correct sub-view.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi necessari per accedere a questa pagina.', 'botega-adesioni' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'view' === $action || 'edit' === $action ) {
			$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! $id ) {
				wp_die( esc_html__( 'Domanda non trovata.', 'botega-adesioni' ) );
			}
			$adesione = BA_Database::get_adesione( $id );
			if ( ! $adesione ) {
				wp_die( esc_html__( 'Domanda non trovata.', 'botega-adesioni' ) );
			}
			include BA_PLUGIN_DIR . 'admin/partials/display-detail.php';
		} elseif ( 'delete' === $action ) {
			$this->handle_delete();
		} else {
			include BA_PLUGIN_DIR . 'admin/partials/display-list.php';
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi necessari.', 'botega-adesioni' ) );
		}

		if ( isset( $_POST['ba_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['ba_settings_nonce'] ), 'ba_save_settings' ) ) {
			$this->save_settings();
		}

		include BA_PLUGIN_DIR . 'admin/partials/display-settings.php';
	}

	/**
	 * Save plugin settings from the settings form.
	 */
	private function save_settings() {
		update_option( 'ba_notification_email', sanitize_email( wp_unslash( $_POST['ba_notification_email'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_option( 'ba_notify_admin', isset( $_POST['ba_notify_admin'] ) ? '1' : '0' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_option( 'ba_notify_applicant', isset( $_POST['ba_notify_applicant'] ) ? '1' : '0' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_option( 'ba_confirmation_message', sanitize_textarea_field( wp_unslash( $_POST['ba_confirmation_message'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Impostazioni salvate.', 'botega-adesioni' ) . '</p></div>';
		} );
	}

	/**
	 * Handle application status update (form POST).
	 */
	public function handle_update_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permesso negato.', 'botega-adesioni' ) );
		}

		if ( ! isset( $_POST['ba_update_status_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ba_update_status_nonce'] ), 'ba_update_status' ) ) {
			wp_die( esc_html__( 'Verifica di sicurezza fallita.', 'botega-adesioni' ) );
		}

		$id         = absint( $_POST['id'] ?? 0 );
		$new_stato  = sanitize_key( $_POST['stato'] ?? '' );
		$note_admin = sanitize_textarea_field( wp_unslash( $_POST['note_admin'] ?? '' ) );

		if ( ! $id || ! array_key_exists( $new_stato, $this->statuses ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni&error=invalid' ) );
			exit;
		}

		$adesione = BA_Database::get_adesione( $id );

		if ( ! $adesione ) {
			wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni&error=notfound' ) );
			exit;
		}

		$old_stato = $adesione->stato;

		BA_Database::update_adesione( $id, array(
			'stato'      => $new_stato,
			'note_admin' => $note_admin,
		) );

		// Notify applicant if status changed to approved or rejected.
		if ( $old_stato !== $new_stato ) {
			$adesione->stato     = $new_stato;
			$adesione->note_admin = $note_admin;
			BA_Email::notify_status_change( $adesione, $old_stato );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni&action=view&id=' . $id . '&updated=1' ) );
		exit;
	}

	/**
	 * Handle deletion of an application.
	 */
	private function handle_delete() {
		$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni&error=invalid' ) );
			exit;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'ba_delete_' . $id ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Verifica di sicurezza fallita.', 'botega-adesioni' ) );
		}

		BA_Database::delete_adesione( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=botega-adesioni&deleted=1' ) );
		exit;
	}

	/**
	 * Handle CSV export.
	 */
	public function handle_export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permesso negato.', 'botega-adesioni' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'ba_export_csv' ) ) {
			wp_die( esc_html__( 'Verifica di sicurezza fallita.', 'botega-adesioni' ) );
		}

		$stato = isset( $_GET['stato'] ) ? sanitize_key( $_GET['stato'] ) : '';
		$rows  = BA_Database::get_all_for_export( $stato );

		$filename = 'adesioni-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8 compatibility.
		fputs( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fputs

		// Header row.
		fputcsv( $output, array(
			'ID',
			'Nome',
			'Cognome',
			'Data nascita',
			'Luogo nascita',
			'Codice fiscale',
			'Indirizzo',
			'CAP',
			'Città',
			'Provincia',
			'Email',
			'Telefono',
			'Motivazione',
			'Statuto accettato',
			'Privacy accettata',
			'Stato',
			'Note admin',
			'IP',
			'Data invio',
		) );

		foreach ( $rows as $row ) {
			fputcsv( $output, array(
				$row['id'],
				$row['nome'],
				$row['cognome'],
				$row['data_nascita'],
				$row['luogo_nascita'],
				$row['codice_fiscale'],
				$row['indirizzo'],
				$row['cap'],
				$row['citta'],
				$row['provincia'],
				$row['email'],
				$row['telefono'],
				$row['motivazione'],
				$row['accetta_statuto'] ? 'Sì' : 'No',
				$row['accetta_privacy'] ? 'Sì' : 'No',
				$row['stato'],
				$row['note_admin'],
				$row['ip_address'],
				$row['data_invio'],
			) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		exit;
	}

	/**
	 * Display admin notices from redirect params.
	 */
	public function admin_notices() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'botega-adesioni' !== $page ) {
			return;
		}

		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Stato aggiornato con successo.', 'botega-adesioni' ) . '</p></div>';
		}

		if ( isset( $_GET['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Domanda eliminata con successo.', 'botega-adesioni' ) . '</p></div>';
		}
	}

	/**
	 * Return statuses array.
	 *
	 * @return array
	 */
	public function get_statuses() {
		return $this->statuses;
	}
}
