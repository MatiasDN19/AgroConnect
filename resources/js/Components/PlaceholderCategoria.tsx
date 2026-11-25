import {
    Apple,
    Beef,
    Carrot,
    CookingPot,
    Egg,
    Fish,
    LucideIcon,
    Milk,
    Sprout,
    Wheat,
} from 'lucide-react';

// Lo que se muestra cuando una publicación no tiene fotos (KAN-205): un fondo suave
// con un ícono según la categoría. Las categorías las crea el admin, así que se busca
// por palabras clave del nombre y, si no coincide ninguna, va un ícono genérico.
const ICONOS: { palabras: string[]; Icono: LucideIcon; fondo: string }[] = [
    {
        palabras: ['verdura', 'hortaliza'],
        Icono: Carrot,
        fondo: 'from-orange-50 to-amber-100 text-orange-400',
    },
    {
        palabras: ['fruta'],
        Icono: Apple,
        fondo: 'from-rose-50 to-red-100 text-rose-400',
    },
    {
        palabras: ['lácteo', 'lacteo', 'leche', 'queso'],
        Icono: Milk,
        fondo: 'from-sky-50 to-blue-100 text-sky-400',
    },
    {
        palabras: ['huevo'],
        Icono: Egg,
        fondo: 'from-amber-50 to-yellow-100 text-amber-400',
    },
    {
        palabras: ['carne', 'pollo', 'cerdo'],
        Icono: Beef,
        fondo: 'from-red-50 to-rose-100 text-red-400',
    },
    {
        palabras: ['pescado', 'pesca'],
        Icono: Fish,
        fondo: 'from-cyan-50 to-sky-100 text-cyan-500',
    },
    {
        palabras: ['miel', 'conserva', 'dulce'],
        Icono: CookingPot,
        fondo: 'from-amber-50 to-orange-100 text-amber-500',
    },
    {
        palabras: ['cereal', 'grano', 'harina', 'semilla'],
        Icono: Wheat,
        fondo: 'from-yellow-50 to-amber-100 text-yellow-600',
    },
];

const GENERICO = {
    Icono: Sprout,
    fondo: 'from-emerald-50 to-green-100 text-emerald-400',
};

// Ícono y colores de una categoría según su nombre. También lo usan los círculos
// de categorías del Inicio (KAN-211).
export function estiloCategoria(categoria?: string): {
    Icono: LucideIcon;
    fondo: string;
} {
    const nombre = (categoria ?? '').toLowerCase();
    return (
        ICONOS.find(({ palabras }) =>
            palabras.some((p) => nombre.includes(p)),
        ) ?? GENERICO
    );
}

export default function PlaceholderCategoria({
    categoria,
    className = '',
}: {
    categoria?: string;
    className?: string;
}) {
    const { Icono, fondo } = estiloCategoria(categoria);

    return (
        <div
            role="img"
            aria-label={`Sin fotos${categoria ? ` (${categoria})` : ''}`}
            className={`flex items-center justify-center bg-gradient-to-br ${fondo} ${className}`}
        >
            <Icono
                className="h-1/3 max-h-[4rem] w-1/3 max-w-[4rem]"
                aria-hidden="true"
            />
        </div>
    );
}
