<?php
/**
 * Estado de Query para Inmovilla
 *
 * Almacena información de paginación y estado de queries para comunicar
 * con los elementos nativos de Bricks (Pagination, Query Results Summary)
 *
 * @package Bricks_API_Integrator
 * @version 0.3-beta
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar el estado de queries de Inmovilla
 */
class Inmovilla_Query_State {

    /**
     * Estados almacenados por query_id
     * @var array
     */
    private static $states = [];

    /**
     * Query ID actual
     * @var string
     */
    private static $current_query_id = '';

    /**
     * Almacenar estado de una query
     *
     * @param string $query_id ID único de la query
     * @param array $data Datos a almacenar (total, max_pages, page, per_page, etc.)
     */
    public static function set($query_id, $data) {
        self::$states[$query_id] = array_merge(
            self::$states[$query_id] ?? [],
            $data
        );
        self::$current_query_id = $query_id;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('INMOVILLA_QUERY_STATE: Guardado estado para ' . $query_id . ' - ' . print_r($data, true));
        }
    }

    /**
     * Obtener estado de una query
     *
     * @param string $query_id ID de la query
     * @param string|null $key Clave específica a obtener (opcional)
     * @return mixed Estado completo, valor de la clave, o null
     */
    public static function get($query_id, $key = null) {
        if (!isset(self::$states[$query_id])) {
            return null;
        }

        if ($key !== null) {
            return self::$states[$query_id][$key] ?? null;
        }

        return self::$states[$query_id];
    }

    /**
     * Obtener estado de la query actual
     *
     * @param string|null $key Clave específica a obtener (opcional)
     * @return mixed
     */
    public static function get_current($key = null) {
        return self::get(self::$current_query_id, $key);
    }

    /**
     * Obtener el query_id actual
     *
     * @return string
     */
    public static function get_current_query_id() {
        return self::$current_query_id;
    }

    /**
     * Limpiar estado de una query
     *
     * @param string $query_id ID de la query
     */
    public static function clear($query_id) {
        if (isset(self::$states[$query_id])) {
            unset(self::$states[$query_id]);
        }
    }

    /**
     * Limpiar todos los estados
     */
    public static function clear_all() {
        self::$states = [];
        self::$current_query_id = '';
    }

    /**
     * Verificar si existe estado para una query
     *
     * @param string $query_id ID de la query
     * @return bool
     */
    public static function exists($query_id) {
        return isset(self::$states[$query_id]);
    }

    /**
     * Obtener total de items para una query
     *
     * @param string|null $query_id ID de la query (usa actual si no se especifica)
     * @return int
     */
    public static function get_total($query_id = null) {
        $qid = $query_id ?? self::$current_query_id;
        return intval(self::get($qid, 'total') ?? 0);
    }

    /**
     * Obtener número máximo de páginas
     *
     * @param string|null $query_id ID de la query (usa actual si no se especifica)
     * @return int
     */
    public static function get_max_pages($query_id = null) {
        $qid = $query_id ?? self::$current_query_id;
        return intval(self::get($qid, 'max_pages') ?? 1);
    }

    /**
     * Obtener página actual
     *
     * @param string|null $query_id ID de la query (usa actual si no se especifica)
     * @return int
     */
    public static function get_current_page($query_id = null) {
        $qid = $query_id ?? self::$current_query_id;
        return intval(self::get($qid, 'page') ?? 1);
    }

    /**
     * Obtener items por página
     *
     * @param string|null $query_id ID de la query (usa actual si no se especifica)
     * @return int
     */
    public static function get_per_page($query_id = null) {
        $qid = $query_id ?? self::$current_query_id;
        return intval(self::get($qid, 'per_page') ?? 20);
    }

    /**
     * Calcular offset para la API de Inmovilla
     *
     * Inmovilla usa pos basado en 1 (pos=1 es primer elemento)
     *
     * @param int $page Página actual
     * @param int $per_page Items por página
     * @return int Posición de inicio (basada en 1)
     */
    public static function calculate_pos($page, $per_page) {
        return (($page - 1) * $per_page) + 1;
    }

    /**
     * Debug: obtener todos los estados almacenados
     *
     * @return array
     */
    public static function debug_get_all_states() {
        return self::$states;
    }
}

/**
 * Filtros de Bricks para informar totales de paginación
 */

// Filtro para el total de resultados
add_filter('bricks/query/result_count', function($count, $query) {
    $object_type = $query->object_type ?? '';

    // Verificar si es un source de Inmovilla
    if (strpos($object_type, 'inmovilla') === false &&
        strpos($object_type, 'source_inmovilla') === false &&
        strpos($object_type, 'snap_source_inmovilla') === false) {
        return $count;
    }

    // Generar query_id consistente
    $query_id = inmovilla_generate_query_id($query);

    $total = Inmovilla_Query_State::get_total($query_id);

    if ($total > 0) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('INMOVILLA PAGINATION: result_count devolviendo ' . $total . ' para query_id=' . $query_id);
        }
        return $total;
    }

    return $count;
}, 10, 2);

// Filtro para el número máximo de páginas
add_filter('bricks/query/result_max_num_pages', function($max_pages, $query) {
    $object_type = $query->object_type ?? '';

    // Verificar si es un source de Inmovilla
    if (strpos($object_type, 'inmovilla') === false &&
        strpos($object_type, 'source_inmovilla') === false &&
        strpos($object_type, 'snap_source_inmovilla') === false) {
        return $max_pages;
    }

    // Generar query_id consistente
    $query_id = inmovilla_generate_query_id($query);

    $pages = Inmovilla_Query_State::get_max_pages($query_id);

    if ($pages > 0) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('INMOVILLA PAGINATION: result_max_num_pages devolviendo ' . $pages . ' para query_id=' . $query_id);
        }
        return $pages;
    }

    return $max_pages;
}, 10, 2);

/**
 * Generar ID único para una query de Bricks
 *
 * @param object $query Objeto query de Bricks
 * @return string
 */
function inmovilla_generate_query_id($query) {
    $object_type = $query->object_type ?? '';
    $element_id = $query->element_id ?? '';

    // Usar element_id si está disponible, sino object_type
    if (!empty($element_id)) {
        return 'inmovilla_' . $element_id;
    }

    return 'inmovilla_' . sanitize_key($object_type);
}
