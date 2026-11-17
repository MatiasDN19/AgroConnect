import { BadgeCheck } from 'lucide-react';

// Insignia de "Socio del Consorcio" (KAN-173). Es solo informativa.
// - completa: "Socio del Consorcio" (detalle y perfil)
// - compacta: "Socio" (tarjetas, donde hay poco lugar); el texto completo
//   queda para lectores de pantalla y como tooltip.
export default function SocioBadge({
    compacta = false,
    className = '',
}: {
    compacta?: boolean;
    className?: string;
}) {
    return (
        <span
            title="Socio del Consorcio Productivo de Laguna Naineck"
            className={`inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-100 font-semibold text-amber-800 ring-1 ring-amber-200 ${
                compacta ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs'
            } ${className}`}
        >
            <BadgeCheck
                className={compacta ? 'h-3.5 w-3.5' : 'h-4 w-4'}
                aria-hidden="true"
            />
            {compacta ? (
                <>
                    Socio
                    <span className="sr-only"> del Consorcio</span>
                </>
            ) : (
                'Socio del Consorcio'
            )}
        </span>
    );
}
