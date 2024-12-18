<?php

namespace App\Livewire;

use Livewire\Component;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
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
            //echo $marca['nombre']."\n";

            // Consulta de la primra página de la marca
            $response = $client->get('/api/v1/marcas/'.'hikvision'.'/productos?pagina=1', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                ],
            ]);

            // Get array de marcas
            $body = $response->getBody();
            $response_paginas = json_decode($body, true);

            array_push($array_productos_bymarca, $response_paginas['productos']);

            // Consulta de todas las páginas de cada categoria
            for ($pagina=1; $pagina < $response_paginas['paginas']; $pagina++) {
                // Consulta de todas las páginas restantes de la marca
                $response = $client->get('/api/v1/marcas/'.'hikvision'.'/productos?pagina='.$pagina, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ],
                ]);

                $body = $response->getBody();
                $response_paginas = json_decode($body, true);
                array_push($array_productos_bymarca, $response_paginas['productos']);

            }

            break; // Detiene el bucle después de la primera iteración FOR TESTS
        }


       /*  foreach ($array_productos_bymarca as $array_productos) {
            foreach ($array_productos as $producto) {
                echo $producto['modelo'];
               dd($producto);
            }
        } */

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        // Encabezados
        $activeWorksheet->setCellValue('A1', 'producto_id');
        $activeWorksheet->setCellValue('B1', 'modelo');
        $activeWorksheet->setCellValue('C1', 'total_existencia');

        $row = 2;
        foreach ($array_productos_bymarca as $array_productos) {
            foreach ($array_productos as $producto) {
                $activeWorksheet->setCellValue('A'.$row, $producto["modelo"]);
            }
            $row++;
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
