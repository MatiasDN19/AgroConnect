import { User } from '@/types';

// A dónde lleva el botón principal de la landing (criterio de KAN-199):
// con sesión iniciada → marketplace; sin sesión → crear cuenta (o ingresar).
export function ctaPrincipal(usuario: User | null, canRegister: boolean) {
    if (usuario) {
        return {
            href: route('publicaciones.index'),
            texto: 'Ir al marketplace',
        };
    }

    return canRegister
        ? { href: route('register'), texto: 'Crear cuenta gratis' }
        : { href: route('login'), texto: 'Ingresar a AgroConnect' };
}
