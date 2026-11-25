import { User } from '@/types';
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Clock,
    Handshake,
    LucideIcon,
    MapPin,
    Sprout,
} from 'lucide-react';

// Dos banners informativos del Inicio (KAN-211), distintos según el rol.
// La prioridad de los socios en el orden es interna: acá solo se invita a validarse,
// sin decir que eso los pone primero.
type Banner = {
    Icono: LucideIcon;
    titulo: string;
    texto: string;
    accion?: { texto: string; href: string };
    estilo: string;
    estiloIcono: string;
};

function bannersPara(usuario: User): Banner[] {
    if (usuario.rol === 'productor') {
        return [
            bannerSocio(usuario),
            {
                Icono: Sprout,
                titulo: 'Publicá tu producción',
                texto: 'Subí fotos, precio y stock. Los compradores te escriben directo por WhatsApp o te mandan una solicitud.',
                accion: {
                    texto: 'Nueva publicación',
                    href: route('publicaciones.create'),
                },
                estilo: 'bg-gradient-to-br from-amber-50 to-orange-100 ring-amber-200',
                estiloIcono: 'bg-marca-ambar text-amber-950',
            },
        ];
    }

    if (usuario.rol === 'comprador') {
        return [
            {
                Icono: Handshake,
                titulo: 'Comprá directo al productor',
                texto: 'Sin intermediarios: mandale una solicitud de compra o escribile por WhatsApp desde cada publicación.',
                estilo: 'bg-gradient-to-br from-sky-50 to-blue-100 ring-sky-200',
                estiloIcono: 'bg-marca-azul text-white',
            },
            {
                Icono: MapPin,
                titulo: 'Productores de la zona',
                texto: 'Ordená el catálogo por cercanía y encontrá lo que se produce cerca tuyo.',
                accion: { texto: 'Ir al catálogo', href: '#catalogo' },
                estilo: 'bg-gradient-to-br from-emerald-50 to-green-100 ring-emerald-200',
                estiloIcono: 'bg-emerald-600 text-white',
            },
        ];
    }

    // El admin no necesita banners de bienvenida.
    return [];
}

// El de socio cambia según en qué punto del trámite está el productor (KAN-92).
function bannerSocio(usuario: User): Banner {
    const base = {
        estilo: 'bg-gradient-to-br from-sky-50 to-blue-100 ring-sky-200',
        estiloIcono: 'bg-marca-azul text-white',
    };

    if (usuario.socio_validado) {
        return {
            ...base,
            Icono: BadgeCheck,
            titulo: 'Sos socio del Consorcio',
            texto: 'Tus publicaciones llevan la insignia de socio. Gracias por ser parte.',
        };
    }

    if (usuario.numero_socio) {
        return {
            ...base,
            Icono: Clock,
            titulo: 'Estamos revisando tu número de socio',
            texto: 'Cuando un administrador lo valide, tus publicaciones van a llevar la insignia de socio.',
        };
    }

    return {
        ...base,
        Icono: BadgeCheck,
        titulo: '¿Sos socio del Consorcio?',
        texto: 'Cargá tu número de socio en tu perfil y sumá la insignia a tus publicaciones.',
        accion: { texto: 'Validar mi número', href: route('profile.edit') },
    };
}

export default function BannersInformativos({ usuario }: { usuario: User }) {
    const banners = bannersPara(usuario);
    if (banners.length === 0) return null;

    return (
        <section aria-label="Información" className="grid gap-4 md:grid-cols-2">
            {banners.map(
                ({ Icono, titulo, texto, accion, estilo, estiloIcono }) => (
                    <article
                        key={titulo}
                        className={`flex gap-4 rounded-2xl p-6 ring-1 ${estilo}`}
                    >
                        <span
                            className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${estiloIcono}`}
                        >
                            <Icono className="h-6 w-6" aria-hidden="true" />
                        </span>
                        <div className="space-y-2">
                            <h3 className="font-display text-lg font-bold text-marca-tinta">
                                {titulo}
                            </h3>
                            <p className="text-sm text-slate-600">{texto}</p>
                            {accion &&
                                (accion.href.startsWith('#') ? (
                                    <a
                                        href={accion.href}
                                        className={claseAccion}
                                    >
                                        {accion.texto}
                                        <ArrowRight
                                            className="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                    </a>
                                ) : (
                                    <Link
                                        href={accion.href}
                                        className={claseAccion}
                                    >
                                        {accion.texto}
                                        <ArrowRight
                                            className="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                ))}
                        </div>
                    </article>
                ),
            )}
        </section>
    );
}

const claseAccion =
    'inline-flex items-center gap-1.5 text-sm font-semibold text-marca-azul hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul';
