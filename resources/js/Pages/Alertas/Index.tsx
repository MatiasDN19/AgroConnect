import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Alerta, abrirAlerta, destinoAlerta } from '@/utils/alertas';
import { Head, router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

interface Props extends PageProps {
    alertas: Alerta[];
}

// Historial de notificaciones. El rediseño de esta página es KAN-323; acá solo
// se suma que cada notificación lleve a su publicación o sección (KAN-344).
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
                                {alertas.map((alerta) => {
                                    const tieneDestino =
                                        destinoAlerta(alerta) !== null;
                                    const contenido = (
                                        <div className="space-y-1">
                                            <p>{alerta.mensaje}</p>
                                            <p className="text-xs text-gray-400">
                                                {new Date(
                                                    alerta.created_at,
                                                ).toLocaleString()}
                                            </p>
                                        </div>
                                    );

                                    return (
                                        <li
                                            key={alerta.id}
                                            className={`flex items-center justify-between gap-4 p-6 transition ${
                                                alerta.leida
                                                    ? 'bg-white text-gray-600'
                                                    : 'bg-indigo-50/50 font-medium text-gray-900'
                                            }`}
                                        >
                                            {tieneDestino ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        abrirAlerta(alerta)
                                                    }
                                                    className="group flex flex-1 items-center justify-between gap-3 rounded-md text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                                                >
                                                    {contenido}
                                                    <ChevronRight
                                                        className="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-150 group-hover:translate-x-0.5"
                                                        aria-hidden="true"
                                                    />
                                                </button>
                                            ) : (
                                                <div className="flex-1">
                                                    {contenido}
                                                </div>
                                            )}

                                            {!alerta.leida && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        marcarComoLeida(
                                                            alerta.id,
                                                        )
                                                    }
                                                    className="shrink-0 rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                >
                                                    Marcar leída
                                                </button>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
