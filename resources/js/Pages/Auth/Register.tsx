import {
    BotonAuth,
    CampoAuth,
    CampoContrasena,
} from '@/Components/Auth/CampoAuth';
import RedesProximamente from '@/Components/Auth/RedesProximamente';
import InputError from '@/Components/InputError';
import ReglasContrasena from '@/Components/ReglasContrasena';
import GuestLayout, { PRECARGA } from '@/Layouts/GuestLayout';
import {
    contrasenaValida,
    emailValido,
    MENSAJES,
    nombreValido,
    useValidacion,
} from '@/utils/validaciones';
import { Head, Link, useForm } from '@inertiajs/react';
import { LucideIcon, Mail, ShoppingBasket, Sprout, User } from 'lucide-react';
import { FormEventHandler, ReactNode } from 'react';

// Roles que se pueden elegir en el registro público (nunca "admin").
// Los valores tienen que coincidir con User::ROL_PRODUCTOR y User::ROL_COMPRADOR.
const ROLES: {
    value: string;
    label: string;
    descripcion: string;
    Icono: LucideIcon;
}[] = [
    {
        value: 'productor',
        label: 'Productor',
        descripcion: 'Publico lo que produzco y recibo pedidos.',
        Icono: Sprout,
    },
    {
        value: 'comprador',
        label: 'Comprador',
        descripcion: 'Busco productos y contacto a productores.',
        Icono: ShoppingBasket,
    },
];

export default function Register() {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            rol: '',
        });

    // Validación en vivo (KAN-313): verde mientras se escribe, rojo al salir
    // del campo o al enviar. El error del backend manda.
    type Campo =
        'rol' | 'name' | 'email' | 'password' | 'password_confirmation';
    const { estado, error, tocar, todoValido } = useValidacion<Campo>(
        {
            rol: {
                valor: data.rol,
                valido: data.rol !== '',
                mensaje: 'Elegí si sos productor o comprador.',
            },
            name: {
                valor: data.name,
                valido: nombreValido(data.name),
                mensaje: MENSAJES.nombre,
            },
            email: {
                valor: data.email,
                valido: emailValido(data.email),
                mensaje: MENSAJES.email,
            },
            password: {
                valor: data.password,
                valido: contrasenaValida(data.password),
                mensaje: MENSAJES.contrasena,
            },
            password_confirmation: {
                valor: data.password_confirmation,
                valido:
                    data.password_confirmation !== '' &&
                    data.password_confirmation === data.password,
                mensaje:
                    data.password_confirmation === ''
                        ? MENSAJES.repetir
                        : MENSAJES.confirmacion,
            },
        },
        errors,
    );
    // Al escribir se borra el error del backend de ese campo (ya no aplica).
    const cambiar = (campo: Campo, valor: string) => {
        setData(campo, valor);
        if (errors[campo]) clearErrors(campo);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Si algo no cumple, se marca todo y no se envía: el backend igual
        // valida, pero así se ve al toque qué falta.
        if (!todoValido()) return;

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Registrarme" />

            <h1 className="font-display text-2xl font-extrabold tracking-tight">
                Creá tu cuenta
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                Es gratis y te lleva un minuto.
            </p>

            <form onSubmit={submit} noValidate className="mt-5 space-y-3.5">
                <fieldset>
                    <legend className="mb-1.5 block text-sm font-medium text-slate-700">
                        Quiero registrarme como
                    </legend>

                    <div className="grid gap-3 sm:grid-cols-2">
                        {ROLES.map(({ value, label, descripcion, Icono }) => {
                            const elegido = data.rol === value;

                            return (
                                <label
                                    key={value}
                                    className={`flex cursor-pointer gap-3 rounded-xl border-2 p-3 transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-marca-azul ${
                                        elegido
                                            ? 'border-marca-azul bg-marca-azul/5'
                                            : 'border-slate-200 hover:border-slate-300'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="rol"
                                        value={value}
                                        checked={elegido}
                                        onChange={() => cambiar('rol', value)}
                                        className="sr-only"
                                        required
                                    />
                                    <Icono
                                        className={`mt-0.5 h-5 w-5 shrink-0 ${
                                            elegido
                                                ? 'text-marca-azul'
                                                : 'text-slate-400'
                                        }`}
                                        aria-hidden="true"
                                    />
                                    <span>
                                        <span className="block text-sm font-semibold">
                                            {label}
                                        </span>
                                        <span className="block text-xs text-slate-500">
                                            {descripcion}
                                        </span>
                                    </span>
                                </label>
                            );
                        })}
                    </div>

                    <InputError message={error('rol')} className="mt-1.5" />
                </fieldset>

                <CampoAuth
                    id="name"
                    etiqueta="Nombre y apellido"
                    name="name"
                    value={data.name}
                    autoComplete="name"
                    placeholder="Ej: Juana Pérez"
                    icono={User}
                    estado={estado('name')}
                    error={error('name')}
                    onChange={(e) => cambiar('name', e.target.value)}
                    onBlur={() => tocar('name')}
                    required
                />

                <CampoAuth
                    id="email"
                    etiqueta="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    placeholder="tucorreo@ejemplo.com"
                    icono={Mail}
                    estado={estado('email')}
                    error={error('email')}
                    onChange={(e) => cambiar('email', e.target.value)}
                    onBlur={() => tocar('email')}
                    required
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoContrasena
                        id="password"
                        etiqueta="Contraseña"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        estado={estado('password')}
                        error={error('password')}
                        onChange={(e) => cambiar('password', e.target.value)}
                        onBlur={() => tocar('password')}
                        aria-describedby="reglas-contrasena"
                        required
                    />

                    <CampoContrasena
                        id="password_confirmation"
                        etiqueta="Repetí la contraseña"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        estado={estado('password_confirmation')}
                        error={error('password_confirmation')}
                        onChange={(e) =>
                            cambiar('password_confirmation', e.target.value)
                        }
                        onBlur={() => tocar('password_confirmation')}
                        required
                    />
                </div>

                {/* Debajo de los dos campos, a todo el ancho (KAN-313). */}
                <div id="reglas-contrasena">
                    <ReglasContrasena contrasena={data.password} />
                </div>

                <div className="pt-2">
                    <BotonAuth disabled={processing}>Crear mi cuenta</BotonAuth>
                </div>
            </form>

            <div className="mt-5">
                <RedesProximamente accion="registrarte" />
            </div>

            <p className="mt-4 text-center text-sm text-slate-500">
                ¿Ya tenés cuenta?{' '}
                <Link
                    href={route('login')}
                    {...PRECARGA}
                    className="font-semibold text-marca-azul hover:underline"
                >
                    Ingresá
                </Link>
            </p>
        </>
    );
}

// Layout persistente: no se desmonta al ir a /login y permite animar el cambio.
Register.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
