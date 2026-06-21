<?php defined( 'ABSPATH' ) || exit;
/**
 * @var array  $result
 * @var array  $stats
 * @var array  $stati
 * @var array  $tipi
 * @var array  $metodi
 * @var string $export_base
 * @var string $search
 * @var string $orderby
 * @var string $order
 * @var string $filter_logic
 * @var array  $filters
 * @var int    $page
 * @var int    $per_page
 */
?>
<div class="wrap ba-wrap">
<?php if ( current_user_can( 'manage_options' ) ) : ?>
<a href="<?php echo esc_url( BA_Frontend_Admin::get_url( 'ba_admin_form' ) ); ?>" class="page-title-action">
    <?php esc_html_e( '+ Nuova adesione (admin)', 'botega-adesioni' ); ?>
</a>
<?php endif; ?>
<?php
$pub_url = BA_Pages::get_url();
if ( $pub_url ) : ?>
<a href="<?php echo esc_url( $pub_url ); ?>" class="page-title-action" target="_blank"
   style="background:#d4e000;color:#2c3a00;border-color:#2c3a00;">
    🌐 <?php esc_html_e( 'Pagina iscrizione pubblica', 'botega-adesioni' ); ?> ↗
</a>
<?php endif; ?>
<hr class="wp-header-end">

<!-- STATISTICHE -->
<div class="ba-stats-bar">
    <div class="ba-stat">
        <span class="ba-stat-num"><?php echo $stats['totale']; ?></span>
        <span class="ba-stat-lbl"><?php esc_html_e( 'Totale', 'botega-adesioni' ); ?></span>
    </div>
    <div class="ba-stat ba-stat-green">
        <span class="ba-stat-num"><?php echo $stats['pagati']; ?></span>
        <span class="ba-stat-lbl"><?php esc_html_e( 'Pagati', 'botega-adesioni' ); ?></span>
    </div>
    <div class="ba-stat ba-stat-orange">
        <span class="ba-stat-num"><?php echo $stats['sospesi'] + $stats['inviati']; ?></span>
        <span class="ba-stat-lbl"><?php esc_html_e( 'In attesa', 'botega-adesioni' ); ?></span>
    </div>
    <div class="ba-stat ba-stat-money">
        <span class="ba-stat-num"><?php echo BA_List_Table::fmt_chf( $stats['incasso'] ); ?></span>
        <span class="ba-stat-lbl"><?php esc_html_e( 'Incassato', 'botega-adesioni' ); ?></span>
    </div>
    <div class="ba-stat ba-stat-pending">
        <span class="ba-stat-num"><?php echo BA_List_Table::fmt_chf( $stats['in_attesa'] ); ?></span>
        <span class="ba-stat-lbl"><?php esc_html_e( 'Da incassare', 'botega-adesioni' ); ?></span>
    </div>
</div>

<!-- QUICK FILTERS PER STATO -->
<div class="ba-quick-filters">
    <?php
    $stato_filter = $_GET['stato'] ?? '';
    $all_url = remove_query_arg( [ 'stato', 'paged' ] );
    ?>
    <a href="<?php echo esc_url( $all_url ); ?>" class="ba-qf <?php echo $stato_filter === '' ? 'active' : ''; ?>">
        <?php esc_html_e( 'Tutti', 'botega-adesioni' ); ?>
    </a>
    <?php foreach ( $stati as $key => $label ) :
        $url = add_query_arg( [ 'stato' => $key, 'paged' => 1 ] );
    ?>
    <a href="<?php echo esc_url( $url ); ?>" class="ba-qf ba-qf-<?php echo esc_attr( $key ); ?> <?php echo $stato_filter === $key ? 'active' : ''; ?>">
        <?php echo esc_html( $label ); ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- FILTRI AVANZATI -->
<div class="ba-filters-panel" id="ba-filters-panel">
    <form method="get" id="ba-filter-form">
        <?php if ( is_admin() ) : ?><input type="hidden" name="page" value="botega-adesioni"><?php endif; ?>
        <?php if ( $stato_filter ) : ?>
            <input type="hidden" name="stato" value="<?php echo esc_attr( $stato_filter ); ?>">
        <?php endif; ?>

        <!-- Ricerca rapida -->
        <div class="ba-search-row">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
                   placeholder="<?php esc_attr_e( 'Cerca nome, email, telefono…', 'botega-adesioni' ); ?>"
                   class="ba-search-input">
            <select name="filter_logic" class="ba-logic-sel" title="<?php esc_attr_e( 'Logica di filtro', 'botega-adesioni' ); ?>">
                <option value="AND" <?php selected( $filter_logic, 'AND' ); ?>><?php esc_html_e( 'Logica: E (AND)', 'botega-adesioni' ); ?></option>
                <option value="OR"  <?php selected( $filter_logic, 'OR' );  ?>><?php esc_html_e( 'Logica: O (OR)',  'botega-adesioni' ); ?></option>
            </select>
            <button type="button" class="button" id="ba-add-filter">
                + <?php esc_html_e( 'Aggiungi filtro', 'botega-adesioni' ); ?>
            </button>
            <?php submit_button( __( 'Applica', 'botega-adesioni' ), 'primary', '', false ); ?>
            <a href="<?php echo esc_url( BA_Frontend_Admin::get_url( 'ba_admin_lista' ) ); ?>" class="button">
                <?php esc_html_e( 'Reset', 'botega-adesioni' ); ?>
            </a>
        </div>

        <!-- Righe filtri avanzati -->
        <div id="ba-filters-rows">
        <?php
        $filter_fields = [
            'cognome_nome'     => __( 'Cognome e nome',   'botega-adesioni' ),
            'email'            => __( 'E-mail',           'botega-adesioni' ),
            'tipo_socio'       => __( 'Tipo socio',       'botega-adesioni' ),
            'stato_pagamento'  => __( 'Stato pagamento',  'botega-adesioni' ),
            'metodo_pagamento' => __( 'Metodo pagamento', 'botega-adesioni' ),
            'data_adesione'    => __( 'Data adesione',    'botega-adesioni' ),
            'importo_totale'   => __( 'Importo totale',   'botega-adesioni' ),
            'localita'            => __( 'Luogo',            'botega-adesioni' ),
        ];
        foreach ( $filters as $i => $f ) :
        ?>
        <div class="ba-filter-row" data-index="<?php echo $i; ?>">
            <select name="filters[<?php echo $i; ?>][campo]" class="ba-f-campo">
                <?php foreach ( $filter_fields as $fk => $fl ) : ?>
                <option value="<?php echo esc_attr( $fk ); ?>" <?php selected( $f['campo'], $fk ); ?>>
                    <?php echo esc_html( $fl ); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="filters[<?php echo $i; ?>][operatore]" class="ba-f-op">
                <?php foreach ( BA_List_Table::get_operators() as $ok => $ol ) : ?>
                <option value="<?php echo esc_attr( $ok ); ?>" <?php selected( $f['operatore'], $ok ); ?>>
                    <?php echo esc_html( $ol ); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="filters[<?php echo $i; ?>][valore]"
                   value="<?php echo esc_attr( $f['valore'] ); ?>" class="ba-f-val">
            <button type="button" class="button-link ba-remove-filter" title="<?php esc_attr_e( 'Rimuovi', 'botega-adesioni' ); ?>">✕</button>
        </div>
        <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- AZIONI BULK + EXPORT -->
<div class="ba-toolbar">
    <div class="ba-bulk-actions">
        <button type="button" class="button ba-btn-richiamo" id="ba-richiamo-btn">
            📧 <?php esc_html_e( 'Invia richiamo ai selezionati', 'botega-adesioni' ); ?>
        </button>
        <button type="button" class="button ba-btn-richiamo-all" id="ba-richiamo-all-btn">
            📧 <?php esc_html_e( 'Richiamo a tutti sospesi/inviati', 'botega-adesioni' ); ?>
        </button>
    </div>
    <div class="ba-export-buttons">
        <strong><?php esc_html_e( 'Esporta:', 'botega-adesioni' ); ?></strong>
        <a href="<?php echo esc_url( add_query_arg( 'ba_export', 'csv',  $export_base ) ); ?>" class="button button-small">
            📄 CSV
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'ba_export', 'xlsx', $export_base ) ); ?>" class="button button-small">
            📊 Excel
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'ba_export', 'pdf',  $export_base ) ); ?>" class="button button-small" target="_blank">
            🖨️ PDF/Stampa
        </a>
    </div>
</div>

<!-- TABELLA -->
<form id="ba-list-form">
<table class="ba-table widefat striped">
<thead>
<tr>
    <th class="ba-col-check"><input type="checkbox" id="ba-check-all"></th>
    <th><?php BA_List_Table::sortable_header( 'id', '#', $orderby, $order ); ?></th>
    <th><?php esc_html_e( 'ID Membro', 'botega-adesioni' ); ?></th>
    <th><?php BA_List_Table::sortable_header( 'cognome_nome', __( 'Cognome e nome', 'botega-adesioni' ), $orderby, $order ); ?></th>
    <th><?php BA_List_Table::sortable_header( 'tipo_socio', __( 'Tipo socio', 'botega-adesioni' ), $orderby, $order ); ?></th>
    <th><?php BA_List_Table::sortable_header( 'metodo_pagamento', __( 'Metodo', 'botega-adesioni' ), $orderby, $order ); ?></th>
    <th><?php BA_List_Table::sortable_header( 'stato_pagamento', __( 'Stato', 'botega-adesioni' ), $orderby, $order ); ?></th>
    <th><?php BA_List_Table::sortable_header( 'importo_totale', __( 'Importo', 'botega-adesioni' ), $orderby, $order ); ?></th>
    <th><?php BA_List_Table::sortable_header( 'data_adesione', __( 'Data', 'botega-adesioni' ), $orderby, $order ); ?></th>
    <th><?php esc_html_e( 'Azioni', 'botega-adesioni' ); ?></th>
</tr>
</thead>
<tbody>
<?php if ( empty( $result['rows'] ) ) : ?>
<tr><td colspan="10" class="ba-empty">
    <?php esc_html_e( 'Nessun record trovato.', 'botega-adesioni' ); ?>
</td></tr>
<?php else : ?>
<?php foreach ( $result['rows'] as $r ) :
    $edit_url = BA_Frontend_Admin::get_url( 'ba_admin_form', [ 'id' => $r->id ] );
?>
<tr data-id="<?php echo $r->id; ?>">
    <td><input type="checkbox" name="ba_ids[]" value="<?php echo $r->id; ?>" class="ba-row-check"></td>
    <td><?php echo $r->id; ?></td>
    <td>
        <code style="font-size:10px;background:#2c3a00;color:#d4e000;padding:1px 5px;border-radius:2px;">
            <?php echo esc_html( $r->id_membro ); ?>
        </code>
    </td>
    <td>
        <a href="<?php echo esc_url( $edit_url ); ?>" class="ba-name-link">
            <strong><?php echo esc_html( $r->cognome_nome ); ?></strong>
        </a>
        <?php if ( $r->email ) : ?>
        <br><small class="ba-email"><?php echo esc_html( $r->email ); ?></small>
        <?php endif; ?>
        <?php if ( ! empty( $r->attestato_data ) ) : ?>
        <br><small title="<?php esc_attr_e( 'Attestato generato', 'botega-adesioni' ); ?>">📄</small>
        <?php endif; ?>
    </td>
    <td><?php echo esc_html( $tipi[ $r->tipo_socio ] ?? $r->tipo_socio ); ?></td>
    <td><?php echo esc_html( $metodi[ $r->metodo_pagamento ] ?? $r->metodo_pagamento ); ?></td>
    <td><?php echo BA_List_Table::stato_badge( $r->stato_pagamento ); ?></td>
    <td class="ba-amount"><?php
        $__tot = (float) $r->importo_totale;
        if ( $__tot <= 0 ) {
            $__tot = ( ! empty( $r->quota_250 ) ? 250.00 : 0.0 ) + (float) ( $r->donazione ?? 0 );
        }
        $__has_quota = ! empty( $r->quota_250 );
        $__has_don   = (float) ( $r->donazione ?? 0 ) > 0;
        if ( $__has_quota && $__has_don ) {
            echo '<small class="ba-imp-tipo">Quota + donazione</small><br>';
        } elseif ( $__has_don ) {
            echo '<small class="ba-imp-tipo">Donazione</small><br>';
        } elseif ( $__has_quota ) {
            echo '<small class="ba-imp-tipo">Quota</small><br>';
        }
        echo BA_List_Table::fmt_chf( $__tot );
    ?></td>
    <td><?php echo $r->data_adesione ? esc_html( date_i18n( 'd.m.Y', strtotime( $r->data_adesione ) ) ) : '—'; ?></td>
    <td class="ba-actions">
        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
            ✏️ <?php esc_html_e( 'Modifica', 'botega-adesioni' ); ?>
        </a>
        <button type="button" class="button button-small button-link-delete ba-delete-btn"
                data-id="<?php echo $r->id; ?>"
                data-name="<?php echo esc_attr( $r->cognome_nome ); ?>">
            🗑️ <?php esc_html_e( 'Elimina', 'botega-adesioni' ); ?>
        </button>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</form>

<!-- PAGINAZIONE -->
<?php if ( $result['pages'] > 1 ) : ?>
<div class="ba-pagination tablenav-pages">
    <span class="displaying-num">
        <?php printf( _n( '%d record', '%d record', $result['total'], 'botega-adesioni' ), $result['total'] ); ?>
    </span>
    <?php
    $page_links = paginate_links([
        'base'    => add_query_arg( 'paged', '%#%' ),
        'format'  => '',
        'current' => $page,
        'total'   => $result['pages'],
        'type'    => 'array',
    ]);
    if ( $page_links ) echo '<span class="pagination-links">' . implode( '', $page_links ) . '</span>';
    ?>
</div>
<?php endif; ?>

</div><!-- .ba-wrap -->

<!-- TEMPLATE RIGA FILTRO (usato da JS) -->
<script type="text/html" id="ba-filter-row-tpl">
<div class="ba-filter-row" data-index="__IDX__">
    <select name="filters[__IDX__][campo]" class="ba-f-campo">
        <?php foreach ( $filter_fields as $fk => $fl ) : ?>
        <option value="<?php echo esc_attr( $fk ); ?>"><?php echo esc_html( $fl ); ?></option>
        <?php endforeach; ?>
    </select>
    <select name="filters[__IDX__][operatore]" class="ba-f-op">
        <?php foreach ( BA_List_Table::get_operators() as $ok => $ol ) : ?>
        <option value="<?php echo esc_attr( $ok ); ?>"><?php echo esc_html( $ol ); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="filters[__IDX__][valore]" value="" class="ba-f-val">
    <button type="button" class="button-link ba-remove-filter" title="Rimuovi">✕</button>
</div>
</script>