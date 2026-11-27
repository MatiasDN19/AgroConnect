import fotoProductores from '../../../images/landing/productores-collage.webp';
import logoConsorcio from '../../../images/logos/consorcio-laguna-naineck.webp';
import logoPlanNutrir from '../../../images/logos/plan-nutrir.webp';
import TituloSeccion from './TituloSeccion';

// Sección institucional. Los textos son generales a propósito: revisar con el
// Consorcio antes de agregar datos concretos (cantidad de socios, año, etc.).
export default function Consorcio() {
    return (
        <section
            id="consorcio"
            className="scroll-mt-8 px-4 py-20 sm:px-6 lg:px-8"
        >
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2">
                <div className="revelar order-2 lg:order-1">
                    <img
                        src={fotoProductores}
                        alt="Productores y productoras trabajando en el campo"
                        width={1054}
                        height={1008}
                        loading="lazy"
                        className="w-full rounded-3xl object-cover shadow-xl"
                    />
                </div>

                <div className="order-1 space-y-6 lg:order-2">
                    <TituloSeccion
                        etiqueta="El Consorcio"
                        titulo="Hecho junto al Consorcio Productivo de Laguna Naineck"
                        bajada="El Consorcio reúne a productores de la zona para fortalecer la producción local. AgroConnect es su punto de encuentro digital: un lugar para mostrar lo que se produce y venderlo directo a quien lo necesita."
                    />

                    <p className="text-slate-600">
                        Los socios del Consorcio pueden validar su condición
                        desde su perfil. Una vez validados, sus publicaciones
                        llevan la insignia de socio y aparecen primero en las
                        búsquedas.
                    </p>

                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-600">
                            Con el apoyo de
                        </p>
                        <div className="flex flex-wrap items-center gap-6 rounded-2xl bg-white px-6 py-4 shadow-sm ring-1 ring-slate-200">
                            <img
                                src={logoConsorcio}
                                alt="Consorcio Productivo de Laguna Naineck"
                                className="h-28 w-auto"
                                loading="lazy"
                            />
                            <img
                                src={logoPlanNutrir}
                                alt="Plan Nutrir, plan provincial alimentario"
                                className="h-20 w-auto"
                                loading="lazy"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
