import { useEffect, useState } from 'react';

// Referencia de precios de la categoría para la misma unidad (KAN-132).
// GET /categorias/{id}/precio-sugerido?unidad=kg. El backend devuelve null en los
// precios si hay menos de 3 publicaciones activas comparables: ahí no se muestra nada.
export type ReferenciaPrecio = {
    mediana: number;
    minimo: number;
    maximo: number;
    cantidad: number;
};

type Respuesta = {
    precio_mediana: number | null;
    precio_minimo: number | null;
    precio_maximo: number | null;
    cantidad: number;
};

// La unidad se escribe letra por letra: se espera a que deje de tipear
// para no hacer un pedido por cada tecla.
const ESPERA_MS = 400;

export default function usePrecioSugerido(
    categoriaId: string,
    unidad: string,
): ReferenciaPrecio | null {
    const [referencia, setReferencia] = useState<ReferenciaPrecio | null>(null);

    useEffect(() => {
        setReferencia(null);

        const unidadLimpia = unidad.trim();
        if (!categoriaId || !unidadLimpia) return;

        // Si cambia la categoría o la unidad antes de que llegue la respuesta, se
        // cancela el pedido anterior: nunca se muestra la referencia de otra combinación.
        const controlador = new AbortController();

        const espera = setTimeout(() => {
            fetch(
                route('categorias.precio-sugerido', {
                    categoria: categoriaId,
                    unidad: unidadLimpia,
                }),
                {
                    headers: { Accept: 'application/json' },
                    signal: controlador.signal,
                },
            )
                .then((respuesta) => (respuesta.ok ? respuesta.json() : null))
                .then((datos: Respuesta | null) =>
                    setReferencia(
                        datos && datos.precio_mediana !== null
                            ? {
                                  mediana: datos.precio_mediana,
                                  minimo: datos.precio_minimo ?? 0,
                                  maximo: datos.precio_maximo ?? 0,
                                  cantidad: datos.cantidad,
                              }
                            : null,
                    ),
                )
                // Es solo una referencia: si falla, simplemente no se muestra.
                .catch(() => {});
        }, ESPERA_MS);

        return () => {
            clearTimeout(espera);
            controlador.abort();
        };
    }, [categoriaId, unidad]);

    return referencia;
}
