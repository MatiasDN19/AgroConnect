import Estrellas from '@/Components/Estrellas';
import { Categoria, Publicacion, User } from '@/types';
import { formatearPrecio } from '@/utils/formato';
import { Link } from '@inertiajs/react';

// Lo mínimo que necesita la tarjeta. Coincide con lo que manda
// PublicacionController::index() (y sirve para otros listados, ej. sugeridos).
export type PublicacionResumen = Pick<
    Publicacion,
    'id' | 'titulo' | 'precio' | 'unidad'
> & {
    categoria: Pick<Categoria, 'id' | 'nombre'>;
    usuario: Pick<User, 'id' | 'name'>;
    // withAvg de Laravel puede devolverlo como string ("4.0000") o null.
    puntuacion_promedio: number | string | null;
};

export default function PublicacionCard({
    publicacion,
}: {
    publicacion: PublicacionResumen;
}) {
    const promedio =
        publicacion.puntuacion_promedio === null
            ? null
            : Number(publicacion.puntuacion_promedio);

    return (
        // Toda la tarjeta es un link al detalle.
        <Link
            href={route('publicaciones.show', publicacion.id)}
            className="flex h-full flex-col gap-3 rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md hover:ring-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            <span className="w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                {publicacion.categoria.nombre}
            </span>

            <h3 className="line-clamp-2 text-lg font-semibold text-gray-900">
                {publicacion.titulo}
            </h3>

            <p className="text-gray-900">
                <span className="text-xl font-semibold">
                    {formatearPrecio(publicacion.precio)}
                </span>{' '}
                <span className="text-sm text-gray-600">
                    por {publicacion.unidad}
                </span>
            </p>

            {/* mt-auto empuja esta fila al fondo, así todas las tarjetas quedan alineadas */}
            <div className="mt-auto flex items-center justify-between gap-2 border-t border-gray-100 pt-3 text-sm">
                <span className="truncate text-gray-600">
                    {publicacion.usuario.name}
                </span>

                {promedio !== null ? (
                    <span className="flex shrink-0 items-center gap-1 text-gray-600">
                        <Estrellas valor={promedio} className="text-sm" />
                        {promedio.toFixed(1)}
                    </span>
                ) : (
                    <span className="shrink-0 text-xs text-gray-400">
                        Sin reseñas
                    </span>
                )}
            </div>
        </Link>
    );
}
