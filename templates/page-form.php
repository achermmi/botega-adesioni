<?php defined( 'ABSPATH' ) || exit;
/**
 * @var object|null $record
 * @var array  $custom_fields
 * @var array  $custom_values
 * @var array  $stati
 * @var array  $tipi
 * @var array  $metodi
 */
$is_edit  = ! empty( $record );
$title    = $is_edit
    ? sprintf( __( 'Modifica: %s', 'botega-adesioni' ), esc_html( $record->cognome_nome ) )
    : __( 'Nuova adesione', 'botega-adesioni' );

if ( ! function_exists( 'ba_fv' ) ) {
    function ba_fv( ?object $r, string $f, $d = '' ) {
        return BA_Record_Form::field_val( $r, $f, $d );
    }
}
?>
<div class="wrap ba-wrap ba-form-wrap">
<h1><?php echo esc_html( $title ); ?></h1>

<?php if ( $is_edit ) : ?>
<div class="ba-form-top-actions">
    <a href="<?php echo esc_url( BA_Frontend_Admin::get_url( 'ba_admin_lista' ) ); ?>" class="button">
        ← <?php esc_html_e( 'Torna all\'elenco', 'botega-adesioni' ); ?>
    </a>
    <button type="button" class="button button-link-delete ba-delete-btn"
            data-id="<?php echo $record->id; ?>"
            data-name="<?php echo esc_attr( $record->cognome_nome ); ?>"
            data-redirect="<?php echo esc_url( BA_Frontend_Admin::get_url( 'ba_admin_lista' ) ); ?>">
        🗑️ <?php esc_html_e( 'Elimina record', 'botega-adesioni' ); ?>
    </button>
    <a href="<?php echo esc_url( BA_Frontend_Admin::get_url( 'ba_admin_lista', [ 'ba_export' => 'pdf', 'record_id' => $record->id ] ) ); ?>"
       class="button" target="_blank">
        🖨️ <?php esc_html_e( 'Stampa / PDF', 'botega-adesioni' ); ?>
    </a>
</div>
<?php endif; ?>

<form method="post" id="ba-record-form">
<?php wp_nonce_field( 'ba_save_record', 'ba_nonce' ); ?>
<input type="hidden" name="ba_action"  value="save_record">
<input type="hidden" name="record_id"  value="<?php echo $is_edit ? $record->id : 0; ?>">

<div class="ba-form-grid">

    <!-- ── COLONNA SINISTRA ─────────────────────────────────────── -->
    <div class="ba-col">

        <div class="ba-card">
            <h2><?php esc_html_e( 'Dati anagrafici', 'botega-adesioni' ); ?></h2>

            <div class="ba-field">
                <label for="cognome_nome"><?php esc_html_e( 'Cognome e nome *', 'botega-adesioni' ); ?></label>
                <input type="text" id="cognome_nome" name="cognome_nome"
                       value="<?php echo esc_attr( ba_fv( $record, 'cognome_nome' ) ); ?>"
                       required class="large-text">
            </div>

            <div class="ba-field">
                <label for="indirizzo"><?php esc_html_e( 'Indirizzo', 'botega-adesioni' ); ?></label>
                <input type="text" id="indirizzo" name="indirizzo"
                       value="<?php echo esc_attr( ba_fv( $record, 'indirizzo' ) ); ?>"
                       class="large-text">
            </div>

            <div class="ba-row-2">
                <div class="ba-field">
                    <label for="cap"><?php esc_html_e( 'CAP', 'botega-adesioni' ); ?></label>
                    <input type="text" id="cap" name="cap"
                           value="<?php echo esc_attr( ba_fv( $record, 'cap' ) ); ?>"
                           maxlength="10">
                </div>
                <div class="ba-field">
                    <label for="localita"><?php esc_html_e( 'Località', 'botega-adesioni' ); ?></label>
                    <input type="text" id="localita" name="localita"
                           value="<?php echo esc_attr( ba_fv( $record, 'localita' ) ); ?>">
                </div>
            </div>

            <div class="ba-row-2">
                <div class="ba-field">
                    <label for="telefono"><?php esc_html_e( 'Telefono', 'botega-adesioni' ); ?></label>
                    <input type="tel" id="telefono" name="telefono"
                           value="<?php echo esc_attr( ba_fv( $record, 'telefono' ) ); ?>">
                </div>
                <div class="ba-field">
                    <label for="email"><?php esc_html_e( 'E-mail *', 'botega-adesioni' ); ?></label>
                    <input type="email" id="email" name="email"
                           value="<?php echo esc_attr( ba_fv( $record, 'email' ) ); ?>"
                           class="regular-text" required>
                </div>
            </div>

            <div class="ba-row-2">
                <div class="ba-field">
                    <label for="tipo_socio">
                        <?php esc_html_e( 'Tipo socio', 'botega-adesioni' ); ?>
                        <span style="font-size:10px;color:#999;font-weight:normal;"> (solo admin)</span>
                    </label>
                    <select id="tipo_socio" name="tipo_socio">
                        <?php echo BA_Record_Form::select_options( $tipi, ba_fv( $record, 'tipo_socio', 'socio_attivo' ) ); ?>
                    </select>
                </div>
                <div class="ba-field">
                    <label for="data_adesione"><?php esc_html_e( 'Data adesione', 'botega-adesioni' ); ?></label>
                    <input type="date" id="data_adesione" name="data_adesione"
                           value="<?php echo esc_attr( ba_fv( $record, 'data_adesione', date( 'Y-m-d' ) ) ); ?>">
                </div>
            </div>
        </div><!-- .ba-card -->

        <!-- Dati bollettino versamento -->
        <div class="ba-card">
            <h2><?php esc_html_e( 'Dati versamento (bollettino QR)', 'botega-adesioni' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Compilare se diversi dai dati anagrafici.', 'botega-adesioni' ); ?></p>

            <div class="ba-field">
                <label for="nome_versamento"><?php esc_html_e( 'Cognome e nome versamento', 'botega-adesioni' ); ?></label>
                <input type="text" id="nome_versamento" name="nome_versamento"
                       value="<?php echo esc_attr( ba_fv( $record, 'nome_versamento' ) ); ?>"
                       class="large-text">
            </div>
            <div class="ba-field">
                <label for="ind_versamento"><?php esc_html_e( 'Indirizzo versamento', 'botega-adesioni' ); ?></label>
                <input type="text" id="ind_versamento" name="ind_versamento"
                       value="<?php echo esc_attr( ba_fv( $record, 'ind_versamento' ) ); ?>"
                       class="large-text">
            </div>
            <div class="ba-field">
                <label for="cap_versamento"><?php esc_html_e( 'CAP e Località', 'botega-adesioni' ); ?></label>
                <input type="text" id="cap_versamento" name="cap_versamento"
                       value="<?php echo esc_attr( ba_fv( $record, 'cap_versamento' ) ); ?>">
            </div>
        </div>

        <!-- Note -->
        <div class="ba-card">
            <h2><?php esc_html_e( 'Note', 'botega-adesioni' ); ?></h2>
            <textarea id="note" name="note" rows="4" class="large-text"><?php
                echo esc_textarea( ba_fv( $record, 'note' ) );
            ?></textarea>
        </div>

        <!-- Campi custom -->
        <?php if ( ! empty( $custom_fields ) ) : ?>
        <div class="ba-card">
            <h2><?php esc_html_e( 'Campi personalizzati', 'botega-adesioni' ); ?></h2>
            <?php foreach ( $custom_fields as $cf ) : ?>
            <div class="ba-field">
                <label for="cf_<?php echo esc_attr( $cf->field_key ); ?>">
                    <?php echo esc_html( $cf->label ); ?>
                </label>
                <?php echo BA_Custom_Fields::render_input( $cf, $custom_values[ $cf->field_key ] ?? '' ); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Gestione PDF (colonna sinistra per avere più spazio) -->
        <?php if ( $is_edit ) : ?>
        <div class="ba-card">
            <h2><?php esc_html_e( 'Documenti PDF', 'botega-adesioni' ); ?></h2>

            <div class="ba-pdf-genera-bar">
                <select id="ba-pdf-tipo" class="ba-sel-pdf-tipo">
                    <?php foreach ( BA_Database::get_tipi_pdf() as $k => $l ) : ?>
                    <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary ba-genera-pdf-btn" data-id="<?php echo $record->id; ?>">
                    + <?php esc_html_e( 'Genera PDF', 'botega-adesioni' ); ?>
                </button>
            </div>

            <?php
            $pdfs     = BA_Database::get_pdfs( $record->id );
            $tipi_pdf = BA_Database::get_tipi_pdf();
            ?>

            <?php if ( empty( $pdfs ) ) : ?>
            <div class="ba-pdf-empty" id="ba-pdf-empty">
                <?php esc_html_e( 'Nessun documento generato ancora.', 'botega-adesioni' ); ?>
            </div>
            <?php else : ?>
            <div class="ba-pdf-empty" id="ba-pdf-empty" style="display:none;">
                <?php esc_html_e( 'Nessun documento generato ancora.', 'botega-adesioni' ); ?>
            </div>
            <?php endif; ?>

            <div class="ba-pdf-list-wrap" id="ba-pdf-list">
            <?php foreach ( $pdfs as $pdf ) : ?>
            <div class="ba-pdf-item" id="pdf-row-<?php echo $pdf->id; ?>">
                <div class="ba-pdf-item-header">
                    <span class="ba-pdf-tipo-badge">
                        <?php echo esc_html( $tipi_pdf[ $pdf->tipo ] ?? $pdf->tipo ); ?>
                    </span>
                    <span class="ba-pdf-data">
                        <?php echo date_i18n( 'd.m.Y H:i', strtotime( $pdf->data_gen ) ); ?>
                    </span>
                </div>
                <div class="ba-pdf-item-body">
                    <div class="ba-pdf-nome-wrap">
                        <span class="ba-pdf-nome-txt ba-pdf-nome"><?php echo esc_html( $pdf->nome_file ); ?></span>
                        <input type="text" class="ba-pdf-nome-input" value="<?php echo esc_attr( $pdf->nome_file ); ?>">
                    </div>
                    <div class="ba-pdf-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?ba_download_pdf=' . $pdf->id ) ); ?>"
                           class="button button-primary button-small" target="_blank">
                            &#8595; <?php esc_html_e( 'Scarica', 'botega-adesioni' ); ?>
                        </a>
                        <button type="button" class="button button-small ba-rename-pdf-btn"
                                data-pdf-id="<?php echo $pdf->id; ?>">
                            <?php esc_html_e( 'Rinomina', 'botega-adesioni' ); ?>
                        </button>
                        <button type="button" class="button button-small button-link-delete ba-delete-pdf-btn"
                                data-pdf-id="<?php echo $pdf->id; ?>"
                                data-nome="<?php echo esc_attr( $pdf->nome_file ); ?>">
                            <?php esc_html_e( 'Elimina', 'botega-adesioni' ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <?php if ( $record->metodo_pagamento === 'fattura' ) : ?>
            <div class="ba-invia-cedolino-box">
                <button type="button" class="button ba-invia-cedolino-btn" data-id="<?php echo $record->id; ?>">
                    &#9993; <?php esc_html_e( 'Invia cedolino per email', 'botega-adesioni' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'Genera il cedolino PDF e lo invia via email. Aggiorna lo stato a "Inviato" oppure "Errore invio".', 'botega-adesioni' ); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- .ba-col -->

    <!-- ── COLONNA DESTRA ─────────────────────────────────────── -->
    <div class="ba-col ba-col-side">

        <div class="ba-card">
            <h2><?php esc_html_e( 'Quota e importo', 'botega-adesioni' ); ?></h2>

            <div class="ba-field ba-field-check">
                <label>
                    <input type="checkbox" id="quota_250" name="quota_250" value="1"
                           <?php checked( ba_fv( $record, 'quota_250', 0 ), 1 ); ?>>
                    <?php esc_html_e( 'Quota associativa CHF 250.00', 'botega-adesioni' ); ?>
                </label>
            </div>

            <div class="ba-field">
                <label for="donazione"><?php esc_html_e( 'Donazione (CHF)', 'botega-adesioni' ); ?></label>
                <input type="number" id="donazione" name="donazione" step="0.01" min="0"
                       value="<?php echo esc_attr( ba_fv( $record, 'donazione', '0.00' ) ); ?>">
            </div>

            <div class="ba-field ba-importo-display">
                <label><?php esc_html_e( 'Importo totale calcolato', 'botega-adesioni' ); ?></label>
                <div class="ba-total-box" id="ba-total-display">
                    <strong id="ba-total-val">
                        <?php echo BA_List_Table::fmt_chf( (float) ba_fv( $record, 'importo_totale', 0 ) ); ?>
                    </strong>
                </div>
            </div>
        </div>

        <div class="ba-card">
            <h2><?php esc_html_e( 'Pagamento', 'botega-adesioni' ); ?></h2>

            <div class="ba-field">
                <label for="metodo_pagamento"><?php esc_html_e( 'Metodo di pagamento', 'botega-adesioni' ); ?></label>
                <select id="metodo_pagamento" name="metodo_pagamento">
                    <?php echo BA_Record_Form::select_options( $metodi, ba_fv( $record, 'metodo_pagamento', 'fattura' ) ); ?>
                </select>
            </div>

            <div class="ba-field">
                <label for="stato_pagamento"><?php esc_html_e( 'Stato pagamento', 'botega-adesioni' ); ?></label>
                <select id="stato_pagamento" name="stato_pagamento" class="ba-stato-select">
                    <?php echo BA_Record_Form::select_options( $stati, ba_fv( $record, 'stato_pagamento', 'inviato' ) ); ?>
                </select>
                <div class="ba-stato-hint" id="ba-stato-hint"></div>
            </div>

            <?php if ( $is_edit && $record->metodo_pagamento === 'fattura' ) : ?>
            <div class="ba-field">
                <button type="button" class="button ba-richiamo-single"
                        data-id="<?php echo $record->id; ?>">
                    📧 <?php esc_html_e( 'Invia richiamo pagamento', 'botega-adesioni' ); ?>
                </button>
                <p class="description">
                    <?php esc_html_e( 'Invia email di richiamo all\'indirizzo del socio.', 'botega-adesioni' ); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info record -->
        <?php if ( $is_edit ) : ?>
        <div class="ba-card ba-card-info">
            <h2><?php esc_html_e( 'Informazioni record', 'botega-adesioni' ); ?></h2>
            <table class="ba-info-table">
                <tr><td><?php esc_html_e( 'ID DB:', 'botega-adesioni' ); ?></td><td><code>#<?php echo $record->id; ?></code></td></tr>
                <tr><td><?php esc_html_e( 'ID Membro:', 'botega-adesioni' ); ?></td>
                    <td><code class="ba-id-badge"><?php echo esc_html( $record->id_membro ); ?></code></td></tr>
                <tr><td><?php esc_html_e( 'ID Quota:', 'botega-adesioni' ); ?></td>
                    <td><code class="ba-id-badge ba-id-quota"><?php echo esc_html( $record->id_quota ); ?></code></td></tr>
                <tr><td><?php esc_html_e( 'Creato:', 'botega-adesioni' ); ?></td>
                    <td><?php echo date_i18n( 'd.m.Y H:i', strtotime( $record->data_creazione ) ); ?></td></tr>
                <tr><td><?php esc_html_e( 'Modificato:', 'botega-adesioni' ); ?></td>
                    <td><?php echo date_i18n( 'd.m.Y H:i', strtotime( $record->data_modifica ) ); ?></td></tr>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- .ba-col-side -->

</div><!-- .ba-form-grid -->

<div class="ba-form-footer">
    <?php submit_button(
        $is_edit ? __( 'Salva modifiche', 'botega-adesioni' ) : __( 'Crea adesione', 'botega-adesioni' ),
        'primary large',
        'submit',
        false
    ); ?>
    <a href="<?php echo esc_url( BA_Frontend_Admin::get_url( 'ba_admin_lista' ) ); ?>" class="button button-large">
        <?php esc_html_e( 'Annulla', 'botega-adesioni' ); ?>
    </a>
</div>

</form>
</div>
