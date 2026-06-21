<?php
/**
 * Core plugin class.
 *
 * Maintains the unique identifier and version, as well as
 * loads all dependencies, defines internationalization, and
 * registers all hooks for the admin and public-facing sides.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Botega_Adesioni
 */
class Botega_Adesioni {

	/**
	 * Plugin unique identifier.
	 *
	 * @var string
	 */
	protected $plugin_name = 'botega-adesioni';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version = BA_VERSION;

	/**
	 * Load dependencies and set locale.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
	}

	/**
	 * Load all required class files.
	 */
	private function load_dependencies() {
		require_once BA_PLUGIN_DIR . 'includes/class-ba-database.php';
		require_once BA_PLUGIN_DIR . 'includes/class-ba-email.php';
		require_once BA_PLUGIN_DIR . 'includes/class-ba-admin.php';
		require_once BA_PLUGIN_DIR . 'includes/class-ba-frontend.php';
	}

	/**
	 * Define the locale for i18n.
	 */
	private function set_locale() {
		add_action(
			'init',
			function() {
				load_plugin_textdomain(
					'botega-adesioni',
					false,
					dirname( BA_PLUGIN_BASENAME ) . '/languages/'
				);
			}
		);
	}

	/**
	 * Register all hooks.
	 */
	public function run() {
		$admin    = new BA_Admin( $this->plugin_name, $this->version );
		$frontend = new BA_Frontend( $this->plugin_name, $this->version );

		// Admin hooks.
		add_action( 'admin_menu', array( $admin, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
		add_action( 'admin_post_ba_update_status', array( $admin, 'handle_update_status' ) );
		add_action( 'admin_post_ba_export_csv', array( $admin, 'handle_export_csv' ) );
		add_action( 'admin_notices', array( $admin, 'admin_notices' ) );

		// Frontend hooks.
		add_shortcode( 'botega_adesione', array( $frontend, 'render_form' ) );
		add_action( 'wp_enqueue_scripts', array( $frontend, 'enqueue_scripts' ) );
		add_action( 'admin_post_nopriv_ba_submit_adesione', array( $frontend, 'handle_form_submission' ) );
		add_action( 'admin_post_ba_submit_adesione', array( $frontend, 'handle_form_submission' ) );
	}
}
