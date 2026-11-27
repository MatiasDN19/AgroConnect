import { clasesCampo } from '@/Components/Formulario';
import { estiloCategoria } from '@/Components/PlaceholderCategoria';
import {
    Label,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/react';
import { Check, ChevronDown } from 'lucide-react';
import { ReactNode } from 'react';

// Desplegable con el estilo de la app, en lugar del <select> nativo: la lista
// abierta de un <select> la dibuja el navegador/sistema y no se puede estilizar.
// Usa Listbox de Headless UI (ya instalado): se maneja con el teclado igual que
// un select (flechas, Enter, Esc, tipear para saltar) y lo entienden los
// lectores de pantalla.

export type OpcionDesplegable = {
    valor: string;
    etiqueta: string;
    icono?: ReactNode;
};

export default function Desplegable({
    id,
    etiqueta,
    valor,
    onChange,
    opciones,
    placeholder = 'Elegí una opción',
    invalido = false,
    disabled = false,
    className = '',
    'aria-describedby': describedBy,
}: {
    id: string;
    // Nombre para lectores de pantalla ("Categoría"). La etiqueta visible, si
    // hay, sigue estando afuera; un botón no toma su nombre de un <label>.
    etiqueta: string;
    valor: string | number;
    onChange: (valor: string) => void;
    opciones: OpcionDesplegable[];
    // Lo que se ve si el valor no es ninguna opción (ej. "Elegí una categoría").
    placeholder?: string;
    invalido?: boolean;
    disabled?: boolean;
    // Ancho y margen los pone quien lo usa (ej. "mt-1", "sm:max-w-[10rem]").
    className?: string;
    'aria-describedby'?: string;
}) {
    const actual = String(valor);
    const elegida = opciones.find((o) => o.valor === actual);

    return (
        <Listbox value={actual} onChange={onChange} disabled={disabled}>
            {/* Label de Headless UI: lo conecta al botón con aria-labelledby
                (un aria-labelledby propio lo pisa la librería). */}
            <Label className="sr-only">{etiqueta}</Label>
            <ListboxButton
                id={id}
                aria-invalid={invalido || undefined}
                aria-describedby={describedBy}
                className={`${clasesCampo(invalido ? 'error' : null).replace('mt-1', '')} group flex items-center justify-between gap-2 border bg-white px-3 py-2 text-left focus:outline-none focus:ring-1 disabled:cursor-not-allowed data-[open]:ring-1 ${
                    invalido
                        ? 'data-[open]:border-red-600 data-[open]:ring-red-600'
                        : 'data-[open]:border-sky-600 data-[open]:ring-sky-600'
                } ${className}`}
            >
                <span className="flex min-w-0 items-center gap-2">
                    {elegida?.icono}
                    <span
                        className={`truncate ${elegida ? 'text-marca-tinta' : 'text-slate-500'}`}
                    >
                        {elegida?.etiqueta ?? placeholder}
                    </span>
                </span>
                <ChevronDown
                    className="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200 group-data-[open]:rotate-180 motion-reduce:transition-none"
                    aria-hidden="true"
                />
            </ListboxButton>

            {/* anchor: Headless UI la ubica debajo del botón (o arriba si no
                entra) y la saca a un portal, así no la corta un contenedor con
                overflow. --button-width: mismo ancho que el botón. */}
            <ListboxOptions
                anchor={{ to: 'bottom start', gap: 6, padding: 12 }}
                transition
                className="z-50 max-h-72 w-[var(--button-width)] min-w-[10rem] origin-top overflow-y-auto rounded-xl bg-white p-1 shadow-xl shadow-slate-900/10 ring-1 ring-slate-200 transition-[opacity,transform] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] focus:outline-none data-[closed]:-translate-y-1 data-[closed]:scale-[0.98] data-[closed]:opacity-0 data-[leave]:duration-100 motion-reduce:data-[closed]:translate-y-0 motion-reduce:data-[closed]:scale-100"
            >
                {opciones.map((opcion) => (
                    <ListboxOption
                        key={opcion.valor}
                        value={opcion.valor}
                        className="group flex cursor-pointer select-none items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 data-[focus]:bg-sky-50 data-[selected]:font-semibold data-[focus]:text-marca-tinta data-[selected]:text-marca-azul-profundo"
                    >
                        {opcion.icono}
                        <span className="flex-1 truncate">
                            {opcion.etiqueta}
                        </span>
                        <Check
                            className="invisible h-4 w-4 shrink-0 group-data-[selected]:visible"
                            aria-hidden="true"
                        />
                    </ListboxOption>
                ))}
            </ListboxOptions>
        </Listbox>
    );
}

// Opciones de categorías con su ícono, como los círculos del Inicio (KAN-211).
export function opcionesCategorias(
    categorias: { id: number; nombre: string }[],
): OpcionDesplegable[] {
    return categorias.map((c) => {
        const { Icono, fondo } = estiloCategoria(c.nombre);
        return {
            valor: String(c.id),
            etiqueta: c.nombre,
            icono: (
                <span
                    className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br ${fondo}`}
                    aria-hidden="true"
                >
                    <Icono className="h-3.5 w-3.5" />
                </span>
            ),
        };
    });
}
