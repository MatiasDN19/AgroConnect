import AdminLayout from '@/Layouts/AdminLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';

interface MetricasData {
    total_usuarios: number;
    total_publicaciones_activas: number;
    total_denuncias_pendientes: number;
    total_resenas: number;
}

interface Props extends PageProps {
    metricas: MetricasData;
}

export default function Index({ metricas }: Props) {
    return (
        <AdminLayout titulo="Resumen">
            <Head title="Resumen" />

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm font-medium text-slate-500">
                        Total de usuarios
                    </p>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {metricas.total_usuarios}
                    </p>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm font-medium text-slate-500">
                        Publicaciones activas
                    </p>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {metricas.total_publicaciones_activas}
                    </p>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm font-medium text-slate-500">
                        Denuncias pendientes
                    </p>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {metricas.total_denuncias_pendientes}
                    </p>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm font-medium text-slate-500">
                        Total de reseñas
                    </p>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {metricas.total_resenas}
                    </p>
                </div>
            </div>
        </AdminLayout>
    );
}
