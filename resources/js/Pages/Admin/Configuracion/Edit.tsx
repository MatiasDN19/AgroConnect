import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface Props {
    configuraciones: Record<string, string | number | null>;
}

export default function Edit({ configuraciones }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Configuración de la plataforma
                </h2>
            }
        >
            <Head title="Configuración" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 text-gray-900 shadow-sm sm:rounded-lg">
                        <p className="text-sm text-gray-600">
                            Ajustes y parámetros globales de AgroConnect.
                        </p>
                        <div className="mt-4 space-y-2 text-sm">
                            {Object.entries(configuraciones).map(
                                ([clave, valor]) => (
                                    <div
                                        key={clave}
                                        className="flex justify-between border-b pb-1"
                                    >
                                        <span className="font-medium text-gray-700">
                                            {clave}
                                        </span>
                                        <span className="text-gray-500">
                                            {valor ?? '—'}
                                        </span>
                                    </div>
                                ),
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
