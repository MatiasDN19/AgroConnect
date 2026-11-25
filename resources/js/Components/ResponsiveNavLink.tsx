import { InertiaLinkProps, Link } from '@inertiajs/react';

// Link del menú del celular (KAN-211), con el mismo estilo de "pastilla" del navbar.
export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}: InertiaLinkProps & { active?: boolean }) {
    return (
        <Link
            {...props}
            aria-current={active ? 'page' : undefined}
            className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-base font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                active
                    ? 'bg-sky-100/80 text-marca-azul-profundo'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
