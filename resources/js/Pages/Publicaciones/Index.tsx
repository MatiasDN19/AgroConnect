import BuscadorPublicaciones from '@/Components/BuscadorPublicaciones';
import BannerBienvenida from '@/Components/Inicio/BannerBienvenida';
import BannersInformativos from '@/Components/Inicio/BannersInformativos';
import CategoriasDestacadas from '@/Components/Inicio/CategoriasDestacadas';
import DemandaPlanNutrir, {
    DemandaProducto,
} from '@/Components/Inicio/DemandaPlanNutrir';
import OrdenCercania from '@/Components/OrdenCercania';
import Paginacion from '@/Components/Paginacion';
import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import SugeridosParaVos from '@/Components/SugeridosParaVos';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Categoria, PageProps, Paginado } from '@/types';
import { irAlListado } from '@/utils/listado';
import { Head, Link, usePage } from '@inertiajs/react';
import { SearchX, X } from 'lucide-react';
import { CSSProperties, useEffect, useRef, useState } from 'react';

// Inicio del marketplace (KAN-211), de arriba hacia abajo: bienvenida según el rol
// con el buscador grande apoyado en su borde, categorías, sugeridos (KAN-170),
// banners informativos y el catálogo paginado. Los datos los manda
// PublicacionController::index() (KAN-209). El resumen del productor que también
// manda se va a mostrar en "Mis publicaciones" (tarea aparte).
export default function Index({
    publicaciones,
    categorias,
    filtros,
    lat,
    lng,
    demanda_plan_nutrir,
}: PageProps<{
    // Solo para productores (KAN-338); null para el resto.
    demanda_plan_nutrir?: DemandaProducto[] | null;
    publicaciones: Paginado<PublicacionResumen>;
    categorias: Required<
        Pick<Categoria, 'id' | 'nombre' | 'publicaciones_count'>
    >[];
    filtros: { q?: string | null; categoria_id?: string | null };
    lat?: string | null;
    lng?: string | null;
}>) {
    const { auth } = usePage<PageProps>().props;
    const usuario = auth.user;

    const categoriaId = filtros.categoria_id
        ? Number(filtros.categoria_id)
        : null;
    const categoriaElegida = categorias.find((c) => c.id === categoriaId);
    const hayFiltros = Boolean(filtros.q) || categoriaId !== null;

    // Mientras el buscador grande se ve, el del navbar se esconde (no tiene
    // sentido tener dos a la vista). Cuando sale de la pantalla, aparece arriba.
    const buscadorGrande = useRef<HTMLFormElement>(null);
    const [grandeVisible, setGrandeVisible] = useState(true);

    useEffect(() => {
        const el = buscadorGrande.current;
        if (!el || !('IntersectionObserver' in window)) return;
        // rootMargin: descuenta el alto del navbar fijo (4rem).
        const observador = new IntersectionObserver(
            ([entrada]) => setGrandeVisible(entrada.isIntersecting),
            { rootMargin: '-64px 0px 0px 0px' },
        );
        observador.observe(el);
        return () => observador.disconnect();
    }, []);

    return (
        <AuthenticatedLayout mostrarBuscador={!grandeVisible}>
            <Head title="Marketplace" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
                    <div>
                        <BannerBienvenida usuario={usuario} />
                        {/* -mt: el buscador "pisa" el borde inferior del banner. */}
                        <div
                            className="aparecer relative z-10 mx-auto -mt-7 max-w-3xl px-2 sm:px-6"
                            style={{ '--orden': 2 } as CSSProperties}
                        >
                            <BuscadorPublicaciones
                                ref={buscadorGrande}
                                variante="grande"
                            />
                        </div>
                    </div>

                    <CategoriasDestacadas
                        categorias={categorias}
                        seleccionada={categoriaId}
                        onSeleccionar={(id) =>
                            irAlListado({ categoria_id: id })
                        }
                    />

                    {/* Solo productores y solo si hay demanda cargada (KAN-341). */}
                    <DemandaPlanNutrir demanda={demanda_plan_nutrir} />

                    {/* No aparece si no hay sugeridas. */}
                    <SugeridosParaVos />

                    <BannersInformativos usuario={usuario} />

                    {/* scroll-mt: al bajar con el ancla o la paginación, que no quede pegado arriba. */}
                    <section
                        id="catalogo"
                        aria-labelledby="titulo-catalogo"
                        className="scroll-mt-6 space-y-5"
                    >
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="space-y-2">
                                <h2
                                    id="titulo-catalogo"
                                    className="font-display text-xl font-bold text-marca-tinta"
                                >
                                    {categoriaElegida
                                        ? categoriaElegida.nombre
                                        : 'Todas las publicaciones'}
                                </h2>
                                <p className="text-sm text-slate-600">
                                    {publicaciones.total === 1
                                        ? '1 publicación'
                                        : `${publicaciones.total} publicaciones`}
                                </p>

                                {hayFiltros && (
                                    <div className="flex flex-wrap gap-2">
                                        {filtros.q && (
                                            <FiltroActivo
                                                onQuitar={() =>
                                                    irAlListado({ q: null })
                                                }
                                            >
                                                “{filtros.q}”
                                            </FiltroActivo>
                                        )}
                                        {categoriaElegida && (
                                            <FiltroActivo
                                                onQuitar={() =>
                                                    irAlListado({
                                                        categoria_id: null,
                                                    })
                                                }
                                            >
                                                {categoriaElegida.nombre}
                                            </FiltroActivo>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* KAN-171: el orden activo sale de la URL (lat/lng). */}
                            <OrdenCercania
                                activo={lat != null && lng != null}
                            />
                        </div>

                        {publicaciones.data.length === 0 ? (
                            <SinResultados
                                hayFiltros={hayFiltros}
                                esProductor={usuario.rol === 'productor'}
                            />
                        ) : (
                            <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {publicaciones.data.map((publicacion, i) => (
                                    // Entrada escalonada corta (tope de 6 pasos
                                    // para que las últimas no tarden).
                                    <li
                                        key={publicacion.id}
                                        className="aparecer"
                                        style={
                                            {
                                                '--orden': Math.min(i, 6),
                                            } as CSSProperties
                                        }
                                    >
                                        <PublicacionCard
                                            publicacion={publicacion}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}

                        <Paginacion paginado={publicaciones} ancla="catalogo" />
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function FiltroActivo({
    onQuitar,
    children,
}: {
    onQuitar: () => void;
    children: React.ReactNode;
}) {
    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 py-1 pl-3 pr-1 text-sm font-medium text-marca-azul-profundo">
            {children}
            <button
                type="button"
                onClick={onQuitar}
                aria-label={`Quitar el filtro ${children}`}
                className="rounded-full p-0.5 hover:bg-sky-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
            >
                <X className="h-4 w-4" aria-hidden="true" />
            </button>
        </span>
    );
}

function SinResultados({
    hayFiltros,
    esProductor,
}: {
    hayFiltros: boolean;
    esProductor: boolean;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-2xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-slate-200">
            <SearchX className="h-10 w-10 text-slate-300" aria-hidden="true" />
            {hayFiltros ? (
                <>
                    <p className="font-medium text-slate-700">
                        No encontramos publicaciones con esos filtros.
                    </p>
                    <button
                        type="button"
                        onClick={() =>
                            irAlListado({ q: null, categoria_id: null })
                        }
                        className="text-sm font-semibold text-marca-azul hover:underline"
                    >
                        Ver todas las publicaciones
                    </button>
                </>
            ) : (
                <>
                    <p className="font-medium text-slate-700">
                        Todavía no hay publicaciones activas.
                    </p>
                    {esProductor && (
                        <Link
                            href={route('publicaciones.create')}
                            className="text-sm font-semibold text-marca-azul hover:underline"
                        >
                            Creá la primera
                        </Link>
                    )}
                </>
            )}
        </div>
    );
}
