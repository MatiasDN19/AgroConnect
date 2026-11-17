import ApplicationLogo from '@/Components/ApplicationLogo';
import { User } from '@/types';
import { Link } from '@inertiajs/react';

// Links a las secciones de la misma página (anclas).
export const SECCIONES = [
    { id: 'como-funciona', texto: 'Cómo funciona' },
    { id: 'beneficios', texto: 'Beneficios' },
    { id: 'consorcio', texto: 'El Consorcio' },
    { id: 'preguntas', texto: 'Preguntas' },
];

export function LogoConNombre({ claro = false }: { claro?: boolean }) {
    return (
        <span className="flex items-center gap-2.5">
            <ApplicationLogo className="h-14 w-14 rounded-full bg-white" />
            <span className="font-display text-xl font-extrabold tracking-tight">
                <span className={claro ? 'text-white' : 'text-marca-azul'}>
                    Agro
                </span>
                <span
                    className={claro ? 'text-marca-ambar' : 'text-marca-dorado'}
                >
                    Connect
                </span>
            </span>
        </span>
    );
}

export default function Navegacion({ usuario }: { usuario: User | null }) {
    return (
        <header className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <Link href="/" aria-label="AgroConnect, inicio">
                <LogoConNombre />
            </Link>

            <nav
                aria-label="Secciones"
                className="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex"
            >
                {SECCIONES.map((seccion) => (
                    <a
                        key={seccion.id}
                        href={`#${seccion.id}`}
                        className="hover:text-marca-azul"
                    >
                        {seccion.texto}
                    </a>
                ))}
            </nav>

            <Link
                href={usuario ? route('publicaciones.index') : route('login')}
                className="rounded-xl bg-marca-azul px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-marca-azul-profundo focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2"
            >
                {usuario ? 'Ir al marketplace' : 'Ingresar'}
            </Link>
        </header>
    );
}
