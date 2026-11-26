import InputError from '@/Components/InputError';
import { LucideIcon } from 'lucide-react';
import { ButtonHTMLAttributes, ReactNode } from 'react';

// Piezas de formulario con el estilo nuevo (panel admin y perfil, KAN-322).
// Antes vivían dentro de Admin/Configuracion/Edit.tsx.

export const CLASES_CAMPO =
    'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600 disabled:bg-slate-100 disabled:text-slate-500';

// Tarjeta con ícono, título y descripción. "peligro" la pinta de rojo
// (ej. eliminar la cuenta).
export function Bloque({
    Icono,
    titulo,
    descripcion,
    extra,
    peligro = false,
    children,
}: {
    Icono: LucideIcon;
    titulo: string;
    descripcion: ReactNode;
    extra?: ReactNode;
    peligro?: boolean;
    children: ReactNode;
}) {
    return (
        <section
            className={`rounded-xl bg-white p-5 shadow-sm ring-1 sm:p-6 ${
                peligro ? 'ring-red-200' : 'ring-slate-200'
            }`}
        >
            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div className="flex gap-3">
                    <span
                        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${
                            peligro
                                ? 'bg-red-50 text-red-600'
                                : 'bg-sky-50 text-marca-azul'
                        }`}
                    >
                        <Icono className="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2
                            className={`font-display text-lg font-bold ${
                                peligro ? 'text-red-700' : 'text-marca-tinta'
                            }`}
                        >
                            {titulo}
                        </h2>
                        <p className="text-sm text-slate-500">{descripcion}</p>
                    </div>
                </div>
                {extra}
            </div>
            <div className="space-y-4">{children}</div>
        </section>
    );
}

export function Campo({
    id,
    etiqueta,
    error,
    ayuda,
    children,
}: {
    id: string;
    etiqueta: string;
    error?: string;
    ayuda?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="block text-sm font-medium text-slate-700"
            >
                {etiqueta}
            </label>
            {children}
            {ayuda && <p className="mt-1 text-xs text-slate-500">{ayuda}</p>}
            <InputError message={error} className="mt-1" />
        </div>
    );
}

// Botón principal de los formularios (azul de la marca).
export function BotonGuardar({
    className = '',
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type="submit"
            {...props}
            className={`rounded-lg bg-marca-azul-profundo px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-marca-azul focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 active:scale-[0.98] disabled:opacity-60 ${className}`}
        >
            {children}
        </button>
    );
}

// Botón secundario (borde gris), ej. "Cancelar" o "Cambiar número".
// "peligro" pinta el texto de rojo (ej. "Quitar"). Va como opción y no por
// className porque dos colores de texto se pisan y no gana el último.
export function BotonSecundario({
    className = '',
    peligro = false,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { peligro?: boolean }) {
    return (
        <button
            type="button"
            {...props}
            className={`rounded-lg bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-300 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 active:scale-[0.98] disabled:opacity-60 ${
                peligro
                    ? 'text-red-600 hover:bg-red-50'
                    : 'text-slate-700 hover:bg-slate-50'
            } ${className}`}
        >
            {children}
        </button>
    );
}

// Botón rojo para acciones que no se pueden deshacer.
export function BotonPeligro({
    className = '',
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type="button"
            {...props}
            className={`rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:scale-[0.98] disabled:opacity-60 ${className}`}
        >
            {children}
        </button>
    );
}
