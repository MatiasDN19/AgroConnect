// Formatos compartidos para mostrar datos en castellano rioplatense.

const formatoMoneda = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
});

// "10000.00" o 10000 -> "$ 10.000,00"
export function formatearPrecio(precio: string | number): string {
    return formatoMoneda.format(Number(precio));
}
