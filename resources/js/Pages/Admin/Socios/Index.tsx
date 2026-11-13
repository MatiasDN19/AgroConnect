import AdminLayout from '@/Layouts/AdminLayout';
import { User } from '@/types';
import { router } from '@inertiajs/react';

export default function Index({
    solicitudes = [],
}: {
    solicitudes: (User & { numero_socio?: string | null })[];
}) {
    const handleAceptar = (usuarioId: number) => {
        router.post(route('admin.socios.aceptar', usuarioId));
    };

    const handleRechazar = (usuarioId: number) => {
        if (
            confirm(
                '¿Estás seguro de que querés rechazar esta solicitud? El usuario perderá el número de socio ingresado.',
            )
        ) {
            router.post(route('admin.socios.rechazar', usuarioId));
        }
    };

    return (
        <AdminLayout titulo="Solicitudes de socios">
            <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 px-6 py-4">
                    <h2 className="text-base font-semibold text-slate-800">
                        Solicitudes pendientes de validación
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Productores que ingresaron su número de socio y esperan
                        confirmación del Consorcio.
                    </p>
                </div>

                {solicitudes.length === 0 ? (
                    <div className="px-6 py-12 text-center text-sm text-slate-500">
                        No hay solicitudes de socios pendientes.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-700">
                            <thead className="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th className="px-6 py-3">Nombre</th>
                                    <th className="px-6 py-3">Email</th>
                                    <th className="px-6 py-3">
                                        Número de socio
                                    </th>
                                    <th className="px-6 py-3 text-right">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {solicitudes.map((solicitud) => (
                                    <tr key={solicitud.id}>
                                        <td className="px-6 py-4 font-medium text-slate-900">
                                            {solicitud.name}
                                        </td>
                                        <td className="px-6 py-4">
                                            {solicitud.email}
                                        </td>
                                        <td className="px-6 py-4 font-mono text-slate-800">
                                            {solicitud.numero_socio}
                                        </td>
                                        <td className="space-x-2 px-6 py-4 text-right">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleAceptar(solicitud.id)
                                                }
                                                className="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-emerald-500 focus:outline-none"
                                            >
                                                Aceptar
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleRechazar(solicitud.id)
                                                }
                                                className="inline-flex items-center rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 focus:outline-none"
                                            >
                                                Rechazar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
