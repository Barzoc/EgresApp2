<?php
require_once 'lib/PDFProcessor.php';
$text = PDFProcessor::sanitizeAccents(file_get_contents('assets/expedientes/debug_texto.txt'));
$position = mb_stripos($text, 'TÍTULO');
if ($position === false) {
    echo "No se encontró 'TÍTULO'";
    exit;
}
$snippet = mb_substr($text, max(0, $position - 50), 200);
echo $snippet, "\n";
