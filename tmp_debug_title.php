<?php
require_once 'lib/PDFProcessor.php';

$text = file_get_contents('assets/expedientes/debug_texto.txt');
$sanitized = PDFProcessor::sanitizeAccents($text);

$patterns = [
    'line_after_titulo' => '/T[ÍI]TULO\s+DE[:\s]*\R+([^\r\n]+)/u',
    'explicit_phrase' => '/(T[íi]cnico[\s\S]{0,120}?Administraci[óo]n)/iu',
];

foreach ($patterns as $label => $pattern) {
    $matched = [];
    if (preg_match($pattern, $sanitized, $matched)) {
        echo "Pattern {$label}:\n";
        var_dump($matched[1]);
    } else {
        echo "Pattern {$label}: no match\n";
    }
}
