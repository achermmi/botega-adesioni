<?php
/**
 * Plugin Name:       Botega Adesioni
 * Plugin URI:        https://github.com/achermmi/botega-adesioni
 * Description:       Gestione adesione cooperativa La Botega da la Lavizzara. Permette ai potenziali soci di inviare la domanda di adesione direttamente dal sito WordPress.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            La Botega da la Lavizzara
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       botega-adesioni
 * Domain Path:       /languages
 *
 * @package BotegaAdesioni
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BA_VERSION', '1.0.0' );
define( 'BA_PLUGIN_FILE', __FILE__ );
define( 'BA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load the plugin.
 */
function botega_adesioni_init() {
	require_once BA_PLUGIN_DIR . 'includes/class-botega-adesioni.php';
	$plugin = new Botega_Adesioni();
	$plugin->run();
}
add_action( 'plugins_loaded', 'botega_adesioni_init' );

/**
 * Plugin activation hook.
 */
function botega_adesioni_activate() {
	require_once BA_PLUGIN_DIR . 'includes/class-ba-activator.php';
	BA_Activator::activate();
}
register_activation_hook( __FILE__, 'botega_adesioni_activate' );

/**
 * Plugin deactivation hook.
 */
function botega_adesioni_deactivate() {
	require_once BA_PLUGIN_DIR . 'includes/class-ba-activator.php';
	BA_Activator::deactivate();
}
register_deactivation_hook( __FILE__, 'botega_adesioni_deactivate' );
