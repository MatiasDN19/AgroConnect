import ApplicationLogo from '@/Components/ApplicationLogo';
import { seccionesDisponibles } from '@/utils/panelAdmin';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, LogOut, Menu, X } from 'lucide-react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import logoConsorcio from '../../images/logos/consorcio-laguna-naineck.webp';
import logoPlanNutrir from '../../images/logos/plan-nutrir.webp';

// Layout del panel de administración (KAN-219): sidebar azul con las secciones
// del panel y área principal clara con el título de la página.
export default function AdminLayout({
    titulo,
    acciones,
    children,
}: PropsWithChildren<{
    titulo: string;
    // Botones a la derecha del título (ej. "Nueva categoría").
    acciones?: ReactNode;
}>) {
    const { auth, admin } = usePage().props;
    const [menuAbierto, setMenuAbierto] = useState(false);

    // En mobile, Escape cierra la sidebar.
    useEffect(() => {
        if (!menuAbierto) return;

        const alApretar = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setMenuAbierto(false);
        };
        window.addEventListener('keydown', alApretar);

        return () => window.removeEventListener('keydown', alApretar);
    }, [menuAbierto]);

    const pendientes = admin?.denuncias_pendientes ?? 0;

    return (
        <div className="min-h-screen bg-slate-50">
            <Head title={`${titulo} · Panel admin`} />

            {/* Fondo oscuro detrás de la sidebar en mobile */}
            {menuAbierto && (
                <div
                    className="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
                    onClick={() => setMenuAbierto(false)}
                    aria-hidden="true"
                />
            )}

            <aside
                id="sidebar-admin"
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-marca-azul-profundo text-sky-50 transition-transform duration-200 lg:translate-x-0 ${
                    menuAbierto ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 items-center justify-between px-5">
                    <Link
                        href={route('publicaciones.index')}
                        className="flex items-center gap-2"
                    >
                        <ApplicationLogo className="h-10 w-10 rounded-full bg-white p-0.5" />
                        <span className="leading-tight">
                            <span className="block font-semibold text-white">
                                AgroConnect
                            </span>
                            <span className="block text-xs uppercase tracking-wider text-sky-200">
                                Panel admin
                            </span>
                        </span>
                    </Link>

                    <button
                        type="button"
                        onClick={() => setMenuAbierto(false)}
                        className="rounded-md p-1 text-sky-100 hover:bg-white/10 lg:hidden"
                        aria-label="Cerrar menú"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    {seccionesDisponibles().map((seccion) => {
                        const activa = route().current(seccion.activo);
                        const Icono = seccion.icono;

                        return (
                            <Link
                                key={seccion.ruta}
                                href={route(seccion.ruta)}
                                aria-current={activa ? 'page' : undefined}
                                className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white ${
                                    activa
                                        ? 'bg-white/15 text-white'
                                        : 'text-sky-100 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                <Icono
                                    className="h-5 w-5 shrink-0"
                                    aria-hidden="true"
                                />
                                <span className="flex-1">{seccion.nombre}</span>

                                {seccion.nombre === 'Denuncias' &&
                                    pendientes > 0 && (
                                        <span className="rounded-full bg-marca-ambar px-2 py-0.5 text-xs font-bold text-amber-950">
                                            {pendientes}
                                            <span className="sr-only">
                                                {' '}
                                                pendientes
                                            </span>
                                        </span>
                                    )}
                            </Link>
                        );
                    })}
                </nav>

                <div className="space-y-4 border-t border-white/10 px-5 py-4">
                    <div>
                        <p className="text-xs uppercase tracking-wider text-sky-200">
                            En alianza con
                        </p>
                        {/* Fondo blanco: los logos institucionales son a color y no se leen sobre azul. */}
                        <div className="mt-2 flex items-center gap-3 rounded-lg bg-white px-3 py-2">
                            <img
                                src={logoConsorcio}
                                alt="Consorcio Productivo de Laguna Naineck"
                                className="h-14 w-auto"
                            />
                            <img
                                src={logoPlanNutrir}
                                alt="Plan Nutrir, plan provincial alimentario"
                                className="h-10 w-auto"
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-marca-ambar font-bold text-amber-950">
                            {auth.user.name.charAt(0).toUpperCase()}
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-medium text-white">
                                {auth.user.name}
                            </p>
                            <p className="text-xs text-sky-200">
                                Administrador
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-col gap-1 text-sm">
                        <Link
                            href={route('publicaciones.index')}
                            className="flex items-center gap-2 rounded-md px-2 py-1 text-sky-100 hover:bg-white/10 hover:text-white"
                        >
                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                            Volver al marketplace
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="flex items-center gap-2 rounded-md px-2 py-1 text-left text-sky-100 hover:bg-white/10 hover:text-white"
                        >
                            <LogOut className="h-4 w-4" aria-hidden="true" />
                            Cerrar sesión
                        </Link>
                    </div>
                </div>
            </aside>

            <div className="lg:ps-64">
                <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div className="flex min-h-16 flex-wrap items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            onClick={() => setMenuAbierto(true)}
                            className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                            aria-label="Abrir menú"
                            aria-controls="sidebar-admin"
                            aria-expanded={menuAbierto}
                        >
                            <Menu className="h-5 w-5" />
                        </button>

                        <h1 className="flex-1 text-xl font-semibold text-slate-800">
                            {titulo}
                        </h1>

                        {acciones}
                    </div>
                </header>

                <main className="aparecer px-4 py-8 sm:px-6 lg:px-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
