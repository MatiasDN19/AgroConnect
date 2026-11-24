import InputError from '@/Components/InputError';
import { Eye, EyeOff, LucideIcon } from 'lucide-react';
import {
    InputHTMLAttributes,
    ReactNode,
    useEffect,
    useRef,
    useState,
} from 'react';

// Campos de las pantallas de ingreso (KAN-231): fondo suave, ícono a la derecha
// y el error asociado al campo con aria-describedby para lectores de pantalla.
const CLASES_INPUT =
    'block w-full rounded-xl border-transparent bg-slate-100 py-2.5 pe-11 ps-4 text-sm text-marca-tinta placeholder:text-slate-400 transition focus:border-marca-azul focus:bg-white focus:ring-marca-azul';

type Props = InputHTMLAttributes<HTMLInputElement> & {
    id: string;
    etiqueta: string;
    error?: string;
    icono?: LucideIcon;
    enfocar?: boolean;
    ayuda?: ReactNode;
};

export function CampoAuth({
    id,
    etiqueta,
    error,
    icono: Icono,
    enfocar = false,
    ayuda,
    ...props
}: Props) {
    const ref = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (enfocar) ref.current?.focus();
    }, [enfocar]);

    const descripcion = [error && `${id}-error`, ayuda && `${id}-ayuda`]
        .filter(Boolean)
        .join(' ');

    return (
        <div>
            <label
                htmlFor={id}
                className="mb-1.5 block text-sm font-medium text-slate-700"
            >
                {etiqueta}
            </label>
            <div className="relative">
                <input
                    {...props}
                    id={id}
                    ref={ref}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={descripcion || undefined}
                    className={CLASES_INPUT}
                />
                {Icono && (
                    <Icono
                        className="pointer-events-none absolute end-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        aria-hidden="true"
                    />
                )}
            </div>
            {ayuda && (
                <p id={`${id}-ayuda`} className="mt-1 text-xs text-slate-500">
                    {ayuda}
                </p>
            )}
            <InputError id={`${id}-error`} message={error} className="mt-1.5" />
        </div>
    );
}

// Contraseña con botón para mostrarla u ocultarla (como en la referencia).
export function CampoContrasena({
    id,
    etiqueta,
    error,
    ...props
}: Omit<Props, 'icono' | 'type'>) {
    const [visible, setVisible] = useState(false);

    return (
        <div>
            <label
                htmlFor={id}
                className="mb-1.5 block text-sm font-medium text-slate-700"
            >
                {etiqueta}
            </label>
            <div className="relative">
                <input
                    {...props}
                    id={id}
                    type={visible ? 'text' : 'password'}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={CLASES_INPUT}
                />
                <button
                    type="button"
                    onClick={() => setVisible((v) => !v)}
                    aria-label={
                        visible ? 'Ocultar contraseña' : 'Mostrar contraseña'
                    }
                    aria-pressed={visible}
                    className="absolute end-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 transition hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                >
                    {visible ? (
                        <EyeOff className="h-4 w-4" aria-hidden="true" />
                    ) : (
                        <Eye className="h-4 w-4" aria-hidden="true" />
                    )}
                </button>
            </div>
            <InputError id={`${id}-error`} message={error} className="mt-1.5" />
        </div>
    );
}

// Botón principal oscuro y a todo el ancho, como en la referencia.
export function BotonAuth({
    children,
    disabled,
}: {
    children: ReactNode;
    disabled?: boolean;
}) {
    return (
        <button
            type="submit"
            disabled={disabled}
            className="w-full rounded-xl bg-marca-tinta px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-marca-azul-profundo focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 disabled:opacity-60"
        >
            {children}
        </button>
    );
}
