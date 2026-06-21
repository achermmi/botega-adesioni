# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Cos'è questo progetto

Plugin WordPress per la gestione di adesioni e donazioni della Cooperativa "La Botega da la Lavizzara" (CH). Registra soci, gestisce pagamenti, genera PDF (cedolino, attestati, conferme) e invia email automatiche.

**Requisiti:** WordPress ≥ 6.0, PHP ≥ 8.0.

## Comandi principali

Non esiste un sistema di build. Il plugin è PHP puro da installare direttamente in `/wp-content/plugins/`.

```bash
# Installare le dipendenze PHP (mPDF come fallback PDF)
composer install

# Installare il plugin in WordPress (dalla root del plugin)
# Copiare la cartella in wp-content/plugins/botega-adesioni/

# Attivare le tabelle DB (fatto automaticamente all'attivazione del plugin in WP)
# register_activation_hook → BA_Database::install()
```

Non ci sono test automatizzati né linter configurati nel repository.

## Architettura

### Flusso di boot

`botega-adesioni.php` carica tutti i file con `require_once`, poi:
- `register_activation_hook` → `BA_Database::install()` + `BA_Roles::setup()`
- `plugins_loaded` → `BA_Admin::init()` + `BA_Public::init()`
- `init` (priorità 20) → `BA_Pages::setup()`

### Classi principali

| File | Classe | Ruolo |
|------|--------|-------|
| `includes/class-ba-database.php` | `BA_Database` | Unico punto di accesso al DB: CRUD adesioni, PDF, campi custom. Metodi statici. |
| `includes/class-ba-pdf-manager.php` | `BA_PDF_Manager` | Genera PDF da template HTML. Usa dompdf (bundled in `vendor/dompdf/`) come motore primario, mPDF (Composer) come fallback. |
| `includes/class-ba-email.php` | `BA_Email` | Invia email con `wp_mail()`. Template con placeholder `{{nome}}`, `{{importo}}`, ecc. Supporta SMTP opzionale. |
| `includes/class-ba-roles.php` | `BA_Roles` | Gestisce la capability `ba_gestione_adesioni` e il ruolo `membro_comitato`. |
| `includes/class-ba-public.php` | `BA_Public` | Shortcode `[ba_modulo_iscrizione]` per il form pubblico di iscrizione. |
| `includes/class-ba-pages.php` | `BA_Pages` | Crea/recupera la pagina WordPress pubblica contenente lo shortcode. |
| `includes/class-ba-export.php` | `BA_Export` | Esporta adesioni in CSV, XLSX (SpreadsheetML senza librerie) o PDF. |
| `includes/class-ba-custom-fields.php` | `BA_Custom_Fields` | Gestisce campi personalizzati aggiuntivi per le adesioni. |
| `admin/class-ba-admin.php` | `BA_Admin` | Menu WP admin, handler AJAX, pagine admin. Macchina a stati per le email in base al cambio `stato_pagamento`. |
| `admin/class-ba-list-table.php` | `BA_List_Table` | Lista adesioni nell'admin con filtri, ricerca, ordinamento e azioni bulk. |
| `admin/class-ba-record-form.php` | `BA_Record_Form` | Form admin per creare/modificare un'adesione. |
| `admin/class-ba-settings.php` | `BA_Settings` | Impostazioni plugin (email mittente, SMTP, Google Maps key). |

### Tabelle database (prefisso `{wpdb->prefix}`)

- `ba_adesioni` — record principale: dati socio, importi, stato pagamento, metodo pagamento
- `ba_pdf` — metadati dei PDF generati (percorso fisico, URL, tipo)
- `ba_custom_fields` — definizioni campi personalizzati
- `ba_custom_values` — valori dei campi custom per ogni adesione

I nomi tabella si ottengono sempre tramite i metodi statici di `BA_Database` (es. `BA_Database::table_adesioni()`), mai costruendo la stringa a mano.

### Tipi di PDF

- `cedolino` — cedolino di pagamento con QR code Swiss SPC (`vendor/phpqrcode/`)
- `attestato_quota` — attestato quota sociale CHF 250.00
- `attestato_don` — attestato donazione
- `conferma_pag` — conferma di pagamento

I template HTML sono in `templates/pdf-*.php` e vengono inclusi da `BA_PDF_Manager::build_html()`.

### Macchina a stati email (in `BA_Admin::handle_save_record()`)

Quando si salva un record dall'admin, le email vengono inviate automaticamente in base alla transizione di stato:
- Nuovo record con `metodo_pagamento = fattura` → invia cedolino (`inviato` | `errore_invio`)
- Transizione a `pagato` → invia conferma + attestato quota (se quota_250=1) + attestato donazione (se donazione>0)
- Transizione a `socio_onorario` → invia conferma + attestato quota (se quota_250=1), ma NON attestato donazione

### Opzioni WordPress usate dal plugin

Tutte salvate con `update_option()`:
- `ba_email_mittente_nome/email`, `ba_email_cedolino/conferma/richiamo_oggetto/corpo`
- `ba_smtp_abilitato`, `ba_smtp_host/port/user/pass/secure`
- `ba_google_maps_key`
- `ba_pagina_iscrizione_id` (ID pagina pubblica)
- `ba_db_version`

### ID univoci

Ogni adesione riceve automaticamente:
- `id_membro`: formato `BLM-{ANNO}-{4CHARS}` (es. `BLM-2024-A3F9`)
- `id_quota`: formato `BLQ-{ANNO}-{8CHARS}` (es. `BLQ-2024-A3F9B2C1`)

### Accesso e capability

Il gate unico è la capability `ba_gestione_adesioni`. Usare sempre `BA_Roles::current_user_can()` per i controlli di accesso. Gli amministratori WordPress la ricevono sempre; il ruolo `membro_comitato` è per utenti non-admin che devono accedere al plugin.

### Template pubblico

Il form pubblico `templates/public-form.php` viene caricato tramite `ob_start()` dallo shortcode. Il submit POST viene gestito da `BA_Public::handle_public_post()` agganciato a `init` (prima dell'output). Valida il dominio email con `checkdnsrr()`.

### PDF fisici

I PDF vengono salvati in `wp-uploads/botega-adesioni/` con `.htaccess` che blocca l'accesso diretto. Il download avviene tramite `?ba_download_pdf={pdf_id}` nell'area admin, servito da `BA_Admin::serve_pdf()`. Se il file fisico manca, viene rigenerato al volo.
