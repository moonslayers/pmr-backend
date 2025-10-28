<?php
namespace App\Services;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

//Esta clase realiza diferentes tareas de verificación para las diferentes acciones que se realizan previas a la inserción
//o recepccion de información externa
class Verifiers
{
    private $strings;
    public function __construct()
    {
    }

    /**
     * Verifica si una cadenaa recibida es un correo electronico valido, si se le pasa un dominio verifica que sea el mismo
     * @param mixed $mail variable a verificar
     * @param mixed $domain dominio a verificar, valor opcional
     * @return bool true si es un correo valido, false si no lo es
     */
    public function validEmail($mail, $domain = null)
    {
        if (!$mail || !is_string($mail))
            return false;
        if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $mail))
            return false;

        if ($domain) {
            $index = $this->searchIndex($mail, '@');
            if ($index == -1)
                return false;
            if (substr($mail, $index + 1) != $domain)
                return false;
        }
        return true;
    }

    public function searchIndex($string, $single)
    {
        if (!is_string($string) || !is_string($single) || strlen($single) != 1)
            return -1;

        $len = strlen($string);
        for ($i = 0; $i < $len; $i++)
            if (substr($string, $i, 1) == $single)
                return $i;
        return -1;
    }

    /**
     * Verifica si una cadena es un RFC de persona fisica (en México) valido
     * @param mixed $rfc cadena a verificar
     * @return bool true si es un rfc valido, false si no lo es
     */
    public function RFC_persona_fisica_mx($rfc)
    {
        if (!$this->string($rfc, 1, 20))
            return false;
        if (!preg_match("/^[A-Za-z]{4}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Za-z0-9]{2}([A-Za-z0-9])?$/", $rfc))
            return false;
        return true;
    }

    /**
     * Verifica si una cadena es un RFC de persona moral (en México) valido
     * @param mixed $rfc cadena a verificar
     * @return bool true si es un rfc valido, false si no lo es
     */
    public function RFC_persona_moral_mx($rfc)
    {
        if (!$this->string($rfc, 1, 20))
            return false;
        if (!preg_match("/^[A-Za-z]{3}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Za-z0-9]{2}([A-Za-z0-9])?$/", $rfc))
            return false;
        return true;
    }

    //Verifica si una cadena es una estructura de número telefonico, se le puede pasar el numero total de digitos, maximo de digitos o una expresion
    //regular con el fromato que debe tener para hacer la verificación
    public function phone($phone = null, $digits = null, $maxDigits = null, $regEx = null)
    {
        if (!$phone || !is_string($phone))
            return false;
        if ($digits && is_numeric($digits) && intval($digits) != strlen($phone))
            return false;
        if ($maxDigits && is_numeric($maxDigits) && intval($maxDigits) < strlen($phone))
            return false;
        if ($regEx && !preg_match($regEx, $phone))
            return false;
        return true;
    }

    /**
     * Verifica si un valor recibido es una variable de tipo string además puedes verificar la longitud de la cadena, la longitud mínima y máxima
     * @param mixed $value valor a verificar
     * @param mixed $len valor opcional, longitud exacta que debe tener la cadena
     * @param mixed $min valor opcional, longitud mínima que debe tener la cadena
     * @param mixed $max valor opcional, longitud máxima que debe tener la cadena
     * @param mixed $normalice valor opcional, booleano que indica si se debe normaliza el texto
     * @return bool|string
     */
    public function string($value, $min = null, $max = null, $normalice = false)
    {
        if ($value === null || !is_string($value)) {
            return false;
        }
        $valueLen = strlen(trim($value));

        if (
            ($min && $valueLen < $min) ||
            ($max && $valueLen > $max)
        ) {
            return false;
        }

        return $normalice ? $this->text_normalice($value) : $value;
    }

    /**
     * Verifica si un valor recibido es un numero entero, además puedes verificar si el valor se encuentra en un rango específico
     * @param mixed $value valor a verificar
     * @param mixed $min valor opcional, valor mínimo que puede tener el entero
     * @param mixed $max valor opcional, valor máximo que puede tener el entero
     * @return bool|int false si no es un entero o no se encuentra en el rango, el valor entero si es correcto
     */
    public function int($value = null, $min = null, $max = null)
    {
        if (
            !isset($value) || !is_numeric($value) ||
            ($min && intval($value) < $min) ||
            ($max && intval($value) > $max)
        )
            return false;
        return intval($value);
    }

    /**
     * Verifica si un valor recibido es un numero flotante, además puedes verificar si el valor se encuentra en un rango específico
     * @param mixed $value valor a verificar
     * @param mixed $min valor opcional, valor mínimo que puede tener el flotante
     * @param mixed $max valor opcional, valor máximo que puede tener el flotante
     * @return bool|float false si no es un flotante o no se encuentra en el rango, el valor flotante si es correcto
     */
    public function float($value = null, $min = null, $max = null)
    {
        if (
            !isset($value) ||
            !is_numeric($value) ||
            ($min && floatval($value) < $min) ||
            ($max && floatval($value) > $max)
        )
            return false;
        return floatval($value);
    }


    /**
     * Verifica si un array es un array de enteros de id, es decir que sean enteros mayor o igual a 1.
     * Devuelve un array con los valores validos, si no hay valores validos devuelve false
     * @param mixed $value
     * @return bool|int[]
     */
    public function check_array_ids($value)
    {
        $revised = [];
        if (!isset($value) || !is_array($value))
            return false;

        foreach ($value as $p) {
            if (!is_numeric($p))
                continue;
            if (($id = intval($p)) <= 0)
                continue;
            $revised[] = $id;
        }
        return count($revised) > 0 ? $revised : false;
    }

    //Atma un query usando las palabras clave y klos nombres de las columnas, amabas deben ser strings cada valor separados por espacios
    public function generalSearh($busqueda, $columnas)
    {
        $busqueda = $this->extraerBloquesUnicos($busqueda);
        $columnas = $this->extraerBloquesUnicos($columnas);
        $conditional = "";
        if (count($busqueda) == 0 || count($columnas) == 0)
            return '';

        foreach ($busqueda as $key) {

            $conc = ' AND LOWER(CONCAT_WS (';
            foreach ($columnas as $col) {
                $conc = "$conc $col,' ',";
            }
            $len = strlen($conc);
            $conditional = $conditional . substr($conc, 0, $len - 5) . ") COLLATE utf8mb4_unicode_ci ) LIKE LOWER('%" . $key . "%') ";
        }

        return substr($conditional, 4);
    }

    //Extrae los bloques de letras dentro de una cadena en una cadena con valores no repetidos
    public function extraerBloquesUnicos($cadena)
    {
        if (!$this->string($cadena, null, 1, null))
            return [];

        $data = [];
        $len = strlen($cadena);
        $palabra = "";
        for ($i = 0; $i < $len; $i++) {
            $letra = substr($cadena, $i, 1);
            if ($i === $len - 1) {
                if ($letra !== ' ')
                    $palabra = $palabra . $letra;
                if ($palabra !== '')
                    $data[] = "" . $palabra;
            } else if ($letra === ' ') {
                if (strlen($palabra) > 0) {
                    $data[] = $palabra;
                    $palabra = '';
                }
            } else {
                $palabra = $palabra . '' . $letra;
            }
        }
        $data = array_unique($data);

        return $data;
    }

    //Extrae los bloques de letras dentro de una cadena en una cadena con valores no repetidos
    public function extraerBloques($cadena)
    {
        if (!is_string($cadena))
            return "";

        $data = [];
        $len = strlen($cadena);
        $palabra = "";
        for ($i = 0; $i < $len; $i++) {
            $letra = substr($cadena, $i, 1);
            if ($i === $len - 1) {
                if ($letra !== ' ')
                    $palabra = $palabra . $letra;
                if ($palabra !== '')
                    $data[] = "" . $palabra;
            } else if ($letra === ' ') {
                if (strlen($palabra) > 0) {
                    $data[] = $palabra;
                    $palabra = '';
                }
            } else {
                $palabra = $palabra . '' . $letra;
            }
        }

        if (count($data) === 0)
            return "";
        $clean = implode(" ", $data);
        return $clean;
    }


    /**
     * recibe una fecha y retona una instancia de Carbon, si la fecha no es válida devuelve el valor por defecto
     * @param string $fecha
     * @param mixed $default
     * @return Carbon|null
     */
    public function fecha($fecha, $default = null)
    {
        if (!$fecha || !is_string($fecha))
            return null;
        try {
            $fecha = Carbon::parse($fecha);
            return $fecha;
        } catch (\Throwable $th) {
            return $default;
        }
    }

    /**
     * recibe 2 fechas, devuelve un array con la fecha mas reciente al inicio y la fecha mas antigua al final, en caso de que las fechas sean iguales devuelve un array con las dos fechas
     * Si hay algun eror en las fecha devuelve la fcha del día
     * @param mixed $fecha1
     * @param mixed $fecha2
     * @return array
     */
    public function ordenarFechas($fecha1, $fecha2)
    {
        $defaultDate = Carbon::now();
        $fecha1 = $this->fecha($fecha1, $defaultDate);
        $fecha2 = $this->fecha($fecha2, $defaultDate);
        if ($fecha1->greaterThan($fecha2))
            return [$fecha2, $fecha1];
        return [$fecha1, $fecha2];
    }

    function text_normalice($cadena, $upper = false)
    {
        // Mapa de caracteres especiales a alfanuméricos
        $map = array(
            'á' => 'a',
            'Á' => 'A',
            'é' => 'e',
            'É' => 'E',
            'í' => 'i',
            'Í' => 'I',
            'ó' => 'o',
            'Ó' => 'O',
            'ú' => 'u',
            'Ú' => 'U',
            //'ñ' => 'n',
            //'Ñ' => 'N',
            'ä' => 'a',
            'Ä' => 'A',
            'ë' => 'e',
            'Ë' => 'E',
            'ï' => 'i',
            'Ï' => 'I',
            'ö' => 'o',
            'Ö' => 'O',
            'ü' => 'u',
            'Ü' => 'U',
            'ç' => 'c',
            'Ç' => 'C',
            'ß' => 'ss',
            // Añade más reemplazos según sea necesario
        );

        // Reemplazar caracteres acentuados y especiales
        $cadena = strtr($cadena, $map);

        // Eliminar espacios dobles
        $cadena = preg_replace('/\s+/', ' ', $cadena);

        // Eliminar caracteres no permitidos (excepto letras, números, guiones, espacios y ñ/Ñ)
        $cadena = preg_replace('/[^a-zA-Z0-9\- ñÑ]/', '', $cadena);

        // Convertir a minúsculas
        $cadena = $upper ? strtoupper($cadena) : strtolower($cadena);

        return trim($cadena);
    }

    /**
     * Recibe un array asociativo y uno de strings, verifica que el array tenga las llaves del array de strings
     * @param mixed $variable array a verificar
     * @param mixed $keys array de strings con las llaves que debe tener el array
     * @return bool
     */
    public function checkArrayKeys($variable, $keys)
    {
        if (!is_array($variable) || !is_array($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            if (!is_string($key))
                return false;
            if (!array_key_exists($key, $variable))
                return false;
        }
        return true;
    }

    /**
     * Verifica si una variable es un array, en caso de serlo verifica si es un array asociativo o secuencial, devuelve el tipo de array que es o 
     * 'invalid' si no es un array.
     * @param mixed $value
     * @return string 'associative', 'sequential', 'invalid'
     */
    public function arrayType($data = null)
    {
        $type = "";

        if (!$data || !is_array($data)) {
            $type = 'invalid';
        } else if (array_keys($data) !== range(0, count($data) - 1)) {
            $type = 'associative';
        } else {
            $type = 'sequential';
        }
        return $type;
    }

    /**
     * Verifica si un valor es un array de enteros con valores mayores a cero, si no lo es devuelve false, si lo es devuelve el array con los valores enteros
     * @param mixed $array
     * @return bool|int[]
     */
    public function arrayIds($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $ids = [];
        foreach ($array as $id) {
            if (($id = $this->int($id, 1)) === false) {
                continue;
            }
            $ids[] = $id;
        }
        if (!empty($ids)) {
            return false;
        }
        return $ids;
    }

    public function paginador(Request $r)
    {
        $paginador = new \stdClass();
        $paginador->page = $this->int($r->input('page'), 1, 10000) ? intval($r->input('page')) : 1;
        $paginador->per_page = $this->int($r->input('per_page'), 1, 1000000) ? intval($r->input('per_page')) : 10;
        $paginador->offset = ($paginador->page - 1) * $paginador->per_page;
        return $paginador;
    }

    /**
     * Recibe dos arrays, uno para revisar y otro con una lista de strings que son las keys que se deben verificar.
     * Si todas las keys existen se devuelve el status true, si no se devuelve en el status false, 
     * en message se describe que causo el error. cuando la respuesta es true se envia un tercer argummnto con los datos encontrados
     * @param array $array array a revisar
     * @param array $keys lista de keys a verificar
     * @param boolean $cleanArray si se desea limpiar el array de valores que no estan en la lista de keys
     * @return array ['status' => boolean, 'message' => string, 'data' => array]
     */
    public function checkArrayContainsKeys($array, $keys, $compleatar_faltantes = false)
    {
        if (!is_array($array) || !is_array($keys)) {
            return ["status" => false, "message" => "El tipo de dato no es un array"];
            ;
        }

        $len = count($keys);
        $arrayClean = [];

        for ($i = 0; $i < $len; $i++) {
            if (!array_key_exists($keys[$i], $array)) {
                return ["status" => false, "message" => "No se encontró el dato " . str_replace('_', ' ', $keys[$i])];
            }
            $key = $keys[$i];
            $value = $array[$key];
            $arrayClean[$key] = $value;
        }

        if (count($arrayClean) === 0) {
            return ["status" => false, "message" => "No se encontraron datos"];
        }
        return ["status" => true, "message" => "datos encontrados", "data" => $arrayClean];
    }

    /**
     * Recibe una variable, verifica ue se aun array, si lo es verifica que los valores sean enteros mayores a 0.
     * @param mixed $arr
     * @return array El array es vacio a menos que sea un array valido con valores validos, si hay valoes validos y otros no se devuelven solo los validos
     */
    public function getArrayIds($arr)
    {
        if (!is_array($arr))
            return [];
        $ids = [];
        foreach ($arr as $a) {
            if (is_numeric($a) && intval($a) > 0) {
                $ids[] = intval($a);
            }
        }
        return $ids;
    }

    /**
     * Verifica si una cadena no vacía contiene la hora en formato 'HH:mm', 'HH:mm:ss', 'HH:mm:ss a. m.' o 'HH:mm:ss p. m.'
     * @param mixed $time cadena a verificar
     * @return string|null la hora en formato 'HH:mm' si es válida, null si no lo es
     */
    public function validTime($time)
    {
        if (!$time || !is_string($time)) {
            return null;
        }

        // Formato 24 horas
        if (preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/", $time)) {
            return substr($time, 0, 5);
        }

        // Formato 12 horas con a. m. o p. m.
        if (preg_match("/^(1[0-2]|0?[1-9]):[0-5][0-9](?::[0-5][0-9])? (a\. m\.|p\. m\.)$/i", $time, $matches)) {
            $hour = intval($matches[1]);
            $minute = substr($matches[0], 3, 2);
            $period = strtolower($matches[2]);

            if ($period == 'p. m.' && $hour != 12) {
                $hour += 12;
            } elseif ($period == 'a. m.' && $hour == 12) {
                $hour = 0;
            }
            return sprintf('%02d:%02d', $hour, $minute);
        }
        return null;
    }


    /**
     * Transforma una fecha del formato numérico que maneja Excel a una fecha en formato 'YYYY-MM-DD'
     * @param mixed $valorExcelOriginal Valor numérico de una celda de Excel que contiene una fecha en formato numérico
     * @param int $desfase Desfase en días para ajustar la fecha
     * @return string|null Fecha en formato 'YYYY-MM-DD' o null si el valor no es válido
     */
    function fechaNumericoExcelADateFromat($valorExcelOriginal, $desfase = 0)
    {
        if ($valorExcelOriginal === null || $valorExcelOriginal === '') {
            return null; // Manejar el caso de valor nulo
        }

        $valorExcel = 0;
        $desfaseEnDias = $desfase * 86400;

        try {
            $valorExcel = intval($valorExcelOriginal);
            if (!is_numeric($valorExcel))
                return null;
        } catch (Exception $e) {
            return null;
        }

        // Convertir a valor Unix Timestamp (segundos desde el 1 de enero de 1970)
        $unixTimestamp = ($valorExcel - 25569) * 86400; // 86400 segundos en un día
        $unixTimestamp += $desfaseEnDias;

        // Crear un objeto de fecha en PHP
        $fecha = date_create_from_format('U', $unixTimestamp); // Crear fecha desde Unix Timestamp

        // Obtener componentes de fecha (año, mes, día)
        $year = $fecha->format('Y');
        $month = str_pad($fecha->format('m'), 2, '0', STR_PAD_LEFT); // Añadir cero inicial si es necesario
        $day = str_pad($fecha->format('d'), 2, '0', STR_PAD_LEFT); // Añadir cero inicial si es necesario

        // Formatear la fecha como "YYYY-MM-DD"
        return "$year-$month-$day";
    }


    /**
     * Receibe un array y una llave, verifica si la llave existe en el array, si no existe o si su valor es nulo la agrega con el valor por defecto
     * @param mixed $array
     * @param mixed $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function sustituirOAgregarValor($array, $key, $defaultValue)
    {
        if (!is_array($array)) {
            return $array;
        }

        if (!array_key_exists($key, $array) || $array[$key] === null) {
            $array[$key] = $defaultValue;
        }
        return $array;
    }

    /**
     * Asigna a un array valores por default cuando no existen o son nulos
     * @param array $data registros a revisar
     * @param array $valores_dafault lista con reglas de agregado conde cada elemento tiene la foma de ['key' => 'valor por defecto']
     * @return array devuelv eel array con los valores por defecto agregados en caso se hannerse necesitado
     */
    public function agregarValoresDefault(array $data, array $valores_default): array
    {
        // Validación de tipos de entrada
        if (!is_array($data) || !is_array($valores_default)) {
            return $data;
        }

        // Filtrar solo las claves que existen en ambos arrays
        $claves_comunes = array_intersect_key($valores_default, $data);

        // Aplicar solo los valores default para claves existentes
        foreach ($claves_comunes as $key => $value) {
            // Solo aplica si el valor en data está vacío (null, '', etc.)
            if (empty($data[$key])) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Obtiene un valor seguro desdde un array, $key y $array pueden ser de cualquier tipo, si no se encuentra la llave en el array se devuelve null
     * @param mixed $key
     * @param mixed $array
     * @param mixed $default valor por defecto a devolver si no se encuentra la llave en el array
     */
    public function arrayValue($key, $array, $default = null)
    {
        if (!is_array($array)) {
            return $default;
        }
        if (!is_numeric($key) && !is_string($key)) {
            return $default;
        }

        if (!array_key_exists($key, $array)) {
            return $default;
        }
        return $array[$key];
    }

    /**
     * Elimina todos los valores e una cadena que no sean alfanumericos (incluidos espacios), si el valor no es una cadena devuelve una cadena vacia
     * @param mixed $texto
     * @param boolean $upper send true id return text in uppercase 
     * @param boolean $original enviar false si se desea aplicar la regla e upper o lowercase
     * @return string
     */
    public function limpiarTextoSoloAlfanumericos($texto, $original = true, $upper = false)
    {
        if (!$this->string($texto))
            return '';
        // Eliminar todos los caracteres que no sean alfanuméricos
        $textoLimpio = preg_replace('/[^a-zA-Z0-9]/', '', $texto);
        if ($original)
            return $textoLimpio;
        return $upper === true ? strtoupper($textoLimpio) : strtolower($textoLimpio);
    }

    /**
     * utiliza dos intancias de Carbon para determinar que abonos se realizaron entre esas fechas
     * @param Carbon $inicio
     * @param Carbon $fin
     * @param array $abonos
     * @return array con los abonos que se realizaron entre las fechas y los que no, con la forma ['usados' => [], 'no_usados' => []]
     */
    public function abonos_entre_2_fechas($inicio, $fin, $abonos)
    {
        $abonos_clasificados = [];
        $no_usados = [];
        foreach ($abonos as $abono) {
            if (Carbon::parse($abono['fecha'])->between($inicio, $fin)) {
                $abonos_clasificados[] = $abono;
            } else {
                $no_usados[] = $abono;
            }
        }
        return ['usados' => $abonos_clasificados, 'no_usados' => $no_usados];

    }

    /**
     * utiliza una instancia de Carbon para determinar que abonos se realizaron hasta esa fecha
     * @param Carbon $fecha
     * @param array $abonos
     * @return array con los abonos que se realizaron entre las fechas y los que no, con la forma ['usados' => [], 'no_usados' => []]
     */
    public function abonos_antes_de_fecha($fechaPago, $abonos)
    {
        $abonos_usados = [];
        $no_usados = [];
        $total_abonado = 0;
        foreach ($abonos as $abono) {
            $fechaAbono = Carbon::parse($abono['fecha'])->endOfDay();
            if ($fechaAbono->lessThanOrEqualTo($fechaPago)) {
                $abonos_usados[] = $abono;
                $total_abonado += $abono['monto'];
            } else {
                $no_usados[] = $abono;
            }
        }
        return ['usados' => $abonos_usados, 'no_usados' => $no_usados, 'monto' => $total_abonado];
    }

    protected function response_error(string $message, int $status_code = 500)
    {
        return response()->json(['status' => false, 'message' => $message], $status_code);
    }

    /**
     * convierte los errores del validator en una cadena
     * @param mixed $validator
     * @return string
     */
    public function fails_string($validator)
    {
        return "Se encontraron uno o más errores: " . implode(' ', $validator->errors()->all());
    }

    /**
     * Valida los registros que se encuentran dentro de la variable Request->data de acuerdo a la regla de errores
     * @param mixed $rules reglas en formato Validator de Laravel   
     * @return array{status: bool, message: string|array{status: bool}}
     */
    public function validarRegistros($rules)
    {

        $data = request()->input('data');
        if (!is_array($data)) {
            return ["status" => false, "message" => "No se encontraron registros"];
        }
        //Verificamos que se recibe el id del pagaré asociado al credito
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return ["status" => false, "message" => $this->fails_string($validator)];
        }
        return ["status" => true];
    }

    public function successResponse($message, $data = null)
    {
        return $this->response(true, $message, $data, 200);
    }

    public function errorResponse($message, $code)
    {
        //['status' => false, 'message' => $message, "data" => $data], $status_code)
        return $this->response(false, $message, null, $code);
    }
    public function response($status, $message, $data, $code)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            "data" => $data
        ], $code);
    }
}