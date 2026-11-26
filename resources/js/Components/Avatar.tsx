// Iniciales del nombre en un círculo con el degradé de la marca.
// Lo usan el navbar (KAN-211) y la cabecera del perfil (KAN-322).
const TAMANOS = {
    chico: 'h-8 w-8 text-xs',
    mediano: 'h-10 w-10 text-sm',
    grande: 'h-16 w-16 text-xl sm:h-20 sm:w-20 sm:text-2xl',
};

export default function Avatar({
    nombre,
    grande = false,
    tamano,
}: {
    nombre: string;
    // Atajo que ya usaba el navbar: grande = mediano.
    grande?: boolean;
    tamano?: keyof typeof TAMANOS;
}) {
    const iniciales = nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0]?.toUpperCase())
        .join('');

    const clases = TAMANOS[tamano ?? (grande ? 'mediano' : 'chico')];

    return (
        <span
            aria-hidden="true"
            className={`flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-marca-azul-claro to-marca-azul-profundo font-bold text-white ${clases}`}
        >
            {iniciales}
        </span>
    );
}
