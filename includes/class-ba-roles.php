<?php
defined( 'ABSPATH' ) || exit;

/**
 * BA_Roles
 *
 * Gestisce i ruoli del plugin e la capability "ba_gestione_adesioni".
 *
 * Ruoli supportati:
 *  - Amministratore (WP built-in)
 *  - Segretariato   (slug: ba_segretariato)
 *  - Comitato       (slug: ba_comitato)
 *  - Cassiere       (slug: ba_cassiere)
 *
 * Strategia:
 *  - La capability `ba_gestione_adesioni` è il gate unico per tutto il plugin.
 *  - Gli Amministratori la ricevono all'attivazione e non la perdono mai.
 *  - I 3 ruoli del plugin vengono creati con read + ba_gestione_adesioni.
 *  - Alla disinstallazione tutto viene ripulito.
 */
class BA_Roles {

    const CAP = 'ba_gestione_adesioni';

    /** @deprecated Mantenuto per compatibilità con installazioni precedenti. */
    const ROLE = 'membro_comitato';

    /**
     * Ruoli del plugin: slug => label (senza __() — usato pre-i18n).
     */
    const ROLES = [
        'ba_segretariato' => 'Segretariato – Adesioni',
        'ba_comitato'     => 'Comitato – Adesioni',
        'ba_cassiere'     => 'Cassiere – Adesioni',
    ];

    // ── Attivazione: crea ruoli + assegna cap agli admin ─────────────────────

    public static function setup(): void {
        // 1. Crea i 3 ruoli del plugin se non esistono, aggiorna etichetta se cambiata
        $wp_roles = wp_roles();
        foreach ( self::ROLES as $slug => $label ) {
            if ( ! get_role( $slug ) ) {
                add_role( $slug, $label, [ self::CAP => true, 'read' => true ] );
            } elseif ( isset( $wp_roles->roles[ $slug ] ) && $wp_roles->roles[ $slug ]['name'] !== $label ) {
                $wp_roles->roles[ $slug ]['name']      = $label;
                $wp_roles->role_objects[ $slug ]->name = $label;
                update_option( $wp_roles->role_key, $wp_roles->roles );
            }
        }

        // 2. Mantieni compatibilità col ruolo legacy
        if ( ! get_role( self::ROLE ) ) {
            add_role( self::ROLE, 'Membro Comitato', [ self::CAP => true, 'read' => true ] );
        }

        // 3. Assegna la capability agli Amministratori
        $admin_role = get_role( 'administrator' );
        if ( $admin_role && ! $admin_role->has_cap( self::CAP ) ) {
            $admin_role->add_cap( self::CAP );
        }
    }

    // ── Disinstallazione: rimuove ruoli + capability ──────────────────────────

    public static function teardown(): void {
        // Rimuovi i ruoli del plugin
        foreach ( array_keys( self::ROLES ) as $slug ) {
            remove_role( $slug );
        }
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

    /** Verifica se l'utente corrente può accedere al plugin. */
    public static function current_user_can(): bool {
        return current_user_can( self::CAP );
    }

    /** Restituisce la capability da usare in add_menu_page / check. */
    public static function cap(): string {
        return self::CAP;
    }

    /** Tutti gli slug dei ruoli del plugin (incluso legacy). */
    public static function all_role_slugs(): array {
        return array_merge( array_keys( self::ROLES ), [ self::ROLE ] );
    }

    // ── Render: gestione ruoli ────────────────────────────────────────────────

    public static function render_gestione_ruoli(): void {
        $msg = '';

        // Gestione POST: aggiungi / rimuovi utente da un ruolo
        if ( isset( $_POST['ba_ruoli_action'] ) && check_admin_referer( 'ba_ruoli', 'ba_ruoli_nonce' ) ) {
            $user_id  = (int) ( $_POST['ba_user_id'] ?? 0 );
            $ba_ruolo = sanitize_key( $_POST['ba_ruolo'] ?? '' );
            $user     = $user_id ? get_user_by( 'id', $user_id ) : null;
            $all_roles = self::ROLES + [ self::ROLE => 'Membro Comitato' ];

            if ( $user && isset( $all_roles[ $ba_ruolo ] ) ) {
                $role_label = $all_roles[ $ba_ruolo ];

                if ( $_POST['ba_ruoli_action'] === 'aggiungi' ) {
                    // Rimuovi eventuali ruoli plugin precedenti, assegna solo quello scelto
                    foreach ( array_keys( $all_roles ) as $r ) {
                        $user->remove_role( $r );
                    }
                    $user->add_role( $ba_ruolo );
                    $user->add_cap( self::CAP );
                    $msg = sprintf(
                        /* translators: 1: display name, 2: role label */
                        __( '✅ %1$s assegnato al ruolo "%2$s".', 'botega-adesioni' ),
                        esc_html( $user->display_name ),
                        esc_html( $role_label )
                    );
                } elseif ( $_POST['ba_ruoli_action'] === 'rimuovi' ) {
                    $user->remove_role( $ba_ruolo );
                    // Rimuovi la cap solo se l'utente non ha altri ruoli plugin
                    $has_other = false;
                    foreach ( array_keys( $all_roles ) as $r ) {
                        if ( $r !== $ba_ruolo && in_array( $r, (array) $user->roles, true ) ) {
                            $has_other = true;
                            break;
                        }
                    }
                    if ( ! $has_other ) {
                        $user->remove_cap( self::CAP );
                    }
                    $msg = sprintf(
                        /* translators: 1: display name, 2: role label */
                        __( '✅ %1$s rimosso dal ruolo "%2$s".', 'botega-adesioni' ),
                        esc_html( $user->display_name ),
                        esc_html( $role_label )
                    );
                }
            }
        }

        // Tutti gli utenti per la select di assegnazione
        $tutti = get_users( [ 'orderby' => 'display_name', 'order' => 'ASC' ] );
        ?>

        <?php if ( $msg ) : ?>
        <div class="notice notice-success inline" style="margin-bottom:16px;"><p><?php echo esc_html( $msg ); ?></p></div>
        <?php endif; ?>

        <!-- Assegna ruolo -->
        <div class="ba-card" style="max-width:860px; margin-top:16px;">
            <h2><?php esc_html_e( 'Assegna ruolo a un utente', 'botega-adesioni' ); ?></h2>
            <p class="description" style="margin-bottom:12px;">
                <?php esc_html_e( 'Gli utenti con questi ruoli possono accedere a tutte le pagine del plugin. Gli Amministratori hanno sempre accesso.', 'botega-adesioni' ); ?>
            </p>
            <form method="post" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <?php wp_nonce_field( 'ba_ruoli', 'ba_ruoli_nonce' ); ?>
                <input type="hidden" name="ba_ruoli_action" value="aggiungi">
                <select name="ba_user_id" style="min-width:220px;">
                    <option value=""><?php esc_html_e( '— Seleziona utente —', 'botega-adesioni' ); ?></option>
                    <?php foreach ( $tutti as $u ) :
                        if ( in_array( 'administrator', (array) $u->roles, true ) ) continue;
                    ?>
                    <option value="<?php echo esc_attr( $u->ID ); ?>">
                        <?php echo esc_html( $u->display_name ); ?> (<?php echo esc_html( $u->user_email ); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="ba_ruolo" style="min-width:160px;">
                    <?php foreach ( self::ROLES as $slug => $label ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button( __( '+ Assegna ruolo', 'botega-adesioni' ), 'primary', 'submit', false ); ?>
            </form>
        </div>

        <!-- Lista utenti per ogni ruolo -->
        <?php foreach ( self::ROLES as $slug => $label ) :
            $membri = get_users( [ 'role' => $slug ] );
        ?>
        <div class="ba-card" style="max-width:860px; margin-top:12px;">
            <h2><?php echo esc_html( $label ); ?></h2>

            <?php if ( empty( $membri ) ) : ?>
            <p style="color:#888; font-style:italic;"><?php esc_html_e( 'Nessun utente con questo ruolo.', 'botega-adesioni' ); ?></p>
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
                            <input type="hidden" name="ba_user_id" value="<?php echo esc_attr( $m->ID ); ?>">
                            <input type="hidden" name="ba_ruolo" value="<?php echo esc_attr( $slug ); ?>">
                            <?php submit_button(
                                __( '✕ Rimuovi', 'botega-adesioni' ),
                                'small button-link-delete', 'submit', false,
                                [ 'onclick' => 'return confirm("' . esc_js( sprintf(
                                    /* translators: 1: display name, 2: role label */
                                    __( 'Rimuovere %1$s dal ruolo %2$s?', 'botega-adesioni' ),
                                    $m->display_name, $label
                                ) ) . '")' ]
                            ); ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:12px; padding:8px 12px; background:#f0f4ff; border-left:3px solid #2271b1; font-size:11px; color:#444; max-width:860px;">
            <strong><?php esc_html_e( 'Nota:', 'botega-adesioni' ); ?></strong>
            <?php esc_html_e( 'Gli Amministratori di WordPress hanno sempre accesso al plugin.', 'botega-adesioni' ); ?>
        </div>
        <?php
    }
}
