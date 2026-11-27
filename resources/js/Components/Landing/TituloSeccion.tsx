import { ReactNode } from 'react';

// Encabezado común de las secciones: etiqueta chica, título y bajada.
export default function TituloSeccion({
    etiqueta,
    titulo,
    bajada,
    centrado = false,
    claro = false,
}: {
    etiqueta: string;
    titulo: ReactNode;
    bajada?: string;
    centrado?: boolean;
    claro?: boolean;
}) {
    return (
        <div
            className={`max-w-2xl space-y-3 ${centrado ? 'mx-auto text-center' : ''}`}
        >
            <p
                className={`text-xs font-bold uppercase tracking-[0.18em] ${claro ? 'text-marca-ambar' : 'text-marca-azul-profundo'}`}
            >
                {etiqueta}
            </p>
            <h2
                className={`text-balance font-display text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl ${claro ? 'text-white' : 'text-marca-tinta'}`}
            >
                {titulo}
            </h2>
            {bajada && (
                <p className={claro ? 'text-white/80' : 'text-slate-600'}>
                    {bajada}
                </p>
            )}
        </div>
    );
}
