<?php
require __DIR__ . '/validar.php';

$tests = [
    '76251984-1',
    '76251984-5',
    '16.769.415-2',
    '12.345.678-5',
    '8.675.309-K',
    '8675309-k',
    '',
    '1-9'
];

foreach ($tests as $rut) {
    $result = validar_rut_endpoint($rut);
    echo sprintf("%s => %s (success=%s, valid=%s)\n", $rut === '' ? "(vacío)" : $rut, $result['message'], $result['success'] ? 'true' : 'false', $result['valid'] ? 'true' : 'false');
}
