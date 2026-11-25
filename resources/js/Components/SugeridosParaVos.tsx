import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import { ChevronLeft, ChevronRight, Sparkles } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

// "Sugeridos para vos" (KAN-170): fila de publicaciones que se desliza de costado.
// GET /publicaciones/sugeridas (KAN-164) devuelve hasta 10 publicaciones activas de
// las categorías que el usuario favoriteó o reseñó, con los socios del Consorcio
// primero; sin historial, las más recientes. Nunca las del propio usuario.
// Componente aparte para poder reubicarlo en el inicio del marketplace (KAN-211).
export default function SugeridosParaVos() {
    const [sugeridas, setSugeridas] = useState<PublicacionResumen[] | null>(
        null,
    );
    const fila = useRef<HTMLUListElement>(null);
    const [enInicio, setEnInicio] = useState(true);
    const [enFinal, setEnFinal] = useState(false);

    useEffect(() => {
        const controlador = new AbortController();

        fetch(route('publicaciones.sugeridas'), {
            headers: { Accept: 'application/json' },
            signal: controlador.signal,
        })
            .then((respuesta) => (respuesta.ok ? respuesta.json() : null))
            .then((datos: { data: PublicacionResumen[] } | null) =>
                setSugeridas(datos?.data ?? []),
            )
            // Es un extra del listado: si falla, la sección simplemente no aparece.
            .catch(() => {});

        return () => controlador.abort();
    }, []);

    // Habilita o deshabilita las flechas según dónde está la fila.
    const actualizarFlechas = useCallback(() => {
        const el = fila.current;
        if (!el) return;
        setEnInicio(el.scrollLeft <= 4);
        setEnFinal(el.scrollLeft + el.clientWidth >= el.scrollWidth - 4);
    }, []);

    useEffect(() => {
        actualizarFlechas();
        window.addEventListener('resize', actualizarFlechas);
        return () => window.removeEventListener('resize', actualizarFlechas);
    }, [sugeridas, actualizarFlechas]);

    const desplazar = (direccion: 1 | -1) => {
        const el = fila.current;
        if (!el) return;
        const reducir = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        el.scrollBy({
            left: direccion * el.clientWidth * 0.85,
            behavior: reducir ? 'auto' : 'smooth',
        });
    };

    // Sin datos todavía o sin sugeridas: no se muestra nada (ni el título).
    if (!sugeridas || sugeridas.length === 0) return null;

    const hayMasDeUnaPantalla = !(enInicio && enFinal);

    return (
        <section
            aria-labelledby="titulo-sugeridos"
            className="aparecer space-y-4"
        >
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2
                        id="titulo-sugeridos"
                        className="flex items-center gap-2 font-display text-xl font-bold text-marca-tinta"
                    >
                        <Sparkles
                            className="h-5 w-5 text-marca-ambar"
                            aria-hidden="true"
                        />
                        Sugeridos para vos
                    </h2>
                    <p className="text-sm text-gray-500">
                        Elegidas a partir de lo que te interesa.
                    </p>
                </div>

                {hayMasDeUnaPantalla && (
                    <div className="hidden gap-2 sm:flex">
                        <BotonFlecha
                            etiqueta="Ver sugeridas anteriores"
                            onClick={() => desplazar(-1)}
                            deshabilitado={enInicio}
                        >
                            <ChevronLeft className="h-5 w-5" />
                        </BotonFlecha>
                        <BotonFlecha
                            etiqueta="Ver más sugeridas"
                            onClick={() => desplazar(1)}
                            deshabilitado={enFinal}
                        >
                            <ChevronRight className="h-5 w-5" />
                        </BotonFlecha>
                    </div>
                )}
            </div>

            {/* snap: al soltar, la fila "encaja" en una tarjeta. pb deja lugar a la barra. */}
            <ul
                ref={fila}
                onScroll={actualizarFlechas}
                className="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-4 pb-3 motion-reduce:scroll-auto sm:mx-0 sm:px-0"
            >
                {sugeridas.map((publicacion) => (
                    <li
                        key={publicacion.id}
                        className="w-64 shrink-0 snap-start sm:w-72"
                    >
                        <PublicacionCard publicacion={publicacion} />
                    </li>
                ))}
            </ul>
        </section>
    );
}

function BotonFlecha({
    etiqueta,
    onClick,
    deshabilitado,
    children,
}: {
    etiqueta: string;
    onClick: () => void;
    deshabilitado: boolean;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={deshabilitado}
            aria-label={etiqueta}
            className="rounded-full bg-white p-2 text-gray-700 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-40"
        >
            {children}
        </button>
    );
}
