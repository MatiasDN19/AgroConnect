import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, router } from '@inertiajs/react';

interface AlertaItem {
    id: number;
    tipo: string;
    mensaje: string;
    leida: boolean;
    created_at: string;
}

interface Props extends PageProps {
    alertas: AlertaItem[];
}

export default function Index({ alertas }: Props) {
    const marcarComoLeida = (alertaId: number) => {
        router.patch(route('alertas.marcar-leida', alertaId));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Notificaciones
                </h2>
            }
        >
            <Head title="Notificaciones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {alertas.length === 0 ? (
                            <div className="p-6 text-gray-500">
                                No tenés notificaciones pendientes.
                            </div>
                        ) : (
                            <ul className="divide-y divide-gray-200">
                                {alertas.map((alerta) => (
                                    <li
                                        key={alerta.id}
                                        className={`flex items-center justify-between p-6 transition ${
                                            alerta.leida
                                                ? 'bg-white text-gray-600'
                                                : 'bg-indigo-50/50 text-gray-900 font-medium'
                                        }`}
                                    >
                                        <div className="space-y-1">
                                            <p>{alerta.mensaje}</p>
                                            <p className="text-xs text-gray-400">
                                                {new Date(
                                                    alerta.created_at
                                                ).toLocaleString()}
                                            </p>
                                        </div>

                                        {!alerta.leida && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    marcarComoLeida(alerta.id)
                                                }
                                                className="ml-4 rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            >
                                                Marcar leída
                                            </button>
                                        )}
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
