<?php
/**
 * Plugin activation and deactivation.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BA_Activator
 */
class BA_Activator {

	/**
	 * Run on plugin activation: create database table and default options.
	 */
	public static function activate() {
		require_once BA_PLUGIN_DIR . 'includes/class-ba-database.php';
		BA_Database::create_table();

		// Set default options.
		if ( ! get_option( 'ba_notification_email' ) ) {
			update_option( 'ba_notification_email', get_option( 'admin_email' ) );
		}
		if ( false === get_option( 'ba_notify_admin' ) ) {
			update_option( 'ba_notify_admin', '1' );
		}
		if ( false === get_option( 'ba_notify_applicant' ) ) {
			update_option( 'ba_notify_applicant', '1' );
		}
		if ( ! get_option( 'ba_confirmation_message' ) ) {
			update_option(
				'ba_confirmation_message',
				__( 'Grazie per aver inviato la tua domanda di adesione. Sarai contattato a breve.', 'botega-adesioni' )
			);
		}
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate() {
		// Nothing to do on deactivation.
	}
}
