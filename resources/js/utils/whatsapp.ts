// Arma el número para wa.me a partir del teléfono que cargó el usuario (KAN-178).
//
// El teléfono se guarda como texto libre ("0370 15-420-2022", "+54 9 3704 202022",
// "3704202022"...), pero WhatsApp necesita el formato internacional solo con dígitos:
// para un celular argentino es 549 + código de área + número (10 dígitos), sin el 0
// del área ni el 15. Si no se puede armar un número válido devuelve null, y el botón
// no se muestra: es mejor no ofrecerlo que abrir un chat con otra persona.
export function numeroWhatsapp(
    telefono: string | null | undefined,
): string | null {
    if (!telefono) return null;

    let digitos = telefono.replace(/\D/g, '');

    // Prefijo internacional marcado con 00 (00 54 ...).
    if (digitos.startsWith('00')) digitos = digitos.slice(2);

    if (digitos.startsWith('54')) {
        digitos = digitos.slice(2);
        // El 9 que va entre el 54 y el área en los celulares.
        if (digitos.startsWith('9')) digitos = digitos.slice(1);
    } else if (digitos.startsWith('0')) {
        // El 0 de larga distancia antes del código de área.
        digitos = digitos.slice(1);
    }

    // El 15 de celular va después del código de área, que tiene 2, 3 o 4 dígitos.
    // Si sobran justo 2 dígitos, lo buscamos en esas posiciones y lo sacamos.
    if (digitos.length === 12) {
        for (const largoArea of [2, 3, 4]) {
            if (digitos.slice(largoArea, largoArea + 2) === '15') {
                digitos =
                    digitos.slice(0, largoArea) + digitos.slice(largoArea + 2);
                break;
            }
        }
    }

    // Código de área + número siempre suman 10 dígitos en Argentina.
    return digitos.length === 10 ? `549${digitos}` : null;
}

// Link a WhatsApp con un mensaje precargado, o null si el teléfono no sirve.
export function linkWhatsapp(
    telefono: string | null | undefined,
    mensaje: string,
): string | null {
    const numero = numeroWhatsapp(telefono);

    return numero
        ? `https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`
        : null;
}
