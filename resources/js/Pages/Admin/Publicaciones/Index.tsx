import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface Props {
    publicaciones: {
        total: number;
        data: Array<Record<string, unknown>>;
    };
    categorias: Array<{ id: number; nombre: string }>;
    filtros: { q?: string; estado?: string; categoria_id?: string };
}

export default function Index({ publicaciones, categorias, filtros }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Moderación de Publicaciones
                </h2>
            }
        >
            <Head title="Moderación de Publicaciones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm text-gray-600">
                            Total de publicaciones: {publicaciones?.total ?? 0}
                        </p>
                        <p className="text-xs text-gray-500">
                            Filtros activos: {JSON.stringify(filtros)} |
                            Categorías: {categorias?.length ?? 0}
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
