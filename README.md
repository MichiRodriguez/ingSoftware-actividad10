# BovWeight CR — API Backend

API REST para la gestión de ganado bovino. Desarrollada para el curso IF7100 Ingeniería del Software, UCR Sede Guanacaste.

**Stack:** Laravel 13 · PHP 8.3 · MySQL · Laravel Sanctum

---

## Requisitos previos

- PHP 8.3+
- Composer
- MySQL 8.0+
- Node.js (solo para compilar assets con Vite, si aplica)
- Una cuenta de Gmail con [App Password](https://myaccount.google.com/apppasswords) habilitada (para correos transaccionales)

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd Bovweightcr-API
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar variables de entorno

Copia el archivo de ejemplo y edítalo:

```bash
cp .env.example .env
```

> Si no existe `.env.example`, crea un archivo `.env` con el contenido de la sección de abajo.

Edita `.env` con tus valores:

```env
APP_NAME="BovWeight CR"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bovweight
DB_USERNAME=root
DB_PASSWORD=tu_password_mysql

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD="xxxx xxxx xxxx xxxx"
MAIL_FROM_ADDRESS="tu_correo@gmail.com"
MAIL_FROM_NAME="BovWeight CR"

FRONTEND_URL=http://localhost:5173
```

> **MAIL_PASSWORD** debe ser un App Password de Google de 16 caracteres (con espacios), NO tu contraseña de Gmail.  
> **MAIL_SCHEME** debe ser `smtp` (para puerto 587 STARTTLS) o `smtps` (para puerto 465). El valor `tls` no es válido en Laravel 13.

### 4. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 5. Crear la base de datos

Crea la base de datos en MySQL:

```sql
CREATE DATABASE bovweight CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Esto ejecuta todas las migraciones y pobla la base de datos con datos de prueba.

**Credenciales de prueba creadas por el seeder:**

| Correo | Contraseña | Rol |
|---|---|---|
| `admin@bovweight.com` | `password123` | Administrador |
| `ganadero@bovweight.com` | `password123` | Ganadero |
| `veterinario@bovweight.com` | `password123` | Veterinario |

### 7. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

La API estará disponible en `http://localhost:8000/api`.

---

## Comandos útiles

```bash
# Reiniciar BD y re-sembrar datos de prueba
php artisan migrate:fresh --seed

# Ejecutar tests (usan SQLite in-memory, no afectan tu BD local)
php artisan test

# Ejecutar solo los tests del Módulo 1
php artisan test --compact tests/Feature/AuthTest.php tests/Feature/UsuarioTest.php tests/Feature/SolicitudRegistroTest.php

# Formatear código con Laravel Pint
./vendor/bin/pint

# Ver rutas registradas
php artisan route:list
```

---

## Estructura del proyecto

```
app/
├── Contracts/          # Interfaces (IUserRepository, ISolicitudRegistroRepository, IUserFactory)
├── Factories/          # Patrón Factory — creación de usuarios por tipo
├── Repositories/       # Patrón Repository — acceso a datos con Eloquent
├── Events/             # Patrón Observer — eventos del dominio
├── Listeners/          # Handlers de eventos (envío de correos)
├── Mail/               # Mailables (plantillas de correo)
├── Services/           # Lógica de negocio
├── Http/
│   ├── Controllers/Api/
│   ├── Middleware/     # EsAdministrador
│   ├── Requests/       # Validación de inputs
│   └── Resources/      # Formato de respuestas JSON
└── Models/
```

---

## Endpoints principales

### Públicos

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/auth/login` | Login, retorna Bearer token |
| POST | `/api/auth/forgot-password` | Envía correo de recuperación |
| POST | `/api/auth/reset-password` | Restablece contraseña |
| POST | `/api/solicitudes` | Enviar solicitud de registro |

### Autenticados (Bearer token)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/auth/me` | Datos del usuario actual |
| POST | `/api/auth/logout` | Cierra sesión |

### Solo administrador

| Método | Endpoint | Descripción |
|---|---|---|
| GET/POST | `/api/usuarios` | Listar / crear usuarios |
| GET/PUT/DELETE | `/api/usuarios/{id}` | Ver / editar / eliminar usuario |
| GET | `/api/solicitudes` | Listar todas las solicitudes |
| GET | `/api/solicitudes/pendientes` | Listar solicitudes pendientes |
| PUT | `/api/solicitudes/{id}/revisar` | Aprobar o rechazar solicitud |

---

## Notas de configuración de correo

El sistema envía correos automáticamente en tres situaciones:
- Al crear un usuario desde el panel admin → se envían sus credenciales
- Al aprobar una solicitud de registro → se envían las credenciales al solicitante
- Al rechazar una solicitud de registro → se notifica al solicitante con el motivo

Para que funcione en local, configura correctamente las variables `MAIL_*` en `.env` con una cuenta Gmail y su App Password. Si no necesitas correos durante el desarrollo, puedes usar el driver `log`:

```env
MAIL_MAILER=log
```

Esto escribe los correos en `storage/logs/laravel.log` en lugar de enviarlos.
