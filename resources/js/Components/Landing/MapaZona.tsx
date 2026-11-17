import { CSSProperties, useMemo } from 'react';
import TituloSeccion from './TituloSeccion';

// Silueta simplificada de la provincia de Formosa (ríos Pilcomayo al norte,
// Bermejo al sur y Paraguay al este), en coordenadas del dibujo (0–400).
// Es ilustrativa: no muestra ubicaciones reales de productores (privacidad).
const SILUETA: [number, number][] = [
    [12, 12],
    [120, 80],
    [220, 150],
    [300, 205],
    [392, 268],
    [370, 320],
    [328, 392],
    [260, 340],
    [180, 280],
    [90, 215],
    [12, 160],
];

// Pines decorativos por zona (posición aproximada dentro del dibujo).
const ZONAS = [
    {
        nombre: 'Laguna Naineck',
        x: 349,
        y: 258,
        lado: 'izquierda',
        destacada: true,
    },
    { nombre: 'Clorinda', x: 381, y: 266, lado: 'arriba', destacada: false },
    {
        nombre: 'Formosa Capital',
        x: 345,
        y: 334,
        lado: 'izquierda',
        destacada: false,
    },
] as const;

const PASO = 10;

// ¿El punto (x, y) cae dentro del polígono? (algoritmo del rayo)
function dentro(x: number, y: number, poligono: [number, number][]) {
    let adentro = false;
    for (let i = 0, j = poligono.length - 1; i < poligono.length; j = i++) {
        const [xi, yi] = poligono[i];
        const [xj, yj] = poligono[j];
        const cruza =
            yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi;
        if (cruza) adentro = !adentro;
    }
    return adentro;
}

export default function MapaZona() {
    // La grilla de puntos se calcula una sola vez.
    const puntos = useMemo(() => {
        const lista: [number, number][] = [];
        for (let x = PASO / 2; x < 400; x += PASO) {
            for (let y = PASO / 2; y < 400; y += PASO) {
                if (dentro(x, y, SILUETA)) lista.push([x, y]);
            }
        }
        return lista;
    }, []);

    return (
        <section className="px-4 py-20 sm:px-6 lg:px-8">
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2">
                <div className="space-y-6">
                    <TituloSeccion
                        etiqueta="Nuestra zona"
                        titulo="Productores de toda la zona del Consorcio"
                        bajada="AgroConnect nace en Laguna Naineck y conecta a productores y compradores de la región: de Laguna Naineck a Clorinda y Formosa Capital."
                    />

                    <ul className="space-y-3 text-slate-700">
                        <li className="flex gap-3">
                            <span className="mt-2 h-2 w-2 shrink-0 rounded-full bg-marca-ambar" />
                            Encontrá productos frescos producidos cerca tuyo.
                        </li>
                        <li className="flex gap-3">
                            <span className="mt-2 h-2 w-2 shrink-0 rounded-full bg-marca-ambar" />
                            Dentro de la plataforma podés ordenar las
                            publicaciones por cercanía a tu ubicación.
                        </li>
                        <li className="flex gap-3">
                            <span className="mt-2 h-2 w-2 shrink-0 rounded-full bg-marca-ambar" />
                            Los socios del Consorcio aparecen primero en las
                            búsquedas.
                        </li>
                    </ul>

                    <p className="text-xs text-slate-500">
                        Mapa ilustrativo de la provincia de Formosa. Por
                        privacidad, no mostramos la ubicación de ningún
                        productor.
                    </p>
                </div>

                <div className="revelar relative mx-auto w-full max-w-lg">
                    <svg
                        viewBox="0 0 400 400"
                        role="img"
                        aria-label="Mapa ilustrativo de Formosa con Laguna Naineck, Clorinda y Formosa Capital"
                        className="h-auto w-full"
                    >
                        {/* Zona del Consorcio resaltada */}
                        <circle
                            cx="355"
                            cy="285"
                            r="70"
                            className="fill-marca-ambar/10"
                        />

                        {puntos.map(([x, y]) => (
                            <circle
                                key={`${x}-${y}`}
                                cx={x}
                                cy={y}
                                r="2.2"
                                className="fill-marca-azul/30"
                            />
                        ))}

                        {ZONAS.map((zona, i) => (
                            <g
                                key={zona.nombre}
                                className="flotar"
                                style={{ '--orden': i } as CSSProperties}
                            >
                                <circle
                                    cx={zona.x}
                                    cy={zona.y}
                                    r={zona.destacada ? 9 : 7}
                                    className={
                                        zona.destacada
                                            ? 'fill-marca-ambar stroke-white'
                                            : 'fill-marca-azul stroke-white'
                                    }
                                    strokeWidth="3"
                                />
                                {/* "izquierda": etiqueta al costado; "arriba": encima del pin, alineada a la derecha para no salirse del dibujo */}
                                <text
                                    x={
                                        zona.lado === 'izquierda'
                                            ? zona.x - 14
                                            : zona.x + 6
                                    }
                                    y={
                                        zona.lado === 'izquierda'
                                            ? zona.y + 4
                                            : zona.y - 14
                                    }
                                    textAnchor="end"
                                    className={`font-sans text-[13px] font-bold ${
                                        zona.destacada
                                            ? 'fill-amber-800'
                                            : 'fill-marca-tinta'
                                    }`}
                                >
                                    {zona.nombre}
                                </text>
                            </g>
                        ))}
                    </svg>
                </div>
            </div>
        </section>
    );
}
