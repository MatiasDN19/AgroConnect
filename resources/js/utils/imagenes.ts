// Achica las fotos en el navegador antes de subirlas (KAN-205).
//
// Las fotos de celular pesan 3-6 MB y el backend acepta hasta 2 MB por foto.
// Redimensionamos a 1600 px de lado como máximo y comprimimos a WebP: una foto de
// 5 MB queda en unos cientos de KB, se sube rápido con poca señal y nunca choca con
// el límite. Sin dependencias: se usa un <canvas>.

const LADO_MAXIMO = 1600;
const CALIDAD = 0.82;

export async function comprimirImagen(archivo: File): Promise<File> {
    // Si no es una imagen (o el navegador no puede leerla), se manda tal cual
    // y el backend devuelve el error de formato.
    if (!archivo.type.startsWith('image/')) return archivo;

    let imagen: ImageBitmap;
    try {
        // "from-image" respeta la rotación EXIF: las fotos verticales del celular
        // no quedan acostadas.
        imagen = await createImageBitmap(archivo, {
            imageOrientation: 'from-image',
        });
    } catch {
        return archivo;
    }

    const escala = Math.min(
        1,
        LADO_MAXIMO / Math.max(imagen.width, imagen.height),
    );
    const ancho = Math.round(imagen.width * escala);
    const alto = Math.round(imagen.height * escala);

    const canvas = document.createElement('canvas');
    canvas.width = ancho;
    canvas.height = alto;
    canvas.getContext('2d')?.drawImage(imagen, 0, 0, ancho, alto);
    imagen.close();

    const blob = await new Promise<Blob | null>((resolver) =>
        canvas.toBlob(resolver, 'image/webp', CALIDAD),
    );

    // Navegadores viejos que no exportan WebP devuelven PNG (pesado): en ese caso,
    // o si el resultado pesa más que el original, nos quedamos con el original.
    if (!blob || blob.type !== 'image/webp' || blob.size >= archivo.size) {
        return archivo;
    }

    const nombre = archivo.name.replace(/\.[^.]+$/, '') + '.webp';
    return new File([blob], nombre, {
        type: 'image/webp',
        lastModified: Date.now(),
    });
}
