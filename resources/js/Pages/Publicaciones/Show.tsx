import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Categoria, PageProps, Publicacion, Resena, User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

// Lo que manda PublicacionController::show() (con las relaciones cargadas).
type PublicacionDetalle = Publicacion & {
    categoria: Pick<Categoria, 'id' | 'nombre'>;
    usuario: Pick<User, 'id' | 'name' | 'telefono'>;
    resenas: Resena[];
};

const formatoPrecio = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
});

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const TEXTO_ESTADO: Record<string, string> = {
    pausada: 'Esta publicación está pausada por el productor.',
    vendida: 'Esta publicación ya fue vendida.',
};

export default function Show({
    publicacion,
    puntuacion_promedio,
}: PageProps<{
    publicacion: PublicacionDetalle;
    puntuacion_promedio: number | null;
}>) {
    const { auth, flash } = usePage().props;

    // Misma regla que PublicacionPolicy::update. Es solo para mostrar el botón:
    // si alguien entra a /editar sin permiso, la ruta igual responde 403.
    const puedeEditar =
        auth.user.id === publicacion.usuario_id || auth.user.rol === 'admin';

    const avisoEstado = publicacion.estado
        ? TEXTO_ESTADO[publicacion.estado]
        : undefined;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {publicacion.titulo}
                </h2>
            }
        >
            <Head title={publicacion.titulo} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    {flash.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    {avisoEstado && (
                        <div className="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                            {avisoEstado}
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Datos de la publicación */}
                        <section className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg lg:col-span-2">
                            <span className="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                                {publicacion.categoria.nombre}
                            </span>

                            <div className="flex flex-wrap items-baseline gap-x-2">
                                <p className="text-3xl font-semibold text-gray-900">
                                    {formatoPrecio.format(
                                        Number(publicacion.precio),
                                    )}
                                </p>
                                <p className="text-gray-600">
                                    por {publicacion.unidad}
                                </p>
                            </div>

                            <p className="text-sm text-gray-600">
                                {publicacion.stock > 0 ? (
                                    <>
                                        Stock disponible:{' '}
                                        <strong className="text-gray-900">
                                            {publicacion.stock.toLocaleString(
                                                'es-AR',
                                            )}{' '}
                                            {publicacion.unidad}
                                        </strong>
                                    </>
                                ) : (
                                    <strong className="text-red-700">
                                        Sin stock disponible
                                    </strong>
                                )}
                            </p>

                            <div>
                                <h3 className="text-sm font-medium text-gray-900">
                                    Descripción
                                </h3>
                                {/* whitespace-pre-line respeta los saltos de línea que escribió el productor */}
                                <p className="mt-1 whitespace-pre-line text-gray-700">
                                    {publicacion.descripcion}
                                </p>
                            </div>

                            {puedeEditar && (
                                // Acciones del dueño (o admin). Acá van también los botones
                                // de pausar / marcar como vendida (KAN-16).
                                <div className="flex flex-wrap gap-3 border-t border-gray-100 pt-4">
                                    <Link
                                        href={route(
                                            'publicaciones.edit',
                                            publicacion.id,
                                        )}
                                        className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Editar
                                    </Link>
                                </div>
                            )}
                        </section>

                        {/* Contacto del productor */}
                        <aside className="h-fit space-y-2 bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-sm font-medium text-gray-500">
                                Productor
                            </h3>
                            <p className="text-lg font-semibold text-gray-900">
                                {publicacion.usuario.name}
                            </p>

                            {publicacion.usuario.telefono ? (
                                <a
                                    href={`tel:${publicacion.usuario.telefono}`}
                                    className="block text-indigo-600 hover:underline"
                                >
                                    {publicacion.usuario.telefono}
                                </a>
                            ) : (
                                <p className="text-sm text-gray-500">
                                    No cargó un teléfono de contacto.
                                </p>
                            )}
                            {/* Acá va el botón de WhatsApp (KAN-178). */}
                        </aside>
                    </div>

                    {/* Reseñas */}
                    <section className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-center gap-3">
                            <h3 className="text-lg font-medium text-gray-900">
                                Reseñas
                            </h3>

                            {puntuacion_promedio !== null && (
                                <div className="flex items-center gap-2 text-sm text-gray-600">
                                    <Estrellas valor={puntuacion_promedio} />
                                    <span>
                                        {puntuacion_promedio.toFixed(1)} de 5 (
                                        {publicacion.resenas.length})
                                    </span>
                                </div>
                            )}
                        </div>

                        {publicacion.resenas.length === 0 ? (
                            <p className="mt-4 text-sm text-gray-600">
                                Todavía no hay reseñas para esta publicación.
                            </p>
                        ) : (
                            <ul className="mt-4 divide-y divide-gray-100">
                                {publicacion.resenas.map((resena) => (
                                    <li key={resena.id} className="py-4">
                                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <Estrellas
                                                valor={resena.puntuacion}
                                            />
                                            <span className="text-sm font-medium text-gray-900">
                                                {resena.usuario.name}
                                            </span>
                                            <span className="text-xs text-gray-500">
                                                {formatoFecha.format(
                                                    new Date(resena.created_at),
                                                )}
                                            </span>
                                        </div>
                                        {resena.comentario && (
                                            <p className="mt-2 whitespace-pre-line text-sm text-gray-700">
                                                {resena.comentario}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                        {/* Acá va el formulario para dejar una reseña (Epic Reseñas). */}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Muestra 5 estrellas, pintando las que corresponden al valor (redondeado).
function Estrellas({ valor }: { valor: number }) {
    const llenas = Math.round(valor);

    return (
        <span
            role="img"
            aria-label={`${valor.toFixed(1)} de 5 estrellas`}
            className="text-base leading-none"
        >
            {[1, 2, 3, 4, 5].map((n) => (
                <span
                    key={n}
                    aria-hidden="true"
                    className={n <= llenas ? 'text-amber-400' : 'text-gray-300'}
                >
                    ★
                </span>
            ))}
        </span>
    );
}
