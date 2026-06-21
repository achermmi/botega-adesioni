<?php
/**
 * Frontend membership application form.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Show success message.
if ( isset( $_GET['ba_success'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$confirmation = get_option(
		'ba_confirmation_message',
		__( 'Grazie per aver inviato la tua domanda di adesione. Sarai contattato a breve.', 'botega-adesioni' )
	);
	?>
	<div class="ba-notice ba-notice--success" role="alert">
		<?php echo esc_html( $confirmation ); ?>
	</div>
	<?php
	return;
}

// Show error messages.
$errors = array();
if ( isset( $_GET['ba_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw_errors = explode( '|', rawurldecode( sanitize_text_field( wp_unslash( $_GET['ba_error'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	foreach ( $raw_errors as $err ) {
		$errors[] = sanitize_text_field( $err );
	}
}

// Repopulate values after error (excluding sensitive data).
$val = function( $key ) {
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
};
?>

<div class="ba-form-wrap">
	<h2><?php esc_html_e( 'Domanda di adesione', 'botega-adesioni' ); ?></h2>
	<p><?php esc_html_e( 'Compila il modulo sottostante per presentare la tua domanda di adesione alla cooperativa La Botega da la Lavizzara.', 'botega-adesioni' ); ?></p>

	<?php if ( ! empty( $errors ) ) : ?>
		<div class="ba-notice ba-notice--error" role="alert">
			<strong><?php esc_html_e( 'Correggi i seguenti errori prima di procedere:', 'botega-adesioni' ); ?></strong>
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ba-form" novalidate>
		<input type="hidden" name="action" value="ba_submit_adesione">
		<input type="hidden" name="ba_current_url" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'ba_submit_adesione', 'ba_form_nonce' ); ?>

		<fieldset class="ba-fieldset">
			<legend><?php esc_html_e( 'Dati anagrafici', 'botega-adesioni' ); ?></legend>

			<div class="ba-row ba-row--2col">
				<div class="ba-field">
					<label for="ba_nome"><?php esc_html_e( 'Nome', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="text" id="ba_nome" name="ba_nome" required
						value="<?php echo esc_attr( $val( 'ba_nome' ) ); ?>"
						autocomplete="given-name">
				</div>
				<div class="ba-field">
					<label for="ba_cognome"><?php esc_html_e( 'Cognome', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="text" id="ba_cognome" name="ba_cognome" required
						value="<?php echo esc_attr( $val( 'ba_cognome' ) ); ?>"
						autocomplete="family-name">
				</div>
			</div>

			<div class="ba-row ba-row--2col">
				<div class="ba-field">
					<label for="ba_data_nascita"><?php esc_html_e( 'Data di nascita', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="date" id="ba_data_nascita" name="ba_data_nascita" required
						value="<?php echo esc_attr( $val( 'ba_data_nascita' ) ); ?>"
						autocomplete="bday">
				</div>
				<div class="ba-field">
					<label for="ba_luogo_nascita"><?php esc_html_e( 'Luogo di nascita', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="text" id="ba_luogo_nascita" name="ba_luogo_nascita" required
						value="<?php echo esc_attr( $val( 'ba_luogo_nascita' ) ); ?>">
				</div>
			</div>

			<div class="ba-field">
				<label for="ba_codice_fiscale"><?php esc_html_e( 'Codice fiscale', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
				<input type="text" id="ba_codice_fiscale" name="ba_codice_fiscale" required
					maxlength="16"
					pattern="[A-Za-z0-9]{16}"
					value="<?php echo esc_attr( $val( 'ba_codice_fiscale' ) ); ?>"
					style="text-transform: uppercase"
					autocomplete="off">
			</div>
		</fieldset>

		<fieldset class="ba-fieldset">
			<legend><?php esc_html_e( 'Residenza', 'botega-adesioni' ); ?></legend>

			<div class="ba-field">
				<label for="ba_indirizzo"><?php esc_html_e( 'Indirizzo (via/piazza e numero civico)', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
				<input type="text" id="ba_indirizzo" name="ba_indirizzo" required
					value="<?php echo esc_attr( $val( 'ba_indirizzo' ) ); ?>"
					autocomplete="street-address">
			</div>

			<div class="ba-row ba-row--3col">
				<div class="ba-field">
					<label for="ba_cap"><?php esc_html_e( 'CAP', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="text" id="ba_cap" name="ba_cap" required
						maxlength="5" pattern="\d{5}"
						value="<?php echo esc_attr( $val( 'ba_cap' ) ); ?>"
						autocomplete="postal-code">
				</div>
				<div class="ba-field">
					<label for="ba_citta"><?php esc_html_e( 'Città', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="text" id="ba_citta" name="ba_citta" required
						value="<?php echo esc_attr( $val( 'ba_citta' ) ); ?>"
						autocomplete="address-level2">
				</div>
				<div class="ba-field">
					<label for="ba_provincia"><?php esc_html_e( 'Provincia', 'botega-adesioni' ); ?></label>
					<input type="text" id="ba_provincia" name="ba_provincia"
						maxlength="2"
						value="<?php echo esc_attr( $val( 'ba_provincia' ) ); ?>"
						style="text-transform: uppercase"
						placeholder="es. MI">
				</div>
			</div>
		</fieldset>

		<fieldset class="ba-fieldset">
			<legend><?php esc_html_e( 'Contatti', 'botega-adesioni' ); ?></legend>

			<div class="ba-row ba-row--2col">
				<div class="ba-field">
					<label for="ba_email"><?php esc_html_e( 'Indirizzo email', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="email" id="ba_email" name="ba_email" required
						value="<?php echo esc_attr( $val( 'ba_email' ) ); ?>"
						autocomplete="email">
				</div>
				<div class="ba-field">
					<label for="ba_telefono"><?php esc_html_e( 'Numero di telefono', 'botega-adesioni' ); ?> <span class="ba-required" aria-hidden="true">*</span></label>
					<input type="tel" id="ba_telefono" name="ba_telefono" required
						value="<?php echo esc_attr( $val( 'ba_telefono' ) ); ?>"
						autocomplete="tel">
				</div>
			</div>
		</fieldset>

		<fieldset class="ba-fieldset">
			<legend><?php esc_html_e( 'Motivazione', 'botega-adesioni' ); ?></legend>

			<div class="ba-field">
				<label for="ba_motivazione"><?php esc_html_e( 'Perché vuoi aderire alla cooperativa?', 'botega-adesioni' ); ?></label>
				<textarea id="ba_motivazione" name="ba_motivazione" rows="5"><?php echo esc_textarea( $val( 'ba_motivazione' ) ); ?></textarea>
			</div>
		</fieldset>

		<fieldset class="ba-fieldset ba-fieldset--checks">
			<legend><?php esc_html_e( 'Dichiarazioni', 'botega-adesioni' ); ?></legend>

			<div class="ba-field ba-field--checkbox">
				<label>
					<input type="checkbox" name="ba_accetta_statuto" value="1" required>
					<?php
					printf(
						/* translators: %s is replaced with an anchor tag for the statute link */
						esc_html__( 'Dichiaro di aver letto e di accettare lo statuto della cooperativa La Botega da la Lavizzara. %s', 'botega-adesioni' ),
						''
					);
					?>
					<span class="ba-required" aria-hidden="true">*</span>
				</label>
			</div>

			<div class="ba-field ba-field--checkbox">
				<label>
					<input type="checkbox" name="ba_accetta_privacy" value="1" required>
					<?php
					printf(
						/* translators: %s is replaced with an anchor tag for the privacy policy link */
						esc_html__( 'Dichiaro di aver preso visione dell\'informativa sulla privacy ai sensi del Reg. UE 2016/679 (GDPR) e acconsento al trattamento dei dati personali. %s', 'botega-adesioni' ),
						''
					);
					?>
					<span class="ba-required" aria-hidden="true">*</span>
				</label>
			</div>
		</fieldset>

		<p class="ba-required-note">
			<span class="ba-required" aria-hidden="true">*</span>
			<?php esc_html_e( 'Campi obbligatori', 'botega-adesioni' ); ?>
		</p>

		<div class="ba-submit">
			<button type="submit" class="ba-btn ba-btn--primary">
				<?php esc_html_e( 'Invia domanda di adesione', 'botega-adesioni' ); ?>
			</button>
		</div>
	</form>
</div>
