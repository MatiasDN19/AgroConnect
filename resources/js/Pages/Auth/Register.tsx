import {
    BotonAuth,
    CampoAuth,
    CampoContrasena,
} from '@/Components/Auth/CampoAuth';
import RedesProximamente from '@/Components/Auth/RedesProximamente';
import InputError from '@/Components/InputError';
import GuestLayout, { PRECARGA } from '@/Layouts/GuestLayout';
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
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        rol: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

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

            <form onSubmit={submit} className="mt-5 space-y-3.5">
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
                                        onChange={() => setData('rol', value)}
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

                    <InputError message={errors.rol} className="mt-1.5" />
                </fieldset>

                <CampoAuth
                    id="name"
                    etiqueta="Nombre y apellido"
                    name="name"
                    value={data.name}
                    autoComplete="name"
                    placeholder="Ej: Juana Pérez"
                    icono={User}
                    error={errors.name}
                    onChange={(e) => setData('name', e.target.value)}
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
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoContrasena
                        id="password"
                        etiqueta="Contraseña"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        error={errors.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <CampoContrasena
                        id="password_confirmation"
                        etiqueta="Repetí la contraseña"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        error={errors.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />
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
