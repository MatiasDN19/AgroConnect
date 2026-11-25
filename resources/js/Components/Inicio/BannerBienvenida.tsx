import { User } from '@/types';
import { saludo } from '@/utils/fecha';
import { rutaInicioAdmin } from '@/utils/panelAdmin';
import { Link } from '@inertiajs/react';
import { ArrowRight, Plus } from 'lucide-react';

// Banner de bienvenida del Inicio (KAN-211): mismo degradé azul que el hero de la
// landing, con un texto y una acción según el rol.
const TEXTOS: Record<User['rol'], { bajada: string }> = {
    productor: {
        bajada: 'Mirá cómo van tus publicaciones y sumá tu producción para que te encuentren los compradores de la zona.',
    },
    comprador: {
        bajada: 'Encontrá productos frescos y comprale directo a los productores de la zona, sin intermediarios.',
    },
    admin: {
        bajada: 'Así ven el marketplace los usuarios. Las denuncias, socios y la configuración están en el panel.',
    },
};

export default function BannerBienvenida({ usuario }: { usuario: User }) {
    const nombre = usuario.name.split(' ')[0];
    const inicioAdmin = usuario.rol === 'admin' ? rutaInicioAdmin() : null;

    return (
        <section className="aparecer relative overflow-hidden rounded-3xl bg-gradient-to-br from-marca-azul-claro via-marca-azul to-marca-azul-profundo px-6 pb-16 pt-8 text-white sm:px-10 sm:pb-20 sm:pt-10">
            {/* Círculos decorativos, como en el hero de la landing. */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -bottom-24 right-1/4 h-48 w-48 rounded-full bg-white/5"
            />

            <div className="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div className="max-w-2xl space-y-2">
                    <h1 className="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                        {saludo()}, {nombre}
                    </h1>
                    <p className="text-white/90">
                        {TEXTOS[usuario.rol].bajada}
                    </p>
                </div>

                {usuario.rol === 'productor' && (
                    <Accion href={route('publicaciones.create')}>
                        <Plus className="h-5 w-5" aria-hidden="true" />
                        Nueva publicación
                    </Accion>
                )}
                {usuario.rol === 'comprador' && (
                    <Accion href="#catalogo">
                        Ver productos
                        <ArrowRight className="h-5 w-5" aria-hidden="true" />
                    </Accion>
                )}
                {inicioAdmin && (
                    <Accion href={inicioAdmin}>
                        Ir al panel
                        <ArrowRight className="h-5 w-5" aria-hidden="true" />
                    </Accion>
                )}
            </div>
        </section>
    );
}

// CTA ámbar, como los de la landing. "#catalogo" es un ancla común (no Inertia).
function Accion({
    href,
    children,
}: {
    href: string;
    children: React.ReactNode;
}) {
    const clases =
        'inline-flex shrink-0 items-center gap-2 self-start rounded-xl bg-marca-ambar px-5 py-3 font-semibold text-amber-950 shadow-lg shadow-amber-900/20 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-marca-azul motion-reduce:hover:translate-y-0 sm:self-auto';

    return href.startsWith('#') ? (
        <a href={href} className={clases}>
            {children}
        </a>
    ) : (
        <Link href={href} className={clases}>
            {children}
        </Link>
    );
}
