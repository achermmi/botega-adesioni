<?php
/**
 * Disinstallazione plugin – elimina tabelle, opzioni, ruoli.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Rimuovi ruolo e capability
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ba-roles.php';
BA_Roles::teardown();

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ba_pdf" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ba_custom_values" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ba_custom_fields" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ba_adesioni" );

// Rimuovi file upload
$upload_dir = wp_upload_dir();
$ba_dir     = $upload_dir['basedir'] . '/botega-adesioni/';
if ( is_dir( $ba_dir ) ) {
    array_map( 'unlink', glob( $ba_dir . '*.pdf' ) ?: [] );
    array_map( 'unlink', glob( $ba_dir . '*.html' ) ?: [] );
    array_map( 'unlink', glob( $ba_dir . '.htaccess' ) ?: [] );
    array_map( 'unlink', glob( $ba_dir . 'index.php' ) ?: [] );
    @rmdir( $ba_dir );
}

// Rimuovi opzioni
$options = [
    'ba_db_version',
    'ba_email_mittente_nome',   'ba_email_mittente_email',
    'ba_email_cedolino_oggetto','ba_email_cedolino_corpo',
    'ba_email_conferma_oggetto','ba_email_conferma_corpo',
    'ba_email_richiamo_oggetto','ba_email_richiamo_corpo',
    'ba_email_attestato_corpo',
];
foreach ( $options as $opt ) delete_option( $opt );
