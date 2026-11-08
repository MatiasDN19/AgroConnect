export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    telefono?: string | null;
    rol: 'productor' | 'comprador' | 'admin';
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
