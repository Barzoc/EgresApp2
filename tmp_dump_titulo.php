<?php
$text = file_get_contents('assets/expedientes/debug_texto.txt');
$replacements = [
    'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
    'Ã' => 'Á', 'Ã‰' => 'É', 'Ã' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú',
    'Ã±' => 'ñ', 'Ã‘' => 'Ñ', 'Â' => '',
];
$text = strtr($text, $replacements);
$text = str_replace(['T�cnico', 'Administraci�n'], ['Técnico', 'Administración'], $text);
$pos = mb_stripos($text, 'Técnico');
if ($pos !== false) {
    echo mb_substr($text, $pos, 200);
}
