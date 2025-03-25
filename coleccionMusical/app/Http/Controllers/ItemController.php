<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Item;
use App\Models\entity\ItemEntity;
use App\Models\entity\ExternalIdEntity;
use App\Models\DTO\ItemDTO;

use TypeError;

class ItemController extends Controller
{

    public function __construct() {}



    // public function index() {

    //     // Ya no necesitamos la utilidad ApiJsonResponse, Laravel tiene incorporada esta función
    //     return response()->json([
    //         'status' => 'OK',
    //         'code' =>  200,
    //         'description' => 'Hola, has llegado al indice de esta API, usa sus endpoints para obtener o modificar datos',
    //         'data' => null
    //     ]);
    // }

    public function getAll() {

        $itemsCollection = DB::table('items')->get();

        $itemsDTO = [];

        foreach($itemsCollection as $itemFila) {

            // Obtiene un itemDTO a partir del ID del objeto devuelto por el ORM
            $itemDTO = $this->getItemDTOById($itemFila->id);

            $itemsDTO[] = $itemDTO;
        }

        // Ya no necesitamos la utilidad ApiJsonResponse, Laravel tiene incorporada esta función
        if(isset($itemsDTO)) {
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'description' => 'Todos los ítems (' . count($itemsDTO) . ')',
                'data' => $itemsDTO
            ]);
        } else {
            return response()->json([
                'status' => 'ERROR',
                'code' => 500,
                'description' => 'No hay ítems',
                'data' => null
            ]);
        }

    }

    // Busca un item por ID, recaba sus entidades, las mapea a un DTO y lo devuelve en la respuesta
    public function getById($id) {

        // Obtiene un DTO a partir de su ID, o false
        $itemDTO = $this->getItemDTOById($id);

        // Devuelve el DTO o un 404
        if ($itemDTO) {

            // Envía la respuesta
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'description' => 'Ítem con ID ' . $id,
                'data' => $itemDTO
            ]);
        } else {
            return response()->json([
                'status' => 'ERROR',
                'code' => 404,
                'description' => 'No existe un ítem con ID ' . $id,
                'data' => null
            ]);
        }
    }


    // public function getByArtist($artist) {

    //     $artist = ucwords(str_replace('-', ' ', $artist));

    //     $items = $this->itemDAO->getItemsByArtist($artist);

    //     if($items) {
    //         $response = new ApiResponse('OK', 200, 'Todos los ítems del artista solicitado (' . $artist . ')', $items);
    //         return $this->sendJsonResponse($response);
    //     } else {
    //         $response = new ApiResponse('ERROR', 404, 'Artista no encontrado (' . $artist . ')', null);
    //         return $this->sendJsonResponse($response);
    //     }
    // }


    // public function getByFormat($format) {

    //     $items = $this->itemDAO->getItemsByFormat($format);

    //     if($items) {
    //         $response = new ApiResponse('OK', 200, 'Todos los ítems del formato solicitado (' . $format . ')', $items);
    //         return $this->sendJsonResponse($response);
    //     } else {
    //         $response = new ApiResponse('ERROR', 404, 'Formato no encontrado (' . $format . ')', null);
    //         return $this->sendJsonResponse($response);
    //     }
    // }




    // public function sortByKey($key, $order) {

    //     if ($key === 'externalIds') {
    //         // No se puede ordenar por externalIds
    //         $response = new ApiResponse('ERROR', 400, 'ERROR: No se puede ordenar por externalIds al ser un array', null);
    //         return $this->sendJsonResponse($response);
    //     }

    //     if (!in_array(strtolower($order), ['asc', 'desc'])) {
    //         // El tipo de orden es incorrecto
    //         $response = new ApiResponse('ERROR', 400, 'El tipo de orden solo puede ser ASC o DESC', null);
    //         return $this->sendJsonResponse($response);
    //     }

    //     try {
            
    //         // Intenta ordenar con la clave y el tipo de orden recibidos
    //         $items = $this->itemDAO->sortItemsByKey($key, $order);

    //         if($items) {
    //             $response = new ApiResponse('OK', 200, 'Listado de ítems ordenados según el criterio solicitado (' . $key . ', ' . $order . ')', $items);
    //             return $this->sendJsonResponse($response);
    //         } else {
    //             $response = new ApiResponse('ERROR', 404, 'No se han encontrado ítems', null);
    //             return $this->sendJsonResponse($response);
    //         }
    
    //     // Si la columna por la que se ha pedido ordenar no existe, o el tipo de orden es erroneo, llega una excepcion y se devuelve un 400
    //     } catch (Exception $e) {
            
    //         $response = new ApiResponse('ERROR', 400, 'La clave para ordenar (' . $key . ') no existe', null);
    //         return $this->sendJsonResponse($response);
    //     }

    // }


    // Guarda un nuevo item en la BD y en caso de exito lo devuelve con un 201
    public function create(Request $request) {

        $payload = json_decode($request->getContent(), true);
        
        // var_dump($payload);

        // Intenta modelar los datos a un ItemDTO para ver si están bien formados
        try {
            @$itemDTOModelado = new ItemDTO(
                $payload['title'], $payload['artist'], $payload['format'],
                $payload['year'], $payload['origYear'], $payload['label'],
                $payload['rating'], $payload['comment'], $payload['buyPrice'],
                $payload['condition'], $payload['sellPrice'], $payload['externalIds']
            );
        } catch(TypeError) {

            return response()->json([
                'status' => 'ERROR',
                'code' => 400,
                'description' => 'Los datos recibidos están mal formados',
                'data' => $request->getContent()
            ]);
        }


        // Comprueba que los campos que no son string tengan buen formato
        if ($this->chequearValores($payload) !== true) {
                
            $textoRespuesta = $this->chequearValores($payload);

            return response()->json([
                'status' => 'ERROR',
                'code' => 400,
                'description' => $textoRespuesta,
                'data' => $payload
            ]);
        }

        // INSERCION en la tabla `items`, obteniendo el ID resultante
        $itemId = DB::table('items')->insertGetId(
            [
                'title' => $payload['title'], 'artist' => $payload['artist'],
                'format' => $payload['format'], 'year' => $payload['year'],
                'origyear' => $payload['origYear'], 'label' => $payload['label'],
                'rating' => $payload['rating'], 'comment' => $payload['comment'],
                'buyprice' => $payload['buyPrice'], 'condition' => $payload['condition'],
                'sellprice' => $payload['sellPrice']
            ]
        );

        // INSERCIONES en la tabla `externalids`, usando el ID de ítem obtenido previamente
        foreach($payload['externalIds'] as $clave => $valor) {
            DB::table('externalids')->insert(

                ['supplier' => $clave, 'value' => $valor, 'itemid' => $itemId]

            );
        }

        if ($itemId) {

            // Obtiene el DTO del ítem creado
            $itemDTO = $this->getItemDTOById($itemId);

           // Envía la respuesta
           return response()->json([
                'status' => 'Created',
                'code' => 201,
                'description' => 'Ítem guardado con ID ' . $itemId,
                'data' => $itemDTO
            ]);
        } else {
            return response()->json([
                'status' => 'ERROR',
                'code' => 500,
                'description' => 'No se pudo guardar el ítem',
                'data' => null
            ]);
        }
    }

    // // Actualiza datos de un item existente. No tienen por que recibir todos los campos, solo los que cambian.
    // public function update($datosJson) {

    //     if(array_key_exists('id', $datosJson)) {

    //         // Comprueba que los campos que no son string tengan buen formato
    //         if ($this->chequearValores($datosJson) !== true) {
                    
    //             $textoRespuesta = $this->chequearValores($datosJson);

    //             $response = new ApiResponse('ERROR', 400, $textoRespuesta, $datosJson);
    //             return $this->sendJsonResponse($response);
    //         }

    //         $itemId = $datosJson['id'];

    //         try {
    //             $itemEntidadActualizado = $this->itemDAO->updateItem($datosJson);
    //             $externalIdsEntidadActualizados = $this->itemDAO->updateExternalIds($datosJson);
    //         } catch (Error) {
    //             $response = new ApiResponse('ERROR', 400, 'Los datos recibidos están mal formados', $datosJson);
    //             return $this->sendJsonResponse($response);
    //         }

            

    //         $arrayExternalIds = [];

    //         foreach($externalIdsEntidadActualizados as $unExternalId) {
    //             $arrayExternalIds[$unExternalId->getSupplier()] = $unExternalId->getValue();
    //         }

    //         if ($itemEntidadActualizado) {

    //             // Mapea el DTO para devolverlo al cliente
    //             $itemDTO = new ItemDTO(
    //                 $itemEntidadActualizado->getId(),
    //                 $itemEntidadActualizado->getTitle(),
    //                 $itemEntidadActualizado->getArtist(),
    //                 $itemEntidadActualizado->getFormat(),
    //                 $itemEntidadActualizado->getYear(),
    //                 $itemEntidadActualizado->getOrigYear(),
    //                 $itemEntidadActualizado->getLabel(),
    //                 $itemEntidadActualizado->getRating(),
    //                 $itemEntidadActualizado->getComment(),
    //                 $itemEntidadActualizado->getBuyprice(),
    //                 $itemEntidadActualizado->getCondition(),
    //                 $itemEntidadActualizado->getSellPrice(),
    //                 $arrayExternalIds
    //             );

    //             if($itemDTO) {
    //                 $response = new ApiResponse('OK', 204, 'Item ' . $itemId . ' actualizado.', $itemDTO);
    //                     return $this->sendJsonResponse($response);
    //             } else {
    //                 $response = new ApiResponse('ERROR', 500, 'No se pudo acualizar el ítem ' . $itemId . '.', null);
    //                 return $this->sendJsonResponse($response);
    //             }
    //         } else {
    //             // No ha encontrado el item
    //             $response = new ApiResponse('ERROR', 404, 'No existe un ítem con ID ' . $itemId, null);
    //             return $this->sendJsonResponse($response);
    //         }
    //     } else {
    //         // No ha encontrado el item
    //         $response = new ApiResponse('ERROR', 400, 'Es necesario un ID para actualizar un ítem', null);
    //         return $this->sendJsonResponse($response);
    //     }
    // }

    // public function delete($datosJson) {
        
    //     $itemId = $datosJson['id'];

    //     // Comprueba si los datos están bien formados ("id" con valor entero)
    //     try {
    //         $itemAEliminar = $this->itemDAO->getItemById($itemId);
    //     } catch (TypeError) {
    //         $response = new ApiResponse('ERROR', 400, 'TypeError: Los datos recibidos están mal formados', $datosJson);
    //         return $this->sendJsonResponse($response);
    //     }

    //     if($itemAEliminar) {
    //         $externalIdsAEliminar = $this->itemDAO->getExternalIdsByItemId($itemId);

    //         // Genera el DTO para devolverlo al cliente
    //         $itemDTO = new ItemDTO(
    //             $itemAEliminar->getId(),
    //             $itemAEliminar->getTitle(),
    //             $itemAEliminar->getArtist(),
    //             $itemAEliminar->getFormat(),
    //             $itemAEliminar->getYear(),
    //             $itemAEliminar->getOrigYear(),
    //             $itemAEliminar->getLabel(),
    //             $itemAEliminar->getRating(),
    //             $itemAEliminar->getComment(),
    //             $itemAEliminar->getBuyprice(),
    //             $itemAEliminar->getCondition(),
    //             $itemAEliminar->getSellPrice(),
    //             $externalIdsAEliminar
    //         );

    //         $itemEntidadEliminado = $this->itemDAO->deleteItem($itemId);

    //         if ($itemEntidadEliminado) {
    //             $response = new ApiResponse('OK', 200, 'Item ' . $itemId . ' eliminado.', null);
    //             return $this->sendJsonResponse($response);
    //         } else {
    //             $response = new ApiResponse('ERROR', 500, 'No se pudo eliminar el ítem con ID ' . $itemId . '.', null);
    //             return $this->sendJsonResponse($response);
    //         }
    //     } else {
    //         $response = new ApiResponse('ERROR', 404, 'No existe un ítem con ID ' . $itemId . '.', null);
    //         return $this->sendJsonResponse($response);
    //     }
        
    // }


    // ------------------------- Funciones auxiliares -------------------------


    // Obtiene un itemDTO a partir de su ID
    private function getItemDTOById(int $itemId) {
        
        // Obtiene la fila correspondiente al ítem buscado por ID
        $itemFila = DB::table('items')->where('id', $itemId)->first();

        // Si existe genera las entidades correspondientes y las mapea al DTO
        if ($itemFila) {
            // Modela la fila a una entidad Item
            $itemEntity = new ItemEntity(
                $itemFila->id, $itemFila->title, $itemFila->artist,
                $itemFila->format, $itemFila->year, $itemFila->origyear,
                $itemFila->label, $itemFila->rating, $itemFila->comment,
                $itemFila->buyprice, $itemFila->condition, $itemFila->sellprice
            );

            $externalIdsArray = $this->getExternalIdsByItemId($itemEntity->getId());

            $itemDTO = new ItemDTO(
                $itemEntity->getTitle(),
                $itemEntity->getArtist(),
                $itemEntity->getFormat(),
                $itemEntity->getYear(),
                $itemEntity->getOrigYear(),
                $itemEntity->getLabel(),
                $itemEntity->getRating(),
                $itemEntity->getComment(),
                $itemEntity->getBuyprice(),
                $itemEntity->getCondition(),
                $itemEntity->getSellPrice(),
                $externalIdsArray
            );

            return $itemDTO;

        // Si no hay resultados, devuelve false
        } else return false;
    }


    // Obtiene los External IDs de un ítem mediante su ID y los devuelve en un array
    private function getExternalIdsByItemId(int $itemId): array {

        // Obtiene una colección de filas con los externalIds correspondientes al ID del ítem
        $externalIdsCollection = DB::table('externalids')->where('itemid', $itemId)->get();

        $externalIdArray = [];

        // Se modelan a entidades y se guardan en un array los datos que interesan para el DTO
        foreach($externalIdsCollection as $externalIdsFila) {
            $externalIdEntity = new ExternalIdEntity(
                $externalIdsFila->id, $externalIdsFila->supplier,
                $externalIdsFila->value, $externalIdsFila->itemid
            );

            $externalIdArray[$externalIdEntity->getSupplier()] = $externalIdEntity->getValue();
        }

        return $externalIdArray;
    }

    // Comprueba que los valores recibidos cumplan los requisitos, si no genera el mensaje que se enviará en la respuesta HTTP
    public function chequearValores($item) {
        $respuesta = 'ERROR: El campo ';
        if (array_key_exists('year', $item) && (!filter_var($item['year'], FILTER_VALIDATE_INT) || intval($item['year']) <= 1900 || intval($item['year']) >= 2156)) return $respuesta . 'year debe ser un entero entre 1901 y 2155';
        if (array_key_exists('origYear', $item) && (!filter_var($item['origYear'], FILTER_VALIDATE_INT) || intval($item['origYear']) <= 1900 || intval($item['year']) >= 2156)) return $respuesta . 'origYear debe ser un entero entre 1901 y 2155';
        if (array_key_exists('rating', $item) && (!filter_var($item['rating'], FILTER_VALIDATE_INT) || intval($item['rating']) < 1 || intval($item['rating']) > 10)) return $respuesta . 'rating debe ser un entero entre 1 y 10';
        if (array_key_exists('buyPrice', $item) && (!is_numeric($item['buyPrice']) || intval($item['buyPrice']) < 0)) return $respuesta . 'buyPrice debe ser un número mayor o igual que cero';
        if (array_key_exists('condition', $item) && !in_array($item['condition'], ['M','NM','E','VG','G','P'])) return $respuesta . 'condition debe contener un valor de la Goldmine Grading Guide (M, NM, E, VG, G, P)';
        if (array_key_exists('sellPrice', $item) && (!is_numeric($item['sellPrice']) || intval($item['sellPrice']) < 0)) return $respuesta . 'sellPrice debe ser un número mayor o igual que cero';
        if (array_key_exists('externalIds', $item) && !is_array($item['externalIds'])) return $respuesta . 'externalIds debe ser un array asociativo de identificadores externos';

        return true;
    }
}
