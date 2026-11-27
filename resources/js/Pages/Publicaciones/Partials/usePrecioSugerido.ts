import { useEffect, useState } from 'react';

// Referencias de precio al publicar.
// GET /categorias/{id}/precio-sugerido?unidad=kg&titulo=...
// - referencias (KAN-338/340): las que carga cada mes el Consorcio (mesa
//   administrativa) para esa categoría y unidad. "coincide" = el producto
//   aparece en el título. Es el dato principal.
// - mercado (KAN-132): mediana y rango de lo que se publica en AgroConnect.
//   El backend devuelve null si hay menos de 3 publicaciones comparables.
//   Pasa a ser el dato secundario.

export type ReferenciaPrecio = {
    mediana: number;
    minimo: number;
    maximo: number;
    cantidad: number;
};

export type ReferenciaOficial = {
    id: number;
    producto: string;
    unidad: string;
    precio: number;
    coincide: boolean;
};

export type PreciosSugeridos = {
    mercado: ReferenciaPrecio | null;
    referencias: ReferenciaOficial[];
};

type Respuesta = {
    precio_mediana: number | null;
    precio_minimo: number | null;
    precio_maximo: number | null;
    cantidad: number;
    referencias?: ReferenciaOficial[];
};

const VACIO: PreciosSugeridos = { mercado: null, referencias: [] };

// La unidad y el título se escriben letra por letra: se espera a que deje de
// tipear para no hacer un pedido por cada tecla.
const ESPERA_MS = 400;

export default function usePrecioSugerido(
    categoriaId: string,
    unidad: string,
    titulo: string,
): PreciosSugeridos {
    const [precios, setPrecios] = useState<PreciosSugeridos>(VACIO);

    useEffect(() => {
        const unidadLimpia = unidad.trim();
        const tituloLimpio = titulo.trim();
        if (!categoriaId || !unidadLimpia) {
            setPrecios(VACIO);
            return;
        }

        // Si cambia algo antes de que llegue la respuesta, se cancela el pedido
        // anterior: nunca se muestran las referencias de otra combinación.
        const controlador = new AbortController();

        const espera = setTimeout(() => {
            fetch(
                route('categorias.precio-sugerido', {
                    categoria: categoriaId,
                    unidad: unidadLimpia,
                    ...(tituloLimpio ? { titulo: tituloLimpio } : {}),
                }),
                {
                    headers: { Accept: 'application/json' },
                    signal: controlador.signal,
                },
            )
                .then((respuesta) => (respuesta.ok ? respuesta.json() : null))
                .then((datos: Respuesta | null) =>
                    setPrecios({
                        mercado:
                            datos && datos.precio_mediana !== null
                                ? {
                                      mediana: datos.precio_mediana,
                                      minimo: datos.precio_minimo ?? 0,
                                      maximo: datos.precio_maximo ?? 0,
                                      cantidad: datos.cantidad,
                                  }
                                : null,
                        referencias: datos?.referencias ?? [],
                    }),
                )
                // Es solo una ayuda: si falla, simplemente no se muestra.
                .catch(() => {});
        }, ESPERA_MS);

        return () => {
            clearTimeout(espera);
            controlador.abort();
        };
    }, [categoriaId, unidad, titulo]);

    return precios;
}
