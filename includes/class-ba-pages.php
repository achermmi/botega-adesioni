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
        // Prima cerca se esiste già una pagina pubblicata con lo shortcode
        $found = self::find_existing_page();
        if ( $found ) {
            update_option( self::OPTION_PAGE_ID, $found );
            return;
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

    // ── Ricerca una pagina pubblicata che contenga lo shortcode ──────────────

    private static function find_existing_page(): int {
        $pages = get_posts([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        foreach ( $pages as $page ) {
            if ( has_shortcode( $page->post_content, 'ba_modulo_iscrizione' ) ) {
                return $page->ID;
            }
        }
        return 0;
    }

    // ── URL pagina pubblica ───────────────────────────────────────────────────

    public static function get_url(): string {
        $id = (int) get_option( self::OPTION_PAGE_ID, 0 );

        // Verifica che la pagina salvata esista, sia pubblicata e abbia lo shortcode
        if ( $id > 0 ) {
            $page = get_post( $id );
            if ( $page && $page->post_status === 'publish' && has_shortcode( $page->post_content, 'ba_modulo_iscrizione' ) ) {
                return (string) get_permalink( $id );
            }
        }

        // La pagina salvata è errata: cerca quella corretta e aggiorna l'opzione
        $found = self::find_existing_page();
        if ( $found ) {
            update_option( self::OPTION_PAGE_ID, $found );
            return (string) get_permalink( $found );
        }

        return home_url( '/adesione-cooperativa/' );
    }

    public static function get_id(): int {
        // Assicura che l'ID sia aggiornato prima di restituirlo
        self::get_url();
        return (int) get_option( self::OPTION_PAGE_ID, 0 );
    }

    // ── Rimuovi riferimento alla pagina alla disinstallazione ─────────────────
    // (la pagina stessa NON viene eliminata per sicurezza)

    public static function teardown(): void {
        delete_option( self::OPTION_PAGE_ID );
    }
}
