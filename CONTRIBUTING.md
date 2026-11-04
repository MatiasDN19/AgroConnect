# Cómo trabajamos en este repo

Guía rápida del flujo de trabajo del equipo. Si tenés dudas sobre esto, preguntale a Mati antes de improvisar — es más fácil alinear antes que desenredar después.

## Ramas

- **`main`** — lo que consideramos estable/entregable. Nadie pushea directo acá.
- **`develop`** — rama de integración. Todas las features se mergean primero acá.
- **`feature/KAN-123-descripcion-corta`** — una rama por tarea de Jira, creada desde `develop`.

```bash
git checkout develop
git pull origin develop
git checkout -b feature/KAN-123-login-productor
```

El número `KAN-123` es el ID de la tarjeta en Jira (`KAN-` + el número que aparece en el tablero). Nombrar así la rama hace que sea inmediato saber de qué tarea viene cada cosa, y si en algún momento conectamos Jira con GitHub, Jira va a poder mostrar automáticamente qué commits/PRs pertenecen a cada tarjeta.

Cuando termines tu rama, mergeala contra `develop` (no contra `main`) vía Pull Request.

## Pull Requests

1. Abrí la PR contra `develop` (salvo que sea una PR de `develop` → `main` para marcar una entrega).
2. En la descripción, poné qué tarjeta de Jira resuelve y un resumen de qué cambiaste.
3. El CI (GitHub Actions) tiene que estar en verde antes de poder mergear — corre tests, lint y build automáticamente.
4. **Por ahora, el merge lo hace Mati** (ver más abajo el porqué), así que avisale cuando tu PR esté lista y el CI esté verde.
5. Usamos **"Squash and merge"** al mergear (no "Merge commit" ni "Rebase") — así `develop`/`main` quedan con un commit prolijo por feature en vez de todo el ruido de commits intermedios ("wip", "arreglo typo", etc.).

## ¿Por qué merge solo Mati por ahora?

Arrancando el proyecto, para evitar errores mientras el equipo todavía se está acostumbrando al flujo de Git + CI + Jira, los merges a `develop` y `main` están restringidos a Mati (configurado en GitHub, no es solo una regla de buena fe). Esto es temporal: a medida que el equipo gane soltura con el flujo, se va a abrir el merge a todos. La idea es sumar autonomía de a poco, no mantenerlo así para siempre.

Esto **no** bloquea a nadie de abrir PRs, pushear a su propia rama, ni trabajar con normalidad — solo reserva el botón final de "Merge" mientras agarramos ritmo.

## Mensajes de commit

No hay una herramienta que lo fuerce, pero tratemos de seguir un formato simple y consistente:

```
tipo: descripción corta en infinitivo

feat: agregar formulario de publicar producto
fix: corregir validación de precio negativo
chore: actualizar dependencias de testing
docs: agregar instrucciones de Sail al README
```

Tipos comunes: `feat` (funcionalidad nueva), `fix` (corrección de bug), `chore` (tareas de mantenimiento, configuración), `docs` (documentación), `test` (tests).

## Setup del entorno local

Ver [README.md](README.md) — ahí están los pasos completos para levantar el proyecto con Sail.

## Convenciones de código

Ver la sección "Convenciones del proyecto" del [README.md](README.md) — Policies para autorización, Form Requests para validación, nombres de tablas del dominio en español.
