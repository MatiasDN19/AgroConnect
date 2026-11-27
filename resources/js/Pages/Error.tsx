import ApplicationLogo from '@/Components/ApplicationLogo';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Home,
    LockKeyhole,
    LucideIcon,
    MapPinOff,
    ServerCrash,
    ShoppingBag,
    TriangleAlert,
    Wrench,
} from 'lucide-react';
import { CSSProperties, useEffect, useState } from 'react';

// Página de error con la identidad de AgroConnect (KAN-267). La renderiza
// bootstrap/app.php para 403, 404, 500 y 503 (KAN-266, Iván), salvo en local
// con APP_DEBUG=true, donde se ve la pantalla de Laravel con el detalle.
// Puede mostrarse con o sin sesión iniciada.

interface Props {
    status: number;
}

const MENSAJES: Record<
    number,
    { titulo: string; descripcion: string; Icono: LucideIcon }
> = {
    403: {
        titulo: 'No tenés permiso para ver esto',
        descripcion:
            'Esta sección es para otro tipo de cuenta. Si creés que es un error, escribinos al Consorcio.',
        Icono: LockKeyhole,
    },
    404: {
        titulo: 'No encontramos esta página',
        descripcion:
            'Puede que el link esté mal escrito o que la publicación ya no esté disponible.',
        Icono: MapPinOff,
    },
    500: {
        titulo: 'Algo salió mal de nuestro lado',
        descripcion:
            'Tuvimos un problema inesperado. Probá de nuevo en unos minutos; si sigue pasando, avisanos.',
        Icono: ServerCrash,
    },
    503: {
        titulo: 'Estamos haciendo mantenimiento',
        descripcion:
            'Estamos mejorando AgroConnect. En un rato volvemos a estar disponibles.',
        Icono: Wrench,
    },
};

const GENERICO = {
    titulo: 'Ocurrió un problema',
    descripcion: 'No pudimos completar lo que pediste. Probá de nuevo.',
    Icono: TriangleAlert,
};

const CLASE_BOTON =
    'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition-[transform,background-color,filter] duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 active:scale-[0.97]';

export default function Error({ status }: Props) {
    const { auth } = usePage<PageProps>().props;
    const { titulo, descripcion, Icono } = MENSAJES[status] ?? GENERICO;
    const conSesion = Boolean(auth?.user);
    const inicio = conSesion ? route('publicaciones.index') : '/';

    // "Volver atrás" solo si hay a dónde volver: si se entró directo con un
    // link, history.back() no haría nada. Se calcula en el navegador.
    const [hayAnterior, setHayAnterior] = useState(false);
    useEffect(() => setHayAnterior(window.history.length > 1), []);

    return (
        <>
            <Head title={titulo} />

            <div className="flex min-h-screen flex-col items-center justify-between gap-8 bg-gradient-to-br from-marca-azul-claro via-marca-azul to-marca-azul-profundo px-4 py-8 sm:py-12">
                {/* Logo a color, como en el navbar */}
                <Link
                    href={inicio}
                    className="aparecer inline-flex items-center gap-2 rounded-full bg-white py-1.5 pl-1.5 pr-4 shadow-lg shadow-sky-950/20 transition-transform duration-150 hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-marca-azul"
                >
                    <ApplicationLogo className="h-9 w-9" />
                    <span className="font-display text-lg font-extrabold tracking-tight">
                        <span className="text-marca-azul">Agro</span>
                        <span className="text-marca-dorado">Connect</span>
                    </span>
                </Link>

                <main
                    className="aparecer relative w-full max-w-lg overflow-hidden rounded-3xl bg-white px-6 py-10 text-center shadow-2xl shadow-sky-950/30 sm:px-10 sm:py-12"
                    style={{ '--orden': 1 } as CSSProperties}
                >
                    {/* El número, grande y suave, de fondo */}
                    <span
                        aria-hidden="true"
                        className="pointer-events-none absolute -top-6 left-1/2 -translate-x-1/2 select-none font-display text-[9rem] font-extrabold leading-none text-sky-50 sm:text-[11rem]"
                    >
                        {status}
                    </span>

                    <div className="relative">
                        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-marca-azul ring-1 ring-sky-100">
                            <Icono className="h-8 w-8" aria-hidden="true" />
                        </span>

                        <p className="mt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Error {status}
                        </p>
                        <h1 className="mt-1 font-display text-2xl font-bold text-marca-tinta sm:text-3xl">
                            {titulo}
                        </h1>
                        <p className="mx-auto mt-3 max-w-sm text-slate-600">
                            {descripcion}
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <Link
                                href={inicio}
                                className={`${CLASE_BOTON} bg-marca-ambar text-amber-950 shadow-sm hover:brightness-105`}
                            >
                                {conSesion ? (
                                    <ShoppingBag
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                ) : (
                                    <Home
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                )}
                                {conSesion
                                    ? 'Volver al marketplace'
                                    : 'Ir al inicio'}
                            </Link>

                            {hayAnterior && (
                                <button
                                    type="button"
                                    onClick={() => window.history.back()}
                                    className={`${CLASE_BOTON} bg-slate-100 text-slate-700 hover:bg-slate-200`}
                                >
                                    <ArrowLeft
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    Volver atrás
                                </button>
                            )}
                        </div>
                    </div>
                </main>

                <footer className="text-center text-xs text-sky-100/80">
                    AgroConnect · Consorcio Productivo de Laguna Naineck
                </footer>
            </div>
        </>
    );
}
