<?php
defined( 'ABSPATH' ) || exit;
/**
 * Template: Conferma avvenuto pagamento
 * @var object $record
 */
$tipi     = BA_Database::get_tipi_socio();
$metodi   = BA_Database::get_metodi_pagamento();
$logo     = BA_PDF_Manager::logo_b64();
$data_fmt = $record->data_adesione ? date_i18n( 'd.m.Y', strtotime( $record->data_adesione ) ) : date_i18n( 'd.m.Y' );
$imp_tot  = BA_PDF_Manager::fmt_chf( (float) $record->importo_totale );
$imp_don  = BA_PDF_Manager::fmt_chf( (float) $record->donazione );
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title><?php esc_html_e( 'Conferma pagamento', 'botega-adesioni' ); ?> – <?php echo esc_html( $record->cognome_nome ); ?></title>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
:root { --verde:#2c3a00; --giallo:#d4e000; --ocra:#C8960A; --prato:#5C7A4A; --avorio:#F8F0E0; }
html, body { width:210mm; font-family:Arial,Helvetica,sans-serif; font-size:10pt; color:#111; background:#fff; }
.page { padding:16mm 18mm 14mm 18mm; }
.hdr { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8mm; padding-bottom:5mm; border-bottom:2px solid var(--ocra); }
.hdr img { height:20mm; width:auto; }
.hdr-addr { font-size:8.5pt; line-height:1.6; color:var(--prato); text-align:right; }
.hdr-addr strong { font-size:11pt; color:var(--verde); display:block; }
.doc-title { text-align:center; margin-bottom:8mm; }
.doc-title h1 { font-size:18pt; color:var(--verde); margin-bottom:2mm; }
.doc-title .sub { font-size:9pt; color:#666; }
.success-banner { background:#d4edda; border:1.5px solid #28a745; border-radius:4px; padding:5mm 8mm; text-align:center; margin-bottom:8mm; }
.success-banner .icon { font-size:22pt; display:block; margin-bottom:2mm; }
.success-banner p { font-size:11pt; font-weight:bold; color:#155724; }
.dati-box { background:var(--avorio); border:1.5px solid var(--ocra); border-radius:4px; padding:5mm 6mm; margin-bottom:6mm; }
.dati-row { display:flex; justify-content:space-between; font-size:9.5pt; padding:2mm 0; border-bottom:1px solid #e8dcc8; }
.dati-row:last-child { border-bottom:none; }
.dati-lbl { color:#555; }
.dati-val { font-weight:bold; color:var(--verde); }
.id-mono { font-family:'Courier New',monospace; font-size:9pt; background:var(--verde); color:var(--giallo); padding:1px 5px; border-radius:2px; }
.text-body { font-size:9.5pt; line-height:1.7; color:#333; margin-bottom:5mm; }
.saluti { margin-top:8mm; font-size:9.5pt; line-height:1.8; color:#333; }
.footer { margin-top:12mm; padding-top:5mm; border-top:1px solid var(--ocra); text-align:center; font-size:7.5pt; color:var(--ocra); line-height:1.6; }
@page { size:A4 portrait; margin:0; }
@media print { body { -webkit-print-color-adjust:exact; print-color-adjust:exact; } }
</style>
</head>
<body>
<div class="page">
  <div class="hdr">
    <div><?php if ( $logo ) : ?><img src="<?php echo $logo; ?>" alt="La Botega"><?php endif; ?></div>
    <div class="hdr-addr">
      <strong>La Botega da la Lavizzara</strong>
      Società cooperativa · Casa Moretti 8A<br>
      6694 Prato Sornico<br>
      labotegalavizzara@gmail.com
    </div>
  </div>

  <div class="doc-title">
    <h1><?php esc_html_e( 'Conferma di Pagamento', 'botega-adesioni' ); ?></h1>
    <div class="sub"><?php printf( esc_html__( 'Emessa il %s', 'botega-adesioni' ), date_i18n( 'd.m.Y' ) ); ?></div>
  </div>

  <div class="success-banner">
    <span class="icon">✅</span>
    <p><?php esc_html_e( 'Pagamento ricevuto e registrato con successo', 'botega-adesioni' ); ?></p>
  </div>

  <p class="text-body"><?php printf(
    esc_html__( 'Gentile %s,', 'botega-adesioni' ),
    '<strong>' . esc_html( $record->cognome_nome ) . '</strong>'
  ); ?></p>
  <p class="text-body"><?php esc_html_e(
    'confermiamo la ricezione del suo versamento. La sua adesione alla Cooperativa è ora attiva.',
    'botega-adesioni'
  ); ?></p>

  <div class="dati-box">
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'ID Membro', 'botega-adesioni' ); ?></span>
      <span class="dati-val"><span class="id-mono"><?php echo esc_html( $record->id_membro ); ?></span></span>
    </div>
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'ID Quota', 'botega-adesioni' ); ?></span>
      <span class="dati-val"><span class="id-mono"><?php echo esc_html( $record->id_quota ); ?></span></span>
    </div>
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'Tipo socio', 'botega-adesioni' ); ?></span>
      <span class="dati-val"><?php echo esc_html( $tipi[ $record->tipo_socio ] ?? $record->tipo_socio ); ?></span>
    </div>
    <?php if ( $record->quota_250 ) : ?>
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'Quota associativa', 'botega-adesioni' ); ?></span>
      <span class="dati-val">CHF 250.00</span>
    </div>
    <?php endif; ?>
    <?php if ( $record->donazione > 0 ) : ?>
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'Donazione', 'botega-adesioni' ); ?></span>
      <span class="dati-val"><?php echo esc_html( $imp_don ); ?></span>
    </div>
    <?php endif; ?>
    <div class="dati-row">
      <span class="dati-lbl"><strong><?php esc_html_e( 'IMPORTO TOTALE VERSATO', 'botega-adesioni' ); ?></strong></span>
      <span class="dati-val" style="font-size:13pt;"><?php echo esc_html( $imp_tot ); ?></span>
    </div>
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'Metodo di pagamento', 'botega-adesioni' ); ?></span>
      <span class="dati-val"><?php echo esc_html( $metodi[ $record->metodo_pagamento ] ?? $record->metodo_pagamento ); ?></span>
    </div>
    <div class="dati-row">
      <span class="dati-lbl"><?php esc_html_e( 'Data', 'botega-adesioni' ); ?></span>
      <span class="dati-val"><?php echo esc_html( $data_fmt ); ?></span>
    </div>
  </div>

  <div class="saluti">
    <p><?php esc_html_e( 'La ringraziamo per il suo contributo e la accogliamo con piacere nella nostra comunità cooperativa.', 'botega-adesioni' ); ?></p>
    <br>
    <p><?php esc_html_e( 'Con cordiali saluti,', 'botega-adesioni' ); ?></p>
    <p><strong><?php esc_html_e( 'La Botega da la Lavizzara', 'botega-adesioni' ); ?></strong></p>
  </div>

  <div class="footer">
    Cooperativa "La Botega da la Lavizzara" · Casa Moretti 8A · 6694 Prato Sornico<br>
    IBAN: CH48 8080 8003 7010 4694 7 · labotegalavizzara@gmail.com · labotega.ch
  </div>
</div>
</body>
</html>
