<?php
require_once 'lib/PDFProcessor.php';
$text = file_get_contents('assets/expedientes/debug_texto.txt');
$result = PDFProcessor::parseCertificateData($text);
var_dump($result['titulo']);
