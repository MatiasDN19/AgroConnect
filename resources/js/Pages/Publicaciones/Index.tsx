import OrdenCercania from '@/Components/OrdenCercania';
import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import SugeridosParaVos from '@/Components/SugeridosParaVos';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index({
    publicaciones,
    categoria_id,
    lat,
    lng,
}: PageProps<{
    publicaciones: PublicacionResumen[];
    categoria_id?: string | null;
    lat?: string | null;
    lng?: string | null;
}>) {
    const { auth } = usePage().props;
    const esProductor = auth.user.rol === 'productor';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Publicaciones
                    </h2>

                    {esProductor && (
                        <Link
                            href={route('publicaciones.create')}
                            className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Nueva publicación
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Publicaciones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
                    {/* Sugeridos para vos (KAN-170): no aparece si no hay sugeridas. */}
                    <SugeridosParaVos />

                    {/* Acá va el filtro por categoría (CategoriaFiltro, KAN-53). */}
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Todas las publicaciones
                            </h3>
                            {/* KAN-171: el orden activo sale de la URL (lat/lng). */}
                            <OrdenCercania
                                activo={lat != null && lng != null}
                                categoriaId={categoria_id}
                            />
                        </div>

                        {publicaciones.length === 0 ? (
                            <div className="rounded-lg bg-white p-6 text-sm text-gray-600 shadow-sm">
                                Todavía no hay publicaciones activas.
                                {esProductor && (
                                    <>
                                        {' '}
                                        <Link
                                            href={route('publicaciones.create')}
                                            className="text-indigo-600 hover:underline"
                                        >
                                            Creá la primera.
                                        </Link>
                                    </>
                                )}
                            </div>
                        ) : (
                            <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {publicaciones.map((publicacion) => (
                                    <li key={publicacion.id}>
                                        <PublicacionCard
                                            publicacion={publicacion}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
