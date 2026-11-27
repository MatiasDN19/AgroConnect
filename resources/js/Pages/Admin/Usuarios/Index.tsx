import BotonExportar from '@/Components/Admin/BotonExportar';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

interface Props {
    usuarios: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            rol: string;
            telefono: string | null;
            socio_validado: boolean;
            inhabilitado: boolean;
            created_at: string;
            publicaciones_count: number;
        }>;
    };
    filtros: {
        q?: string;
        rol?: string;
        inhabilitado?: string;
    };
}

export default function Index({ usuarios, filtros }: Props) {
    return (
        <AdminLayout
            titulo="Gestión de Usuarios"
            acciones={
                // Exporta con los mismos filtros que la pantalla (KAN-318).
                // El listado en sí es KAN-119.
                <BotonExportar
                    href={route('admin.exportar.usuarios', {
                        ...(filtros.q ? { q: filtros.q } : {}),
                        ...(filtros.rol ? { rol: filtros.rol } : {}),
                        ...(filtros.inhabilitado
                            ? { inhabilitado: filtros.inhabilitado }
                            : {}),
                    })}
                />
            }
        >
            <Head title="Gestión de Usuarios - Admin" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-gray-600">
                            Borrador de usuarios del panel admin (
                            {usuarios.data.length} usuarios cargados).
                        </p>
                        {filtros.q && (
                            <p className="text-sm text-gray-500">
                                Búsqueda activa: {filtros.q}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
