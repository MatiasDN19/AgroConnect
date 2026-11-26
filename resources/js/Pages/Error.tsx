import ApplicationLogo from '@/Components/ApplicationLogo';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Home, ShieldAlert, ShoppingBag } from 'lucide-react';

interface Props {
    status: number;
}

const MENSAJES: Record<number, { titulo: string; descripcion: string }> = {
    403: {
        titulo: 'No tenés permiso para ver esto',
        descripcion:
            'No contás con las autorizaciones necesarias para ingresar a esta sección.',
    },
    404: {
        titulo: 'No encontramos esta página',
        descripcion:
            'La página que estás buscando no existe, cambió de lugar o no está disponible.',
    },
    500: {
        titulo: 'Algo salió mal de nuestro lado',
        descripcion:
            'Tuvimos un inconveniente inesperado en nuestros servidores. Por favor, probá de nuevo en unos momentos.',
    },
    503: {
        titulo: 'Estamos haciendo mantenimiento',
        descripcion:
            'Estamos realizando mejoras técnicas en el sistema. En breve estaremos de vuelta.',
    },
};

export default function Error({ status }: Props) {
    const { auth } = usePage<PageProps>().props;
    const info = MENSAJES[status] ?? {
        titulo: `Error ${status}`,
        descripcion: 'Ocurrió un problema al procesar tu solicitud.',
    };

    const estaAutenticado = Boolean(auth?.user);

    return (
        <>
            <Head title={`${status} — ${info.titulo}`} />

            <div className="flex min-h-screen flex-col justify-between bg-gradient-to-b from-sky-950 via-sky-900 to-slate-900 px-4 py-12 sm:px-6 lg:px-8">
                {/* Cabecera / Logo */}
                <div className="flex justify-center">
                    <Link
                        href={
                            estaAutenticado ? route('publicaciones.index') : '/'
                        }
                        className="transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
                    >
                        <ApplicationLogo className="h-12 w-auto brightness-0 drop-shadow invert" />
                    </Link>
                </div>

                {/* Contenido principal */}
                <main className="mx-auto my-auto w-full max-w-lg rounded-3xl bg-white/95 p-8 text-center shadow-2xl ring-1 ring-white/20 backdrop-blur-md sm:p-12">
                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 sm:h-20 sm:w-20">
                        <ShieldAlert
                            className="h-8 w-8 sm:h-10 sm:w-10"
                            aria-hidden="true"
                        />
                    </div>

                    <span className="mt-6 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Código {status}
                    </span>

                    <h1 className="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                        {info.titulo}
                    </h1>

                    <p className="mt-3 text-base text-gray-600">
                        {info.descripcion}
                    </p>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        {estaAutenticado ? (
                            <Link
                                href={route('publicaciones.index')}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                            >
                                <ShoppingBag
                                    className="h-4 w-4"
                                    aria-hidden="true"
                                />
                                Volver al marketplace
                            </Link>
                        ) : (
                            <Link
                                href="/"
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                            >
                                <Home className="h-4 w-4" aria-hidden="true" />
                                Ir al inicio
                            </Link>
                        )}

                        <button
                            type="button"
                            onClick={() => window.history.back()}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                        >
                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                            Volver atrás
                        </button>
                    </div>
                </main>

                {/* Pie con texto institucional */}
                <footer className="text-center text-xs text-sky-200/70">
                    AgroConnect — Consorcio Productivo de Laguna Naineck
                </footer>
            </div>
        </>
    );
}
