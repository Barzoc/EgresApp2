<?php
require __DIR__ . '/modelo/Utils.php';

$tests = [
    '76251984-1', // ejemplo (de tu enunciado el DV: 1)
    '76251984-5', // ejemplo erróneo
    '12345678-5',
    '11111111-1',
    '12.345.678-5',
    '8.675.309-K',
    '8675309-k',
    '1-9'
];

foreach ($tests as $rut) {
    $valid = Utils::validarRut($rut) ? 'válido' : 'inválido';
    echo "$rut => $valid\n";
}
