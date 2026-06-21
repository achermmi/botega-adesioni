<?php
/**
 * Plugin Name:       Botega Adesioni
 * Plugin URI:        https://labotegalavizzara.ch
 * Description:       Gestione adesioni e donazioni per La Botega da la Lavizzara. Registra soci, pagamenti, donazioni con esportazione e notifiche email.
 * Version:           1.0.0
 * Author:            La Botega da la Lavizzara
 * Author URI:        https://labotegalavizzara.ch
 * License:           GPL v2 or later
 * Text Domain:       botega-adesioni
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

defined( 'ABSPATH' ) || exit;

// ── Costanti ─────────────────────────────────────────────────────────────────
define( 'BA_VERSION',     '1.0.0' );
define( 'BA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'BA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'BA_PLUGIN_FILE', __FILE__ );

// ── Autoload includes ────────────────────────────────────────────────────────
require_once BA_PLUGIN_DIR . 'includes/class-ba-roles.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-pages.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-database.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-pdf-manager.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-email.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-export.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-custom-fields.php';
require_once BA_PLUGIN_DIR . 'includes/class-ba-public.php';
require_once BA_PLUGIN_DIR . 'admin/class-ba-admin.php';
require_once BA_PLUGIN_DIR . 'admin/class-ba-list-table.php';
require_once BA_PLUGIN_DIR . 'admin/class-ba-record-form.php';
require_once BA_PLUGIN_DIR . 'admin/class-ba-settings.php';

// ── Attivazione / Disattivazione ─────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    BA_Database::install();
    BA_Roles::setup();
    // BA_Pages::setup() non può girare qui (permalink non pronti)
    // Viene eseguito al primo caricamento tramite l'hook init
} );
register_deactivation_hook( __FILE__, [ 'BA_Database', 'deactivate' ] );

// ── i18n ─────────────────────────────────────────────────────────────────────
add_action( 'init', function () {
    load_plugin_textdomain(
        'botega-adesioni',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

// ── Boot ─────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
    // Ruoli e capability: nessuna traduzione qui, safe in plugins_loaded
    BA_Roles::setup();
    BA_Admin::init();
    BA_Public::init();
} );

// BA_Pages::setup() richiede il sistema di permalink → deve girare su init
add_action( 'init', function () {
    BA_Pages::setup();
}, 20 );
