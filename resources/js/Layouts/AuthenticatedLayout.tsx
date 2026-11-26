import ApplicationLogo from '@/Components/ApplicationLogo';
import Avatar from '@/Components/Avatar';
import BotonIcono from '@/Components/BotonIcono';
import BuscadorPublicaciones from '@/Components/BuscadorPublicaciones';
import Campanita from '@/Components/Campanita';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import PieApp from '@/Components/PieApp';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { User } from '@/types';
import { rutaInicioAdmin } from '@/utils/panelAdmin';
import { Link, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    Heart,
    Inbox,
    LayoutDashboard,
    LogOut,
    Menu,
    Plus,
    Store,
    UserRound,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

// Layout de la app logueada (KAN-211): navbar blanco translúcido fijo arriba
// (efecto vidrio), que suma sombra al bajar; menú de usuario con avatar; footer
// compacto. El buscador del navbar se puede ocultar: el Inicio lo muestra solo
// cuando el buscador grande salió de la pantalla.
const ROLES: Record<User['rol'], string> = {
    productor: 'Productor',
    comprador: 'Comprador',
    admin: 'Administrador',
};

export default function Authenticated({
    header,
    mostrarBuscador = true,
    children,
}: PropsWithChildren<{ header?: ReactNode; mostrarBuscador?: boolean }>) {
    const user = usePage().props.auth.user;
    const esProductor = user.rol === 'productor';

    // Link al panel admin: va a la primera sección que ya exista (KAN-219).
    const inicioAdmin = user.rol === 'admin' ? rutaInicioAdmin() : null;

    const [menuCelular, setMenuCelular] = useState(false);
    const [conSombra, setConSombra] = useState(false);

    useEffect(() => {
        const alBajar = () => setConSombra(window.scrollY > 8);
        alBajar();
        window.addEventListener('scroll', alBajar, { passive: true });
        return () => window.removeEventListener('scroll', alBajar);
    }, []);

    const enMarketplace =
        route().current('publicaciones.index') ||
        route().current('publicaciones.show');

    return (
        // overflow-x-clip: nada decorativo puede generar scroll horizontal
        // (clip, a diferencia de hidden, no rompe el navbar "sticky").
        <div className="flex min-h-screen flex-col overflow-x-clip bg-marca-fondo">
            <nav
                className={`sticky top-0 z-40 border-b bg-white/80 backdrop-blur-md transition-[box-shadow,border-color] duration-200 ${
                    conSombra
                        ? 'border-slate-200/80 shadow-[0_8px_24px_-12px_rgba(15,37,64,0.18)]'
                        : 'border-transparent'
                }`}
            >
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('publicaciones.index')}
                        className="flex shrink-0 items-center gap-2 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                    >
                        <ApplicationLogo className="h-10 w-10" />
                        <span className="hidden font-display text-lg font-extrabold tracking-tight lg:inline">
                            <span className="text-marca-azul">Agro</span>
                            <span className="text-marca-dorado">Connect</span>
                        </span>
                    </Link>

                    <div className="ms-2 hidden items-center gap-1 md:flex">
                        <NavLink
                            href={route('publicaciones.index')}
                            active={enMarketplace}
                        >
                            Marketplace
                        </NavLink>
                        {esProductor && (
                            <NavLink
                                href={route('solicitudes-compra.index')}
                                active={route().current(
                                    'solicitudes-compra.index',
                                )}
                            >
                                Solicitudes
                            </NavLink>
                        )}
                        {inicioAdmin && (
                            <NavLink href={inicioAdmin} active={false}>
                                Panel admin
                            </NavLink>
                        )}
                    </div>

                    {/* Buscador compacto: aparece/desaparece con un fundido corto. */}
                    <div className="hidden flex-1 justify-center px-2 md:flex">
                        <div
                            aria-hidden={!mostrarBuscador}
                            className={`w-full max-w-sm transition-[opacity,transform] duration-200 ease-[cubic-bezier(0.23,1,0.32,1)] ${
                                mostrarBuscador
                                    ? 'translate-y-0 opacity-100'
                                    : 'pointer-events-none -translate-y-1 opacity-0'
                            }`}
                        >
                            <BuscadorPublicaciones
                                variante="compacto"
                                inactivo={!mostrarBuscador}
                            />
                        </div>
                    </div>

                    <div className="ms-auto flex items-center gap-1 md:ms-0">
                        {esProductor && (
                            <Link
                                href={route('publicaciones.create')}
                                className="me-1 hidden items-center gap-1.5 rounded-xl bg-marca-ambar px-3.5 py-2 text-sm font-semibold text-amber-950 shadow-sm transition-transform duration-150 ease-out hover:brightness-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul active:scale-[0.97] sm:inline-flex"
                            >
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Publicar
                            </Link>
                        )}

                        <BotonIcono
                            href={route('favoritos.index')}
                            etiqueta="Mis favoritos"
                            activo={route().current('favoritos.index')}
                        >
                            <Heart className="h-5 w-5" aria-hidden="true" />
                        </BotonIcono>
                        <Campanita />

                        <div className="ms-1 hidden sm:block">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="group flex items-center gap-2 rounded-full py-1 pe-2 ps-1 transition-colors duration-150 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                                    >
                                        <Avatar nombre={user.name} />
                                        <span className="hidden max-w-[9rem] truncate text-sm font-semibold text-slate-700 lg:inline">
                                            {user.name.split(' ')[0]}
                                        </span>
                                        <ChevronDown
                                            className="h-4 w-4 text-slate-400"
                                            aria-hidden="true"
                                        />
                                        <span className="sr-only">
                                            Abrir el menú de usuario
                                        </span>
                                    </button>
                                </Dropdown.Trigger>

                                <Dropdown.Content width="64">
                                    <div className="flex items-center gap-3 px-3 pb-3 pt-2">
                                        <Avatar nombre={user.name} grande />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-slate-900">
                                                {user.name}
                                            </p>
                                            <p className="truncate text-xs text-slate-500">
                                                {user.email}
                                            </p>
                                            <span className="mt-1 inline-block rounded-full bg-sky-50 px-2 py-0.5 text-[0.7rem] font-semibold text-marca-azul-profundo">
                                                {ROLES[user.rol]}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="space-y-0.5 border-t border-slate-100 pt-1.5">
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            <UserRound
                                                className="h-4 w-4 text-slate-400"
                                                aria-hidden="true"
                                            />
                                            Mi perfil
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('favoritos.index')}
                                        >
                                            <Heart
                                                className="h-4 w-4 text-slate-400"
                                                aria-hidden="true"
                                            />
                                            Mis favoritos
                                        </Dropdown.Link>
                                        {esProductor && (
                                            <Dropdown.Link
                                                href={route(
                                                    'solicitudes-compra.index',
                                                )}
                                            >
                                                <Inbox
                                                    className="h-4 w-4 text-slate-400"
                                                    aria-hidden="true"
                                                />
                                                Solicitudes recibidas
                                            </Dropdown.Link>
                                        )}
                                        {inicioAdmin && (
                                            <Dropdown.Link href={inicioAdmin}>
                                                <LayoutDashboard
                                                    className="h-4 w-4 text-slate-400"
                                                    aria-hidden="true"
                                                />
                                                Panel admin
                                            </Dropdown.Link>
                                        )}
                                    </div>
                                    <div className="mt-1.5 border-t border-slate-100 pt-1.5">
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="text-red-600 hover:bg-red-50 hover:text-red-700 focus:bg-red-50"
                                        >
                                            <LogOut
                                                className="h-4 w-4"
                                                aria-hidden="true"
                                            />
                                            Cerrar sesión
                                        </Dropdown.Link>
                                    </div>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <button
                            type="button"
                            onClick={() =>
                                setMenuCelular((abierto) => !abierto)
                            }
                            aria-expanded={menuCelular}
                            aria-controls="menu-celular"
                            className="ms-1 inline-flex items-center justify-center rounded-xl p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul sm:hidden"
                        >
                            {menuCelular ? (
                                <X className="h-6 w-6" aria-hidden="true" />
                            ) : (
                                <Menu className="h-6 w-6" aria-hidden="true" />
                            )}
                            <span className="sr-only">
                                {menuCelular
                                    ? 'Cerrar el menú'
                                    : 'Abrir el menú'}
                            </span>
                        </button>
                    </div>
                </div>

                {menuCelular && (
                    <div
                        id="menu-celular"
                        className="border-t border-slate-200/80 px-4 pb-4 pt-3 sm:hidden"
                    >
                        <BuscadorPublicaciones variante="compacto" />

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink
                                href={route('publicaciones.index')}
                                active={enMarketplace}
                            >
                                <Store className="h-5 w-5" aria-hidden="true" />
                                Marketplace
                            </ResponsiveNavLink>
                            {esProductor && (
                                <ResponsiveNavLink
                                    href={route('publicaciones.create')}
                                    active={route().current(
                                        'publicaciones.create',
                                    )}
                                >
                                    <Plus
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                    Publicar
                                </ResponsiveNavLink>
                            )}
                            {esProductor && (
                                <ResponsiveNavLink
                                    href={route('solicitudes-compra.index')}
                                    active={route().current(
                                        'solicitudes-compra.index',
                                    )}
                                >
                                    <Inbox
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                    Solicitudes recibidas
                                </ResponsiveNavLink>
                            )}
                            {inicioAdmin && (
                                <ResponsiveNavLink href={inicioAdmin}>
                                    <LayoutDashboard
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                    Panel admin
                                </ResponsiveNavLink>
                            )}
                        </div>

                        <div className="mt-3 border-t border-slate-200 pt-3">
                            <div className="flex items-center gap-3 px-3 pb-2">
                                <Avatar nombre={user.name} grande />
                                <div className="min-w-0">
                                    <p className="truncate font-semibold text-slate-900">
                                        {user.name}
                                    </p>
                                    <p className="truncate text-sm text-slate-500">
                                        {user.email}
                                    </p>
                                </div>
                            </div>
                            <div className="space-y-1">
                                <ResponsiveNavLink
                                    href={route('profile.edit')}
                                    active={route().current('profile.edit')}
                                >
                                    <UserRound
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                    Mi perfil
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                    className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                >
                                    <LogOut
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                    Cerrar sesión
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                )}
            </nav>

            {header && (
                <header className="border-b border-slate-200/70 bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="flex-1">{children}</main>

            <PieApp />
        </div>
    );
}
