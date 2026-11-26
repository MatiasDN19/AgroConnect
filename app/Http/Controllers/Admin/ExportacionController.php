<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SolicitudCompra;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacionController extends Controller
{
    /**
     * Punto de entrada parametrizado para /admin/exportar/{tipo}.
     */
    public function exportar(Request $request, string $tipo): StreamedResponse
    {
        return match ($tipo) {
            'socios' => $this->socios($request),
            'usuarios' => $this->usuarios($request),
            'solicitudes' => $this->solicitudes($request),
            default => abort(404),
        };
    }

    /**
     * Exporta socios validados a un archivo CSV compatible con Excel.
     */
    public function socios(Request $request): StreamedResponse
    {
        $fecha = now()->format('Y-m-d');
        $nombreArchivo = "agroconnect-socios-{$fecha}.csv";

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para que Excel abra acentos correctamente
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Nombre', 'Email', 'Teléfono', 'Número de socio', 'Fecha de validación'], ';');

            User::query()
                ->where('socio_validado', true)
                ->orderBy('name')
                ->lazy(100)
                ->each(function (User $user) use ($handle) {
                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        $user->telefono ?? '',
                        $user->numero_socio ?? '',
                        $user->updated_at?->format('d/m/Y H:i') ?? '',
                    ], ';');
                });

            fclose($handle);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Exporta usuarios a CSV respetando los filtros de búsqueda y estado.
     */
    public function usuarios(Request $request): StreamedResponse
    {
        $fecha = now()->format('Y-m-d');
        $nombreArchivo = "agroconnect-usuarios-{$fecha}.csv";

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Nombre', 'Email', 'Rol', 'Estado', 'Socio', 'Fecha de alta'], ';');

            User::query()
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->input('q').'%';
                    $query->where(function ($q) use ($term) {
                        $q->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
                })
                ->when($request->filled('rol'), function ($query) use ($request) {
                    $query->where('rol', $request->input('rol'));
                })
                ->when($request->filled('inhabilitado'), function ($query) use ($request) {
                    $query->where('inhabilitado', (bool) $request->input('inhabilitado'));
                })
                ->orderBy('name')
                ->lazy(100)
                ->each(function (User $user) use ($handle) {
                    $estado = $user->inhabilitado ? 'Inhabilitado' : 'Habilitado';
                    $socio = $user->socio_validado ? 'Validado' : ($user->numero_socio ? 'Pendiente' : 'No');

                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        ucfirst($user->rol),
                        $estado,
                        $socio,
                        $user->created_at?->format('d/m/Y H:i') ?? '',
                    ], ';');
                });

            fclose($handle);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Exporta solicitudes de compra a CSV.
     */
    public function solicitudes(Request $request): StreamedResponse
    {
        $fecha = now()->format('Y-m-d');
        $nombreArchivo = "agroconnect-solicitudes-{$fecha}.csv";

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Fecha', 'Estado', 'Publicación', 'Cantidad', 'Unidad', 'Comprador', 'Productor'], ';');

            SolicitudCompra::query()
                ->with(['usuario', 'publicacion.usuario'])
                ->when($request->filled('estado'), function ($query) use ($request) {
                    $query->where('estado', $request->input('estado'));
                })
                ->latest('created_at')
                ->lazy(100)
                ->each(function (SolicitudCompra $solicitud) use ($handle) {
                    fputcsv($handle, [
                        $solicitud->created_at?->format('d/m/Y H:i') ?? '',
                        ucfirst($solicitud->estado),
                        $solicitud->publicacion?->titulo ?? 'Publicación eliminada',
                        $solicitud->cantidad,
                        $solicitud->publicacion?->unidad ?? '',
                        $solicitud->usuario?->name ?? 'Usuario eliminado',
                        $solicitud->publicacion?->usuario?->name ?? 'Productor eliminado',
                    ], ';');
                });

            fclose($handle);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
