# Botega Adesioni

Plugin WordPress per la gestione delle domande di adesione alla cooperativa **La Botega da la Lavizzara**.

## Funzionalità

- **Modulo di adesione frontend** tramite shortcode: potenziali soci possono compilare e inviare la domanda direttamente dal sito.
- **Pannello di amministrazione** con elenco, ricerca, filtro per stato ed esportazione CSV delle domande.
- **Gestione stati**: `In attesa` → `In revisione` → `Approvata` / `Rifiutata`.
- **Notifiche email** automatiche all'amministratore (nuova domanda) e al richiedente (conferma invio e aggiornamento stato).
- **Validazione** lato server di tutti i campi obbligatori (codice fiscale, CAP, email, ecc.).
- **Pulizia completa** alla disinstallazione del plugin (tabella DB e opzioni).

## Installazione

1. Carica la cartella `botega-adesioni` nella directory `/wp-content/plugins/`.
2. Attiva il plugin dalla sezione **Plugin** del pannello WordPress.
3. Un nuovo menu **Adesioni** comparirà nella barra laterale dell'amministrazione.

## Utilizzo

### Shortcode modulo di adesione

Inserisci il seguente shortcode in qualsiasi pagina o post per mostrare il modulo:

```
[botega_adesione]
```

### Pannello di amministrazione

- **Adesioni → Elenco domande**: visualizza, filtra per stato e cerca le domande ricevute.
- **Adesioni → Impostazioni**: configura l'email di notifica, attiva/disattiva le email automatiche e personalizza il messaggio di conferma.

### Esportazione CSV

Dall'elenco domande, clicca sul pulsante **Esporta CSV** (in alto a destra) per scaricare tutte le domande o solo quelle con lo stato selezionato.

## Struttura del plugin

```
botega-adesioni/
├── botega-adesioni.php          # Entry point del plugin
├── uninstall.php                # Script di disinstallazione
├── includes/
│   ├── class-botega-adesioni.php  # Classe principale (loader)
│   ├── class-ba-activator.php     # Attivazione / disattivazione
│   ├── class-ba-database.php      # Operazioni database
│   ├── class-ba-email.php         # Notifiche email
│   ├── class-ba-admin.php         # Funzionalità admin
│   └── class-ba-frontend.php      # Funzionalità frontend (shortcode)
├── admin/
│   ├── css/admin.css
│   ├── js/admin.js
│   └── partials/
│       ├── display-list.php       # Vista elenco domande
│       ├── display-detail.php     # Vista dettaglio domanda
│       └── display-settings.php   # Vista impostazioni
├── public/
│   ├── css/public.css
│   └── partials/
│       └── form-adesione.php      # Template modulo di adesione
└── languages/                     # File di traduzione (.pot)
```

## Requisiti

- WordPress 5.8 o superiore
- PHP 7.4 o superiore

## Licenza

GPL v2 o successiva — vedi [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
