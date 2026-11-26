const relativo = new Intl.RelativeTimeFormat('es-AR', { numeric: 'auto' });

// "hace 5 minutos", "ayer", "hace 3 semanas"... Para fechas pasadas (KAN-80).
export function haceCuanto(fecha: string | Date): string {
    const segundos = Math.round(
        (new Date(fecha).getTime() - Date.now()) / 1000,
    );
    const pasos: [Intl.RelativeTimeFormatUnit, number][] = [
        ['second', 60],
        ['minute', 60],
        ['hour', 24],
        ['day', 7],
        ['week', 4.35],
        ['month', 12],
    ];

    if (Math.abs(segundos) < 45) return 'recién';

    let valor = segundos;
    for (const [unidad, tope] of pasos) {
        if (Math.abs(valor) < tope) {
            return relativo.format(Math.round(valor), unidad);
        }
        valor /= tope;
    }
    return relativo.format(Math.round(valor), 'year');
}

// "Buenos días" / "Buenas tardes" / "Buenas noches" según la hora del navegador.
// Lo usan el Inicio del marketplace (KAN-211) y el resumen del panel admin (KAN-159).
export function saludo(): string {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 20) return 'Buenas tardes';
    return 'Buenas noches';
}
