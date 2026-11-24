<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Valores de configuración por defecto de AgroConnect
    |--------------------------------------------------------------------------
    |
    | Estos valores se utilizan si no existen filas en la tabla 'configuraciones'
    | o si el valor configurado es nulo.
    |
    */
    // Contacto y redes: vacíos hasta que el admin cargue los datos reales del Consorcio
    // (KAN-225). Así el footer público nunca muestra datos de ejemplo como si fueran reales.
    'contacto_email' => null,
    'contacto_whatsapp' => null,
    'contacto_direccion' => null,
    'red_facebook' => null,
    'red_instagram' => null,

    'precio_sugerido_minimo' => 3,
    'fotos_maximo' => 6,
    'fotos_tamano_max_kb' => 2048,
];
