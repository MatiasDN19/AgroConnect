import { useState } from 'react';

// Validaciones en vivo de los formularios de cuenta (KAN-313). Son las mismas
// reglas que el backend (KAN-300: Password::min(8)->letters()->numbers()).
// El backend además revisa que la contraseña no aparezca en filtraciones
// conocidas: eso solo lo puede saber el servidor y llega como error.

export type Estado = 'ok' | 'error' | null;

// Formato básico: algo@algo.algo, sin espacios.
export function emailValido(email: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

export function nombreValido(nombre: string): boolean {
    return nombre.trim().length > 0;
}

// Cada regla de la contraseña, para la lista que se va tildando.
export function reglasContrasena(contrasena: string) {
    return {
        largo: contrasena.length >= 8,
        letra: /\p{L}/u.test(contrasena),
        numero: /\d/.test(contrasena),
    };
}

export function contrasenaValida(contrasena: string): boolean {
    const r = reglasContrasena(contrasena);
    return r.largo && r.letra && r.numero;
}

export const NIVELES_FORTALEZA = [
    'Débil',
    'Aceptable',
    'Buena',
    'Fuerte',
] as const;

// 0 = débil (no cumple lo mínimo), 1 = aceptable (cumple lo mínimo), y suma
// por combinar mayúsculas y minúsculas, usar símbolos o tener 12 o más.
export function fortaleza(contrasena: string): 0 | 1 | 2 | 3 {
    if (!contrasenaValida(contrasena)) return 0;
    const extras = [
        /[a-z]/.test(contrasena) && /[A-Z]/.test(contrasena),
        /[^\p{L}\d]/u.test(contrasena),
        contrasena.length >= 12,
    ].filter(Boolean).length;
    return extras >= 2 ? 3 : extras === 1 ? 2 : 1;
}

// Qué estado mostrar en un campo:
// - el error del backend siempre manda;
// - el verde aparece en vivo, apenas el valor es válido;
// - el rojo recién cuando la persona salió del campo (o intentó enviar), así
//   no se marca como error algo que todavía está escribiendo.
export function estadoCampo({
    valor,
    valido,
    tocado,
    errorBackend,
}: {
    valor: string;
    valido: boolean;
    tocado: boolean;
    errorBackend?: string;
}): Estado {
    if (errorBackend) return 'error';
    if (!valor) return tocado ? 'error' : null;
    if (valido) return 'ok';
    return tocado ? 'error' : null;
}

// Mensajes de error locales, iguales en todos los formularios.
export const MENSAJES = {
    nombre: 'Escribí tu nombre y apellido.',
    email: 'Revisá el correo: tiene que ser como tucorreo@ejemplo.com.',
    contrasena:
        'Tiene que tener 8 caracteres o más, con al menos una letra y un número.',
    confirmacion: 'Las contraseñas no coinciden.',
    repetir: 'Repetí la contraseña.',
    requerido: 'Completá este campo.',
};

// Todo junto para un formulario: a cada campo se le pasa su valor, si es
// válido y el mensaje; devuelve el estado y el error a mostrar de cada uno,
// "tocar" para el onBlur y "todoValido" para el submit (si algo falla,
// marca todo para que se vea qué falta).
export function useValidacion<K extends string>(
    campos: Record<K, { valor: string; valido: boolean; mensaje: string }>,
    erroresBackend: Partial<Record<string, string>>,
) {
    const { tocado, tocar, tocarTodos } = useTocados<K>();

    const estado = (c: K): Estado =>
        estadoCampo({
            valor: campos[c].valor,
            valido: campos[c].valido,
            tocado: tocado(c),
            errorBackend: erroresBackend[c],
        });

    const error = (c: K): string | undefined =>
        erroresBackend[c] ??
        (estado(c) === 'error' ? campos[c].mensaje : undefined);

    const todoValido = () => {
        const claves = Object.keys(campos) as K[];
        if (claves.every((k) => campos[k].valido)) return true;
        tocarTodos(claves);
        return false;
    };

    return { estado, error, tocar, todoValido };
}

// Lleva qué campos ya se "tocaron" (se salió de ellos o se intentó enviar).
export function useTocados<K extends string>() {
    const [tocados, setTocados] = useState<Partial<Record<K, boolean>>>({});
    return {
        tocado: (campo: K) => Boolean(tocados[campo]),
        tocar: (campo: K) =>
            setTocados((t) => (t[campo] ? t : { ...t, [campo]: true })),
        tocarTodos: (campos: K[]) =>
            setTocados(
                Object.fromEntries(campos.map((c) => [c, true])) as Partial<
                    Record<K, boolean>
                >,
            ),
    };
}
