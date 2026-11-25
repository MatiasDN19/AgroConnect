import { Paginado } from '@/types';
import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

// Paginación de una respuesta de ->paginate() (KAN-209/211). Laravel manda en
// "links": [anterior, 1, 2, "...", 9, siguiente]. Los extremos los dibujamos con
// flechas (el label viene traducido con &laquo;) y los del medio son números.
// Al cambiar de página se baja hasta el ancla indicada, no al principio de todo.
export default function Paginacion({
    paginado,
    ancla,
}: {
    paginado: Pick<Paginado<unknown>, 'links' | 'last_page'>;
    ancla?: string;
}) {
    if (paginado.last_page <= 1) return null;

    const anterior = paginado.links[0];
    const siguiente = paginado.links[paginado.links.length - 1];
    const paginas = paginado.links.slice(1, -1);

    const ir = (url: string | null) => {
        if (!url) return;
        router.visit(url, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                const reducir = window.matchMedia(
                    '(prefers-reduced-motion: reduce)',
                ).matches;
                document
                    .getElementById(ancla ?? '')
                    ?.scrollIntoView({ behavior: reducir ? 'auto' : 'smooth' });
            },
        });
    };

    return (
        <nav aria-label="Paginación" className="flex justify-center">
            <ul className="flex flex-wrap items-center gap-1">
                <li>
                    <Boton
                        onClick={() => ir(anterior.url)}
                        deshabilitado={!anterior.url}
                        etiqueta="Página anterior"
                    >
                        <ChevronLeft className="h-5 w-5" aria-hidden="true" />
                    </Boton>
                </li>
                {paginas.map((link, i) =>
                    link.url === null ? (
                        // Los "..." no tienen url.
                        <li key={`puntos-${i}`} className="px-2 text-slate-400">
                            …
                        </li>
                    ) : (
                        <li key={link.label}>
                            <Boton
                                onClick={() => ir(link.url)}
                                activo={link.active}
                                etiqueta={`Página ${link.label}`}
                            >
                                {link.label}
                            </Boton>
                        </li>
                    ),
                )}
                <li>
                    <Boton
                        onClick={() => ir(siguiente.url)}
                        deshabilitado={!siguiente.url}
                        etiqueta="Página siguiente"
                    >
                        <ChevronRight className="h-5 w-5" aria-hidden="true" />
                    </Boton>
                </li>
            </ul>
        </nav>
    );
}

function Boton({
    onClick,
    activo = false,
    deshabilitado = false,
    etiqueta,
    children,
}: {
    onClick: () => void;
    activo?: boolean;
    deshabilitado?: boolean;
    etiqueta: string;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={deshabilitado || activo}
            aria-label={etiqueta}
            aria-current={activo ? 'page' : undefined}
            className={`flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl px-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul disabled:cursor-default ${
                activo
                    ? 'bg-marca-azul text-white shadow-sm'
                    : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-sky-50 disabled:opacity-40 disabled:hover:bg-white'
            }`}
        >
            {children}
        </button>
    );
}
