# AGENTS.md — Guía para agentes de IA en este repo

Este archivo es para cualquier asistente de IA (Claude, Copilot, Cursor, etc.) que un integrante del equipo use para trabajar en una tarea de este repo. Si sos un agente leyendo esto: leé también la descripción de la tarea de Jira específica que te asignaron (tiene los archivos exactos a tocar y una guía paso a paso) antes de escribir código — este archivo da el contexto general del proyecto, no reemplaza esa descripción puntual.

## Qué es este proyecto

AgroConnect: marketplace agro para el Consorcio Productivo de Laguna Naineck. TFI de Programación (UTN). Conecta productores locales con compradores.

## Stack

- Backend: Laravel 13 (PHP 8.3+), Inertia.js v2, Laravel Breeze (scaffold de auth).
- Frontend: React + TypeScript, Tailwind CSS, Vite.
- Testing: Pest 4.7 (`tests/Feature/`), `RefreshDatabase` aplicado globalmente a Feature tests vía `tests/Pest.php`.
- Lint/format: Laravel Pint (PHP), ESLint + Prettier (JS/TS).
- Entorno local: Docker vía Laravel Sail.

## Qué ya está construido (no lo reinventes)

El modelo de datos completo, las migraciones, los factories, las Policies y los Form Requests YA EXISTEN. Lo que falta en la gran mayoría de las tareas es controlador + rutas + frontend. Antes de crear algo, fijate si ya existe:

- **Modelos** (`app/Models/`): `User`, `Categoria`, `Publicacion`, `Resena`, `Denuncia`, `Alerta`. Todos con `protected $table` explícito (Eloquent pluraliza mal nombres en español — ej. `Publicacion` → `publicacions` si no se lo fijás a mano, por eso está puesto en todos).
- **Policies** (`app/Policies/`): `PublicacionPolicy`, `DenunciaPolicy`, `ResenaPolicy` — tienen las reglas de negocio reales (quién puede editar/pausar/denunciar/reseñar qué). Usalas en los controladores vía `$this->authorize()` o el middleware `can:`, no reimplementes la lógica a mano.
- **Form Requests** (`app/Http/Requests/`): `StorePublicacionRequest`, `UpdatePublicacionRequest`, `StoreResenaRequest`, `StoreDenunciaRequest` — ya traen las validaciones, incluyendo reglas de negocio en `withValidator` (ej. no podés reseñar o denunciar tu propia publicación). Usalas como type-hint en el método del controlador, no valides a mano.
- **Middleware**: `EnsureUserIsAdmin` (alias `admin`, registrado en `bootstrap/app.php`).

Lo que falta (y es el contenido real del backlog): controladores, rutas (`routes/web.php`, más allá de las de auth que trae Breeze), y las páginas/componentes Inertia en `resources/js/Pages/`.

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
