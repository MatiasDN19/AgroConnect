import {
    BarChart3,
    FileText,
    Flag,
    LucideIcon,
    Settings,
    Tags,
    UserCheck,
    Users,
} from 'lucide-react';

export type SeccionAdmin = {
    nombre: string;
    // Nombre de la ruta de Laravel. Si todavía no existe, la sección no se muestra.
    ruta: string;
    // Patrón para marcar el ítem activo (route().current(activo)).
    activo: string;
    icono: LucideIcon;
};

// Secciones del panel en el orden de la sidebar.
// IMPORTANTE para backend: usar estos nombres de ruta al registrarlas,
// así el ítem aparece solo en la sidebar sin tocar el frontend.
export const SECCIONES_ADMIN: SeccionAdmin[] = [
    {
        nombre: 'Resumen',
        ruta: 'admin.resumen',
        activo: 'admin.resumen',
        icono: BarChart3,
    },
    {
        nombre: 'Denuncias',
        ruta: 'admin.denuncias.index',
        activo: 'admin.denuncias.*',
        icono: Flag,
    },
    {
        nombre: 'Publicaciones',
        ruta: 'admin.publicaciones.index',
        activo: 'admin.publicaciones.*',
        icono: FileText,
    },
    {
        nombre: 'Usuarios',
        ruta: 'admin.usuarios.index',
        activo: 'admin.usuarios.*',
        icono: Users,
    },
    {
        nombre: 'Socios',
        ruta: 'admin.socios.index',
        activo: 'admin.socios.*',
        icono: UserCheck,
    },
    {
        nombre: 'Categorías',
        ruta: 'categorias.index',
        activo: 'categorias.*',
        icono: Tags,
    },
    {
        nombre: 'Configuración',
        ruta: 'admin.configuracion.edit',
        activo: 'admin.configuracion.*',
        icono: Settings,
    },
];

// Solo las secciones cuya ruta ya existe en el backend.
export function seccionesDisponibles(): SeccionAdmin[] {
    return SECCIONES_ADMIN.filter((seccion) => route().has(seccion.ruta));
}

// A dónde lleva el link "Panel admin": la primera sección disponible
// (cuando exista el Resumen, va a ser esa).
export function rutaInicioAdmin(): string | null {
    const primera = seccionesDisponibles()[0];

    return primera ? route(primera.ruta) : null;
}
