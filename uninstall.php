<?php
/**
 * Uninstall script – runs when the plugin is deleted from WordPress admin.
 *
 * Removes all plugin data: custom database table and options.
 *
 * @package BotegaAdesioni
 */

// Block direct calls and ensure this is a legitimate uninstall request.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the database class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ba-database.php';

// Drop the custom table.
BA_Database::drop_table();

// Remove plugin options.
$options = array(
	'ba_notification_email',
	'ba_notify_admin',
	'ba_notify_applicant',
	'ba_confirmation_message',
	'ba_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
