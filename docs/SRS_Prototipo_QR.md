# SRS - Prototipo QR

Fecha: 5 de noviembre de 2025

Versión: Borrador 1.0

## 1. Introducción

### 1.1 Propósito
Este SRS define los requisitos funcionales y no funcionales para la plataforma de gestión de egresados (proyecto "Prototipo QR"). El objetivo es describir el comportamiento esperado, interfaces, restricciones y criterios de aceptación para guiar implementación y pruebas.

### 1.2 Alcance
- Aplicación web administrativa para gestionar egresados, títulos y expedientes.
- Subida y almacenamiento de expedientes en PDF, extracción automática de datos desde PDFs (texto y año de titulación), actualización de la base de datos.
- Generación de certificados y búsquedas por QR / carnet.
- Exportes y reportes (CSV/Excel/PDF).
- Roles: Administrador (completo), Operador/Secretaría (gestión de expedientes), Consulta (lectura limitada).

### 1.3 Definiciones, acrónimos
- Egresado: persona registrada con datos personales y académicos.
- Expediente: archivo PDF asociado a un egresado.
- TituloEgresado / tituloegresado: tabla que almacena títulos y fechaGrado.
- QR: mecanismo para consulta rápida con código.
- SRS: Software Requirements Specification.

## 2. Referencias
- Código fuente actual: PHP (controladores en /controlador), modelo en /modelo (Egresado.php, etc.), frontend: DataTables, Bootstrap, SweetAlert, html5-qrcode, jsQR.
- Config: config/google_vision.php (posible integración OCR).
- DB: esquema parcial en db/gestion_egresados.sql.

## 3. Visión general del sistema
- Usuario administra egresados (CRUD), sube expedientes PDF, el sistema intenta extraer rut/nombre/año de titulación/fecha nacimiento/número de certificado y guarda el archivo. El usuario valida/edita si la extracción falla.
- Funcionalidad de búsqueda y filtros (DataTables).
- Generación de certificados (export PDF con datos del egresado).
- Seguridad mínima: login/logout y control por roles.

## 4. Requisitos funcionales (FR)

FR-1 Gestión de Egresados (CRUD)
- FR-1.1 Crear egresado con campos: identificacion (PK), nombreCompleto, dirResidencia, telResidencia, telAlternativo, correoPrincipal, correoSecundario, carnet, sexo, fallecido, idGestion, avatar.
- FR-1.2 Editar egresado existente.
- FR-1.3 Eliminar egresado (si no está referenciado por otros registros).

FR-2 Listado y búsqueda
- FR-2.1 Mostrar lista paginada con filtros y ordenación (DataTables).
- FR-2.2 Exportar listados a CSV/Excel/PDF e impresión.

FR-3 Subida y procesamiento de Expediente (PDF)
- FR-3.1 Subir expediente PDF desde modal por egresado o desde botón global.
- FR-3.2 Almacenar archivo en servidor en /assets/expedientes/ con nombre único.
- FR-3.3 Ejecutar extracción automática de texto (OCR o extracción básica) y mapear: rut, nombre, año de titulación, número de certificado, fecha de nacimiento.
- FR-3.4 Guardar nombre de archivo en `egresado.expediente_pdf`.
- FR-3.5 Si se extrae año de titulación, actualizar `tituloegresado.fechaGrado` con formato yyyy-mm-dd (usar 01-01 cuando solo hay año).

FR-4 Validación y edición
- FR-4.1 Mostrar campos extraídos al usuario y permitir edición manual antes de guardar.
- FR-4.2 Botón “No reconocido”/manual para edición completa.

FR-5 Generación de certificados y autoconsulta
- FR-5.1 Generar certificado PDF usando datos del egresado y título.
- FR-5.2 Endpoint de autoconsulta para obtener datos por rut/carnet (usado por front).

FR-6 Escaneo QR
- FR-6.1 Leer QR con cámara desde navegador (html5-qrcode) y mostrar ficha resumida del egresado.
- FR-6.2 Permitir generación de QR para un egresado (contenido mínimo: URL con identificacion o token).

FR-7 Gestión de títulos
- FR-7.1 Asignar título a egresado (tabla `tituloegresado`).
- FR-7.2 Registrar fechaGrado.

FR-8 Auditoría y logs
- FR-8.1 Registrar acciones críticas (subida/edición/eliminación) con usuario y timestamp.
- FR-8.2 Guardar error logs en servidor (no mostrar en respuestas JSON).

FR-9 Autenticación y autorización
- FR-9.1 Login/logout.
- FR-9.2 Control de acceso por rol (Admin, Operador, Consulta).

FR-10 Integridad y respaldo
- FR-10.1 Copia de seguridad de la carpeta de expedientes y BD (procedimiento/documentado).

## 5. Requisitos no funcionales (NFR)

NFR-1 Seguridad
- NFR-1.1 HTTPS obligatorio en producción.
- NFR-1.2 Sanitizar entradas (SQL parametrizado, evitar XSS).
- NFR-1.3 Límite de tamaño para expedientes (p. ej. 10 MB) y verificación MIME.

NFR-2 Rendimiento
- NFR-2.1 Página de listado: primera carga < 2s con hasta 5000 registros paginados (server-side recommended).
- NFR-2.2 Procesamiento de PDF: tiempo máximo de 10s por documento (si excede, procesar en background y notificar).

NFR-3 Disponibilidad
- NFR-3.1 99% uptime (acuerdos operativos; backup diario).

NFR-4 Mantenibilidad
- NFR-4.1 Código modular y documentado; endpoints RESTful/consistentes.

NFR-5 Usabilidad
- NFR-5.1 Interfaz en español, accesible, modales claros y mensajes de error útiles.

NFR-6 Privacidad
- NFR-6.1 Acceso restringido a datos personales; cumplir normativa local de protección de datos.

## 6. Casos de uso (resumidos)

CU-1 Subir expediente y extraer datos
- Actores: Operador, Admin.
- Flujo principal: Seleccionar egresado → Abrir modal → Seleccionar PDF → Subir → Mostrar datos extraídos → Guardar.
- Excepciones: archivo no PDF, OCR falla → permitir carga manual.

CU-2 Crear egresado
- Actores: Admin.
- Flujo: Rellenar formulario → Guardar → Ver en listado.

CU-3 Generar certificado
- Actores: Operador, Admin.
- Flujo: Seleccionar egresado → Generar certificado → Descargar/Enviar.

CU-4 Escanear QR
- Actores: Consulta, Operador.
- Flujo: Abrir escáner en móvil/PC → Leer QR → Mostrar resumen → Opcional: ver ficha completa.

## 7. Modelo de datos (alto nivel)

- egresado
  - identificacion (PK)
  - nombreCompleto
  - dirResidencia
  - telResidencia
  - telAlternativo
  - correoPrincipal
  - correoSecundario
  - carnet
  - sexo
  - fallecido (boolean)
  - idGestion (FK)
  - avatar (filename)
  - expediente_pdf (filename)
  - created_at, updated_at

- tituloegresado
  - id (PK)
  - identificacion (FK -> egresado)
  - idTitulo (FK -> titulos)
  - fechaGrado (date)

- gestion
  - id, nombre, año, etc.

- usuarios
  - id, username, password_hash, rol, nombre, email, created_at

- auditoria
  - id, usuario_id, accion, detalle, ts

## 8. Requisitos de interfaz y UI

- Modales para subir expediente y editar datos extraídos.
- Botón global “Subir Expediente” junto a “Crear Egresado”.
- Tabla con botones por fila (subir expediente, editar, asignar título, eliminar).
- Páginas: listado, crear/editar, gestión títulos, escáner QR, reportes.
- Mensajes usando SweetAlert (ya integrado en el proyecto).

## 9. Criterios de aceptación y pruebas (por requisito clave)

- AC-FR-3.1: Al subir un PDF correcto, el archivo queda en /assets/expedientes/ y la respuesta JSON incluye success:true, archivo:<nombre>, datos:{...}.
- AC-FR-3.3: Si el OCR extrae año de titulación, el registro `tituloegresado.fechaGrado` se actualiza con yyyy-01-01.
- AC-FR-2.1: La DataTable muestra los registros devueltos por `EgresadoController.php` y paginación funciona.
- AC-FR-6.1: El escáner QR, al leer QR con identificacion válida, muestra la ficha del egresado.

Pruebas sugeridas:
- Endpoint `ProcesarExpedienteController.php` con PDF de prueba → validar JSON y archivo en disco.
- Test de búsqueda (EgresadoController listar) devuelve JSON válido.
- Test de roles: un usuario con rol Consulta no puede eliminar egresados.

## 10. Matriz de trazabilidad (ejemplo)

- FR-3.1 → AC-FR-3.1 → Caso de prueba PT-01
- FR-2.1 → AC-FR-2.1 → Caso de prueba PT-02

## 11. Riesgos y mitigaciones

- R1 OCR poco fiable con PDFs escaneados: Mitigar con opción manual y/o usar Google Vision (config disponible).
- R2 Archivos grandes/virus: Limitar tamaño y escanear (antivirus) en producción.
- R3 Respuesta JSON “contaminada” por warnings PHP: configurar display_errors=0, usar buffering (ya implementado).
- R4 Acceso no autorizado: aplicar políticas de roles y HTTPS.

## 12. Priorización (MoSCoW)

Must (M)
- M1 FR-1 Gestión de egresados (CRUD).
- M2 FR-2 Listado y búsqueda.
- M3 FR-9 Autenticación y autorización básica.
- M4 FR-3 Subida y almacenamiento de expedientes.
- M5 FR-8 Auditoría básica y logs.

Should (S)
- S1 FR-3.3 Extracción básica de datos desde PDF y guardado.
- S2 FR-4 UI para revisar/editar datos extraídos.
- S3 FR-6 Escaneo QR (básico).

Could (C)
- C1 Integración con Google Vision (mejora OCR).
- C2 Procesamiento en background y notificaciones.

Won't (W)
- W1 Integración con sistemas externos de validación de títulos.
- W2 Firma digital avanzada de certificados.

## 13. Simulación de toma de requerimientos (29 Sep — 3 Oct)

### Día 1 — Lunes 29 Septiembre — Kickoff y alcance
- Hora: 09:30 — 10:30
- Asistentes: Jefe de Proyecto (JP), Administrador del Área (Admin), Responsable TI (RTI), Analista (Ana).
- Objetivos: Presentación del prototipo, revisar alcance, confirmar stakeholders, listar procesos críticos.
- Decisiones:
  - Alcance inicial centrado en gestión de egresados + expedientes + generación de certificados.
  - Roles: Admin, Operador, Consulta.
- Entregables: Acta de kickoff, lista inicial de procesos críticos.
- Acciones:
  - Ana prepara mapa de procesos y envía antes del día 2.

### Día 2 — Martes 30 Septiembre — Entrevistas a usuarios y flujos
- Hora: 10:00 — 12:00
- Asistentes: Operador/Secretaría, Admin, Desarrollador principal (Dev).
- Objetivos: Recolectar flujos de trabajo para subir y procesar expedientes, generación de certificados.
- Puntos claves discutidos:
  - Flujo actual: secretaría recibe PDF → debe poder subir y verificar datos → emitir certificado.
  - Necesidad de botón global “Subir Expediente”.
  - Edición manual si OCR falla.
- Decisiones:
  - Mantener modal con vista previa y campos editables.
  - Guardar siempre el PDF al subir, independientemente de si extrae datos.
- Acciones:
  - Dev implementa modal y endpoint; Ana prepara especificación de campos.

### Día 3 — Miércoles 1 Octubre — Requisitos de datos y validaciones
- Hora: 09:30 — 11:00
- Asistentes: Admin, DB Admin, Dev, Ana.
- Objetivos: Definir campos obligatorios, formatos de fecha, reglas de negocio (ej. actualización fechaGrado).
- Decisiones:
  - Formato para fechaGrado: yyyy-mm-dd; si solo existe año → yyyy-01-01.
  - RUT debe normalizarse (quitar puntos/guion) al buscar.
- Entregables: Lista de validaciones y cambios en esquema (nota para DB).
- Acciones:
  - DB Admin revisa índice sobre carnet/identificacion para búsquedas rápidas.

### Día 4 — Jueves 2 Octubre — No funcionales, seguridad y rendimiento
- Hora: 11:00 — 12:00
- Asistentes: RTI, Dev, Admin.
- Objetivos: Revisar NFRs: HTTPS, límites de archivo, tiempos, backups.
- Decisiones:
  - Límite máximo por expediente 10 MB; procesar en background si > 5 MB.
  - Backup diario y copias de la carpeta /assets/expedientes/.
- Entregables: Plan de NFRs y checklist de producción.
- Acciones:
  - RTI configura pruebas de rendimiento básicas.

### Día 5 — Viernes 3 Octubre — Revisión final y firma de requerimientos
- Hora: 10:00 — 11:00
- Asistentes: JP, Admin, RTI, Dev, Ana.
- Objetivos: Revisión del SRS preliminar y aprobación para la fase de implementación.
- Decisiones:
  - Aprobar SRS preliminar con las prioridades indicadas.
  - Implementación fase 1: M1..M5 (4 semanas estimadas, ver notas).
- Entregables: SRS preliminar firmado (acta) y backlog priorizado.
- Acciones:
  - Dev comienza sprint 0 (configuración y correcciones detectadas: logging, buffering JSON).

## 14. Estimación rápida
- Sprint 0 (setup, correcciones críticas, tests): 1 semana
- Fase 1 (M1..M5): 3-4 sprints (cada sprint 2 semanas) — estimación ~6-8 semanas de desarrollo según equipo y disponibilidad.
- FR-3 (OCR y mejoras) y tareas C1/C2: +2-4 semanas adicionales para integración y robustez.

## 15. Entregables disponibles
- Documento SRS (Markdown): `docs/SRS_Prototipo_QR.md`.
- Actas diarias y backlog priorizado (pueden exportarse a archivos individuales bajo demanda).

---

Para convertir este Markdown a PDF en tu máquina local puedes usar `pandoc` o un conversor online. Si prefieres, intento convertirlo aquí a PDF (necesito que me permitas ejecutar la conversión; si `pandoc` no está instalado te daré el Markdown y las instrucciones).