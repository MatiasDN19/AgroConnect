// "Buenos días" / "Buenas tardes" / "Buenas noches" según la hora del navegador.
// Lo usan el Inicio del marketplace (KAN-211) y el resumen del panel admin (KAN-159).
export function saludo(): string {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 20) return 'Buenas tardes';
    return 'Buenas noches';
}
