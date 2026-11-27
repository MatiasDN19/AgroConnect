import { avisar } from '@/Components/Avisos/avisos';
import { Bloque } from '@/Components/Formulario';
import { router, usePage } from '@inertiajs/react';
import { Bell, Mail } from 'lucide-react';
import { useState } from 'react';

// Notificaciones por mail (KAN-77). Backend: KAN-268 (columna
// notificar_por_mail, que consultan los envíos de mail de solicitudes,
// reseñas y denuncias). Es un interruptor: se guarda al tocarlo.
// profile.update exige name y email, así que se mandan sin cambios.

export default function UpdateNotificationPreferencesForm() {
    const user = usePage().props.auth.user;
    // Por defecto todos reciben mails (así lo pidió el equipo).
    const [activado, setActivado] = useState(user.notificar_por_mail ?? true);
    const [guardando, setGuardando] = useState(false);

    const cambiar = () => {
        const nuevo = !activado;
        // Se mueve enseguida; si falla, vuelve a como estaba.
        setActivado(nuevo);
        setGuardando(true);
        router.patch(
            route('profile.update'),
            { name: user.name, email: user.email, notificar_por_mail: nuevo },
            {
                preserveScroll: true,
                onSuccess: () =>
                    avisar(
                        'exito',
                        nuevo
                            ? 'Vas a recibir los avisos por mail.'
                            : 'Ya no vas a recibir avisos por mail.',
                    ),
                onError: () => {
                    setActivado(!nuevo);
                    avisar(
                        'error',
                        'No pudimos guardar el cambio. Probá de nuevo.',
                    );
                },
                onFinish: () => setGuardando(false),
            },
        );
    };

    return (
        <Bloque
            Icono={Bell}
            titulo="Notificaciones"
            descripcion="Elegí cómo querés enterarte de lo que pasa con tus publicaciones y solicitudes."
        >
            <div className="flex items-start justify-between gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                <div className="flex gap-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-marca-azul ring-1 ring-slate-200">
                        <Mail className="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p
                            id="titulo-mail"
                            className="font-medium text-marca-tinta"
                        >
                            Recibir avisos por mail
                        </p>
                        <p
                            id="detalle-mail"
                            className="mt-0.5 text-sm text-slate-500"
                        >
                            Cuando te mandan o te responden una solicitud de
                            compra, cuando reseñan tus publicaciones y cuando se
                            resuelve una denuncia. Llegan a {user.email}.
                        </p>
                    </div>
                </div>

                {/* Interruptor accesible: un botón con role="switch". */}
                <button
                    type="button"
                    role="switch"
                    aria-checked={activado}
                    aria-labelledby="titulo-mail"
                    aria-describedby="detalle-mail"
                    onClick={cambiar}
                    disabled={guardando}
                    className={`relative mt-1 inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 disabled:cursor-wait ${
                        activado ? 'bg-marca-azul' : 'bg-slate-300'
                    }`}
                >
                    <span
                        aria-hidden="true"
                        className={`inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-[cubic-bezier(0.23,1,0.32,1)] motion-reduce:transition-none ${
                            activado ? 'translate-x-6' : 'translate-x-1'
                        }`}
                    />
                </button>
            </div>

            <p className="flex gap-2 text-xs text-slate-500">
                <Bell
                    className="h-4 w-4 shrink-0 text-marca-azul"
                    aria-hidden="true"
                />
                La campanita de AgroConnect te sigue avisando de todo, aunque
                apagues los mails.
            </p>
        </Bloque>
    );
}
