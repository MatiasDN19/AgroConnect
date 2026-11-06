# Backlog de AgroConnect

> **Este archivo es un espejo de solo lectura del tablero de Jira, no la fuente de verdad.** El backlog real (estados, asignaciones, comentarios, cambios día a día) vive en Jira — acá solo lo volcamos para tener el contexto completo versionado junto al código, útil para orientarse rápido o para pasarle contexto a un agente/IA sin salir del repo. No lo edites a mano pensando que se sincroniza con Jira: es al revés, si el backlog cambia en Jira, este archivo puede quedar desactualizado hasta que alguien lo regenere.

Tablero: https://matiasdn19-1995.atlassian.net/jira/software/projects/KAN/boards/2

---

## Epic: KAN-4 — Autenticación y perfil

_Completar lo que Breeze dejó a medias: elegir rol (productor/comprador) al registrarse, y poder editar datos de perfil como el teléfono. Login, registro básico, logout y recuperación de contraseña ya los provee Laravel Breeze._

### KAN-10 — Elegir rol (productor/comprador) al registrarme

> Como usuario nuevo, quiero elegir mi rol (productor o comprador) al registrarme, para que el sistema sepa qué funcionalidades mostrarme desde el principio.
>
> Criterios de aceptación:
>
> * El formulario de registro (Register.tsx) muestra una opción para elegir entre "Productor" y "Comprador".
> * El rol elegido se guarda en el campo `rol` del usuario (hoy el default en la base es "productor" si no se manda nada).
> * Si el rol es "productor", después del registro puede crear publicaciones (lo exige PublicacionPolicy::create).
> * Si el rol es "comprador", no puede crear publicaciones, pero sí puede dejar reseñas y denunciar.
> * No se puede elegir "admin" desde el registro público (los admins se crean aparte, vía seeder/consola).

<details>
<summary>**KAN-28** [backend] — Backend: agregar selección de rol al formulario y controlador de registro de Breeze</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Auth/RegisteredUserController.php` — agregar `rol` a la validación de `store()` y pasarlo a `User::create()`.
* `app/Models/User.php` — revisar si hace falta una constante `ROL_COMPRADOR` (hoy solo existen `ROL_PRODUCTOR` y `ROL_ADMIN`).

Guía:

1. Agregá la regla de validación para `rol` en `RegisteredUserController::store()` (required, string, uno de los roles permitidos para registro público — nunca "admin").
2. Pasá `$request->rol` al `User::create()`.
3. Si se decide tener un rol "comprador" explícito (no solo "no productor"), agregá la constante `ROL_COMPRADOR` en `User.php` y actualizá `esAdmin()`/`esProductor()` si hace falta un `esComprador()`.

</details>

<details>
<summary>**KAN-29** [frontend] — Frontend: agregar selector de rol (productor/comprador) en la página de registro</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Auth/Register.tsx` — agregar el campo rol al formulario.

Guía:

1. Agregá `rol` al estado inicial de `useForm` (junto a name, email, password).
2. Agregá un selector (radio buttons o `<select>`) con las opciones Productor/Comprador, siguiendo el estilo de `InputLabel` + `InputError` que ya usa el resto del formulario.
3. Mandá el valor elegido junto con el resto del formulario al hacer submit (ya viaja solo si lo agregaste al estado de `useForm`).

</details>

<details>
<summary>**KAN-30** [backend] — Tests: feature test que verifique que el rol elegido se guarda al registrarse</summary>

Archivos a crear/modificar:

* `tests/Feature/Auth/RegistrationTest.php` (ya existe, generado por Breeze) — agregar tests nuevos ahí.

Guía:

1. Agregá un test que registre un usuario mandando `rol=productor` y verifique que el usuario quedó creado con ese rol en la base.
2. Agregá un test que intente mandar `rol=admin` y verifique que NO se puede crear un admin desde el registro público.

</details>

### KAN-11 — Editar mi teléfono en el perfil

> Como usuario, quiero poder cargar o editar mi número de teléfono en mi perfil, para que los compradores interesados en mis publicaciones puedan contactarme.
>
> Criterios de aceptación:
>
> * La página de perfil (Profile/Edit.tsx) muestra un campo de teléfono editable.
> * `ProfileUpdateRequest` valida el campo `telefono` (hoy no lo valida, solo `name` y `email`).
> * El teléfono es opcional (la columna es nullable en la migración).
> * Al guardar el teléfono, no se dispara la re-verificación de email (esa lógica es solo para cambios de email).

<details>
<summary>**KAN-31** [backend] — Backend: agregar campo teléfono al controlador/request de actualización de perfil</summary>

Archivos a crear/modificar:

* `app/Http/Requests/ProfileUpdateRequest.php` — agregar `telefono` a `rules()` (hoy solo valida `name` y `email`).

Guía:

1. Agregá `'telefono' => ['nullable', 'string', 'max:50']` (ajustá el max al largo real que se quiera permitir) en `ProfileUpdateRequest::rules()`.
2. No hace falta tocar `ProfileController::update()` — ya usa `fill($request->validated())`, así que agregar la regla en el Request alcanza.

</details>

<details>
<summary>**KAN-32** [frontend] — Frontend: agregar campo de teléfono editable en la página de perfil</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.tsx` — agregar el campo teléfono.

Guía:

1. Agregá `telefono` al estado de `useForm` junto a name/email (revisá qué props recibe el componente, debería recibir el user actual con su teléfono).
2. Agregá un `TextInput` + `InputLabel` para teléfono, siguiendo el mismo patrón que el campo email en ese mismo archivo.

</details>

<details>
<summary>**KAN-33** [backend] — Tests: feature test de edición del teléfono en el perfil</summary>

Archivos a crear/modificar:

* `tests/Feature/ProfileTest.php` (ya existe, generado por Breeze) — agregar un test nuevo.

Guía:

1. Agregá un test que actualice el perfil mandando un teléfono y verifique que se guardó en la base.
2. Verificá que el teléfono es opcional (se puede actualizar el perfil sin mandarlo, sin error).

</details>

---

## Epic: KAN-5 — Categorías

_ABM de categorías para que el admin organice el catálogo, y su uso para navegar/filtrar publicaciones. El modelo Categoria ya existe en la base de datos._

### KAN-12 — Admin: crear, editar y eliminar categorías

> Como admin, quiero crear, editar y eliminar categorías, para mantener organizado el catálogo de publicaciones.
>
> Criterios de aceptación:
>
> * Solo un usuario con rol admin puede acceder a esta funcionalidad (bloqueado para productor/comprador).
> * Se puede crear una categoría con nombre y descripción.
> * Se puede editar el nombre/descripción de una categoría existente.
> * Se puede eliminar una categoría (definir qué pasa con las publicaciones que la tenían asignada, para no romper la foreign key).
> * Las validaciones respetan los límites de la tabla `categorias`.

<details>
<summary>**KAN-34** [backend] — Backend: controlador y rutas CRUD de categorías (solo admin)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/CategoriaController.php` (nuevo) — index, store, update, destroy.
* `routes/web.php` — agregar `Route::resource('categorias', CategoriaController::class)` (o rutas equivalentes) dentro de un grupo con middleware `['auth', 'admin']` (el middleware `admin` ya existe en `app/Http/Middleware/EnsureUserIsAdmin.php`).
* `app/Http/Requests/StoreCategoriaRequest.php` y `UpdateCategoriaRequest.php` (nuevos) — validar nombre/descripcion.

Guía:

1. Creá el controlador con los métodos CRUD, usando `Inertia::render` para las vistas.
2. Registrá las rutas en un grupo auth+admin en `web.php` (mirá cómo está armado el grupo de `/profile` como referencia de sintaxis).
3. Creá los Form Requests de validación siguiendo el estilo de `StorePublicacionRequest.php` (namespace, `authorize()`, `rules()`).

</details>

<details>
<summary>**KAN-35** [frontend] — Frontend: página de administración de categorías (listar, crear, editar, eliminar)</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Categorias/Index.tsx` (nuevo) — listado + formulario de crear/editar.

Guía:

1. Creá la carpeta `Categorias/` en `resources/js/Pages/`, siguiendo la estructura de carpetas de `Profile/` como referencia.
2. Armá una tabla con las categorías existentes y un formulario (modal o inline) para crear/editar, usando los componentes ya existentes (`TextInput`, `InputLabel`, `PrimaryButton`, `Modal`).
3. Usá `AuthenticatedLayout` como layout de la página.

</details>

<details>
<summary>**KAN-36** [backend] — Tests: feature test de CRUD de categorías y bloqueo a usuarios no admin</summary>

Archivos a crear/modificar:

* `tests/Feature/CategoriaControllerTest.php` (nuevo).

Guía:

1. Testeá que un admin puede crear, editar y eliminar una categoría.
2. Testeá que un productor o comprador NO puede acceder a estas rutas (debería dar 403, gracias al middleware `admin`).

</details>

### KAN-13 — Ver el listado de categorías al navegar publicaciones

> Como usuario, quiero ver el listado de categorías disponibles al navegar publicaciones, para poder explorar el catálogo organizado por tipo de producto.
>
> Criterios de aceptación:
>
> * Se muestra el listado completo de categorías existentes (sin paginar, son pocas).
> * Cada categoría lleva al listado de publicaciones filtrado por esa categoría (ver Historia "Buscar/filtrar publicaciones por categoría").
> * A definir con Mati: si hay vista pública sin login o el listado solo aplica a usuarios autenticados.

<details>
<summary>**KAN-37** [backend] — Backend: endpoint para listar categorías disponibles</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/CategoriaController.php` (mismo controlador de la Historia de ABM) — reutilizar o agregar un método para exponer el listado a cualquier usuario autenticado.

Guía:

1. Decidí si se reutiliza el `index()` del CRUD de categorías (subtask del admin) o se separa un endpoint de solo lectura.
2. Devolvé las categorías ordenadas por nombre.

</details>

<details>
<summary>**KAN-38** [frontend] — Frontend: mostrar categorías en el navegador/filtro de publicaciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Index.tsx` (se crea en la Historia "Ver listado de publicaciones activas") o un componente compartido, ej. `resources/js/Components/CategoriaFiltro.tsx`.

Guía:

1. Mostrá las categorías recibidas como prop en la página de listado de publicaciones (o donde se decida que vivan).
2. Cada categoría es clickeable/seleccionable y dispara el filtro — coordiná con la subtask de frontend de "Buscar/filtrar por categoría" para no duplicar trabajo.

</details>

<details>
<summary>**KAN-39** [backend] — Tests: feature test del listado de categorías</summary>

Archivos a crear/modificar:

* `tests/Feature/CategoriaControllerTest.php` (mismo archivo del CRUD de categorías, agregar un test más) o un test nuevo si se separó el endpoint.

Guía:

1. Testeá que el endpoint de listado devuelve todas las categorías existentes.
2. Testeá que es accesible para cualquier usuario autenticado (no solo admin).

</details>

---

## Epic: KAN-6 — Publicaciones

_Crear, editar y gestionar el estado de publicaciones de productos (productor), y poder listar/buscar/ver el detalle (comprador). El modelo, policy (PublicacionPolicy) y form requests (Store/UpdatePublicacionRequest) ya están hechos; falta controlador, rutas y frontend._

### KAN-14 — Productor: crear una publicación

> Como productor, quiero crear una publicación de mi producto, para ofrecerlo en el marketplace.
>
> Criterios de aceptación:
>
> * Solo usuarios con rol productor pueden crear publicaciones (PublicacionPolicy::create).
> * El formulario pide categoría, título, descripción, precio, unidad y stock (ya validado por StorePublicacionRequest).
> * El precio y el stock no pueden ser negativos.
> * La publicación queda asociada al usuario autenticado.
> * Si no se especifica estado, queda en "activa" por defecto.

<details>
<summary>**KAN-40** [backend] — Backend: controlador y ruta para crear publicación usando StorePublicacionRequest</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` (nuevo) — método `store()`.
* `routes/web.php` — agregar `Route::post('/publicaciones', [PublicacionController::class, 'store'])` dentro del grupo `auth`.

Guía:

1. Creá el controlador (si todavía no existe; lo vas a ir completando en esta y las próximas Historias de Publicaciones).
2. `store(StorePublicacionRequest $request)` ya tiene la validación y la autorización resueltas por el Request — solo falta armar `Publicacion::create([...$request->validated(), 'usuario_id' => auth()->id()])`.
3. Redirigí al detalle de la publicación recién creada.

</details>

<details>
<summary>**KAN-41** [frontend] — Frontend: formulario Inertia para crear una publicación</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Create.tsx` (nuevo).

Guía:

1. Creá la carpeta `Publicaciones/` en `resources/js/Pages/`.
2. Armá el formulario con los campos de `StorePublicacionRequest`: categoria_id (select, recibí las categorías como prop), titulo, descripcion, precio, unidad, stock.
3. Seguí el patrón de `useForm` + `InputError` + `PrimaryButton` que ya usan `Register.tsx` y el formulario de perfil.

</details>

<details>
<summary>**KAN-42** [backend] — Tests: feature test de creación de publicación (incluye casos que bloquea la policy)</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php` (nuevo).

Guía:

1. Testeá que un productor puede crear una publicación con datos válidos.
2. Testeá que un comprador (no productor) NO puede crear una publicación (la PublicacionPolicy ya bloquea esto; el test feature confirma que se refleja en el endpoint real).
3. Testeá la validación de precio/stock negativos.

</details>

### KAN-15 — Productor: editar una publicación

> Como productor, quiero editar mi publicación, para corregir datos o actualizar el precio.
>
> Criterios de aceptación:
>
> * Solo el dueño de la publicación o un admin pueden editarla (PublicacionPolicy::update).
> * Los campos son los mismos que en la creación, todos opcionales en la edición (UpdatePublicacionRequest usa `sometimes`).
> * Un productor no puede editar la publicación de otro productor.

<details>
<summary>**KAN-43** [backend] — Backend: controlador y ruta para editar publicación usando UpdatePublicacionRequest y PublicacionPolicy</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método `update()`.
* `routes/web.php` — `Route::put('/publicaciones/{publicacion}', [PublicacionController::class, 'update'])`.

Guía:

1. `update(UpdatePublicacionRequest $request, Publicacion $publicacion)` ya tiene la autorización resuelta en el Request (usa `PublicacionPolicy::update` vía route model binding).
2. Actualizá la publicación con `$publicacion->update($request->validated())`.

</details>

<details>
<summary>**KAN-44** [frontend] — Frontend: formulario Inertia para editar una publicación existente</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Edit.tsx` (nuevo).

Guía:

1. Similar a `Create.tsx` pero precargando los datos actuales de la publicación (recibida como prop).
2. Considerá extraer un formulario compartido entre `Create.tsx` y `Edit.tsx` si tiene sentido, en vez de duplicar todo el JSX.

</details>

<details>
<summary>**KAN-45** [backend] — Tests: feature test que verifique que solo el dueño puede editar su publicación</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php` (mismo archivo, agregar tests).

Guía:

1. Testeá que el dueño puede editar su publicación.
2. Testeá que otro productor NO puede editar una publicación ajena (403).
3. Testeá que un admin SÍ puede editar cualquier publicación.

</details>

### KAN-16 — Productor: marcar una publicación como pausada o vendida

> Como productor, quiero marcar mi publicación como pausada o vendida, para reflejar su disponibilidad real sin tener que borrarla.
>
> Criterios de aceptación:
>
> * Los estados posibles son: activa, pausada, vendida (constantes en el modelo Publicacion).
> * Solo el dueño de la publicación o un admin pueden cambiar el estado (misma regla que editar).
> * Una publicación pausada o vendida no debería aparecer en el listado público de activas.

<details>
<summary>**KAN-46** [backend] — Backend: endpoint para cambiar el estado de una publicación (pausada/vendida) respetando PublicacionPolicy</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método nuevo (ej. `cambiarEstado()`) o reutilizar `update()` si el estado viaja dentro del mismo payload.
* `routes/web.php` — ruta específica, ej. `Route::patch('/publicaciones/{publicacion}/estado', ...)`.

Guía:

1. Decidí si es un endpoint propio o parte del `update()` general (`UpdatePublicacionRequest` ya valida `estado` con `Rule::in` de los 3 estados posibles).
2. Reusa `PublicacionPolicy::update` como autorización (misma regla: dueño o admin).

</details>

<details>
<summary>**KAN-47** [frontend] — Frontend: botones/acciones en la vista de la publicación para cambiar su estado</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` (se crea en la Historia "Ver el detalle de una publicación") o `Edit.tsx`.

Guía:

1. Agregá botones "Pausar" / "Marcar como vendida" / "Reactivar", visibles solo para el dueño (o admin) de la publicación.
2. Cada botón dispara un patch al endpoint de cambio de estado.

</details>

<details>
<summary>**KAN-48** [backend] — Tests: feature test de cambio de estado de publicación</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php`.

Guía:

1. Testeá que el dueño puede cambiar el estado de su publicación a pausada y a vendida.
2. Testeá que otro usuario no puede cambiar el estado de una publicación ajena.

</details>

### KAN-17 — Ver listado de publicaciones activas

> Como usuario, quiero ver el listado de publicaciones activas, para explorar los productos disponibles en el marketplace.
>
> Criterios de aceptación:
>
> * Solo se muestran publicaciones con estado "activa".
> * El listado muestra al menos título, precio, categoría y la puntuación promedio (Publicacion::puntuacionPromedio()).
> * Las publicaciones pausadas o vendidas no aparecen acá (pero sí deberían verse en "mis publicaciones" del dueño; eso queda fuera del alcance de esta historia).

<details>
<summary>**KAN-49** [backend] — Backend: endpoint para listar publicaciones activas (público)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método `index()`.
* `routes/web.php` — `Route::get('/publicaciones', [PublicacionController::class, 'index'])`.

Guía:

1. `index()` consulta `Publicacion::where('estado', Publicacion::ESTADO_ACTIVA)`, con la relación `categoria` cargada (`with('categoria')`).
2. Devolvé también la puntuación promedio de cada una (usando `puntuacionPromedio()` del modelo, o un `withAvg` si se quiere optimizar).

</details>

<details>
<summary>**KAN-50** [frontend] — Frontend: página de listado de publicaciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Index.tsx` (nuevo).

Guía:

1. Listá las publicaciones recibidas como prop: título, precio, categoría, puntuación promedio.
2. Cada publicación lleva al detalle (Historia "Ver el detalle de una publicación").
3. Usá `AuthenticatedLayout`.

</details>

<details>
<summary>**KAN-51** [backend] — Tests: feature test que solo muestre publicaciones activas en el listado</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php`.

Guía:

1. Creá publicaciones en estado activa, pausada y vendida, y verificá que el listado solo devuelve las activas.

</details>

### KAN-18 — Buscar/filtrar publicaciones por categoría

> Como usuario, quiero buscar o filtrar publicaciones por categoría, para encontrar más rápido lo que necesito.
>
> Criterios de aceptación:
>
> * Se puede filtrar el listado de publicaciones activas por una categoría específica.
> * El filtro se combina con la regla de "solo activas" (no se filtran publicaciones pausadas/vendidas).
> * Si no hay publicaciones para esa categoría, se muestra un estado vacío claro, no un error.

<details>
<summary>**KAN-52** [backend] — Backend: agregar filtro por categoría al endpoint de listado de publicaciones</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — `index()` (mismo método de la Historia de listado, agregarle el filtro).

Guía:

1. Aceptá un parámetro de query (ej. `?categoria_id=X`) en `index()` y aplicá un `where('categoria_id', ...)` condicional cuando venga.
2. No rompas el comportamiento sin filtro (debe seguir devolviendo todas las activas si no se manda `categoria_id`).

</details>

<details>
<summary>**KAN-53** [frontend] — Frontend: selector/buscador de categoría en la página de listado</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Index.tsx` (mismo archivo de la Historia de listado).

Guía:

1. Agregá un selector de categoría (reusá el listado de categorías del Epic Categorías) que al cambiar dispare una nueva request con el filtro (`router.get` de Inertia con los query params).

</details>

<details>
<summary>**KAN-54** [backend] — Tests: feature test de búsqueda y filtrado de publicaciones por categoría</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php`.

Guía:

1. Testeá que el filtro por `categoria_id` devuelve solo publicaciones de esa categoría.
2. Testeá que sin filtro se siguen devolviendo todas las activas.

</details>

### KAN-19 — Ver el detalle de una publicación

> Como usuario, quiero ver el detalle de una publicación, para decidir si me interesa comprarla.
>
> Criterios de aceptación:
>
> * Se muestran todos los datos de la publicación: título, descripción, precio, unidad, stock, categoría, datos de contacto del productor (nombre, teléfono si lo cargó).
> * Se muestra la puntuación promedio y el listado de reseñas.
> * El detalle es accesible para cualquier usuario autenticado (PublicacionPolicy::view devuelve true).

<details>
<summary>**KAN-55** [backend] — Backend: endpoint para mostrar el detalle de una publicación</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método `show()`.
* `routes/web.php` — `Route::get('/publicaciones/{publicacion}', [PublicacionController::class, 'show'])`.

Guía:

1. `show(Publicacion $publicacion)` usa route model binding; cargá las relaciones `usuario`, `categoria` y `resenas.usuario`.
2. `PublicacionPolicy::view` ya autoriza a cualquier usuario autenticado.

</details>

<details>
<summary>**KAN-56** [frontend] — Frontend: página de detalle de una publicación</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` (nuevo).

Guía:

1. Mostrá todos los datos de la publicación + datos de contacto del productor (nombre, teléfono si tiene).
2. Dejá un lugar para el listado de reseñas (Epic Reseñas) y los botones de cambio de estado si corresponde (Historia "marcar como pausada o vendida").

</details>

<details>
<summary>**KAN-57** [backend] — Tests: feature test que verifique el contenido mostrado en el detalle</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php`.

Guía:

1. Testeá que el detalle devuelve los datos esperados de la publicación, su categoría y su dueño.

</details>

---

## Epic: KAN-7 — Reseñas

_Dejar y ver reseñas en publicaciones. ResenaPolicy y StoreResenaRequest (que ya bloquean auto-reseña y reseña duplicada) están hechos; falta controlador, rutas y frontend._

### KAN-20 — Comprador: dejar una reseña en una publicación (no la mía)

> Como comprador, quiero dejar una reseña con puntuación y comentario en una publicación, para compartir mi experiencia con otros usuarios.
>
> Criterios de aceptación:
>
> * La puntuación es obligatoria, entre 1 y 5 (StoreResenaRequest).
> * El comentario es opcional, hasta 2000 caracteres.
> * No se puede reseñar la propia publicación (ya validado en StoreResenaRequest::withValidator).
> * No se puede dejar más de una reseña por publicación por usuario (ya validado, mensaje "Ya dejaste una reseña en esta publicación").
> * La ruta esperada es anidada: POST /publicaciones/{publicacion}/resenas.

<details>
<summary>**KAN-58** [backend] — Backend: controlador y ruta para crear reseña usando StoreResenaRequest y ResenaPolicy</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/ResenaController.php` (nuevo) — método `store()`.
* `routes/web.php` — `Route::post('/publicaciones/{publicacion}/resenas', [ResenaController::class, 'store'])` (ruta anidada, tal como espera `StoreResenaRequest` según su comentario).

Guía:

1. `store(StoreResenaRequest $request, Publicacion $publicacion)` — la autorización y las validaciones de auto-reseña/reseña duplicada ya están resueltas en el Request.
2. Creá la reseña con `$publicacion->resenas()->create([...$request->validated(), 'usuario_id' => auth()->id()])`.

</details>

<details>
<summary>**KAN-59** [frontend] — Frontend: formulario para dejar una reseña en una publicación</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` (mismo archivo del detalle) o un `resources/js/Components/ResenaForm.tsx` aparte.

Guía:

1. Agregá un formulario de reseña (puntuación 1-5 + comentario opcional) en la página de detalle de la publicación.
2. Mostrá los mensajes de error que devuelve el backend si el usuario ya reseñó o intenta reseñar su propia publicación.

</details>

<details>
<summary>**KAN-60** [backend] — Tests: feature test de creación de reseña (bloqueo de auto-reseña y reseña duplicada)</summary>

Archivos a crear/modificar:

* `tests/Feature/ResenaControllerTest.php` (nuevo).

Guía:

1. Testeá que un usuario puede dejar una reseña válida.
2. Testeá que no puede reseñar su propia publicación (mensaje de error esperado).
3. Testeá que no puede dejar dos reseñas en la misma publicación.

</details>

### KAN-21 — Ver las reseñas de una publicación

> Como usuario, quiero ver las reseñas de una publicación, para evaluar la confiabilidad del productor antes de comprar.
>
> Criterios de aceptación:
>
> * Se listan todas las reseñas de la publicación con puntuación, comentario y nombre del usuario que la dejó.
> * Se muestra la puntuación promedio (Publicacion::puntuacionPromedio()).
> * Si no hay reseñas todavía, se muestra un estado vacío claro.

<details>
<summary>**KAN-61** [backend] — Backend: endpoint para listar las reseñas de una publicación</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método `show()` (mismo de la Historia "Ver el detalle de una publicación", agregar la carga de reseñas).

Guía:

1. Cargá la relación `resenas.usuario` en el `show()` de `PublicacionController` (`with(['resenas.usuario'])`), así no hace falta un controlador/endpoint aparte.

</details>

<details>
<summary>**KAN-62** [frontend] — Frontend: mostrar listado de reseñas en la página de detalle de la publicación</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` (mismo archivo del detalle).

Guía:

1. Listá las reseñas recibidas (puntuación, comentario, nombre de quien la dejó).
2. Mostrá la puntuación promedio arriba del listado.

</details>

<details>
<summary>**KAN-63** [backend] — Tests: feature test que verifique que las reseñas se muestran correctamente</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php` (o `ResenaControllerTest.php`).

Guía:

1. Creá reseñas de prueba para una publicación y verificá que el detalle las devuelve con los datos correctos.

</details>

---

## Epic: KAN-8 — Denuncias y moderación

_Denunciar publicaciones/usuarios sospechosos y el panel de admin para revisarlas y resolverlas. DenunciaPolicy y StoreDenunciaRequest (bloquea auto-denuncia) ya están hechos; falta controlador, rutas y frontend._

### KAN-22 — Denunciar una publicación o usuario sospechoso

> Como usuario, quiero denunciar una publicación o usuario sospechoso, para alertar a los administradores de un posible problema.
>
> Criterios de aceptación:
>
> * Se pide un motivo (obligatorio, hasta 255 caracteres) y una descripción opcional (hasta 2000 caracteres).
> * No se puede denunciar la propia publicación (ya validado en StoreDenunciaRequest::withValidator).
> * La denuncia se crea en estado "pendiente" (Denuncia::ESTADO_PENDIENTE).
> * La ruta esperada es anidada: POST /publicaciones/{publicacion}/denuncias.
> * DECIDIDO: un mismo usuario NO puede denunciar más de una vez la misma publicación (evita spam de denuncias repetidas). Usuarios DISTINTOS SÍ pueden denunciar la misma publicación cada uno la suya — esto es intencional, le sirve al admin para ver que hay varias quejas acumuladas sobre una misma publicación. Se implementa con una constraint unique(usuario_id, publicacion_id) en la tabla denuncias (mismo patrón que ya existe en resenas) + la validación correspondiente en StoreDenunciaRequest.

<details>
<summary>**KAN-64** [backend] — Backend: controlador y ruta para crear denuncia usando StoreDenunciaRequest y DenunciaPolicy</summary>

Archivos a crear/modificar:

* `database/migrations/2026_11_03_231020_create_denuncias_table.php` — agregar `$table->unique(['usuario_id', 'publicacion_id']);` (mismo patrón que ya tiene la tabla resenas). Si la migración ya corrió en algún ambiente, puede hacer falta una migración nueva en vez de editar la existente — revisarlo en equipo antes de tocarla.
* `app/Http/Requests/StoreDenunciaRequest.php` — en `withValidator`, agregar la validación de denuncia duplicada (mismo usuario + misma publicación), con el mismo estilo que ya usa para bloquear la auto-denuncia.
* `app/Http/Controllers/DenunciaController.php` (nuevo) — método `store()`.
* `routes/web.php` — `Route::post('/publicaciones/{publicacion}/denuncias', [DenunciaController::class, 'store'])`.

Guía:

1. `store(StoreDenunciaRequest $request, Publicacion $publicacion)` — autorización y validación de auto-denuncia Y de denuncia duplicada ya resueltas en el Request una vez que lo actualices.
2. Creá la denuncia con `$publicacion->denuncias()->create([...$request->validated(), 'usuario_id' => auth()->id(), 'estado' => Denuncia::ESTADO_PENDIENTE])`.

</details>

<details>
<summary>**KAN-65** [frontend] — Frontend: formulario/botón para denunciar una publicación o usuario</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` (mismo archivo del detalle) — agregar botón "Denunciar" que abre un Modal con el formulario.

Guía:

1. Usá el componente `Modal.tsx` ya existente para el formulario de denuncia (motivo + descripción opcional).
2. Mostrá confirmación o error según la respuesta.

</details>

<details>
<summary>**KAN-66** [backend] — Tests: feature test de creación de denuncia (bloqueo de auto-denuncia)</summary>

Archivos a crear/modificar:

* `tests/Feature/DenunciaControllerTest.php` (nuevo).

Guía:

1. Testeá que un usuario puede denunciar una publicación ajena.
2. Testeá que no puede denunciar su propia publicación.
3. Testeá que un usuario no puede denunciar dos veces la misma publicación (debe fallar la segunda vez).
4. Testeá que DOS usuarios distintos SÍ pueden denunciar, cada uno por separado, la misma publicación (esto tiene que funcionar bien, no bloquearse).

</details>

### KAN-23 — Admin: ver listado de denuncias pendientes

> Como admin, quiero ver el listado de denuncias pendientes, para poder revisarlas y tomar una decisión.
>
> Criterios de aceptación:
>
> * Solo un usuario con rol admin puede acceder a este listado (DenunciaPolicy::viewAny).
> * Se muestran al menos: motivo, descripción, quién denunció, y qué publicación/usuario fue denunciado.
> * Se pueden filtrar por estado (pendiente, revisada, descartada).

<details>
<summary>**KAN-67** [backend] — Backend: endpoint para listar denuncias pendientes (solo admin)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/DenunciaController.php` — método `index()`.
* `routes/web.php` — `Route::get('/admin/denuncias', [DenunciaController::class, 'index'])` dentro de un grupo con middleware `['auth', 'admin']`.

Guía:

1. `index()` lista denuncias con estado pendiente por defecto (permití filtrar por estado vía query param).
2. `DenunciaPolicy::viewAny` ya bloquea a no-admins.

</details>

<details>
<summary>**KAN-68** [frontend] — Frontend: panel de admin con el listado de denuncias pendientes</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Denuncias/Index.tsx` (nuevo — primera página bajo una carpeta `Admin/`, creála siguiendo el mismo patrón que `Categorias/`).

Guía:

1. Armá una tabla con motivo, descripción, quién denunció, publicación/usuario denunciado y estado.
2. Esta página solo debería ser alcanzable por admins (el middleware ya lo protege en el backend, pero evitá linkearla desde la navegación de usuarios comunes).

</details>

<details>
<summary>**KAN-69** [backend] — Tests: feature test que bloquee el acceso a usuarios no admin</summary>

Archivos a crear/modificar:

* `tests/Feature/DenunciaControllerTest.php`.

Guía:

1. Testeá que un admin puede ver el listado de denuncias.
2. Testeá que un productor/comprador recibe 403 al intentar acceder.

</details>

### KAN-24 — Admin: resolver una denuncia (aceptar/rechazar)

> Como admin, quiero poder marcar una denuncia como revisada o descartada, para mantener la calidad y confianza del marketplace.
>
> Criterios de aceptación:
>
> * Solo un admin puede cambiar el estado de una denuncia (DenunciaPolicy::update).
> * Los estados posibles son: pendiente, revisada, descartada (constantes del modelo Denuncia).
> * Al resolver una denuncia, se podría disparar una Alerta para el usuario que denunció (a definir junto con el Epic de Alertas).

<details>
<summary>**KAN-70** [backend] — Backend: endpoint para aceptar/rechazar una denuncia usando DenunciaPolicy</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/DenunciaController.php` — método `update()` (cambiar estado).
* `routes/web.php` — `Route::patch('/admin/denuncias/{denuncia}', [DenunciaController::class, 'update'])`.

Guía:

1. `update(Request $request, Denuncia $denuncia)` autoriza con `DenunciaPolicy::update` (solo admin).
2. Validá que el nuevo estado sea uno de los válidos (`revisada`/`descartada`) y actualizá.

</details>

<details>
<summary>**KAN-71** [frontend] — Frontend: acciones de aceptar/rechazar en el panel de denuncias</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Denuncias/Index.tsx` (mismo archivo del panel de denuncias).

Guía:

1. Agregá botones "Marcar como revisada" / "Descartar" en cada fila de la tabla.
2. No hace falta manejar el refresh manualmente — Inertia ya actualiza los props tras el patch.

</details>

<details>
<summary>**KAN-72** [backend] — Tests: feature test de resolución de denuncias</summary>

Archivos a crear/modificar:

* `tests/Feature/DenunciaControllerTest.php`.

Guía:

1. Testeá que un admin puede cambiar el estado de una denuncia.
2. Testeá que un no-admin no puede.

</details>

---

## Epic: KAN-9 — Alertas

_Notificaciones in-app (campanita) y por mail ante eventos relevantes (nueva reseña, denuncia resuelta, etc.), más el historial de notificaciones. El modelo Alerta existe en la base de datos pero el comportamiento está totalmente por construir._

### KAN-25 — Recibir notificación in-app (campanita) ante eventos relevantes

> Como usuario, quiero recibir una notificación in-app (campanita) cuando pase algo relevante, para enterarme sin tener que estar revisando manualmente.
>
> Criterios de aceptación:
>
> * Se genera una Alerta (el modelo ya existe) ante eventos como: nueva reseña en mi publicación, denuncia resuelta, etc. (lista final de eventos a definir con Mati).
> * La campanita muestra un contador de alertas no leídas (campo `leida` del modelo Alerta).
> * El campo `tipo` de la Alerta permite diferenciar el tipo de evento en el frontend.

<details>
<summary>**KAN-73** [backend] — Backend: lógica para generar una Alerta ante eventos relevantes (nueva reseña, denuncia resuelta, etc.)</summary>

**Archivos a crear/modificar:**

* `app/Models/Alerta.php` (ya existe, no debería necesitar cambios: ya tiene `usuario_id`, `tipo`, `mensaje`, `leida`)
* Punto de generación de la alerta: lo más simple es crearla directamente donde ya ocurre el evento, por ejemplo:

    * En el controller que guarda una Reseña nueva (cuando se implemente, Historia de Reseñas), después de crear la Resena: `Alerta::create(['usuario_id' => $publicacion->usuario_id, 'tipo' => 'nueva_resena', 'mensaje' => '...', 'leida' => false])`.
    * En el controller/acción donde el admin resuelve una Denuncia (Historia KAN-24), avisarle al usuario denunciado o al denunciante según corresponda.
    
* Si se quiere algo más prolijo y desacoplado se puede usar un Observer (`app/Observers/ResenaObserver.php`) o un Event+Listener de Laravel, pero no es obligatorio para el MVP: decidan en equipo si conviene la versión simple (crear la Alerta inline en el controller) o la del Observer.

**Guía:**

1. Definan junto con el resto del equipo qué eventos concretos generan alerta (mínimo: nueva reseña recibida en una publicación propia, denuncia resuelta).
2. Por cada evento, agreguen la línea de `Alerta::create(...)` en el lugar donde ese evento ya se procesa en el backend (no hace falta tocar el modelo `Alerta`, ya está listo).
3. El campo `mensaje` tiene que ser un texto legible para mostrar en la campanita (ej: "Tu publicación 'Tomates' recibió una nueva reseña").
4. Dejen `leida` en `false` al crearla; se marca como leída desde la Historia KAN-27 (ver KAN-79).

Esta task es la base para KAN-74 (campanita en el front) y KAN-76 (mail): ambas dependen de que acá se esté generando realmente la Alerta en la base de datos.

</details>

<details>
<summary>**KAN-74** [frontend] — Frontend: componente de campanita con contador de notificaciones no leídas</summary>

**Archivos a crear/modificar:**

* `resources/js/Components/Campanita.tsx` (crear)
* `resources/js/Layouts/AuthenticatedLayout.tsx` (modificar: hoy ya tiene la barra de navegación superior con el nombre del usuario y el Dropdown de cuenta; ahí hay que agregar el ícono de campanita)

**Guía:**

1. Creen `Campanita.tsx` como un componente que reciba o pida (via Inertia `usePage().props` o un fetch propio) la cantidad de alertas con `leida = false` del usuario actual.
2. Muéstrenlo como un ícono de campana con un contador (badge) arriba a la derecha, solo visible si el contador es mayor a 0.
3. Para traer el dato real necesitan que el backend ya expon ga algo: pueden usar un endpoint simple tipo `GET /alertas/no-leidas-count` (ver si conviene sumarlo a la Historia KAN-79) o, mientras tanto, compartir el conteo como prop global de Inertia (`HandleInertiaRequests::share()`) para no bloquearse esperando esa otra task.
4. Importénlo en `AuthenticatedLayout.tsx` junto al Dropdown de usuario que ya existe, reusando los mismos estilos de Tailwind del resto del header.
5. Esta task es solo el ícono + contador; el panel desplegable con el listado de notificaciones es la Historia KAN-27 / subtask KAN-80, no hace falta implementarlo acá.

</details>

<details>
<summary>**KAN-75** [backend] — Tests: feature test que verifique que se genera una Alerta ante el evento correspondiente</summary>

**Archivos a crear/modificar:**

* `tests/Feature/AlertaTest.php` (crear)

**Guía:**

1. Sigan el estilo Pest que ya usa el proyecto (ver `tests/Feature/Policies/PublicacionPolicyTest.php` como referencia: `it('...', function () { ... });` + `User::factory()->create()` + `expect(...)->toBeTrue()`).
2. Casos mínimos a cubrir:

    * Al ocurrir el evento elegido en KAN-73 (ej: crear una Resena sobre una publicación), se crea en la base una `Alerta` con `usuario_id` igual al dueño de la publicación y `leida = false`. Usen `assertDatabaseHas('alertas', [...])`.
    * El mensaje de la alerta no viene vacío.
    
3. Esta task depende de que KAN-73 ya esté andando (necesitan el código que genera la Alerta para poder testearlo).

</details>

### KAN-26 — Recibir un mail ante esos mismos eventos

> Como usuario, quiero recibir un mail cuando pase algo relevante, para enterarme aunque no tenga la aplicación abierta.
>
> Criterios de aceptación:
>
> * Se envía un mail por los mismos eventos que generan una Alerta in-app.
> * El usuario puede activar o desactivar las notificaciones por mail desde su perfil (requiere un campo nuevo en `users`, a definir).
> * Los tests usan `Mail::fake()` para verificar el envío sin mandar mails reales.

<details>
<summary>**KAN-76** [backend] — Backend: Mailable y envío de mail ante los mismos eventos que generan una Alerta</summary>

**Archivos a crear/modificar:**

* `app/Mail/NuevaAlertaMail.php` (crear, con `php artisan make:mail NuevaAlertaMail`)
* `resources/views/emails/nueva-alerta.blade.php` (crear, vista simple del mail)
* El mismo punto donde en KAN-73 se hace `Alerta::create(...)`: agregar justo después `Mail::to($usuario)->send(new NuevaAlertaMail($alerta))` (modificar el controller/observer correspondiente)

**Guía:**

1. Esta task depende de KAN-73: reusen exactamente los mismos puntos donde ya se genera la Alerta, no inventen una lógica de eventos paralela.
2. `NuevaAlertaMail` recibe la instancia de `Alerta` en el constructor y la expone a la vista Blade (mensaje, tipo, fecha).
3. Antes de enviar el mail de verdad, respeten la preferencia del usuario que se agrega en KAN-77 (si el usuario desactivó los mails, no enviar). Si KAN-77 todavía no está lista, enviar siempre y después agregar el chequeo cuando esté el campo.
4. Configuren el mailer de desarrollo (ej. Mailtrap o el log driver de Laravel) según lo que ya tengan en `.env.example`/README del proyecto para poder ver el mail sin enviar uno real.

</details>

<details>
<summary>**KAN-77** [frontend] — Frontend: opción en el perfil para activar/desactivar notificaciones por mail</summary>

**Archivos a crear/modificar:**

* Nueva migración, ej. `database/migrations/xxxx_xx_xx_add_notificar_por_mail_to_users_table.php` (crear: columna booleana `notificar_por_mail`, default `true`)
* `app/Http/Requests/ProfileUpdateRequest.php` (modificar: hoy solo valida `name` y `email`, hay que sumar la regla para el nuevo campo)
* `resources/js/Pages/Profile/Partials/UpdateNotificationPreferencesForm.tsx` (crear, siguiendo el patrón de `useForm` + `InputLabel` que ya usan los otros partials de `Profile/`)
* `resources/js/Pages/Profile/Edit.tsx` (modificar: importar y renderizar el nuevo partial junto a los que ya existen)
* `app/Http/Controllers/ProfileController.php` (revisar nada más: el método `update()` ya usa `$request->user()->fill($request->validated())`, así que alcanza con que la regla de validación incluya el campo nuevo)

**Guía:**

1. Corran `php artisan make:migration add_notificar_por_mail_to_users_table` y agreguen la columna boolean con default `true` (que por defecto todos reciban mail, como pidió Mati).
2. Sumen la regla en `ProfileUpdateRequest::rules()`: `'notificar_por_mail' => ['sometimes', 'boolean']`.
3. En el nuevo form del perfil, un simple checkbox/switch que mande ese booleano via Inertia `useForm`, igual al patrón de `Register.tsx` que ya usa `InputLabel` + inputs controlados.
4. En KAN-76, antes de mandar el mail, consultar `$usuario->notificar_por_mail` y no enviar si es `false`.

</details>

<details>
<summary>**KAN-78** [backend] — Tests: feature test que verifique el envío del mail ante el evento correspondiente (Mail::fake)</summary>

**Archivos a crear/modificar:**

* `tests/Feature/AlertaMailTest.php` (crear)

**Guía:**

1. Usen `Mail::fake()` al principio del test (patrón estándar de Laravel para testear envío de mails sin mandarlos de verdad).
2. Disparen el evento correspondiente (ej. crear una Resena) y después hagan `Mail::assertSent(NuevaAlertaMail::class, function ($mail) { return $mail->hasTo($usuario->email); });`.
3. Agreguen también un caso donde el usuario tiene `notificar_por_mail = false` (de KAN-77) y verifiquen con `Mail::assertNotSent(...)` que no se envía nada.
4. Depende de KAN-76 (el Mailable tiene que existir) y de KAN-77 si quieren cubrir el caso de la preferencia desactivada.

</details>

### KAN-27 — Ver historial de notificaciones en la campanita

> Como usuario, quiero ver el historial de mis notificaciones en la campanita, para repasar alertas que me perdí.
>
> Criterios de aceptación:
>
> * Al abrir la campanita se despliega un listado de las últimas alertas, ordenadas de más reciente a más antigua.
> * Al ver una alerta, se marca como leída (`leida = true`).
> * Marcar todas como leídas de una vez queda como acción opcional, a confirmar si entra en esta historia o se deja para después.

<details>
<summary>**KAN-79** [backend] — Backend: endpoint para listar el historial de alertas del usuario y marcarlas como leídas</summary>

**Archivos a crear/modificar:**

* `app/Http/Controllers/AlertaController.php` (crear)
* `routes/web.php` (modificar: hoy solo tiene las rutas de Welcome/Dashboard/Profile; agregar algo como `Route::get('/alertas', [AlertaController::class, 'index'])` y `Route::patch('/alertas/{alerta}/leida', [AlertaController::class, 'marcarLeida'])`, protegidas con el middleware `auth` igual que Dashboard/Profile)
* `app/Policies/AlertaPolicy.php` (crear, opcional pero recomendado: para que un usuario no pueda marcar como leída una alerta de otro. Laravel la detecta automáticamente por convención de nombre, como ya pasa con `PublicacionPolicy` y `ResenaPolicy`, no hace falta registrarla a mano)

**Guía:**

1. `index()`: devuelve (via Inertia o JSON, según decidan con KAN-80) las alertas del usuario autenticado (`$request->user()->alertas`), ordenadas por más recientes primero.
2. `marcarLeida(Alerta $alerta)`: verifica con `$this->authorize('update', $alerta)` que la alerta sea del usuario logueado, y actualiza `leida = true`.
3. Si quieren el contador de KAN-74 ya resuelto de forma prolija, pueden sumar un método extra `noLeidasCount()` que devuelva solo el número, o compartirlo como prop global de Inertia.
4. Depende de KAN-73 (tiene que haber Alertas reales en la base para listar algo).

</details>

<details>
<summary>**KAN-80** [frontend] — Frontend: panel desplegable de la campanita con el historial de notificaciones</summary>

**Archivos a crear/modificar:**

* `resources/js/Components/Campanita.tsx` (modificar: el que se creó en KAN-74 solo tenía el ícono + contador, acá se le agrega el panel desplegable)
* Pueden reusar `resources/js/Components/Dropdown.tsx` (ya existe en el proyecto, es el mismo que usa el menu de cuenta en el navbar) en vez de armar un dropdown nuevo desde cero

**Guía:**

1. Al hacer click en la campanita de KAN-74, abran un panel (usando el `Dropdown` existente como base) que liste las alertas devueltas por el endpoint de KAN-79 (`GET /alertas`).
2. Cada item: mensaje de la alerta + fecha, con estilo distinto si `leida` es `false` (ej. fondo más claro o un punto de color).
3. Al abrir el panel (o al hacer click en un item puntual, según decidan en equipo), llamen a `PATCH /alertas/{id}/leida` de KAN-79 para marcarla como leída y que el contador de la campanita baje.
4. Depende de KAN-74 (el ícono base) y KAN-79 (el endpoint que trae los datos).

</details>

<details>
<summary>**KAN-81** [backend] — Tests: feature test del listado e historial de notificaciones</summary>

**Archivos a crear/modificar:**

* `tests/Feature/AlertaControllerTest.php` (crear)

**Guía:**

1. Mismo estilo Pest que el resto del proyecto (ver `tests/Feature/Policies/PublicacionPolicyTest.php`).
2. Casos mínimos:

    * Un usuario autenticado puede ver (via `GET /alertas`) solo sus propias alertas, no las de otro usuario.
    * Un usuario puede marcar como leída (via `PATCH /alertas/{id}/leida`) una alerta propia, y después el registro en la base tiene `leida = true`.
    * Un usuario NO puede marcar como leída una alerta que no es suya (debería devolver 403, probando que la `AlertaPolicy` de KAN-79 funciona).
    
3. Depende de KAN-79 (el controller y las rutas tienen que existir para poder testearlos).

</details>

---

_Generado automáticamente a partir de Jira — 6 Epics, 18 Historias, 54 Subtasks._
