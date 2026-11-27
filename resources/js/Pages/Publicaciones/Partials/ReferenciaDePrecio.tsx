import { formatearPrecio } from '@/utils/formato';
import { BadgeCheck, TriangleAlert } from 'lucide-react';
import { PreciosSugeridos } from './usePrecioSugerido';

// Recuadro de referencias de precio al publicar (KAN-340), debajo de precio,
// unidad y stock. Es solo una ayuda: nunca completa el campo ni bloquea.
//   1. Destacado: el precio de referencia del Consorcio del producto que
//      coincide con el título; si no coincide ninguno, la lista corta de
//      referencias del mes de esa categoría y unidad.
//   2. Secundario: el rango de lo que se publica en AgroConnect (KAN-132).
//   3. Aviso suave si el precio se aleja más de un 30% de la referencia.

// A partir de cuánto se avisa (0,3 = 30%).
const DESVIO_PARA_AVISAR = 0.3;
// Cuántas referencias se listan cuando ninguna coincide con el título.
const MAXIMO_LISTA = 4;

const formatoMes = new Intl.DateTimeFormat('es-AR', {
    month: 'long',
    year: 'numeric',
});

export default function ReferenciaDePrecio({
    precios,
    unidad,
    precio,
    nombreCategoria,
}: {
    precios: PreciosSugeridos;
    unidad: string;
    precio: string;
    nombreCategoria?: string;
}) {
    const { mercado, referencias } = precios;
    if (!mercado && referencias.length === 0) return null;

    const unidadLimpia = unidad.trim();
    const coincide = referencias.find((r) => r.coincide);
    // Las referencias son las del mes actual (vigentes).
    const mes = formatoMes.format(new Date());

    const precioEscrito = Number(precio);
    const desvio =
        coincide && precio !== '' && precioEscrito > 0
            ? (precioEscrito - coincide.precio) / coincide.precio
            : 0;
    const avisarDesvio = Math.abs(desvio) > DESVIO_PARA_AVISAR;

    return (
        <div
            id="precio-referencia"
            aria-live="polite"
            className="space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200"
        >
            {coincide ? (
                <div className="flex gap-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-marca-azul">
                        <BadgeCheck className="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p className="text-xs font-medium text-slate-500">
                            Precio de referencia del Consorcio · {mes}
                        </p>
                        <p className="text-lg font-bold text-marca-tinta">
                            {formatearPrecio(coincide.precio)}{' '}
                            <span className="text-sm font-medium text-slate-500">
                                por {coincide.unidad} · {coincide.producto}
                            </span>
                        </p>
                    </div>
                </div>
            ) : (
                referencias.length > 0 && (
                    <div>
                        <p className="text-xs font-medium text-slate-500">
                            Precios de referencia del Consorcio · {mes}
                            {nombreCategoria && ` · ${nombreCategoria}`}
                        </p>
                        <ul className="mt-1.5 flex flex-wrap gap-2">
                            {referencias.slice(0, MAXIMO_LISTA).map((r) => (
                                <li
                                    key={r.id}
                                    className="rounded-lg bg-white px-2.5 py-1 text-sm ring-1 ring-slate-200"
                                >
                                    {r.producto}{' '}
                                    <span className="font-semibold text-marca-tinta">
                                        {formatearPrecio(r.precio)}
                                    </span>
                                    <span className="text-slate-500">
                                        {' '}
                                        / {r.unidad}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        {referencias.length > MAXIMO_LISTA && (
                            <p className="mt-1 text-xs text-slate-500">
                                Y {referencias.length - MAXIMO_LISTA} más.
                                Nombrá el producto en el título para ver el
                                suyo.
                            </p>
                        )}
                    </div>
                )
            )}

            {avisarDesvio && coincide && (
                <p className="flex gap-2 rounded-lg bg-amber-50 p-2.5 text-sm text-amber-900">
                    <TriangleAlert
                        className="mt-0.5 h-4 w-4 shrink-0 text-amber-600"
                        aria-hidden="true"
                    />
                    <span>
                        Tu precio está un{' '}
                        {Math.round(Math.abs(desvio) * 100).toLocaleString(
                            'es-AR',
                        )}{' '}
                        % {desvio > 0 ? 'por encima' : 'por debajo'} de la
                        referencia del Consorcio. Podés publicarlo igual.
                    </span>
                </p>
            )}

            {mercado && (
                <p className="text-xs text-slate-600">
                    En AgroConnect se publica entre{' '}
                    {formatearPrecio(mercado.minimo)} y{' '}
                    {formatearPrecio(mercado.maximo)} por {unidadLimpia} (precio
                    típico {formatearPrecio(mercado.mediana)}, según{' '}
                    {mercado.cantidad} publicaciones).
                </p>
            )}

            {referencias.length > 0 && (
                <p className="text-xs text-slate-500">
                    El precio de referencia lo define cada mes la mesa
                    administrativa del Consorcio.
                </p>
            )}
        </div>
    );
}
