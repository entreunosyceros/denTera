# DenTera — Gestión de Citas Dentales

<img width="768" height="419" alt="logo" src="https://github.com/user-attachments/assets/ad1e0c7d-398b-4efd-86ae-086e6e28cc48" />

Aplicación web para la gestión de citas de una clínica dental, con base de datos MySQL para no olvidarme del PHP.

## Requisitos

- PHP 8.0+
- MySQL 8.0+ (o MariaDB 10.3+)
- Apache (u otro servidor web con soporte PHP)

## Instalación (nueva clínica)

1. Copia los archivos al directorio web, por ejemplo:
   ```
   /var/www/html/dentista/
   ```

2. Configura las credenciales de MySQL en `config.php` (host, nombre de base, usuario y contraseña).

3. Crea la base de datos y los datos iniciales:
   ```bash
   mysql -u tu_usuario -p < schema.sql
   ```
   El archivo `schema.sql` crea la base `dentista`, las tablas, usuarios de acceso, doctores, tratamientos, datos de clínica, **pacientes y citas de ejemplo**, y al final incluye **actualizaciones idempotentes** por si alguna columna ya existiera (no fallan al repetirse).

4. Accede a la aplicación:
   ```
   http://localhost/dentista/
   ```

## Actualización desde una versión anterior

Si ya tenías DenTera instalado y solo quieres alinear el esquema (nuevas columnas como notas clínicas u orden en agenda):

1. Haz copia de seguridad de la base.
2. Ejecuta de nuevo el archivo completo **o** copia y pega en tu cliente MySQL **solo el bloque «Actualización idempotente»** al final de `schema.sql`. Ese bloque consulta `INFORMATION_SCHEMA` y solo añade columnas o índices que falten.
3. Si el login dejó de funcionar tras un `schema` antiguo, ejecuta además:
   ```bash
   mysql -u tu_usuario -p dentista < schema_fix_passwords_demo.sql
   ```

## Credenciales por defecto

<img width="532" height="604" alt="login" src="https://github.com/user-attachments/assets/ee6840c3-6926-4c01-a40a-aad7e85b498f" />

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `admin` | `admin123` | Administrador |
| `auxiliar` | `auxiliar123` | Auxiliar de clínica |

Si importaste un `schema.sql` muy antiguo y no puedes entrar, ejecuta `mysql ... < schema_fix_passwords_demo.sql` para alinear los hashes con estas contraseñas.

## Estructura de archivos (principal)

<img width="1428" height="931" alt="dentera-panel-inicio" src="https://github.com/user-attachments/assets/bb68a929-43f8-4383-b7dc-2059b83db6e6" />

| Archivo / carpeta | Descripción |
|-------------------|-------------|
| `config.php`, `sesion.php` | Base de datos y sesión |
| `login.php`, `logout.php` | Acceso al sistema |
| `index.php` | Agenda: lista, calendario, filtros, búsqueda en vivo, próximas citas, export CSV |
| `crear.php`, `editar.php`, `eliminar.php` | Alta, edición y borrado de citas |
| `duplicar_cita.php` | Abre nueva cita copiando datos de una existente |
| `export_citas_csv.php` | Descarga CSV según filtros actuales de la agenda |
| `pacientes.php` | Directorio de pacientes con búsqueda |
| `paciente.php` | Historial del paciente, notas clínicas, nueva cita / duplicar |
| `presupuesto.php`, `factura.php` | Presupuestos y facturas (citas completadas) |
| `doctores.php`, `tratamientos.php` | Equipo y catálogo de tratamientos |
| `configuracion.php` | Datos de la clínica y auditoría. Configurar aquí los datos de la conexión a la base de datos |
| `api_tabla_citas.php`, `api_reordenar_citas.php` | API interna para agenda (tabla en vivo y orden por arrastre) |
| `js/agenda-live.js`, `js/citas-sortable.js`, `js/Sortable.min.js` | Scripts de la agenda |
| `inc/` | Fragmentos PHP reutilizados (filtros de citas, tabla, cabecera, pie) |
| `schema.sql` | Esquema completo + datos demo |
| `estilos.css`, `img/logo.png` | Interfaz y logo |

## Funcionalidades

### Agenda y citas

- Crear, editar, eliminar y **duplicar** citas (duplicado sugiere fecha +7 días y estado pendiente).
- Estados: pendiente, confirmada, cancelada, completada; forma de pago y notas por cita.
- **Búsqueda en vivo** por nombre, DNI, teléfono, email o fecha escrita en el cuadro de búsqueda; filtros por estado y rango de fechas.
- Acceso rápido **«Citas de hoy»** y calendario mensual con citas por día.
- **Próximas citas (14 días)**: listado de pendientes/confirmadas con enlace a editar.
- **Exportar CSV** (UTF-8, separador `;`, hasta 10.000 filas) según los filtros actuales.
- Ordenación por columnas; **orden manual** arrastrando filas el mismo día (requiere columna `orden_agenda`; se añade al final de `schema.sql` si faltaba).
- Doble reserva: un mismo doctor no puede tener dos citas pendientes/confirmadas a la misma hora.

### Pacientes

- **Directorio** (`pacientes.php`): búsqueda, teléfono y email clicables, historial y **nueva cita** con datos cargados.
- **Ficha** (`paciente.php`): historial de citas, estadísticas, **notas clínicas** persistentes (alergias, antecedentes), botón nueva cita y duplicar cita desde el listado.

### Doctores, tratamientos, presupuestos y facturación

- CRUD de profesionales (doctor / higienista) y de tratamientos; desactivación sin borrar si hay dependencias.
- Presupuesto agrupado por paciente; factura para citas **completadas** con IVA 21 % e impresión/PDF desde el navegador.

### Configuración y seguridad

- Datos fiscales y de contacto de la clínica.
- **Auditoría** de acciones (incluye exportaciones CSV y reordenación de citas).
- Login por sesión; roles admin y auxiliar.

## Base de datos (tablas principales)

- **usuarios** — Acceso al sistema (admin / auxiliar).
- **auditoria** — Registro de acciones.
- **doctores** — Profesionales.
- **tratamientos** — Catálogo y precios.
- **pacientes** — Datos de contacto y **notas_clinicas** (observaciones clínicas permanentes).
- **citas** — Citas con paciente, fecha/hora, **orden_agenda** (orden manual en listado por día), doctor, tratamiento, estado, pago, notas.
- **config** — Datos de la clínica para presupuestos y facturas.
