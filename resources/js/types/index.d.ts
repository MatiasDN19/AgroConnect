export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    telefono?: string | null;
    rol: 'productor' | 'comprador' | 'admin';
    // Socio del Consorcio (KAN-92): sin número = sin solicitar.
    numero_socio?: string | null;
    socio_validado?: boolean;
    // Ubicación del perfil (KAN-90). Llega como "-26.1849000" por el cast decimal:7.
    lat?: string | null;
    lng?: string | null;
}

export interface Publicacion {
    id: number;
    usuario_id: number;
    categoria_id: number;
    titulo: string;
    descripcion: string;
    precio: string; // viene como "1234.50" por el cast decimal:2 de Laravel
    unidad: string;
    stock: number;
    estado?: EstadoPublicacion;
    // Moderación (KAN-221/222). El backend los manda solo al dueño y al admin.
    motivo_bloqueo?: string | null;
    bloqueada_at?: string | null;
    // Ordenadas por "orden": la primera es la portada (KAN-204/205).
    // En el listado viene solo la portada; en el detalle y la edición, todas.
    fotos?: PublicacionFoto[];
}

// "bloqueada" la pone solo un admin; el dueño no puede cambiarla (KAN-221).
export type EstadoPublicacion = 'activa' | 'pausada' | 'vendida' | 'bloqueada';

export interface PublicacionFoto {
    id: number;
    ruta: string;
    orden: number;
    url: string; // URL pública lista para el <img> (Storage::url)
}

export interface Resena {
    id: number;
    puntuacion: number; // 1 a 5
    comentario: string | null;
    created_at: string;
    usuario: Pick<User, 'id' | 'name'>;
}

export type EstadoDenuncia = 'pendiente' | 'revisada' | 'descartada';

// Lo que manda DenunciaController::index() para el panel admin.
export interface Denuncia {
    id: number;
    motivo: string;
    descripcion: string | null;
    estado: EstadoDenuncia;
    created_at: string;
    usuario?: Pick<User, 'id' | 'name' | 'email'>;
    publicacion?: Pick<Publicacion, 'id' | 'titulo' | 'usuario_id'> & {
        usuario?: Pick<User, 'id' | 'name' | 'email'>;
    };
}

export interface Categoria {
    id: number;
    nombre: string;
    descripcion: string | null;
    publicaciones_count?: number;
}

// Respuesta de ->paginate() de Laravel (KAN-209). "links" trae anterior, los
// números de página y siguiente, con url null cuando no hay a dónde ir.
export interface Paginado<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
    // Solo viene para admins (KAN-218); para el resto es null o no está.
    admin?: {
        denuncias_pendientes: number;
    } | null;
    // Datos públicos de contacto que carga el admin en Configuración (KAN-224/225).
    // Vacíos (null o "") hasta que se carguen: no hay datos de ejemplo.
    contacto: ContactoPlataforma;
};

export type ContactoPlataforma = {
    email: string | null;
    whatsapp: string | null;
    direccion: string | null;
    facebook: string | null;
    instagram: string | null;
};

// Lo que manda ConfiguracionController::edit(). Los valores llegan como string
// (se guardan como texto) o como número si vienen de los valores por defecto.
export type Configuracion = {
    contacto_email: string | null;
    contacto_whatsapp: string | null;
    contacto_direccion: string | null;
    red_facebook: string | null;
    red_instagram: string | null;
    precio_sugerido_minimo: string | number | null;
    fotos_maximo: string | number | null;
    fotos_tamano_max_kb: string | number | null;
};

// Límites de fotos que el backend aplica y le pasa al formulario (KAN-225).
export type LimitesFotos = { maximo: number; tamano_max_kb: number };
