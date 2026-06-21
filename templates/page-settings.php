<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ba-wrap">
<h1><?php esc_html_e( 'Impostazioni – Botega Adesioni', 'botega-adesioni' ); ?></h1>

<form method="post">
<?php wp_nonce_field( 'ba_save_settings', 'ba_nonce' ); ?>
<input type="hidden" name="ba_action" value="save_settings">

<div class="ba-form-grid">
<div class="ba-col">

<div class="ba-card">
    <h2><?php esc_html_e( 'Pagina pubblica di iscrizione', 'botega-adesioni' ); ?></h2>
    <?php
    $page_id  = BA_Pages::get_id();
    $page_url = BA_Pages::get_url();
    $page     = $page_id ? get_post( $page_id ) : null;
    ?>
    <?php if ( $page ) : ?>
    <p><?php esc_html_e( 'La pagina pubblica di iscrizione è attiva. Chiunque può compilare il modulo senza effettuare il login.', 'botega-adesioni' ); ?></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
        <a href="<?php echo esc_url( $page_url ); ?>" target="_blank" class="button button-primary">
            🌐 <?php esc_html_e( 'Visualizza pagina pubblica', 'botega-adesioni' ); ?>
        </a>
        <a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" class="button">
            ✏️ <?php esc_html_e( 'Modifica pagina', 'botega-adesioni' ); ?>
        </a>
    </div>
    <p class="description" style="margin-top:8px;">
        <?php esc_html_e( 'URL:', 'botega-adesioni' ); ?>
        <code><?php echo esc_url( $page_url ); ?></code>
    </p>
    <p class="description">
        <?php esc_html_e( 'Shortcode usato nella pagina:', 'botega-adesioni' ); ?>
        <code>[ba_modulo_iscrizione]</code>
        <?php esc_html_e( '— puoi inserirlo in qualsiasi altra pagina WordPress.', 'botega-adesioni' ); ?>
    </p>
    <?php else : ?>
    <p><?php esc_html_e( 'La pagina pubblica non è ancora stata creata.', 'botega-adesioni' ); ?></p>
    <form method="post">
        <?php wp_nonce_field( 'ba_crea_pagina', 'ba_nonce' ); ?>
        <input type="hidden" name="ba_action" value="crea_pagina">
        <?php submit_button( __( '+ Crea pagina pubblica', 'botega-adesioni' ), 'primary', 'submit', false ); ?>
    </form>
    <?php endif; ?>
</div>

<div class="ba-card">
    <h2><?php esc_html_e( 'Autocomplete indirizzo', 'botega-adesioni' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Il form pubblico usa OpenStreetMap (Nominatim) per l\'autocomplete degli indirizzi, gratuitamente e senza API key. Per usare Google Places (più preciso), inserire una chiave API Google Maps con il servizio "Places API" abilitato.', 'botega-adesioni' ); ?>
    </p>
    <div class="ba-field">
        <label for="ba_google_maps_key"><?php esc_html_e( 'Chiave API Google Maps (facoltativa)', 'botega-adesioni' ); ?></label>
        <input type="text" id="ba_google_maps_key" name="ba_google_maps_key"
               value="<?php echo esc_attr( get_option( 'ba_google_maps_key', '' ) ); ?>"
               class="large-text" placeholder="AIzaSy…">
        <p class="description"><?php esc_html_e( 'Lasciare vuoto per usare OpenStreetMap (gratuito, nessuna registrazione richiesta).', 'botega-adesioni' ); ?></p>
    </div>
</div>

<div class="ba-card">
    <h2><?php esc_html_e( 'Mittente email', 'botega-adesioni' ); ?></h2>
    <div class="ba-field">
        <label for="ba_email_mittente_nome"><?php esc_html_e( 'Nome mittente', 'botega-adesioni' ); ?></label>
        <input type="text" id="ba_email_mittente_nome" name="ba_email_mittente_nome"
               value="<?php echo esc_attr( get_option( 'ba_email_mittente_nome', get_bloginfo( 'name' ) ) ); ?>"
               class="large-text">
    </div>
    <div class="ba-field">
        <label for="ba_email_mittente_email"><?php esc_html_e( 'Email mittente', 'botega-adesioni' ); ?></label>
        <input type="email" id="ba_email_mittente_email" name="ba_email_mittente_email"
               value="<?php echo esc_attr( get_option( 'ba_email_mittente_email', get_option( 'admin_email' ) ) ); ?>"
               class="regular-text">
    </div>
</div>

<!-- ── SMTP ──────────────────────────────────────────────── -->
<div class="ba-card" style="border-top:3px solid #d4e000;">
    <h2><?php esc_html_e( 'Configurazione SMTP', 'botega-adesioni' ); ?></h2>
    <p class="description" style="margin-bottom:12px;">
        <?php esc_html_e( '⚠️ Il server non supporta la funzione mail() di PHP. Configurare un server SMTP per inviare email correttamente (es. Gmail, Infomaniak, Outlook).', 'botega-adesioni' ); ?>
    </p>

    <div class="ba-field">
        <label>
            <input type="checkbox" name="ba_smtp_abilitato" value="1"
                   <?php checked( get_option( 'ba_smtp_abilitato', '0' ), '1' ); ?>>
            <strong><?php esc_html_e( 'Abilita invio via SMTP', 'botega-adesioni' ); ?></strong>
        </label>
    </div>

    <div id="ba-smtp-fields" style="<?php echo get_option( 'ba_smtp_abilitato' ) !== '1' ? 'opacity:.5;pointer-events:none;' : ''; ?>">
        <div class="ba-row-2">
            <div class="ba-field">
                <label for="ba_smtp_host"><?php esc_html_e( 'Server SMTP (Host)', 'botega-adesioni' ); ?></label>
                <input type="text" id="ba_smtp_host" name="ba_smtp_host"
                       value="<?php echo esc_attr( get_option( 'ba_smtp_host', '' ) ); ?>"
                       placeholder="smtp.infomaniak.com"
                       class="regular-text">
            </div>
            <div class="ba-field">
                <label for="ba_smtp_port"><?php esc_html_e( 'Porta', 'botega-adesioni' ); ?></label>
                <input type="number" id="ba_smtp_port" name="ba_smtp_port"
                       value="<?php echo esc_attr( get_option( 'ba_smtp_port', '587' ) ); ?>"
                       placeholder="587" style="width:100px;">
                <p class="description"><?php esc_html_e( '587 = TLS (raccomandato) · 465 = SSL · 25 = nessuna cifratura', 'botega-adesioni' ); ?></p>
            </div>
        </div>

        <div class="ba-field">
            <label for="ba_smtp_secure"><?php esc_html_e( 'Cifratura', 'botega-adesioni' ); ?></label>
            <select id="ba_smtp_secure" name="ba_smtp_secure">
                <option value="tls"  <?php selected( get_option( 'ba_smtp_secure', 'tls' ), 'tls' ); ?>>TLS (porta 587)</option>
                <option value="ssl"  <?php selected( get_option( 'ba_smtp_secure', 'tls' ), 'ssl' ); ?>>SSL (porta 465)</option>
                <option value=""     <?php selected( get_option( 'ba_smtp_secure', 'tls' ), '' );    ?>>Nessuna (porta 25)</option>
            </select>
        </div>

        <div class="ba-row-2">
            <div class="ba-field">
                <label for="ba_smtp_user"><?php esc_html_e( 'Username SMTP', 'botega-adesioni' ); ?></label>
                <input type="text" id="ba_smtp_user" name="ba_smtp_user"
                       value="<?php echo esc_attr( get_option( 'ba_smtp_user', '' ) ); ?>"
                       placeholder="labotegalavizzara@gmail.com"
                       class="regular-text" autocomplete="off">
            </div>
            <div class="ba-field">
                <label for="ba_smtp_pass"><?php esc_html_e( 'Password SMTP', 'botega-adesioni' ); ?></label>
                <input type="password" id="ba_smtp_pass" name="ba_smtp_pass"
                       value=""
                       placeholder="<?php echo get_option( 'ba_smtp_pass' ) ? '••••••••' : ''; ?>"
                       class="regular-text" autocomplete="new-password">
                <p class="description">
                    <?php esc_html_e( 'Lasciare vuoto per mantenere la password attuale.', 'botega-adesioni' ); ?>
                    <?php if ( get_option( 'ba_smtp_pass' ) ) : ?>
                    <span style="color:#46b450;">✓ <?php esc_html_e( 'Password salvata', 'botega-adesioni' ); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Configurazioni rapide comuni -->
        <div class="ba-field">
            <p><strong><?php esc_html_e( 'Configurazioni rapide:', 'botega-adesioni' ); ?></strong></p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="button button-small ba-smtp-preset"
                        data-host="smtp.gmail.com" data-port="587" data-secure="tls">Gmail</button>
                <button type="button" class="button button-small ba-smtp-preset"
                        data-host="mail.infomaniak.com" data-port="587" data-secure="tls">Infomaniak</button>
                <button type="button" class="button button-small ba-smtp-preset"
                        data-host="smtp.office365.com" data-port="587" data-secure="tls">Microsoft 365</button>
                <button type="button" class="button button-small ba-smtp-preset"
                        data-host="smtp-relay.brevo.com" data-port="587" data-secure="tls">Brevo (SendinBlue)</button>
            </div>
        </div>
    </div><!-- #ba-smtp-fields -->

    <script>
    (function(){
        var cb = document.querySelector('[name="ba_smtp_abilitato"]');
        var fields = document.getElementById('ba-smtp-fields');
        if(cb) cb.addEventListener('change', function(){
            fields.style.opacity = this.checked ? '1' : '.5';
            fields.style.pointerEvents = this.checked ? '' : 'none';
        });
        document.querySelectorAll('.ba-smtp-preset').forEach(function(btn){
            btn.addEventListener('click', function(){
                document.getElementById('ba_smtp_host').value   = this.dataset.host;
                document.getElementById('ba_smtp_port').value   = this.dataset.port;
                document.getElementById('ba_smtp_secure').value = this.dataset.secure;
            });
        });
    })();
    </script>
</div>

<div class="ba-card">
    <h2><?php esc_html_e( 'Email conferma pagamento', 'botega-adesioni' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Inviata automaticamente quando una fattura viene impostata su "Pagato".', 'botega-adesioni' ); ?>
        <?php esc_html_e( 'Placeholder disponibili: {{nome}}, {{email}}, {{tipo_socio}}, {{importo}}, {{quota}}, {{donazione}}, {{metodo}}, {{stato}}, {{data_adesione}}, {{luogo}}, {{iban}}, {{associazione}}, {{data_oggi}}', 'botega-adesioni' ); ?>
    </p>
    <div class="ba-field">
        <label for="ba_email_conferma_oggetto"><?php esc_html_e( 'Oggetto', 'botega-adesioni' ); ?></label>
        <input type="text" id="ba_email_conferma_oggetto" name="ba_email_conferma_oggetto"
               value="<?php echo esc_attr( get_option( 'ba_email_conferma_oggetto', '' ) ); ?>"
               class="large-text">
    </div>
    <div class="ba-field">
        <label for="ba_email_conferma_corpo"><?php esc_html_e( 'Corpo email', 'botega-adesioni' ); ?></label>
        <textarea id="ba_email_conferma_corpo" name="ba_email_conferma_corpo"
                  rows="10" class="large-text"><?php
            echo esc_textarea( get_option( 'ba_email_conferma_corpo', '' ) );
        ?></textarea>
    </div>
</div>

</div>
<div class="ba-col">

<div class="ba-card">
    <h2><?php esc_html_e( 'Email richiamo pagamento', 'botega-adesioni' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Inviata manualmente ai soci con pagamento sospeso/inviato. Stessi placeholder dell\'email di conferma.', 'botega-adesioni' ); ?>
    </p>
    <div class="ba-field">
        <label for="ba_email_richiamo_oggetto"><?php esc_html_e( 'Oggetto', 'botega-adesioni' ); ?></label>
        <input type="text" id="ba_email_richiamo_oggetto" name="ba_email_richiamo_oggetto"
               value="<?php echo esc_attr( get_option( 'ba_email_richiamo_oggetto', '' ) ); ?>"
               class="large-text">
    </div>
    <div class="ba-field">
        <label for="ba_email_richiamo_corpo"><?php esc_html_e( 'Corpo email', 'botega-adesioni' ); ?></label>
        <textarea id="ba_email_richiamo_corpo" name="ba_email_richiamo_corpo"
                  rows="10" class="large-text"><?php
            echo esc_textarea( get_option( 'ba_email_richiamo_corpo', '' ) );
        ?></textarea>
    </div>
</div>

</div>
</div>

<div class="ba-form-footer">
    <?php submit_button( __( 'Salva impostazioni', 'botega-adesioni' ), 'primary large', 'submit', false ); ?>
</div>

</form>
</div>
