# UniMarket

UniMarket es un marketplace universitario donde estudiantes pueden publicar y descubrir productos, gestionar sus emprendimientos y comunicarse de forma privada dentro de la plataforma.

## Tecnologías

- Laravel 13
- PHP 8.3 o superior (desarrollo validado con PHP 8.5)
- Eloquent ORM y Blade
- Tailwind CSS 4 y Vite
- SQLite o el motor de base de datos configurado en Laravel

## Funcionalidades

- Registro, inicio de sesión, verificación de correo y roles de estudiante y administrador.
- Catálogo de publicaciones con categorías, gestión de disponibilidad y publicaciones propias.
- Favoritos de publicaciones y listado personal de favoritos.
- Perfil de emprendimiento, asociación opcional con publicaciones y página pública para perfiles activos.
- Seguimiento de emprendimientos.
- Notificaciones internas por nuevas publicaciones y cambios de disponibilidad.
- Mensajería comprador-vendedor con bandeja, chat, estado de lectura y notificación de mensajes.

## Requisitos

- PHP 8.3 o superior y Composer.
- Node.js y npm.
- SQLite o un motor compatible configurado en el archivo `.env`.

## Instalación local

Desde la carpeta del proyecto:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configura la conexión a la base de datos en `.env`. Para una base de datos nueva de desarrollo, aplica el esquema y carga las categorías:

```powershell
php artisan migrate --seed
```

Instala y compila los recursos del frontend, e inicia el servidor:

```powershell
npm install
npm run build
php artisan serve
```

Abre `http://127.0.0.1:8000`, regístrate en `/register` y verifica el correo para acceder a las funciones de estudiante. Para recibir el enlace de verificación durante el desarrollo local, configura `MAIL_MAILER=log` en `.env` y revisa `storage/logs/laravel.log`.

### Base existente sin categorías

Si la base ya tiene sus migraciones aplicadas, no es necesario volver a migrar. El seeder de categorías es idempotente y se puede ejecutar así:

```powershell
php artisan db:seed --class=CategorySeeder
```

## Rutas principales

Las funciones de estudiante requieren una sesión iniciada y correo verificado.

| Ruta | Descripción |
| --- | --- |
| `/marketplace` | Explorar publicaciones |
| `/my-listings` | Gestionar mis publicaciones |
| `/my-favorites` | Consultar mis publicaciones favoritas |
| `/my-entrepreneur` | Crear o gestionar mi emprendimiento |
| `/entrepreneurs-following` | Ver emprendimientos que sigo |
| `/notifications` | Consultar y marcar notificaciones |
| `/conversations` | Abrir la bandeja de mensajes |

Para iniciar una conversación, abre una publicación y selecciona la opción para contactar al vendedor. Solo el comprador y el vendedor participantes pueden consultar ese chat.

## Pruebas

Ejecuta la suite automatizada con:

```powershell
php artisan test
```

## Seguridad y configuración

- No incluyas `.env`, credenciales ni la base de datos local en commits.
- No compartas cuentas de demostración en este README.
- Configura correo, base de datos y claves de aplicación mediante variables de entorno; UniMarket no depende de servicios externos de mensajería push.
