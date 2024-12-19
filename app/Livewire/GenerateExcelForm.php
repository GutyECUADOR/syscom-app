<?php

namespace App\Livewire;

use Livewire\Component;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class GenerateExcelForm extends Component
{
    public $isSubmitting = false;

    // Método que se ejecutará cuando el enlace sea clickeado
    public function handleClick()
    {
        $this->isSubmitting = true;

         // Inicializar el cliente HTTP Guzzle
         $client = new Client([
            'base_uri' => 'https://developers.syscomcolombia.com',
            'timeout'  => 10.0, // Opcional: Tiempo de espera para la solicitud
        ]);

        // Preparar los datos en formato JSON
        $data = [
            'grant_type'    => 'client_credentials', // Tipo de autenticación
            'client_id'     => env('CLIENT_ID_SYSCOM_API'),    // Reemplaza con tu client ID
            'client_secret' => env('CLIENT_SECRET_SYSCOM_API') // Reemplaza con tu client secret
        ];

        // Enviar la solicitud POST
        $response = $client->post('/oauth/token', [
            'json' => $data,
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);

        // Obtener el cuerpo de la respuesta
        $body = $response->getBody();
        $result = json_decode($body, true);
        $token = $result['access_token']; // Get Access Token

        // Consulta de lista de marcas
        $response = $client->get('/api/v1/marcas', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token, // Set the Authorization header
                'Accept'        => 'application/json', // Optional: Specify that you expect JSON response
            ],
        ]);

        // Get array de marcas
        $body = $response->getBody();
        $array_marcas = json_decode($body, true);
        $array_productos_bymarca = [];

        foreach ($array_marcas as $marca) {
            $marca = $marca['id'];
            //$marca = '3m'; //test category - descomentar el brake;
            // Consulta de la primra página de la marca

            try {
                $response = $client->get('/api/v1/marcas/'.$marca.'/productos', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ],
                ]);

                // Get array de marcas
                $body = $response->getBody();
                $response_paginas = json_decode($body, true);

                array_push($array_productos_bymarca, $response_paginas['productos']);

                //LOG
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/syscom-api.log'),
                    ])->info(json_encode([$marca, $response_paginas['cantidad']]));

                 // Consulta de todas las páginas de cada categoria
                for ($pagina=1; $pagina < $response_paginas['paginas']; $pagina++) {
                    // Consulta de todas las páginas restantes de la marca
                    $response = $client->get('/api/v1/marcas/'.$marca.'/productos?pagina='.$pagina, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'Accept'        => 'application/json',
                        ],
                    ]);

                    $body = $response->getBody();
                    $response_paginas = json_decode($body, true);
                    array_push($array_productos_bymarca, $response_paginas['productos']);
                    //LOG
                    Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/syscom-api.log'),
                        ])->info(json_encode([$marca, $response_paginas['cantidad']]));

                }


            } catch (\Exception $ex) {
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/syscom-api.log'),
                ])->error($ex->getMessage());
            }


            //dd($array_productos_bymarca[0][0]["precios"]["precio_especial"]);
            //dd($array_productos_bymarca[0][0]["categorias"][0]["nombre"]);
            //break; // Detiene el bucle después de la primera iteración FOR TESTS

        }

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        // Encabezados
        $activeWorksheet->setCellValue('A1', 'PRODUCTOID');
        $activeWorksheet->setCellValue('B1', 'MODELO');
        $activeWorksheet->setCellValue('C1', 'TOTALEXISTENCIAS');
        $activeWorksheet->setCellValue('D1', 'TITULO');
        $activeWorksheet->setCellValue('E1', 'MARCA');
        $activeWorksheet->setCellValue('F1', 'IMAGEN01');
        $activeWorksheet->setCellValue('G1', 'IDMENUVL3');
        $activeWorksheet->setCellValue('H1', 'MENULV3');
        $activeWorksheet->setCellValue('I1', 'PRECIOESPECIAL');
        $activeWorksheet->setCellValue('J1', 'PRECIODESCUENTO');
        $activeWorksheet->setCellValue('K1', 'PRECIOLISTA');

        foreach ($activeWorksheet->getColumnIterator() as $column) {
            $activeWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $activeWorksheet->getStyle('A1:K1')->getFont()->setBold(true);

        $row = 2;
        foreach ($array_productos_bymarca as $array_productos) {
            foreach ($array_productos as $producto) {
                $activeWorksheet->setCellValue('A'.$row, $producto['producto_id']);
                $activeWorksheet->setCellValue('B'.$row, $producto['modelo']);
                $activeWorksheet->setCellValue('C'.$row, $producto['total_existencia']);
                $activeWorksheet->setCellValue('D'.$row, $producto['titulo']);
                $activeWorksheet->setCellValue('E'.$row, $producto['marca']);
                $activeWorksheet->setCellValue('F'.$row, $producto['img_portada']);
                $activeWorksheet->setCellValue('G'.$row, $producto['categorias'][0]['id'] ?? '');
                $activeWorksheet->setCellValue('H'.$row, $producto['categorias'][0]['nombre'] ?? '');
                $activeWorksheet->setCellValue('I'.$row, $producto['precios']['precio_especial'] ?? '');
                $activeWorksheet->setCellValue('J'.$row, $producto['precios']['precio_descuento'] ?? '');
                $activeWorksheet->setCellValue('K'.$row, $producto['precios']['precio_lista'] ?? '');

                $row++;
            }
        }

        session()->flash('message', '¡El archivo se ha generado correctamente!');
        $this->isSubmitting = false;

        $fileName = 'productos.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path($fileName);
        $writer->save($filePath);

        // Retornar el archivo como respuesta para descargar
        return Response::download($filePath)->deleteFileAfterSend(true);

        // Success

    }


    public function render()
    {
        return view('livewire.generate-excel-form');
    }
}
