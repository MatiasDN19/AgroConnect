import { LucideIcon, Store, Tags, Users } from 'lucide-react';
import { CSSProperties } from 'react';
import Contador from './Contador';

// Datos que manda LandingController (KAN-200): conteos reales de la base.
export interface EstadisticasLanding {
    productores: number;
    publicaciones_activas: number;
    categorias: number;
}

export default function Estadisticas({
    estadisticas,
}: {
    estadisticas: EstadisticasLanding;
}) {
    const datos: { valor: number; texto: string; icono: LucideIcon }[] = [
        {
            valor: estadisticas.productores,
            texto: 'productores registrados',
            icono: Users,
        },
        {
            valor: estadisticas.publicaciones_activas,
            texto: 'publicaciones activas',
            icono: Store,
        },
        {
            valor: estadisticas.categorias,
            texto: 'categorías de productos',
            icono: Tags,
        },
    ];

    return (
        <section
            aria-label="AgroConnect en números"
            className="px-4 sm:px-6 lg:px-8"
        >
            <div className="mx-auto grid max-w-5xl gap-px overflow-hidden rounded-2xl bg-white/20 shadow-lg sm:grid-cols-3">
                {datos.map((dato, i) => {
                    const Icono = dato.icono;

                    return (
                        <div
                            key={dato.texto}
                            className="revelar flex items-center gap-4 bg-marca-azul-profundo px-6 py-6 text-white sm:justify-center"
                            style={{ '--orden': i } as CSSProperties}
                        >
                            <Icono
                                className="h-8 w-8 shrink-0 text-marca-ambar"
                                aria-hidden="true"
                            />
                            <div className="leading-tight">
                                <p className="font-display text-3xl font-extrabold">
                                    <Contador valor={dato.valor} />
                                </p>
                                <p className="text-sm text-white/75">
                                    {dato.texto}
                                </p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
