import { Head, Link } from '@inertiajs/react';

interface Estadisticas {
    productores: number;
    publicaciones_activas: number;
    categorias: number;
}

interface LandingProps {
    estadisticas: Estadisticas;
    canLogin?: boolean;
    canRegister?: boolean;
}

export default function Landing({
    estadisticas,
    canLogin = false,
    canRegister = false,
}: LandingProps) {
    return (
        <div className="min-h-screen bg-gray-50 text-gray-900">
            <Head title="AgroConnect - Laguna Naineck" />
            <header className="p-6">
                <nav className="flex justify-end gap-4">
                    {canLogin && (
                        <Link
                            href={route('login')}
                            className="text-sm font-semibold text-gray-700 hover:text-gray-900"
                        >
                            Iniciar sesión
                        </Link>
                    )}
                    {canRegister && (
                        <Link
                            href={route('register')}
                            className="text-sm font-semibold text-gray-700 hover:text-gray-900"
                        >
                            Registrarse
                        </Link>
                    )}
                </nav>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="text-center">
                    <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                        AgroConnect
                    </h1>
                    <p className="mt-4 text-lg text-gray-600">
                        Marketplace agro para el Consorcio Productivo de Laguna Naineck
                    </p>
                </div>
                <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div className="rounded-lg bg-white p-6 text-center shadow">
                        <dt className="text-sm font-medium text-gray-500">
                            Productores
                        </dt>
                        <dd className="mt-2 text-3xl font-semibold text-gray-900">
                            {estadisticas.productores}
                        </dd>
                    </div>
                    <div className="rounded-lg bg-white p-6 text-center shadow">
                        <dt className="text-sm font-medium text-gray-500">
                            Publicaciones Activas
                        </dt>
                        <dd className="mt-2 text-3xl font-semibold text-gray-900">
                            {estadisticas.publicaciones_activas}
                        </dd>
                    </div>
                    <div className="rounded-lg bg-white p-6 text-center shadow">
                        <dt className="text-sm font-medium text-gray-500">
                            Categorías
                        </dt>
                        <dd className="mt-2 text-3xl font-semibold text-gray-900">
                            {estadisticas.categorias}
                        </dd>
                    </div>
                </div>
            </main>
        </div>
    );
}
