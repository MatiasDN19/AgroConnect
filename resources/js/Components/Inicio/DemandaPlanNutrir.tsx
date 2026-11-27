import { estiloCategoria } from '@/Components/PlaceholderCategoria';
import { Link } from '@inertiajs/react';
import { ArrowRight, ChevronDown, Info } from 'lucide-react';
import { useState } from 'react';
import logoPlanNutrir from '../../../images/logos/plan-nutrir.webp';

// "Este mes el Plan Nutrir necesita…" en el Inicio, solo para productores
// (KAN-341). Los datos los carga el admin en "Referencias del mes" (KAN-339) y
// los manda PublicacionController::index() como demanda_plan_nutrir (KAN-338).
// Cada producto tiene un acceso a Publicar con la categoría, el producto y la
// unidad ya cargados: así también aparece su precio de referencia (KAN-340).

export interface DemandaProducto {
    id: number;
    producto: string;
    unidad: string;
    cantidad: number;
    categoria_id: number;
    categoria: { id: number; nombre: string } | null;
}

// Cuántos se muestran antes de "Ver todos".
const VISIBLES = 6;

const formatoMes = new Intl.DateTimeFormat('es-AR', { month: 'long' });
const formatoCantidad = new Intl.NumberFormat('es-AR', {
    maximumFractionDigits: 2,
});

export default function DemandaPlanNutrir({
    demanda,
}: {
    demanda: DemandaProducto[] | null | undefined;
}) {
    const [todos, setTodos] = useState(false);

    // Sin demanda cargada (o si no es productor, llega null): no se muestra.
    if (!demanda || demanda.length === 0) return null;

    const mostrados = todos ? demanda : demanda.slice(0, VISIBLES);
    const mes = formatoMes.format(new Date());

    return (
        <section
            aria-labelledby="titulo-plan-nutrir"
            className="rounded-2xl bg-gradient-to-br from-green-50 via-white to-sky-50 p-5 shadow-sm ring-1 ring-green-100 sm:p-6"
        >
            <div className="flex flex-wrap items-center gap-4">
                <img
                    src={logoPlanNutrir}
                    alt="Plan Nutrir"
                    className="h-12 w-auto rounded-lg bg-white p-1.5 shadow-sm ring-1 ring-slate-100"
                />
                <div className="min-w-0 flex-1">
                    <h2
                        id="titulo-plan-nutrir"
                        className="font-display text-xl font-bold text-marca-tinta"
                    >
                        Este mes el Plan Nutrir necesita…
                    </h2>
                    <p className="text-sm text-slate-600">
                        Pedido de {mes}: {demanda.length}{' '}
                        {demanda.length === 1 ? 'producto' : 'productos'}.
                    </p>
                </div>
            </div>

            <ul className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {mostrados.map((item) => {
                    const { Icono, fondo } = estiloCategoria(
                        item.categoria?.nombre,
                    );
                    return (
                        <li
                            key={item.id}
                            className="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200"
                        >
                            <span
                                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${fondo}`}
                            >
                                <Icono className="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium text-marca-tinta">
                                    {item.producto}
                                </p>
                                <p className="text-sm font-semibold text-green-700">
                                    {formatoCantidad.format(item.cantidad)}{' '}
                                    {item.unidad}
                                </p>
                            </div>
                            <Link
                                href={route('publicaciones.create', {
                                    categoria_id: item.categoria_id,
                                    titulo: item.producto,
                                    unidad: item.unidad,
                                })}
                                aria-label={`Publicar ${item.producto}`}
                                className="group inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-marca-azul transition-colors duration-150 hover:bg-sky-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                            >
                                Publicar
                                <ArrowRight
                                    className="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    );
                })}
            </ul>

            {demanda.length > VISIBLES && (
                <button
                    type="button"
                    onClick={() => setTodos((t) => !t)}
                    aria-expanded={todos}
                    className="mt-3 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-marca-azul transition-colors duration-150 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                >
                    {todos ? 'Ver menos' : `Ver todos (${demanda.length})`}
                    <ChevronDown
                        className={`h-4 w-4 transition-transform duration-200 ${todos ? 'rotate-180' : ''}`}
                        aria-hidden="true"
                    />
                </button>
            )}

            <p className="mt-4 flex gap-2 text-xs text-slate-600">
                <Info
                    className="h-4 w-4 shrink-0 text-marca-azul"
                    aria-hidden="true"
                />
                Los cupos los asigna el Consorcio. Si podés ofrecer alguno de
                estos productos, publicalo o consultá en el Consorcio.
            </p>
        </section>
    );
}
