# AgroConnect

Marketplace agro para el **Consorcio Productivo de Laguna Naineck**. Trabajo Final Integrador de la carrera Programación (UTN).

**Equipo:** Nuñez Matías Daniel · Medina Nadia Carolina · Olmedo Iván Alejandro de Jesús

## Stack

- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** React 18 + TypeScript, integrado vía [Inertia.js](https://inertiajs.com) (sin API REST separada)
- **Autenticación:** Laravel Breeze (stack React)
- **Base de datos:** MySQL 8 (vía Docker, con Laravel Sail)
- **Testing:** [Pest](https://pestphp.com) (backend)
- **Estilo de código:** [Laravel Pint](https://laravel.com/docs/pint) (PHP) + ESLint/Prettier (TS/React)
- **CI:** GitHub Actions — corre tests, lint y build en cada push/PR a `main` y `develop`

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (con WSL2 si estás en Windows)
- [Composer](https://getcomposer.org) instalado localmente (solo para el primer `composer install`, después todo corre dentro de los contenedores)
- Node.js 22+ (para correr `npm` localmente mientras desarrollás el frontend)

## Puesta en marcha (primera vez)

```bash
git clone https://github.com/MatiasDN19/AgroConnect.git
cd AgroConnect

# 1. Variables de entorno
cp .env.example .env

# 2. Dependencias de PHP (necesario antes de levantar Sail)
composer install

# 3. Levantar los contenedores (app + MySQL)
./vendor/bin/sail up -d

# 4. Generar la key de la app
./vendor/bin/sail artisan key:generate

# 5. Migrar y cargar datos de ejemplo (categorías + usuarios de prueba)
./vendor/bin/sail artisan migrate --seed

# 6. Dependencias de JS y build del frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

La app queda disponible en **http://localhost**.

> Tip: armate un alias `alias sail='./vendor/bin/sail'` en tu terminal para no escribir `./vendor/bin/sail` cada vez — es lo que vas a usar en lugar de `php artisan` y `npm` mientras el proyecto corre en Docker.

### Usuarios de prueba (del seeder)

| Rol | Email | Password |
|---|---|---|
| Admin | `admin@agroconnect.test` | `password` |
| Productor | `productor@agroconnect.test` | `password` |

## Comandos del día a día

```bash
sail up -d              # levantar los contenedores
sail down                # apagarlos
sail artisan migrate      # correr migraciones nuevas
sail artisan test         # correr los tests (Pest)
sail npm run dev          # frontend en modo desarrollo (hot reload)

./vendor/bin/pint          # corregir estilo de código PHP
npx eslint resources/js --ext .js,.jsx,.ts,.tsx --fix   # corregir estilo JS/TS
```

## Estructura del modelo de datos

El diagrama completo está en [`docs/diagrama-base-de-datos.png`](docs/diagrama-base-de-datos.png). Resumen de las entidades principales:

- **users** — productores y administradores (campo `rol`)
- **categorias** — clasificación de productos
- **publicaciones** — productos publicados por un productor
- **resenas** — calificaciones de 1 a 5 sobre una publicación
- **denuncias** — reportes de usuarios sobre una publicación
- **alertas** — notificaciones internas por usuario

Las reglas de quién puede hacer qué sobre cada entidad están en `app/Policies/` (por ejemplo, solo el dueño de una publicación o un admin puede editarla/borrarla).

## Convenciones del proyecto

- **Nombres de tablas y columnas del dominio en español** (`publicaciones`, `usuario_id`, etc.), siguiendo el diagrama ya acordado con el Consorcio. Las tablas que trae Laravel/Breeze por defecto (`users`, `sessions`, `cache`, `jobs`) quedan en inglés, como vienen de fábrica.
- **Autorización:** vía [Policies](https://laravel.com/docs/authorization#creating-policies) de Laravel, no lógica de permisos suelta en los controllers.
- **Validación:** vía [Form Requests](https://laravel.com/docs/validation#form-request-validation) dedicados por acción (`StorePublicacionRequest`, `UpdatePublicacionRequest`, etc.), con mensajes de error en español.
- **Rutas del panel de administración:** protegidas con el middleware `admin` (alias de `EnsureUserIsAdmin`), no con checks manuales de rol en cada controller.
- Antes de hacer commit, correr `pint` y `eslint --fix` para no romper el CI por estilo.

## Testing

```bash
sail artisan test
```

Los tests viven en `tests/Feature/`, agrupados por tema (`Auth/`, `Policies/`, `Models/`). Usan `RefreshDatabase`, así que corren contra una base limpia cada vez — no hace falta preparar datos a mano salvo los que cada test necesite.

## Estado del proyecto

🚧 En desarrollo — entrega prevista para la primera semana de diciembre. El tablero de tareas del equipo está en Jira (link a agregar cuando esté armado).
