<?php defined( 'ABSPATH' ) || exit;
$fields      = BA_Custom_Fields::get_all_fields();
$field_types = BA_Custom_Fields::get_field_types();
?>
<div class="wrap ba-wrap">
<p class="description">
    <?php esc_html_e( 'Aggiungi campi aggiuntivi al modulo adesioni. I campi vengono creati automaticamente nel database.', 'botega-adesioni' ); ?>
</p>

<!-- Aggiungi nuovo campo -->
<div class="ba-card" style="max-width:700px; margin-top:15px;">
    <h2><?php esc_html_e( 'Aggiungi nuovo campo', 'botega-adesioni' ); ?></h2>
    <div class="ba-field-add-form">
        <div class="ba-row-3">
            <div class="ba-field">
                <label><?php esc_html_e( 'Label (nome visibile)', 'botega-adesioni' ); ?></label>
                <input type="text" id="new-field-label" placeholder="<?php esc_attr_e( 'es. Data rinnovo', 'botega-adesioni' ); ?>" class="regular-text">
            </div>
            <div class="ba-field">
                <label><?php esc_html_e( 'Tipo di campo', 'botega-adesioni' ); ?></label>
                <select id="new-field-type">
                    <?php foreach ( $field_types as $tk => $tl ) : ?>
                    <option value="<?php echo esc_attr( $tk ); ?>"><?php echo esc_html( $tl ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <!-- Opzioni per radio (visibili solo se tipo=radio) -->
        <div id="new-field-options-wrap" style="display:none;">
            <div class="ba-field">
                <label><?php esc_html_e( 'Opzioni (una per riga)', 'botega-adesioni' ); ?></label>
                <textarea id="new-field-options" rows="3" class="regular-text"
                          placeholder="<?php esc_attr_e( 'Opzione 1\nOpzione 2\nOpzione 3', 'botega-adesioni' ); ?>"></textarea>
            </div>
        </div>
        <button type="button" class="button button-primary" id="ba-add-field-btn">
            + <?php esc_html_e( 'Aggiungi campo', 'botega-adesioni' ); ?>
        </button>
        <div id="ba-add-field-msg" style="display:none;" class="notice inline"></div>
    </div>
</div>

<!-- Elenco campi esistenti -->
<div class="ba-card" style="max-width:900px; margin-top:15px;">
    <h2><?php esc_html_e( 'Campi esistenti', 'botega-adesioni' ); ?></h2>
    <?php if ( empty( $fields ) ) : ?>
        <p><?php esc_html_e( 'Nessun campo personalizzato ancora.', 'botega-adesioni' ); ?></p>
    <?php else : ?>
    <table class="ba-table widefat" id="ba-fields-table">
    <thead>
        <tr>
            <th><?php esc_html_e( '#', 'botega-adesioni' ); ?></th>
            <th><?php esc_html_e( 'Label', 'botega-adesioni' ); ?></th>
            <th><?php esc_html_e( 'Chiave DB', 'botega-adesioni' ); ?></th>
            <th><?php esc_html_e( 'Tipo', 'botega-adesioni' ); ?></th>
            <th><?php esc_html_e( 'Opzioni', 'botega-adesioni' ); ?></th>
            <th><?php esc_html_e( 'Azioni', 'botega-adesioni' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $fields as $f ) : ?>
    <tr data-field-id="<?php echo $f->id; ?>">
        <td><?php echo $f->id; ?></td>
        <td><strong><?php echo esc_html( $f->label ); ?></strong></td>
        <td><code><?php echo esc_html( $f->field_key ); ?></code></td>
        <td><?php echo esc_html( $field_types[ $f->field_type ] ?? $f->field_type ); ?></td>
        <td>
            <?php
            if ( $f->options ) {
                $opts = json_decode( $f->options, true ) ?: [];
                echo esc_html( implode( ', ', $opts ) );
            } else {
                echo '—';
            }
            ?>
        </td>
        <td>
            <button type="button" class="button button-small button-link-delete ba-del-field-btn"
                    data-id="<?php echo $f->id; ?>"
                    data-label="<?php echo esc_attr( $f->label ); ?>">
                🗑️ <?php esc_html_e( 'Elimina', 'botega-adesioni' ); ?>
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>
</div>

</div>
