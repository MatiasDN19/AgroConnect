import { Categoria } from '@/types';
import { ReactNode } from 'react';

// Fila de "chips" para elegir una categoría (o "Todas").
// Es un componente de presentación: no navega ni pide datos por su cuenta.
// La página que lo usa le pasa las categorías y la seleccionada, y decide qué
// hacer en onSeleccionar (ej. router.get('/publicaciones', { categoria_id })).
export default function CategoriaFiltro({
    categorias,
    seleccionada,
    onSeleccionar,
    className = '',
}: {
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    seleccionada: number | null;
    onSeleccionar: (categoriaId: number | null) => void;
    className?: string;
}) {
    return (
        <div
            role="group"
            aria-label="Filtrar por categoría"
            className={`flex flex-wrap gap-2 ${className}`}
        >
            <Chip
                activo={seleccionada === null}
                onClick={() => onSeleccionar(null)}
            >
                Todas
            </Chip>

            {categorias.map((categoria) => (
                <Chip
                    key={categoria.id}
                    activo={seleccionada === categoria.id}
                    onClick={() => onSeleccionar(categoria.id)}
                >
                    {categoria.nombre}
                </Chip>
            ))}
        </div>
    );
}

function Chip({
    activo,
    onClick,
    children,
}: {
    activo: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={activo}
            className={
                'rounded-full border px-4 py-1.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ' +
                (activo
                    ? 'border-indigo-600 bg-indigo-600 text-white'
                    : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-400 hover:text-indigo-700')
            }
        >
            {children}
        </button>
    );
}
