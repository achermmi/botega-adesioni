<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_Roles
 *
 * Gestisce il ruolo "Membro Comitato" e la capability "ba_gestione_adesioni".
 *
 * Strategia:
 *  - La capability  `ba_gestione_adesioni` è il gate unico per tutto il plugin.
 *  - Gli Amministratori la ricevono all'attivazione e non la perdono mai.
 *  - Il ruolo `membro_comitato` è creato con questa (e solo questa) capability.
 *  - Alla disinstallazione tutto viene ripulito.
 */
class BA_Roles {

    const CAP  = 'ba_gestione_adesioni';
    const ROLE = 'membro_comitato';

    // ── Attivazione: crea ruolo + assegna cap agli admin ─────────────────────

    public static function setup(): void {
        // 1. Crea il ruolo "Membro Comitato" se non esiste già
        if ( ! get_role( self::ROLE ) ) {
            add_role(
                self::ROLE,
                'Membro Comitato',   // stringa senza __() — troppo presto per i18n
                [
                    self::CAP  => true,
                    'read'     => true,
                ]
            );
        }

        // 2. Assegna la capability agli Amministratori
        $admin_role = get_role( 'administrator' );
        if ( $admin_role && ! $admin_role->has_cap( self::CAP ) ) {
            $admin_role->add_cap( self::CAP );
        }
    }

    // ── Disinstallazione: rimuove ruolo + capability ──────────────────────────

    public static function teardown(): void {
        // Rimuovi il ruolo
        remove_role( self::ROLE );

        // Rimuovi la capability dagli amministratori
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->remove_cap( self::CAP );
        }

        // Rimuovi la capability da tutti gli utenti che la possedevano
        $users = get_users( [ 'capability' => self::CAP ] );
        foreach ( $users as $user ) {
            $user->remove_cap( self::CAP );
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Verifica se l'utente corrente può accedere al plugin.
     */
    public static function current_user_can(): bool {
        return current_user_can( self::CAP );
    }

    /**
     * Restituisce la capability da usare in add_menu_page / check.
     */
    public static function cap(): string {
        return self::CAP;
    }

    /**
     * Aggiunge la pagina di gestione ruoli nella sezione Impostazioni del plugin.
     * Mostra gli utenti con il ruolo Membro Comitato e permette di aggiungerne/rimuoverne.
     */
    public static function render_gestione_ruoli(): void {
        // Gestione POST: aggiungi / rimuovi utente
        if ( isset( $_POST['ba_ruoli_action'] ) && check_admin_referer( 'ba_ruoli', 'ba_ruoli_nonce' ) ) {
            $user_id = (int) ( $_POST['ba_user_id'] ?? 0 );
            $user    = $user_id ? get_user_by( 'id', $user_id ) : null;

            if ( $user ) {
                if ( $_POST['ba_ruoli_action'] === 'aggiungi' ) {
                    $user->add_role( self::ROLE );
                    $user->add_cap( self::CAP );
                    $msg = sprintf(
                        __( '✅ %s aggiunto come Membro Comitato.', 'botega-adesioni' ),
                        esc_html( $user->display_name )
                    );
                } elseif ( $_POST['ba_ruoli_action'] === 'rimuovi' ) {
                    $user->remove_role( self::ROLE );
                    $user->remove_cap( self::CAP );
                    $msg = sprintf(
                        __( '✅ %s rimosso dai Membri Comitato.', 'botega-adesioni' ),
                        esc_html( $user->display_name )
                    );
                }
            }
        }

        // Utenti con ruolo membro_comitato
        $membri = get_users( [ 'role' => self::ROLE ] );

        // Tutti gli utenti (per select aggiungi)
        $tutti = get_users( [
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ] );
        ?>
        <div class="ba-card" style="max-width:800px; margin-top:16px;">
            <h2><?php esc_html_e( 'Gestione Ruolo – Membro Comitato', 'botega-adesioni' ); ?></h2>
            <p class="description" style="margin-bottom:12px;">
                <?php esc_html_e( 'Gli utenti con ruolo "Membro Comitato" possono accedere a tutte le pagine del plugin Botega Adesioni, ma non hanno accesso alle altre impostazioni di WordPress.', 'botega-adesioni' ); ?>
            </p>

            <?php if ( ! empty( $msg ) ) : ?>
            <div class="notice notice-success inline" style="margin-bottom:12px;"><p><?php echo esc_html( $msg ); ?></p></div>
            <?php endif; ?>

            <!-- Aggiungi utente -->
            <form method="post" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
                <?php wp_nonce_field( 'ba_ruoli', 'ba_ruoli_nonce' ); ?>
                <input type="hidden" name="ba_ruoli_action" value="aggiungi">
                <select name="ba_user_id" style="min-width:220px;">
                    <option value=""><?php esc_html_e( '— Seleziona utente —', 'botega-adesioni' ); ?></option>
                    <?php foreach ( $tutti as $u ) :
                        // Salta chi è già amministratore o già membro comitato
                        if ( in_array( self::ROLE, $u->roles, true ) ) continue;
                        if ( in_array( 'administrator', $u->roles, true ) ) continue;
                    ?>
                    <option value="<?php echo $u->ID; ?>">
                        <?php echo esc_html( $u->display_name ); ?> (<?php echo esc_html( $u->user_email ); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button(
                    __( '+ Aggiungi come Membro Comitato', 'botega-adesioni' ),
                    'primary', 'submit', false
                ); ?>
            </form>

            <!-- Lista membri comitato -->
            <h3 style="font-size:13px; margin-bottom:8px;">
                <?php printf(
                    _n( '%d Membro Comitato', '%d Membri Comitato', count( $membri ), 'botega-adesioni' ),
                    count( $membri )
                ); ?>
            </h3>

            <?php if ( empty( $membri ) ) : ?>
            <p style="color:#888; font-style:italic;"><?php esc_html_e( 'Nessun Membro Comitato configurato.', 'botega-adesioni' ); ?></p>
            <?php else : ?>
            <table class="ba-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Nome', 'botega-adesioni' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'botega-adesioni' ); ?></th>
                        <th><?php esc_html_e( 'Username', 'botega-adesioni' ); ?></th>
                        <th><?php esc_html_e( 'Azione', 'botega-adesioni' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $membri as $m ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $m->display_name ); ?></strong></td>
                    <td><?php echo esc_html( $m->user_email ); ?></td>
                    <td><code><?php echo esc_html( $m->user_login ); ?></code></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'ba_ruoli', 'ba_ruoli_nonce' ); ?>
                            <input type="hidden" name="ba_ruoli_action" value="rimuovi">
                            <input type="hidden" name="ba_user_id" value="<?php echo $m->ID; ?>">
                            <?php submit_button(
                                __( '✕ Rimuovi', 'botega-adesioni' ),
                                'small button-link-delete', 'submit', false,
                                [ 'onclick' => 'return confirm("' . esc_js( sprintf(
                                    __( 'Rimuovere %s dal ruolo Membro Comitato?', 'botega-adesioni' ),
                                    $m->display_name
                                ) ) . '")' ]
                            ); ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Note amministratori -->
            <div style="margin-top:16px; padding:8px 12px; background:#f0f4ff; border-left:3px solid #2271b1; font-size:11px; color:#444;">
                <strong><?php esc_html_e( 'Nota:', 'botega-adesioni' ); ?></strong>
                <?php esc_html_e( 'Gli Amministratori di WordPress hanno sempre accesso al plugin. Non è necessario assegnare loro il ruolo Membro Comitato.', 'botega-adesioni' ); ?>
            </div>
        </div>
        <?php
    }
}
