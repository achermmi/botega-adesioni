<?php
/**
 * Admin detail/edit view for a single membership application.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 *
 * Variables available:
 * @var object $adesione Application data object from BA_Database::get_adesione().
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$statuses = array(
	'in_attesa'    => __( 'In attesa', 'botega-adesioni' ),
	'in_revisione' => __( 'In revisione', 'botega-adesioni' ),
	'approvata'    => __( 'Approvata', 'botega-adesioni' ),
	'rifiutata'    => __( 'Rifiutata', 'botega-adesioni' ),
);

$list_url   = admin_url( 'admin.php?page=botega-adesioni' );
$delete_url = wp_nonce_url(
	admin_url( 'admin.php?page=botega-adesioni&action=delete&id=' . $adesione->id ),
	'ba_delete_' . $adesione->id
);
?>
<div class="wrap ba-admin-wrap">
	<h1>
		<?php
		printf(
			/* translators: %s: applicant full name */
			esc_html__( 'Domanda di %s', 'botega-adesioni' ),
			esc_html( $adesione->nome . ' ' . $adesione->cognome )
		);
		?>
	</h1>
	<p>
		<a href="<?php echo esc_url( $list_url ); ?>" class="button">
			&larr; <?php esc_html_e( 'Torna all\'elenco', 'botega-adesioni' ); ?>
		</a>
		<a href="<?php echo esc_url( $delete_url ); ?>" class="button ba-delete-link" style="margin-left:8px">
			<?php esc_html_e( 'Elimina domanda', 'botega-adesioni' ); ?>
		</a>
	</p>

	<div class="ba-detail-grid">

		<!-- Left column: applicant data -->
		<div class="ba-detail-col">
			<h2><?php esc_html_e( 'Dati del richiedente', 'botega-adesioni' ); ?></h2>
			<table class="ba-info-table">
				<tr>
					<th><?php esc_html_e( 'Nome', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->nome ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Cognome', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->cognome ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Data di nascita', 'botega-adesioni' ); ?></th>
					<td><?php echo $adesione->data_nascita ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $adesione->data_nascita ) ) ) : '—'; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Luogo di nascita', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->luogo_nascita ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Codice fiscale', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->codice_fiscale ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Indirizzo', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->indirizzo ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'CAP', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->cap ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Città', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->citta ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Provincia', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->provincia ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Email', 'botega-adesioni' ); ?></th>
					<td><a href="mailto:<?php echo esc_attr( $adesione->email ); ?>"><?php echo esc_html( $adesione->email ); ?></a></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Telefono', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->telefono ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Statuto accettato', 'botega-adesioni' ); ?></th>
					<td><?php echo $adesione->accetta_statuto ? esc_html__( 'Sì', 'botega-adesioni' ) : esc_html__( 'No', 'botega-adesioni' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Privacy accettata', 'botega-adesioni' ); ?></th>
					<td><?php echo $adesione->accetta_privacy ? esc_html__( 'Sì', 'botega-adesioni' ) : esc_html__( 'No', 'botega-adesioni' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'IP richiedente', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( $adesione->ip_address ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Data invio', 'botega-adesioni' ); ?></th>
					<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $adesione->data_invio ) ) ); ?></td>
				</tr>
			</table>

			<?php if ( ! empty( $adesione->motivazione ) ) : ?>
				<h3><?php esc_html_e( 'Motivazione', 'botega-adesioni' ); ?></h3>
				<div class="ba-motivazione">
					<?php echo nl2br( esc_html( $adesione->motivazione ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Right column: status management -->
		<div class="ba-detail-col">
			<h2><?php esc_html_e( 'Gestione domanda', 'botega-adesioni' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ba_update_status">
				<input type="hidden" name="id" value="<?php echo esc_attr( $adesione->id ); ?>">
				<?php wp_nonce_field( 'ba_update_status', 'ba_update_status_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="ba_stato"><?php esc_html_e( 'Stato domanda', 'botega-adesioni' ); ?></label>
						</th>
						<td>
							<select name="stato" id="ba_stato">
								<?php foreach ( $statuses as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $adesione->stato, $slug ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="ba_note_admin"><?php esc_html_e( 'Note interne', 'botega-adesioni' ); ?></label>
						</th>
						<td>
							<textarea name="note_admin" id="ba_note_admin" rows="6" class="large-text"><?php echo esc_textarea( $adesione->note_admin ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Visibili solo agli amministratori.', 'botega-adesioni' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Salva modifiche', 'botega-adesioni' ) ); ?>
			</form>
		</div>

	</div><!-- .ba-detail-grid -->
</div>
