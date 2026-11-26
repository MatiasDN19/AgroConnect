// Texto de la distancia al productor, igual en la tarjeta del listado (KAN-171)
// y en el detalle (KAN-274):
// - menos de 1 km: "a menos de 1 km"
// - menos de 10 km: con un decimal ("a 3,5 km")
// - desde 10 km: sin decimales ("a 12 km")
const unDecimal = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 1 });
const sinDecimales = new Intl.NumberFormat('es-AR', {
    maximumFractionDigits: 0,
});

export function textoDistancia(km: number): string {
    if (km < 1) return 'a menos de 1 km';
    const formato = km < 10 ? unDecimal : sinDecimales;
    return `a ${formato.format(km)} km`;
}
