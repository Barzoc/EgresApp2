// Validador de RUT (Módulo 11) - uso en cliente
// Ejemplo de uso: validarRut('12.345.678-5') -> true/false

function validarRut(rut) {
  if (!rut) return false;
  // Normalizar: quitar puntos, guión y pasar a mayúscula
  rut = rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();
  if (rut.length < 2) return false;

  const dv = rut.slice(-1);
  const num = rut.slice(0, -1);
  if (!/^[0-9]+$/.test(num)) return false;

  let sum = 0;
  let factor = 2;
  for (let i = num.length - 1; i >= 0; i--) {
    sum += parseInt(num.charAt(i), 10) * factor;
    factor++;
    if (factor > 7) factor = 2;
  }

  const rest = sum % 11;
  const calc = 11 - rest;
  let dvExpected;
  if (calc === 11) dvExpected = '0';
  else if (calc === 10) dvExpected = 'K';
  else dvExpected = String(calc);

  return dvExpected === dv;
}

// Hacer accesible desde ventanas/otros scripts
if (typeof window !== 'undefined') {
  window.validarRut = validarRut;
}

// Para Node.js / CommonJS
if (typeof module !== 'undefined' && module.exports) {
  module.exports = validarRut;
}
