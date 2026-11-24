import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface Props {
    configuraciones: Record<string, any>;
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
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
                        <p className="text-sm text-gray-600">
                            Ajustes y parámetros globales de AgroConnect.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
