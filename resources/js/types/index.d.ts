export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    telefono?: string | null;
    rol: 'productor' | 'comprador' | 'admin';
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
    estado?: 'activa' | 'pausada' | 'vendida';
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
};
