import Estrellas from '@/Components/Estrellas';
import Paginacion from '@/Components/Paginacion';
import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import SocioBadge from '@/Components/SocioBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Paginado } from '@/types';
import { Head } from '@inertiajs/react';
import { Calendar, MapPin, Store } from 'lucide-react';

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

function iniciales(nombre: string): string {
    return nombre
        .split(' ')
        .map((p) => p[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function formatearFechaRegistro(fechaIso?: string | null): string {
    if (!fechaIso) return '';
    const fecha = new Date(fechaIso);
    if (isNaN(fecha.getTime())) return '';
    return fecha.toLocaleDateString('es-AR', {
        month: 'long',
        year: 'numeric',
    });
}

export default function Show({
    productor,
    publicaciones,
    puntuacion_promedio,
    promedio_resenas,
    cantidad_resenas,
}: Props) {
    const promedio = puntuacion_promedio ?? promedio_resenas ?? null;
    const fechaRegistro = formatearFechaRegistro(productor.created_at);

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-bold text-gray-900 sm:text-2xl">
                    Perfil del productor
                </h1>
            }
        >
            <Head title={`Perfil de ${productor.name}`} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Cabecera del perfil */}
                <div className="mb-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4">
                            {/* Avatar con iniciales */}
                            <div
                                aria-hidden="true"
                                className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-bold text-white shadow-sm ring-2 ring-emerald-500/20 sm:h-20 sm:w-20 sm:text-2xl"
                            >
                                {iniciales(productor.name)}
                            </div>

                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-2xl font-bold text-gray-900">
                                        {productor.name}
                                    </h2>
                                    {productor.socio_validado && <SocioBadge />}
                                </div>

                                <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    {productor.localidad && (
                                        <div className="flex items-center gap-1.5">
                                            <MapPin
                                                className="h-4 w-4 text-gray-400"
                                                aria-hidden="true"
                                            />
                                            <span>{productor.localidad}</span>
                                        </div>
                                    )}

                                    {fechaRegistro && (
                                        <div className="flex items-center gap-1.5">
                                            <Calendar
                                                className="h-4 w-4 text-gray-400"
                                                aria-hidden="true"
                                            />
                                            <span>
                                                En AgroConnect desde{' '}
                                                {fechaRegistro}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Puntuación y reseñas */}
                        <div className="flex items-center gap-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100 sm:self-center">
                            {promedio !== null ? (
                                <div className="flex flex-col items-center sm:items-end">
                                    <div className="flex items-center gap-2">
                                        <Estrellas valor={promedio} />
                                        <span className="font-bold text-gray-900">
                                            {promedio.toFixed(1)}
                                        </span>
                                    </div>
                                    <span className="mt-1 text-xs text-gray-500">
                                        {cantidad_resenas}{' '}
                                        {cantidad_resenas === 1
                                            ? 'reseña'
                                            : 'reseñas'}
                                    </span>
                                </div>
                            ) : (
                                <span className="text-sm text-gray-500">
                                    Sin reseñas todavía
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Sección de publicaciones activas */}
                <div>
                    <div className="mb-6 flex items-center justify-between">
                        <h3 className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <Store
                                className="h-5 w-5 text-emerald-600"
                                aria-hidden="true"
                            />
                            Publicaciones activas
                            <span className="ml-1 text-sm font-normal text-gray-500">
                                ({publicaciones.total})
                            </span>
                        </h3>
                    </div>

                    {publicaciones.data.length > 0 ? (
                        <>
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                {publicaciones.data.map((pub) => (
                                    <PublicacionCard
                                        key={pub.id}
                                        publicacion={pub}
                                    />
                                ))}
                            </div>

                            <div className="mt-8">
                                <Paginacion paginado={publicaciones} />
                            </div>
                        </>
                    ) : (
                        <div className="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                            <Store className="mx-auto h-12 w-12 text-gray-400" />
                            <h4 className="mt-3 text-base font-medium text-gray-900">
                                Sin publicaciones activas
                            </h4>
                            <p className="mt-1 text-sm text-gray-500">
                                Este productor no tiene publicaciones activas en
                                este momento.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
