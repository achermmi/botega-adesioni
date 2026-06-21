<?php
/**
 * Admin list view for membership applications.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 *
 * @var BA_Admin $this (rendered from BA_Admin context - use $admin object instead)
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve filter/pagination params (nonce-free read-only display).
$current_stato   = isset( $_GET['stato'] ) ? sanitize_key( $_GET['stato'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search          = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged           = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page        = 20;

$result = BA_Database::get_adesioni( array(
	'stato'    => $current_stato,
	'search'   => $search,
	'paged'    => $paged,
	'per_page' => $per_page,
) );

$items         = $result['items'];
$total         = $result['total'];
$total_pages   = ceil( $total / $per_page );
$status_counts = BA_Database::get_status_counts();

$statuses = array(
	'in_attesa'    => __( 'In attesa', 'botega-adesioni' ),
	'in_revisione' => __( 'In revisione', 'botega-adesioni' ),
	'approvata'    => __( 'Approvata', 'botega-adesioni' ),
	'rifiutata'    => __( 'Rifiutata', 'botega-adesioni' ),
);

$export_url = wp_nonce_url(
	admin_url( 'admin-post.php?action=ba_export_csv' . ( $current_stato ? '&stato=' . esc_attr( $current_stato ) : '' ) ),
	'ba_export_csv'
);
?>
<div class="wrap ba-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Domande di adesione', 'botega-adesioni' ); ?></h1>
	<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">
		<?php esc_html_e( 'Esporta CSV', 'botega-adesioni' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php // Status tabs. ?>
	<ul class="subsubsub">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=botega-adesioni' ) ); ?>"
				class="<?php echo '' === $current_stato ? 'current' : ''; ?>">
				<?php esc_html_e( 'Tutte', 'botega-adesioni' ); ?>
				<span class="count">(<?php echo esc_html( array_sum( $status_counts ) ); ?>)</span>
			</a> |
		</li>
		<?php foreach ( $statuses as $slug => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=botega-adesioni&stato=' . $slug ) ); ?>"
					class="<?php echo $current_stato === $slug ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
					<span class="count">(<?php echo esc_html( $status_counts[ $slug ] ?? 0 ); ?>)</span>
				</a>
				<?php if ( 'rifiutata' !== $slug ) : ?> |<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php // Search form. ?>
	<form method="get" action="">
		<input type="hidden" name="page" value="botega-adesioni">
		<?php if ( $current_stato ) : ?>
			<input type="hidden" name="stato" value="<?php echo esc_attr( $current_stato ); ?>">
		<?php endif; ?>
		<p class="search-box">
			<label class="screen-reader-text" for="ba-search"><?php esc_html_e( 'Cerca domande:', 'botega-adesioni' ); ?></label>
			<input type="search" id="ba-search" name="s" value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Nome, cognome, email o codice fiscale&hellip;', 'botega-adesioni' ); ?>">
			<?php submit_button( __( 'Cerca', 'botega-adesioni' ), '', '', false ); ?>
		</p>
	</form>

	<table class="wp-list-table widefat fixed striped ba-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'botega-adesioni' ); ?></th>
				<th><?php esc_html_e( 'Nome', 'botega-adesioni' ); ?></th>
				<th><?php esc_html_e( 'Email', 'botega-adesioni' ); ?></th>
				<th><?php esc_html_e( 'Telefono', 'botega-adesioni' ); ?></th>
				<th><?php esc_html_e( 'Stato', 'botega-adesioni' ); ?></th>
				<th><?php esc_html_e( 'Data invio', 'botega-adesioni' ); ?></th>
				<th><?php esc_html_e( 'Azioni', 'botega-adesioni' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $items ) ) : ?>
			<tr>
				<td colspan="7"><?php esc_html_e( 'Nessuna domanda trovata.', 'botega-adesioni' ); ?></td>
			</tr>
		<?php else : ?>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$view_url   = admin_url( 'admin.php?page=botega-adesioni&action=view&id=' . $item->id );
				$delete_url = wp_nonce_url(
					admin_url( 'admin.php?page=botega-adesioni&action=delete&id=' . $item->id ),
					'ba_delete_' . $item->id
				);
				$status_class = 'ba-status ba-status--' . esc_attr( $item->stato );
				$status_label = $statuses[ $item->stato ] ?? $item->stato;
				?>
				<tr>
					<td><?php echo esc_html( $item->id ); ?></td>
					<td>
						<strong>
							<a href="<?php echo esc_url( $view_url ); ?>">
								<?php echo esc_html( $item->cognome . ' ' . $item->nome ); ?>
							</a>
						</strong>
					</td>
					<td><?php echo esc_html( $item->email ); ?></td>
					<td><?php echo esc_html( $item->telefono ); ?></td>
					<td><span class="<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
					<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->data_invio ) ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'Visualizza', 'botega-adesioni' ); ?></a>
						&nbsp;|&nbsp;
						<a href="<?php echo esc_url( $delete_url ); ?>" class="ba-delete-link"
							data-confirm="<?php esc_attr_e( 'Sei sicuro di voler eliminare questa domanda?', 'botega-adesioni' ); ?>">
							<?php esc_html_e( 'Elimina', 'botega-adesioni' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %d: number of items */
						esc_html( _n( '%d elemento', '%d elementi', $total, 'botega-adesioni' ) ),
						esc_html( $total )
					);
					?>
				</span>
				<?php
				$base_url = admin_url( 'admin.php?page=botega-adesioni' );
				if ( $current_stato ) {
					$base_url = add_query_arg( 'stato', $current_stato, $base_url );
				}
				if ( $search ) {
					$base_url = add_query_arg( 's', $search, $base_url );
				}

				echo paginate_links( array(
					'base'    => add_query_arg( 'paged', '%#%', $base_url ),
					'format'  => '',
					'current' => $paged,
					'total'   => $total_pages,
				) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links output is safe
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
