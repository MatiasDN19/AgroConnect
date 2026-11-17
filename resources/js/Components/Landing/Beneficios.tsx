import {
    BadgeCheck,
    LineChart,
    LucideIcon,
    MapPin,
    MessageCircle,
    ShieldCheck,
    Sprout,
} from 'lucide-react';
import { CSSProperties } from 'react';
import TituloSeccion from './TituloSeccion';

type Beneficio = { icono: LucideIcon; titulo: string; texto: string };

const PARA_PRODUCTORES: Beneficio[] = [
    {
        icono: Sprout,
        titulo: 'Mostrá lo que producís',
        texto: 'Publicá tus productos en minutos y llegá a compradores de toda la zona.',
    },
    {
        icono: LineChart,
        titulo: 'Precios de referencia',
        texto: 'Al publicar ves el precio promedio de tu categoría para no quedar ni caro ni barato.',
    },
    {
        icono: BadgeCheck,
        titulo: 'Prioridad para socios',
        texto: 'Si sos socio del Consorcio, tus publicaciones aparecen primero.',
    },
];

const PARA_COMPRADORES: Beneficio[] = [
    {
        icono: MapPin,
        titulo: 'Productos de cerca',
        texto: 'Comprá directo a productores de la región, más frescos y sin vueltas.',
    },
    {
        icono: ShieldCheck,
        titulo: 'Reseñas reales',
        texto: 'Leé la experiencia de otros compradores antes de decidir.',
    },
    {
        icono: MessageCircle,
        titulo: 'Contacto directo',
        texto: 'Hablá con el productor por WhatsApp o mandale una solicitud de compra.',
    },
];

export default function Beneficios() {
    return (
        <section
            id="beneficios"
            className="scroll-mt-8 bg-white px-4 py-20 sm:px-6 lg:px-8"
        >
            <div className="mx-auto max-w-7xl space-y-12">
                <TituloSeccion
                    etiqueta="Beneficios"
                    titulo="Pensado para los dos lados del mostrador"
                    centrado
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <Columna
                        titulo="Para productores"
                        beneficios={PARA_PRODUCTORES}
                        acento="ambar"
                    />
                    <Columna
                        titulo="Para compradores"
                        beneficios={PARA_COMPRADORES}
                        acento="azul"
                    />
                </div>
            </div>
        </section>
    );
}

function Columna({
    titulo,
    beneficios,
    acento,
}: {
    titulo: string;
    beneficios: Beneficio[];
    acento: 'ambar' | 'azul';
}) {
    const colorIcono =
        acento === 'ambar'
            ? 'bg-amber-100 text-amber-800'
            : 'bg-sky-100 text-marca-azul-profundo';

    return (
        <div className="revelar rounded-3xl border border-slate-200 bg-marca-fondo/60 p-6 sm:p-8">
            <h3 className="font-display text-2xl font-extrabold text-marca-tinta">
                {titulo}
            </h3>

            <ul className="mt-6 space-y-5">
                {beneficios.map((beneficio, i) => {
                    const Icono = beneficio.icono;

                    return (
                        <li
                            key={beneficio.titulo}
                            className="revelar flex gap-4"
                            style={{ '--orden': i } as CSSProperties}
                        >
                            <span
                                className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${colorIcono}`}
                            >
                                <Icono className="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div>
                                <p className="font-semibold text-marca-tinta">
                                    {beneficio.titulo}
                                </p>
                                <p className="text-slate-600">
                                    {beneficio.texto}
                                </p>
                            </div>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
