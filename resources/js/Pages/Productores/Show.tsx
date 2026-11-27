import Avatar from '@/Components/Avatar';
import Estrellas from '@/Components/Estrellas';
import Paginacion from '@/Components/Paginacion';
import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import SocioBadge from '@/Components/SocioBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Paginado } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Calendar, MapPin, Store } from 'lucide-react';

// Perfil público del productor (KAN-260/261 backend y primera versión de la
// página: Iván; estilo y links desde las publicaciones: KAN-262).
// Muestra quién es y sus publicaciones activas. Nunca la ubicación exacta:
// solo la localidad.

export interface ProductorPublico {
    id: number;
    name: string;
    socio_validado: boolean;
    localidad?: string | null;
    created_at?: string | null;
}

interface Props extends PageProps {
    productor: ProductorPublico;
    publicaciones: Paginado<PublicacionResumen>;
    puntuacion_promedio: number | null;
    promedio_resenas?: number | null;
    cantidad_resenas: number;
}

const formatoAlta = new Intl.DateTimeFormat('es-AR', {
    month: 'long',
    year: 'numeric',
});

function fechaDeAlta(fechaIso?: string | null): string {
    if (!fechaIso) return '';
    const fecha = new Date(fechaIso);
    return isNaN(fecha.getTime()) ? '' : formatoAlta.format(fecha);
}

export default function Show({
    productor,
    publicaciones,
    puntuacion_promedio,
    promedio_resenas,
    cantidad_resenas,
}: Props) {
    const promedio = puntuacion_promedio ?? promedio_resenas ?? null;
    const alta = fechaDeAlta(productor.created_at);

    return (
        <AuthenticatedLayout>
            <Head title={productor.name} />

            <div className="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
                {/* Cabecera: misma línea que el perfil propio (KAN-322). */}
                <header className="flex flex-col gap-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div className="flex items-center gap-4 sm:gap-5">
                        <Avatar nombre={productor.name} tamano="grande" />
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="break-words font-display text-2xl font-bold leading-tight text-marca-tinta sm:text-3xl">
                                    {productor.name}
                                </h1>
                                {productor.socio_validado && <SocioBadge />}
                            </div>

                            <p className="mt-1 text-sm font-medium text-slate-700">
                                Productor
                            </p>

                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                {productor.localidad && (
                                    <span className="flex items-center gap-1.5">
                                        <MapPin
                                            className="h-4 w-4 text-marca-azul"
                                            aria-hidden="true"
                                        />
                                        {productor.localidad}
                                    </span>
                                )}
                                {alta && (
                                    <span className="flex items-center gap-1.5">
                                        <Calendar
                                            className="h-4 w-4 text-slate-400"
                                            aria-hidden="true"
                                        />
                                        En AgroConnect desde {alta}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Reseñas */}
                    <div className="flex shrink-0 flex-col items-start rounded-xl bg-slate-50 px-5 py-4 ring-1 ring-slate-100 sm:items-end">
                        {promedio !== null ? (
                            <>
                                <span className="flex items-center gap-2">
                                    <Estrellas valor={promedio} />
                                    <span className="font-display text-xl font-bold text-marca-tinta">
                                        {promedio.toFixed(1)}
                                    </span>
                                </span>
                                <span className="mt-0.5 text-xs text-slate-500">
                                    {cantidad_resenas}{' '}
                                    {cantidad_resenas === 1
                                        ? 'reseña'
                                        : 'reseñas'}{' '}
                                    en sus publicaciones
                                </span>
                            </>
                        ) : (
                            <span className="text-sm text-slate-500">
                                Sin reseñas todavía
                            </span>
                        )}
                    </div>
                </header>

                {/* Publicaciones activas */}
                <section aria-labelledby="titulo-publicaciones">
                    <div className="mb-5 flex items-baseline gap-2">
                        <h2
                            id="titulo-publicaciones"
                            className="font-display text-2xl font-bold text-marca-tinta"
                        >
                            Publicaciones activas
                        </h2>
                        <span className="text-sm text-slate-500">
                            ({publicaciones.total})
                        </span>
                    </div>

                    {publicaciones.data.length > 0 ? (
                        <>
                            <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {publicaciones.data.map((publicacion) => (
                                    <li key={publicacion.id}>
                                        {/* Ya estamos en su perfil: el nombre no es link. */}
                                        <PublicacionCard
                                            publicacion={publicacion}
                                            enlaceProductor={false}
                                        />
                                    </li>
                                ))}
                            </ul>

                            <div className="mt-8">
                                <Paginacion paginado={publicaciones} />
                            </div>
                        </>
                    ) : (
                        <div className="flex flex-col items-center gap-3 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200">
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <Store className="h-6 w-6" aria-hidden="true" />
                            </span>
                            <p className="font-medium text-slate-700">
                                {productor.name} no tiene publicaciones activas
                                en este momento.
                            </p>
                            <Link
                                href={route('publicaciones.index')}
                                className="text-sm font-medium text-marca-azul underline-offset-2 hover:underline"
                            >
                                Ver todo el marketplace →
                            </Link>
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
