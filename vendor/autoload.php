<?php
/**
 * Autoloader PSR-4 manuale per mPDF 8.x, dompdf e dipendenze.
 * Generato senza Composer per compatibilità con ambienti senza accesso Packagist.
 */

spl_autoload_register(function (string $class): void {
    $map = [
        // mPDF
        'Mpdf\\PsrLogAwareTrait\\'   => __DIR__ . '/mpdf/psr-log-aware-trait/src/',
        'Mpdf\\PsrHttpMessageShim\\' => __DIR__ . '/mpdf/psr-http-message-shim/src/',
        'Mpdf\\'                     => __DIR__ . '/mpdf/mpdf/src/',
        // PSR
        'Psr\\Log\\'                 => __DIR__ . '/psr/log/src/',
        'Psr\\Http\\Message\\'       => __DIR__ . '/psr/http-message/src/',
        // FPDI
        'Setasign\\Fpdi\\'           => __DIR__ . '/setasign/fpdi/src/',
        'setasign\\Fpdi\\'           => __DIR__ . '/setasign/fpdi/src/',
        // DeepCopy
        'DeepCopy\\'                 => __DIR__ . '/myclabs/deep-copy/src/DeepCopy/',
        // dompdf
        'Dompdf\\'                   => __DIR__ . '/dompdf/src/',
        // dompdf dipendenze
        'FontLib\\'                  => __DIR__ . '/dompdf/php-font-lib/src/FontLib/',
        'Svg\\'                      => __DIR__ . '/dompdf/php-svg-lib/src/Svg/',
        // Masterminds HTML5
        'Masterminds\\'              => __DIR__ . '/masterminds/html5/src/',
    ];

    foreach ($map as $prefix => $base) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $file = $base . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// dompdf richiede Cpdf dalla sua lib/
if (!class_exists('Cpdf', false) && file_exists(__DIR__ . '/dompdf/lib/Cpdf.php')) {
    require_once __DIR__ . '/dompdf/lib/Cpdf.php';
}
