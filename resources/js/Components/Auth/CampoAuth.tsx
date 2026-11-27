import InputError from '@/Components/InputError';
import { Estado } from '@/utils/validaciones';
import {
    CircleAlert,
    CircleCheck,
    Eye,
    EyeOff,
    LucideIcon,
} from 'lucide-react';
import {
    InputHTMLAttributes,
    ReactNode,
    useEffect,
    useRef,
    useState,
} from 'react';

// Campos de las pantallas de ingreso (KAN-231): fondo suave, ícono a la derecha
// y el error asociado al campo con aria-describedby para lectores de pantalla.
// Validación en vivo (KAN-313): "estado" pinta el borde de verde o rojo y
// cambia el ícono por ✓ o ✕, así no depende solo del color.
const CLASES_INPUT =
    'block w-full rounded-xl border bg-slate-100 py-2.5 ps-4 text-sm text-marca-tinta placeholder:text-slate-400 transition-[border-color,background-color,box-shadow] duration-150 focus:bg-white focus:ring-1 motion-reduce:transition-none';

const CLASES_ESTADO: Record<'ok' | 'error' | 'neutro', string> = {
    neutro: 'border-transparent focus:border-marca-azul focus:ring-marca-azul',
    ok: 'border-green-500 focus:border-green-600 focus:ring-green-600',
    error: 'border-red-500 focus:border-red-600 focus:ring-red-600',
};

type Props = InputHTMLAttributes<HTMLInputElement> & {
    id: string;
    etiqueta: string;
    error?: string;
    icono?: LucideIcon;
    enfocar?: boolean;
    ayuda?: ReactNode;
    estado?: Estado;
};

function IconoEstado({
    estado,
    className,
}: {
    estado: Estado;
    className: string;
}) {
    if (estado === 'ok') {
        return (
            <CircleCheck
                className={`${className} text-green-600`}
                aria-hidden="true"
            />
        );
    }
    if (estado === 'error') {
        return (
            <CircleAlert
                className={`${className} text-red-600`}
                aria-hidden="true"
            />
        );
    }
    return null;
}

export function CampoAuth({
    id,
    etiqueta,
    error,
    icono: Icono,
    enfocar = false,
    ayuda,
    estado = null,
    ...props
}: Props) {
    const ref = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (enfocar) ref.current?.focus();
    }, [enfocar]);

    // Se suma a lo que venga de afuera (ej. la lista de reglas de la contraseña).
    const descripcion = [
        props['aria-describedby'],
        error && `${id}-error`,
        ayuda && `${id}-ayuda`,
    ]
        .filter(Boolean)
        .join(' ');
    const claseIcono =
        'pointer-events-none absolute end-4 top-1/2 h-4 w-4 -translate-y-1/2';

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
                    aria-invalid={
                        estado === 'error' || error ? true : undefined
                    }
                    aria-describedby={descripcion || undefined}
                    className={`${CLASES_INPUT} pe-11 ${CLASES_ESTADO[estado ?? 'neutro']}`}
                />
                {estado ? (
                    <IconoEstado estado={estado} className={claseIcono} />
                ) : (
                    Icono && (
                        <Icono
                            className={`${claseIcono} text-slate-400`}
                            aria-hidden="true"
                        />
                    )
                )}
            </div>
            {ayuda && (
                <div id={`${id}-ayuda`} className="mt-1 text-xs text-slate-500">
                    {ayuda}
                </div>
            )}
            <InputError id={`${id}-error`} message={error} className="mt-1.5" />
        </div>
    );
}

// Contraseña con botón para mostrarla u ocultarla (como en la referencia).
// "ayuda" va debajo (ej. la lista de reglas y la fortaleza, KAN-313).
export function CampoContrasena({
    id,
    etiqueta,
    error,
    ayuda,
    estado = null,
    enfocar = false,
    ...props
}: Omit<Props, 'icono' | 'type'>) {
    const [visible, setVisible] = useState(false);
    // "enfocar" antes se pasaba de largo al <input> (React avisaba en consola).
    const ref = useRef<HTMLInputElement>(null);
    useEffect(() => {
        if (enfocar) ref.current?.focus();
    }, [enfocar]);
    // Se suma a lo que venga de afuera (ej. la lista de reglas de la contraseña).
    const descripcion = [
        props['aria-describedby'],
        error && `${id}-error`,
        ayuda && `${id}-ayuda`,
    ]
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
                    type={visible ? 'text' : 'password'}
                    aria-invalid={
                        estado === 'error' || error ? true : undefined
                    }
                    aria-describedby={descripcion || undefined}
                    className={`${CLASES_INPUT} ${estado ? 'pe-[4.5rem]' : 'pe-11'} ${CLASES_ESTADO[estado ?? 'neutro']}`}
                />
                <IconoEstado
                    estado={estado}
                    className="pointer-events-none absolute end-11 top-1/2 h-4 w-4 -translate-y-1/2"
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
            {ayuda && (
                <div id={`${id}-ayuda`} className="mt-2">
                    {ayuda}
                </div>
            )}
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
