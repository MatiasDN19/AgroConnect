import { Alerta } from '@/utils/alertas';
import { haceCuanto } from '@/utils/fecha';
import {
    Bell,
    CircleCheck,
    CircleX,
    Flag,
    LucideIcon,
    ShoppingBag,
    Star,
} from 'lucide-react';

// Un renglón de notificación (KAN-80/323): lo usan el panel de la campanita y
// la página /alertas, así se ven iguales en los dos lugares.

// Ícono y color según el tipo. Los tipos salen de los controladores que crean
// alertas (solicitudes, reseñas y denuncias). Los que no estén acá (por ejemplo,
// si el backend suma uno nuevo) usan la campana.
const ESTILO_TIPO: Record<string, { Icono: LucideIcon; color: string }> = {
    nueva_solicitud_compra: {
        Icono: ShoppingBag,
        color: 'bg-sky-50 text-marca-azul',
    },
    solicitud_compra_aceptada: {
        Icono: CircleCheck,
        color: 'bg-green-50 text-green-600',
    },
    solicitud_compra_rechazada: {
        Icono: CircleX,
        color: 'bg-red-50 text-red-600',
    },
    nueva_resena: { Icono: Star, color: 'bg-amber-50 text-amber-600' },
    denuncia_resuelta: { Icono: Flag, color: 'bg-slate-100 text-slate-600' },
};
const ESTILO_GENERICO = { Icono: Bell, color: 'bg-sky-50 text-marca-azul' };

export default function ItemAlerta({
    alerta,
    onClick,
    grande = false,
}: {
    alerta: Alerta;
    onClick: () => void;
    // En la página, un poco más de aire que en el panel.
    grande?: boolean;
}) {
    const { Icono, color } = ESTILO_TIPO[alerta.tipo] ?? ESTILO_GENERICO;

    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex w-full items-start gap-3 rounded-xl text-left transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                grande ? 'p-3.5' : 'px-2.5 py-2.5'
            } ${alerta.leida ? '' : 'bg-sky-50/60'}`}
        >
            <span
                className={`flex shrink-0 items-center justify-center rounded-full ${color} ${
                    grande ? 'h-10 w-10' : 'h-9 w-9'
                }`}
            >
                <Icono
                    className="h-[1.125rem] w-[1.125rem]"
                    aria-hidden="true"
                />
            </span>
            <span className="min-w-0 flex-1">
                <span
                    className={`block leading-snug ${grande ? 'text-[0.9375rem]' : 'text-sm'} ${
                        alerta.leida
                            ? 'text-slate-600'
                            : 'font-medium text-marca-tinta'
                    }`}
                >
                    {alerta.mensaje}
                </span>
                <span className="mt-0.5 block text-xs text-slate-400">
                    {haceCuanto(alerta.created_at)}
                </span>
            </span>
            {!alerta.leida && (
                <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-marca-azul">
                    <span className="sr-only">Sin leer</span>
                </span>
            )}
        </button>
    );
}
