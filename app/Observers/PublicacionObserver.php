<?php

namespace App\Observers;

use App\Models\Publicacion;

class PublicacionObserver
{
    /**
     * Handle the Publicacion "updating" event.
     *
     * Regla de negocio automática según stock (KAN-180 / KAN-186):
     * - Al actualizar una publicación existente, si su stock pasa a ser <= 0 y no está vendida,
     *   se pausa automáticamente (estado = Publicacion::ESTADO_PAUSADA).
     * - Si se repone stock (pasa de 0 a mayor a 0) y estaba en estado pausada, se reactiva a activa.
     * - Si el estado es "vendida", no se modifica automáticamente por cambios de stock.
     * - Si se está modificando explícitamente el estado a mano (isDirty('estado')), respetamos
     *   la decisión explícita (ej. pausa manual o reactivación manual) y no la sobreescribimos.
     */
    public function updating(Publicacion $publicacion): void
    {
        // Si el estado es "vendida", no se altera automáticamente por stock.
        if ($publicacion->estado === Publicacion::ESTADO_VENDIDA) {
            return;
        }

        // Si el usuario está cambiando explícitamente el estado a mano, respetamos la acción.
        if ($publicacion->isDirty('estado')) {
            return;
        }

        // Si el stock fue modificado en la actualización
        if ($publicacion->isDirty('stock')) {
            $stockAnterior = (int) $publicacion->getOriginal('stock');
            $nuevoStock = (int) $publicacion->stock;

            if ($nuevoStock <= 0) {
                $publicacion->estado = Publicacion::ESTADO_PAUSADA;
            } elseif ($publicacion->estado === Publicacion::ESTADO_PAUSADA && $stockAnterior <= 0 && $nuevoStock > 0) {
                // Solo reactiva automáticamente si estaba pausada y venía de tener stock agotado (<= 0)
                $publicacion->estado = Publicacion::ESTADO_ACTIVA;
            }
        }
    }
}
