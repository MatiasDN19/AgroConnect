import Estrellas from '@/Components/Estrellas';
import PlaceholderCategoria from '@/Components/PlaceholderCategoria';
import SocioBadge from '@/Components/SocioBadge';
import { Categoria, Publicacion, PublicacionFoto, User } from '@/types';
import { textoDistancia } from '@/utils/distancia';
import { formatearPrecio } from '@/utils/formato';
import { Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';

// Lo mínimo que necesita la tarjeta. Coincide con lo que manda
// PublicacionController::index() (y sirve para otros listados, ej. sugeridos).
export type PublicacionResumen = Pick<
    Publicacion,
    'id' | 'titulo' | 'precio' | 'unidad'
> & {
    categoria: Pick<Categoria, 'id' | 'nombre'>;
    usuario: Pick<User, 'id' | 'name' | 'socio_validado'>;
    // withAvg de Laravel puede devolverlo como string ("4.0000") o null.
    puntuacion_promedio: number | string | null;
    // El listado manda solo la portada (KAN-204). Otros listados pueden no mandarla.
    fotos?: Pick<PublicacionFoto, 'url'>[];
    // Solo al ordenar por cercanía (KAN-171), en km. null si el productor no
    // tiene ubicación. Puede llegar como string según el motor de base de datos.
    distancia?: number | string | null;
};

// Las coordenadas viajan redondeadas (~1 km), así que no mostramos decimales.
export default function PublicacionCard({
    publicacion,
    enlaceProductor = true,
}: {
    publicacion: PublicacionResumen;
    // En el perfil público del productor va en false: sería un link a la
    // misma página (KAN-262).
    enlaceProductor?: boolean;
}) {
    const promedio =
        publicacion.puntuacion_promedio === null
            ? null
            : Number(publicacion.puntuacion_promedio);

    const portada = publicacion.fotos?.[0]?.url;

    const distancia =
        publicacion.distancia == null ? null : Number(publicacion.distancia);

    return (
        // "Link estirado" (KAN-262): el link al detalle es el título, y su
        // ::after cubre toda la tarjeta, así tocar en cualquier lado lleva al
        // detalle. El nombre del productor es otro link que queda por encima
        // (relative + z-10). Un <a> no puede ir dentro de otro <a>.
        <article className="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70 transition-[box-shadow,transform] duration-200 ease-out focus-within:ring-2 focus-within:ring-marca-azul hover:-translate-y-0.5 hover:shadow-lg hover:shadow-sky-900/10 active:scale-[0.99] motion-reduce:hover:translate-y-0">
            {/* Portada con proporción fija: todas las tarjetas quedan del mismo alto
                aunque las fotos tengan tamaños distintos (KAN-205). */}
            {portada ? (
                <img
                    src={portada}
                    alt=""
                    loading="lazy"
                    className="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                />
            ) : (
                <PlaceholderCategoria
                    categoria={publicacion.categoria.nombre}
                    className="aspect-[4/3] w-full"
                />
            )}

            <div className="flex flex-1 flex-col gap-3 p-5">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                        {publicacion.categoria.nombre}
                    </span>
                    {distancia !== null && (
                        <span className="flex items-center gap-1 text-xs text-gray-500">
                            <MapPin
                                className="h-3.5 w-3.5"
                                aria-hidden="true"
                            />
                            {/* En la tarjeta va sola, así que con mayúscula: "A 12 km". */}
                            {textoDistancia(distancia).replace(/^a/, 'A')}
                        </span>
                    )}
                </div>

                <h3 className="line-clamp-2 text-lg font-semibold text-gray-900">
                    <Link
                        href={route('publicaciones.show', publicacion.id)}
                        className="after:absolute after:inset-0 after:content-[''] focus:outline-none"
                    >
                        {publicacion.titulo}
                    </Link>
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
                    {/* min-w-0 deja que el nombre se corte con "…" y la insignia nunca se esconda */}
                    <span className="flex min-w-0 items-center gap-1.5">
                        {enlaceProductor ? (
                            <Link
                                href={route(
                                    'productores.show',
                                    publicacion.usuario.id,
                                )}
                                className="relative z-10 truncate rounded text-gray-600 underline-offset-2 transition-colors duration-150 hover:text-marca-azul hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                            >
                                {publicacion.usuario.name}
                            </Link>
                        ) : (
                            <span className="truncate text-gray-600">
                                {publicacion.usuario.name}
                            </span>
                        )}
                        {publicacion.usuario.socio_validado && (
                            <SocioBadge compacta />
                        )}
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
            </div>
        </article>
    );
}
