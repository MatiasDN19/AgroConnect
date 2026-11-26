<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva notificación en AgroConnect</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #0369a1;
            font-size: 24px;
            margin: 0;
        }
        .content {
            font-size: 16px;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .footer {
            margin-top: 32px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AgroConnect</h1>
        </div>
        <div class="content">
            <span class="badge">{{ str_replace('_', ' ', $alerta->tipo) }}</span>
            <p>Hola <strong>{{ $alerta->usuario?->name ?? 'Usuario' }}</strong>,</p>
            <p>{{ $alerta->mensaje }}</p>
            @if ($alerta->publicacion_id)
                <p style="margin-top: 24px;">
                    <a href="{{ route('publicaciones.show', $alerta->publicacion_id) }}" style="display: inline-block; background-color: #0284c7; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                        Ver publicación
                    </a>
                </p>
            @endif
        </div>
        <div class="footer">
            <p>Este es un correo automático enviado por AgroConnect - Consorcio Productivo de Laguna Naineck.</p>
        </div>
    </div>
</body>
</html>
