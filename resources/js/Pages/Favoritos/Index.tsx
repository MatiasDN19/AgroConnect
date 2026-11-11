import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';

interface FavoritoItem {
    id: number;
    publicacion_id: number;
    publicacion: {
        id: number;
        titulo: string;
        precio: number;
        categoria?: { id: number; nombre: string };
        usuario?: { id: number; name: string };
    };
}

export default function Index({
    favoritos,
}: PageProps<{ favoritos: FavoritoItem[] }>) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Mis Favoritos
                </h2>
            }
        >
            <Head title="Mis Favoritos" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {favoritos.length === 0 ? (
                        <p>No tienes publicaciones favoritas aún.</p>
                    ) : (
                        <ul>
                            {favoritos.map((fav) => (
                                <li key={fav.id}>{fav.publicacion.titulo}</li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
