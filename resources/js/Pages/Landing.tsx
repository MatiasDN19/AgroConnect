import Beneficios from '@/Components/Landing/Beneficios';
import BotonVolverArriba from '@/Components/Landing/BotonVolverArriba';
import ComoFunciona from '@/Components/Landing/ComoFunciona';
import Consorcio from '@/Components/Landing/Consorcio';
import CtaFinal from '@/Components/Landing/CtaFinal';
import Estadisticas, {
    EstadisticasLanding,
} from '@/Components/Landing/Estadisticas';
import Hero from '@/Components/Landing/Hero';
import MapaZona from '@/Components/Landing/MapaZona';
import Navegacion from '@/Components/Landing/Navegacion';
import Pie from '@/Components/Landing/Pie';
import PreguntasFrecuentes from '@/Components/Landing/PreguntasFrecuentes';
import useRevelar from '@/Components/Landing/useRevelar';
import { User } from '@/types';
import { Head, usePage } from '@inertiajs/react';

// Landing pública de AgroConnect (KAN-201). Cada sección es un componente
// en Components/Landing; acá solo se arman en orden.
export default function Landing({
    estadisticas,
    canRegister = false,
}: {
    // Props que manda LandingController (KAN-200).
    estadisticas: EstadisticasLanding;
    canLogin?: boolean;
    canRegister?: boolean;
}) {
    // En la landing el usuario puede no haber iniciado sesión.
    const usuario = usePage().props.auth.user as User | null;

    // Animaciones de aparición al hacer scroll (etapa 3).
    useRevelar();

    return (
        <div className="min-h-screen bg-marca-fondo text-marca-tinta">
            <Head title="AgroConnect · Marketplace del Consorcio de Laguna Naineck" />

            <Navegacion usuario={usuario} />

            <main>
                <Hero
                    usuario={usuario}
                    canRegister={canRegister}
                    productores={estadisticas.productores}
                />
                <Estadisticas estadisticas={estadisticas} />
                <MapaZona />
                <ComoFunciona />
                <Consorcio />
                <Beneficios />
                <PreguntasFrecuentes />
                <CtaFinal usuario={usuario} canRegister={canRegister} />
            </main>

            <Pie />

            <BotonVolverArriba />
        </div>
    );
}
