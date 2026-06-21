<?php
/**
 * Email notifications for membership applications.
 *
 * @package BotegaAdesioni
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BA_Email
 */
class BA_Email {

	/**
	 * Send notification to admin when a new application is received.
	 *
	 * @param object $adesione Application data object.
	 * @return bool
	 */
	public static function notify_admin( $adesione ) {
		if ( ! get_option( 'ba_notify_admin', '1' ) ) {
			return false;
		}

		$to      = get_option( 'ba_notification_email', get_option( 'admin_email' ) );
		$subject = sprintf(
			/* translators: %s: applicant full name */
			__( '[Botega Adesioni] Nuova domanda di adesione da %s', 'botega-adesioni' ),
			esc_html( $adesione->nome . ' ' . $adesione->cognome )
		);

		$admin_url = admin_url( 'admin.php?page=botega-adesioni&action=view&id=' . intval( $adesione->id ) );

		$message  = __( 'È stata ricevuta una nuova domanda di adesione alla cooperativa.', 'botega-adesioni' ) . "\n\n";
		$message .= __( 'Dettagli richiedente:', 'botega-adesioni' ) . "\n";
		$message .= '---' . "\n";
		/* translators: %s: full name */
		$message .= sprintf( __( 'Nome: %s', 'botega-adesioni' ), $adesione->nome . ' ' . $adesione->cognome ) . "\n";
		/* translators: %s: email address */
		$message .= sprintf( __( 'Email: %s', 'botega-adesioni' ), $adesione->email ) . "\n";
		/* translators: %s: phone number */
		$message .= sprintf( __( 'Telefono: %s', 'botega-adesioni' ), $adesione->telefono ) . "\n";
		/* translators: %s: fiscal code */
		$message .= sprintf( __( 'Codice fiscale: %s', 'botega-adesioni' ), $adesione->codice_fiscale ) . "\n";
		$message .= '---' . "\n\n";
		/* translators: %s: admin URL */
		$message .= sprintf( __( 'Visualizza la domanda nell\'area amministrativa: %s', 'botega-adesioni' ), $admin_url ) . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Send confirmation to the applicant.
	 *
	 * @param object $adesione Application data object.
	 * @return bool
	 */
	public static function notify_applicant( $adesione ) {
		if ( ! get_option( 'ba_notify_applicant', '1' ) ) {
			return false;
		}

		$to      = $adesione->email;
		$subject = __( 'Domanda di adesione ricevuta - La Botega da la Lavizzara', 'botega-adesioni' );

		$message  = sprintf(
			/* translators: %s: applicant first name */
			__( 'Gentile %s,', 'botega-adesioni' ),
			esc_html( $adesione->nome )
		) . "\n\n";
		$message .= __( 'abbiamo ricevuto la tua domanda di adesione alla cooperativa La Botega da la Lavizzara.', 'botega-adesioni' ) . "\n\n";
		$message .= get_option( 'ba_confirmation_message', __( 'Grazie per aver inviato la tua domanda di adesione. Sarai contattato a breve.', 'botega-adesioni' ) ) . "\n\n";
		$message .= __( 'Cordiali saluti,', 'botega-adesioni' ) . "\n";
		$message .= __( 'La Botega da la Lavizzara', 'botega-adesioni' ) . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Notify applicant that their application status has changed.
	 *
	 * @param object $adesione Application data object.
	 * @param string $old_stato Previous status slug.
	 * @return bool
	 */
	public static function notify_status_change( $adesione, $old_stato ) {
		if ( 'approvata' !== $adesione->stato && 'rifiutata' !== $adesione->stato ) {
			return false;
		}

		$to = $adesione->email;

		if ( 'approvata' === $adesione->stato ) {
			$subject = __( 'Domanda di adesione approvata - La Botega da la Lavizzara', 'botega-adesioni' );
			$message  = sprintf(
				/* translators: %s: applicant first name */
				__( 'Gentile %s,', 'botega-adesioni' ),
				esc_html( $adesione->nome )
			) . "\n\n";
			$message .= __( 'siamo lieti di comunicarti che la tua domanda di adesione alla cooperativa La Botega da la Lavizzara è stata approvata.', 'botega-adesioni' ) . "\n\n";
			$message .= __( 'Ti contatteremo a breve per completare le formalità di iscrizione.', 'botega-adesioni' ) . "\n\n";
		} else {
			$subject = __( 'Aggiornamento domanda di adesione - La Botega da la Lavizzara', 'botega-adesioni' );
			$message  = sprintf(
				/* translators: %s: applicant first name */
				__( 'Gentile %s,', 'botega-adesioni' ),
				esc_html( $adesione->nome )
			) . "\n\n";
			$message .= __( 'ti informiamo che la tua domanda di adesione alla cooperativa La Botega da la Lavizzara non ha potuto essere accolta.', 'botega-adesioni' ) . "\n\n";
			$message .= __( 'Per ulteriori informazioni puoi contattarci direttamente.', 'botega-adesioni' ) . "\n\n";
		}

		$message .= __( 'Cordiali saluti,', 'botega-adesioni' ) . "\n";
		$message .= __( 'La Botega da la Lavizzara', 'botega-adesioni' ) . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $to, $subject, $message, $headers );
	}
}
