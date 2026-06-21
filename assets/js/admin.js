/* global BA, jQuery */
(function ($) {
    'use strict';

    // ── Elimina record ──────────────────────────────────────────────
    $(document).on('click', '.ba-delete-btn', function () {
        const id       = $(this).data('id');
        const name     = $(this).data('name');
        const redirect = $(this).data('redirect') || '';

        if (!confirm(BA.i18n.confirm_delete + '\n\n"' + name + '" (#' + id + ')')) return;

        const $btn = $(this);
        $btn.text(BA.i18n.deleting).prop('disabled', true);

        $.post(BA.ajaxurl, {
            action : 'ba_delete_record',
            nonce  : BA.nonce,
            id     : id,
        })
        .done(function (res) {
            if (res.success) {
                if (redirect) {
                    window.location.href = redirect;
                } else {
                    // Ricarica pagina per aggiornare le statistiche
                    $btn.closest('tr').fadeOut(300, function () {
                        window.location.reload();
                    });
                }
            } else {
                alert(res.data || BA.i18n.error);
                $btn.text('🗑️ Elimina').prop('disabled', false);
            }
        })
        .fail(function () {
            alert(BA.i18n.error);
            $btn.prop('disabled', false);
        });
    });

    // ── Check all ────────────────────────────────────────────────────
    $(document).on('change', '#ba-check-all', function () {
        $('.ba-row-check').prop('checked', $(this).is(':checked'));
    });

    // ── Aggiungi riga filtro ──────────────────────────────────────────
    var filterIdx = $('#ba-filters-rows .ba-filter-row').length;

    $(document).on('click', '#ba-add-filter', function () {
        const tpl = $('#ba-filter-row-tpl').html();
        if (!tpl) return;
        const row = tpl.replace(/__IDX__/g, filterIdx++);
        $('#ba-filters-rows').append(row);
    });

    $(document).on('click', '.ba-remove-filter', function () {
        $(this).closest('.ba-filter-row').remove();
    });

    // ── Richiamo massivo (selezionati) ────────────────────────────────
    $('#ba-richiamo-btn').on('click', function () {
        const ids = $('.ba-row-check:checked').map(function () {
            return $(this).val();
        }).get();

        if (ids.length === 0) {
            alert('Selezionare almeno un record.');
            return;
        }
        if (!confirm(BA.i18n.confirm_richiamo)) return;

        doRichiamo(ids, $(this));
    });

    // ── Richiamo massivo (tutti sospesi/inviati) ──────────────────────
    $('#ba-richiamo-all-btn').on('click', function () {
        if (!confirm(BA.i18n.confirm_richiamo)) return;
        doRichiamo([], $(this));
    });

    function doRichiamo(ids, $btn) {
        const orig = $btn.text();
        $btn.text(BA.i18n.sending).prop('disabled', true);

        $.post(BA.ajaxurl, {
            action : 'ba_richiamo_massivo',
            nonce  : BA.nonce,
            ids    : ids,
        })
        .done(function (res) {
            if (res.success) {
                const d = res.data;
                alert('✓ Richiamo completato!\nInviati: ' + d.inviati +
                      '\nErrori: ' + d.errori +
                      '\nSaltati (senza email): ' + (d.saltati || 0));
            } else {
                alert(BA.i18n.error);
            }
        })
        .fail(function () { alert(BA.i18n.error); })
        .always(function () { $btn.text(orig).prop('disabled', false); });
    }

    // ── Richiamo singolo ────────────────────────────────────────────
    $(document).on('click', '.ba-richiamo-single', function () {
        const id   = $(this).data('id');
        const $btn = $(this);
        const orig = $btn.text();
        if (!confirm(BA.i18n.confirm_richiamo)) return;
        $btn.text(BA.i18n.sending).prop('disabled', true);

        $.post(BA.ajaxurl, {
            action : 'ba_richiamo_massivo',
            nonce  : BA.nonce,
            ids    : [id],
        })
        .done(function (res) {
            alert(res.success ? '✓ Email di richiamo inviata.' : BA.i18n.error);
        })
        .fail(function () { alert(BA.i18n.error); })
        .always(function () { $btn.text(orig).prop('disabled', false); });
    });

    // ── Genera PDF ──────────────────────────────────────────────────
    $(document).on('click', '.ba-genera-pdf-btn', function () {
        const id   = $(this).data('id');
        const tipo = $('#ba-pdf-tipo').val();
        const $btn = $(this);
        const orig = $btn.text();
        $btn.text('Generazione…').prop('disabled', true);

        $.post(BA.ajaxurl, { action: 'ba_genera_pdf', nonce: BA.nonce, id, tipo })
        .done(function (res) {
            if (res.success) {
                const d = res.data;
                const tipoLabel = $('#ba-pdf-tipo option:selected').text();
                const now = new Date().toLocaleString('it-CH', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
                const card = `<div class="ba-pdf-item" id="pdf-row-${d.pdf_id}">
  <div class="ba-pdf-item-header">
    <span class="ba-pdf-tipo-badge">${tipoLabel}</span>
    <span class="ba-pdf-data">${now}</span>
  </div>
  <div class="ba-pdf-item-body">
    <div class="ba-pdf-nome-wrap">
      <span class="ba-pdf-nome-txt ba-pdf-nome">${d.nome_file}</span>
      <input type="text" class="ba-pdf-nome-input" value="${d.nome_file}" style="display:none">
    </div>
    <div class="ba-pdf-actions">
      <a href="${d.url}" class="button button-primary button-small" target="_blank">&#8595; Scarica</a>
      <button type="button" class="button button-small ba-rename-pdf-btn" data-pdf-id="${d.pdf_id}">Rinomina</button>
      <button type="button" class="button button-small button-link-delete ba-delete-pdf-btn" data-pdf-id="${d.pdf_id}" data-nome="${d.nome_file}">Elimina</button>
    </div>
  </div>
</div>`;
                $('#ba-pdf-list').prepend(card);
                $('#ba-pdf-empty').hide();
                alert('OK: ' + d.message);
            } else {
                alert('Errore: ' + (res.data || BA.i18n.error));
            }
        })
        .fail(() => alert(BA.i18n.error))
        .always(() => $btn.text(orig).prop('disabled', false));
    });

    // ── Elimina PDF ────────────────────────────────────────────────
    $(document).on('click', '.ba-delete-pdf-btn', function () {
        const pdfId = $(this).data('pdf-id');
        const nome  = $(this).data('nome');
        if (!confirm(`Eliminare il file "${nome}"? L'operazione non può essere annullata.`)) return;
        const $item = $(`#pdf-row-${pdfId}`);
        $.post(BA.ajaxurl, { action: 'ba_delete_pdf', nonce: BA.nonce, pdf_id: pdfId })
        .done(res => {
            if (res.success) {
                $item.fadeOut(300, () => {
                    $item.remove();
                    if ($('#ba-pdf-list .ba-pdf-item').length === 0) $('#ba-pdf-empty').show();
                });
            } else alert(BA.i18n.error);
        });
    });

    // ── Rinomina PDF ───────────────────────────────────────────────
    $(document).on('click', '.ba-rename-pdf-btn', function () {
        const pdfId = $(this).data('pdf-id');
        const $row  = $(`#pdf-row-${pdfId}`);
        const $txt  = $row.find('.ba-pdf-nome-txt');
        const $inp  = $row.find('.ba-pdf-nome-input');
        if ($inp.is(':visible')) {
            // Salva
            const nuovo = $inp.val().trim();
            if (!nuovo) return;
            $.post(BA.ajaxurl, { action: 'ba_rename_pdf', nonce: BA.nonce, pdf_id: pdfId, nuovo_nome: nuovo })
            .done(res => {
                if (res.success) { $txt.text(nuovo); $(this).text('✏️ Rinomina'); }
                else alert(BA.i18n.error);
            });
            $inp.hide(); $txt.show();
        } else {
            $txt.hide(); $inp.show().focus();
            $(this).text('💾 Salva');
        }
    });

    // ── Invia cedolino ─────────────────────────────────────────────
    $(document).on('click', '.ba-invia-cedolino-btn', function () {
        const id   = $(this).data('id');
        const $btn = $(this);
        const orig = $btn.text();
        if (!confirm('Inviare il cedolino di pagamento via email?')) return;
        $btn.text(BA.i18n.sending).prop('disabled', true);
        $.post(BA.ajaxurl, { action: 'ba_invia_cedolino', nonce: BA.nonce, id })
        .done(res => {
            if (res.success) {
                alert('✅ ' + res.data.message);
                location.reload();
            } else {
                alert('❌ ' + (res.data?.message || BA.i18n.error));
                location.reload();
            }
        })
        .fail(() => alert(BA.i18n.error))
        .always(() => $btn.text(orig).prop('disabled', false));
    });
    function aggiornaImporto() {
        const quota   = $('#quota_250').is(':checked') ? 250 : 0;
        const don     = parseFloat($('#donazione').val().replace(/'/g, '')) || 0;
        const totale  = quota + don;

        const parts   = totale.toFixed(2).split('.');
        const intFmt  = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "'");
        const display = totale > 0 ? 'CHF ' + intFmt + '.' + parts[1] : 'CHF 0.00';

        $('#ba-total-val').text(display);
    }

    $(document).on('change', '#quota_250', aggiornaImporto);
    $(document).on('input',  '#donazione',  aggiornaImporto);
    aggiornaImporto(); // calcola importo all'apertura della pagina

    // ── Hint stato pagamento ──────────────────────────────────────────
    const statoHints = {
        inviato        : 'Pagamento fattura inviato per email al nuovo aderente.',
        sospeso        : 'Pagamento sospeso dal segretariato per accordi particolari.',
        pagato         : 'Fattura pagata e importo ricevuto sul conto IBAN.',
        socio_onorario : 'L\'importo non viene fatturato — socio onorario.',
        stornato       : 'Pagamento stornato per errori o doppioni.',
        annullato      : 'Adesione annullata per mancato pagamento.',
    };

    function aggiornaHintStato() {
        const val  = $('#stato_pagamento').val();
        const hint = statoHints[val] || '';
        $('#ba-stato-hint').text(hint);
    }

    $(document).on('change', '#stato_pagamento', aggiornaHintStato);
    aggiornaHintStato(); // init

    // ── Aggiungi campo custom ─────────────────────────────────────────
    $('#new-field-type').on('change', function () {
        if ($(this).val() === 'radio') {
            $('#new-field-options-wrap').slideDown(200);
        } else {
            $('#new-field-options-wrap').slideUp(200);
        }
    });

    $('#ba-add-field-btn').on('click', function () {
        const label     = $('#new-field-label').val().trim();
        const fieldType = $('#new-field-type').val();
        const optsTxt   = $('#new-field-options').val();
        const options   = fieldType === 'radio'
            ? optsTxt.split('\n').map(s => s.trim()).filter(Boolean)
            : [];

        if (!label) {
            showFieldMsg('error', 'Inserire una label per il campo.');
            return;
        }

        $.post(BA.ajaxurl, {
            action     : 'ba_add_custom_field',
            nonce      : BA.nonce,
            label      : label,
            field_type : fieldType,
            options    : options,
        })
        .done(function (res) {
            if (res.success) {
                showFieldMsg('success', res.data.message);
                $('#new-field-label').val('');
                $('#new-field-options').val('');
                // Ricarica la pagina per mostrare il nuovo campo
                setTimeout(() => location.reload(), 800);
            } else {
                showFieldMsg('error', res.data || BA.i18n.error);
            }
        })
        .fail(function () { showFieldMsg('error', BA.i18n.error); });
    });

    function showFieldMsg(type, msg) {
        $('#ba-add-field-msg')
            .removeClass('notice-success notice-error')
            .addClass('notice-' + type)
            .text(msg)
            .show();
    }

    // ── Elimina campo custom ──────────────────────────────────────────
    $(document).on('click', '.ba-del-field-btn', function () {
        const id    = $(this).data('id');
        const label = $(this).data('label');
        if (!confirm('Eliminare il campo "' + label + '"? Tutti i dati associati verranno persi.')) return;

        const $row = $(this).closest('tr');
        $.post(BA.ajaxurl, {
            action : 'ba_del_custom_field',
            nonce  : BA.nonce,
            id     : id,
        })
        .done(function (res) {
            if (res.success) $row.fadeOut(300, () => $row.remove());
            else alert(BA.i18n.error);
        })
        .fail(function () { alert(BA.i18n.error); });
    });

})(jQuery);
