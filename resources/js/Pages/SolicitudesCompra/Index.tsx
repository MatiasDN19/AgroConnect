import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';

interface SolicitudItem {
    id: number;
    estado: string;
    mensaje: string | null;
    publicacion: { id: number; titulo: string; precio: number; unidad: string };
    usuario: {
        id: number;
        name: string;
        email: string;
        telefono: string | null;
    };
}

export default function Index({
    solicitudes,
}: PageProps<{ solicitudes: SolicitudItem[] }>) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Solicitudes de Compra Recibidas
                </h2>
            }
        >
            <Head title="Solicitudes de Compra Recibidas" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {solicitudes.length === 0 ? (
                        <p>No tienes solicitudes de compra recibidas.</p>
                    ) : (
                        <ul>
                            {solicitudes.map((s) => (
                                <li key={s.id}>
                                    {s.publicacion.titulo} - {s.estado}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
