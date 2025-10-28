<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Schema;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Services\Verifiers;
use Throwable;

abstract class SuperController extends BaseController
{
    protected Model $model;
    public $rules = [];
    public $rules_update = [];
    public $verif = null;
    public $mainRelations = [];
    public $ignoreArgs = [];
    protected $valoresDefault = [];
    protected $applyRulesOnUpdate = true;
    protected $useUsuarioId = false;
    //files options
    protected $folderName = '';
    protected $pathColumn = '';
    protected $originalNameColumn = '';
    protected $requireFile = false;
    protected $createdByColumn = 'created_by';
    protected $fileFieldName = 'file';
    protected $allowedMimes = [];
    protected $maxFileSize = 10240; // 10MB
    protected $allowMultipleFiles = false;
    protected $excludedColumnsInSearch = ['id', 'created_at', 'deleted_at', 'created_by', 'updated_at', 'password', 'token'];

    public function __construct(string $model)
    {
        $this->model = new $model;
        $this->verif = app(Verifiers::class);
    }

    /**
     * Función estandar para aobtener los registros de los modelos en el llamado a las API usando el metodo GET
     * @param Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return $this->basicGet($request);
    }

    /**
     * Basic function to return data using relations, conditionals, paginate, etc.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function basicGet(Request $request)
    {
        $paginador = $this->verif->paginador($request);
        $columns = $this->getColumns();
        $query = $this->model->withTrashed()->with($this->getQueryRelations($request));
        $conditionals = $this->checkArrayArgs($request->input('conditionals'));
        $query = $this->applyConditionals($conditionals, $query);
        $query = $this->applySorting($query, $request->input('sort'));
        $query = $this->applyAdvancedSearch($query, $request);
        $query = $this->applyMainFilters($query, $request->input('filtro'));
        $query = $this->applySearch($query, $request);
        $query = $query->paginate($paginador->per_page, $columns, 'page', $paginador->page);
        return response()->json([
            'status' => true,
            'message' => 'Consulta exitosa',
            'data' => $query->items(),
            'page' => $query->currentPage(),
            'per_page' => $query->perPage(),
            'total_pages' => $query->lastPage(),
            'total_items' => $query->total()
        ]);
    }

    /**
     * Responde a las solicitudes de tipo Post,
     * si es invalido regresa mensaje de error, si es array usa el metodo de creacion masiva si no usa el de creacion singular y refresa mensaje estandar
     * si es 1 objeto regresa el objeto recien creado.
     * @param Request $r
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function store(Request $r)
    {
        // Obtén los datos del request
        $data = $r->input('data');
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $insertionType = $this->verif->arrayType($data);
        if ($insertionType === 'invalid') {
            return $this->response_error('El formato incorrecto, el formato correcto es { data: Object|Object[] }}', 422);
        }

        $verification = $this->storeValidacionExtra($r, $data);
        if ($verification['status'] === false) {
            return $this->response_error($verification['message'], $verification['code']);
        } elseif ($this->verif->arrayValue('data', $verification, null) !== null) {
            $data = $verification['data'];
        }

        // Si es un array de objetos, llamamos la función para la inserción de multiples registros
        if ($insertionType === 'sequential') {
            $userData = auth()->user() ? auth()->user()->toArray() : null;
            return $this->store_multiple_inputs($data, $userData);
        }

        return $this->store_single_input($data);
    }

    /**
     * obtiene objeto cuando se llama a la api api/modelo/:id
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function show(Request $r, $id)
    {
        $resp = $this->verificacionPreShow($id);
        if ($resp['status'] === false) {
            return $this->response_error($resp['message'], $resp['code']);
        }

        $item = $this->model->with($this->getQueryRelations($r))->withTrashed()->find($id);

        if (!$item) {
            return $this->response_error("El registro con id $id no existe", 404);
        }

        return response()->json(['status' => true, 'message' => 'Solicitud Correcta', 'data' => $item]);
    }

    public function verificacionPreShow($id)
    {
        return ['status' => true, 'message' => 'Solicitud Correcta', 'code' => 200];
    }

    /**
     * Actualiza un registro al acceder a la ruta api/model/:id
     * @param Request $r
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function update(Request $r, $id)
    {
        if ($this->verif->arrayType($r->input('data')) == 'sequential' && $id === 'multiple') {
            return $this->massiveUpdate($r);
        }

        //Verificamos que el id sea un valor entero válido
        if (!$id = $this->verif->int($id, 1)) {
            return $this->response_error("El valor recibido no es válido $id", 400);
        }

        $item = $this->model::find($id);
        if (!$item) {
            return $this->response_error("El registro con id $id no existe", 404);
        }

        $originalData = $r->input('data');
        $currentAttributes = $item->toArray();

        // Filtrar solo los datos que han cambiado ***
        $datosCambios = [];
        foreach ($originalData as $key => $value) {
            // Campos sensibles como password siempre deben incluirse si se envían
            $sensitiveFields = ['password'];

            if (in_array($key, $sensitiveFields)) {
                $datosCambios[$key] = $value;
                // Para campos con confirmación, incluir también el campo de confirmación
                if ($key === 'password' && array_key_exists('password_confirmation', $originalData)) {
                    $datosCambios['password_confirmation'] = $originalData['password_confirmation'];
                }
                continue;
            }

            // Comprobamos si el atributo existe en el modelo y si su valor es diferente.
            // Usamos una comparación flexible (!=) para manejar conversiones de tipo (ej. string "123" vs int 123).
            if (array_key_exists($key, $currentAttributes) && $currentAttributes[$key] != $value) {
                $datosCambios[$key] = $value;
            }
        }

        // Salir temprano si no hay cambios ***
        if (empty($datosCambios)) {
            // No hay nada que actualizar. Devolvemos el modelo actual.
            // Usamos toArray() para mantener la consistencia con la respuesta final.
            return $this->updatePostAccion($item->toArray(), $item->toArray());
        }

        // Validamos solo los datos que han cambiado ***
        // Esto evita que fallen validaciones en campos que no se modificaron.
        if ($this->applyRulesOnUpdate === true) {
            $validator = Validator::make($datosCambios, $this->rules_update ?? []);
            if ($validator->fails()) {
                return $this->response_error($this->fails_string($validator), 422, $validator->errors());
            }
        }

        // Extraemos los datos del modelo DESPUÉS de filtrar y validar.
        // Ahora usamos $datosCambios en lugar de $originalData.
        $data = $this->extractModelData($datosCambios);
        unset($data[$this->createdByColumn]);

        //Validación antes de realizar la actualización
        $respVal = $this->verificacionPreActualizacion($id, $data);
        if ($respVal['status'] === false) {
            return $this->response_error($respVal['message'], $respVal['code']);
        }

        if (array_key_exists('data', $respVal)) {
            $data = $respVal['data'];
        }
        $data = $this->verif->agregarValoresDefault($data, $this->valoresDefault);

        $old_data = $item->toArray();
        try {
            $item->update($data);
        } catch (Throwable $th) {
            return $this->response_error($th->getMessage(), 500);
        }

        // Usamos $item->fresh() para asegurar que obtenemos el estado más reciente del modelo desde la BD
        return $this->updatePostAccion($item->fresh()->toArray(), $old_data);
    }

    private function massiveUpdate(Request $r)
    {
        /** @var array<int, array<string, int, null>> $data */
        $data = $r->input('data');
        $actualizados = 0;
        foreach ($data as $row) {
            unset($row['created_at']);
            $row['updated_at'] = now();

            $model = $this->model::where('id', '=', $row['id'])
                ->update($row);
            if ($model) {
                $actualizados++;
            }
        }
        return response()->json(['status' => true, 'message' => "$actualizados registros fueron actualizados."], 200);
    }

    /**
     * Elimina el registro en la url api/model/:id, si ya esta eliminado lo restaura, si no existe regresa error.
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Buscar el registro
        $registro = $this->model::withTrashed()->find($id);

        // Verificar si el registro existe
        if (!$registro) {
            return response()->json([
                'status' => false,
                'message' => "El registro a eliminar no existe"
            ], 404);
        }

        $result = $this->revisionPreEliminacion($id, $registro);
        if ($result['status'] === false) {
            return $this->response_error($result['message'], $result['code']);
        }

        // Si el registro está "eliminado" (soft delete), restaurarlo
        if ($registro->trashed()) {
            $registro->restore();
            $message = "El registro con id $id ha sido restaurado.";
        } else {
            // Si el registro no está eliminado, proceder a eliminarlo
            $registro->delete();
            $message = "El registro con id $id ha sido eliminado.";
        }

        return $this->revisionPostEliminacion($registro, $message);
    }

    /**
     * Devuelve los resultados del get usando las relaciones de los modelos, devolviendo los datos relacionados de tablas de nivel mas alto
     * @param Request $r
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithRelation(Request $r)
    {
        $query = $this->model->with($this->mainRelations ?? []);
        $conditionals = $this->checkArrayArgs($r->input('conditionals'));
        $query = $this->applyConditionals($conditionals, $query);
        $items = $query->get();
        return response()->json(['status' => true, 'message' => 'Consulta exitosa', 'data' => $items]);
    }

    /*FUNCIONES SUBORDINADAS PARA AYUDAR A LAS FUNCIONES PRINCIPALES */

    /**
     * Verifica si una variable de argumentos contiene datos correctos para ser usados en una consulta.
     *
     * ### Formato esperado:
     * Un array que contiene a su vez arrays secuenciales con al menos 3 elementos:
     * 1. **Elemento 1:** Nombre de la columna.
     * 2. **Elemento 2:** Operador de comparación.
     * 3. **Elemento 3:** Valor para la comparación.
     *
     * - La columna debe existir en el modelo de la tabla que se consultará.
     * - El operador debe ser uno válido.
     * - El valor debe ser de un tipo de dato compatible.
     *
     * ### Uso opcional de un modelo diferente:
     * Si se desea verificar las condiciones relativas a otra tabla, puedes especificar un modelo diferente al del controlador.
     * Esto es útil, por ejemplo, cuando estás trabajando en el contexto de "créditos", pero necesitas aplicar condicionales
     * sobre la tabla "usuarios".
     *
     * ### Ejemplo de uso:
     * ```php
     * $conditionals = $this->checkArrayArgs($r->conditionals, new Usuario());
     * $usuarios = $this->applyConditionals($conditionals, $usuarios);
     * $usuarios = $usuarios->get();
     * ```
     *
     * @param mixed $arrayArgs Arreglo de argumentos a verificar.
     * @param Model|null $model Modelo opcional para verificar las columnas relativas.
     * @return array Arreglo de argumentos válidos para la consulta.
     */
    public function checkArrayArgs($arrayArgs, ?Model $model = null)
    {
        if (is_string($arrayArgs)) {
            try {
                $arrayArgs = json_decode($arrayArgs);
            } catch (Throwable $th) {
                return [];
            }
        }

        if ($this->verif->arrayType($arrayArgs) !== 'sequential') {
            return [];
        }
        //el array debe tener dentro otros arrays secuanciales con la menos 3 elementos cada uno
        $validArgs = [];
        $operaters = ['=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', '!=', '<>', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'IS NULL', 'IS NOT NULL'];
        foreach ($arrayArgs as $argument) {
            if ($this->verif->arrayType($argument) !== 'sequential' || count($argument) < 3) {
                continue;
            }
            $column = $argument[0];
            $operater = $argument[1];
            $value = $argument[2] ?? "";
            //verificamos el tipo de dato de la columna
            if (
                !is_string($column) ||
                !is_string($operater) ||
                (!is_string($value) && !is_numeric($value))
            ) {
                continue;
            }

            if ($model) {
                if (!Schema::hasColumn($model->getTable(), $column)) {
                    continue;
                }
            } else {
                //Verificamos que la columna exista en la tabla que consultaremos
                if (!Schema::hasColumn($this->model->getTable(), $column)) {
                    continue;
                }
            }

            //Comproobamos que el operador sea uno valido
            $operater = strtoupper($operater);
            if (!in_array($operater, $operaters)) {
                continue;
            }

            $validArgs[] = [$column, $operater, $value];
        }
        return $validArgs;
    }

    protected function applyMainFilters(Builder $query, $filtro)
    {
        return $query;
    }

    /**
     * Aplica los condicionales (salida de la función checkArrayArgs) a una consulta
     * @param array $conditionals
     * @param mixed $query
     * @return Builder
     */
    public function applyConditionals($conditionals, Builder $query)
    {
        foreach ($conditionals as $condition) {
            switch ($condition[1]) {
                case 'IS NULL':
                    $query->whereNull($condition[0]);
                    break;
                case 'IS NOT NULL':
                    $query->whereNotNull($condition[0]);
                    break;
                case 'LIKE':
                    $query->where($condition[0], $condition[1], "%{$condition[2]}%");
                    break;
                case 'NOT LIKE':
                    $query->where($condition[0], $condition[1], "%{$condition[2]}%");
                    break;
                case 'IN':
                    try {
                        $array = explode(',', "{$condition[2]}");
                        $query->whereIn($condition[0], $array);
                    } catch (Throwable $th) {
                    }
                    break;
                case 'NOT IN':
                    try {
                        $array = explode(',', "{$condition[2]}");
                        $query->whereNotIn($condition[0], $array);
                    } catch (Throwable $th) {
                    }
                    break;
                default:
                    $query->where($condition[0], $condition[1], "{$condition[2]}");
                    break;
            }

        }
        return $query;
    }

    /**
     * Aplica ordenamiento a una consulta Eloquent si se proporcionan parámetros de orden.
     *
     * @param Builder $query La consulta sobre la cual se aplicará el ordenamiento.
     * @param array|null $sort Un arreglo con las claves:
     *     - 'column' (string): El nombre de la columna para ordenar.
     *     - 'desc' (bool, opcional): Si está presente y es verdadero, aplica orden descendente; de lo contrario, ascendente.
     * @return Builder La consulta modificada con el orden aplicado.
     */
    protected function applySorting(Builder $query, $sort = null)
    {
        if (is_string(($sort))) {
            $sort = json_decode($sort);
        }
        // Verificar si hay un parámetro de ordenamiento válido.
        if (!$sort || !$sort->column) {
            return $query; // Sin ordenamiento, regresar la consulta original.
        }

        // Obtener las columnas disponibles en la tabla.
        $columns = Schema::getColumnListing($query->getModel()->getTable());

        // Verificar si la columna proporcionada es válida.
        if (!in_array($sort->column, $columns, true)) {
            return $query; // Columna no válida, regresar la consulta original.
        }

        // Determinar el orden (ascendente por defecto).
        $direction = isset($sort->desc) && $sort->desc ? 'desc' : 'asc';

        // Aplicar el ordenamiento a la consulta.
        /**
         * @var Builder
         */
        $query = $query->orderBy($sort->column, $direction);

        return $query;
    }

    /**
     * Aplica una búsqueda genérica en las columnas de la tabla principal y en las relaciones definidas.
     *
     * Esta función toma un término de búsqueda proporcionado en la solicitud y lo aplica a todas las columnas
     * de la tabla principal asociada al modelo, así como a las columnas de las relaciones especificadas
     * en `$this->mainRelations`. Las búsquedas utilizan la cláusula SQL `LIKE` para encontrar coincidencias parciales.
     *
     * ### Ejemplo de uso:
     * ```php
     * $query = User::query();
     * $query = $this->applySearch($query, $request);
     * $users = $query->get();
     * ```
     *
     * @param  Builder $query $query  El objeto de consulta base donde se aplicará la búsqueda.
     * @param \Illuminate\Http\Request           $request La solicitud HTTP que contiene el término de búsqueda en el campo `search`.
     *
     * @return \Illuminate\Database\Query\Builder Devuelve el objeto de consulta modificado con las condiciones de búsqueda aplicadas.
     *
     * @throws \Exception Si ocurre un error durante la obtención de las columnas de las relaciones.
     */
    public function applySearch(Builder $query, Request $request): Builder
    {
        // Validamos el término de búsqueda
        $search = trim($request->search ?? '');
        if (empty($search) || !is_string($search)) {
            return $query;
        }
        $relations = $this->getQueryRelations($request);

        $excludedColumns = $this->excludedColumnsInSearch;
        // Obtenemos las columnas de la tabla principal
        $columns = Schema::getColumnListing($query->getModel()->getTable());
        $columns = array_diff($columns, $excludedColumns);

        // Creamos un grupo de condiciones para aplicar el "orWhere"
        $query->where(function ($query) use ($columns, $search) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', '%' . $search . '%');
            }
        });

        // Aplicamos las búsquedas en las relaciones
        foreach ($relations as $relation) {
            $query->orWhereHas($relation, function ($subQuery) use ($search, $excludedColumns) {
                // Obtenemos las columnas de la relación
                $subColumns = Schema::getColumnListing($subQuery->getModel()->getTable());
                $subColumns = array_diff($subColumns, $excludedColumns);

                $subQuery->where(function ($subQuery) use ($subColumns, $search) {
                    foreach ($subColumns as $subColumn) {
                        $subQuery->orWhere($subColumn, 'LIKE', '%' . $search . '%');
                    }
                });
            });
        }

        return $query;
    }

    public function applyAdvancedSearch(Builder $query, Request $request)
    {
        $busqueda_avanzada = $request->input('busqueda_avanzada');
        if (is_string($busqueda_avanzada)) {
            $busqueda_avanzada = json_decode($busqueda_avanzada);
        }

        $verifier = new Verifiers();
        if (!$busqueda_avanzada) {
            return $query;
        }
        if ($verifier->arrayType($busqueda_avanzada) !== 'sequential') {
            return $query;
        }
        foreach ($busqueda_avanzada as $filtro) {
            if (!property_exists($filtro, 'operator')) {
                $filtro->operator = '>=';
            }
            if (!property_exists($filtro, 'count') || $filtro->count < 0) {
                $filtro->count = 1;
            }
            if (!property_exists($filtro, 'opWhere') || !$filtro->opWhere) {
                $filtro->opWhere = 'AND';
            }

            if (!in_array($filtro->relation, $this->mainRelations) && $filtro->relation != 'self') {
                return $query;
            }
            if (!is_array($filtro->conditionals)) {
                return $query;
            }
            //revisamos si existe relacion en la que hacer la busqueda
            if ($filtro->relation !== 'self') {
                //revisamos si mandaron operador OR o AND, si no mandaron nada sera AND
                if ($filtro->opWhere && $filtro->opWhere == 'AND') {
                    $query->whereHas($filtro->relation, function ($subQuery) use ($filtro) {
                        $this->applyAdvancedSubQuery($subQuery, $filtro);
                    }, $filtro->operator, $filtro->count);
                }
                if ($filtro->opWhere && $filtro->opWhere == 'OR') {
                    $query->orWhereHas($filtro->relation, function ($subQuery) use ($filtro) {
                        $this->applyAdvancedSubQuery($subQuery, $filtro);
                    }, $filtro->operator, $filtro->count);
                }
            }

            //revisamos si no mandaron relacion aplicaremos la busqueda avanzada dentro del mismo modelo
            if ($filtro->relation == 'self') {
                $this->applyAdvancedQuery($query, $filtro);
            }
        }
        return $query;
    }

    private function applyAdvancedQuery($query, $filtro)
    {
        $columns = Schema::getColumnListing($query->getModel()->getTable());

        $firstAdded = false;
        foreach ($filtro->conditionals as $conditional) {
            // Verificar si la columna existe en la tabla
            if (!in_array($conditional[0], $columns)) {
                continue;
            }

            // Aplicar la condición (asumiendo formato: ['column', 'operator', 'value'])
            if ($this->isConditionalValid($conditional)) {
                switch ($filtro->opWhere ?? 'AND') {
                    case 'AND':
                        $query = $this->applyConditional($conditional, $query);
                        break;
                    case 'OR':
                        $firstAdded ?
                            $query = $this->applyOrConditional($conditional, $query) :
                            $query = $this->applyConditional($conditional, $query);
                        break;
                    default:
                        $query = $this->applyConditional($conditional, $query);
                }
                $firstAdded = true;
            }
        }
    }

    private function applyAdvancedSubQuery($subQuery, $filtro)
    {
        $columns = Schema::getColumnListing($subQuery->getModel()->getTable());

        if ($filtro->conditionals) {
            $firstAdded = false;
            foreach ($filtro->conditionals as $conditional) {
                // Verificar si la columna existe en la tabla
                if (!in_array($conditional[0], $columns)) {
                    continue;
                }

                // Aplicar la condición (asumiendo formato: ['column', 'operator', 'value'])
                if ($this->isConditionalValid($conditional)) {
                    $firstAdded ?
                        $subQuery = $this->applyOrConditional($conditional, $subQuery) :
                        $subQuery = $this->applyConditional($conditional, $subQuery);
                    $firstAdded = true;
                }
            }
        }

        if ($filtro->andConditionals) {
            $firstAdded = false;
            foreach ($filtro->andConditionals as $conditional) {
                // Verificar si la columna existe en la tabla
                if (!in_array($conditional[0], $columns)) {
                    continue;
                }
                // Aplicar la condición (asumiendo formato: ['column', 'operator', 'value'])
                if ($this->isConditionalValid($conditional)) {
                    $subQuery = $this->applyConditional($conditional, $subQuery);
                    $firstAdded = true;
                }
            }
        }
    }

    /**
     * Checks if one conditional is valid or not
     * @param array $conditional [column, operator, value]
     * @return bool
     */
    private function isConditionalValid(array $conditional)
    {
        $operaters = ['=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', '!=', '<>', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'IS NULL', 'IS NOT NULL'];
        if (count($conditional) !== 3) {
            return false;
        }
        if (
            !is_string($conditional[0]) ||
            !is_string($conditional[1]) ||
            (!is_string($conditional[2]) && !is_numeric($conditional[2]) && !is_null($conditional[2]))
        ) {
            return false;
        }
        if (!in_array($conditional[1], $operaters)) {
            return false;
        }

        return true;
    }

    private function applyConditional($conditional, Builder $query)
    {
        switch ($conditional[1]) {
            case 'IS NULL':
                $query->WhereNull($conditional[0]);
                break;
            case 'IS NOT NULL':
                $query->WhereNotNull($conditional[0]);
                break;
            case 'LIKE':
                $query->Where($conditional[0], $conditional[1], "%{$conditional[2]}%");
                break;
            case 'NOT LIKE':
                $query->Where($conditional[0], $conditional[1], "%{$conditional[2]}%");
                break;
            case 'IN':
                try {
                    $array = explode(',', "{$conditional[2]}");
                    $query->whereIn($conditional[0], $array);
                } catch (Throwable $th) {
                }
                break;
            case 'NOT IN':
                try {
                    $array = explode(',', "{$conditional[2]}");
                    $query->WhereNotIn($conditional[0], $array);
                } catch (Throwable $th) {
                }
                break;
            default:
                $query->Where($conditional[0], $conditional[1], $conditional[2]);
                break;
        }
        return $query;
    }

    /**
     * Apply one conditional to query if operator not found do nothing, always return query
     * @param mixed $conditional [column, operator, value]
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Builder
     */
    private function applyOrConditional($conditional, Builder $query)
    {
        switch ($conditional[1]) {
            case 'IS NULL':
                $query->orWhereNull($conditional[0]);
                break;
            case 'IS NOT NULL':
                $query->orWhereNotNull($conditional[0]);
                break;
            case 'LIKE':
                $query->orWhere($conditional[0], $conditional[1], "%{$conditional[2]}%");
                break;
            case 'NOT LIKE':
                $query->orWhere($conditional[0], $conditional[1], "%{$conditional[2]}%");
                break;
            case 'IN':
                try {
                    $array = explode(',', "{$conditional[2]}");
                    $query->whereIn($conditional[0], $array);
                } catch (Throwable $th) {
                }
                break;
            case 'NOT IN':
                try {
                    $array = explode(',', "{$conditional[2]}");
                    $query->orWhereNotIn($conditional[0], $array);
                } catch (Throwable $th) {
                }
                break;
            default:
                $query->orWhere($conditional[0], $conditional[1], $conditional[2]);
                break;
        }
        return $query;
    }

    protected function isDate(string $date): bool
    {
        try {
            new \DateTime($date);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Hace una sola insercion y regresa el objeto creado
     * @param array $data
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    protected function store_single_input(array $data)
    {
        $r = request();
        // Validación para un solo objeto con los datos originales
        $validator = Validator::make($data, $this->rules);
        if ($validator->fails()) {
            return $this->response_error($this->fails_string($validator), 422, $validator->errors());
        }
        if (($this->requireFile || $r->hasFile($this->fileFieldName)) && (!$this->folderName || !$this->pathColumn)) {
            abort(500, 'Path o nombre de carpeta no definido para documentos');
        }
        if ($this->requireFile && !$r->hasFile($this->fileFieldName)) {
            return $this->response_error('El documento es obligatorio.', 422, );
        }

        // verificación sobre los datos a insertar
        $respVal = $this->storeValidacionPreInsercion($data, true);
        if ($respVal['status'] === false) {
            return $this->response_error($respVal['message'], $respVal['code']);
        } elseif (array_key_exists('data', $respVal)) {
            $data = $respVal['data'];
        }

        // Extraer solo los datos que corresponden a columnas de la tabla DESPUÉS de la validación
        $data = $this->extractModelData($data);
        $data = $this->verif->agregarValoresDefault($data, $this->valoresDefault);
        $insertion = null;

        // Handle file uploads
        if ($r->hasFile($this->fileFieldName)) {
            $files = $r->file($this->fileFieldName);

            if ($this->allowMultipleFiles && is_array($files)) {
                // Handle multiple files
                $filePaths = [];
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $folder = $this->folderName;
                        $path = "documents/$folder";
                        $filename = $file->hashName();
                        $file->storeAs($path, $filename);
                        $filePaths[] = "$path/$filename";
                    }
                }
                $data[$this->pathColumn] = json_encode($filePaths);
            } elseif (!is_array($files) && $files->isValid()) {
                // Handle single file
                $folder = $this->folderName;
                $path = "documents/$folder";
                $filename = $files->hashName();
                $data[$this->pathColumn] = "$path/$filename";
                $files->storeAs($path, $filename);
            }
        }

        $insertion = $this->model::create($data)->toArray();
        try {
        } catch (Throwable $th) {
            return $this->response_error($th->getMessage(), 500);
        }

        // Creación del objeto si la validación pasa
        return $this->storePostInsercionAcciones($insertion, true);
    }

    /**
     * Extención d ela función store, se activa cuando se detecta que se insertarán multiples registros
     * @param Request $r
     * @return JsonResponse|mixed
     */
    public function store_multiple_inputs($data, $userData)
    {
        $errors = [];
        $cleanData = [];
        //Verificamos que user data sea un array asociativo
        if (!is_array($userData) || !array_key_exists('id', $userData)) {
            return $this->response_error('Problema con los datos de sesión de usuario', 422);
        }
        $id = $userData['id'];
        // Validamos cada objeto individualmente
        foreach ($data as $index => $rawItem) {
            // Validar con los datos originales ANTES de extractModelData
            $validator = Validator::make($rawItem, $this->rules);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors();
            } else {
                // Extraer solo los datos que corresponden a columnas DESPUÉS de la validación
                $item = $this->extractModelData($rawItem);
                $item = $this->verif->agregarValoresDefault($item, $this->valoresDefault);
                $cleanData[] = $item;
            }
        }

        // Si hay errores, los retornamos con código 422
        if (!empty($errors)) {
            $errors_string = "Se encontraron " . count($errors) . " registros con errores en los datos recibidos";
            return $this->response_error($errors_string, 422, $errors);
        }

        // verificación sobre los datos a insertar
        $respVal = $this->storeValidacionPreInsercion($cleanData, false);
        if ($respVal['status'] === false) {
            return $this->response_error($respVal['message'], $respVal['code']);
        } elseif (array_key_exists('data', $respVal)) {
            $cleanData = $respVal['data'];
        }

        try {
            $this->model::insert($cleanData);
        } catch (Throwable $th) {
            return $this->response_error("Ocurrió un error al insertar los registros, si el problema persisste comuniquelo al equipo de desarrollo:" . $th->getMessage(), 500);
        }

        //Obtenemos los datos insertados
        $inserciones = $this->model::orderByDesc('id')->take(count($cleanData))->get()->toArray();

        return $this->storePostInsercionAcciones($inserciones, false);
    }

    /**
     * convierte los errores del validator en una cadena
     * @param mixed $validator
     * @return string
     */
    protected function fails_string($validator)
    {
        return "Se encontraron uno o más errores: " . implode(' ', $validator->errors()->all());
    }

    /**
     * funcion axuliar para regresar una respuesta de error estandar
     * @param string $message
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    protected function response_error(string $message, int $status_code = 500, $data = null)
    {
        return response()->json(['status' => false, 'message' => $message, "errors" => $data], $status_code);
    }

    /**
     * Verifica que las columnas existan en la tablas del modelo, devuelve en el array solo la información que si existe
     * @param array $arrayArgs datos recibidos desde el cliente
     * @return array
     */
    protected function extractModelData($arrayArgs, $ignore = [])
    {
        if ($this->verif->arrayType($arrayArgs) !== 'associative') {
            return [];
        }

        $validArgs = [];
        foreach ($arrayArgs as $column => $value) {
            //Verificamos que la columna exista en la tabla que consultaremos
            if (Schema::hasColumn($this->model->getTable(), $column)) {
                $columnType = Schema::getColumnType($this->model->getTable(), $column);
                if ($columnType === 'json') {
                    $value = json_encode($value);
                }
                $validArgs[$column] = $value;
            }
        }
        if ($this->useUsuarioId && auth()->user()) {
            $validArgs[$this->createdByColumn] = auth()->user()->id;
        }

        if (count($this->ignoreArgs) === 0) {
            return $validArgs;
        }

        //Guardamos en noIgnoreArgs los elementos que no se deben ignorar, descartamos el resto
        $noIgnoreArgs = [];
        foreach ($validArgs as $key => $value) {
            if (in_array($key, $this->ignoreArgs)) {
                continue;
            }
            $noIgnoreArgs[$key] = $value;

        }

        return $noIgnoreArgs;
    }

    /**
     * Obtiene las columnas solicitadas en el request
     * @param Request $r
     * @return array
     */
    public function getColumns(): array
    {
        $r = request();
        $columns = ['*'];
        //en el fron lo mando como string, pero con el decode se convierte a json otra vez, solo si se manda string
        if (is_string($r->input('columns'))) {
            try {
                $columns = json_decode($r->input('columns'));
            } catch (Throwable $th) {
                return $columns;
            }
        }

        //Si las relaciones no son un array secuencial entonces no se incluye ninguna realción
        if ($this->verif->arrayType($columns) !== 'sequential') {
            return ['*'];
        }
        return $columns;
    }

    /**
     * Obtiene las relaciones que se solicitan en la consulta, si no se solicitan se devuelven las relaciones principales
     * @param Request $r
     * @return array
     */
    public function getQueryRelations(Request $r)
    {
        //en el fron lo mando como string, pero con el decode se convierte a json otra vez, solo si se manda string
        if (is_string($r->input('relations'))) {
            try {
                $relations = json_decode($r->input('relations'));
            } catch (Throwable $th) {
                $relations = null;
            }
        } else {
            $relations = $r->input('relations');
        }

        //Si se evia la relación vacía, se devuelven todas las relaciones diponibles
        if (is_array($relations) && count($relations) === 1 && $relations[0] === '*') {
            return $this->mainRelations;
        }

        //Si las relaciones no son un array secuencial entonces no se incluye ninguna realción
        if ($this->verif->arrayType($relations) !== 'sequential') {
            return [];
        }
        return $this->validRelations($relations);
    }

    private function validRelations(array $relations)
    {
        // Convertir mainRelations a un conjunto asociativo para búsqueda rápida
        $allowedRelations = array_flip($this->mainRelations);
        $relationsMatched = [];
        foreach ($relations as $relation) {
            if (!is_string($relation)) {
                continue;
            }

            // Separar por : para obtener solo el nombre de la relación
            $relationName = trim($relation);

            // Verificar que la relación esté permitida
            if (isset($allowedRelations[$relationName])) {
                $relationsMatched[] = $relationName;
            }
        }
        return $relationsMatched;
    }

    //Cada relacion puede tener una subquery, esta funcion adjunta esas subquerys a la relacion solicitada
    public function subquerysInRelations($queryRelation, $relationName, $relations)
    {
        $len = count($relations);
        for ($i = 0; $i < $len; $i++) {
            if ($relations[$i]['relation'] === $relationName) {
                $relations[$i]['relation'] = [$relationName => $queryRelation];
            }
        }
        return $relations;
    }

    /**
     * Funciones de validación personalizadas para diferentes modelos, es necesario reeescribirla en el controlador del modelo si se requiee usar
     * @param Request $r request de la solicitud
     * @param mixed $data datos verificados por el arrray de verificaciones del supercontroller
     * @return array
     */
    public function storeValidacionExtra(Request $r, $data)
    {
        return ["status" => true, "message" => "", "code" => 200];
    }

    /**
     * Esta función se ejecuta justo despues de realizar la insercción de un POST, recibe los datos insertados y en caso de no requerir
     * regresa un array con el resultado del post-proceso que se utilizará como respuesta
     * @param array $datos_insertados
     * @return \Illuminate\Http\JsonResponse [status => bool, message => string, code => int, data => mixed]
     */
    public function storePostInsercionAcciones(array $datos_insertados, bool $insercion_unica)
    {
        if ($insercion_unica) {
            return response()->json(['status' => true, 'message' => 'El registro fue creado con éxito.', 'data' => $datos_insertados]);
        }
        return response()->json(data: ['status' => true, 'message' => 'Registros creados con éxito.'], status: 200);
    }

    /**
     * Esta función se ejecuta justo antes de la inserción de uno o más registros. Si no se requiere validación extra, regresa un array con status true.
     * Cuando esta función se ejecuta ya se verificaron previamente todos los datos a ingresar
     * @param mixed $data
     * @return array
     */
    public function storeValidacionPreInsercion(array $data, bool $insercion_unica)
    {
        return ["status" => true, "message" => "", "code" => 200, 'data' => $data];
    }

    public function updatePostAccion($refrehedData, $oldData)
    {
        return response()->json(['status' => true, 'message' => "El registro fue actualizado."], status: 200);
    }

    /**
     * Esta función se llama justo antes de ejecutar la actualización de un registro
     * @return array [status => bool, message => string, code => int, data => mixed]
     */
    public function verificacionPreActualizacion($id, $updateData)
    {
        return ["status" => true, "message" => "", "code" => 200, 'data' => $updateData];

    }

    /**
     * Realiza una verificacion previa a la cancelación de un registro, si la verificación falla regresa un array con el mensaje de error
     * @param int $id
     * @return array [status => bool, message => string, code => int]
     */
    public function revisionPreEliminacion($id, $registro)
    {
        return ["status" => true, "message" => "", "code" => 200];

    }

    /**
     * Realiza una verificacion tras la cancelación de un registro, la respuesta es la que se envia directamente al cliente
     * @param object $registro registro eliminado o recien restaurao
     * @param string $message mensaje a enviar al cliente
     * @return JsonResponse
     */
    public function revisionPostEliminacion($registro, $message)
    {
        return response()->json([
            'status' => true,
            'message' => $message
        ], 200);

    }
}
