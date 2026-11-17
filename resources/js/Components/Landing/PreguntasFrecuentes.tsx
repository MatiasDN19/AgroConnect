import { ChevronDown } from 'lucide-react';
import TituloSeccion from './TituloSeccion';

const PREGUNTAS = [
    {
        pregunta: '¿Tiene costo usar AgroConnect?',
        respuesta:
            'No. Crear una cuenta, publicar y comprar es gratis. La plataforma no cobra comisiones por las ventas.',
    },
    {
        pregunta: '¿Cómo se paga lo que compro?',
        respuesta:
            'AgroConnect no procesa pagos. Comprador y productor acuerdan el precio final, la forma de pago y la entrega directamente entre ellos.',
    },
    {
        pregunta: '¿Quién puede publicar productos?',
        respuesta:
            'Cualquier persona que se registre como productor. Los compradores pueden navegar, dejar reseñas y contactar a los productores.',
    },
    {
        pregunta: '¿Cómo contacto a un productor?',
        respuesta:
            'Desde el detalle de cada publicación: por WhatsApp o teléfono, si el productor lo cargó, o enviándole una solicitud de compra.',
    },
    {
        pregunta: '¿Cómo me valido como socio del Consorcio?',
        respuesta:
            'Desde tu perfil cargás tu número de socio. Un administrador lo revisa y, cuando lo valida, tus publicaciones muestran la insignia de socio y aparecen primero.',
    },
    {
        pregunta: '¿Qué pasa si veo una publicación sospechosa?',
        respuesta:
            'Podés denunciarla desde la publicación. El equipo de administración revisa cada denuncia.',
    },
];

// Acordeón con <details>/<summary>: funciona con teclado y lectores de pantalla sin JavaScript.
export default function PreguntasFrecuentes() {
    return (
        <section
            id="preguntas"
            className="scroll-mt-8 px-4 py-20 sm:px-6 lg:px-8"
        >
            <div className="mx-auto max-w-3xl space-y-10">
                <TituloSeccion
                    etiqueta="Preguntas frecuentes"
                    titulo="Lo que más nos preguntan"
                    centrado
                />

                <div className="divide-y divide-slate-200 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    {PREGUNTAS.map((item) => (
                        <details
                            key={item.pregunta}
                            className="group px-6 py-5"
                        >
                            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-marca-tinta focus:outline-none focus-visible:text-marca-azul [&::-webkit-details-marker]:hidden">
                                {item.pregunta}
                                <ChevronDown
                                    className="h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    aria-hidden="true"
                                />
                            </summary>
                            <p className="mt-3 text-slate-600">
                                {item.respuesta}
                            </p>
                        </details>
                    ))}
                </div>
            </div>
        </section>
    );
}
