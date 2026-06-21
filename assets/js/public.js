/* global BA_Public, jQuery */
(function ($) {
    'use strict';

    // ── Calcolo totale ─────────────────────────────────────────────
    function aggiornaImporto() {
        const quota = $('#pub_quota_250').is(':checked') ? 250 : 0;
        const don   = parseFloat($('#pub_donazione').val()) || 0;
        const tot   = quota + don;

        const fmt = function(n) {
            if (n <= 0) return "CHF 0.00";
            const p    = n.toFixed(2).split('.');
            p[0]       = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            return 'CHF ' + p.join('.');
        };

        $('#ba-pub-totale').text(fmt(tot));
    }

    $(document).on('change', '#pub_quota_250', aggiornaImporto);
    $(document).on('input',  '#pub_donazione', aggiornaImporto);
    aggiornaImporto();

    // ── Toggle donazione ───────────────────────────────────────────
    $('#pub_qdon').on('change', function () {
        const wrap = $('#ba-pub-don-wrap');
        if ($(this).is(':checked')) {
            wrap.addClass('visible');
            $('#pub_donazione').focus();
        } else {
            wrap.removeClass('visible');
            $('#pub_donazione').val(0);
            aggiornaImporto();
        }
    });

    // ── Validazione email lato client ─────────────────────────────
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
    }

    $('#pub_email').on('blur', function () {
        const val = $(this).val().trim();
        if (val && !isValidEmail(val)) {
            $(this).addClass('ba-invalid').removeClass('ba-valid');
            showFieldError($(this), BA_Public.i18n.email_invalid);
        } else if (val) {
            $(this).addClass('ba-valid').removeClass('ba-invalid');
            clearFieldError($(this));
        }
    });

    function showFieldError($input, msg) {
        clearFieldError($input);
        $('<span class="ba-field-error" style="color:#dc3545;font-size:11px;display:block;margin-top:3px;">' + msg + '</span>')
            .insertAfter($input);
    }

    function clearFieldError($input) {
        $input.parent().find('.ba-field-error').remove();
    }

    // ── Validazione form submit ────────────────────────────────────
    $('#ba-public-form').on('submit', function (e) {
        let valid = true;

        // Nome
        const nome = $('#pub_cognome_nome').val().trim();
        if (!nome) {
            showFieldError($('#pub_cognome_nome'), BA_Public.i18n.nome_required);
            $('#pub_cognome_nome').addClass('ba-invalid');
            valid = false;
        }

        // Email
        const email = $('#pub_email').val().trim();
        if (!email) {
            showFieldError($('#pub_email'), BA_Public.i18n.email_required);
            $('#pub_email').addClass('ba-invalid');
            valid = false;
        } else if (!isValidEmail(email)) {
            showFieldError($('#pub_email'), BA_Public.i18n.email_invalid);
            $('#pub_email').addClass('ba-invalid');
            valid = false;
        }

        // Quota/donazione
        const quota = $('#pub_quota_250').is(':checked');
        const don   = parseFloat($('#pub_donazione').val()) > 0;
        if (!quota && !don) {
            alert(BA_Public.i18n.quota_required);
            valid = false;
        }

        // Privacy
        if (!$('#pub_privacy').is(':checked')) {
            showFieldError($('#pub_privacy').closest('.ba-pub-field'), 'Accettare la politica sulla privacy per procedere.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: $('.ba-invalid:first').offset()?.top - 80 || 0 }, 300);
            return;
        }

        // Disabilita pulsante
        $('#ba-pub-submit').text(BA_Public.i18n.sending).prop('disabled', true);
    });

    // ── Autocomplete indirizzo (Nominatim OpenStreetMap – gratuito, no API key) ──

    const NOMINATIM = 'https://nominatim.openstreetmap.org/search';
    let autocompleteTimer = null;
    let currentDropdown   = null;

    function closeAllDropdowns() {
        $('.ba-autocomplete-dropdown').remove();
        currentDropdown = null;
    }

    if (BA_Public.has_maps && BA_Public.maps_key) {
        initPlacesNewAutocomplete(BA_Public.maps_key);
    } else {
        // Nessuna chiave API: Nominatim gratuito
        initNominatimAutocomplete();
    }

    function initNominatimAutocomplete() {
        const $indirizzo = $('#pub_indirizzo');
        if (!$indirizzo.length) return;

        // Wrappa per posizionamento dropdown
        $indirizzo.wrap('<div class="ba-autocomplete-wrap"></div>');

        $indirizzo.on('input', function () {
            const val = $(this).val().trim();
            clearTimeout(autocompleteTimer);

            if (val.length < 3) { closeAllDropdowns(); return; }

            autocompleteTimer = setTimeout(function () {
                $.getJSON(NOMINATIM, {
                    q:               val,
                    format:          'json',
                    addressdetails:  1,
                    limit:           6,
                    countrycodes:    'ch,it,de,fr,at', // Svizzera e paesi vicini
                    'accept-language': 'it',
                })
                .done(function (results) {
                    closeAllDropdowns();
                    if (!results.length) return;

                    const $wrap    = $indirizzo.parent();
                    const $dropdown = $('<div class="ba-autocomplete-dropdown"></div>');

                    results.forEach(function (r) {
                        const addr   = r.address || {};
                        const strada = addr.road || addr.pedestrian || addr.footway || '';
                        const num    = addr.house_number || '';
                        const cap    = addr.postcode || '';
                        const citta  = addr.city || addr.town || addr.village || addr.municipality || '';
                        const paese  = addr.country_code?.toUpperCase() || '';

                        const displayStrada = strada + (num ? ' ' + num : '');
                        const displayLabel  = [displayStrada, cap, citta, paese].filter(Boolean).join(', ');

                        if (!displayLabel) return;

                        $('<div class="ba-autocomplete-item"></div>')
                            .text(displayLabel)
                            .on('mousedown', function (e) {
                                e.preventDefault(); // evita blur dell'input
                                // Compila i campi
                                $indirizzo.val(displayStrada || r.display_name.split(',')[0]);
                                if (cap)   $('#pub_cap').val(cap);
                                if (citta) $('#pub_localita').val(citta);
                                closeAllDropdowns();
                                $indirizzo.blur();
                            })
                            .appendTo($dropdown);
                    });

                    if ($dropdown.children().length) {
                        $wrap.append($dropdown);
                        currentDropdown = $dropdown;
                    }
                });
            }, 400); // debounce 400ms
        });

        // Chiudi dropdown cliccando altrove
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.ba-autocomplete-wrap').length) {
                closeAllDropdowns();
            }
        });

        $indirizzo.on('keydown', function (e) {
            if (!currentDropdown) return;
            const $items = currentDropdown.find('.ba-autocomplete-item');
            const $active = $items.filter('.active');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const $next = $active.length ? $active.removeClass('active').next() : $items.first();
                $next.addClass('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const $prev = $active.length ? $active.removeClass('active').prev() : $items.last();
                $prev.addClass('active');
            } else if (e.key === 'Enter' && $active.length) {
                e.preventDefault();
                $active.trigger('mousedown');
            } else if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });
    }

    function initPlacesNewAutocomplete(apiKey) {
        const PLACES_AC  = 'https://places.googleapis.com/v1/places:autocomplete';
        const PLACES_DET = 'https://places.googleapis.com/v1/';

        const $indirizzo = $('#pub_indirizzo');
        if (!$indirizzo.length) return;

        $indirizzo.wrap('<div class="ba-autocomplete-wrap"></div>');

        let acTimer = null;

        function buildDropdown(suggestions) {
            closeAllDropdowns();
            if (!suggestions || !suggestions.length) return;

            const $wrap     = $indirizzo.parent();
            const $dropdown = $('<div class="ba-autocomplete-dropdown"></div>');

            suggestions.forEach(function (s) {
                const pred = s.placePrediction;
                if (!pred) return;
                const sf    = pred.structuredFormat || {};
                const label = sf.mainText
                    ? sf.mainText.text + (sf.secondaryText ? ', ' + sf.secondaryText.text : '')
                    : (pred.text ? pred.text.text : '');
                if (!label) return;

                $('<div class="ba-autocomplete-item"></div>')
                    .text(label)
                    .on('mousedown', function (e) {
                        e.preventDefault();
                        fetch(PLACES_DET + pred.place + '?languageCode=it', {
                            headers: {
                                'X-Goog-Api-Key':  apiKey,
                                'X-Goog-FieldMask': 'addressComponents',
                            },
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (place) {
                            let strada = '', num = '', cap = '', citta = '';
                            (place.addressComponents || []).forEach(function (c) {
                                const t = c.types || [];
                                if (t.includes('route'))                         strada = c.longText;
                                if (t.includes('street_number'))                 num    = c.longText;
                                if (t.includes('postal_code'))                   cap    = c.longText;
                                if (t.includes('locality') ||
                                    t.includes('administrative_area_level_3'))   citta  = c.longText;
                            });
                            $indirizzo.val(
                                (strada + (num ? ' ' + num : '')).trim() ||
                                label.split(',')[0].trim()
                            );
                            if (cap)   $('#pub_cap').val(cap);
                            if (citta) $('#pub_localita').val(citta);
                            closeAllDropdowns();
                            $indirizzo.blur();
                        })
                        .catch(function () {
                            $indirizzo.val(label.split(',')[0].trim());
                            closeAllDropdowns();
                            $indirizzo.blur();
                        });
                    })
                    .appendTo($dropdown);
            });

            if ($dropdown.children().length) {
                $wrap.append($dropdown);
                currentDropdown = $dropdown;
            }
        }

        $indirizzo.on('input', function () {
            const val = $(this).val().trim();
            clearTimeout(acTimer);
            closeAllDropdowns();
            if (val.length < 3) return;

            acTimer = setTimeout(function () {
                fetch(PLACES_AC, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Goog-Api-Key': apiKey,
                    },
                    body: JSON.stringify({
                        input:               val,
                        languageCode:        'it',
                        includedRegionCodes: ['ch', 'it', 'de', 'fr', 'at'],
                    }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) {
                        console.warn('[BA] Places API (New) errore:', data.error.message);
                        return;
                    }
                    buildDropdown(data.suggestions || []);
                })
                .catch(function (err) {
                    console.warn('[BA] Places API (New) fetch fallito:', err);
                });
            }, 400);
        });

        $indirizzo.on('keydown', function (e) {
            if (!currentDropdown) return;
            const $items  = currentDropdown.find('.ba-autocomplete-item');
            const $active = $items.filter('.active');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                ($active.length ? $active.removeClass('active').next() : $items.first()).addClass('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                ($active.length ? $active.removeClass('active').prev() : $items.last()).addClass('active');
            } else if (e.key === 'Enter' && $active.length) {
                e.preventDefault();
                $active.trigger('mousedown');
            } else if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.ba-autocomplete-wrap').length) {
                closeAllDropdowns();
            }
        });
    }

    // ── CAP: autocompilazione Svizzera ────────────────────────────
    // Se l'utente digita il CAP svizzero (4 cifre), suggerisce la località
    $('#pub_cap').on('blur', function () {
        const cap = $(this).val().trim();
        if (cap.length === 4 && /^\d{4}$/.test(cap) && !$('#pub_localita').val()) {
            $.getJSON(NOMINATIM, {
                postalcode:      cap,
                country:         'CH',
                format:          'json',
                addressdetails:  1,
                limit:           1,
                'accept-language': 'it',
            })
            .done(function (r) {
                if (r.length && r[0].address) {
                    const addr   = r[0].address;
                    const citta  = addr.city || addr.town || addr.village || addr.municipality || '';
                    if (citta) $('#pub_localita').val(citta);
                }
            });
        }
    });

})(jQuery);
