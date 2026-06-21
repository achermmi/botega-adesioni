<?php
/**
 * Frontend functionality: membership application form shortcode.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BA_Frontend
 */
class BA_Frontend {

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
	 * Enqueue public-facing CSS.
	 */
	public function enqueue_scripts() {
		global $post;

		// Only enqueue when the shortcode is present on the page.
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'botega_adesione' ) ) {
			wp_enqueue_style(
				$this->plugin_name,
				BA_PLUGIN_URL . 'public/css/public.css',
				array(),
				$this->version
			);
		}
	}

	/**
	 * Render the membership application form shortcode.
	 *
	 * @param array $atts Shortcode attributes (not used).
	 * @return string HTML output.
	 */
	public function render_form( $atts ) {
		ob_start();
		include BA_PLUGIN_DIR . 'public/partials/form-adesione.php';
		return ob_get_clean();
	}

	/**
	 * Handle form submission via admin-post.
	 */
	public function handle_form_submission() {
		// Verify nonce.
		if ( ! isset( $_POST['ba_form_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ba_form_nonce'] ), 'ba_submit_adesione' ) ) {
			wp_die( esc_html__( 'Verifica di sicurezza fallita. Torna indietro e riprova.', 'botega-adesioni' ) );
		}

		$errors = $this->validate_form( $_POST );

		// Determine redirect URL.
		$redirect_url = isset( $_POST['ba_current_url'] ) ? esc_url_raw( wp_unslash( $_POST['ba_current_url'] ) ) : home_url();
		$redirect_url = remove_query_arg( array( 'ba_success', 'ba_error' ), $redirect_url );

		if ( ! empty( $errors ) ) {
			$redirect_url = add_query_arg( 'ba_error', rawurlencode( implode( '|', $errors ) ), $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Sanitize and insert.
		$data = array(
			'nome'            => sanitize_text_field( wp_unslash( $_POST['ba_nome'] ?? '' ) ),
			'cognome'         => sanitize_text_field( wp_unslash( $_POST['ba_cognome'] ?? '' ) ),
			'data_nascita'    => sanitize_text_field( wp_unslash( $_POST['ba_data_nascita'] ?? '' ) ),
			'luogo_nascita'   => sanitize_text_field( wp_unslash( $_POST['ba_luogo_nascita'] ?? '' ) ),
			'codice_fiscale'  => strtoupper( sanitize_text_field( wp_unslash( $_POST['ba_codice_fiscale'] ?? '' ) ) ),
			'indirizzo'       => sanitize_text_field( wp_unslash( $_POST['ba_indirizzo'] ?? '' ) ),
			'cap'             => sanitize_text_field( wp_unslash( $_POST['ba_cap'] ?? '' ) ),
			'citta'           => sanitize_text_field( wp_unslash( $_POST['ba_citta'] ?? '' ) ),
			'provincia'       => strtoupper( sanitize_text_field( wp_unslash( $_POST['ba_provincia'] ?? '' ) ) ),
			'email'           => sanitize_email( wp_unslash( $_POST['ba_email'] ?? '' ) ),
			'telefono'        => sanitize_text_field( wp_unslash( $_POST['ba_telefono'] ?? '' ) ),
			'motivazione'     => sanitize_textarea_field( wp_unslash( $_POST['ba_motivazione'] ?? '' ) ),
			'accetta_statuto' => isset( $_POST['ba_accetta_statuto'] ) ? 1 : 0,
			'accetta_privacy' => isset( $_POST['ba_accetta_privacy'] ) ? 1 : 0,
			'stato'           => 'in_attesa',
			'ip_address'      => $this->get_client_ip(),
		);

		$id = BA_Database::insert_adesione( $data );

		if ( ! $id ) {
			$redirect_url = add_query_arg( 'ba_error', rawurlencode( __( 'Si è verificato un errore durante l\'invio. Riprova più tardi.', 'botega-adesioni' ) ), $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Send email notifications.
		$adesione     = BA_Database::get_adesione( $id );
		BA_Email::notify_admin( $adesione );
		BA_Email::notify_applicant( $adesione );

		$redirect_url = add_query_arg( 'ba_success', '1', $redirect_url );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Validate form data.
	 *
	 * @param array $data Raw POST data.
	 * @return array Error messages (empty if valid).
	 */
	private function validate_form( array $data ) {
		$errors = array();

		$required_fields = array(
			'ba_nome'           => __( 'Il campo Nome è obbligatorio.', 'botega-adesioni' ),
			'ba_cognome'        => __( 'Il campo Cognome è obbligatorio.', 'botega-adesioni' ),
			'ba_data_nascita'   => __( 'Il campo Data di nascita è obbligatorio.', 'botega-adesioni' ),
			'ba_luogo_nascita'  => __( 'Il campo Luogo di nascita è obbligatorio.', 'botega-adesioni' ),
			'ba_codice_fiscale' => __( 'Il campo Codice fiscale è obbligatorio.', 'botega-adesioni' ),
			'ba_indirizzo'      => __( 'Il campo Indirizzo è obbligatorio.', 'botega-adesioni' ),
			'ba_cap'            => __( 'Il campo CAP è obbligatorio.', 'botega-adesioni' ),
			'ba_citta'          => __( 'Il campo Città è obbligatorio.', 'botega-adesioni' ),
			'ba_email'          => __( 'Il campo Email è obbligatorio.', 'botega-adesioni' ),
			'ba_telefono'       => __( 'Il campo Telefono è obbligatorio.', 'botega-adesioni' ),
		);

		foreach ( $required_fields as $field => $message ) {
			if ( empty( trim( $data[ $field ] ?? '' ) ) ) {
				$errors[] = $message;
			}
		}

		// Validate email.
		if ( ! empty( $data['ba_email'] ) && ! is_email( $data['ba_email'] ) ) {
			$errors[] = __( 'L\'indirizzo email non è valido.', 'botega-adesioni' );
		}

		// Validate fiscal code format (basic 16-char alphanumeric).
		if ( ! empty( $data['ba_codice_fiscale'] ) && ! preg_match( '/^[A-Z0-9]{16}$/i', trim( $data['ba_codice_fiscale'] ) ) ) {
			$errors[] = __( 'Il codice fiscale non è valido (deve essere di 16 caratteri alfanumerici).', 'botega-adesioni' );
		}

		// Validate Italian CAP (5 digits).
		if ( ! empty( $data['ba_cap'] ) && ! preg_match( '/^\d{5}$/', trim( $data['ba_cap'] ) ) ) {
			$errors[] = __( 'Il CAP deve essere composto da 5 cifre.', 'botega-adesioni' );
		}

		// Check mandatory checkboxes.
		if ( ! isset( $data['ba_accetta_statuto'] ) ) {
			$errors[] = __( 'Devi accettare lo statuto della cooperativa per procedere.', 'botega-adesioni' );
		}

		if ( ! isset( $data['ba_accetta_privacy'] ) ) {
			$errors[] = __( 'Devi accettare l\'informativa sulla privacy per procedere.', 'botega-adesioni' );
		}

		return $errors;
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		// Use REMOTE_ADDR as the primary source to avoid header spoofing.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}
}
