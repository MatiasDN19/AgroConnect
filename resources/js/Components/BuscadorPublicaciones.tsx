import { irAlListado } from '@/utils/listado';
import { router, usePage } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { FormEvent, forwardRef, useEffect, useState } from 'react';

// Buscador de publicaciones (KAN-211). Busca por título o descripción (?q=, KAN-209).
// - "grande": el del Inicio, centrado sobre el borde del banner.
// - "compacto": el del navbar.
// Busca al apretar Enter (o el botón), no mientras escribís: cada búsqueda es una
// visita al servidor y así no se dispara una por letra.
const BuscadorPublicaciones = forwardRef<
    HTMLFormElement,
    {
        variante: 'grande' | 'compacto';
        // Oculto (ej. el del navbar mientras se ve el grande): no se puede enfocar con Tab.
        inactivo?: boolean;
        className?: string;
    }
>(function BuscadorPublicaciones(
    { variante, inactivo = false, className = '' },
    ref,
) {
    const { url } = usePage();
    const qActual = new URLSearchParams(url.split('?')[1] ?? '').get('q') ?? '';
    const [valor, setValor] = useState(qActual);

    // Si la búsqueda cambia desde otro lado (ej. el chip "✕" del catálogo), se sincroniza.
    useEffect(() => setValor(qActual), [qActual]);

    const buscar = (q: string) => {
        if (route().current('publicaciones.index')) {
            irAlListado({ q: q.trim() || null });
        } else {
            router.get(
                route('publicaciones.index'),
                q.trim() ? { q: q.trim() } : {},
            );
        }
    };

    const enviar = (e: FormEvent) => {
        e.preventDefault();
        buscar(valor);
    };

    const limpiar = () => {
        setValor('');
        if (qActual) buscar('');
    };

    const grande = variante === 'grande';

    return (
        <form
            ref={ref}
            role="search"
            onSubmit={enviar}
            className={`relative flex items-center ${className}`}
        >
            <label htmlFor={`buscar-${variante}`} className="sr-only">
                Buscar productos
            </label>
            <Search
                className={`pointer-events-none absolute text-slate-400 ${
                    grande ? 'left-5 h-5 w-5' : 'left-3 h-4 w-4'
                }`}
                aria-hidden="true"
            />
            <input
                id={`buscar-${variante}`}
                type="search"
                value={valor}
                onChange={(e) => setValor(e.target.value)}
                placeholder={
                    grande
                        ? '¿Qué estás buscando? Ej: tomate, miel, huevos…'
                        : 'Buscar productos…'
                }
                autoComplete="off"
                enterKeyHint="search"
                tabIndex={inactivo ? -1 : undefined}
                className={`w-full border-0 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-marca-azul [&::-webkit-search-cancel-button]:hidden ${
                    grande
                        ? 'h-14 rounded-2xl pl-12 pr-36 text-base shadow-xl shadow-sky-900/10 ring-1 ring-slate-200 sm:pr-40'
                        : 'h-10 rounded-xl bg-slate-100 pl-9 pr-9 text-sm ring-1 ring-transparent focus:bg-white'
                }`}
            />

            {valor && (
                <button
                    type="button"
                    onClick={limpiar}
                    aria-label="Borrar la búsqueda"
                    className={`absolute rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                        grande ? 'right-32 sm:right-36' : 'right-2'
                    }`}
                >
                    <X className="h-4 w-4" aria-hidden="true" />
                </button>
            )}

            {grande && (
                <button
                    type="submit"
                    className="absolute right-2 inline-flex h-10 items-center gap-2 rounded-xl bg-marca-ambar px-4 font-semibold text-amber-950 transition-transform duration-150 ease-out hover:brightness-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul active:scale-[0.97] sm:px-5"
                >
                    <Search className="h-4 w-4" aria-hidden="true" />
                    Buscar
                </button>
            )}
        </form>
    );
});

export default BuscadorPublicaciones;
