Instrucciones para descargar las librerías localmente (Windows PowerShell)

Ejecuta estos comandos desde PowerShell en la raíz del proyecto (por ejemplo, C:\xampp\htdocs\Prototipo):

# 1) html5-qrcode
# URL de referencia usada en el proyecto: https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js" -OutFile ".\assets\plugins\html5-qrcode\html5-qrcode.min.js"

# 2) jsQR
# URL de referencia: https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js" -OutFile ".\assets\plugins\jsqr\jsQR.js"

# 3) jsPDF (UMD build)
# URL de referencia: https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js
Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" -OutFile ".\assets\plugins\jspdf\jspdf.umd.min.js"

# Nota: Si alguno de los enlaces cambia o devuelve error, puedes visitar las páginas oficiales para obtener la URL actualizada.

# Después de descargar, refresca la página del escáner en el navegador para que use las versiones locales.
