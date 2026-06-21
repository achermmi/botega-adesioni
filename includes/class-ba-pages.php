<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_Pages
 * Crea automaticamente la pagina pubblica di iscrizione all'attivazione del plugin.
 * La pagina contiene lo shortcode [ba_modulo_iscrizione].
 */
class BA_Pages {

    const OPTION_PAGE_ID = 'ba_pagina_iscrizione_id';

    // ── Crea pagina pubblica se non esiste ────────────────────────────────────

    public static function setup(): void {
        $existing_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

        // Verifica se la pagina esiste ancora
        if ( $existing_id > 0 && get_post( $existing_id ) ) {
            return; // Già creata
        }

        $page_id = wp_insert_post([
            'post_title'     => __( 'Adesione alla Cooperativa', 'botega-adesioni' ),
            'post_name'      => 'adesione-cooperativa',
            'post_content'   => '<!-- wp:shortcode -->[ba_modulo_iscrizione]<!-- /wp:shortcode -->',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ]);

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( self::OPTION_PAGE_ID, $page_id );
        }
    }

    // ── URL pagina pubblica ───────────────────────────────────────────────────

    public static function get_url(): string {
        $id = (int) get_option( self::OPTION_PAGE_ID, 0 );
        return $id > 0 ? get_permalink( $id ) : home_url( '/adesione-cooperativa/' );
    }

    public static function get_id(): int {
        return (int) get_option( self::OPTION_PAGE_ID, 0 );
    }

    // ── Rimuovi riferimento alla pagina alla disinstallazione ─────────────────
    // (la pagina stessa NON viene eliminata per sicurezza)

    public static function teardown(): void {
        delete_option( self::OPTION_PAGE_ID );
    }
}
