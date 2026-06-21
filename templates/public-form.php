<?php
defined( 'ABSPATH' ) || exit;
/**
 * Template form pubblico iscrizione / donazione
 * @var bool   $success
 * @var string $error
 * @var array  $atts
 */
$current_url = get_permalink();
?>
<div class="ba-public-wrap" id="ba-public-form-wrap">

<?php if ( $success ) : ?>
<!-- ── MESSAGGIO SUCCESSO ─────────────────────────────────── -->
<?php
$email_ok    = ( $email_stato === 'inviato' );
$email_error = ( $email_stato === 'errore_invio' );
?>
<div class="ba-public-success">
    <div class="ba-success-icon">✅</div>
    <h3><?php echo $solo_don
        ? esc_html__( 'Donazione ricevuta!', 'botega-adesioni' )
        : esc_html__( 'Iscrizione ricevuta!', 'botega-adesioni' ); ?></h3>
    <p><?php echo $solo_don
        ? esc_html__( 'Grazie per aver aderito alla Cooperativa La Botega da la Lavizzara! La tua donazione sarà attiva dopo la ricezione del pagamento.', 'botega-adesioni' )
        : esc_html__( 'Grazie per aver aderito alla Cooperativa La Botega da la Lavizzara! La tua iscrizione sarà attiva dopo la ricezione del pagamento.', 'botega-adesioni' ); ?></p>

    <?php if ( $email_ok ) : ?>
    <div class="ba-success-email-ok">
        📧 <?php esc_html_e( 'Ti abbiamo inviato un\'email con il cedolino di pagamento. Controlla la tua casella di posta (anche la cartella Spam).', 'botega-adesioni' ); ?>
    </div>
    <?php elseif ( $email_error ) : ?>
    <div class="ba-success-email-warn">
        ⚠️ <?php esc_html_e( 'La tua iscrizione è stata registrata, ma non è stato possibile inviare l\'email di conferma. Contattaci a labotegalavizzara@gmail.com indicando il tuo nome per ricevere il cedolino.', 'botega-adesioni' ); ?>
    </div>
    <?php else : ?>
    <div class="ba-success-email-ok">
        📧 <?php esc_html_e( 'Riceverai a breve un\'email con il cedolino di pagamento.', 'botega-adesioni' ); ?>
    </div>
    <?php endif; ?>

    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ba-btn-secondary">
        <?php esc_html_e( '← Torna alla homepage', 'botega-adesioni' ); ?>
    </a>
</div>

<?php else : ?>

<?php if ( $error ) : ?>
<div class="ba-public-error" role="alert">
    <strong><?php esc_html_e( '⚠️ Attenzione:', 'botega-adesioni' ); ?></strong>
    <?php echo esc_html( $error ); ?>
</div>
<?php endif; ?>

<!-- ── FORM ──────────────────────────────────────────────── -->
<form method="post" id="ba-public-form" novalidate
      action="<?php echo esc_url( get_permalink() ?: BA_Pages::get_url() ); ?>">
    <?php wp_nonce_field( 'ba_public_form', 'ba_public_nonce' ); ?>
    <input type="hidden" name="ba_public_action" value="iscriviti">
    <input type="hidden" name="ba_page_url" value="<?php echo esc_url( get_permalink() ?: BA_Pages::get_url() ); ?>">

    <div class="ba-pub-grid">

        <!-- COLONNA SINISTRA: dati anagrafici -->
        <div class="ba-pub-col ba-pub-col-main">

            <div class="ba-pub-section">
                <h3 class="ba-pub-section-title">
                    <?php esc_html_e( 'Dati personali', 'botega-adesioni' ); ?>
                </h3>

                <div class="ba-pub-field ba-pub-field-required">
                    <label for="pub_cognome_nome">
                        <?php esc_html_e( 'Cognome e nome', 'botega-adesioni' ); ?>
                        <span class="ba-req">*</span>
                    </label>
                    <input type="text" id="pub_cognome_nome" name="cognome_nome"
                           value="<?php echo esc_attr( $_GET['cognome_nome'] ?? '' ); ?>"
                           placeholder="<?php esc_attr_e( 'Cognome e nome', 'botega-adesioni' ); ?>"
                           required autocomplete="name">
                </div>

                <!-- Indirizzo con autocomplete -->
                <div class="ba-pub-field">
                    <label for="pub_indirizzo">
                        <?php esc_html_e( 'Indirizzo', 'botega-adesioni' ); ?>
                    </label>
                    <input type="text" id="pub_indirizzo" name="indirizzo"
                           value="<?php echo esc_attr( $_GET['indirizzo'] ?? '' ); ?>"
                           placeholder="<?php esc_attr_e( 'Via / Strada…', 'botega-adesioni' ); ?>"
                           autocomplete="address-line1"
                           data-ba-autocomplete="indirizzo">
                </div>

                <div class="ba-pub-row-2">
                    <div class="ba-pub-field">
                        <label for="pub_cap">
                            <?php esc_html_e( 'CAP', 'botega-adesioni' ); ?>
                        </label>
                        <input type="text" id="pub_cap" name="cap"
                               value="<?php echo esc_attr( $_GET['cap'] ?? '' ); ?>"
                               placeholder="<?php esc_attr_e( 'CAP', 'botega-adesioni' ); ?>"
                               autocomplete="postal-code"
                               maxlength="10"
                               data-ba-autocomplete="cap">
                    </div>
                    <div class="ba-pub-field">
                        <label for="pub_localita">
                            <?php esc_html_e( 'Località', 'botega-adesioni' ); ?>
                        </label>
                        <input type="text" id="pub_localita" name="localita"
                               value="<?php echo esc_attr( $_GET['localita'] ?? '' ); ?>"
                               placeholder="<?php esc_attr_e( 'Comune / Città', 'botega-adesioni' ); ?>"
                               autocomplete="address-level2"
                               data-ba-autocomplete="localita">
                    </div>
                </div>

                <div class="ba-pub-row-2">
                    <div class="ba-pub-field">
                        <label for="pub_telefono">
                            <?php esc_html_e( 'Numero di telefono', 'botega-adesioni' ); ?>
                        </label>
                        <input type="tel" id="pub_telefono" name="telefono"
                               value="<?php echo esc_attr( $_GET['telefono'] ?? '' ); ?>"
                               placeholder="+41…"
                               autocomplete="tel">
                    </div>
                    <div class="ba-pub-field ba-pub-field-required">
                        <label for="pub_email">
                            <?php esc_html_e( 'E-mail', 'botega-adesioni' ); ?>
                            <span class="ba-req">*</span>
                        </label>
                        <input type="email" id="pub_email" name="email"
                               value="<?php echo esc_attr( $_GET['email'] ?? '' ); ?>"
                               placeholder="nome@esempio.ch"
                               required autocomplete="email">
                        <span class="ba-field-hint">
                            <?php esc_html_e( 'Riceverai il cedolino di pagamento a questo indirizzo.', 'botega-adesioni' ); ?>
                        </span>
                    </div>
                </div>

                <div class="ba-pub-field">
                    <label for="pub_note">
                        <?php esc_html_e( 'Note (facoltativo)', 'botega-adesioni' ); ?>
                    </label>
                    <textarea id="pub_note" name="note" rows="3"
                              placeholder="<?php esc_attr_e( 'Eventuali note o messaggi per il comitato…', 'botega-adesioni' ); ?>"></textarea>
                </div>
            </div><!-- .ba-pub-section -->

        </div><!-- .ba-pub-col-main -->

        <!-- COLONNA DESTRA: quota e importo -->
        <div class="ba-pub-col ba-pub-col-side">

            <div class="ba-pub-section ba-pub-quota-box">
                <h3 class="ba-pub-section-title">
                    <?php esc_html_e( 'Quota e contributo', 'botega-adesioni' ); ?>
                </h3>

                <!-- Quota 250 -->
                <label class="ba-pub-check-label" for="pub_quota_250">
                    <input type="checkbox" id="pub_quota_250" name="quota_250" value="1"
                           <?php checked( true ); ?>>
                    <span class="ba-pub-check-box"></span>
                    <span class="ba-pub-check-txt">
                        <strong><?php esc_html_e( 'Quota associativa', 'botega-adesioni' ); ?></strong>
                        <em>CHF 250.00</em>
                    </span>
                </label>

                <!-- Donazione -->
                <label class="ba-pub-check-label" for="pub_qdon">
                    <input type="checkbox" id="pub_qdon" name="_qdon_check" value="1">
                    <span class="ba-pub-check-box"></span>
                    <span class="ba-pub-check-txt">
                        <strong><?php esc_html_e( 'Donazione volontaria', 'botega-adesioni' ); ?></strong>
                    </span>
                </label>

                <div class="ba-pub-donazione-wrap" id="ba-pub-don-wrap">
                    <label for="pub_donazione" class="ba-pub-don-label">
                        <?php esc_html_e( 'Importo donazione (CHF)', 'botega-adesioni' ); ?>
                    </label>
                    <div class="ba-pub-don-input-wrap">
                        <span class="ba-pub-don-prefix">CHF</span>
                        <input type="number" id="pub_donazione" name="donazione"
                               value="0" min="0" step="10" placeholder="0.00">
                    </div>
                </div>

                <!-- Totale calcolato -->
                <div class="ba-pub-totale-box">
                    <span><?php esc_html_e( 'Importo totale:', 'botega-adesioni' ); ?></span>
                    <strong id="ba-pub-totale">CHF 250.00</strong>
                </div>

                <!-- Info pagamento -->
                <div class="ba-pub-info-box">
                    <p><strong><?php esc_html_e( 'Modalità di pagamento:', 'botega-adesioni' ); ?></strong></p>
                    <p><?php esc_html_e( 'Riceverai un cedolino di pagamento via email. Potrai pagare tramite:', 'botega-adesioni' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'E-banking (QR code)', 'botega-adesioni' ); ?></li>
                        <li><?php esc_html_e( 'Bonifico bancario', 'botega-adesioni' ); ?></li>
                        <li><?php esc_html_e( 'Ufficio postale / Banca', 'botega-adesioni' ); ?></li>
                    </ul>
                    <p class="ba-iban">
                        <strong>IBAN:</strong> CH48 8080 8003 7010 4694 7
                    </p>
                </div>

                <!-- Privacy -->
                <div class="ba-pub-field ba-pub-field-required">
                    <label class="ba-pub-check-label" for="pub_privacy">
                        <input type="checkbox" id="pub_privacy" name="privacy" value="1" required>
                        <span class="ba-pub-check-box"></span>
                        <span class="ba-pub-check-txt ba-pub-privacy-txt">
                            <?php printf(
                                esc_html__( 'Ho letto e accetto la %spolitica sulla privacy%s e lo statuto della Cooperativa.', 'botega-adesioni' ),
                                '<a href="#" target="_blank">',
                                '</a>'
                            ); ?>
                            <span class="ba-req">*</span>
                        </span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" id="ba-pub-submit" class="ba-pub-btn-submit">
                    <?php esc_html_e( 'Invia adesione / donazione', 'botega-adesioni' ); ?>
                </button>

                <p class="ba-pub-required-note">
                    <span class="ba-req">*</span> <?php esc_html_e( 'Campi obbligatori', 'botega-adesioni' ); ?>
                </p>

            </div><!-- .ba-pub-quota-box -->

        </div><!-- .ba-pub-col-side -->

    </div><!-- .ba-pub-grid -->
</form>

<?php endif; // !$success ?>
</div><!-- .ba-public-wrap -->
