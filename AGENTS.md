# AGENTS.md — Guía para agentes de IA en este repo

Este archivo es para cualquier asistente de IA (Claude, Copilot, Cursor, etc.) que un integrante del equipo use para trabajar en una tarea de este repo. Si sos un agente leyendo esto: leé también la descripción de la tarea de Jira específica que te asignaron (tiene los archivos exactos a tocar y una guía paso a paso) antes de escribir código — este archivo da el contexto general del proyecto, no reemplaza esa descripción puntual.

## Qué es este proyecto

AgroConnect: marketplace agro para el Consorcio Productivo de Laguna Naineck. TFI de Programación (UTN). Conecta productores locales con compradores.

## Stack

AgroConnect tiene DOS frontends que consumen el mismo backend Laravel — no son alternativos, son complementarios: la web (Inertia) cubre tanto el panel de administración como todas las funciones de productor/comprador, y la app Flutter cubre las mismas funciones de productor/comprador para mobile. Si tu tarea es de "Frontend" fijate en la Subtask de Jira si dice "(Web)" o "(Flutter)" — son implementaciones separadas de la misma funcionalidad, no te bases en una para hacer la otra sin leer su propia guía.

- Backend: Laravel 13 (PHP 8.3+), Inertia.js v2, Laravel Breeze (scaffold de auth), Laravel Sanctum (autenticación por token para la API que consume Flutter — Epic KAN-82, todavía no instalado, es tarea pendiente).
- Frontend Web: React + TypeScript, Tailwind CSS, Vite (vía Inertia).
- Frontend Mobile: Flutter (Android). El proyecto Flutter todavía NO existe en este repo — la primera tarea que lo toque tiene que crear la estructura base (`lib/screens/`, `lib/services/`, `lib/models/`, etc.) antes de la pantalla puntual.
- Testing: Pest 4.7 (`tests/Feature/`), `RefreshDatabase` aplicado globalmente a Feature tests vía `tests/Pest.php`. Flutter no tiene convención de testing definida todavía.
- Lint/format: Laravel Pint (PHP), ESLint + Prettier (JS/TS).
- Entorno local: Docker vía Laravel Sail.

## Qué ya está construido (no lo reinventes)

El modelo de datos base, las migraciones, los factories, las Policies y los Form Requests del MVP original YA EXISTEN. Lo que falta en la gran mayoría de las tareas es controlador + rutas + frontend (Web y/o Flutter). Antes de crear algo, fijate si ya existe:

- **Modelos** (`app/Models/`): `User`, `Categoria`, `Publicacion`, `Resena`, `Denuncia`, `Alerta`. Todos con `protected $table` explícito (Eloquent pluraliza mal nombres en español — ej. `Publicacion` → `publicacions` si no se lo fijás a mano, por eso está puesto en todos).
- **Policies** (`app/Policies/`): `PublicacionPolicy`, `DenunciaPolicy`, `ResenaPolicy` — tienen las reglas de negocio reales (quién puede editar/pausar/denunciar/reseñar qué). Usalas en los controladores vía `$this->authorize()` o el middleware `can:`, no reimplementes la lógica a mano.
- **Form Requests** (`app/Http/Requests/`): `StorePublicacionRequest`, `UpdatePublicacionRequest`, `StoreResenaRequest`, `StoreDenunciaRequest` — ya traen las validaciones, incluyendo reglas de negocio en `withValidator` (ej. no podés reseñar o denunciar tu propia publicación). Usalas como type-hint en el método del controlador, no valides a mano. Si tu tarea agrega un modelo nuevo (socios, favoritos, solicitudes de compra, etc.) seguí el mismo patrón: Form Request con la validación + regla de negocio, Policy si hace falta autorización fina.
- **Middleware**: `EnsureUserIsAdmin` (alias `admin`, registrado en `bootstrap/app.php`).

Lo que falta (y es el contenido real del backlog, ampliado después del MVP original con geolocalización, socios del Consorcio, compra directa/WhatsApp, panel de administración extendido e inteligencia/notificaciones): controladores, rutas (`routes/web.php` para Inertia y `routes/api.php` para lo que consume Flutter), las páginas/componentes Inertia en `resources/js/Pages/`, y las pantallas Flutter.

## Convenciones de nombres

Las tablas y columnas del dominio están en español (`categorias`, `publicaciones`, `resenas`, `denuncias`, `alertas`, y `rol`/`telefono` en `users`). Mantené esa convención en lo nuevo que agregues — no mezcles inglés para nombres de dominio.

## Migraciones

Nunca edites una migración que ya corrió en algún ambiente (la tuya local corrida cuenta). Si necesitás cambiar una tabla existente, creá una migración nueva. Si tenés dudas de si una ya corrió en otro lado, preguntá en el equipo antes de tocarla.

## Comandos útiles

```bash
./vendor/bin/sail up -d          # levantar el entorno
./vendor/bin/sail artisan test   # correr tests (Pest)
./vendor/bin/sail artisan pint --test   # chequear estilo PHP
npm run lint                     # ESLint (JS/TS)
npm run build                    # build de producción (también corre en CI)
```

## Flujo de trabajo (ramas, commits, PRs)

Ver [CONTRIBUTING.md](CONTRIBUTING.md) — ahí está la convención completa de nombres de rama (ligados al número de tarjeta de Jira), el proceso de PR, y la política de squash-merge. No la dupliques acá.

## Setup de entorno y convenciones de código completas

Ver [README.md](README.md), sección "Convenciones del proyecto".

## Backlog

El backlog completo (Epics, Historias, Subtasks con archivos a tocar y guía paso a paso) vive en Jira: https://matiasdn19-1995.atlassian.net/jira/software/projects/KAN/boards/2 — también hay un espejo de solo lectura en [BACKLOG.md](BACKLOG.md) para no depender de tener Jira abierto, pero la fuente de verdad siempre es Jira (este archivo no se actualiza solo).
