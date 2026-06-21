<?php
defined( 'ABSPATH' ) || exit;
/**
 * Template: Cedolino di pagamento – compatibile mPDF (NO flex/grid)
 * @var object $record
 */
$tipi      = BA_Database::get_tipi_socio();
$logo      = BA_PDF_Manager::logo_b64();
$qr_img    = BA_PDF_Manager::qr_code_b64( $record );
$_totale   = (float) $record->importo_totale;
if ( $_totale <= 0 ) {
    $_totale = ( ! empty( $record->quota_250 ) ? 250.00 : 0.0 ) + (float) ( $record->donazione ?? 0 );
}
$imp       = BA_PDF_Manager::fmt_chf( $_totale );
$imp_num   = number_format( $_totale, 2, '.', "'" );
$anno      = date( 'Y' );
$solo_don  = empty( $record->quota_250 ) && (float) ( $record->donazione ?? 0 ) > 0;
$data_fmt  = $record->data_adesione
    ? date_i18n( 'd.m.Y', strtotime( $record->data_adesione ) )
    : date_i18n( 'd.m.Y' );
$vers_nome = trim( $record->nome_versamento ?: $record->cognome_nome );
$vers_ind  = trim( $record->ind_versamento  ?: $record->indirizzo ?? '' );
if ( $record->cap_versamento ) {
    $vers_cap = trim( $record->cap_versamento . ' ' . ( $record->localita ?? '' ) );
} else {
    $vers_cap = trim( ( $record->cap ?? '' ) . ' ' . ( $record->localita ?? '' ) );
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cedolino</title>
<style>
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111; }
.hdr { width: 100%; border-collapse: collapse; border-bottom: 2.5px solid #2c3a00; margin-bottom: 7mm; }
.hdr td { padding: 0 0 3mm 0; vertical-align: top; }
.hdr-logo-cell { width: 42mm; }
.hdr-logo-cell img { height: 22mm; width: auto; }
.hdr-org-cell { padding-left: 8mm; }
.hdr-org-name { font-size: 11pt; font-weight: bold; color: #2c3a00; margin-bottom: 1mm; }
.hdr-org-addr { font-size: 8.5pt; color: #444; line-height: 1.7; }
.dest { width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
.dest td { vertical-align: top; font-size: 10pt; line-height: 1.6; }
.dest-data { text-align: right; font-size: 9pt; color: #555; width: 45%; }
.titolo { font-size: 12pt; font-weight: bold; color: #2c3a00; border-bottom: 2px solid #C8960A; padding-bottom: 2mm; margin-bottom: 4mm; }
.para { font-size: 9.5pt; line-height: 1.7; color: #333; margin-bottom: 3mm; }
.avviso { background: #fffbea; border-left: 3px solid #C8960A; padding: 2mm 4mm; margin: 3mm 0; font-size: 9pt; color: #6b4c00; }
.riepilogo { margin: 4mm 0; }
.riepilogo-head { background: #2c3a00; color: #d4e000; font-size: 9pt; font-weight: bold; padding: 2mm 4mm; }
.riepilogo-body { border: 1.5px solid #C8960A; border-top: none; background: #F8F0E0; }
.riep { width: 100%; border-collapse: collapse; }
.riep td { padding: 2mm 4mm; font-size: 9pt; border-bottom: 1px solid #e8dcc8; vertical-align: middle; }
.riep td:first-child { color: #555; width: 62%; }
.riep td:last-child  { font-weight: bold; color: #2c3a00; text-align: right; }
.riep .totale td { background: #fff8ee; font-size: 10.5pt; padding: 2.5mm 4mm; }
.riep .totale td:last-child { font-size: 12pt; color: #A06828; }
.riep tr:last-child td { border-bottom: none; }
.firma { font-size: 9pt; color: #444; line-height: 1.7; margin-top: 5mm; padding-top: 3mm; border-top: 1px solid #ddd; }
.sep { border: none; border-top: 2px dashed #888; margin: 6mm 0 2mm 0; }
.sep-label { text-align: center; font-size: 7pt; color: #888; margin-bottom: 2mm; letter-spacing: 1px; }
/* Bollettino QR */
.qrb-outer { width: 100%; border-collapse: collapse; border-top: 1.5px solid #333; }
.qrb-outer td { vertical-align: top; padding: 3mm 4mm 3mm 4mm; font-size: 8pt; }
.qrb-col-ricevuta  { width: 56mm; border-right: 1.5px solid #333; }
.qrb-col-pagamento { width: 68mm; border-right: 1.5px solid #333; text-align: left; }
.qrb-col-dati { }
.qrb-section-title { font-size: 9pt; font-weight: bold; margin-bottom: 3mm; }
/* Etichette: NON uppercase, solo grassetto piccolo */
.qrb-lbl { font-size: 6.5pt; font-weight: bold; color: #222; margin-top: 2.5mm; margin-bottom: 1mm; }
.qrb-val { font-size: 8pt; line-height: 1.5; }
/* Campi del debitore: senza bordi, interlinea compatta */
.qrb-pagabile-box {
  padding: 0;
  margin: 0;
  line-height: 1.3;
  font-size: 8pt;
  line-height: 1.5;
}
/* Riga valuta + importo con angoli */
.qrb-imp-row { margin-top: 2.5mm; }
.qrb-imp-row table { border-collapse: collapse; }
.qrb-imp-row td { vertical-align: bottom; padding: 0; }
.qrb-imp-chf { font-size: 8pt; padding-right: 2mm; white-space: nowrap; }
.qrb-imp-box {
  font-size: 11pt;
  font-weight: bold;
  min-width: 28mm;
  text-align: left;
  padding: 0;
  line-height: 1.2;
}
.qrb-accept { font-size: 6.5pt; font-weight: bold; text-align: center; margin-top: 4mm; color: #222; }
.qrb-col-dati .qrb-pagabile-box { font-size: 7pt; }
/* Colonna dati: riduce padding destro per evitare wrapping del numero civico */
.qrb-outer td.qrb-col-dati { padding-right: 1mm; }
.hdr-sub { font-size: 9pt; font-weight: normal; }
.riep-mono { font-family: monospace; }
.page-break { page-break-before: always; }
.sep-label-cedolino { margin-bottom: 8mm; }
.qrb-ref { font-family: monospace; font-size: 7pt; }
.qrb-imp-row-full { width: 100%; border-collapse: collapse; }
.qrb-imp-row-pag { width: 100%; border-collapse: collapse; margin-top: 4mm; }
.qrb-imp-valuta-col { width: 11mm; }
.qrb-img { width: 174px; height: 174px; display: block; }
.qrb-img-fallback { width: 174px; height: 174px; border: 1px solid #bbb; }
.qrb-img-fallback td { text-align: center; vertical-align: middle; font-size: 7pt; color: #888; }
@page { size: A4 portrait; margin: 14mm 16mm 12mm 16mm; }
</style>
</head>
<body>

<table class="hdr" cellpadding="0" cellspacing="0">
  <tr>
    <td class="hdr-logo-cell">
      <?php if ( $logo ) : ?>
        <img src="<?php echo $logo; ?>" alt="Logo">
      <?php else : ?>
        <div class="hdr-org-name">La Botega<br><span class="hdr-sub">da la Lavizzara</span></div>
      <?php endif; ?>
    </td>
    <td class="hdr-org-cell">
      <div class="hdr-org-name">Societa cooperativa La Botega da la Lavizzara</div>
      <div class="hdr-org-addr">Casa Moretti 8A &middot; 6694 Prato Sornico<br>labotegalavizzara@gmail.com &middot; IBAN: CH48 8080 8003 7010 4694 7</div>
    </td>
  </tr>
</table>

<table class="dest" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <strong><?php echo esc_html( $record->cognome_nome ); ?></strong><br>
      <?php if ( $record->indirizzo ?? '' ) : ?><?php echo esc_html( $record->indirizzo ); ?><br><?php endif; ?>
      <?php echo esc_html( trim( ( $record->cap ?? '' ) . ' ' . ( $record->localita ?? '' ) ) ); ?>
    </td>
    <td class="dest-data">Prato Sornico, <?php echo esc_html( $data_fmt ); ?></td>
  </tr>
</table>

<div class="titolo"><?php echo $solo_don
    ? esc_html( 'Donazione volontaria ' . $anno )
    : 'Conferma ricezione domanda di adesione'; ?></div>

<div class="para">Gentile <strong><?php echo esc_html( $record->cognome_nome ); ?></strong>,</div>
<?php if ( $solo_don ) : ?>
<div class="para">abbiamo ricevuto con piacere la sua donazione volontaria a favore di <strong>"La Botega da la Lavizzara"</strong> per l'anno <strong><?php echo $anno; ?></strong>.</div>
<?php else : ?>
<div class="para">abbiamo ricevuto con piacere la sua domanda di adesione alla <strong>Cooperativa "La Botega da la Lavizzara"</strong> come <strong><?php echo esc_html( $tipi[ $record->tipo_socio ] ?? $record->tipo_socio ); ?></strong>.</div>

<div class="avviso">Attenzione: la sua adesione sara attiva <strong>solo dopo il ricevimento del pagamento</strong> della quota sociale. Si prega di effettuare il versamento entro <strong>30 giorni</strong>.</div>
<?php endif; ?>

<div class="riepilogo">
  <div class="riepilogo-head"><?php echo $solo_don ? 'RIEPILOGO DONAZIONE' : 'RIEPILOGO ADESIONE'; ?></div>
  <div class="riepilogo-body">
    <table class="riep" cellpadding="0" cellspacing="0">
      <tr><td>Numero socio / ID Membro</td><td class="riep-mono"><?php echo esc_html( $record->id_membro ); ?></td></tr>
      <tr><td>Riferimento / ID Quota</td><td class="riep-mono"><?php echo esc_html( $record->id_quota ); ?></td></tr>
      <tr><td>Data domanda</td><td><?php echo esc_html( $data_fmt ); ?></td></tr>
      <?php if ( $record->quota_250 ) : ?>
      <tr><td>Quota associativa annua</td><td>CHF 250.00</td></tr>
      <?php endif; ?>
      <?php if ( !empty( $record->donazione ) && (float) $record->donazione > 0 ) : ?>
      <tr><td>Donazione volontaria</td><td><?php echo esc_html( BA_PDF_Manager::fmt_chf( (float) $record->donazione ) ); ?></td></tr>
      <?php endif; ?>
      <tr class="totale"><td><strong>IMPORTO TOTALE DA VERSARE</strong></td><td><?php echo esc_html( $imp ); ?></td></tr>
    </table>
  </div>
</div>

<div class="para">Il versamento puo essere effettuato tramite bonifico bancario, e-banking con QR code o versamento allo sportello postale/bancario.</div>

<div class="firma">
  <div class="para">Per informazioni: <strong>labotegalavizzara@gmail.com</strong></div>
  <br>
  <div class="para">Cordiali saluti,<br><strong>La segreteria de La Botega da la Lavizzara</strong></div>
</div>

<div class="page-break"></div>
<div class="sep-label sep-label-cedolino">- - - CEDOLINO DI PAGAMENTO - - -</div>

<table class="qrb-outer" cellpadding="0" cellspacing="0">
  <tr>

    <!-- COLONNA 1: RICEVUTA -->
    <td class="qrb-col-ricevuta">
      <div class="qrb-section-title">Ricevuta</div>
      <div class="qrb-lbl">Conto / Pagabile a</div>
      <div class="qrb-val">CH48 8080 8003 7010 4694 7<br>Societa cooperativa La Botega<br>da la Lavizzara<br>Via Cantonale 6<br>6694 Prato-Sornico</div>
      <div class="qrb-lbl">Riferimento</div>
      <div class="qrb-ref"><?php echo esc_html( $record->id_quota ); ?></div>
      <div class="qrb-lbl">Pagabile da (nome/indirizzo)</div>
      <div class="qrb-pagabile-box"><?php echo esc_html( $vers_nome ); ?></div>
      <?php if ( $vers_ind ) : ?>
      <div class="qrb-pagabile-box"><?php echo esc_html( $vers_ind ); ?></div>
      <?php endif; ?>
      <?php if ( $vers_cap ) : ?>
      <div class="qrb-pagabile-box"><?php echo esc_html( $vers_cap ); ?></div>
      <?php endif; ?>
      <table class="qrb-imp-row-full" cellpadding="0" cellspacing="0">
        <tr>
          <td class="qrb-imp-chf qrb-imp-valuta-col"><span class="qrb-lbl">Valuta</span></td>
          <td><span class="qrb-lbl">Importo</span></td>
        </tr>
        <tr>
          <td class="qrb-imp-chf qrb-imp-valuta-col">CHF</td>
          <td class="qrb-imp-box"><?php echo esc_html( $imp_num ); ?></td>
        </tr>
      </table>
      <div class="qrb-accept"><strong>Punto di accettazione</strong></div>
    </td>

    <!-- COLONNA 2: QR CODE -->
    <td class="qrb-col-pagamento">
      <div class="qrb-section-title">Sezione pagamento</div>
      <?php if ( $qr_img ) : ?>
        <img src="<?php echo $qr_img; ?>" class="qrb-img" alt="QR Swiss">
      <?php else : ?>
        <table class="qrb-img-fallback" cellpadding="3"><tr><td>QR-Code<br>e-banking<br>IBAN CH48 8080<br>8003 7010 4694 7</td></tr></table>
      <?php endif; ?>
      <table class="qrb-imp-row-pag" cellpadding="0" cellspacing="0">
        <tr>
          <td class="qrb-imp-chf qrb-imp-valuta-col"><span class="qrb-lbl">Valuta</span></td>
          <td><span class="qrb-lbl">Importo</span></td>
        </tr>
        <tr>
          <td class="qrb-imp-chf qrb-imp-valuta-col">CHF</td>
          <td class="qrb-imp-box"><?php echo esc_html( $imp_num ); ?></td>
        </tr>
      </table>
    </td>

    <!-- COLONNA 3: DATI PAGAMENTO -->
    <td class="qrb-col-dati">
      <div class="qrb-lbl">Conto / Pagabile a</div>
      <div class="qrb-val">CH48 8080 8003 7010 4694 7<br>Societa cooperativa La Botega da la Lavizzara<br>Via Cantonale 6 &middot; 6694 Prato-Sornico</div>
      <div class="qrb-lbl">Riferimento</div>
      <div class="qrb-ref"><?php echo esc_html( $record->id_quota ); ?></div>
      <div class="qrb-lbl">Informazioni aggiuntive</div>
      <div class="qrb-val">Adesione – <?php echo esc_html( $tipi[ $record->tipo_socio ] ?? '' ); ?> – <?php echo esc_html( $data_fmt ); ?></div>
      <div class="qrb-lbl">Pagabile da (nome/indirizzo)</div>
      <div class="qrb-pagabile-box"><?php echo esc_html( $vers_nome ); ?></div>
      <?php if ( $vers_ind ) : ?>
      <div class="qrb-pagabile-box"><?php echo esc_html( $vers_ind ); ?></div>
      <?php endif; ?>
      <?php if ( $vers_cap ) : ?>
      <div class="qrb-pagabile-box"><?php echo esc_html( $vers_cap ); ?></div>
      <?php endif; ?>
    </td>

  </tr>
</table>

</body>
</html>