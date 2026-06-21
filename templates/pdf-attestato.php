<?php
defined( 'ABSPATH' ) || exit;
/**
 * Template attestato – usato per attestato_quota e attestato_don
 * @var object $record
 * @var string $tipo   'attestato_quota' | 'attestato_don'
 */
$tipi      = BA_Database::get_tipi_socio();
$logo      = BA_PDF_Manager::logo_b64();
$anno      = $record->data_adesione ? date( 'Y', strtotime( $record->data_adesione ) ) : date( 'Y' );
$data_fmt  = $record->data_adesione ? date_i18n( 'd.m.Y', strtotime( $record->data_adesione ) ) : date_i18n( 'd.m.Y' );
$tipo_label = $tipi[ $record->tipo_socio ] ?? $record->tipo_socio;
$e_donazione = ( $tipo === 'attestato_don' );
$imp_quota = BA_PDF_Manager::fmt_chf( $record->quota_250 ? 250.00 : 0 );
$imp_don   = BA_PDF_Manager::fmt_chf( (float) $record->donazione );
$imp_tot   = BA_PDF_Manager::fmt_chf( (float) $record->importo_totale );

$titolo_doc = $e_donazione
    ? __( 'Attestato di Donazione', 'botega-adesioni' )
    : __( 'Attestato di Membro', 'botega-adesioni' );
$sottotitolo = $e_donazione
    ? __( 'Ricevuta e ringraziamento per la donazione', 'botega-adesioni' )
    : sprintf( __( 'Anno %s', 'botega-adesioni' ), $anno );
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title><?php echo esc_html( $titolo_doc ); ?> – <?php echo esc_html( $record->cognome_nome ); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --verde:#2c3a00; --giallo:#d4e000; --ocra:#C8960A; --rame:#A06828; --noce:#6B4A1E; --prato:#5C7A4A; --avorio:#F8F0E0; }
html, body { width:210mm; height:297mm; margin:0 auto; background:var(--avorio); font-family:'Georgia','Times New Roman',serif; color:var(--verde); }
.attestato { width:210mm; height:297mm; padding:0; position:relative; overflow:hidden; display:flex; flex-direction:column; background:var(--avorio); }
.outer-border { position:absolute; inset:8mm; border:3px solid var(--ocra); pointer-events:none; z-index:10; }
.inner-border { position:absolute; inset:10mm; border:1px solid var(--rame); pointer-events:none; z-index:10; }
.corner { position:absolute; width:12mm; height:12mm; z-index:11; }
.corner-tl { top:6mm; left:6mm; border-top:4px solid var(--ocra); border-left:4px solid var(--ocra); }
.corner-tr { top:6mm; right:6mm; border-top:4px solid var(--ocra); border-right:4px solid var(--ocra); }
.corner-bl { bottom:6mm; left:6mm; border-bottom:4px solid var(--ocra); border-left:4px solid var(--ocra); }
.corner-br { bottom:6mm; right:6mm; border-bottom:4px solid var(--ocra); border-right:4px solid var(--ocra); }
.watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-20deg); font-size:72pt; font-weight:bold; color:rgba(92,122,74,0.04); pointer-events:none; z-index:1; white-space:nowrap; letter-spacing:6px; font-family:Georgia,serif; }
.header { padding:14mm 20mm 7mm 20mm; display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid var(--ocra); }
.header img { height:22mm; width:auto; }
.header-txt { font-family:Arial,sans-serif; font-size:8.5pt; color:var(--prato); text-align:right; line-height:1.6; }
.header-txt strong { font-size:11pt; color:var(--verde); display:block; margin-bottom:2px; }
.title-section { padding:7mm 20mm 4mm 20mm; text-align:center; }
.title-label { font-size:8pt; letter-spacing:3px; text-transform:uppercase; color:var(--rame); margin-bottom:3mm; font-family:Arial,sans-serif; }
.title-main { font-size:<?php echo $e_donazione ? '20pt' : '22pt'; ?>; font-weight:bold; color:var(--verde); letter-spacing:1px; }
.title-sub { font-size:10pt; color:var(--noce); margin-top:2mm; font-style:italic; }
.title-divider { margin:4mm auto; width:55mm; height:2px; background:linear-gradient(to right,transparent,var(--ocra),transparent); }
.body-section { padding:4mm 22mm; text-align:center; flex:1; }
.body-intro { font-size:9.5pt; color:var(--noce); line-height:1.7; margin-bottom:4mm; }
.member-name { font-size:<?php echo $e_donazione ? '20pt' : '22pt'; ?>; font-weight:bold; color:var(--verde); letter-spacing:1px; padding:3mm 8mm; border-top:1.5px solid var(--ocra); border-bottom:1.5px solid var(--ocra); margin:3mm 0 5mm 0; display:inline-block; min-width:100mm; }
.body-type { font-size:11pt; color:var(--prato); font-style:italic; margin-bottom:5mm; }
.quota-table { margin:0 auto 5mm auto; border-collapse:separate; border-spacing:0; border:1.5px solid var(--ocra); border-radius:4px; overflow:hidden; min-width:120mm; }
.quota-table th { background:var(--verde); color:var(--giallo); font-family:Arial,sans-serif; font-size:7.5pt; padding:2.5mm 6mm; text-align:left; letter-spacing:.5px; }
.quota-table td { background:#fff; padding:2.5mm 6mm; font-size:9pt; color:var(--verde); border-top:1px solid #e8e0d0; }
.quota-table tr:first-child td { border-top:none; }
.id-badge { display:inline-block; background:var(--verde); color:var(--giallo); font-family:'Courier New',monospace; font-size:10pt; font-weight:bold; letter-spacing:2px; padding:1.5mm 4mm; border-radius:3px; }
.id-badge-q { background:var(--ocra); color:#fff; }
<?php if ( $e_donazione ) : ?>
.ringraziamento { background:rgba(92,122,74,0.08); border:1px solid var(--prato); border-radius:4px; padding:4mm 6mm; margin:4mm 22mm; font-size:9pt; line-height:1.7; color:var(--noce); font-style:italic; text-align:center; }
<?php endif; ?>
.legal-text { padding:0 22mm; font-size:8pt; color:var(--noce); line-height:1.6; text-align:center; margin-bottom:5mm; }
.firma-section { padding:0 22mm; display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:4mm; }
.firma-block { text-align:center; }
.firma-line { width:50mm; border-bottom:1px solid var(--noce); margin-bottom:2mm; height:11mm; }
.firma-label { font-size:7.5pt; color:var(--noce); font-family:Arial,sans-serif; }
.footer { padding:3mm 20mm 12mm 20mm; text-align:center; border-top:1px solid var(--ocra); }
.footer-text { font-size:7pt; color:var(--rame); font-family:Arial,sans-serif; line-height:1.6; }
@page { size:A4 portrait; margin:0; }
@media print { html,body { -webkit-print-color-adjust:exact; print-color-adjust:exact; } }
</style>
</head>
<body>
<div class="attestato">
  <div class="outer-border"></div>
  <div class="inner-border"></div>
  <div class="corner corner-tl"></div>
  <div class="corner corner-tr"></div>
  <div class="corner corner-bl"></div>
  <div class="corner corner-br"></div>
  <div class="watermark">La Botega</div>

  <div class="header">
    <div><?php if ( $logo ) : ?><img src="<?php echo $logo; ?>" alt="La Botega"><?php else : ?><div style="font-size:13pt;font-weight:bold;color:var(--verde);">La Botega</div><?php endif; ?></div>
    <div class="header-txt">
      <strong>La Botega da la Lavizzara</strong>
      Società cooperativa<br>Casa Moretti 8A · 6694 Prato Sornico<br>labotegalavizzara@gmail.com
    </div>
  </div>

  <div class="title-section">
    <div class="title-label"><?php esc_html_e( 'Cooperativa La Botega da la Lavizzara', 'botega-adesioni' ); ?></div>
    <div class="title-main"><?php echo esc_html( $titolo_doc ); ?></div>
    <div class="title-sub"><?php echo esc_html( $sottotitolo ); ?></div>
    <div class="title-divider"></div>
  </div>

  <div class="body-section">
    <p class="body-intro">
      <?php echo $e_donazione
        ? esc_html__( 'La Società Cooperativa La Botega da la Lavizzara ringrazia con gratitudine', 'botega-adesioni' )
        : esc_html__( 'La Società Cooperativa La Botega da la Lavizzara certifica che', 'botega-adesioni' ); ?>
    </p>
    <div class="member-name"><?php echo esc_html( $record->cognome_nome ); ?></div>
    <div class="body-type"><?php echo esc_html( $tipo_label ); ?></div>

    <table class="quota-table">
      <tr><th><?php esc_html_e( 'Campo', 'botega-adesioni' ); ?></th><th><?php esc_html_e( 'Valore', 'botega-adesioni' ); ?></th></tr>
      <tr><td><?php esc_html_e( 'ID Membro', 'botega-adesioni' ); ?></td><td><span class="id-badge"><?php echo esc_html( $record->id_membro ); ?></span></td></tr>
      <tr><td><?php esc_html_e( 'ID Quota', 'botega-adesioni' ); ?></td><td><span class="id-badge id-badge-q"><?php echo esc_html( $record->id_quota ); ?></span></td></tr>
      <tr><td><?php esc_html_e( 'Data', 'botega-adesioni' ); ?></td><td><?php echo esc_html( $data_fmt ); ?></td></tr>
      <?php if ( ! $e_donazione && $record->quota_250 ) : ?>
      <tr><td><?php esc_html_e( 'Quota associativa', 'botega-adesioni' ); ?></td><td><strong><?php echo esc_html( $imp_quota ); ?></strong></td></tr>
      <?php endif; ?>
      <?php if ( $e_donazione && $record->donazione > 0 ) : ?>
      <tr><td><?php esc_html_e( 'Donazione', 'botega-adesioni' ); ?></td><td><strong><?php echo esc_html( $imp_don ); ?></strong></td></tr>
      <?php endif; ?>
      <?php if ( $record->quota_250 && $record->donazione > 0 ) : ?>
      <tr><td><?php esc_html_e( 'Importo totale', 'botega-adesioni' ); ?></td><td><strong><?php echo esc_html( $imp_tot ); ?></strong></td></tr>
      <?php endif; ?>
    </table>
  </div>

  <?php if ( $e_donazione ) : ?>
  <div class="ringraziamento">
    <?php esc_html_e(
      '"Il suo generoso contributo sostiene la nostra comunità e ci permette di continuare a valorizzare i prodotti e le tradizioni della Valle Lavizzara. La ringraziamo di cuore."',
      'botega-adesioni'
    ); ?><br><br>
    <em>— <?php esc_html_e( 'Il Comitato de La Botega da la Lavizzara', 'botega-adesioni' ); ?></em>
  </div>
  <?php endif; ?>

  <div class="legal-text">
    <?php if ( $e_donazione ) :
      esc_html_e( 'Il presente documento certifica la ricezione della donazione indicata. La donazione è deducibile fiscalmente secondo la legislazione vigente (art. 33a LF/LIFD). Si consiglia di conservare questo documento ai fini fiscali.', 'botega-adesioni' );
    else :
      esc_html_e( 'è regolarmente iscritto/a alla Società Cooperativa "La Botega da la Lavizzara" e ha versato la quota sociale conformemente allo statuto vigente. Il presente attestato è valido per l\'anno di riferimento indicato.', 'botega-adesioni' );
    endif; ?>
  </div>

  <div class="firma-section">
    <div class="firma-block"><div class="firma-line"></div><div class="firma-label"><?php esc_html_e( 'Il Presidente', 'botega-adesioni' ); ?></div></div>
    <div class="firma-block" style="text-align:center;font-size:8pt;color:var(--rame);font-family:Arial,sans-serif;">
      <?php printf( esc_html__( 'Prato Sornico, %s', 'botega-adesioni' ), esc_html( date_i18n( 'd.m.Y' ) ) ); ?>
    </div>
    <div class="firma-block"><div class="firma-line"></div><div class="firma-label"><?php esc_html_e( 'La Segretaria', 'botega-adesioni' ); ?></div></div>
  </div>

  <div class="footer">
    <div class="footer-text">
      Cooperativa "La Botega da la Lavizzara" · Casa Moretti 8A · 6694 Prato Sornico<br>
      IBAN: CH48 8080 8003 7010 4694 7 · Banca Raiffeisen Losone Pedemonte Vallemaggia<br>
      labotegalavizzara@gmail.com · labotega.ch
    </div>
  </div>
</div>
</body>
</html>
