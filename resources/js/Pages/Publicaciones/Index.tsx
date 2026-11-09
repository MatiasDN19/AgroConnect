import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index({
    publicaciones,
}: PageProps<{ publicaciones: PublicacionResumen[] }>) {
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
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Acá va el filtro por categoría (CategoriaFiltro, KAN-53). */}

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
        </AuthenticatedLayout>
    );
}
