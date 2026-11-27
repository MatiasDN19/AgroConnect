import { Handshake, PackagePlus, Search } from 'lucide-react';
import { CSSProperties } from 'react';
import TituloSeccion from './TituloSeccion';

const PASOS = [
    {
        icono: PackagePlus,
        titulo: 'Publicá tu producto',
        texto: 'Cargá qué producís, el precio, la unidad y cuánto tenés disponible.',
    },
    {
        icono: Search,
        titulo: 'Encontrá quien lo necesita',
        texto: 'Buscá por categoría, mirá las reseñas y la reputación de cada productor.',
    },
    {
        icono: Handshake,
        titulo: 'Cerrá el trato directo',
        texto: 'Hablá por WhatsApp o mandá una solicitud de compra y coordiná la entrega, sin comisiones.',
    },
];

// Conversación de ejemplo entre comprador y productor (ilustración).
const MENSAJES = [
    { quien: 'comprador', texto: '¿Tenés tomate perita para esta semana?' },
    { quien: 'productor', texto: '¡Sí! Tengo 200 kg de cosecha propia 🍅' },
    { quien: 'comprador', texto: 'Genial, te escribo por WhatsApp' },
] as const;

export default function ComoFunciona() {
    return (
        <section
            id="como-funciona"
            className="scroll-mt-8 bg-white px-4 py-20 sm:px-6 lg:px-8"
        >
            <div className="mx-auto max-w-7xl space-y-12">
                <TituloSeccion
                    etiqueta="Cómo funciona"
                    titulo="Tres pasos para comprar o vender dentro del Consorcio"
                    centrado
                />

                <div className="grid items-center gap-12 lg:grid-cols-2">
                    <ol className="space-y-5">
                        {PASOS.map((paso, i) => {
                            const Icono = paso.icono;

                            return (
                                <li
                                    key={paso.titulo}
                                    className="revelar flex gap-5 rounded-2xl border border-slate-200 bg-marca-fondo/60 p-5"
                                    style={{ '--orden': i } as CSSProperties}
                                >
                                    <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-marca-azul text-white">
                                        <Icono
                                            className="h-6 w-6"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <h3 className="font-display text-lg font-bold text-marca-tinta">
                                            <span className="text-marca-azul-profundo">
                                                {i + 1}.
                                            </span>{' '}
                                            {paso.titulo}
                                        </h3>
                                        <p className="mt-1 text-slate-600">
                                            {paso.texto}
                                        </p>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>

                    {/* Ilustración: conversación directa, sin intermediarios */}
                    <div
                        aria-hidden="true"
                        className="relative mx-auto w-full max-w-md rounded-3xl bg-gradient-to-br from-sky-50 to-amber-50 p-6 sm:p-8"
                    >
                        <div className="space-y-4">
                            {MENSAJES.map((mensaje, i) => {
                                const esProductor =
                                    mensaje.quien === 'productor';

                                return (
                                    <div
                                        key={mensaje.texto}
                                        className={`flotar flex items-end gap-2 ${esProductor ? 'flex-row-reverse' : ''}`}
                                        style={
                                            { '--orden': i } as CSSProperties
                                        }
                                    >
                                        <span
                                            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white ${
                                                esProductor
                                                    ? 'bg-marca-ambar text-amber-950'
                                                    : 'bg-marca-azul'
                                            }`}
                                        >
                                            {esProductor ? 'P' : 'C'}
                                        </span>
                                        <p
                                            className={`max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-sm ${
                                                esProductor
                                                    ? 'rounded-br-sm bg-white text-marca-tinta'
                                                    : 'rounded-bl-sm bg-marca-azul text-white'
                                            }`}
                                        >
                                            {mensaje.texto}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>

                        <p className="mt-6 text-center text-xs font-medium text-slate-500">
                            Comprador y productor, cara a cara. Sin
                            intermediarios.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}
