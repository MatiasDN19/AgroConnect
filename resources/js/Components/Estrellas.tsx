// Muestra 5 estrellas, pintando las que corresponden al valor (redondeado).
// Para lectores de pantalla se anuncia como "4.0 de 5 estrellas".
export default function Estrellas({
    valor,
    className = '',
}: {
    valor: number;
    className?: string;
}) {
    const llenas = Math.round(valor);

    return (
        <span
            role="img"
            aria-label={`${valor.toFixed(1)} de 5 estrellas`}
            className={`text-base leading-none ${className}`}
        >
            {[1, 2, 3, 4, 5].map((n) => (
                <span
                    key={n}
                    aria-hidden="true"
                    className={n <= llenas ? 'text-amber-400' : 'text-gray-300'}
                >
                    ★
                </span>
            ))}
        </span>
    );
}
