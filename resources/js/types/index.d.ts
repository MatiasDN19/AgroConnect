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
    categoria_id: number;
    titulo: string;
    descripcion: string;
    precio: string; // viene como "1234.50" por el cast decimal:2 de Laravel
    unidad: string;
    stock: number;
    estado?: 'activa' | 'pausada' | 'vendida';
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
};
