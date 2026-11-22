import { linkWhatsapp } from '@/utils/whatsapp';
import { MessageCircle } from 'lucide-react';

// Botón que abre WhatsApp con un mensaje precargado (KAN-178).
// Si el teléfono no se puede convertir a un número de WhatsApp válido,
// no muestra nada: quien lo usa deja visible el teléfono común.
export default function WhatsappButton({
    telefono,
    mensaje,
    texto = 'Escribir por WhatsApp',
    className = '',
}: {
    telefono: string | null | undefined;
    mensaje: string;
    texto?: string;
    className?: string;
}) {
    const href = linkWhatsapp(telefono, mensaje);

    if (!href) return null;

    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className={`inline-flex items-center justify-center gap-2 rounded-md bg-[#128C7E] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0E6F64] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#128C7E] focus-visible:ring-offset-2 ${className}`}
        >
            <MessageCircle className="h-4 w-4" aria-hidden="true" />
            {texto}
            <span className="sr-only"> (se abre en una pestaña nueva)</span>
        </a>
    );
}
