import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

// Botón cuadrado con un ícono para el navbar (favoritos, notificaciones).
// Es "relative" para que se le pueda poner un globito encima (KAN-74).
export default function BotonIcono({
    href,
    etiqueta,
    activo,
    children,
}: {
    href: string;
    etiqueta: string;
    activo: boolean;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            aria-label={etiqueta}
            title={etiqueta}
            aria-current={activo ? 'page' : undefined}
            className={`relative inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                activo
                    ? 'bg-sky-100/80 text-marca-azul-profundo'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
            }`}
        >
            {children}
        </Link>
    );
}
