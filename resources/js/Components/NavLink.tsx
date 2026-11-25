import { InertiaLinkProps, Link } from '@inertiajs/react';

// Link del navbar (KAN-211): la sección activa se marca con una "pastilla" azul.
export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}: InertiaLinkProps & { active: boolean }) {
    return (
        <Link
            {...props}
            aria-current={active ? 'page' : undefined}
            className={
                'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ' +
                (active
                    ? 'bg-sky-100/80 text-marca-azul-profundo'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900') +
                ` ${className}`
            }
        >
            {children}
        </Link>
    );
}
