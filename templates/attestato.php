<?php
defined( 'ABSPATH' ) || exit;
/**
 * Template attestato membro cooperativa
 *
 * Variabili disponibili:
 * @var object $record      – record adesione dal DB
 * @var string $tipo_label  – label leggibile del tipo socio
 * @var string $anno        – anno di adesione
 * @var string $data_fmt    – data formattata GG.MM.AAAA
 * @var string $logo_b64    – logo in base64 (se presente)
 * @var string $importo_fmt – importo formattato CHF
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php printf( esc_html__( 'Attestato di Membro – %s', 'botega-adesioni' ), esc_html( $record->cognome_nome ) ); ?></title>
<style>
/* ── Reset ──────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Variabili colori Botega ────────────────────────────────── */
:root {
  --verde:   #2c3a00;
  --giallo:  #d4e000;
  --ocra:    #C8960A;
  --rame:    #A06828;
  --noce:    #6B4A1E;
  --prato:   #5C7A4A;
  --avorio:  #F8F0E0;
  --bianco:  #ffffff;
}

/* ── Pagina ─────────────────────────────────────────────────── */
html, body {
  width: 210mm; height: 297mm;
  margin: 0 auto;
  background: var(--avorio);
  font-family: 'Georgia', 'Times New Roman', serif;
  color: var(--verde);
}

.attestato {
  width: 210mm; height: 297mm;
  padding: 0;
  position: relative;
  overflow: hidden;
  background: var(--avorio);
  display: flex;
  flex-direction: column;
}

/* ── Bordo decorativo esterno ───────────────────────────────── */
.outer-border {
  position: absolute;
  inset: 8mm;
  border: 3px solid var(--ocra);
  pointer-events: none;
  z-index: 10;
}
.inner-border {
  position: absolute;
  inset: 10mm;
  border: 1px solid var(--rame);
  pointer-events: none;
  z-index: 10;
}

/* Angoli decorativi */
.corner {
  position: absolute;
  width: 12mm; height: 12mm;
  z-index: 11;
}
.corner-tl { top:  6mm; left:  6mm; border-top: 4px solid var(--ocra); border-left:  4px solid var(--ocra); }
.corner-tr { top:  6mm; right: 6mm; border-top: 4px solid var(--ocra); border-right: 4px solid var(--ocra); }
.corner-bl { bottom: 6mm; left:  6mm; border-bottom: 4px solid var(--ocra); border-left:  4px solid var(--ocra); }
.corner-br { bottom: 6mm; right: 6mm; border-bottom: 4px solid var(--ocra); border-right: 4px solid var(--ocra); }

/* ── Header ─────────────────────────────────────────────────── */
.header {
  padding: 14mm 20mm 8mm 20mm;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid var(--ocra);
}
.header-logo img {
  height: 24mm;
  width: auto;
}
.header-logo-text {
  font-family: Georgia, serif;
  font-size: 9pt;
  color: var(--prato);
  text-align: right;
  line-height: 1.6;
}
.header-logo-text strong {
  font-size: 12pt;
  color: var(--verde);
  display: block;
  margin-bottom: 2px;
}

/* ── Titolo ─────────────────────────────────────────────────── */
.title-section {
  padding: 8mm 20mm 5mm 20mm;
  text-align: center;
}
.title-label {
  font-size: 9pt;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--rame);
  margin-bottom: 3mm;
  font-family: Arial, sans-serif;
}
.title-main {
  font-size: 22pt;
  font-weight: bold;
  color: var(--verde);
  letter-spacing: 1px;
  line-height: 1.2;
}
.title-sub {
  font-size: 11pt;
  color: var(--noce);
  margin-top: 2mm;
  font-style: italic;
}
.title-divider {
  margin: 5mm auto;
  width: 60mm;
  height: 2px;
  background: linear-gradient(to right, transparent, var(--ocra), transparent);
}

/* ── Corpo certificato ──────────────────────────────────────── */
.body-section {
  padding: 5mm 24mm;
  text-align: center;
  flex: 1;
}
.body-intro {
  font-size: 10pt;
  color: var(--noce);
  line-height: 1.7;
  margin-bottom: 6mm;
}

/* Nome del membro */
.member-name {
  font-size: 24pt;
  font-weight: bold;
  color: var(--verde);
  letter-spacing: 1px;
  padding: 4mm 10mm;
  border-top: 1.5px solid var(--ocra);
  border-bottom: 1.5px solid var(--ocra);
  margin: 4mm 0 6mm 0;
  display: inline-block;
  min-width: 120mm;
}

.body-type {
  font-size: 13pt;
  color: var(--prato);
  font-style: italic;
  margin-bottom: 6mm;
}

/* Dati quota ── tabella centrata */
.quota-table {
  margin: 0 auto 6mm auto;
  border-collapse: separate;
  border-spacing: 0;
  width: auto;
  border: 1.5px solid var(--ocra);
  border-radius: 4px;
  overflow: hidden;
}
.quota-table th {
  background: var(--verde);
  color: var(--giallo);
  font-family: Arial, sans-serif;
  font-size: 8pt;
  padding: 3mm 6mm;
  text-align: left;
  letter-spacing: .5px;
}
.quota-table td {
  background: #fff;
  padding: 3mm 6mm;
  font-size: 10pt;
  color: var(--verde);
  border-top: 1px solid #e8e0d0;
}
.quota-table tr:first-child td { border-top: none; }

/* ID badge */
.id-badge {
  display: inline-block;
  background: var(--verde);
  color: var(--giallo);
  font-family: 'Courier New', monospace;
  font-size: 11pt;
  font-weight: bold;
  letter-spacing: 2px;
  padding: 2mm 5mm;
  border-radius: 3px;
}

/* ── Corpo testo legale ──────────────────────────────────────── */
.legal-text {
  padding: 0 24mm;
  font-size: 8.5pt;
  color: var(--noce);
  line-height: 1.6;
  text-align: center;
  margin-bottom: 6mm;
}

/* ── Sezione firme ───────────────────────────────────────────── */
.firma-section {
  padding: 0 24mm;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 4mm;
}
.firma-block { text-align: center; }
.firma-line  { width: 55mm; border-bottom: 1px solid var(--noce); margin-bottom: 2mm; height: 12mm; }
.firma-label { font-size: 8pt; color: var(--noce); font-family: Arial, sans-serif; }

/* ── Footer ─────────────────────────────────────────────────── */
.footer {
  padding: 4mm 20mm 13mm 20mm;
  text-align: center;
  border-top: 1px solid var(--ocra);
}
.footer-text {
  font-size: 7.5pt;
  color: var(--rame);
  font-family: Arial, sans-serif;
  line-height: 1.6;
}
.footer-qr-area {
  display: flex;
  justify-content: flex-end;
  margin-top: 2mm;
}

/* ── Filigrana decorativa di sfondo ─────────────────────────── */
.watermark {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%) rotate(-20deg);
  font-size: 80pt;
  font-weight: bold;
  color: rgba(92, 122, 74, 0.04);
  pointer-events: none;
  z-index: 1;
  white-space: nowrap;
  letter-spacing: 8px;
  font-family: Georgia, serif;
}

/* ── Print ───────────────────────────────────────────────────── */
@page {
  size: A4 portrait;
  margin: 0;
}
@media print {
  html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .no-print  { display: none !important; }
}
</style>
</head>
<body>
<div class="attestato">

  <!-- Bordi decorativi -->
  <div class="outer-border"></div>
  <div class="inner-border"></div>
  <div class="corner corner-tl"></div>
  <div class="corner corner-tr"></div>
  <div class="corner corner-bl"></div>
  <div class="corner corner-br"></div>

  <!-- Filigrana -->
  <div class="watermark">La Botega</div>

  <!-- HEADER -->
  <div class="header">
    <div class="header-logo">
      <?php if ( $logo_b64 ) : ?>
      <img src="<?php echo $logo_b64; ?>" alt="La Botega da la Lavizzara">
      <?php else : ?>
      <div style="width:24mm;height:24mm;background:var(--verde);border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <span style="color:var(--giallo);font-size:8pt;font-weight:bold;text-align:center;padding:2px;">LA<br>BOTEGA</span>
      </div>
      <?php endif; ?>
    </div>
    <div class="header-logo-text">
      <strong>La Botega da la Lavizzara</strong>
      Società cooperativa<br>
      Casa Moretti 8A · 6694 Prato Sornico<br>
      labotegalavizzara@gmail.com
    </div>
  </div>

  <!-- TITOLO -->
  <div class="title-section">
    <div class="title-label"><?php esc_html_e( 'Cooperativa La Botega da la Lavizzara', 'botega-adesioni' ); ?></div>
    <div class="title-main"><?php esc_html_e( 'Attestato di Membro', 'botega-adesioni' ); ?></div>
    <div class="title-sub"><?php printf( esc_html__( 'Anno %s', 'botega-adesioni' ), esc_html( $anno ) ); ?></div>
    <div class="title-divider"></div>
  </div>

  <!-- CORPO -->
  <div class="body-section">
    <p class="body-intro">
      <?php esc_html_e( 'La Società Cooperativa La Botega da la Lavizzara certifica che', 'botega-adesioni' ); ?>
    </p>

    <div class="member-name"><?php echo esc_html( $record->cognome_nome ); ?></div>

    <div class="body-type">
      <?php echo esc_html( $tipo_label ); ?>
    </div>

    <!-- Tabella dati quota -->
    <table class="quota-table">
      <tr>
        <th><?php esc_html_e( 'Campo', 'botega-adesioni' ); ?></th>
        <th><?php esc_html_e( 'Valore', 'botega-adesioni' ); ?></th>
      </tr>
      <tr>
        <td><?php esc_html_e( 'ID Membro', 'botega-adesioni' ); ?></td>
        <td><span class="id-badge"><?php echo esc_html( $record->id_membro ); ?></span></td>
      </tr>
      <tr>
        <td><?php esc_html_e( 'ID Quota', 'botega-adesioni' ); ?></td>
        <td><span class="id-badge"><?php echo esc_html( $record->id_quota ); ?></span></td>
      </tr>
      <?php if ( $record->indirizzo ) : ?>
      <tr>
        <td><?php esc_html_e( 'Indirizzo', 'botega-adesioni' ); ?></td>
        <td><?php echo esc_html( $record->indirizzo ); ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td><?php esc_html_e( 'Data adesione', 'botega-adesioni' ); ?></td>
        <td><?php echo esc_html( $data_fmt ); ?></td>
      </tr>
      <?php if ( $record->quota_250 ) : ?>
      <tr>
        <td><?php esc_html_e( 'Quota associativa', 'botega-adesioni' ); ?></td>
        <td>CHF 250.00</td>
      </tr>
      <?php endif; ?>
      <?php if ( $record->donazione > 0 ) : ?>
      <tr>
        <td><?php esc_html_e( 'Donazione', 'botega-adesioni' ); ?></td>
        <td><?php echo esc_html( BA_Attestato::fmt_chf( (float) $record->donazione ) ); ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td><?php esc_html_e( 'Importo versato', 'botega-adesioni' ); ?></td>
        <td><strong><?php echo esc_html( $importo_fmt ); ?></strong></td>
      </tr>
    </table>
  </div>

  <!-- TESTO LEGALE -->
  <div class="legal-text">
    <?php esc_html_e(
        'è regolarmente iscritto/a alla Società Cooperativa "La Botega da la Lavizzara" e ha versato '
        . 'la quota sociale conformemente allo statuto vigente. '
        . 'Il presente attestato è valido per l\'anno di riferimento indicato.',
        'botega-adesioni'
    ); ?>
  </div>

  <!-- FIRME -->
  <div class="firma-section">
    <div class="firma-block">
      <div class="firma-line"></div>
      <div class="firma-label"><?php esc_html_e( 'Il Presidente', 'botega-adesioni' ); ?></div>
    </div>
    <div class="firma-block" style="text-align:center;">
      <div style="font-size:8pt;color:var(--rame);font-family:Arial,sans-serif;margin-bottom:2mm;">
        <?php printf(
            esc_html__( 'Prato Sornico, %s', 'botega-adesioni' ),
            esc_html( date_i18n( 'd.m.Y' ) )
        ); ?>
      </div>
    </div>
    <div class="firma-block">
      <div class="firma-line"></div>
      <div class="firma-label"><?php esc_html_e( 'La Segretaria', 'botega-adesioni' ); ?></div>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <div class="footer-text">
      Cooperativa "La Botega da la Lavizzara" · Casa Moretti 8A · 6694 Prato Sornico<br>
      IBAN: CH48 8080 8003 7010 4694 7 · Banca Raiffeisen Losone Pedemonte Vallemaggia<br>
      labotegalavizzara@gmail.com · labotegalavizzara.ch
    </div>
  </div>

</div><!-- .attestato -->
</body>
</html>
