import {
    fortaleza,
    NIVELES_FORTALEZA,
    reglasContrasena,
} from '@/utils/validaciones';
import { Check, Circle } from 'lucide-react';

// Reglas de la contraseña que se van tildando y medidor de fortaleza
// (KAN-313). Mismas reglas que el backend (KAN-300). Va debajo del campo de
// la contraseña nueva en el registro, el reseteo y el perfil.

const COLOR_NIVEL = [
    'bg-red-500',
    'bg-amber-500',
    'bg-sky-500',
    'bg-green-600',
] as const;
const TEXTO_NIVEL = [
    'text-red-700',
    'text-amber-700',
    'text-sky-700',
    'text-green-700',
] as const;

export default function ReglasContrasena({
    contrasena,
}: {
    contrasena: string;
}) {
    const reglas = reglasContrasena(contrasena);
    const nivel = fortaleza(contrasena);
    const vacia = contrasena.length === 0;

    const lista: [boolean, string][] = [
        [reglas.largo, '8 caracteres o más'],
        [reglas.letra, 'Una letra'],
        [reglas.numero, 'Un número'],
    ];

    return (
        <div className="space-y-2">
            <ul className="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                {lista.map(([cumple, texto]) => (
                    <li
                        key={texto}
                        className={`flex items-center gap-1.5 transition-colors duration-150 motion-reduce:transition-none ${
                            cumple ? 'text-green-700' : 'text-slate-500'
                        }`}
                    >
                        {cumple ? (
                            <Check className="h-3.5 w-3.5" aria-hidden="true" />
                        ) : (
                            <Circle className="h-3 w-3" aria-hidden="true" />
                        )}
                        {texto}
                        <span className="sr-only">
                            {cumple ? ' (cumplido)' : ' (falta)'}
                        </span>
                    </li>
                ))}
            </ul>

            {!vacia && (
                <div className="flex items-center gap-3">
                    {/* Cuatro segmentos: se pintan según el nivel. */}
                    <div className="flex flex-1 gap-1" aria-hidden="true">
                        {[0, 1, 2, 3].map((i) => (
                            <span
                                key={i}
                                className={`h-1.5 flex-1 rounded-full transition-colors duration-150 motion-reduce:transition-none ${
                                    i <= nivel
                                        ? COLOR_NIVEL[nivel]
                                        : 'bg-slate-200'
                                }`}
                            />
                        ))}
                    </div>
                    <p
                        className={`w-20 text-right text-xs font-semibold ${TEXTO_NIVEL[nivel]}`}
                        aria-live="polite"
                    >
                        <span className="sr-only">Fortaleza: </span>
                        {NIVELES_FORTALEZA[nivel]}
                    </p>
                </div>
            )}
        </div>
    );
}
