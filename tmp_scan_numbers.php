<?php
$text = file_get_contents('assets/expedientes/debug_texto.txt');
preg_match_all('/\d{3,}/', $text, $matches);
var_export($matches[0]);
