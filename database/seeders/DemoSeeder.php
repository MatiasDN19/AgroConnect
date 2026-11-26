<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Favorito;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\SolicitudCompra;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Carga datos de demostración realistas para presentaciones y pruebas de AgroConnect.
     * Es idempotente: puede ejecutarse múltiples veces sin duplicar registros.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Categorías base
        $categoriasData = [
            'Verduras' => 'Hortalizas, tubérculos y hojas verdes cultivadas en huertas y chacras locales.',
            'Frutas' => 'Frutas frescas de estación típicas del clima subtropical de Formosa.',
            'Lácteos' => 'Quesos artesanales, leche y derivados producidos en tambos familiares.',
            'Carnes' => 'Animales de granja criados a campo y alimentación natural.',
            'Miel y conservas' => 'Miel pura de monte, dulces regionales y conservas tradicionales.',
            'Huevos' => 'Huevos frescos de campo de gallinas criadas libres.',
        ];

        $categorias = [];
        foreach ($categoriasData as $nombre => $descripcion) {
            $categorias[$nombre] = Categoria::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $descripcion]
            );
        }

        // 2. Productores locales de Laguna Naineck y alrededores
        $productoresData = [
            'ramon' => [
                'name' => 'Don Ramón Benítez',
                'email' => 'ramon.benitez@agroconnect.test',
                'telefono' => '+543718401122',
                'localidad' => 'Laguna Naineck',
                'lat' => -25.2425000,
                'lng' => -57.7312000,
                'numero_socio' => 'SOC-001',
                'socio_validado' => true,
                'notificar_por_mail' => true,
            ],
            'elena' => [
                'name' => 'Doña Elena Ayala',
                'email' => 'elena.ayala@agroconnect.test',
                'telefono' => '+543718412233',
                'localidad' => 'Laguna Naineck',
                'lat' => -25.2480000,
                'lng' => -57.7380000,
                'numero_socio' => 'SOC-002',
                'socio_validado' => true,
                'notificar_por_mail' => true,
            ],
            'cacho' => [
                'name' => 'Carlos "Cacho" Fernández',
                'email' => 'carlos.fernandez@agroconnect.test',
                'telefono' => '+543718423344',
                'localidad' => 'Laguna Blanca',
                'lat' => -25.2510000,
                'lng' => -57.7420000,
                'numero_socio' => 'SOC-003',
                'socio_validado' => true,
                'notificar_por_mail' => false,
            ],
            'soledad' => [
                'name' => 'María Soledad Gómez',
                'email' => 'soledad.gomez@agroconnect.test',
                'telefono' => '+543718434455',
                'localidad' => 'Laguna Naineck',
                'lat' => -25.2390000,
                'lng' => -57.7250000,
                'numero_socio' => null,
                'socio_validado' => false,
                'notificar_por_mail' => true,
            ],
            'esteban' => [
                'name' => 'Esteban Morales',
                'email' => 'esteban.morales@agroconnect.test',
                'telefono' => '+543718445566',
                'localidad' => 'Riacho He-Hé',
                'lat' => -25.3520000,
                'lng' => -57.8760000,
                'numero_socio' => null,
                'socio_validado' => false,
                'notificar_por_mail' => true,
            ],
            'graciela' => [
                'name' => 'Graciela Duarte',
                'email' => 'graciela.duarte@agroconnect.test',
                'telefono' => '+543718456677',
                'localidad' => 'Laguna Naineck',
                'lat' => -25.2460000,
                'lng' => -57.7350000,
                'numero_socio' => 'SOC-004',
                'socio_validado' => true,
                'notificar_por_mail' => true,
            ],
        ];

        $productores = [];
        foreach ($productoresData as $key => $data) {
            $productores[$key] = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'rol' => User::ROL_PRODUCTOR,
                    'telefono' => $data['telefono'],
                    'localidad' => $data['localidad'],
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                    'numero_socio' => $data['numero_socio'],
                    'socio_validado' => $data['socio_validado'],
                    'notificar_por_mail' => $data['notificar_por_mail'],
                    'inhabilitado' => false,
                ]
            );
        }

        // 3. Compradores
        $compradoresData = [
            'lucas' => [
                'name' => 'Lucas Peralta',
                'email' => 'lucas.peralta@agroconnect.test',
                'telefono' => '+543718501122',
                'localidad' => 'Clorinda',
                'lat' => -25.2848000,
                'lng' => -57.7180000,
            ],
            'mariana' => [
                'name' => 'Mariana Beltrán',
                'email' => 'mariana.beltran@agroconnect.test',
                'telefono' => '+543718512233',
                'localidad' => 'Laguna Blanca',
                'lat' => -25.2530000,
                'lng' => -57.7450000,
            ],
            'jorge' => [
                'name' => 'Jorge Insfrán',
                'email' => 'jorge.insfran@agroconnect.test',
                'telefono' => '+543718523344',
                'localidad' => 'Formosa',
                'lat' => -26.1849000,
                'lng' => -58.1731000,
            ],
            'valeria' => [
                'name' => 'Valeria Centurión',
                'email' => 'valeria.centurion@agroconnect.test',
                'telefono' => '+543718534455',
                'localidad' => 'Laguna Naineck',
                'lat' => -25.2430000,
                'lng' => -57.7320000,
            ],
        ];

        $compradores = [];
        foreach ($compradoresData as $key => $data) {
            $compradores[$key] = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'rol' => User::ROL_COMPRADOR,
                    'telefono' => $data['telefono'],
                    'localidad' => $data['localidad'],
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                    'notificar_por_mail' => true,
                    'inhabilitado' => false,
                ]
            );
        }

        // 4. Publicaciones creíbles (30 en total)
        $publicacionesData = [
            // Frutas
            [
                'productor' => 'ramon',
                'categoria' => 'Frutas',
                'titulo' => 'Banana Cavendish madurada en planta',
                'descripcion' => 'Bananas seleccionadas de primera calidad, cosechadas en su punto justo de madurez sin cámaras de maduración artificial. Dulzura garantizada.',
                'precio' => 1200.00,
                'unidad' => 'kg',
                'stock' => 150,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'ramon',
                'categoria' => 'Frutas',
                'titulo' => 'Banana Oro dulce de chacra',
                'descripcion' => 'Variedad pequeña muy dulce, ideal para consumo familiar o repostería. Cultivada bajo sombra natural en Laguna Naineck.',
                'precio' => 1400.00,
                'unidad' => 'docena',
                'stock' => 80,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'elena',
                'categoria' => 'Frutas',
                'titulo' => 'Pomelos blancos y rosados frescos',
                'descripcion' => 'Cítricos jugosos cosechados en el día, con excelente equilibrio de acidez y dulzura. Ideales para jugo matinal o postres.',
                'precio' => 950.00,
                'unidad' => 'kg',
                'stock' => 100,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'elena',
                'categoria' => 'Frutas',
                'titulo' => 'Naranjas de ombligo dulces',
                'descripcion' => 'Naranjas de mesa sin semillas, piel brillante y gran cantidad de jugo. Venta directa por cajón o docena.',
                'precio' => 1100.00,
                'unidad' => 'docena',
                'stock' => 120,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'cacho',
                'categoria' => 'Frutas',
                'titulo' => 'Mandarinas criollas de estación',
                'descripcion' => 'Mandarinas fáciles de pelar, aromáticas y muy dulces. Fruta fresca recién bajada de la planta.',
                'precio' => 850.00,
                'unidad' => 'docena',
                'stock' => 90,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'soledad',
                'categoria' => 'Frutas',
                'titulo' => 'Mamón maduro para dulce y fresco',
                'descripcion' => 'Mamones de pulpa firme y color anaranjado intenso. Perfectos para preparar mamón en almíbar casero o comer fresco.',
                'precio' => 1300.00,
                'unidad' => 'kg',
                'stock' => 60,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'graciela',
                'categoria' => 'Frutas',
                'titulo' => 'Limones cuatro estaciones jugosos',
                'descripcion' => 'Limones frescos de corteza fina y abundante jugo natural, sin ceras ni tratamientos químicos posteriores.',
                'precio' => 800.00,
                'unidad' => 'docena',
                'stock' => 110,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'esteban',
                'categoria' => 'Frutas',
                'titulo' => 'Mango criollo formoseño',
                'descripcion' => 'Mangos aromáticos de árbol añoso, sabor intenso y gran tamaño. Última cosecha de la temporada.',
                'precio' => 1500.00,
                'unidad' => 'kg',
                'stock' => 0,
                'estado' => Publicacion::ESTADO_VENDIDA,
            ],

            // Verduras
            [
                'productor' => 'ramon',
                'categoria' => 'Verduras',
                'titulo' => 'Mandioca fresca pelada al vacío',
                'descripcion' => 'Mandioca blanda y harinosa, de cocción rápida. Ya pelada, lavada y lista para cocinar o congelar.',
                'precio' => 900.00,
                'unidad' => 'kg',
                'stock' => 200,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'cacho',
                'categoria' => 'Verduras',
                'titulo' => 'Mandioca con cáscara recién arrancada',
                'descripcion' => 'Raíces tiernas cosechadas temprano a la mañana. Excelente para hervir, freír o acompañar asados.',
                'precio' => 700.00,
                'unidad' => 'kg',
                'stock' => 300,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'elena',
                'categoria' => 'Verduras',
                'titulo' => 'Batatas coloradas dulces de suelo arenoso',
                'descripcion' => 'Batatas parejas de pulpa cremosa y muy dulce, cultivadas en lomadas del Consorcio.',
                'precio' => 850.00,
                'unidad' => 'kg',
                'stock' => 140,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'soledad',
                'categoria' => 'Verduras',
                'titulo' => 'Zapallo plomo criollo entero',
                'descripcion' => 'Zapallos enteros de cáscara firme, ideales para locro, sopa o puré. Conservación prolongada.',
                'precio' => 650.00,
                'unidad' => 'kg',
                'stock' => 180,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'graciela',
                'categoria' => 'Verduras',
                'titulo' => 'Tomates perita de huerta agroecológica',
                'descripcion' => 'Tomates carnosos regados por goteo, sin agroquímicos. Sabor auténtico a tomate de antes.',
                'precio' => 1600.00,
                'unidad' => 'kg',
                'stock' => 75,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'graciela',
                'categoria' => 'Verduras',
                'titulo' => 'Lechuga mantecosa fresca de tablón',
                'descripcion' => 'Hojas tiernas y crujientes, cortadas a pedido para mantener máxima frescura.',
                'precio' => 950.00,
                'unidad' => 'kg',
                'stock' => 50,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'esteban',
                'categoria' => 'Verduras',
                'titulo' => 'Acelga criolla de hojas grandes',
                'descripcion' => 'Atados abundantes de acelga verde brillante, pencas tiernas y hojas sanas.',
                'precio' => 750.00,
                'unidad' => 'unidad',
                'stock' => 60,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'elena',
                'categoria' => 'Verduras',
                'titulo' => 'Cebolla de verdeo y perejil fresco',
                'descripcion' => 'Atado combinado de verdeo y perejil recién cortados de la huerta familiar para condimento.',
                'precio' => 600.00,
                'unidad' => 'unidad',
                'stock' => 80,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'cacho',
                'categoria' => 'Verduras',
                'titulo' => 'Pimientos verdes y rojos de huerta',
                'descripcion' => 'Morrones carnosos de buen calibre, ideales para salsas, guisados y ensaladas.',
                'precio' => 1800.00,
                'unidad' => 'kg',
                'stock' => 40,
                'estado' => Publicacion::ESTADO_PAUSADA,
            ],
            [
                'productor' => 'ramon',
                'categoria' => 'Verduras',
                'titulo' => 'Choclo dulce para sopa paraguaya',
                'descripcion' => 'Espigas tiernas de choclo fresco con granos lechosos, especial para chipá guazú y sopa paraguaya.',
                'precio' => 2200.00,
                'unidad' => 'docena',
                'stock' => 95,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],

            // Miel y conservas
            [
                'productor' => 'graciela',
                'categoria' => 'Miel y conservas',
                'titulo' => 'Miel pura de monte virgen en frasco',
                'descripcion' => 'Miel de flores silvestres del monte nativo, 100% natural sin pasteurizar ni aditivos. Frasco de 1 kg.',
                'precio' => 4500.00,
                'unidad' => 'kg',
                'stock' => 60,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'cacho',
                'categoria' => 'Miel y conservas',
                'titulo' => 'Miel de eucalipto cosechada en colmenar',
                'descripcion' => 'Miel aromática de color ámbar oscuro con propiedades balsámicas tradicionales.',
                'precio' => 4200.00,
                'unidad' => 'kg',
                'stock' => 45,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'elena',
                'categoria' => 'Miel y conservas',
                'titulo' => 'Dulce casero de mamón en almíbar',
                'descripcion' => 'Elaboración artesanal según receta tradicional familiar, trozos firmes en almíbar denso.',
                'precio' => 3200.00,
                'unidad' => 'unidad',
                'stock' => 35,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'soledad',
                'categoria' => 'Miel y conservas',
                'titulo' => 'Pickles caseros de hortalizas en vinagre',
                'descripcion' => 'Conservas de zanahoria, coliflor, cebollitas y morrones en vinagre especiado.',
                'precio' => 2800.00,
                'unidad' => 'unidad',
                'stock' => 30,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'soledad',
                'categoria' => 'Miel y conservas',
                'titulo' => 'Mermelada artesanal de naranja agria',
                'descripcion' => 'Dulce casero con cáscaras confitadas y azúcar orgánica, punto exacto de cocción.',
                'precio' => 2900.00,
                'unidad' => 'unidad',
                'stock' => 25,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],

            // Huevos
            [
                'productor' => 'elena',
                'categoria' => 'Huevos',
                'titulo' => 'Huevos de campo de gallinas libres',
                'descripcion' => 'Huevos frescos de gallinas alimentadas con maíz partido y pastoreo diario en la chacra.',
                'precio' => 2400.00,
                'unidad' => 'docena',
                'stock' => 50,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'esteban',
                'categoria' => 'Huevos',
                'titulo' => 'Maple de huevos frescos de campo (30 un)',
                'descripcion' => 'Maple completo de 30 huevos clasificados, cáscara firme y yema de coloración naranja intenso.',
                'precio' => 5500.00,
                'unidad' => 'unidad',
                'stock' => 20,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'graciela',
                'categoria' => 'Huevos',
                'titulo' => 'Huevos caseros colorados seleccionados',
                'descripcion' => 'Docena de huevos colorados de granja familiar, controlados día a día.',
                'precio' => 2600.00,
                'unidad' => 'docena',
                'stock' => 30,
                'estado' => Publicacion::ESTADO_PAUSADA,
            ],

            // Lácteos
            [
                'productor' => 'cacho',
                'categoria' => 'Lácteos',
                'titulo' => 'Queso criollo artesanal estacionado',
                'descripcion' => 'Queso de pasta semidura elaborado con leche entera de ordeñe matinal, salado a punto.',
                'precio' => 5800.00,
                'unidad' => 'kg',
                'stock' => 40,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'ramon',
                'categoria' => 'Lácteos',
                'titulo' => 'Queso paraguay fresco para chipá',
                'descripcion' => 'Queso fresco artesanal de consistencia blanda y cremosa, el toque indispensable para chipá tradicional.',
                'precio' => 6200.00,
                'unidad' => 'kg',
                'stock' => 35,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'cacho',
                'categoria' => 'Lácteos',
                'titulo' => 'Leche cruda entera recién ordeñada',
                'descripcion' => 'Leche de tambo familiar, pura y sin rebajar. Requiere hervido antes de su consumo.',
                'precio' => 1100.00,
                'unidad' => 'litro',
                'stock' => 0,
                'estado' => Publicacion::ESTADO_VENDIDA,
            ],

            // Carnes
            [
                'productor' => 'ramon',
                'categoria' => 'Carnes',
                'titulo' => 'Lechón de campo criado a maíz',
                'descripcion' => 'Lechón faenado fresco bajo pedido, peso aproximado 10 a 12 kg, criado libre en potrero.',
                'precio' => 7500.00,
                'unidad' => 'kg',
                'stock' => 15,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
            [
                'productor' => 'esteban',
                'categoria' => 'Carnes',
                'titulo' => 'Pollo casero campero entero',
                'descripcion' => 'Pollo de campo de carne firme y sabrosa, criado a maíz y verde natural sin promotores de crecimiento.',
                'precio' => 3800.00,
                'unidad' => 'kg',
                'stock' => 25,
                'estado' => Publicacion::ESTADO_ACTIVA,
            ],
        ];

        $publicaciones = [];
        foreach ($publicacionesData as $item) {
            $productorModel = $productores[$item['productor']];
            $categoriaModel = $categorias[$item['categoria']];

            $pub = Publicacion::query()->firstOrCreate(
                [
                    'usuario_id' => $productorModel->id,
                    'titulo' => $item['titulo'],
                ],
                [
                    'categoria_id' => $categoriaModel->id,
                    'descripcion' => $item['descripcion'],
                    'precio' => $item['precio'],
                    'unidad' => $item['unidad'],
                    'stock' => $item['stock'],
                    'estado' => $item['estado'],
                ]
            );

            $publicaciones[] = $pub;
        }

        // 5. Reseñas creíbles
        $resenasData = [
            [
                'comprador' => 'lucas',
                'publicacion_index' => 0, // Banana Cavendish
                'puntuacion' => 5,
                'comentario' => 'Excelente la banana, se nota la diferencia de madurar en planta. Muy dulce y fresca.',
            ],
            [
                'comprador' => 'mariana',
                'publicacion_index' => 0,
                'puntuacion' => 5,
                'comentario' => 'Compré 10 kg para dulce y para consumir en la semana, calidad insuperable. Don Ramón muy atento.',
            ],
            [
                'comprador' => 'valeria',
                'publicacion_index' => 2, // Pomelos
                'puntuacion' => 4,
                'comentario' => 'Muy jugosos los pomelos. Excelente para el desayuno.',
            ],
            [
                'comprador' => 'jorge',
                'publicacion_index' => 8, // Mandioca fresca pelada
                'puntuacion' => 5,
                'comentario' => 'Mandioca manteca de primera, se cocinó en 15 minutos. Un espectáculo.',
            ],
            [
                'comprador' => 'lucas',
                'publicacion_index' => 12, // Tomates perita
                'puntuacion' => 5,
                'comentario' => 'Hacía mucho que no comía un tomate con verdadero sabor. Felicitaciones a Graciela.',
            ],
            [
                'comprador' => 'mariana',
                'publicacion_index' => 18, // Miel pura de monte
                'puntuacion' => 5,
                'comentario' => 'Miel espesa y aromática, 100% auténtica del monte formoseño.',
            ],
            [
                'comprador' => 'valeria',
                'publicacion_index' => 23, // Huevos de campo
                'puntuacion' => 4,
                'comentario' => 'Huevos con yemas bien naranjas, nada que ver con los de súper.',
            ],
            [
                'comprador' => 'jorge',
                'publicacion_index' => 26, // Queso criollo
                'puntuacion' => 5,
                'comentario' => 'Queso con buen estacionamiento, firme y con sabor definido. Volveré a comprar.',
            ],
            [
                'comprador' => 'lucas',
                'publicacion_index' => 27, // Queso paraguay
                'puntuacion' => 5,
                'comentario' => 'El chipá salió increíble gracias a este queso bien fresco y húmedo.',
            ],
        ];

        foreach ($resenasData as $resena) {
            $compradorModel = $compradores[$resena['comprador']];
            $pubModel = $publicaciones[$resena['publicacion_index']];

            Resena::query()->firstOrCreate(
                [
                    'usuario_id' => $compradorModel->id,
                    'publicacion_id' => $pubModel->id,
                ],
                [
                    'puntuacion' => $resena['puntuacion'],
                    'comentario' => $resena['comentario'],
                ]
            );
        }

        // 6. Favoritos
        $favoritosData = [
            ['comprador' => 'lucas', 'publicacion_index' => 0],
            ['comprador' => 'lucas', 'publicacion_index' => 8],
            ['comprador' => 'mariana', 'publicacion_index' => 18],
            ['comprador' => 'mariana', 'publicacion_index' => 20],
            ['comprador' => 'jorge', 'publicacion_index' => 26],
            ['comprador' => 'jorge', 'publicacion_index' => 29],
            ['comprador' => 'valeria', 'publicacion_index' => 12],
            ['comprador' => 'valeria', 'publicacion_index' => 23],
        ];

        foreach ($favoritosData as $fav) {
            $compradorModel = $compradores[$fav['comprador']];
            $pubModel = $publicaciones[$fav['publicacion_index']];

            Favorito::query()->firstOrCreate([
                'usuario_id' => $compradorModel->id,
                'publicacion_id' => $pubModel->id,
            ]);
        }

        // 7. Solicitudes de compra (pendientes, aceptadas, rechazadas)
        $solicitudesData = [
            [
                'comprador' => 'lucas',
                'publicacion_index' => 0, // Banana Cavendish
                'cantidad' => 15,
                'mensaje' => 'Hola Ramón, quisiera coordinar la compra de 15 kg para retirar el sábado por tu chacra.',
                'estado' => SolicitudCompra::ESTADO_PENDIENTE,
            ],
            [
                'comprador' => 'mariana',
                'publicacion_index' => 18, // Miel pura de monte
                'cantidad' => 3,
                'mensaje' => 'Buen día Graciela, me interesan 3 frascos de miel. ¿Se puede retirar por Laguna Naineck?',
                'estado' => SolicitudCompra::ESTADO_ACEPTADA,
            ],
            [
                'comprador' => 'jorge',
                'publicacion_index' => 26, // Queso criollo
                'cantidad' => 50,
                'mensaje' => 'Hola Cacho, necesitaría 50 kg de queso para mi rotisería en Formosa.',
                'estado' => SolicitudCompra::ESTADO_RECHAZADA,
            ],
            [
                'comprador' => 'valeria',
                'publicacion_index' => 12, // Tomates perita
                'cantidad' => 5,
                'mensaje' => 'Hola Graciela, ¿tenés disponibles 5 kg de tomate para pasar hoy a la tarde?',
                'estado' => SolicitudCompra::ESTADO_ACEPTADA,
            ],
            [
                'comprador' => 'lucas',
                'publicacion_index' => 27, // Queso paraguay
                'cantidad' => 4,
                'mensaje' => 'Don Ramón, necesitaría 4 kg de queso para el fin de semana largo. Gracias.',
                'estado' => SolicitudCompra::ESTADO_PENDIENTE,
            ],
        ];

        foreach ($solicitudesData as $solicitud) {
            $compradorModel = $compradores[$solicitud['comprador']];
            $pubModel = $publicaciones[$solicitud['publicacion_index']];

            SolicitudCompra::query()->firstOrCreate(
                [
                    'usuario_id' => $compradorModel->id,
                    'publicacion_id' => $pubModel->id,
                    'mensaje' => $solicitud['mensaje'],
                ],
                [
                    'cantidad' => $solicitud['cantidad'],
                    'estado' => $solicitud['estado'],
                ]
            );
        }
    }
}
