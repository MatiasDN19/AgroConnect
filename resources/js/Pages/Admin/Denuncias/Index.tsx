import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface DenunciaItem {
    id: number;
    motivo: string;
    descripcion: string | null;
    estado: 'pendiente' | 'revisada' | 'descartada';
    created_at: string;
    usuario?: {
        id: number;
        name: string;
        email: string;
    };
    publicacion?: {
        id: number;
        titulo: string;
        usuario_id: number;
        usuario?: {
            id: number;
            name: string;
            email: string;
        };
    };
}

interface Props {
    denuncias: DenunciaItem[];
    estado_actual: string;
}

export default function Index({ denuncias, estado_actual }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Panel de Denuncias
                </h2>
            }
        >
            <Head title="Admin - Denuncias" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="mb-4">
                            <span className="font-medium text-gray-700">
                                Estado filtrado:{' '}
                            </span>
                            <span className="capitalize">{estado_actual}</span>
                        </div>

                        {denuncias.length === 0 ? (
                            <p className="text-gray-500">
                                No hay denuncias para mostrar.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {denuncias.map((denuncia) => (
                                    <div
                                        key={denuncia.id}
                                        className="border-b pb-4"
                                    >
                                        <p className="font-bold text-gray-800">
                                            {denuncia.motivo}
                                        </p>
                                        {denuncia.descripcion && (
                                            <p className="mt-1 text-sm text-gray-600">
                                                {denuncia.descripcion}
                                            </p>
                                        )}
                                        <div className="mt-2 flex gap-4 text-xs text-gray-500">
                                            <span>
                                                Por: {denuncia.usuario?.name}
                                            </span>
                                            <span>
                                                Publicación:{' '}
                                                {denuncia.publicacion?.titulo}
                                            </span>
                                            <span>
                                                Estado: {denuncia.estado}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
