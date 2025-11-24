<?php

class Utils {
    /**
     * Valida un RUT chileno usando el algoritmo Módulo 11.
     * Acepta formatos con o sin puntos y con o sin guión y acepta 'k' o 'K' como dígito verificador.
     *
     * @param string $rut RUT con o sin dígito verificador (ej: "12.345.678-5" o "123456785")
     * @return bool true si es válido, false en caso contrario
     */
    public static function validarRut(string $rut): bool {
        // Normalizar: quitar todo lo que no sea dígito o K/k
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        if ($rut === null) return false;
        if (strlen($rut) < 2) return false; // al menos un número + dígito verificador

        $dv = strtoupper(substr($rut, -1));
        $numero = substr($rut, 0, -1);
        if (!ctype_digit($numero)) return false;

        $reversed = strrev($numero);
        $factor = 2;
        $suma = 0;

        for ($i = 0, $len = strlen($reversed); $i < $len; $i++) {
            $suma += intval($reversed[$i]) * $factor;
            $factor++;
            if ($factor > 7) $factor = 2;
        }

        $resto = $suma % 11;
        $calculo = 11 - $resto;

        if ($calculo == 11) $dvEsperado = '0';
        elseif ($calculo == 10) $dvEsperado = 'K';
        else $dvEsperado = strval($calculo);

        return $dvEsperado === $dv;
    }
}
