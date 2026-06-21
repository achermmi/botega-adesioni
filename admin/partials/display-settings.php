<?php
/**
 * Admin settings page.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ba-admin-wrap">
	<h1><?php esc_html_e( 'Impostazioni Botega Adesioni', 'botega-adesioni' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'ba_save_settings', 'ba_settings_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ba_notification_email">
						<?php esc_html_e( 'Email di notifica admin', 'botega-adesioni' ); ?>
					</label>
				</th>
				<td>
					<input type="email" name="ba_notification_email" id="ba_notification_email"
						class="regular-text"
						value="<?php echo esc_attr( get_option( 'ba_notification_email', get_option( 'admin_email' ) ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'Indirizzo email che riceve le notifiche per le nuove domande.', 'botega-adesioni' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Notifiche email', 'botega-adesioni' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="ba_notify_admin" value="1"
								<?php checked( get_option( 'ba_notify_admin', '1' ), '1' ); ?>>
							<?php esc_html_e( 'Notifica l\'amministratore per ogni nuova domanda', 'botega-adesioni' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="ba_notify_applicant" value="1"
								<?php checked( get_option( 'ba_notify_applicant', '1' ), '1' ); ?>>
							<?php esc_html_e( 'Invia email di conferma al richiedente', 'botega-adesioni' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="ba_confirmation_message">
						<?php esc_html_e( 'Messaggio di conferma', 'botega-adesioni' ); ?>
					</label>
				</th>
				<td>
					<textarea name="ba_confirmation_message" id="ba_confirmation_message"
						class="large-text" rows="4"><?php echo esc_textarea( get_option( 'ba_confirmation_message', __( 'Grazie per aver inviato la tua domanda di adesione. Sarai contattato a breve.', 'botega-adesioni' ) ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Testo mostrato sul sito e incluso nell\'email di conferma dopo l\'invio della domanda.', 'botega-adesioni' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Utilizzo shortcode', 'botega-adesioni' ); ?></h2>
		<p>
			<?php esc_html_e( 'Inserisci il seguente shortcode in qualsiasi pagina o post per mostrare il modulo di adesione:', 'botega-adesioni' ); ?>
		</p>
		<code>[botega_adesione]</code>

		<?php submit_button( __( 'Salva impostazioni', 'botega-adesioni' ) ); ?>
	</form>
</div>
