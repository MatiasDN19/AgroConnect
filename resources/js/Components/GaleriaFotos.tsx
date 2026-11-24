import PlaceholderCategoria from '@/Components/PlaceholderCategoria';
import { PublicacionFoto } from '@/types';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';

// Galería del detalle de la publicación (KAN-205): foto principal grande con flechas
// y miniaturas para elegir. Sin fotos, el placeholder de la categoría.
export default function GaleriaFotos({
    fotos,
    titulo,
    categoria,
}: {
    fotos: PublicacionFoto[];
    titulo: string;
    categoria: string;
}) {
    const [actual, setActual] = useState(0);

    if (fotos.length === 0) {
        return (
            <PlaceholderCategoria
                categoria={categoria}
                className="aspect-[4/3] w-full rounded-lg"
            />
        );
    }

    const total = fotos.length;
    const ir = (indice: number) => setActual((indice + total) % total);

    return (
        <div className="space-y-3">
            <div className="relative overflow-hidden rounded-lg bg-gray-100">
                <img
                    src={fotos[actual].url}
                    alt={`${titulo}: foto ${actual + 1} de ${total}`}
                    className="aspect-[4/3] w-full object-cover"
                />

                {total > 1 && (
                    <>
                        <BotonFlecha
                            etiqueta="Foto anterior"
                            lado="izquierda"
                            onClick={() => ir(actual - 1)}
                        />
                        <BotonFlecha
                            etiqueta="Foto siguiente"
                            lado="derecha"
                            onClick={() => ir(actual + 1)}
                        />
                        <span className="absolute bottom-2 right-2 rounded-full bg-black/55 px-2.5 py-0.5 text-xs font-medium text-white">
                            {actual + 1} / {total}
                        </span>
                    </>
                )}
            </div>

            {total > 1 && (
                <ul className="flex gap-2 overflow-x-auto pb-1">
                    {fotos.map((foto, i) => (
                        <li key={foto.id} className="shrink-0">
                            <button
                                type="button"
                                onClick={() => setActual(i)}
                                aria-label={`Ver foto ${i + 1} de ${total}`}
                                aria-current={i === actual ? 'true' : undefined}
                                className={`block h-16 w-20 overflow-hidden rounded-md ring-2 transition focus:outline-none focus-visible:ring-indigo-500 ${
                                    i === actual
                                        ? 'ring-indigo-500'
                                        : 'opacity-70 ring-transparent hover:opacity-100'
                                }`}
                            >
                                <img
                                    src={foto.url}
                                    alt=""
                                    loading="lazy"
                                    className="h-full w-full object-cover"
                                />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function BotonFlecha({
    etiqueta,
    lado,
    onClick,
}: {
    etiqueta: string;
    lado: 'izquierda' | 'derecha';
    onClick: () => void;
}) {
    const Icono = lado === 'izquierda' ? ChevronLeft : ChevronRight;

    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={etiqueta}
            className={`absolute top-1/2 -translate-y-1/2 rounded-full bg-white/85 p-2 text-gray-800 shadow transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 ${
                lado === 'izquierda' ? 'left-2' : 'right-2'
            }`}
        >
            <Icono className="h-5 w-5" aria-hidden="true" />
        </button>
    );
}
