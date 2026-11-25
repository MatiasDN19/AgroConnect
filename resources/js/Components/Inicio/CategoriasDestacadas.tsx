import { estiloCategoria } from '@/Components/PlaceholderCategoria';
import { Categoria } from '@/types';
import { LayoutGrid, LucideIcon } from 'lucide-react';

// Categorías en círculos con su cantidad de publicaciones activas (KAN-211, cierra
// KAN-53). Tocar una filtra el catálogo; tocar la elegida (o "Todas") lo limpia.
// En el celular la fila se desliza de costado.
export default function CategoriasDestacadas({
    categorias,
    seleccionada,
    onSeleccionar,
}: {
    categorias: Required<
        Pick<Categoria, 'id' | 'nombre' | 'publicaciones_count'>
    >[];
    seleccionada: number | null;
    onSeleccionar: (categoriaId: number | null) => void;
}) {
    const total = categorias.reduce(
        (suma, c) => suma + c.publicaciones_count,
        0,
    );

    return (
        <section aria-labelledby="titulo-categorias" className="space-y-4">
            <h2
                id="titulo-categorias"
                className="font-display text-xl font-bold text-marca-tinta"
            >
                Categorías
            </h2>

            <ul className="-mx-4 flex snap-x gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0">
                <li className="snap-start">
                    <Circulo
                        Icono={LayoutGrid}
                        fondo="from-sky-50 to-blue-100 text-marca-azul"
                        nombre="Todas"
                        cantidad={total}
                        activo={seleccionada === null}
                        onClick={() => onSeleccionar(null)}
                    />
                </li>
                {categorias.map((categoria) => {
                    const { Icono, fondo } = estiloCategoria(categoria.nombre);
                    const activo = seleccionada === categoria.id;
                    return (
                        <li key={categoria.id} className="snap-start">
                            <Circulo
                                Icono={Icono}
                                fondo={fondo}
                                nombre={categoria.nombre}
                                cantidad={categoria.publicaciones_count}
                                activo={activo}
                                onClick={() =>
                                    onSeleccionar(activo ? null : categoria.id)
                                }
                            />
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}

function Circulo({
    Icono,
    fondo,
    nombre,
    cantidad,
    activo,
    onClick,
}: {
    Icono: LucideIcon;
    fondo: string;
    nombre: string;
    cantidad: number;
    activo: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={activo}
            className="group flex w-24 flex-col items-center gap-2 rounded-2xl p-2 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
        >
            <span
                className={`flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br ${fondo} ring-2 ring-offset-2 ring-offset-marca-fondo transition ${
                    activo
                        ? 'ring-marca-azul'
                        : 'ring-transparent group-hover:ring-sky-200'
                }`}
            >
                <Icono className="h-7 w-7" aria-hidden="true" />
            </span>
            <span
                className={`line-clamp-2 text-sm leading-tight ${
                    activo
                        ? 'font-semibold text-marca-azul'
                        : 'font-medium text-slate-700'
                }`}
            >
                {nombre}
            </span>
            <span className="text-xs text-slate-500">
                {cantidad === 1 ? '1 producto' : `${cantidad} productos`}
            </span>
        </button>
    );
}
