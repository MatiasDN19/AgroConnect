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

<details>
<summary>**KAN-85** [frontend] — Frontend (Flutter): selector de rol (productor/comprador) en la pantalla de registro</summary>

Archivos a crear/modificar:

* `lib/screens/auth/register_screen.dart` (crear, si no existe todavía la carpeta `lib/screens/auth/`, crearla).
* `lib/services/api_service.dart` (crear si no existe: cliente HTTP base que agrega el token de Sanctum a los headers).

Guía:

1. Depende de que la Epic "API REST (Sanctum)" ya tenga expuesto el endpoint de registro (`POST /api/register`) devolviendo el token.
2. Armá el formulario de registro con los mismos campos que la versión web (`name`, `email`, `password`) y agregá un selector (radio buttons o `DropdownButton`) para elegir entre "Productor" y "Comprador", igual que en `Register.tsx`.
3. Mandá el `rol` elegido en el body del request al endpoint de registro.
4. Al recibir el token en la respuesta, guardalo (ej. con `flutter_secure_storage`) para las siguientes llamadas autenticadas.

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

<details>
<summary>**KAN-88** [frontend] — Frontend (Flutter): campo de teléfono editable en la pantalla de perfil</summary>

Archivos a crear/modificar:

* `lib/screens/profile/profile_edit_screen.dart` (crear, si no existe `lib/screens/profile/`, crearla).

Guía:

1. Depende de que la Epic "API REST (Sanctum)" tenga el endpoint de actualización de perfil (`PATCH /api/profile` o similar) devolviendo/aceptando el campo `telefono`.
2. Agregá un campo de texto editable para `telefono` en la pantalla de perfil, igual que el de `UpdateProfileInformationForm.tsx` en la web.
3. Es opcional (puede ir vacío), igual que en la versión web.

</details>

### KAN-230 — Ingresar y registrarme en pantallas claras y con la identidad de AgroConnect

> Como productor o comprador, quiero que las pantallas de ingreso y registro sean claras, estén en castellano y tengan la identidad visual de AgroConnect, para entender rápido qué tengo que completar y confiar en la plataforma desde el primer contacto.
>
> Criterios de aceptación:
>
> * Login, registro, "olvidé mi contraseña" y "restablecer contraseña" comparten el mismo diseño, con el logo, los colores y las tipografías de la marca (los mismos de la landing).
> * Todos los textos y mensajes de error están en castellano.
> * Se ven bien en celular (una columna, sin scroll horizontal) y en escritorio.
> * Son accesibles: cada campo tiene su etiqueta, los errores se asocian al campo y se puede completar todo con el teclado.
> * El registro mantiene el selector de rol (productor/comprador) y explica en una línea qué hace cada uno.

<details>
<summary>**KAN-231** [frontend] — Frontend Web: rediseño de login y registro con la identidad de AgroConnect</summary>

Archivos a crear/modificar:

* `resources/js/Layouts/GuestLayout.tsx` — layout compartido por todas las pantallas de ingreso.
* `resources/js/Pages/Auth/Login.tsx`
* `resources/js/Pages/Auth/Register.tsx`
* `resources/js/Pages/Auth/ForgotPassword.tsx` y `resources/js/Pages/Auth/ResetPassword.tsx` (alinear estilo y textos).

Guía:

1. Rediseñar `GuestLayout` con la identidad de la marca (logo, colores `marca-*` de Tailwind, fuentes Manrope / Bricolage Grotesque) para que todas las pantallas de ingreso queden iguales.
2. Traducir al castellano los textos que quedaron en inglés de Breeze ("Log in", "Remember me", "Forgot your password?", etc.).
3. En el registro, mantener el selector de rol y sumar una línea que explique qué puede hacer un productor y qué puede hacer un comprador.
4. Links claros entre login y registro, y para volver a la landing.
5. Verificar responsive y accesibilidad (etiquetas, errores asociados al campo, navegación con teclado).

Relacionada: KAN-210 (Backend: eliminar /dashboard y redirigir al marketplace después de login/registro). No depende de ella, pero juntas completan el flujo de entrada.

</details>

<details>
<summary>**KAN-232** [frontend] — Frontend (Flutter): pantallas de login y registro con la identidad de AgroConnect</summary>

Archivos a crear/modificar:

* `lib/theme/app_theme.dart` (crear si no existe: colores, tipografías y logo de la identidad de AgroConnect, para que los use toda la app, no solo estas pantallas).
* `lib/screens/auth/login_screen.dart` (crear, si no existe todavía la carpeta `lib/screens/auth/`, crearla).
* `lib/screens/auth/register_screen.dart` (compartida con KAN-85, que agrega ahí el selector de rol — coordinar para no pisarse).
* `lib/screens/auth/forgot_password_screen.dart` y `lib/screens/auth/reset_password_screen.dart` (recuperación de contraseña).

Guía:

1. Depende de que la Epic "API REST (Sanctum)" (KAN-82) tenga expuestos los endpoints de login, registro y recuperación de contraseña.
2. Definir en `app_theme.dart` los mismos colores, logo y tipografías que la versión web (ver KAN-231 y la landing, KAN-201), para que ambas implementaciones compartan la misma identidad visual aunque el código esté separado.
3. Todos los textos y mensajes de error en castellano.
4. Mostrar los errores de validación que devuelve el backend debajo de cada campo correspondiente.
5. Links claros entre login y registro, y accesibilidad básica (labels en los campos, tamaño de toque adecuado en los botones).

Relacionada: KAN-231 (la misma Historia, versión Web) y KAN-85 (selector de rol en el registro de Flutter). No depende de ellas para empezar el armado visual, pero conviene coordinarlas porque tocan los mismos archivos.

</details>

### KAN-235 — Ver los mensajes de error y avisos del sistema en castellano

> Como usuario, quiero que los mensajes de error y avisos que genera el sistema (login incorrecto, campos obligatorios, recuperación de contraseña) estén en castellano, para entender qué pasó sin saber inglés.
>
> Criterios de aceptación:
>
> * El login incorrecto, las validaciones de los formularios y los correos y avisos de recuperación de contraseña se muestran en castellano rioplatense.
> * Los mensajes personalizados que ya están en castellano en los FormRequest no cambian.
> * Los tests existentes siguen pasando.

<details>
<summary>**KAN-236** [backend] — Backend: configurar el idioma español y agregar las traducciones de Laravel</summary>

Archivos a crear/modificar:

* `.env.example` y `config/app.php` — `APP_LOCALE=es` (y `APP_FALLBACK_LOCALE=en`).
* `lang/es/auth.php`, `lang/es/passwords.php`, `lang/es/validation.php`, `lang/es/pagination.php` (crear; se pueden publicar con `php artisan lang:publish` y traducir, o tomar una traducción comunitaria revisándola).
* `lang/es.json` (textos sueltos de Breeze, por ejemplo los del correo de recuperación de contraseña).

Guía:

1. Publicar los archivos de idioma y traducirlos al castellano rioplatense (vos/tenés).
2. En `validation.php`, completar `attributes` con los nombres de los campos en castellano (email → correo electrónico, password → contraseña, name → nombre, etc.).
3. Revisar los tests que comparen mensajes en inglés de Laravel y actualizarlos si hace falta.
4. Cada integrante agrega `APP_LOCALE=es` a su `.env` local.

</details>

<details>
<summary>**KAN-237** [backend] — Tests: verificar que los mensajes de autenticación y validación salen en castellano</summary>

Archivos a crear/modificar:

* `tests/Feature/Auth/AuthenticationTest.php` y `tests/Feature/Auth/RegistrationTest.php` (agregar casos).

Guía:

1. Login con contraseña incorrecta → el error de `email` está en castellano.
2. Registro con campos vacíos → los errores de validación están en castellano.

Relacionada: KAN-231 (rediseño del login y registro web, ya en castellano del lado del frontend).

</details>

### KAN-238 — Ingresar o registrarme con mi cuenta de Google, Facebook o Apple

> Como usuario, quiero ingresar o registrarme con mi cuenta de Google, Facebook o Apple, para no tener que crear y recordar otra contraseña.
>
> Criterios de aceptación:
>
> * Los botones de Google, Facebook y Apple de las pantallas de ingreso (hoy deshabilitados como "próximamente", ver KAN-231) funcionan.
> * Si es la primera vez, se crea la cuenta y se le pide al usuario elegir su rol (productor o comprador), igual que en el registro común.
> * Si ya existe un usuario con ese correo, se vincula la red social a esa cuenta en lugar de duplicarla.
> * Los usuarios inhabilitados no pueden ingresar por esta vía.

<details>
<summary>**KAN-239** [backend] — Backend: ingreso con redes sociales usando Laravel Socialite</summary>

Archivos a crear/modificar:

* `composer.json` — agregar `laravel/socialite` (consultarlo con el equipo antes de instalar).
* Migración nueva para guardar el proveedor y el id externo del usuario (por ejemplo, tabla `cuentas_sociales` con `usuario_id`, `proveedor`, `proveedor_id`).
* `app/Http/Controllers/Auth/SocialController.php` (nuevo): `redirigir($proveedor)` y `callback($proveedor)`.
* `routes/auth.php` — rutas de redirección y callback.
* `config/services.php` y `.env.example` — credenciales de cada proveedor.

Guía:

1. Empezar por Google (el más simple de configurar); Facebook y Apple después, cada uno necesita su cuenta de desarrollador.
2. Si el correo ya existe, vincular; si no, crear el usuario y redirigir a elegir el rol.
3. Respetar el bloqueo de usuarios inhabilitados.

</details>

<details>
<summary>**KAN-240** [frontend] — Frontend Web: conectar los botones de redes sociales y elegir el rol después del primer ingreso</summary>

Archivos a crear/modificar:

* `resources/js/Components/Auth/RedesProximamente.tsx` — pasar de botones deshabilitados a links a las rutas de redirección (y renombrar el componente).
* `resources/js/Pages/Auth/ElegirRol.tsx` (nuevo) — elegir productor o comprador después del primer ingreso con una red social, reusando las tarjetas de rol del registro.

Guía:

1. Mostrar habilitados solo los proveedores que ya estén configurados en el backend.
2. Mostrar un error claro si el proveedor rechaza el ingreso o el usuario cancela.

</details>

<details>
<summary>**KAN-241** [backend] — Tests: ingreso con redes sociales (creación, vinculación y usuario inhabilitado)</summary>

Archivos a crear/modificar:

* `tests/Feature/Auth/SocialLoginTest.php` (nuevo), mockeando Socialite.

Guía:

1. Primer ingreso crea el usuario y pide el rol.
2. Correo existente vincula la cuenta sin duplicarla.
3. Usuario inhabilitado no puede ingresar.

Relacionada: KAN-231 (botones visibles como "próximamente").

</details>

### KAN-242 — Leer los términos y condiciones y la política de privacidad

> Como usuario, quiero poder leer los términos y condiciones y cómo se usan mis datos, para saber a qué me comprometo al registrarme (Ley 25.326 de Protección de Datos Personales).
>
> Criterios de aceptación:
>
> * Hay una página pública con los términos y condiciones y la política de privacidad, accesible desde el login, el registro y el pie de la landing.
> * El texto está revisado por el equipo y aprobado por el Consorcio antes de publicarse.
> * En el registro queda claro que al crear la cuenta se aceptan los términos (con un link a la página).

<details>
<summary>**KAN-243** [backend] — Redactar los términos y condiciones y la política de privacidad</summary>

Archivos a crear/modificar:

* `docs/terminos-y-condiciones.md` (borrador del texto).

Guía:

1. Cubrir: qué es AgroConnect, compra directa sin pasarela de pago (el acuerdo se cierra entre las partes), responsabilidades de productores y compradores, denuncias y moderación, datos que se guardan y para qué (Ley 25.326), y cómo pedir la baja de la cuenta.
2. Hacerlo revisar por el equipo y aprobar por el Consorcio.

</details>

<details>
<summary>**KAN-244** [frontend] — Frontend Web: página de términos y condiciones y links desde login, registro y landing</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Terminos.tsx` (nuevo) y su ruta pública en `routes/web.php`.
* `resources/js/Layouts/GuestLayout.tsx` — reemplazar "Términos y condiciones · Próximamente" por el link (ver KAN-231).
* `resources/js/Pages/Auth/Register.tsx` — texto "Al crear tu cuenta aceptás los términos y condiciones" con link.
* `resources/js/Components/Landing/Pie.tsx` — link en el pie.

Guía:

1. Depende de la Subtarea "Redactar los términos y condiciones y la política de privacidad" (el texto aprobado).
2. Misma identidad visual que la landing, legible en celular.

Relacionada: KAN-231.

</details>

---

### KAN-256 — Ver páginas de error claras y en castellano

> Como usuario, quiero que si una página no existe, no tengo permiso o algo falla, vea una pantalla clara en castellano y con el diseño de AgroConnect, para entender qué pasó y poder volver al marketplace.
>
> Criterios de aceptación:
>
> * Los errores 403, 404, 419 (sesión vencida), 500 y 503 muestran una página propia con un título, una explicación corta y un botón para volver.
> * La página usa la identidad visual de AgroConnect y está en castellano.
> * En desarrollo (`APP_DEBUG=true`) se sigue viendo la pantalla de error de Laravel para poder depurar.
> * Las respuestas JSON de la API no cambian.

<details>
<summary>**KAN-266** [backend] — Backend: mostrar una página Inertia en los errores 403, 404, 419, 500 y 503</summary>

Archivos a crear/modificar:

* `bootstrap/app.php` — en `withExceptions()`, agregar un `$exceptions->respond(...)`.
* `tests/Feature/PaginasErrorTest.php` (nuevo).

Guía:

1. Patrón de la documentación de Inertia ("Error handling"): si la respuesta tiene estado 403, 404, 500 o 503 y **no** estamos en `local`/`testing` con debug, devolver `Inertia::render('Error', ['status' => $estado])->toResponse($request)->setStatusCode($estado)`.
2. Para el 419 (sesión vencida, típico al dejar un formulario abierto mucho tiempo), volver atrás con un mensaje flash: `back()->with('error', 'La página venció. Probá de nuevo.')`.
3. No tocar lo que ya hace `shouldRenderJsonWhen` para la API (`api/*` o `expectsJson()` siguen devolviendo JSON).
4. Tests: con debug apagado (`config(['app.debug' => false])`), una URL inexistente responde 404 con el componente `Error` y `status` 404; una ruta de admin pedida por un comprador responde 403 con el mismo componente; una llamada a la API sigue devolviendo JSON.

Relacionada: tarea de frontend hermana de esta historia (`Pages/Error.tsx`).

</details>

<details>
<summary>**KAN-267** [frontend] — Frontend Web: página de error con la identidad de AgroConnect</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Error.tsx` (nuevo).

Guía:

1. Recibe `status` (403, 404, 500 o 503) y muestra un título y una explicación corta en castellano según el caso. Ej.: 404 "No encontramos esta página"; 403 "No tenés permiso para ver esto"; 500 "Algo salió mal de nuestro lado"; 503 "Estamos haciendo mantenimiento".
2. Botón principal "Volver al marketplace" (o "Ir al inicio" si no hay sesión) y uno secundario "Volver atrás".
3. Identidad visual de AgroConnect: logo, degradé azul y CTA ámbar (como la landing y el Inicio, KAN-201/211). No depende de que haya usuario logueado: puede pasar con o sin sesión.
4. Probarla en local poniendo `APP_DEBUG=false` un momento.

Depende de: la tarea de backend de esta historia.

</details>

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

<details>
<summary>**KAN-91** [frontend] — Frontend (Flutter): mostrar categorías en el navegador/filtro de publicaciones</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicaciones_list_screen.dart` (se crea en la Historia "Ver listado de publicaciones activas") o un widget compartido `lib/widgets/categoria_filtro.dart`.

Guía:

1. Depende de que la Epic "API REST (Sanctum)" tenga el endpoint de listado de categorías (`GET /api/categorias`).
2. Mostrá las categorías obtenidas de la API (ej. como chips horizontales o un `DropdownButton`) en la pantalla de listado de publicaciones.
3. Cada categoría es seleccionable y dispara el filtro — coordiná con la subtask de Flutter de "Buscar/filtrar por categoría" (KAN-18) para no duplicar trabajo.

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

<details>
<summary>**KAN-97** [frontend] — Frontend (Flutter): pantalla para crear una publicación</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_create_screen.dart` (crear, si no existe `lib/screens/publicaciones/`, crearla).
* `lib/models/publicacion.dart` (crear si no existe: modelo con los campos de `StorePublicacionRequest`).

Guía:

1. Depende de que la Epic "API REST (Sanctum)" tenga el endpoint `POST /api/publicaciones`.
2. Armá el formulario con los mismos campos que `StorePublicacionRequest`: categoria_id (selector, trayendo las categorías de la API), titulo, descripcion, precio, unidad, stock.
3. Solo mostrá esta pantalla/acción si el usuario logueado es productor (`esProductor()` en el backend ya lo valida, pero conviene ocultar el botón en la UI también).
4. Validá que precio y stock no sean negativos antes de mandar el request (la API también lo valida, pero da mejor feedback hacerlo en el form).

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

<details>
<summary>**KAN-102** [frontend] — Frontend (Flutter): pantalla para editar una publicación existente</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_edit_screen.dart` (crear).

Guía:

1. Depende de que la Epic "API REST (Sanctum)" tenga el endpoint `PATCH /api/publicaciones/{id}`.
2. Similar a la pantalla de creación (KAN-14 Flutter), pero precargando los datos actuales de la publicación.
3. Considerá extraer un widget de formulario compartido entre la pantalla de creación y la de edición, en vez de duplicar todo.
4. Solo debería poder llegar a esta pantalla el dueño de la publicación (o un admin) — ocultá el botón de editar para el resto, aunque la policy del backend ya lo bloquea.

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

<details>
<summary>**KAN-107** [frontend] — Frontend (Flutter): acciones para cambiar el estado de la publicación</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` (se crea en la Historia "Ver el detalle de una publicación", KAN-19 Flutter).

Guía:

1. Depende del endpoint de cambio de estado expuesto en la Epic "API REST (Sanctum)".
2. Agregá botones "Pausar" / "Marcar como vendida" / "Reactivar", visibles solo para el dueño de la publicación (o admin).
3. Cada botón dispara un PATCH al endpoint de cambio de estado y refresca la pantalla con el nuevo estado.

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

<details>
<summary>**KAN-113** [frontend] — Frontend (Flutter): pantalla de listado de publicaciones activas</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicaciones_list_screen.dart` (crear).
* `lib/models/publicacion.dart`, `lib/services/publicaciones_service.dart` (crear si no existen).

Guía:

1. Depende del endpoint `GET /api/publicaciones` expuesto en la Epic "API REST (Sanctum)".
2. Listá las publicaciones activas devueltas por la API: título, precio, categoría, puntuación promedio.
3. Cada item lleva a la pantalla de detalle (Historia "Ver el detalle de una publicación", KAN-19 Flutter).
4. Esta es la pantalla de "Inicio" que menciona el plan de trabajo para la navegación principal de la app.

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

<details>
<summary>**KAN-115** [frontend] — Frontend (Flutter): selector/buscador de categoría en el listado</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicaciones_list_screen.dart` (mismo archivo de KAN-17 Flutter).

Guía:

1. Depende del parámetro de filtro por categoría en el endpoint `GET /api/publicaciones?categoria_id=...` de la Epic "API REST (Sanctum)".
2. Agregá un selector de categoría (reusando el de KAN-13 Flutter) que al cambiar dispare una nueva consulta a la API con el filtro aplicado.
3. Si no hay resultados para esa categoría, mostrá un estado vacío claro (no un error).

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

<details>
<summary>**KAN-118** [frontend] — Frontend (Flutter): pantalla de detalle de una publicación</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` (crear).

Guía:

1. Depende del endpoint `GET /api/publicaciones/{id}` de la Epic "API REST (Sanctum)".
2. Mostrá todos los datos de la publicación (título, descripción, precio, unidad, stock, categoría) + datos de contacto del productor (nombre, teléfono si tiene).
3. Dejá un espacio para el listado de reseñas (Epic Reseñas, KAN-21 Flutter) y los botones de cambio de estado si corresponde (KAN-16 Flutter).
4. Esta pantalla corresponde a "Detalle" en el maquetado de pantallas principales que menciona el plan de trabajo.

</details>

### KAN-180 — Bloquear automáticamente una publicación sin stock disponible

> Como productor, quiero que mi publicación se pause automáticamente cuando se agota el stock, para no seguir recibiendo contactos por un producto que ya no tengo disponible.
>
> Criterios de aceptación:
>
> * Cuando el `stock` de una publicación llega a 0, su `estado` pasa automáticamente a `pausada` (si no estaba ya `vendida`).
> * Si se repone stock (`stock` vuelve a ser mayor a 0) sobre una publicación que estaba `pausada` por falta de stock, su `estado` vuelve a `activa` automáticamente.
> * Esto es automático: no requiere que el productor haga una acción manual de pausar/reactivar (eso ya existe, KAN-16/KAN-46/KAN-47, y sigue disponible para pausar manualmente por otros motivos).
> * No afecta a una publicación que el productor marcó como `vendida` a mano.

<details>
<summary>**KAN-186** [backend] — Backend: Observer que pausa/reactiva la publicación automáticamente según el stock</summary>

Archivos a crear/modificar:

* `app/Observers/PublicacionObserver.php` (nuevo) — método `saving()` o `updating()`.
* `app/Providers/AppServiceProvider.php` — registrar el observer (`Publicacion::observe(PublicacionObserver::class)`).

Guía:

1. En `saving()`, si `$publicacion->isDirty('stock')`: si el nuevo `stock` es 0 y el `estado` actual no es `vendida`, setear `estado = Publicacion::ESTADO_PAUSADA`. Si el nuevo `stock` es mayor a 0 y el `estado` actual es `pausada`, setear `estado = Publicacion::ESTADO_ACTIVA`.
2. Ojo con no pisar un `pausada`/`vendida` que el productor eligió a mano por otro motivo cuando el stock ya era 0 antes del cambio — guiate por el criterio de aceptación de la Historia (KAN-180) para los casos borde, y dejá un comentario en el código explicando la decisión que tomes.
3. Revisar que esto no rompa el flujo de creación de publicación (KAN-40): al crear, `isDirty('stock')` es true pero no hay transición "previa" que reactivar, usar tu criterio.

</details>

<details>
<summary>**KAN-195** [frontend] — Frontend Web: reflejar el estado automático en la vista de la publicación</summary>

Archivos a crear/modificar:

* Revisar `resources/js/Pages/Publicaciones/Show.tsx` y el componente de acciones de KAN-47 — probablemente no haga falta tocar nada si ya muestran el `estado` actual de la publicación tal cual viene del backend.

Guía:

1. Confirmá con quien hizo KAN-47 que el badge/label de estado se recalcula solo (no queda cacheado en el front), ya que ahora el estado puede cambiar sin que el productor haga una acción manual.
2. Si hace falta, agregar un texto aclaratorio como "Pausada automáticamente por falta de stock" cuando corresponda.

</details>

<details>
<summary>**KAN-197** [backend] — Tests: feature test del cambio automático de estado por stock</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionStockAutomaticoTest.php` (nuevo).

Casos a cubrir:

* Una publicación `activa` con stock > 0 pasa a `pausada` automáticamente cuando su stock se actualiza a 0.
* Una publicación `pausada` por falta de stock vuelve a `activa` automáticamente cuando se le repone stock.
* Una publicación marcada `vendida` a mano no cambia de estado aunque su stock llegue a 0 o se le reponga stock.

</details>

<details>
<summary>**KAN-234** [frontend] — Frontend (Flutter): reflejar el estado automático en la vista de la publicación</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` (pantalla de detalle, ver KAN-118) — probablemente no haga falta tocar nada si ya muestra el `estado` tal cual viene del backend.

Guía:

1. Confirmá que el estado/badge de la publicación en esta pantalla se recalcula siempre desde la respuesta del backend (no queda cacheado en el estado local de la app).
2. Si hace falta, agregar un texto aclaratorio como "Pausada automáticamente por falta de stock" cuando corresponda, igual que en la versión Web (KAN-195).

Relacionada: KAN-195 (la misma Historia, versión Web).

</details>

### KAN-203 — Agregar fotos a mis publicaciones

> Como productor, quiero agregar fotos a mis publicaciones, para que los compradores vean el producto antes de contactarme.
>
> Criterios de aceptación:
>
> * Al crear o editar una publicación se pueden subir hasta 6 fotos (opcional, ninguna es obligatoria).
> * Formatos permitidos: jpg, png y webp; tamaño máximo por foto: 2 MB.
> * Las fotos tienen un orden; la primera es la portada que aparece en el listado.
> * Al editar se pueden agregar fotos nuevas y quitar existentes.
> * Solo el dueño de la publicación (o un admin) puede agregar o quitar fotos (`PublicacionPolicy::update`).
> * Al borrar una publicación o una foto, también se borra el archivo del disco.
> * Si una publicación no tiene fotos, se muestra un placeholder (ilustración por categoría).

<details>
<summary>**KAN-204** [backend] — Backend: migración, modelo y guardado de fotos de publicación</summary>

Archivos a crear/modificar:

* `database/migrations/[fecha]_create_publicacion_fotos_table.php` (nuevo): `id`, `publicacion_id` (FK `cascadeOnDelete`), `ruta` (string), `orden` (`unsignedTinyInteger`), timestamps.
* `app/Models/PublicacionFoto.php` (nuevo, con `protected $table = 'publicacion_fotos'`) y relación `fotos()` en `app/Models/Publicacion.php` (ordenada por `orden`).
* `app/Http/Requests/StorePublicacionRequest.php` y `UpdatePublicacionRequest.php`: reglas `'fotos' => ['nullable', 'array', 'max:6']`, `'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']`; en Update, además, `'fotos_eliminar' => ['nullable', 'array']` con `'fotos_eliminar.*' => ['integer', 'exists:publicacion_fotos,id']`, y validar que el total final no supere 6.
* `app/Http/Controllers/PublicacionController.php`: `store()` y `update()` guardan los archivos en el disco `public` (carpeta `publicaciones/{id}`) y crean las filas; `update()` borra las de `fotos_eliminar` (fila + archivo). `show()`, `edit()` e `index()` devuelven las fotos (`index` solo la portada) con su URL pública (`Storage::url`).
* Correr `php artisan storage:link` (documentarlo en el README).

Guía:

1. No editar migraciones ya corridas; todo en migraciones nuevas.
2. Guardar con `$archivo->store("publicaciones/{$publicacion->id}", 'public')`.
3. Borrar archivos del disco al eliminar una foto o la publicación (en el controller o con un evento `deleting` del modelo).
4. Ojo: con Inertia, un formulario con archivos que usa PUT no llega bien a Laravel; el frontend va a mandar POST con `_method=put`, así que la ruta de update tiene que aceptarlo (`Route::match` ya incluye put/patch; verificar que funcione con method spoofing).

</details>

<details>
<summary>**KAN-205** [frontend] — Frontend: subir, ordenar y ver fotos de la publicación</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Partials/PublicacionForm.tsx` — campo de fotos con vista previa.
* `resources/js/Pages/Publicaciones/Create.tsx` y `Edit.tsx` — mandar las fotos.
* `resources/js/Pages/Publicaciones/Show.tsx` — galería de fotos.
* `resources/js/Components/PublicacionCard.tsx` — portada o placeholder.
* `resources/js/types/index.d.ts` — tipo `PublicacionFoto`.

Guía:

1. Input `type="file" multiple accept="image/jpeg,image/png,image/webp"` con vista previa (`URL.createObjectURL`, y revocarla al quitar la foto); permitir quitar antes de enviar y mostrar el límite de 6.
2. `useForm` de Inertia manda `multipart/form-data` automáticamente cuando hay archivos; en Edit usar `post(route('publicaciones.update', id), { forceFormData: true })` agregando `_method: 'put'` a los datos (con PUT directo los archivos no llegan).
3. En Edit: mostrar las fotos existentes con un botón para quitarlas (se agregan a `fotos_eliminar`) y permitir sumar nuevas sin pasar el máximo.
4. Show: foto principal grande + miniaturas para cambiar; en `PublicacionCard`, la portada con `aspect-ratio` fijo y `object-cover`. Sin fotos, placeholder.
5. Mostrar los errores de validación de fotos (tamaño, formato, cantidad) debajo del campo.

</details>

<details>
<summary>**KAN-206** [frontend] — Frontend (Flutter): subir fotos al crear/editar una publicación</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_create_screen.dart` y `publicacion_edit_screen.dart`.
* `pubspec.yaml` — dependencia `image_picker`.

Guía:

1. Depende de que la API REST (Epic Sanctum) acepte fotos en `POST`/`PATCH` `/api/publicaciones` (multipart).
2. Elegir fotos con `image_picker` (galería o cámara), con vista previa y máximo 6.
3. Mandarlas como multipart con el cliente HTTP autenticado.

</details>

<details>
<summary>**KAN-207** [backend] — Tests: feature tests de fotos de publicación</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionFotoTest.php` (nuevo).

Guía:

1. Usar `Storage::fake('public')` y `UploadedFile::fake()->image('foto.jpg')`.
2. Casos: el dueño crea una publicación con fotos (filas en la base y archivos en el disco fake); se rechaza más de 6 fotos; se rechaza un archivo que no es imagen o que pesa más de 2 MB; al editar se pueden quitar fotos (se borra el archivo); otro productor no puede agregar ni quitar fotos de una publicación ajena (403); al borrar la publicación se borran sus archivos.

</details>

### KAN-208 — Pantalla de inicio del marketplace

> Como usuario de AgroConnect, quiero que al entrar a la aplicación vea directamente el marketplace con buscador, categorías y el catálogo paginado, para encontrar rápido lo que necesito (y, si soy productor, ver de un vistazo el estado de mis publicaciones y solicitudes).
>
> Criterios de aceptación:
>
> * Al iniciar sesión, registrarse o verificar el email, el usuario va a "/publicaciones" (el marketplace). La página `/dashboard` de Breeze se elimina.
> * Hay un buscador por texto que busca en el título y la descripción de las publicaciones activas.
> * Se muestran las categorías con la cantidad de publicaciones activas de cada una; al elegir una se filtra el catálogo.
> * El catálogo está paginado (12 publicaciones por página) y la paginación conserva la búsqueda y el filtro activos.
> * Si el usuario es productor, arriba del catálogo ve una franja con SU resumen: cantidad de publicaciones activas, pausadas y vendidas, y cantidad de solicitudes de compra pendientes, con links a cada sección.
> * Las tarjetas no muestran carrito, descuentos ni "comprar ahora" (no hay pagos: la compra es por solicitud directa o WhatsApp).
> * Responsive (mobile, tablet y escritorio).

<details>
<summary>**KAN-209** [backend] — Backend: búsqueda, paginación, categorías con conteo y resumen del productor en el listado</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método `index()`.

Guía:

1. Búsqueda: aceptar `?q=` y, cuando venga, filtrar con `where(fn ($w) => $w->where('publicaciones.titulo', 'like', "%{$q}%")->orWhere('publicaciones.descripcion', 'like', "%{$q}%"))` (agrupado entre paréntesis para no romper el filtro de estado/categoría).
2. Paginación: reemplazar `->get()` por `->paginate(12)->withQueryString()`, para que los links de página conserven `q`, `categoria_id`, `lat` y `lng`.
3. Categorías: mandar `'categorias' => Categoria::withCount(['publicaciones' => fn ($q) => $q->where('estado', Publicacion::ESTADO_ACTIVA)])->orderBy('nombre')->get(['id', 'nombre'])`.
4. Mandar también `'filtros' => $request->only(['q', 'categoria_id'])` para que el frontend sepa qué está activo.
5. Resumen del productor: si `$request->user()->esProductor()`, mandar `'resumen' => ['activas' => ..., 'pausadas' => ..., 'vendidas' => ..., 'solicitudes_pendientes' => ...]` (contando SUS publicaciones por estado y las solicitudes de compra pendientes sobre sus publicaciones). Para el resto, `'resumen' => null`.
6. Ojo: al paginar, la prop `publicaciones` pasa a ser un objeto `{ data, links, meta... }` en vez de un array; avisar a frontend.

</details>

<details>
<summary>**KAN-210** [backend] — Backend: eliminar /dashboard y redirigir al marketplace después de login/registro</summary>

Archivos a crear/modificar:

* `routes/web.php` — eliminar la ruta `/dashboard`.
* `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `ConfirmablePasswordController.php`, `EmailVerificationNotificationController.php`, `EmailVerificationPromptController.php`, `RegisteredUserController.php`, `VerifyEmailController.php` — reemplazar `route('dashboard', absolute: false)` por `route('publicaciones.index', absolute: false)` (en `VerifyEmailController` conservar el `?verified=1`).
* `tests/Feature/Auth/AuthenticationTest.php`, `EmailVerificationTest.php`, `RegistrationTest.php` — actualizar los `assertRedirect` que apuntan a `'dashboard'`.

Guía:

1. Buscar con grep todos los usos de `'dashboard'` en `app/`, `routes/`, `tests/` y `resources/js` para no dejar ninguno roto (Ziggy tira error en el frontend si se llama a `route('dashboard')` y la ruta no existe).
2. Coordinar con frontend: `AuthenticatedLayout.tsx` y `Welcome.tsx` también usan `route('dashboard')`; se actualizan en la subtask de frontend, en el mismo PR o justo después.

</details>

<details>
<summary>**KAN-211** [frontend] — Frontend: rediseño de la pantalla de inicio del marketplace</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Index.tsx` — nueva estructura de la pantalla.
* `resources/js/Layouts/AuthenticatedLayout.tsx` — header nuevo (logo, buscador, favoritos, notificaciones, + Publicar para productores, avatar); quitar el link "Dashboard".
* `resources/js/Pages/Dashboard.tsx` — eliminar.
* `resources/js/Components/` — componentes nuevos que hagan falta (ej. `BuscadorPublicaciones`, `CategoriasDestacadas`, `ResumenProductor`, `Paginacion`, `BannerBienvenida`).
* `resources/js/types/index.d.ts` — tipos de la respuesta paginada y del resumen.

Guía:

1. Estructura, de arriba hacia abajo: banner de bienvenida (azul en degradé, según el rol) → franja de resumen (solo productores) → categorías en círculos con su cantidad (al tocar una se filtra; la elegida queda marcada) → espacio para "Sugerido para vos" (KAN-170, no implementarlo acá) → dos banners informativos (ej. "Socios del Consorcio" y "Publicá tu producción") → catálogo en grilla con paginación.
2. Buscador: `router.get(route('publicaciones.index'), { ...filtros, q }, { preserveState: true })`, con un debounce corto o al apretar Enter.
3. Adaptar a la respuesta paginada: `publicaciones.data` para la grilla y `publicaciones.links` para la paginación.
4. Reusar `Components/PublicacionCard.tsx`; los agregados de foto, favorito e insignia de socio van en sus propias tareas (KAN-203, KAN-188, KAN-173).
5. Paleta y tipografía del proyecto: azules, ámbar en los CTA; títulos en Bricolage Grotesque y texto en Manrope (definidos en la tarea de la landing, KAN-201).
6. Coordinar con KAN-53: el filtro por categoría se conecta acá mismo (si KAN-53 sigue abierta, resolverla en conjunto).

</details>

<details>
<summary>**KAN-212** [backend] — Tests: feature tests de la pantalla de inicio</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php` (agregar casos).

Guía:

1. La búsqueda `?q=` devuelve solo publicaciones activas cuyo título o descripción coincide, y se combina bien con `?categoria_id=`.
2. La paginación devuelve 12 por página y los links conservan los filtros.
3. `'categorias'` trae el conteo de publicaciones ACTIVAS (una pausada no suma).
4. `'resumen'` trae los conteos correctos para un productor (sus publicaciones por estado y solicitudes pendientes, sin contar las de otros) y es `null` para un comprador.
5. Después del login el usuario es redirigido a `publicaciones.index`, y `/dashboard` devuelve 404.

</details>

<details>
<summary>**KAN-233** [frontend] — Frontend (Flutter): pantalla de inicio del marketplace</summary>

Archivos a crear/modificar:

* `lib/screens/marketplace/home_screen.dart` (crear, pantalla principal del marketplace).
* `lib/widgets/publicacion_card.dart` (reusar si ya existe del listado/detalle; crear si no).
* `lib/widgets/categoria_chip.dart` (nuevo) — categorías destacadas, al tocar una se filtra el listado.
* `lib/models/publicacion.dart` y `lib/models/categoria.dart` (si no existen todavía, para tipar la respuesta paginada).
* `lib/services/publicacion_service.dart` (o el servicio que ya exista de la Epic API REST para consumir el listado).

Guía:

1. Depende de que la Epic "API REST (Sanctum)" (KAN-82) tenga expuesto el endpoint de listado paginado de publicaciones con búsqueda y filtro por categoría — el mismo que consume la Web (ver KAN-209, el backend, y KAN-211, la versión Web de esta misma pantalla).
2. Estructura equivalente a la versión Web (KAN-211): banner de bienvenida según el rol, resumen para productores, categorías destacadas, buscador arriba, catálogo con scroll infinito o paginación (lo que sea más natural en Flutter).
3. Reusar la identidad visual definida en `lib/theme/app_theme.dart` (ver KAN-232).
4. Los agregados de foto, favorito e insignia de socio van en sus propias tareas Flutter (KAN-206, y las de favoritos/insignia si ya existen — confirmar).
5. Pull-to-refresh para actualizar el listado.

Relacionada: KAN-211 (la misma pantalla, versión Web).

</details>

<details>
<summary>**KAN-269** [backend] — Backend: seeder de datos de demostración para la presentación</summary>

Archivos a crear/modificar:

* `database/seeders/DemoSeeder.php` (nuevo).
* `README.md` — cómo cargarlo (`php artisan db:seed --class=DemoSeeder`).

Guía:

1. Separado del `DatabaseSeeder` (que sigue creando solo los usuarios de prueba), para poder cargarlo cuando haga falta mostrar la app.
2. Unos 6 productores de la zona de Laguna Naineck (algunos socios validados, otros no, con teléfono, localidad y ubicación cerca de Laguna Naineck), 3 o 4 compradores y unas 30 publicaciones creíbles repartidas en todas las categorías (precios y unidades realistas: kg, docena, litro, unidad), con algunas pausadas y vendidas.
3. Reseñas, favoritos y algunas solicitudes de compra (pendientes, aceptadas y rechazadas) para que todas las pantallas tengan algo que mostrar.
4. Fotos: opcionales. Si se agregan, que sean propias o con licencia libre, guardadas en `storage/app/public` como las demás.
5. Que se pueda correr dos veces sin duplicar (usar `firstOrCreate` por email o limpiar antes).

Relacionada: la presentación del TFI. Reemplaza los datos "[prueba KAN-…]" que se fueron creando a mano en las bases locales.

</details>

---

### KAN-253 — Gestionar mis publicaciones

> Como productor, quiero ver en un solo lugar todas mis publicaciones (activas, pausadas, vendidas y bloqueadas) y gestionarlas desde ahí, para saber cómo van y reactivar o editar las que no están a la vista en el marketplace.
>
> Criterios de aceptación:
>
> * Hay una página "Mis publicaciones", accesible desde el navbar y el menú de usuario, solo para productores.
> * Muestra todas mis publicaciones, de cualquier estado, con un filtro por estado y paginación.
> * Arriba veo un resumen: cantidad de activas, pausadas y vendidas, y solicitudes de compra pendientes.
> * Desde cada publicación puedo ir a editarla, pausarla, reactivarla o marcarla como vendida. Las bloqueadas muestran el motivo y no se pueden reactivar.
> * Nadie más puede ver las publicaciones pausadas, vendidas o bloqueadas de otro productor desde esta página.

<details>
<summary>**KAN-257** [backend] — Backend: ruta "Mis publicaciones" con todos los estados, filtro por estado y resumen</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método nuevo `misPublicaciones()` (o un controlador `MisPublicacionesController`).
* `routes/web.php` — `GET /mis-publicaciones` con nombre `publicaciones.mias`, solo para productores (`->can('create', Publicacion::class)` como la ruta de crear).

Guía:

1. Devolver `Inertia::render('Publicaciones/Mias', [...])` con las publicaciones del usuario logueado de **todos** los estados (activa, pausada, vendida, bloqueada), las más nuevas primero, `paginate(12)->withQueryString()`.
2. Filtro opcional `?estado=` (validar que sea uno de los estados conocidos; si no viene, todas).
3. Cargar lo mismo que el listado para la tarjeta: `categoria:id,nombre`, la foto de portada (`fotos` con `limit(1)`), el promedio de reseñas y, para las bloqueadas, `motivo_bloqueo`.
4. Mandar `resumen` con la cantidad de activas, pausadas, vendidas y bloqueadas, y las solicitudes de compra pendientes. Es el mismo cálculo que hoy hace `index()` (KAN-209): moverlo a un método reutilizable y **sacarlo de** `index()`, donde ya no se muestra (KAN-211).
5. Mandar `filtros` con el estado elegido.

Relacionada: KAN-211 (el Inicio ya no muestra el resumen), tarea de frontend hermana de esta historia.

</details>

<details>
<summary>**KAN-258** [backend] — Tests: feature tests de "Mis publicaciones"</summary>

Archivos a crear/modificar:

* `tests/Feature/MisPublicacionesTest.php` (nuevo).

Guía:

1. Un productor ve sus publicaciones de todos los estados, incluidas pausadas, vendidas y bloqueadas.
2. No ve las publicaciones de otros productores.
3. `?estado=pausada` devuelve solo las pausadas; un estado inválido da error de validación o se ignora (según cómo se implemente).
4. El `resumen` tiene los conteos correctos (activas, pausadas, vendidas, bloqueadas y solicitudes pendientes).
5. Un comprador recibe 403 y un invitado es redirigido al login.
6. La paginación devuelve 12 por página y conserva el filtro.
7. El listado del marketplace (`publicaciones.index`) ya no manda `resumen`.

</details>

<details>
<summary>**KAN-259** [frontend] — Frontend Web: página "Mis publicaciones" con resumen, pestañas por estado y acciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Mias.tsx` (nuevo).
* `resources/js/Layouts/AuthenticatedLayout.tsx` — link "Mis publicaciones" en el navbar y en el menú de usuario (solo productores).
* `resources/js/types/index.d.ts` — tipo del resumen.

Guía:

1. Arriba, el resumen con los números (activas, pausadas, vendidas, bloqueadas y solicitudes pendientes con link al panel de solicitudes).
2. Pestañas por estado ("Todas", "Activas", "Pausadas", "Vendidas", "Bloqueadas") que cambian `?estado=` conservando lo demás (`irAlListado` o un `router.get` parecido).
3. Cada publicación con su estado en un chip de color y acciones: Ver, Editar, Pausar/Reactivar, Marcar como vendida (usando la ruta `publicaciones.estado` que ya existe). Las bloqueadas muestran el motivo y no tienen acciones de estado.
4. Paginación con `Components/Paginacion.tsx` (KAN-211).
5. Estado vacío con link a "Nueva publicación".

Depende de: la tarea de backend de esta historia (ruta `publicaciones.mias`).

</details>

---

### KAN-254 — Ver el perfil público de un productor

> Como comprador, quiero ver el perfil de un productor desde sus publicaciones, para conocer quién es, en qué zona produce, cómo lo calificaron y qué más vende.
>
> Criterios de aceptación:
>
> * Desde la tarjeta y el detalle de una publicación puedo ir al perfil del productor.
> * El perfil muestra el nombre, la insignia de socio si corresponde, la localidad, el promedio de sus reseñas y sus publicaciones activas.
> * No muestra datos privados: ni email, ni teléfono, ni la ubicación exacta.
> * Si el usuario no es productor o está inhabilitado, la página responde 404.

<details>
<summary>**KAN-260** [backend] — Backend: ruta del perfil público de un productor</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/ProductorController.php` (nuevo) — método `show()`.
* `routes/web.php` — `GET /productores/{usuario}` con nombre `productores.show`.

Guía:

1. Si el usuario no es productor o está inhabilitado, `abort(404)`.
2. Devolver `Inertia::render('Productores/Show', [...])` con **solo datos públicos** del productor: `id`, `name`, `socio_validado`, `localidad` (si ya existe la columna de la tarea de ubicación de KAN-90) y la fecha desde que está en AgroConnect. Nada de email, teléfono, lat/lng ni número de socio.
3. Sus publicaciones **activas**, con lo mismo que manda el listado para la tarjeta (categoría, portada, promedio), paginadas de a 12.
4. El promedio general de las reseñas de todas sus publicaciones y la cantidad de reseñas.

Relacionada: tarea de frontend hermana de esta historia.

</details>

<details>
<summary>**KAN-261** [backend] — Tests: feature tests del perfil público de un productor</summary>

Archivos a crear/modificar:

* `tests/Feature/ProductorPerfilTest.php` (nuevo).

Guía:

1. Un usuario logueado ve el perfil de un productor con su nombre y sus publicaciones activas (no las pausadas, vendidas ni bloqueadas).
2. La respuesta **no** incluye `email`, `telefono`, `lat`, `lng` ni `numero_socio` (`->missing(...)`).
3. El promedio de reseñas es el correcto.
4. El perfil de un comprador o de un admin da 404; el de un productor inhabilitado también.
5. Un invitado es redirigido al login.

</details>

<details>
<summary>**KAN-262** [frontend] — Frontend Web: página del perfil público del productor y links desde las publicaciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Productores/Show.tsx` (nuevo).
* `resources/js/Components/PublicacionCard.tsx` — el nombre del productor lleva a su perfil (cuidado: la tarjeta entera ya es un link; usar un link aparte o mover el nombre fuera del link).
* `resources/js/Pages/Publicaciones/Show.tsx` — link "Ver perfil" en el recuadro del productor.

Guía:

1. Cabecera con avatar de iniciales, nombre, insignia de socio, localidad, "En AgroConnect desde …" y el promedio de reseñas con estrellas.
2. Grilla de sus publicaciones activas reusando `PublicacionCard` y la paginación de KAN-211.
3. Estado vacío si no tiene publicaciones activas.
4. Mismo estilo que el Inicio (KAN-211).

Depende de: la tarea de backend de esta historia (ruta `productores.show`).

</details>

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

<details>
<summary>**KAN-122** [frontend] — Frontend (Flutter): formulario para dejar una reseña</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` (mismo archivo del detalle, KAN-19 Flutter) o un widget aparte `lib/widgets/resena_form.dart`.

Guía:

1. Depende del endpoint `POST /api/publicaciones/{publicacion}/resenas` de la Epic "API REST (Sanctum)".
2. Agregá un formulario de reseña (puntuación 1-5 con un selector de estrellas + comentario opcional) en la pantalla de detalle.
3. Mostrá los mensajes de error que devuelve la API si el usuario ya reseñó esa publicación o intenta reseñar la propia.

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

<details>
<summary>**KAN-127** [frontend] — Frontend (Flutter): mostrar listado de reseñas en el detalle</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` (mismo archivo del detalle, KAN-19 Flutter).

Guía:

1. Depende del endpoint de listado de reseñas de una publicación en la Epic "API REST (Sanctum)".
2. Listá las reseñas (puntuación, comentario, nombre de quien la dejó) debajo de los datos de la publicación.
3. Mostrá la puntuación promedio arriba del listado.
4. Si no hay reseñas todavía, mostrá un estado vacío claro.

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

<details>
<summary>**KAN-133** [frontend] — Frontend (Flutter): formulario/botón para denunciar una publicación o usuario</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` (mismo archivo del detalle, KAN-19 Flutter) — agregar botón "Denunciar" que abre un modal/bottom sheet con el formulario.

Guía:

1. Depende del endpoint `POST /api/publicaciones/{publicacion}/denuncias` de la Epic "API REST (Sanctum)".
2. Usá un `showModalBottomSheet` (patrón nativo de Flutter) para el formulario de denuncia (motivo obligatorio + descripción opcional).
3. Mostrá confirmación o el error que devuelva la API — recordá que la API bloquea denunciar la propia publicación y denunciar dos veces la misma publicación (regla "DECIDIDO" de KAN-22).

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

<details>
<summary>**KAN-138** [frontend] — Frontend (Flutter): ícono de campanita con contador de notificaciones no leídas</summary>

Archivos a crear/modificar:

* `lib/widgets/campanita.dart` (crear).
* Pantalla principal/navegación (donde esté el AppBar) — agregar el ícono en esa barra.

Guía:

1. Depende de un endpoint tipo `GET /alertas/no-leidas-count` (igual que la versión web, KAN-74) expuesto en la Epic "API REST (Sanctum)".
2. Mostrá un ícono de campana (`Icons.notifications`) con un badge con el contador, visible solo si es mayor a 0.
3. Al tocar el ícono, navegá a la pantalla de historial (Historia KAN-27 Flutter).

</details>

<details>
<summary>**KAN-248** [backend] — Backend: compartir la cantidad de alertas no leídas en todas las páginas</summary>

Archivos a crear/modificar:

* `app/Http/Middleware/HandleInertiaRequests.php` — sumar una prop compartida `alertas_no_leidas`.
* `tests/Feature/AlertaTest.php` (o un test nuevo) — agregar casos.

Guía:

1. `alertas_no_leidas` = cantidad de alertas del usuario logueado con `leida = false` (la tabla ya tiene índice en `usuario_id, leida`). Para invitados, `0` o `null`.
2. Usar una closure (`fn () => ...`) como el resto de las props compartidas, así solo se calcula cuando hace falta.
3. Tests: el número coincide con las alertas no leídas del usuario, baja al marcar una como leída y no cuenta las de otros usuarios.

Relacionada: KAN-74 (campanita con contador) y KAN-80 (panel desplegable), que necesitan el número en todas las páginas del layout.

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

<details>
<summary>**KAN-143** [frontend] — Frontend (Flutter): opción para activar/desactivar notificaciones por mail</summary>

Archivos a crear/modificar:

* `lib/screens/profile/profile_edit_screen.dart` (mismo archivo de KAN-11 Flutter).

Guía:

1. Depende del campo `notificar_por_mail` agregado a `users` (ver KAN-77, Subtask de la versión web) y de que la Epic "API REST (Sanctum)" lo acepte en el endpoint de perfil.
2. Agregá un switch/checkbox en la pantalla de perfil para activar/desactivar el envío de mails, igual que en la versión web.

</details>

<details>
<summary>**KAN-268** [backend] — Backend: columna notificar_por_mail y guardarla desde el perfil</summary>

Archivos a crear/modificar:

* `database/migrations/xxxx_add_notificar_por_mail_to_users_table.php` (nuevo) — columna boolean `notificar_por_mail`, default `true`.
* `app/Models/User.php` — agregarla a `Fillable` y castearla a `boolean`.
* `app/Http/Requests/ProfileUpdateRequest.php` — regla `'notificar_por_mail' => ['sometimes', 'boolean']`.
* `tests/Feature/ProfileTest.php` — agregar casos.

Guía:

1. Hoy el código de alertas ya consulta `$usuario->notificar_por_mail ?? true` (reseñas, denuncias, KAN-250), pero **la columna no existe**: siempre da `null` y siempre se manda el mail. Con la columna, esa consulta empieza a funcionar sola.
2. `ProfileController::update()` ya hace `fill($request->validated())`, así que alcanza con la regla.
3. Tests: un usuario puede desactivar y volver a activar el mail desde el perfil; con `false` no se manda `NuevaAlertaMail` ante una reseña nueva (`Mail::fake()`); los usuarios nuevos arrancan con `true`.

Destraba: KAN-77 (el switch en el perfil); de KAN-77 queda solo la parte de frontend.

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

<details>
<summary>**KAN-147** [frontend] — Frontend (Flutter): pantalla con el historial de notificaciones</summary>

Archivos a crear/modificar:

* `lib/screens/alertas/alertas_screen.dart` (crear).

Guía:

1. Depende del endpoint `GET /alertas` (y `PATCH /alertas/{id}/leida`) expuesto en la Epic "API REST (Sanctum)" — mismos endpoints que usa KAN-80 en la versión web.
2. Al tocar el ícono de campanita (KAN-25 Flutter), navegá a esta pantalla con el listado de alertas, de más reciente a más antigua.
3. Cada item: mensaje + fecha, con estilo distinto si `leida` es `false`.
4. Al tocar una alerta, marcala como leída (PATCH) y actualizá el contador de la campanita.

</details>

---

## Epic: KAN-82 — API REST para la app móvil (Sanctum)

_Exponer el mismo backend Laravel como API REST autenticada por token (Sanctum), para que la futura app Flutter consuma los mismos datos y reglas de negocio que ya usa la web (Inertia). No se duplica lógica de negocio: se reusan las Form Requests y Policies existentes, solo cambia el formato de respuesta (JSON vía API Resources en lugar de Inertia::render)._

### KAN-89 — Configurar autenticación por token (Sanctum) para la app móvil

> Como app móvil (Flutter), quiero autenticarme contra el backend de Laravel usando un token, para poder hacer pedidos a la API sin depender de las sesiones basadas en cookies que usa la web con Inertia.
>
> Criterios de aceptación:
>
> * `laravel/sanctum` está instalado y configurado (migración de `personal_access_tokens` corrida).
> * `POST /api/login` recibe email+password y devuelve un token de acceso si las credenciales son correctas.
> * `POST /api/logout` revoca el token actual del usuario autenticado.
> * Las rutas de `routes/api.php` protegidas usan el middleware `auth:sanctum`.
> * Las credenciales inválidas devuelven un error 401/422 claro, no una redirección (como sí hace la web).

<details>
<summary>**KAN-128** [backend] — Backend: instalar Sanctum y crear endpoints de login/logout por API</summary>

Archivos a crear/modificar:

* `composer.json` — agregar `laravel/sanctum` (`composer require laravel/sanctum`).
* `database/migrations/xxxx_create_personal_access_tokens_table.php` (la trae el propio paquete via `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`).
* `app/Models/User.php` — agregar el trait `Laravel\Sanctum\HasApiTokens`.
* `routes/api.php` (nuevo) — rutas `POST /login` y `POST /logout`.
* `app/Http/Controllers/Api/AuthController.php` (nuevo).
* `bootstrap/app.php` — registrar `routes/api.php` si Laravel 13 no lo hace automático (`->withRouting(..., api: __DIR__.'/../routes/api.php', ...)`).

Guía:

1. Instalá y configurá Sanctum siguiendo la documentación oficial (`php artisan install:api` en Laravel 13 hace casi todo esto automático).
2. En `AuthController::login`, validá email+password, y si son correctos devolvé `$user->createToken('mobile')->plainTextToken`. Si no, devolvé un error 422/401 en JSON (no redirección).
3. En `AuthController::logout`, usá `$request->user()->currentAccessToken()->delete()`.
4. Las rutas que necesiten usuario autenticado van dentro de `Route::middleware('auth:sanctum')->group(...)`.

</details>

<details>
<summary>**KAN-134** [backend] — Tests: feature test de login/logout vía API con token</summary>

Archivos a crear/modificar:

* `tests/Feature/Api/AuthControllerTest.php` (nuevo).

Guía:

1. Testeá que `POST /api/login` con credenciales correctas devuelve 200 y un token no vacío.
2. Testeá que credenciales incorrectas devuelven 401/422 y ningún token.
3. Testeá que `POST /api/logout` con un token válido revoca el token (una request posterior con el mismo token ya no autentica).
4. Usá `Sanctum::actingAs($user)` o el token real devuelto por login, según lo que sea más simple de testear.

</details>

### KAN-94 — Exponer endpoint de registro vía API

> Como app móvil (Flutter), quiero registrar un usuario nuevo contra la API, para que un productor o comprador pueda crear su cuenta desde la app sin pasar por la web.
>
> Criterios de aceptación:
>
> * `POST /api/register` acepta los mismos campos que el registro de Breeze más `rol` y `telefono` (ver KAN-10/KAN-11).
> * Usa las mismas reglas de validación que el formulario web (no se duplican, se extraen a una clase compartida si hace falta).
> * Devuelve el usuario creado y un token de acceso, para loguear automáticamente tras registrarse.
> * Un email ya registrado devuelve un error de validación claro.

<details>
<summary>**KAN-139** [backend] — Backend: endpoint de registro vía API reusando validaciones de Breeze/rol/teléfono</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Api/AuthController.php` — agregar método `register()`.
* `routes/api.php` — `Route::post('/register', [AuthController::class, 'register'])`.
* Posiblemente extraer las reglas de validación del `RegisteredUserController` de Breeze (resources/web) a algo reusable si están hardcodeadas ahí, para no repetirlas.

Guía:

1. Fijate cómo registra Breeze hoy (`app/Http/Controllers/Auth/RegisteredUserController.php`) — mismas reglas: name, email único, password, más `rol` (productor/comprador — ver KAN-10) y `telefono` (ver KAN-11).
2. Creá el usuario igual que la web, pero en vez de loguear por sesión y redirigir, devolvé el usuario + `createToken('mobile')->plainTextToken` en JSON.
3. Reusá el mismo `User::ROL_PRODUCTOR`/`ROL_ADMIN` (o el valor que corresponda a "comprador" si se agrega) en vez de hardcodear strings.

</details>

<details>
<summary>**KAN-145** [backend] — Tests: feature test de registro vía API</summary>

Archivos a crear/modificar:

* `tests/Feature/Api/RegisterControllerTest.php` (nuevo).

Guía:

1. Testeá que `POST /api/register` con datos válidos crea el usuario, guarda rol y teléfono, y devuelve un token.
2. Testeá que un email duplicado devuelve error de validación (422).
3. Testeá que falta de campos obligatorios devuelve error de validación.

</details>

### KAN-100 — Exponer API REST de categorías y publicaciones

> Como app móvil (Flutter), quiero listar categorías y crear/editar/buscar publicaciones contra la API, para poder ofrecer las mismas funciones de Publicaciones que ya existen en la web.
>
> Criterios de aceptación:
>
> * Endpoints JSON (en `routes/api.php`) para: listar categorías, listar/filtrar/ver detalle de publicaciones activas, crear publicación, editar publicación, cambiar estado (pausada/vendida).
> * Reusan exactamente las mismas `StorePublicacionRequest`, `UpdatePublicacionRequest` y `PublicacionPolicy` que ya usan los controladores web — no se reimplementa la validación ni la autorización.
> * Las respuestas usan API Resources (`JsonResource`), no vistas Inertia.
> * Mismas reglas de negocio que la web: solo productor crea, solo dueño/admin edita, etc.

<details>
<summary>**KAN-149** [backend] — Backend: endpoint API para listar categorías</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Api/CategoriaController.php` (nuevo).
* `app/Http/Resources/CategoriaResource.php` (nuevo).
* `routes/api.php` — `Route::get('/categorias', [CategoriaController::class, 'index'])`.

Guía:

1. Reusá el mismo query que ya usa el controlador web de categorías (KAN-37) — no dupliques lógica, llamá al mismo método/scope del modelo si existe, o replicá la query simple.
2. Envolvé la respuesta en `CategoriaResource::collection(...)`.

</details>

<details>
<summary>**KAN-154** [backend] — Backend: endpoints API de publicaciones (listar, detalle, crear, editar, cambiar estado)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Api/PublicacionController.php` (nuevo).
* `app/Http/Resources/PublicacionResource.php` (nuevo).
* `routes/api.php` — rutas dentro de `Route::middleware('auth:sanctum')->group(...)`: `GET /publicaciones`, `GET /publicaciones/{publicacion}`, `POST /publicaciones`, `PUT /publicaciones/{publicacion}`, `PATCH /publicaciones/{publicacion}/estado`.

Guía:

1. NO reescribas la lógica de negocio: `store()` y `update()` reciben `StorePublicacionRequest`/`UpdatePublicacionRequest` igual que los controladores web (KAN-40/43), y usan `$this->authorize()` con `PublicacionPolicy` igual.
2. Lo único que cambia respecto al controlador web es la respuesta: en vez de `Inertia::render(...)` o un `redirect()`, devolvé `PublicacionResource` (o una colección) en JSON.
3. Para el filtro por categoría (KAN-52) y el listado de activas (KAN-49), reusá el mismo query/scope que ya usa el controlador web, no lo reimplementes.
4. Pensá si conviene que `PublicacionController` web y `Api\PublicacionController` comparta un Service/Action interno — no es obligatorio para el TFI, pero evita duplicar queries si crece mucho.

</details>

<details>
<summary>**KAN-158** [backend] — Tests: feature test de los endpoints de categorías y publicaciones vía API</summary>

Archivos a crear/modificar:

* `tests/Feature/Api/CategoriaControllerTest.php` (nuevo).
* `tests/Feature/Api/PublicacionControllerTest.php` (nuevo).

Guía:

1. Testeá el listado de categorías sin autenticación (si es público) o con token (según se defina).
2. Testeá crear/editar/cambiar estado de una publicación con token de un productor — y que un comprador (no productor) recibe 403 al crear.
3. Testeá el filtro por categoría y que el listado solo devuelve publicaciones activas.
4. Reusá `Sanctum::actingAs($user)` para simular el token en los tests, en vez de loguear por sesión.

</details>

### KAN-105 — Exponer API REST de reseñas y denuncias

> Como app móvil (Flutter), quiero crear/listar reseñas y crear denuncias contra la API, para que esas funciones también estén disponibles desde la app.
>
> Criterios de aceptación:
>
> * Endpoints JSON para: crear reseña de una publicación, listar reseñas de una publicación, crear denuncia (de publicación o usuario).
> * Reusan `StoreResenaRequest`, `StoreDenunciaRequest`, `ResenaPolicy` y `DenunciaPolicy` ya existentes — misma lógica que la web (bloqueo de auto-reseña, auto-denuncia y denuncia duplicada).
> * Respuestas en JSON vía API Resources.

<details>
<summary>**KAN-162** [backend] — Backend: endpoints API de reseñas y denuncias</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Api/ResenaController.php` (nuevo).
* `app/Http/Controllers/Api/DenunciaController.php` (nuevo).
* `app/Http/Resources/ResenaResource.php` (nuevo).
* `routes/api.php` — `POST /publicaciones/{publicacion}/resenas`, `GET /publicaciones/{publicacion}/resenas`, `POST /denuncias`, dentro del grupo `auth:sanctum`.

Guía:

1. Igual que en la Epic de API de Publicaciones: reusá `StoreResenaRequest`/`StoreDenunciaRequest` y `ResenaPolicy`/`DenunciaPolicy` tal cual los usan los controladores web (KAN-58/64) — solo cambia la respuesta a JSON.
2. El bloqueo de auto-reseña, auto-denuncia y denuncia duplicada (KAN-22/64/66) ya está resuelto en el Form Request/Policy, así que no hay nada nuevo que validar acá, solo exponerlo.

</details>

<details>
<summary>**KAN-163** [backend] — Tests: feature test de reseñas y denuncias vía API</summary>

Archivos a crear/modificar:

* `tests/Feature/Api/ResenaControllerTest.php` (nuevo).
* `tests/Feature/Api/DenunciaControllerTest.php` (nuevo).

Guía:

1. Testeá crear una reseña vía API con token, y que el dueño de la publicación no puede reseñarse a sí mismo (ya bloqueado por el Form Request, el test confirma que se refleja en el endpoint real).
2. Testeá crear una denuncia vía API, bloqueo de auto-denuncia y de denuncia duplicada del mismo usuario sobre la misma publicación (ver KAN-22/66).

</details>

### KAN-112 — Exponer API REST de alertas/notificaciones

> Como app móvil (Flutter), quiero listar mis alertas/notificaciones y marcarlas como leídas contra la API, para mostrar la campanita de notificaciones dentro de la app.
>
> Criterios de aceptación:
>
> * `GET /api/alertas` devuelve las alertas del usuario autenticado (vía Sanctum), igual que la campanita web (KAN-25/27).
> * `PATCH /api/alertas/{id}/leida` marca una alerta como leída.
> * Reusa el modelo `Alerta` y la misma lógica de autorización (un usuario solo ve/marca sus propias alertas).

<details>
<summary>**KAN-167** [backend] — Backend: endpoints API para listar alertas y marcarlas como leídas</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Api/AlertaController.php` (nuevo).
* `app/Http/Resources/AlertaResource.php` (nuevo).
* `routes/api.php` — `GET /alertas`, `PATCH /alertas/{alerta}/leida`, dentro del grupo `auth:sanctum`.

Guía:

1. `index()` devuelve `$request->user()->alertas` (la relación ya existe en `User::alertas()`), ordenadas por fecha, con un campo `leida` en el resource.
2. `marcarLeida()` verifica que la alerta pertenezca al usuario autenticado antes de marcarla (403 si no), igual que haría la versión web (KAN-79).

</details>

<details>
<summary>**KAN-172** [backend] — Tests: feature test de alertas vía API</summary>

Archivos a crear/modificar:

* `tests/Feature/Api/AlertaControllerTest.php` (nuevo).

Guía:

1. Testeá que un usuario autenticado solo ve sus propias alertas (no las de otro usuario).
2. Testeá que marcar como leída una alerta ajena devuelve 403.
3. Testeá que marcar como leída una alerta propia la actualiza correctamente.

</details>

---

## Epic: KAN-83 — Geolocalización

_Agregar ubicación geográfica a usuarios y publicaciones, mostrarlas en un mapa (Google Maps), ordenar resultados por cercanía y permitir marcar favoritos. Hoy no existe ningún campo de ubicación (lat/lng) en el modelo de datos ni tabla de favoritos — hay que crear las migraciones, el backend y el frontend (Web + Flutter) de cero._

### KAN-90 — Registrar la ubicación de mi perfil para que me encuentren cerca

> Como productor o comprador, quiero registrar la ubicación de mi perfil, para que otros usuarios puedan encontrarme o encontrar mis publicaciones por cercanía.
>
> Criterios de aceptación:
>
> * El perfil tiene un campo de ubicación (lat/lng) opcional.
> * Puedo setear mi ubicación desde un selector en el perfil (dirección + mapa).
> * Si no seteo ubicación, el resto de las funciones de geolocalización simplemente no me muestran/ordenan (no rompe nada).
> * La ubicación se guarda asociada a mi usuario.

<details>
<summary>**KAN-117** [backend] — Backend: migración y endpoint para guardar lat/lng del perfil</summary>

Archivos a crear/modificar:

* `database/migrations/<fecha>_add_lat_lng_to_users_table.php` (nueva).
* `app/Models/User.php` — agregar `lat`/`lng` al `#[Fillable]`.
* Controlador de perfil (el que ya maneja `ProfileUpdateRequest`) — aceptar y guardar `lat`/`lng`.

Guía:

1. Migración: agregar `decimal('lat', 10, 7)->nullable()` y `decimal('lng', 10, 7)->nullable()` a `users`. No edites ninguna migración ya corrida — esta va aparte.
2. Agregá `lat`/`lng` al array de `Fillable` del modelo `User`.
3. Sumá las reglas `lat`/`lng` como `nullable|numeric` (con rango -90/90 y -180/180) en el Request de actualización de perfil.
4. Si el usuario no manda lat/lng, se guarda null — no rompe nada del resto de la app.

</details>

<details>
<summary>**KAN-121** [frontend] — Frontend Web: selector de ubicación en el perfil (Google Maps JS)</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Profile/Edit.tsx` (o el componente de edición de perfil que ya exista) — agregar el selector de ubicación.
* `resources/js/Components/MapaSelectorUbicacion.tsx` (nuevo).

Guía:

1. Agregá la librería de Google Maps JS (`@googlemaps/js-api-loader` o similar) al proyecto.
2. El componente nuevo muestra un mapa con un marker arrastrable; al moverlo, actualiza `lat`/`lng` en el formulario (`useForm` de Inertia).
3. Sumá también un input de texto para buscar una dirección (Geocoding API) que centre el mapa, como alternativa a arrastrar el marker a mano.
4. Es opcional: si el usuario no toca nada, no se manda lat/lng y el perfil queda sin ubicación.

</details>

<details>
<summary>**KAN-125** [frontend] — Frontend Flutter: selector de ubicación en la pantalla de perfil</summary>

Archivos a crear/modificar (dentro del proyecto Flutter, carpeta sugerida `lib/screens/profile/`):

* `lib/screens/profile/edit_profile_screen.dart` — agregar el selector de ubicación.
* `lib/widgets/map_location_picker.dart` (nuevo).

Guía:

1. Agregá el paquete `google_maps_flutter` y `geolocator` (para pedir permiso de ubicación del dispositivo) al `pubspec.yaml`.
2. El widget nuevo muestra un mapa con un marker que el usuario puede mover, o un botón "usar mi ubicación actual" que centra el mapa con `geolocator`.
3. Al confirmar, mandá `lat`/`lng` al mismo endpoint de perfil que usa la versión Web (mismo contrato de API).
4. Pedí el permiso de ubicación en runtime (Android) antes de usar `geolocator`, y manejá el caso de que el usuario lo rechace (el selector sigue funcionando a mano, moviendo el marker).

</details>

<details>
<summary>**KAN-131** [backend] — Tests: feature test de guardado de lat/lng en el perfil</summary>

Archivos a crear/modificar:

* `tests/Feature/PerfilUbicacionTest.php` (nuevo).

Guía:

1. Test: un usuario autenticado manda lat/lng válidos al endpoint de perfil y quedan guardados.
2. Test: lat/lng fuera de rango (ej. lat=200) son rechazados por la validación.
3. Test: no mandar lat/lng no rompe la actualización del resto del perfil (quedan null).

</details>

<details>
<summary>**KAN-272** [backend] — Backend: localidad del productor y distancia en el detalle de la publicación</summary>

Archivos a crear/modificar:

* `database/migrations/xxxx_add_localidad_to_users_table.php` (nuevo) — columna `localidad` (string nullable, máx. 100).
* `app/Models/User.php` — agregarla a `Fillable`.
* `app/Http/Requests/ProfileUpdateRequest.php` — regla `'localidad' => ['nullable', 'string', 'max:100']`.
* `app/Http/Controllers/PublicacionController.php` — en `show()`.
* `tests/Feature/PerfilUbicacionTest.php` y `tests/Feature/PublicacionControllerTest.php` — agregar casos.

Guía:

1. La ubicación del perfil del productor (lat/lng, KAN-117) pasa a ser la de **su chacra o punto de venta**. `localidad` es el nombre de la zona que carga él (ej. "Laguna Naineck"): con lat/lng solos no se puede mostrar un nombre sin un servicio externo.
2. En `show()`, mandar del productor `localidad` y una prop `distancia_km`: la distancia entre el usuario que mira (si tiene lat/lng) y el productor (si tiene lat/lng), redondeada a 1 decimal; `null` si falta alguna de las dos. Se puede reusar la fórmula de Haversine de `index()` (en PHP, no en SQL).
3. **No** mandar la lat/lng del productor en el detalle: la ubicación exacta de la chacra no se expone.
4. Tests: guardar y borrar la localidad desde el perfil; `distancia_km` correcta con las dos ubicaciones y `null` si falta una; el detalle no incluye `usuario.lat` ni `usuario.lng`.

Relacionada: KAN-171 (ordenar por cercanía), tareas de frontend de ubicación de esta historia.

</details>

<details>
<summary>**KAN-273** [frontend] — Frontend Web: ubicación de la chacra o punto de venta en el perfil (sin mapa)</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Profile/Partials/UpdateUbicacionForm.tsx` (nuevo).
* `resources/js/Pages/Profile/Edit.tsx` — sumar la sección.
* `resources/js/types/index.d.ts` — `localidad` en `User`.

Guía:

1. Sección "Ubicación de tu chacra o punto de venta" para productores (para compradores: "Tu ubicación", para ordenar por cercanía).
2. Campo de texto "Localidad" (ej. "Laguna Naineck").
3. Botón "Usar mi ubicación actual": pide la posición al navegador (`navigator.geolocation`, como el orden por cercanía de KAN-171) y guarda lat/lng con `profile.update`. Mostrar "Ubicación guardada ✓" y un botón para borrarla.
4. Aclarar que **no se muestra el punto exacto** a nadie: solo la localidad y la distancia aproximada.
5. Manejar el permiso rechazado con un mensaje claro.

Es un adelanto de KAN-121 (selector con Google Maps, bloqueada por la API key): cuando se haga, el mapa reemplaza o complementa el botón.

Depende de: la tarea de backend de la localidad (esta historia).

</details>

<details>
<summary>**KAN-274** [frontend] — Frontend Web: mostrar la localidad y la distancia del productor en el detalle</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` — recuadro del productor.

Guía:

1. Debajo del nombre del productor: "📍 Laguna Naineck · a 12 km" (ícono `MapPin` de lucide).
2. Sin `distancia_km` (el que mira no tiene ubicación), solo la localidad; sin localidad, solo la distancia; sin ninguna de las dos, no se muestra nada.
3. Distancia sin decimales si es de 10 km o más; "a menos de 1 km" si es menor a 1 (mismo criterio que la tarjeta, KAN-171).

Depende de: la tarea de backend de la localidad y la distancia (esta historia).

</details>

### KAN-95 — Ver productores y publicaciones en un mapa

> Como comprador, quiero ver en un mapa las publicaciones y productores cercanos, para elegir con quién contactarme según la distancia.
>
> Criterios de aceptación:
>
> * Hay una vista de mapa (Google Maps) con markers de las publicaciones activas que tienen ubicación.
> * Tocar/clickear un marker muestra un resumen de la publicación (y lleva al detalle).
> * Las publicaciones sin ubicación asociada no aparecen en el mapa (pero sí en el listado normal).

<details>
<summary>**KAN-144** [backend] — Backend: endpoint con publicaciones georreferenciadas para el mapa</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — nuevo método `mapa()`.
* `routes/web.php` y `routes/api.php` — agregar la ruta (ver Epic de API REST para el contrato que consume Flutter).

Guía:

1. `mapa()` devuelve las publicaciones activas (`estado = activa`) cuyo dueño (`usuario_id`) tiene `lat`/`lng` no nulos, con `lat`, `lng`, `titulo`, `precio` y `id` de la publicación.
2. Reusá el scope/filtro de "activas" que ya existe para el listado (KAN-49), no dupliques la condición de estado.
3. No hace falta paginar: es para pintar markers, no para un listado largo (igual, se puede acotar por bounding box como mejora futura si el mapa queda lento).

</details>

<details>
<summary>**KAN-148** [frontend] — Frontend Web: vista de mapa con markers de publicaciones (Google Maps JS)</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Mapa.tsx` (nuevo).

Guía:

1. Pedí al endpoint de `mapa()` (backend) las publicaciones con ubicación y pintá un marker por cada una.
2. Al clickear un marker, mostrá un popup/infowindow con título y precio, y un link al detalle (KAN-56).
3. Agregá el link a esta vista desde el listado de publicaciones (KAN-50), como un botón "Ver en mapa".

</details>

<details>
<summary>**KAN-153** [frontend] — Frontend Flutter: pantalla de mapa con markers de publicaciones</summary>

Archivos a crear/modificar:

* `lib/screens/map/map_screen.dart` (nuevo).

Guía:

1. Usá `google_maps_flutter` para pintar un `GoogleMap` con un `Marker` por cada publicación que devuelve el endpoint de mapa (mismo endpoint que consume la Web).
2. Al tocar un marker, mostrá un bottom sheet con título/precio y un botón para ir al detalle de la publicación.
3. Agregá esta pantalla a la navegación principal (tab o botón desde el listado de publicaciones).

</details>

<details>
<summary>**KAN-157** [backend] — Tests: feature test del endpoint de publicaciones para el mapa</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionMapaTest.php` (nuevo).

Guía:

1. Test: una publicación activa cuyo dueño tiene lat/lng aparece en la respuesta del endpoint.
2. Test: una publicación cuyo dueño NO tiene lat/lng no aparece.
3. Test: una publicación pausada o vendida no aparece, aunque el dueño tenga ubicación.

</details>

### KAN-101 — Ordenar el listado de publicaciones por cercanía

> Como comprador, quiero ordenar el listado de publicaciones por cercanía a mi ubicación, para priorizar productores cerca de mí.
>
> Criterios de aceptación:
>
> * En el listado de publicaciones (KAN-17) hay una opción "ordenar por cercanía".
> * Esa opción solo está disponible si yo tengo ubicación seteada en mi perfil.
> * El orden se calcula por distancia real (no aproximada por zona/categoría).
> * Se puede combinar con el filtro por categoría existente (KAN-18).

<details>
<summary>**KAN-166** [backend] — Backend: ordenar el listado de publicaciones por distancia (Haversine)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — método de listado (el de KAN-49/KAN-52).

Guía:

1. Si el request trae `lat`/`lng` del usuario, calculá la distancia a cada publicación (vía la ubicación de su `usuario_id`) con la fórmula de Haversine, directamente en la query SQL (raw expression) para poder ordenar con `orderBy`.
2. Si el usuario no manda lat/lng, el listado se comporta como siempre (sin romper KAN-49/KAN-52).
3. Combinable con el filtro de categoría existente (KAN-52): el orden por cercanía se aplica después del filtro.

</details>

<details>
<summary>**KAN-171** [frontend] — Frontend Web: opción "ordenar por cercanía" en el listado de publicaciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Index.tsx` (la página de listado de KAN-50/KAN-53).

Guía:

1. Agregá un toggle/select "ordenar por cercanía", visible solo si el usuario tiene ubicación seteada en su perfil.
2. Al activarlo, mandá `lat`/`lng` del usuario (ya los tiene guardados, no hace falta pedir permiso del navegador) como query param al endpoint de listado.
3. Mantiene compatibilidad con el selector de categoría existente (KAN-53).

</details>

<details>
<summary>**KAN-175** [frontend] — Frontend Flutter: opción "ordenar por cercanía" en la pantalla de listado</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicaciones_list_screen.dart`.

Guía:

1. Mismo criterio que la Web: mostrar el toggle solo si el usuario tiene ubicación guardada en su perfil (no hace falta volver a pedir el permiso del dispositivo acá).
2. Mandá lat/lng como parámetro al mismo endpoint que usa la Web.
3. Mantiene compatibilidad con el filtro de categoría existente en la pantalla.

</details>

<details>
<summary>**KAN-179** [backend] — Tests: feature test de ordenamiento de publicaciones por cercanía</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionOrdenCercaniaTest.php` (nuevo).

Guía:

1. Test: con lat/lng del usuario, el listado devuelve las publicaciones ordenadas de más cerca a más lejos (armá 2-3 productores con ubicaciones conocidas y verificá el orden exacto).
2. Test: sin lat/lng, el listado se comporta igual que antes (no se rompe KAN-49/KAN-51).
3. Test: combinar orden por cercanía con filtro de categoría (KAN-54) y verificar que ambos se aplican juntos.

</details>

<details>
<summary>**KAN-213** [backend] — Bug: el listado de publicaciones con ?lat=&lng= da error de SQL en MySQL</summary>

En `PublicacionController::index()`, la fórmula de Haversine usa `min(1.0, max(-1.0, ...))`. En MySQL, `MIN` y `MAX` son funciones de agregado y eso da "SQLSTATE\[42000\] Syntax error" (confirmado contra la base MySQL local). En SQLite funciona, por eso no se detectó. Además no hay ningún test que llame a `/publicaciones?lat=..&lng=..`.

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php`
* `tests/Feature/PublicacionControllerTest.php`

Guía:

1. Usar `LEAST(1.0, GREATEST(-1.0, ...))` en MySQL. Como SQLite no tiene `LEAST`/`GREATEST`, armar la expresión según `DB::getDriverName()`, o mover el cálculo a un scope del modelo.
2. Agregar un test que cree productores con lat/lng distintos y verifique el orden por distancia.
3. Correr ese test también contra MySQL (el entorno local usa MySQL) antes de mergear.

Nota: el proyecto no tiene un tipo de issue "Bug" habilitado — se creó como Subtask de KAN-101 (la Historia de "ordenar por cercanía"), tal como indicó el reporte original como alternativa.

</details>

### KAN-106 — Marcar productores o publicaciones como favoritos

> Como comprador, quiero marcar publicaciones como favoritas, para encontrarlas rápido después sin tener que buscarlas de nuevo.
>
> Criterios de aceptación:
>
> * Puedo marcar/desmarcar una publicación como favorita desde su detalle o desde el listado.
> * Tengo una pantalla/página "Mis favoritos" con todo lo que marqué.
> * No puedo marcar como favorita una publicación que ya no existe o está vendida sin que se note claramente en el listado de favoritos.
> * Un usuario no puede duplicar el mismo favorito (constraint único por usuario+publicación).

<details>
<summary>**KAN-184** [backend] — Backend: migración y endpoints de favoritos (agregar/quitar/listar)</summary>

Archivos a crear/modificar:

* `database/migrations/<fecha>_create_favoritos_table.php` (nueva).
* `app/Models/Favorito.php` (nuevo).
* `app/Http/Controllers/FavoritoController.php` (nuevo).
* `routes/web.php` y `routes/api.php` — rutas de favoritos.

Guía:

1. Migración: `usuario_id` (FK a users, cascadeOnDelete), `publicacion_id` (FK a publicaciones, cascadeOnDelete), timestamps, y `unique(['usuario_id', 'publicacion_id'])` para que no se pueda duplicar.
2. Controlador: `store()` (crear favorito), `destroy()` (quitar favorito) e `index()` (listar los míos, con la publicación cargada vía `with('publicacion')`).
3. Solo el dueño del favorito puede borrarlo (no hace falta Policy separada, alcanza con filtrar por `usuario_id = auth()->id()` en el controlador).
4. El `store()` debe ser idempotente o devolver un error claro si ya existe el favorito (por el constraint único).

</details>

<details>
<summary>**KAN-188** [frontend] — Frontend Web: botón de favorito y página "Mis favoritos"</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` (detalle, KAN-56) — agregar botón de favorito.
* `resources/js/Pages/Favoritos/Index.tsx` (nuevo).

Guía:

1. Botón de corazón/estrella en el detalle de la publicación que llama al endpoint de favoritos (toggle).
2. Página nueva que lista los favoritos del usuario autenticado, con link al detalle de cada publicación.
3. Si una publicación favorita está vendida/pausada, mostrala igual pero con una etiqueta clara ("ya no disponible").

</details>

<details>
<summary>**KAN-191** [frontend] — Frontend Flutter: botón de favorito y pantalla "Mis favoritos"</summary>

Archivos a crear/modificar:

* `lib/screens/publicaciones/publicacion_detail_screen.dart` — agregar botón de favorito.
* `lib/screens/favoritos/favoritos_screen.dart` (nuevo).

Guía:

1. Mismo botón de favorito (ícono) en el detalle, llamando al mismo endpoint que usa la Web.
2. Pantalla nueva con la lista de favoritos, reusando el widget de card de publicación que ya exista en el listado principal.
3. Mismo criterio que la Web para publicaciones ya no disponibles: mostrarlas marcadas, no ocultarlas.

</details>

<details>
<summary>**KAN-193** [backend] — Tests: feature test de agregar/quitar/listar favoritos</summary>

Archivos a crear/modificar:

* `tests/Feature/FavoritoTest.php` (nuevo).

Guía:

1. Test: un usuario autenticado puede agregar una publicación a favoritos y aparece en su listado.
2. Test: no se puede duplicar el mismo favorito (el constraint único se respeta, no explota con error 500).
3. Test: un usuario puede quitar su propio favorito, pero no puede borrar un favorito de otro usuario.

</details>

<details>
<summary>**KAN-247** [backend] — Backend: indicar si la publicación es favorita del usuario en el detalle (es_favorito)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — en `show()`, mandar la prop `es_favorito`.
* `tests/Feature/FavoritoTest.php` — agregar casos.

Guía:

1. `es_favorito` = `true` si el usuario logueado tiene a esa publicación en favoritos (`$publicacion->favoritos()->where('usuario_id', $request->user()->id)->exists()`), `false` si no. Mismo patrón que `mi_denuncia` y `mi_solicitud`, que ya manda `show()`.
2. Tests: `es_favorito` es `true` después de marcarla y `false` para otro usuario que no la marcó.

Relacionada: KAN-188 (botón de favorito en el detalle y página "Mis favoritos"), que necesita este dato para mostrar el corazón lleno o vacío de entrada.

</details>

---

## Epic: KAN-84 — Socios del Consorcio

_Validación de productores como socios del Consorcio productivo de Laguna Naineck mediante número de socio, insignia visible de socio validado, y priorización de socios en los resultados de búsqueda y catálogo frente al resto de usuarios. Alcance definido en el Plan de Trabajo del TFI (sección "Gestión de Usuarios y Seguridad" y "Búsqueda y Comercialización")._

### KAN-92 — Solicitar la validación como socio del Consorcio con mi número de socio

> Como productor, quiero ingresar mi número de socio del Consorcio, para solicitar que el admin valide mi condición de socio y obtener los beneficios de visibilidad.
>
> Criterios de aceptación:
>
> * En el perfil, el productor puede ingresar su número de socio (campo nuevo `numero_socio` en `users`).
> * Al enviarlo, queda en estado "pendiente de validación" (campo nuevo `socio_validado` boolean, default false).
> * El usuario puede ver el estado actual de su solicitud (pendiente / validado) en su perfil.
> * Si todavía no ingresó un número de socio, no se le muestra como pendiente ni validado (es un estado aparte, "sin solicitar").

<details>
<summary>**KAN-123** [backend] — Backend: migración de numero_socio/socio_validado y endpoint para solicitar validación</summary>

Archivos a crear/modificar:

* `database/migrations/xxxx_xx_xx_add_numero_socio_to_users_table.php` (nuevo) — `$table->string('numero_socio')->nullable()->after('telefono'); $table->boolean('socio_validado')->default(false)->after('numero_socio');` (no editar la migración de rol/telefono ya corrida, es una nueva).
* `app/Models/User.php` — agregar `numero_socio` y `socio_validado` al `#[Fillable(...)]`, y un método `esSocioValidado(): bool`.
* `app/Http/Requests/UpdateNumeroSocioRequest.php` (nuevo) — valida `numero_socio` (string, requerido, max 50).
* `app/Http/Controllers/ProfileController.php` (o uno nuevo `SocioController`) — método para guardar el número de socio del usuario autenticado, reseteando `socio_validado` a false si cambia el número.
* `routes/web.php` — ruta tipo `PATCH /perfil/socio`.

Guía:

1. Solo el usuario autenticado puede actualizar su propio número de socio (`auth()->id()`).
2. Al guardar un número de socio nuevo o distinto, `socio_validado` vuelve a false (hay que re-validar).

</details>

<details>
<summary>**KAN-130** [frontend] — Frontend Web: formulario en el perfil para ingresar el número de socio y ver el estado</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Profile/Edit.tsx` (o el componente de perfil existente) — agregar sección "Socio del Consorcio" con input de número de socio y badge de estado (sin solicitar / pendiente / validado).

Guía:

1. Mostrá el estado según `numero_socio` (null = sin solicitar) y `socio_validado` (true = validado, false con numero_socio = pendiente).
2. Deshabilitá el input mientras está en estado "validado" (si quiere cambiarlo, aclaralo con un mensaje, no bloquees el caso raro).

</details>

<details>
<summary>**KAN-136** [frontend] — Frontend Flutter: pantalla para ingresar el número de socio y ver el estado</summary>

Archivos a crear/modificar:

* `lib/features/profile/presentation/` (pantalla de perfil de la app) — agregar sección "Socio del Consorcio" equivalente a la versión web (input + badge de estado).

Guía:

1. Misma lógica de estados que la versión web (sin solicitar / pendiente / validado), consumiendo el mismo endpoint `PATCH /perfil/socio` vía la API REST.
2. Reusar el cliente HTTP/autenticación que ya se use en el resto de la app para llamadas al backend.

</details>

<details>
<summary>**KAN-141** [backend] — Tests: feature test de solicitud de validación de socio</summary>

Archivos a crear/modificar:

* `tests/Feature/SocioValidacionTest.php` (nuevo).

Guía:

1. Testeá que un usuario autenticado puede guardar su número de socio y queda `socio_validado=false`.
2. Testeá que si cambia el número de socio estando ya validado, `socio_validado` vuelve a false.
3. Testeá que un usuario no autenticado no puede acceder al endpoint.

</details>

### KAN-99 — Admin valida o rechaza la solicitud de socio

> Como admin, quiero ver las solicitudes de validación de socio pendientes y aceptarlas o rechazarlas, para controlar quién obtiene la insignia y la prioridad de socio del Consorcio.
>
> Criterios de aceptación:
>
> * Solo un usuario con rol admin puede acceder a este listado y a las acciones de aceptar/rechazar.
> * Se muestran los usuarios con `numero_socio` cargado y `socio_validado` todavía en false.
> * Al aceptar, `socio_validado` pasa a true. Al rechazar, se limpia `numero_socio` (vuelve a poder solicitarlo de nuevo) y `socio_validado` queda en false.

<details>
<summary>**KAN-152** [backend] — Backend: endpoint para listar solicitudes de socio pendientes y aceptar/rechazar (solo admin)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/SocioController.php` (nuevo, o el mismo del subtask de solicitud) — métodos `index()`, `aceptar(User $usuario)`, `rechazar(User $usuario)`.
* `routes/web.php` — rutas dentro del grupo `['auth', 'admin']`: `GET /admin/socios`, `POST /admin/socios/{usuario}/aceptar`, `POST /admin/socios/{usuario}/rechazar`.

Guía:

1. `index()` devuelve los usuarios con `numero_socio` no nulo y `socio_validado=false`.
2. `aceptar()` pone `socio_validado=true`. `rechazar()` pone `numero_socio=null` y `socio_validado=false`.
3. El middleware `admin` ya existente (`EnsureUserIsAdmin`) bloquea el acceso a no-admins — no reimplementar esa validación a mano.

</details>

<details>
<summary>**KAN-156** [frontend] — Frontend Web: panel de admin con solicitudes de socio pendientes (aceptar/rechazar)</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Socios/Index.tsx` (nuevo) — listado con botones "Aceptar"/"Rechazar" por fila.

Guía:

1. Reusá el layout de admin que ya se use para el panel de denuncias (KAN-68), para mantener consistencia visual.
2. Mostrá confirmación antes de rechazar (es una acción destructiva para el usuario, pierde el número ingresado).

</details>

<details>
<summary>**KAN-161** [backend] — Tests: feature test de validación/rechazo de socio y bloqueo a no-admin</summary>

Archivos a crear/modificar:

* `tests/Feature/SocioValidacionTest.php` (mismo archivo del subtask de solicitud, agregar casos de admin).

Guía:

1. Testeá que un admin puede aceptar y rechazar una solicitud.
2. Testeá que un usuario no-admin recibe 403 al intentar acceder a `/admin/socios` o a aceptar/rechazar.

</details>

### KAN-104 — Ver la insignia de socio en el perfil y las publicaciones

> Como usuario, quiero ver una insignia de "Socio del Consorcio" en el perfil y en las publicaciones de productores validados, para identificar fácilmente a los socios al navegar la plataforma.
>
> Criterios de aceptación:
>
> * La insignia solo se muestra si `socio_validado` es true.
> * Aparece en la página de perfil del productor y en cada tarjeta/detalle de publicación que le pertenece.
> * No requiere ninguna acción del usuario que la ve, es solo informativa.

<details>
<summary>**KAN-169** [backend] — Backend: incluir socio_validado en las respuestas de perfil y publicación</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — en `show()`/`index()`, incluir `socio_validado` del productor (`with('usuario:id,name,socio_validado')` o similar) en la respuesta/props de Inertia.
* `app/Http/Controllers/ProfileController.php` — incluir `socio_validado` en los props del perfil.

Guía:

1. No expongas `numero_socio` en estas respuestas públicas (es un dato que solo debería verse en el propio perfil y en el panel de admin), solo el booleano `socio_validado`.

</details>

<details>
<summary>**KAN-173** [frontend] — Frontend Web: insignia de socio en el perfil y en las tarjetas/detalle de publicación</summary>

Archivos a crear/modificar:

* `resources/js/Components/SocioBadge.tsx` (nuevo) — componente chico reutilizable (ícono + texto "Socio del Consorcio").
* `resources/js/Pages/Profile/Edit.tsx`, `resources/js/Pages/Publicaciones/Index.tsx`, `resources/js/Pages/Publicaciones/Show.tsx` — usar `<SocioBadge />` cuando `socio_validado` sea true.

Guía:

1. Un solo componente reutilizado en los 3 lugares, para no duplicar el markup del badge.

</details>

<details>
<summary>**KAN-176** [frontend] — Frontend Flutter: insignia de socio en el perfil y en las tarjetas/detalle de publicación</summary>

Archivos a crear/modificar:

* `lib/shared/widgets/socio_badge.dart` (nuevo) — widget chico reutilizable (ícono + texto).
* Pantallas de perfil, listado y detalle de publicación — usar el widget cuando `socio_validado` sea true.

Guía:

1. Mismo criterio que la versión web: un solo widget reutilizado en todas las pantallas que lo necesiten.

</details>

<details>
<summary>**KAN-181** [backend] — Tests: feature test que verifique que la insignia solo aparece si socio_validado es true</summary>

Archivos a crear/modificar:

* `tests/Feature/SocioValidacionTest.php` (agregar casos) o `tests/Feature/PublicacionControllerTest.php`.

Guía:

1. Testeá que el prop/campo `socio_validado` llega en true para un socio validado y en false para el resto, tanto en el perfil como en el listado/detalle de publicaciones.

</details>

### KAN-110 — Ver a los socios del Consorcio primero en las búsquedas

> Como comprador, quiero que las publicaciones de socios del Consorcio aparezcan primero en los resultados de búsqueda y el catálogo, para darle la prioridad de visibilidad que el Consorcio les quiere dar a sus socios.
>
> Criterios de aceptación:
>
> * El listado/búsqueda de publicaciones activas (KAN-49/KAN-52) ordena primero las publicaciones de productores con `socio_validado` true, y después el resto, manteniendo el filtro por categoría si está aplicado.
> * Esto no oculta ni bloquea las publicaciones de no-socios, solo cambia el orden.

<details>
<summary>**KAN-185** [backend] — Backend: ordenar el listado de publicaciones priorizando socios validados</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — en `index()` (el mismo método ampliado por el filtro de categoría de KAN-52), agregar `->orderByDesc(...)` usando un join/whereHas con `usuario.socio_validado`, manteniendo el filtro de categoría existente.

Guía:

1. Usá `->join('users', ...)` o `->orderByRaw` basado en un subquery de `socio_validado` — lo importante es que el orden sea: primero `socio_validado=true`, después el resto, y dentro de cada grupo mantener el orden que ya exista (ej. más reciente primero).
2. No cambiés el filtro de categoría ya implementado, solo el orden del resultado final.

</details>

<details>
<summary>**KAN-189** [frontend] — Frontend Web: reflejar el nuevo orden de prioridad en el listado de publicaciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Index.tsx` — no necesita lógica de ordenamiento propia (ya viene ordenado del backend), solo verificar visualmente que la insignia de socio (KAN-173) se note para entender el orden.

Guía:

1. Este subtask es principalmente de verificación visual, el ordenamiento real lo hace el backend.

</details>

<details>
<summary>**KAN-192** [frontend] — Frontend Flutter: reflejar el nuevo orden de prioridad en el listado de publicaciones</summary>

Archivos a crear/modificar:

* Pantalla de listado de publicaciones en la app — no necesita lógica de ordenamiento propia (ya viene ordenado del backend vía API REST), solo verificar visualmente que la insignia de socio (KAN-176) se note.

Guía:

1. Igual que en la versión web: es un subtask de verificación visual, el orden lo resuelve el backend.

</details>

<details>
<summary>**KAN-196** [backend] — Tests: feature test del orden de prioridad de socios en el listado</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionControllerTest.php` (agregar caso).

Guía:

1. Creá publicaciones de un productor socio validado y de uno no socio, en orden inverso de creación, y verificá que la respuesta del listado devuelve primero la del socio a pesar de ser más vieja.
2. Verificá que el filtro de categoría (KAN-54) sigue funcionando junto con este orden.

</details>

---

## Epic: KAN-86 — Compra directa y contacto

_Resolver la compra como "compra directa" en el MVP: el comprador solicita la compra de una publicación, el productor la acepta o rechaza, y el acuerdo (precio, cantidad, forma de entrega) se cierra manualmente entre las partes. No hay pasarela de pago en esta Epic — MercadoPago queda documentado como Fase 2 y no se implementa ahora.

También cubre el contacto directo por WhatsApp (deep-link wa.me) desde la publicación o el perfil del productor, usando el teléfono que ya existe en el modelo `User`._

### KAN-96 — Solicitar la compra directa de una publicación

> Como comprador, quiero solicitar la compra directa de una publicación, para avisarle al productor que quiero concretar la operación y coordinar con él los detalles (cantidad, forma de entrega, pago).
>
> Criterios de aceptación:
>
> * La solicitud queda en estado "pendiente" hasta que el productor la resuelve.
> * No se puede solicitar la compra de la propia publicación (mismo patrón que StoreResenaRequest/StoreDenunciaRequest con withValidator).
> * Se puede incluir un mensaje opcional para el productor (ej. cantidad deseada).
> * No hay pago ni pasarela en este flujo: es una solicitud de contacto, el acuerdo final es manual entre las partes (MercadoPago queda para Fase 2, fuera de esta Historia).

<details>
<summary>**KAN-108** [backend] — Backend: migración, modelo y controlador para crear solicitudes de compra</summary>

Archivos a crear/modificar:

* `database/migrations/2026_11_07_000001_create_solicitudes_compra_table.php` (nuevo).
* `app/Models/SolicitudCompra.php` (nuevo, con `protected $table = 'solicitudes_compra'`, siguiendo el mismo patrón que `Publicacion`/`Denuncia`).
* `app/Http/Requests/StoreSolicitudCompraRequest.php` (nuevo, con `withValidator` para bloquear que el comprador solicite su propia publicación — mismo patrón que `StoreDenunciaRequest`).
* `app/Http/Controllers/SolicitudCompraController.php` (nuevo) — método `store()`.
* `routes/web.php` — `Route::post('/publicaciones/{publicacion}/solicitudes-compra', [SolicitudCompraController::class, 'store'])`.

Guía:

1. La tabla lleva `usuario_id` (comprador), `publicacion_id`, `mensaje` (nullable), `estado` enum `['pendiente', 'aceptada', 'rechazada']` default `pendiente`, timestamps.
2. En `StoreSolicitudCompraRequest::withValidator`, agregá la regla: si `$publicacion->usuario_id === auth()->id()`, error "No podés solicitar la compra de tu propia publicación."
3. `store()` crea la solicitud con `estado = 'pendiente'` y devuelve redirect/Inertia response según corresponda.

</details>

<details>
<summary>**KAN-116** [frontend] — Frontend Web: formulario "Solicitar compra" en el detalle de la publicación</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` o un `resources/js/Components/SolicitudCompraForm.tsx` aparte.

Guía:

1. Agregá un botón "Solicitar compra" en la página de detalle de la publicación (React + Inertia), que abre un formulario simple con el mensaje opcional.
2. Mostrá el error del backend si el usuario intenta solicitar su propia publicación.
3. Si ya existe una solicitud pendiente del usuario para esa publicación, mostrá el estado en vez del botón.

</details>

<details>
<summary>**KAN-120** [frontend] — Frontend Flutter: pantalla "Solicitar compra" en el detalle de la publicación</summary>

Archivos a crear/modificar (app Flutter):

* `lib/screens/publicacion_detalle_screen.dart` (o el widget de detalle de publicación que exista).
* `lib/widgets/solicitud_compra_form.dart` (nuevo).
* `lib/services/solicitud_compra_service.dart` (nuevo) — llamada a la API REST (`POST /api/publicaciones/{id}/solicitudes-compra`).

Guía:

1. Agregá un botón "Solicitar compra" en la pantalla de detalle, que abre un formulario (bottom sheet) con el mensaje opcional.
2. Mostrá el error que devuelve la API si el usuario intenta solicitar su propia publicación.
3. Si ya existe una solicitud pendiente del usuario para esa publicación, mostrá el estado en vez del botón.

Depende de que exista el endpoint de la API REST (ver Epic "API REST para la app móvil").

</details>

<details>
<summary>**KAN-124** [backend] — Tests: feature test de creación de solicitud de compra</summary>

Archivos a crear/modificar:

* `tests/Feature/SolicitudCompraTest.php` (nuevo).

Guía — casos a cubrir:

1. Un comprador puede crear una solicitud de compra sobre una publicación ajena (queda en estado "pendiente").
2. Un usuario NO puede crear una solicitud de compra sobre su propia publicación (422 con el mensaje esperado).
3. El mensaje opcional se guarda correctamente cuando se envía.

</details>

### KAN-135 — Ver y gestionar las solicitudes de compra que recibí

> Como productor, quiero ver las solicitudes de compra que recibí sobre mis publicaciones y aceptarlas o rechazarlas, para coordinar la entrega con el comprador o descartar las que no me interesan.
>
> Criterios de aceptación:
>
> * Solo veo las solicitudes de publicaciones que son mías.
> * Solo el dueño de la publicación (o un admin) puede aceptar/rechazar una solicitud.
> * Al aceptar o rechazar, la solicitud cambia de "pendiente" a "aceptada" o "rechazada" y no se puede volver a resolver.
> * No hay pago involucrado: aceptar la solicitud solo significa que ambas partes coordinan manualmente el resto (precio final, entrega).

<details>
<summary>**KAN-146** [backend] — Backend: endpoints para listar y resolver (aceptar/rechazar) solicitudes de compra recibidas</summary>

Archivos a crear/modificar:

* `app/Policies/SolicitudCompraPolicy.php` (nuevo) — método `resolve(User $user, SolicitudCompra $solicitud)`: `$user->esAdmin() || $user->id === $solicitud->publicacion->usuario_id`.
* `app/Http/Controllers/SolicitudCompraController.php` — métodos `index()` (listar las recibidas) y `resolve()` (aceptar/rechazar).
* `routes/web.php` — `GET /solicitudes-compra` (recibidas por el productor autenticado) y `PATCH /solicitudes-compra/{solicitud}` (cambiar estado).

Guía:

1. `index()` filtra `SolicitudCompra::whereHas('publicacion', fn ($q) => $q->where('usuario_id', auth()->id()))`.
2. `resolve()` usa `$this->authorize('resolve', $solicitud)` con la Policy nueva, y solo permite la transición si el estado actual es "pendiente".
3. Registrá `SolicitudCompraPolicy` en el service provider de policies (como las demás).

</details>

<details>
<summary>**KAN-151** [frontend] — Frontend Web: panel "Mis solicitudes recibidas" con aceptar/rechazar</summary>

Archivos a crear/modificar:

* `resources/js/Pages/SolicitudesCompra/Index.tsx` (nuevo).

Guía:

1. Página con el listado de solicitudes recibidas (publicación, comprador, mensaje, estado).
2. Botones "Aceptar"/"Rechazar" visibles solo mientras el estado es "pendiente".
3. Agregá el link a esta página en el menú/nav del productor.

</details>

<details>
<summary>**KAN-155** [frontend] — Frontend Flutter: pantalla "Mis solicitudes recibidas" con aceptar/rechazar</summary>

Archivos a crear/modificar (app Flutter):

* `lib/screens/solicitudes_recibidas_screen.dart` (nuevo).
* `lib/services/solicitud_compra_service.dart` — agregar métodos `listarRecibidas()` y `resolver(id, estado)`.

Guía:

1. Pantalla con lista de solicitudes recibidas (publicación, comprador, mensaje, estado).
2. Botones "Aceptar"/"Rechazar" visibles solo mientras el estado es "pendiente".
3. Agregá el acceso desde el menú principal de la app (solo visible si el usuario es productor).

Depende del endpoint de la API REST (ver Epic "API REST para la app móvil").

</details>

<details>
<summary>**KAN-160** [backend] — Tests: feature test de aceptar/rechazar solicitudes de compra</summary>

Archivos a crear/modificar:

* `tests/Feature/SolicitudCompraTest.php` (agregar casos a la clase creada en la Historia anterior).

Guía — casos a cubrir:

1. El dueño de la publicación puede aceptar o rechazar una solicitud pendiente.
2. Un usuario que no es dueño de la publicación (ni admin) NO puede resolver la solicitud (403).
3. Una solicitud ya resuelta no puede volver a cambiar de estado.
4. El listado de "recibidas" solo muestra solicitudes de publicaciones del usuario autenticado.

</details>

<details>
<summary>**KAN-250** [backend] — Backend: generar alertas al recibir y al responder una solicitud de compra</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/SolicitudCompraController.php` — en `store()` y `resolve()`, crear la alerta y mandar mail/push.
* `tests/Feature/SolicitudCompraTest.php` — agregar casos.

Guía:

1. En `store()`: alerta para el **productor** (dueño de la publicación), tipo `nueva_solicitud_compra`, mensaje tipo "Recibiste una solicitud de compra por '{titulo}'."
2. En `resolve()`: alerta para el **comprador** que hizo la solicitud, tipo `solicitud_compra_aceptada` o `solicitud_compra_rechazada`, mensaje tipo "Tu solicitud por '{titulo}' fue aceptada." / "...fue rechazada."
3. Mismo patrón que `ResenaController::store()` (KAN-73/76/187): `Alerta::create(...)`, después `NuevaAlertaMail` si `notificar_por_mail` no está en `false`, y `PushNotificationService::sendPush()`. Si se repite en varios lugares, se puede extraer a un método o servicio chico.
4. Tests: con `Mail::fake()`, verificar que al crear la solicitud el productor tiene 1 alerta del tipo correcto y que al aceptar/rechazar la tiene el comprador (y no el productor). Verificar que no se manda mail si `notificar_por_mail` es `false`.

Relacionada: KAN-116 (solicitar compra) y KAN-151 (panel de solicitudes recibidas), que hoy no le avisan a nadie. KAN-74/KAN-80 (campanita) las van a mostrar sin cambios en el frontend.

</details>

<details>
<summary>**KAN-251** [backend] — Backend: cantidad en la solicitud de compra y descontar el stock al aceptarla</summary>

Archivos a crear/modificar:

* `database/migrations/xxxx_add_cantidad_to_solicitudes_compra_table.php` (nuevo) — columna `cantidad` (unsignedInteger, default 1 para las solicitudes que ya existen).
* `app/Models/SolicitudCompra.php` — agregar `cantidad` a fillable y cast integer.
* `app/Http/Requests/StoreSolicitudCompraRequest.php` — validar `cantidad`.
* `app/Http/Controllers/SolicitudCompraController.php` — guardar la cantidad en `store()`, descontar en `resolve()` y mandarla en `index()`.
* `tests/Feature/SolicitudCompraTest.php` — agregar casos.

Guía:

1. `cantidad`: obligatoria, entero, mínimo 1 y como máximo el `stock` actual de la publicación (mensaje en castellano: "No hay stock suficiente: quedan X").
2. En `resolve()`, al **aceptar**: dentro de `DB::transaction()`, bloquear la publicación (`lockForUpdate()`), verificar que todavía alcance el stock (pudo cambiar desde que se pidió) y hacer `decrement('stock', cantidad)`. Si no alcanza, no aceptar y devolver error en `estado`. Rechazar no toca el stock.
3. Usar `update()`/`decrement()` sobre el modelo para que corra el `PublicacionObserver`: si el stock llega a 0 la publicación se pausa sola (KAN-180), no hay que hacerlo a mano.
4. `index()` tiene que mandar `cantidad` en cada solicitud para el panel.
5. Tests: no se puede pedir más que el stock; aceptar descuenta; rechazar no descuenta; aceptar con stock insuficiente da error y no cambia nada; al llegar a 0 la publicación queda pausada.

Relacionada: KAN-116 y KAN-151 (frontend de solicitar y gestionar solicitudes), KAN-180 (pausa automática sin stock), KAN-252 (campo cantidad en el formulario y el panel, depende de esta).

</details>

<details>
<summary>**KAN-252** [frontend] — Frontend Web: pedir la cantidad al solicitar una compra y mostrarla en el panel</summary>

Archivos a crear/modificar:

* `resources/js/Components/SolicitarCompra.tsx` — agregar el campo cantidad.
* `resources/js/Pages/SolicitudesCompra/Index.tsx` (panel de solicitudes recibidas, KAN-151) — mostrar la cantidad.

Guía:

1. En el modal de solicitar compra, campo numérico "Cantidad" con la unidad de la publicación al lado (ej: "3 kg"), mínimo 1 y máximo el stock disponible. Mostrar el subtotal estimado (cantidad × precio) como referencia.
2. Mostrar el error de validación del backend (ej: "No hay stock suficiente").
3. En el panel, mostrar la cantidad pedida en cada solicitud y el error si al aceptar ya no alcanza el stock.
4. Si la publicación se pausa al quedarse sin stock, el detalle ya lo maneja (no se puede solicitar).

Depende de: KAN-251 (cantidad en la solicitud y descuento de stock).

</details>

<details>
<summary>**KAN-275** [backend] — Backend: compartir la cantidad de solicitudes de compra pendientes del productor en todas las páginas</summary>

Archivos a crear/modificar:

* `app/Http/Middleware/HandleInertiaRequests.php` — sumar una prop compartida `solicitudes_pendientes`.
* `tests/Feature/SolicitudCompraTest.php` (o un test nuevo) — agregar casos.

Guía:

1. `solicitudes_pendientes` = cantidad de solicitudes de compra en estado `pendiente` sobre publicaciones del usuario logueado, **solo si es productor**; para compradores, admins e invitados, `0` o `null`.
2. Mismo patrón que `alertas_no_leidas` (KAN-248): una closure (`fn () => ...`) para que solo se calcule cuando hace falta. Consulta: `SolicitudCompra::where('estado', SolicitudCompra::ESTADO_PENDIENTE)->whereHas('publicacion', fn ($q) => $q->where('usuario_id', $user->id))->count()`.
3. Tests: el número coincide con las pendientes del productor; baja al aceptar o rechazar una; no cuenta las de publicaciones de otros productores; para un comprador da 0 o null.

Relacionada: KAN-248 (mismo patrón para las alertas), KAN-276 (número en "Solicitudes" del navbar).

</details>

<details>
<summary>**KAN-276** [frontend] — Frontend Web: número de solicitudes pendientes en "Solicitudes" del navbar y los menús</summary>

Archivos a crear/modificar:

* `resources/js/Layouts/AuthenticatedLayout.tsx` — link "Solicitudes" del navbar, "Solicitudes recibidas" del menú de usuario y del menú del celular.
* `resources/js/types/index.d.ts` — `solicitudes_pendientes` en `PageProps`.

Guía:

1. Leer `solicitudes_pendientes` de `usePage().props` (prop compartida de KAN-275).
2. Si es mayor a 0, mostrar un globito con el número al lado de "Solicitudes" (ámbar, como los avisos de pendientes del panel admin); con más de 9, "9+". Si es 0, no se muestra nada.
3. Agregarle al link un texto para lectores de pantalla, ej. "Solicitudes, 3 pendientes".
4. Mismo estilo que el número de la campanita (KAN-74), para que se vean iguales.

Depende de: KAN-275 (prop `solicitudes_pendientes`).

</details>

### KAN-168 — Contactar al productor por WhatsApp desde la publicación

> Como comprador, quiero contactar directamente al productor por WhatsApp desde la publicación o su perfil, para coordinar la compra sin depender de mensajería interna.
>
> Criterios de aceptación:
>
> * El link usa el formato deep-link `https://wa.me/<telefono>` con un mensaje precargado (ej. mencionando el título de la publicación).
> * El botón/link solo se muestra si el productor tiene el campo `telefono` cargado (ya existe en `User`, ver KAN-11/KAN-31/KAN-32).
> * No implica ningún endpoint backend nuevo: es un link directo que se arma en el frontend con datos que ya vienen en la respuesta de la publicación/perfil.

<details>
<summary>**KAN-178** [frontend] — Frontend Web: botón de contacto por WhatsApp en publicación y perfil</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` — botón de contacto.
* `resources/js/Components/WhatsappButton.tsx` (nuevo, reutilizable).

Guía:

1. Armá el link `https://wa.me/<telefono sin espacios ni guiones>?text=<mensaje precargado codificado>` mencionando el título de la publicación.
2. Mostrá el botón solo si `publicacion.productor.telefono` no es null/vacío.
3. Reusá el mismo componente en la página de perfil público del productor.

</details>

<details>
<summary>**KAN-182** [frontend] — Frontend Flutter: botón de contacto por WhatsApp en publicación y perfil</summary>

Archivos a crear/modificar (app Flutter):

* `lib/widgets/whatsapp_button.dart` (nuevo, reutilizable).
* `pubspec.yaml` — agregar dependencia `url_launcher`.

Guía:

1. Usá `url_launcher` para abrir `https://wa.me/<telefono>?text=<mensaje precargado>` mencionando el título de la publicación.
2. Mostrá el botón solo si el productor tiene `telefono` cargado.
3. Reusá el widget en la pantalla de detalle de publicación y en la de perfil del productor.

</details>

<details>
<summary>**KAN-183** [backend] — Tests: feature test que verifique la visibilidad del botón/link de WhatsApp</summary>

Archivos a crear/modificar:

* `tests/Feature/PublicacionTest.php` (agregar caso) o un test nuevo si no existe archivo de detalle.

Guía — casos a cubrir:

1. La respuesta del detalle de la publicación incluye el `telefono` del productor cuando está cargado.
2. Cuando el productor NO tiene `telefono` cargado, el campo viene null y el frontend no debería mostrar el botón (test a nivel de datos devueltos por el backend, no de UI).

</details>

<details>
<summary>**KAN-270** [backend] — Backend: no mandar el email del productor en el detalle de la publicación</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — en `show()`, el `load()` de `usuario`.
* `tests/Feature/PublicacionControllerTest.php` — agregar un caso.

Guía:

1. Hoy `show()` carga `'usuario:id,name,email,telefono,socio_validado'`. La pantalla no muestra el email, pero viaja en los datos de la página y cualquiera lo puede ver desde el navegador (es un dato privado).
2. Sacar `email` de ese `load()`. El teléfono queda porque lo usa el botón de WhatsApp (KAN-178).
3. Revisar si algún test esperaba el email (ej. el del detalle completo) y ajustarlo.
4. Test: el detalle de una publicación **no** incluye `publicacion.usuario.email` (`->missing(...)`).

Relacionada: KAN-178 (contacto por WhatsApp).

</details>

<details>
<summary>**KAN-271** [frontend] — Frontend Web: mostrar el teléfono en el detalle solo cuando no hay botón de WhatsApp</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Publicaciones/Show.tsx` — recuadro del productor.

Guía:

1. Hoy el recuadro muestra el número y, abajo, el botón "Escribir por WhatsApp": queda repetido.
2. Si `numeroWhatsapp(telefono)` (`utils/whatsapp.ts`) da un número válido, mostrar solo el botón de WhatsApp.
3. Si no (teléfono fijo o mal cargado), mostrar un link "Llamar al …" (`tel:`) para que el comprador no se quede sin forma de contacto.
4. Si no hay teléfono, como hoy: solo la solicitud de compra.

Relacionada: KAN-178 (botón de WhatsApp).

</details>

---

### KAN-255 — Ver las solicitudes de compra que envié

> Como comprador, quiero ver las solicitudes de compra que mandé y en qué estado están, para saber si el productor las aceptó y coordinar la entrega.
>
> Criterios de aceptación:
>
> * Hay una página "Mis solicitudes" accesible desde el menú de usuario.
> * Muestra cada solicitud que envié con la publicación, el productor, la cantidad, el mensaje, la fecha y el estado (pendiente, aceptada o rechazada).
> * Si fue aceptada, veo cómo contactar al productor (botón de WhatsApp si tiene).
> * Solo veo mis propias solicitudes.

<details>
<summary>**KAN-263** [backend] — Backend: listado de las solicitudes de compra enviadas por el comprador</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/SolicitudCompraController.php` — método nuevo `enviadas()`.
* `routes/web.php` — `GET /mis-solicitudes` con nombre `solicitudes-compra.enviadas`.

Guía:

1. Listar las solicitudes donde `usuario_id` es el usuario logueado, las más nuevas primero, paginadas.
2. Por cada una: `id`, `estado`, `mensaje`, `cantidad` (KAN-251), `created_at`, `updated_at`, la publicación (`id`, `titulo`, `unidad`, portada) y el productor (`id`, `name`, `socio_validado`).
3. El **teléfono del productor solo en las aceptadas** (para el botón de WhatsApp), con el mismo criterio que `index()` usa para mostrarle al productor el contacto del comprador.
4. Devolver `Inertia::render('SolicitudesCompra/Enviadas', [...])`.

Relacionada: KAN-146 (listado de las recibidas), tarea de frontend hermana de esta historia.

</details>

<details>
<summary>**KAN-264** [backend] — Tests: feature tests de las solicitudes de compra enviadas</summary>

Archivos a crear/modificar:

* `tests/Feature/SolicitudCompraTest.php` — agregar casos (o un archivo nuevo `SolicitudesEnviadasTest.php`).

Guía:

1. Un comprador ve solo las solicitudes que él envió, no las de otros.
2. Vienen con la publicación, el productor y el estado.
3. El teléfono del productor viene solo en las aceptadas; en las pendientes y rechazadas, no (`->missing(...)`).
4. Un invitado es redirigido al login.

</details>

<details>
<summary>**KAN-265** [frontend] — Frontend Web: página "Mis solicitudes" con el estado de cada solicitud enviada</summary>

Archivos a crear/modificar:

* `resources/js/Pages/SolicitudesCompra/Enviadas.tsx` (nuevo).
* `resources/js/Layouts/AuthenticatedLayout.tsx` — link "Mis solicitudes" en el menú de usuario.

Guía:

1. Lista de solicitudes con la portada y el título de la publicación (link al detalle), el productor, la cantidad con su unidad, el mensaje, la fecha ("hace 2 días") y el estado en un chip de color.
2. En las aceptadas, el botón de WhatsApp del productor (`WhatsappButton`, KAN-178).
3. Pestañas o filtro por estado si hay muchas; paginación con `Components/Paginacion.tsx`.
4. Estado vacío con link al marketplace.
5. Mismo estilo que el panel de solicitudes recibidas (KAN-151).

Depende de: la tarea de backend de esta historia (ruta `solicitudes-compra.enviadas`).

</details>

## Epic: KAN-87 — Panel de Administración

_Funciones del panel de administración web que no están cubiertas por otras Epics: inhabilitar/reactivar cuentas de usuario y ver métricas básicas de uso de la plataforma. La moderación de denuncias ya está cubierta por la Epic "Denuncias y moderación" (KAN-8) y la validación de socios del Consorcio por la Epic "Socios del Consorcio" — no se duplican acá._

### KAN-98 — Admin inhabilita o reactiva una cuenta de usuario

> Como administrador, quiero poder inhabilitar o reactivar la cuenta de un usuario, para poder actuar ante casos graves de mal uso de la plataforma (más allá de resolver una denuncia puntual).
>
> Criterios de aceptación:
>
> * Se agrega una columna `inhabilitado` (boolean, default false) a la tabla `users` vía una migración nueva (no se edita ninguna migración ya corrida).
> * Solo un usuario con `esAdmin()` puede inhabilitar/reactivar cuentas.
> * Un usuario inhabilitado no puede iniciar sesión (el intento de login debe fallar con un mensaje claro), aunque su contraseña sea correcta.
> * El admin no puede inhabilitarse a sí mismo (evita que se bloquee por error).

<details>
<summary>**KAN-111** [backend] — Backend: migración + endpoint para inhabilitar/reactivar usuarios y bloqueo de login</summary>

Archivos a crear/modificar:

* `database/migrations/[fecha]_add_inhabilitado_to_users_table.php` — migración nueva (no editar `2026_11_03_231016_add_rol_and_telefono_to_users_table.php`), agrega `inhabilitado` boolean default false.
* `app/Models/User.php` — agregar `inhabilitado` al cast/fillable y un método `estaInhabilitado()`.
* `app/Http/Controllers/Admin/UsuarioController.php` (nuevo) — método para togglear `inhabilitado`, valida que no sea el propio admin.
* `routes/web.php` — ruta dentro del grupo `['auth', 'admin']`.
* Lógica de bloqueo de login: puede ir en un Listener del evento `Illuminate\Auth\Events\Attempting` o `Login`, o chequeando en el `AuthenticatedSessionController` de Breeze — usar el patrón que ya tenga el proyecto para no duplicar lógica de auth.

Guía:

1. Si `inhabilitado = true`, el login debe fallar con un mensaje del tipo "Tu cuenta fue inhabilitada, contactá al administrador" (no un error genérico de credenciales).
2. El endpoint de toggle rechaza si `$request->user()->id === $usuario->id` (admin no se inhabilita a sí mismo).
3. No hace falta Policy nueva: alcanza con el middleware `admin` que ya existe.

</details>

<details>
<summary>**KAN-119** [frontend] — Frontend Web: listado de usuarios con acción inhabilitar/reactivar</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Usuarios/Index.tsx` (nuevo) — tabla de usuarios con su rol y estado (`inhabilitado`), y botón de inhabilitar/reactivar.

Guía:

1. El botón de inhabilitar/reactivar no debe mostrarse (o debe estar deshabilitado) en la fila del propio admin logueado.
2. Mostrar confirmación antes de inhabilitar (acción con impacto, el usuario deja de poder loguearse).
3. Esta página se accede solo desde el panel de admin (mismo patrón de layout que las páginas de KAN-35 y KAN-68).

</details>

<details>
<summary>**KAN-129** [backend] — Tests: feature test de inhabilitación/reactivación y bloqueo de login</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/InhabilitarUsuarioTest.php` (nuevo).

Casos a cubrir:

* Un admin puede inhabilitar a otro usuario (`inhabilitado` pasa a true).
* Un admin puede reactivar a un usuario inhabilitado.
* Un usuario `inhabilitado` no puede loguearse aunque la contraseña sea correcta.
* Un admin no puede inhabilitarse a sí mismo (la acción se rechaza).
* Un usuario no-admin recibe 403 al intentar inhabilitar a otro usuario.

</details>

<details>
<summary>**KAN-245** [backend] — Backend: listado de usuarios del panel admin con búsqueda y filtros</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Admin/UsuarioController.php` — agregar el método `index()` (hoy solo tiene `toggleInhabilitado()`).
* `routes/web.php` — `GET /admin/usuarios` dentro del grupo `['auth', 'admin']`, con el nombre `admin.usuarios.index` (es el nombre que ya espera el menú del panel en `resources/js/utils/panelAdmin.ts`, así la sección aparece sola).
* `resources/js/Pages/Admin/Usuarios/Index.tsx` — solo un borrador mínimo para que la ruta renderice; la página la hace el frontend (KAN-119).

Guía:

1. `index()`: todos los usuarios con `id`, `name`, `email`, `rol`, `telefono`, `socio_validado`, `inhabilitado` y `created_at`, más `withCount('publicaciones')`. Ordenados por más recientes, `paginate(20)->withQueryString()`.
2. Filtros: `?q=` (busca en nombre y email), `?rol=` (productor, comprador o admin) e `?inhabilitado=1|0`.
3. Devolver también `filtros` con lo que se aplicó (`$request->only(['q', 'rol', 'inhabilitado'])`), igual que `PublicacionModeracionController::index()`.
4. No mandar campos sensibles (contraseña, tokens, `fcm_token`).

Relacionada: KAN-119 (frontend del listado) y KAN-228 (dar o quitar admin desde el listado), que hoy están bloqueadas por esta ruta.

</details>

<details>
<summary>**KAN-246** [backend] — Tests: feature tests del listado de usuarios del panel admin</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/UsuariosListadoTest.php` (nuevo).

Guía:

1. Un admin ve el listado paginado con los datos esperados y el conteo de publicaciones de cada usuario.
2. La búsqueda `?q=` encuentra por nombre y por email.
3. Los filtros `?rol=` e `?inhabilitado=` devuelven solo los usuarios que corresponden.
4. La respuesta no incluye la contraseña ni el `fcm_token`.
5. Un productor o comprador recibe 403; un invitado es redirigido al login.

</details>

### KAN-140 — Ver métricas básicas de uso de la plataforma

> Como administrador, quiero ver métricas básicas de uso de la plataforma, para entender el estado general del Consorcio sin tener que revisar cada módulo por separado.
>
> Criterios de aceptación:
>
> * El panel muestra al menos: total de usuarios registrados, total de publicaciones activas, total de denuncias pendientes y total de reseñas.
> * Solo accesible para usuarios con `esAdmin()`.
> * Son conteos simples (no se pide un gráfico ni series históricas en esta primera versión).

<details>
<summary>**KAN-150** [backend] — Backend: endpoint de métricas básicas (solo admin)</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Admin/MetricasController.php` (nuevo) — método `index()`.
* `routes/web.php` — ruta dentro del grupo `['auth', 'admin']`.

Guía:

1. Devolver conteos vía `Model::count()` / `Model::where(...)->count()`: `User::count()`, `Publicacion::where('estado', Publicacion::ESTADO_ACTIVA)->count()`, `Denuncia::where('estado', Denuncia::ESTADO_PENDIENTE)->count()`, `Resena::count()`.
2. No hace falta Policy nueva, alcanza con el middleware `admin` ya existente.

</details>

<details>
<summary>**KAN-159** [frontend] — Frontend Web: dashboard de métricas en el panel de admin</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Metricas/Index.tsx` (nuevo) — tarjetas simples con cada conteo (usuarios, publicaciones activas, denuncias pendientes, reseñas).

Guía:

1. Alcanza con 4 tarjetas/cards, sin gráficos ni librerías de charts — es una primera versión.
2. Agregar el link a esta página en el layout/menú del panel de admin.

</details>

<details>
<summary>**KAN-165** [backend] — Tests: feature test del endpoint de métricas</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/MetricasTest.php` (nuevo).

Casos a cubrir:

* Un admin recibe los 4 conteos esperados, armando datos de prueba con factories (ej. crear 3 publicaciones activas y 1 pausada, verificar que el conteo de activas sea 3).
* Un usuario no-admin recibe 403 al pedir el endpoint.

</details>

### KAN-214 — Navegación del panel de administración

> Como administrador, quiero un panel con su propia navegación lateral, para moverme rápido entre las secciones de gestión y ver de un vistazo cuántas denuncias tengo pendientes.
>
> Criterios de aceptación:
>
> * Las páginas de admin usan un layout propio con sidebar fija: Resumen, Denuncias, Publicaciones, Usuarios, Socios, Categorías y Configuración.
> * El ítem "Denuncias" muestra un globito con la cantidad de denuncias pendientes (se actualiza en cada navegación).
> * La página de categorías existente (KAN-35) pasa a usar este layout.
> * Un usuario que no es admin no ve el panel ni sus links (el acceso lo sigue bloqueando el middleware `admin`).
> * En mobile, la sidebar se abre y cierra con un botón.

<details>
<summary>**KAN-218** [backend] — Backend: compartir el conteo de denuncias pendientes para el panel admin</summary>

Archivos a crear/modificar:

* `app/Http/Middleware/HandleInertiaRequests.php` — método `share()`.

Guía:

1. Agregar `'admin' => fn () => $request->user()?->esAdmin() ? ['denuncias_pendientes' => Denuncia::where('estado', Denuncia::ESTADO_PENDIENTE)->count()] : null`.
2. Que sea una función (lazy) para que solo se calcule cuando hay un admin logueado.

</details>

<details>
<summary>**KAN-219** [frontend] — Frontend: layout del panel de administración</summary>

Archivos a crear/modificar:

* `resources/js/Layouts/AdminLayout.tsx` (nuevo).
* `resources/js/Pages/Categorias/Index.tsx` — pasar a usar `AdminLayout`.
* `resources/js/Layouts/AuthenticatedLayout.tsx` — reemplazar el link "Categorías" por uno "Panel admin" (solo admins).
* `resources/js/types/index.d.ts` — tipo de la prop compartida `admin`.

Guía:

1. Sidebar azul profundo con los ítems de la historia; marcar el activo con `route().current(...)`. Los ítems cuyas páginas todavía no existan quedan ocultos o deshabilitados hasta que se implementen.
2. Globito en "Denuncias" con `usePage().props.admin.denuncias_pendientes` (no mostrarlo si es 0).
3. Abajo de la sidebar: "En alianza con" + logos; y el usuario logueado con acceso a cerrar sesión.
4. Responsive: en mobile la sidebar es un panel que se abre con un botón.
5. Animación de entrada de las tarjetas (aparición escalonada) con CSS, respetando `prefers-reduced-motion`.

</details>

<details>
<summary>**KAN-220** [backend] — Tests: feature test del conteo compartido en el panel admin</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/PanelAdminTest.php` (nuevo).

Guía:

1. Un admin recibe la prop `admin.denuncias_pendientes` con el número correcto (crear denuncias pendientes y resueltas con factories).
2. Un productor o comprador recibe `admin` => `null`.

</details>

### KAN-215 — Moderar publicaciones

> Como administrador, quiero ver todas las publicaciones de la plataforma y poder bloquear o eliminar las que no cumplen las reglas, para mantener el marketplace confiable.
>
> Criterios de aceptación:
>
> * Revisión posterior: las publicaciones se publican al instante (no hay aprobación previa).
> * El admin ve un listado de TODAS las publicaciones (cualquier estado), paginado, con buscador por título y filtros por estado y categoría.
> * El admin puede bloquear una publicación indicando un motivo obligatorio. Una publicación bloqueada no aparece en el catálogo ni en el listado público.
> * El dueño NO puede reactivar una publicación bloqueada (ni cambiarle el estado); solo el admin puede desbloquearla.
> * El dueño ve en el detalle de su publicación que está bloqueada y el motivo.
> * El admin puede eliminar una publicación, con confirmación.
> * Si el sistema de alertas (KAN-73) ya existe, el dueño recibe una alerta cuando su publicación es bloqueada o eliminada.

<details>
<summary>**KAN-221** [backend] — Backend: estado "bloqueada" y endpoints de moderación de publicaciones</summary>

Archivos a crear/modificar:

* `database/migrations/[fecha]_add_bloqueo_to_publicaciones_table.php` (nuevo): agregar `'bloqueada'` a los valores permitidos de la columna `estado`, y columnas `motivo_bloqueo` (text, nullable), `bloqueada_por` (foreignId `users`, nullable, `nullOnDelete`) y `bloqueada_at` (timestamp, nullable).
* `app/Models/Publicacion.php` — constante `ESTADO_BLOQUEADA`, fillable/casts y método `estaBloqueada()`.
* `app/Http/Requests/CambiarEstadoPublicacionRequest.php` y `UpdatePublicacionRequest.php` — que el dueño no pueda cambiar el estado de una bloqueada ni elegir `'bloqueada'`.
* `app/Observers/PublicacionObserver.php` — no reactivar por stock una publicación bloqueada.
* `app/Http/Controllers/Admin/PublicacionModeracionController.php` (nuevo) — `index()`, `bloquear()`, `desbloquear()`, `destroy()`.
* `app/Http/Requests/BloquearPublicacionRequest.php` (nuevo) — `motivo required|string|max:1000`.
* `routes/web.php` — rutas dentro del grupo `['auth', 'admin']`: `GET /admin/publicaciones`, `PATCH /admin/publicaciones/{publicacion}/bloquear`, `PATCH /admin/publicaciones/{publicacion}/desbloquear`, `DELETE /admin/publicaciones/{publicacion}`.

Guía:

1. No editar migraciones ya corridas. Para cambiar el enum en MySQL usar `->change()` en una migración nueva (verificar que también funcione en SQLite, que usa el CI).
2. `index()`: todas las publicaciones con `usuario:id,name` y `categoria:id,nombre`, filtros `?q=`, `?estado=`, `?categoria_id=`, `paginate(20)->withQueryString()`.
3. `bloquear()`: `estado = bloqueada` + `motivo` + `bloqueada_por` + `bloqueada_at`. `desbloquear()`: vuelve a `'pausada'` (para que el dueño decida cuándo reactivarla) y limpia los campos de bloqueo.
4. El listado público (`PublicacionController::index`) ya filtra por `estado activa`, así que las bloqueadas no aparecen; verificarlo.
5. `show()`: incluir `motivo_bloqueo` solo si quien mira es el dueño o un admin.
6. `destroy()`: borrar la publicación (y sus fotos, si KAN-204 ya está hecha).

</details>

<details>
<summary>**KAN-222** [frontend] — Frontend: sección de moderación de publicaciones en el panel admin</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Publicaciones/Index.tsx` (nuevo).
* `resources/js/Pages/Publicaciones/Show.tsx` — aviso de "bloqueada" con el motivo para el dueño/admin; ocultar al dueño los botones de estado si está bloqueada; botones de moderación para el admin.
* `resources/js/types/index.d.ts` — sumar `'bloqueada'` a los estados y los campos de bloqueo.

Guía:

1. Usar `AdminLayout` (Historia "Navegación del panel de administración").
2. Tabla con título, productor, categoría, estado (chip de color), fecha y acciones: Ver, Bloquear/Desbloquear, Eliminar.
3. Buscador y filtros por estado y categoría con `router.get` conservando los filtros; paginación.
4. Bloquear abre un modal con el motivo obligatorio; Eliminar abre un modal de confirmación.
5. Mostrar los mensajes flash de éxito/error.

</details>

<details>
<summary>**KAN-223** [backend] — Tests: feature tests de moderación de publicaciones</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/ModeracionPublicacionTest.php` (nuevo).

Guía:

1. Un admin ve todas las publicaciones (de cualquier estado) y los filtros funcionan.
2. Un admin bloquea con motivo; sin motivo da error de validación.
3. Una publicación bloqueada no aparece en el listado público.
4. El dueño no puede reactivar ni cambiar el estado de una bloqueada (403 o error de validación).
5. El observer no reactiva por stock una bloqueada.
6. Desbloquear la deja en `'pausada'`; eliminar la borra.
7. Un productor o comprador recibe 403 en todas las rutas `/admin/publicaciones`.

</details>

<details>
<summary>**KAN-249** [backend] — Backend: responder 404 al ver una publicación bloqueada si no es el dueño ni un admin</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/PublicacionController.php` — en `show()` (web).
* `app/Http/Controllers/Api/PublicacionController.php` — en `show()` (API), si también la devuelve.
* `tests/Feature/Admin/ModeracionPublicacionTest.php` — agregar casos.

Guía:

1. Si la publicación está `bloqueada` y quien la pide no es el dueño ni un admin, responder `abort(404)`. Hoy el listado ya la oculta, pero se puede seguir pidiendo por URL (un link viejo o compartido) y el backend la devuelve completa.
2. El frontend (KAN-222) ya muestra "Esta publicación no está disponible" en ese caso; con el 404 el dato directamente no viaja.
3. Tests: un comprador recibe 404 al pedir una bloqueada; el dueño y un admin la siguen viendo con el motivo.

Relacionada: KAN-222 (moderación en el panel admin).

</details>

### KAN-216 — Configuración de la plataforma

> Como administrador, quiero editar los datos de contacto y algunas reglas de la plataforma desde el panel, para mantenerlos actualizados sin tener que tocar el código.
>
> Criterios de aceptación:
>
> * El admin puede editar los datos de contacto: email de soporte, WhatsApp de soporte, dirección, y links a Facebook e Instagram. Se muestran en el footer y en la landing.
> * El admin puede editar reglas de la plataforma que hoy están fijas en el código: cantidad mínima de publicaciones activas para mostrar el precio sugerido (hoy 3), cantidad máxima de fotos por publicación (6) y tamaño máximo por foto (2 MB).
> * Los cambios se aplican sin redeploy y los valores se validan (por ejemplo, el máximo de fotos entre 1 y 10).
> * Si nunca se configuró un valor, se usa el valor por defecto actual.
> * Solo los admins pueden ver y editar la configuración.

<details>
<summary>**KAN-224** [backend] — Backend: tabla de configuración, lectura con valores por defecto y endpoint de edición</summary>

Archivos a crear/modificar:

* `database/migrations/[fecha]_create_configuraciones_table.php` (nuevo): `id`, `clave` (string, unique), `valor` (text, nullable), timestamps.
* `app/Models/Configuracion.php` (nuevo, `protected $table = 'configuraciones'`) con un método estático `obtener($clave, $porDefecto)` cacheado (`Cache::rememberForever`, que se limpia al guardar).
* `config/agroconnect.php` (nuevo) — valores por defecto de cada clave.
* `app/Http/Controllers/Admin/ConfiguracionController.php` (nuevo) — `edit()` y `update()`.
* `app/Http/Requests/UpdateConfiguracionRequest.php` (nuevo) — validación de cada campo (email, URL, enteros con rangos).
* `app/Models/Publicacion.php` — `calcularPrecioPromedioPorCategoria()` usa `Configuracion::obtener('precio_sugerido_minimo', 3)` en vez del 3 fijo.
* Reglas de fotos (KAN-204, si ya existe) — leer el máximo de fotos y el tamaño desde `Configuracion`.
* `app/Http/Middleware/HandleInertiaRequests.php` — compartir los datos de contacto (públicos) para el footer y la landing.
* `routes/web.php` — `GET` y `PUT` `/admin/configuracion` en el grupo `['auth', 'admin']`.

Guía:

1. Claves sugeridas: `contacto_email`, `contacto_whatsapp`, `contacto_direccion`, `red_facebook`, `red_instagram`, `precio_sugerido_minimo`, `fotos_maximo`, `fotos_tamano_max_kb`.
2. Compartir solo los datos de contacto; las reglas no hace falta mandarlas a todas las páginas.
3. Que todo siga funcionando si la tabla está vacía (valores por defecto de `config/agroconnect.php`).

</details>

<details>
<summary>**KAN-225** [frontend] — Frontend: página de configuración en el panel admin</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Configuracion/Edit.tsx` (nuevo).
* Footer/landing: usar los datos de contacto compartidos (si la landing KAN-201 ya existe).

Guía:

1. Usar `AdminLayout`. Formulario en dos bloques: "Datos de contacto" y "Reglas de la plataforma", con una ayuda corta debajo de cada regla explicando qué afecta.
2. `useForm` + `put(route(...))` + `InputError` por campo + mensaje de guardado.

</details>

<details>
<summary>**KAN-226** [backend] — Tests: feature tests de configuración</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/ConfiguracionTest.php` (nuevo).

Guía:

1. Un admin puede ver y guardar la configuración; los valores inválidos dan error de validación.
2. Sin configuración guardada se usan los valores por defecto.
3. Cambiar `precio_sugerido_minimo` cambia el resultado del endpoint de precio sugerido (KAN-126).
4. Un productor o comprador recibe 403.

</details>

### KAN-217 — Gestionar administradores

> Como administrador, quiero dar o quitar el rol de administrador a otros usuarios, para sumar personas del Consorcio a la moderación sin depender de la consola.
>
> Criterios de aceptación:
>
> * Desde el listado de usuarios del panel (KAN-119) el admin puede hacer admin a un usuario o quitarle el rol de admin, con confirmación.
> * Al quitar el rol de admin, el usuario vuelve al rol que elija el admin (productor o comprador).
> * Un admin no puede quitarse el rol a sí mismo.
> * Siempre tiene que quedar al menos un administrador en la plataforma.
> * Solo los admins pueden hacerlo.

<details>
<summary>**KAN-227** [backend] — Backend: endpoint para cambiar el rol de un usuario</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/Admin/UsuarioController.php` (el de KAN-111; crearlo si todavía no existe) — método `cambiarRol()`.
* `app/Http/Requests/CambiarRolUsuarioRequest.php` (nuevo) — `rol required|in:admin,productor,comprador`.
* `routes/web.php` — `PATCH /admin/usuarios/{usuario}/rol` en el grupo `['auth', 'admin']`.

Guía:

1. Rechazar si el admin intenta cambiarse el rol a sí mismo.
2. Rechazar si el cambio dejaría la plataforma sin ningún admin.
3. Redirigir con flash de éxito o error.

</details>

<details>
<summary>**KAN-228** [frontend] — Frontend: acción de dar/quitar admin en el listado de usuarios</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Admin/Usuarios/Index.tsx` (el de KAN-119).

Guía:

1. Mostrar el rol de cada usuario con un chip.
2. Botón "Hacer admin" / "Quitar admin" (no se muestra en la fila del propio admin logueado). Al quitar, un modal para elegir el nuevo rol (productor o comprador) y confirmar.
3. Mostrar los mensajes flash.

</details>

<details>
<summary>**KAN-229** [backend] — Tests: feature tests de gestión de administradores</summary>

Archivos a crear/modificar:

* `tests/Feature/Admin/GestionAdministradoresTest.php` (nuevo).

Guía:

1. Un admin puede hacer admin a un productor y quitárselo eligiendo el nuevo rol.
2. Un admin no puede quitarse el rol a sí mismo.
3. No se puede quitar el último admin.
4. Un productor o comprador recibe 403.

</details>

---

## Epic: KAN-93 — Inteligencia y notificaciones avanzadas

_Matching y sugerencia de productos/productores relevantes (priorizando socios del Consorcio), precio sugerido/promedio por categoría y zona, y notificaciones push (Firebase Cloud Messaging) ante alertas de disponibilidad. Son reglas de negocio y agregaciones sobre los datos existentes (no un modelo de IA/ML entrenado), tal como lo describe el plan de trabajo en la sección "Inteligencia y Notificaciones". Algunas Historias dependen de las Epics de Geolocalización (zona/ubicación) y Socios del Consorcio (socio_validado)._

### KAN-103 — Ver el precio sugerido/promedio al crear una publicación

> Como productor, quiero ver un precio sugerido/promedio al crear una publicación, para tener una referencia y no publicar muy por fuera del precio de mercado de la zona.
>
> Criterios de aceptación:
>
> * Al elegir una categoría en el formulario de creación, se muestra el precio promedio de las publicaciones activas de esa categoría (campo `precio` de `publicaciones` con `estado = activa`).
> * Al principio el promedio es general (todas las zonas); cuando exista la columna de ubicación (Epic de Geolocalización), el promedio se recalcula por categoría + zona cercana.
> * Si no hay suficientes publicaciones activas de esa categoría para calcular un promedio representativo, no se muestra el dato (en vez de mostrar 0 o un valor engañoso).

<details>
<summary>**KAN-126** [backend] — Backend: endpoint que calcule el precio promedio por categoría (y zona a futuro)</summary>

**Archivos a crear/modificar:**

* `app/Http/Controllers/PublicacionController.php` (agregar un método, ej. `precioSugerido(Categoria $categoria)`)
* `routes/web.php` (agregar la ruta, ej. `GET /categorias/{categoria}/precio-sugerido`)

**Guía:**

1. Hagan una query de agregación sobre `publicaciones`: `Publicacion::where('categoria_id', $categoria->id)->where('estado', Publicacion::ESTADO_ACTIVA)->avg('precio')`.
2. Definan un mínimo de publicaciones activas (ej. 3) para considerar el promedio representativo; si no se llega al mínimo, devuelvan `null` en vez de un número engañoso.
3. Dejen el cálculo en un método aparte (ej. un método estático en `Publicacion` o un pequeño servicio) para poder agregarle el filtro por zona después, sin tener que tocar el controller de nuevo, cuando exista la columna de ubicación de la Epic de Geolocalización.
4. Devuelvan JSON simple: `{ "precio_promedio": 123.45 }` o `{ "precio_promedio": null }`.

</details>

<details>
<summary>**KAN-132** [frontend] — Frontend Web: mostrar el precio sugerido en el formulario de creación de publicación</summary>

**Archivos a crear/modificar:**

* `resources/js/Pages/Publicaciones/Create.tsx` (la página del formulario de creación, Historia KAN-14/Subtask KAN-41)

**Guía:**

1. Cuando el usuario elija una categoría en el formulario, hagan un fetch al endpoint de KAN-126 (`GET /categorias/{id}/precio-sugerido`).
2. Si devuelve un valor, muéstrenlo como texto de ayuda debajo del campo precio, ej: "Precio promedio actual en esta categoría: $X" (no autocompleten el campo, es solo una referencia).
3. Si devuelve `null`, no muestren nada (no hay suficientes datos todavía).

</details>

<details>
<summary>**KAN-137** [frontend] — Frontend Flutter: mostrar el precio sugerido en la pantalla de publicar</summary>

**Archivos a crear/modificar:**

* `mobile/lib/features/publicaciones/presentation/publicar_screen.dart` (o la ruta equivalente una vez que exista el proyecto Flutter — ver Epic de API REST para el setup inicial del proyecto)

**Guía:**

1. Mismo criterio que la versión Web (ver Subtask hermana): al elegir categoría, llamar al endpoint `GET /categorias/{id}/precio-sugerido` de la API REST (Epic "API REST (Sanctum) para la app móvil") y mostrar el precio promedio como texto de ayuda debajo del campo de precio.
2. Si la API devuelve `null`, no mostrar nada.
3. Esta task depende de que ya exista el scaffold de la app Flutter y el cliente HTTP configurado contra la API (ver Epic de API REST).

</details>

<details>
<summary>**KAN-142** [backend] — Tests: feature test del cálculo del precio promedio</summary>

**Archivos a crear/modificar:**

* `tests/Feature/PrecioSugeridoTest.php` (crear)

**Guía:**

1. Casos a cubrir (estilo Pest, igual que `tests/Feature/Policies/PublicacionPolicyTest.php`):

    * Con suficientes publicaciones activas de una categoría (igual o por encima del mínimo definido), el endpoint devuelve el promedio correcto — armen el escenario con `Publicacion::factory()->count(n)->create([...])` y calculen el promedio esperado a mano para comparar con `assertJson`.
    * Con menos publicaciones que el mínimo, devuelve `precio_promedio: null`.
    * Las publicaciones con `estado` distinto de `activa` no entran en el cálculo.

</details>

### KAN-109 — Recibir productos y productores sugeridos

> Como comprador, quiero ver publicaciones y productores sugeridos relevantes para mí, para descubrir oferta sin tener que buscar todo manualmente.
>
> Criterios de aceptación:
>
> * El listado/home incluye una sección de "sugeridos para vos" con publicaciones de categorías que el usuario ya vio o marcó como favorita antes.
> * Entre publicaciones igualmente relevantes, se priorizan las de productores con `socio_validado = true` (depende de la Epic Socios del Consorcio; mientras esa columna no exista, la priorización simplemente no aplica ningún orden extra).
> * Si el usuario no tiene historial de navegación/favoritos todavía, se muestran publicaciones activas recientes como fallback (no una sección vacía).

<details>
<summary>**KAN-164** [backend] — Backend: endpoint de publicaciones sugeridas con priorización de socios</summary>

**Archivos a crear/modificar:**

* `app/Http/Controllers/PublicacionController.php` (agregar método `sugeridas()`)
* `routes/web.php` (agregar ruta, ej. `GET /publicaciones/sugeridas`)

**Guía:**

1. Para el MVP de esta lógica, usen como "interés" del usuario las categorías de las publicaciones que ya vio o favoriteó (si la Epic de Favoritos aún no está lista, usen de fallback las categorías de sus propias reseñas o, si no tiene ninguna, salten directo al paso 3).
2. Trabajen con `Publicacion::whereIn('categoria_id', $categoriasDeInteres)->where('estado', Publicacion::ESTADO_ACTIVA)`.
3. Si el usuario no tiene historial (sin reseñas, sin favoritos todavía), devuelvan como fallback las publicaciones activas más recientes (`orderBy('created_at', 'desc')`), para no devolver una lista vacía.
4. Cuando exista la columna `socio_validado` en `users` (Epic Socios del Consorcio), agreguen un `orderByDesc` que priorice las publicaciones de productores con `socio_validado = true` sobre el resto — documenten este paso como pendiente de esa Epic si todavía no se hizo.
5. Paginen el resultado (`paginate()`), no devuelvan todo sin límite.

</details>

<details>
<summary>**KAN-170** [frontend] — Frontend Web: sección "sugeridos para vos" en el listado/home</summary>

**Archivos a crear/modificar:**

* `resources/js/Pages/Publicaciones/Index.tsx` (la página de listado, Historia KAN-17/Subtask KAN-50)

**Guía:**

1. Agreguen una sección arriba del listado general que consuma el endpoint de la Subtask de backend hermana (`GET /publicaciones/sugeridas`).
2. Reutilicen el mismo componente de tarjeta de publicación que ya usa el listado general, no inventen uno nuevo.
3. Si la sección viene vacía por algún motivo, simplemente no la muestren (no dejen un título "Sugeridos para vos" sin contenido abajo).

</details>

<details>
<summary>**KAN-174** [frontend] — Frontend Flutter: sección "sugeridos para vos" en la pantalla de inicio</summary>

**Archivos a crear/modificar:**

* `mobile/lib/features/publicaciones/presentation/inicio_screen.dart` (o la ruta equivalente — ver Epic de API REST para el setup inicial del proyecto Flutter)

**Guía:**

1. Mismo criterio que la versión Web (ver Subtask hermana): consumir `GET /publicaciones/sugeridas` de la API REST y mostrar la sección arriba del listado general de la pantalla de inicio.
2. Reutilicen el widget de tarjeta de publicación que ya se use en el listado general de la app, no dupliquen el diseño.
3. Depende de que exista el scaffold de Flutter y el cliente HTTP contra la API (Epic de API REST).

</details>

<details>
<summary>**KAN-177** [backend] — Tests: feature test de la lógica de sugerencia y priorización de socios</summary>

**Archivos a crear/modificar:**

* `tests/Feature/PublicacionesSugeridasTest.php` (crear)

**Guía:**

1. Casos a cubrir (estilo Pest):

    * Un usuario sin historial recibe como sugeridas las publicaciones activas más recientes (fallback), no una lista vacía.
    * Un usuario con historial en una categoría recibe sugeridas principalmente de esa categoría.
    * Las publicaciones con `estado` distinto de `activa` nunca aparecen entre las sugeridas.
    * (Cuando ya exista `socio_validado` de la Epic Socios del Consorcio) entre dos publicaciones igualmente relevantes, la del productor socio aparece primero — si esa columna todavía no existe al momento de escribir este test, dejen el caso comentado con un TODO explícito en vez de borrarlo.

</details>

### KAN-114 — Recibir notificaciones push ante nuevo stock disponible

> Como usuario de la app Flutter, quiero recibir una notificación push cuando haya nuevo stock disponible de algo que me interesa, para enterarme sin tener la app abierta.
>
> Criterios de aceptación:
>
> * La app Flutter registra el token de dispositivo (Firebase Cloud Messaging) del usuario al iniciar sesión.
> * Cuando se genera una Alerta in-app (el mismo punto que ya dispara KAN-73), si el usuario tiene un token FCM registrado, también se le envía la notificación push con el mismo mensaje.
> * Esto es exclusivo de la app Flutter: la versión Web sigue notificando solo con la campanita (KAN-74) y el mail (KAN-76), no hay push en el navegador.
> * Si el envío del push falla (token inválido, usuario desinstaló la app), no debe romper el flujo que generó la Alerta — se loguea el error y se sigue.

<details>
<summary>**KAN-187** [backend] — Backend: registrar token FCM y enviar push al generarse una Alerta</summary>

**Archivos a crear/modificar:**

* `database/migrations/[fecha]_add_fcm_token_to_users_table.php` (crear: columna `fcm_token` nullable en `users`)
* `app/Http/Controllers/Api/PushTokenController.php` (crear: endpoint `POST /api/push-token` para que la app guarde/actualice su token)
* El punto donde hoy se crea la Alerta (ver KAN-73) — agregar el envío del push ahí mismo o en un Listener si KAN-73 usó Observer/Event

**Guía:**

1. Agreguen la columna `fcm_token` (string, nullable) a `users` con una migración nueva — no toquen la de `2026_11_03_231016_add_rol_and_telefono_to_users_table.php`, que ya corrió.
2. El endpoint de registro de token va en las rutas de `routes/api.php` (requiere que ya exista la Epic de API REST con Sanctum, porque este endpoint lo llama la app Flutter autenticada).
3. Después de crear la Alerta (mismo punto que KAN-73), si `$user->fcm_token` no es null, llamen a un servicio simple (ej. `app/Services/PushNotificationService.php`) que haga el POST a la API de Firebase Cloud Messaging con el `mensaje` de la Alerta.
4. Envuelvan el envío en un `try/catch`: si falla, lo loguean (`Log::warning(...)`) pero no relanzan la excepción — la Alerta in-app ya se creó bien y eso no debe romperse por un error de push.
5. La clave/credencial de Firebase va en `.env` (ej. `FCM_SERVER_KEY`), nunca hardcodeada.

</details>

<details>
<summary>**KAN-190** [frontend] — Frontend Flutter: integrar Firebase Messaging y registrar el token</summary>

**Archivos a crear/modificar:**

* `mobile/pubspec.yaml` (agregar dependencia `firebase_messaging`)
* `mobile/lib/core/push/push_service.dart` (crear)

**Guía:**

1. Configuren el proyecto Flutter con Firebase (requiere crear el proyecto en la consola de Firebase y bajar el `google-services.json` para Android — coordinar con el equipo quién lo hace, porque la cuenta de Firebase queda asociada a esa persona).
2. Al iniciar sesión en la app, pidan el token FCM (`FirebaseMessaging.instance.getToken()`) y mándenlo al backend con `POST /api/push-token`.
3. Si el token cambia (`onTokenRefresh`), vuelvan a mandarlo.
4. Manejen la recepción de la notificación en foreground y background según la documentación de `firebase_messaging` para mostrarla como notificación del sistema.
5. Depende de que ya exista el scaffold del proyecto Flutter y el cliente HTTP autenticado contra la API (Epic de API REST).

</details>

<details>
<summary>**KAN-194** [backend] — Tests: feature test del envío de push ante la Alerta (Http::fake)</summary>

**Archivos a crear/modificar:**

* `tests/Feature/PushNotificationTest.php` (crear)

**Guía:**

1. Usen `Http::fake()` para interceptar la llamada a la API de Firebase (no llamen a Firebase real en los tests).
2. Casos a cubrir (estilo Pest):

    * Si el usuario tiene `fcm_token` seteado y se genera el evento que dispara la Alerta, se hace la llamada HTTP esperada (`Http::assertSent(...)`).
    * Si el usuario NO tiene `fcm_token`, no se intenta ningún envío.
    * Si `Http::fake()` simula una respuesta de error de Firebase, el test verifica que la Alerta in-app de todas formas se creó en la base (el fallo del push no debe afectar lo demás) — usen `assertDatabaseHas('alertas', [...])`.

</details>

---

## Epic: KAN-198 — Landing y presencia institucional

_Página pública (ruta "/") que presenta AgroConnect a visitantes sin cuenta: qué es, cómo funciona y quién lo respalda (el Consorcio), antes de que decidan registrarse._

### KAN-199 — Conocer AgroConnect en la landing pública antes de registrarme

> Como visitante (sin cuenta), quiero conocer qué es AgroConnect, cómo funciona y quién lo respalda, para decidir si me registro como productor o comprador.
>
> Criterios de aceptación:
>
> * La ruta "/" muestra la landing a cualquier visitante, sin login (reemplaza la página Welcome de Breeze).
> * Secciones, en este orden: Hero (título "El campo se conecta directo con quien lo necesita", con la palabra "conecta" en tipografía manuscrita, foto del productor y CTA "Ingresar"/"Crear cuenta"); franja con números reales (productores, publicaciones activas y categorías) con contador animado; mapa ilustrado de la zona (Laguna Naineck, Clorinda, Formosa Capital) con pines decorativos por zona; "Cómo funciona" en 3 pasos; "El Consorcio" (quiénes son y logos del Consorcio y del plan provincial); "Para productores / Para compradores" (beneficios por público, sin testimonios); Preguntas frecuentes; CTA final y footer con logos.
> * Privacidad: la landing no muestra ubicaciones reales ni datos personales de ningún usuario.
> * Los números de la franja salen de la base de datos; no se muestran cifras inventadas.
> * Animaciones de aparición al hacer scroll, elementos flotantes y contador, hechas sin librerías externas; si el usuario tiene activado "reducir movimiento" (prefers-reduced-motion) no se anima nada.
> * Responsive (mobile, tablet y escritorio).
> * Identidad visual: se mantiene la paleta del mockup (azules, ámbar en los CTA, dorado del logo). Tipografía: títulos Bricolage Grotesque, texto Manrope, acento manuscrito Caveat Brush.
> * Si el usuario ya inició sesión, los CTA de la landing llevan a "/publicaciones" en vez de login/registro.

<details>
<summary>**KAN-200** [backend] — Backend: ruta pública "/" y datos de la landing</summary>

Archivos a crear/modificar:

* `app/Http/Controllers/LandingController.php` (nuevo) — método `index()`.
* `routes/web.php` — reemplazar el closure de "/" que renderiza Welcome por `[LandingController::class, 'index']` (nombre de ruta: `landing`), fuera del grupo `auth`.

Guía:

1. `index()` renderiza `Inertia::render('Landing', [...])` con: `'estadisticas' => ['productores' => User::where('rol', User::ROL_PRODUCTOR)->count(), 'publicaciones_activas' => Publicacion::where('estado', Publicacion::ESTADO_ACTIVA)->count(), 'categorias' => Categoria::count()]`, más `'canLogin'` y `'canRegister'` como tenía Welcome.
2. No exponer ningún dato personal (nombres, emails, teléfonos ni lat/lng).
3. Opcional: cachear las estadísticas unos minutos (`Cache::remember`) para no contar en cada visita.

</details>

<details>
<summary>**KAN-201** [frontend] — Frontend: página Landing con secciones y animaciones</summary>

Archivos a crear/modificar:

* `resources/js/Pages/Landing.tsx` (nuevo).
* `resources/js/Components/Landing/` (nuevo, un componente por sección si conviene: Hero, Estadisticas, MapaZona, ComoFunciona, Consorcio, Beneficios, PreguntasFrecuentes, Footer).
* `resources/images/landing/` (nuevo) — fotos propias del proyecto y logos (AgroConnect, Consorcio, plan provincial).
* `resources/views/app.blade.php` y `tailwind.config.js` — cargar las fuentes (Bricolage Grotesque, Manrope, Caveat Brush) y definir colores/fuentes como tokens del theme. Esto es configuración: acordarlo con el equipo.

Guía:

1. Seguir la estructura de secciones de los criterios de aceptación; usar los textos y la paleta del mockup.
2. Animaciones sin librerías: aparición al hacer scroll con `IntersectionObserver` + clases CSS (con aparición escalonada en las tarjetas), elementos flotantes con `@keyframes`, contador animado con `requestAnimationFrame`, trazos SVG dibujados con `stroke-dashoffset`.
3. Respetar `prefers-reduced-motion` (sin animaciones) y dejar todo el contenido visible aunque el JavaScript no haya corrido.
4. El mapa es una ilustración (SVG/imagen) con pines decorativos por zona; no consumir `/publicaciones/mapa`.
5. Los CTA cambian según `auth.user`: sin sesión → login/registro; con sesión → `/publicaciones`.
6. Borrar `resources/js/Pages/Welcome.tsx` cuando la landing la reemplace.

</details>

<details>
<summary>**KAN-202** [backend] — Tests: feature test de la landing pública</summary>

Archivos a crear/modificar:

* `tests/Feature/LandingTest.php` (nuevo).

Guía:

1. Testear que "/" responde 200 sin login y renderiza el componente `Landing` (`assertInertia`).
2. Testear que `estadisticas` trae los conteos correctos (crear productores, compradores y publicaciones en distintos estados con factories, y verificar que solo cuenta productores y publicaciones activas).
3. Testear que la respuesta no incluye datos personales (ni emails ni lat/lng).

</details>

---
