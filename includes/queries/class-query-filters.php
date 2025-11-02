<?php
/**
 * Sistema Avanzado de Filtros para Queries
 *
 * Permite filtrar resultados de API con múltiples operadores y fuentes de valores
 *
 * @package Bricks_API_Integrator
 * @version 0.3-beta
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bricks_API_Query_Filters {

    /**
     * Operadores soportados
     */
    const OPERATORS = [
        'equals',
        'not_equals',
        'contains',
        'not_contains',
        'starts_with',
        'ends_with',
        'greater_than',
        'less_than',
        'greater_or_equal',
        'less_or_equal',
        'in',
        'not_in',
        'empty',
        'not_empty'
    ];

    /**
     * Fuentes de valores soportadas
     */
    const SOURCES = [
        'static',
        'url',
        'post_meta',
        'user_meta',
        'post_field',
        'dynamic'
    ];

    /**
     * Aplicar filtros a un array de items
     *
     * @param array $items Array de items a filtrar
     * @param array $filters_config Configuración de filtros
     * @return array Items filtrados
     */
    public function apply_filters($items, $filters_config) {
        if (empty($items) || !is_array($items) || empty($filters_config)) {
            return $items;
        }

        // Log de debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('QUERY FILTERS: Aplicando ' . count($filters_config) . ' filtros a ' . count($items) . ' items');
        }

        $filtered_items = $items;

        // Agrupar filtros por lógica (AND/OR)
        $and_filters = [];
        $or_filters = [];

        foreach ($filters_config as $filter) {
            $logic = $filter['logic'] ?? 'AND';
            if ($logic === 'OR') {
                $or_filters[] = $filter;
            } else {
                $and_filters[] = $filter;
            }
        }

        // Aplicar filtros AND (todos deben cumplirse)
        if (!empty($and_filters)) {
            $filtered_items = array_filter($filtered_items, function($item) use ($and_filters) {
                foreach ($and_filters as $filter) {
                    if (!$this->item_matches_filter($item, $filter)) {
                        return false; // Si no cumple uno, rechazar
                    }
                }
                return true; // Cumple todos los AND
            });
        }

        // Aplicar filtros OR (al menos uno debe cumplirse)
        if (!empty($or_filters)) {
            $or_results = array_filter($filtered_items, function($item) use ($or_filters) {
                foreach ($or_filters as $filter) {
                    if ($this->item_matches_filter($item, $filter)) {
                        return true; // Si cumple uno, aceptar
                    }
                }
                return false; // No cumple ningún OR
            });

            // Si había filtros AND, hacer intersección; si no, usar directamente OR
            if (!empty($and_filters)) {
                $filtered_items = array_intersect_key($filtered_items, $or_results);
            } else {
                $filtered_items = $or_results;
            }
        }

        // Reindexar array
        $filtered_items = array_values($filtered_items);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('QUERY FILTERS: Resultado: ' . count($filtered_items) . ' items después de filtrar');
        }

        return $filtered_items;
    }

    /**
     * Verificar si un item cumple con un filtro
     *
     * @param mixed $item Item a verificar
     * @param array $filter Configuración del filtro
     * @return bool True si cumple el filtro
     */
    private function item_matches_filter($item, $filter) {
        $field = $filter['field'] ?? '';
        $operator = $filter['operator'] ?? 'equals';
        $value_config = $filter['value'] ?? '';
        $source = $filter['source'] ?? 'static';

        // Obtener valor del campo en el item
        $item_value = $this->get_field_value($item, $field);

        // Obtener valor de comparación según la fuente
        $compare_value = $this->get_compare_value($value_config, $source);

        // Aplicar operador
        return $this->apply_operator($item_value, $compare_value, $operator);
    }

    /**
     * Obtener valor de un campo del item
     *
     * @param mixed $item Item (puede ser array u objeto)
     * @param string $field Nombre del campo (soporta notación de punto)
     * @return mixed Valor del campo o null
     */
    private function get_field_value($item, $field) {
        if (empty($field)) {
            return null;
        }

        // Convertir objeto a array para procesamiento uniforme
        if (is_object($item)) {
            $item = (array)$item;
        }

        // Soportar notación de punto (ej: "address.city")
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $value = $item;

            foreach ($parts as $part) {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } elseif (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return null;
                }
            }

            return $value;
        }

        // Campo simple
        if (is_array($item) && isset($item[$field])) {
            return $item[$field];
        }

        return null;
    }

    /**
     * Obtener valor de comparación según la fuente
     *
     * @param string $value_config Valor configurado
     * @param string $source Fuente del valor
     * @return mixed Valor procesado
     */
    private function get_compare_value($value_config, $source) {
        switch ($source) {
            case 'url':
                // Extraer nombre de parámetro de {query_var:nombre}
                if (preg_match('/\{query_var:([^}]+)\}/', $value_config, $matches)) {
                    $param_name = $matches[1];
                    return isset($_GET[$param_name]) ? sanitize_text_field($_GET[$param_name]) : '';
                }
                return '';

            case 'post_meta':
                // Extraer nombre de meta de {post_meta:nombre}
                if (preg_match('/\{post_meta:([^}]+)\}/', $value_config, $matches)) {
                    $meta_key = $matches[1];
                    $post_id = get_the_ID();
                    return get_post_meta($post_id, $meta_key, true);
                }
                return '';

            case 'user_meta':
                // Extraer nombre de meta de {user_meta:nombre}
                if (preg_match('/\{user_meta:([^}]+)\}/', $value_config, $matches)) {
                    $meta_key = $matches[1];
                    $user_id = get_current_user_id();
                    return get_user_meta($user_id, $meta_key, true);
                }
                return '';

            case 'post_field':
                // Extraer nombre de campo de {post_field:nombre}
                if (preg_match('/\{post_field:([^}]+)\}/', $value_config, $matches)) {
                    $field_name = $matches[1];
                    $post = get_post();
                    if ($post && isset($post->$field_name)) {
                        return $post->$field_name;
                    }
                }
                return '';

            case 'dynamic':
                // Intentar renderizar con Bricks
                if (function_exists('bricks_render_dynamic_data')) {
                    return bricks_render_dynamic_data($value_config);
                }
                return $value_config;

            case 'static':
            default:
                return $value_config;
        }
    }

    /**
     * Aplicar operador de comparación
     *
     * @param mixed $item_value Valor del item
     * @param mixed $compare_value Valor de comparación
     * @param string $operator Operador
     * @return bool Resultado de la comparación
     */
    private function apply_operator($item_value, $compare_value, $operator) {
        // Convertir a string para comparaciones de texto
        $item_str = is_scalar($item_value) ? (string)$item_value : '';
        $compare_str = is_scalar($compare_value) ? (string)$compare_value : '';

        switch ($operator) {
            case 'equals':
                return $item_str === $compare_str;

            case 'not_equals':
                return $item_str !== $compare_str;

            case 'contains':
                return stripos($item_str, $compare_str) !== false;

            case 'not_contains':
                return stripos($item_str, $compare_str) === false;

            case 'starts_with':
                return stripos($item_str, $compare_str) === 0;

            case 'ends_with':
                $length = strlen($compare_str);
                if ($length === 0) {
                    return true;
                }
                return substr($item_str, -$length) === $compare_str;

            case 'greater_than':
                return floatval($item_value) > floatval($compare_value);

            case 'less_than':
                return floatval($item_value) < floatval($compare_value);

            case 'greater_or_equal':
                return floatval($item_value) >= floatval($compare_value);

            case 'less_or_equal':
                return floatval($item_value) <= floatval($compare_value);

            case 'in':
                // $compare_value debe ser array o string separado por comas
                $array = is_array($compare_value) ? $compare_value : explode(',', $compare_str);
                $array = array_map('trim', $array);
                return in_array($item_str, $array);

            case 'not_in':
                $array = is_array($compare_value) ? $compare_value : explode(',', $compare_str);
                $array = array_map('trim', $array);
                return !in_array($item_str, $array);

            case 'empty':
                return empty($item_value);

            case 'not_empty':
                return !empty($item_value);

            default:
                return false;
        }
    }

    /**
     * Validar configuración de filtro
     *
     * @param array $filter Configuración del filtro
     * @return bool|string True si es válido, string con error si no
     */
    public static function validate_filter($filter) {
        if (empty($filter['field'])) {
            return 'El campo es obligatorio';
        }

        if (empty($filter['operator'])) {
            return 'El operador es obligatorio';
        }

        if (!in_array($filter['operator'], self::OPERATORS)) {
            return 'Operador no válido: ' . $filter['operator'];
        }

        if (!empty($filter['source']) && !in_array($filter['source'], self::SOURCES)) {
            return 'Fuente no válida: ' . $filter['source'];
        }

        return true;
    }

    /**
     * Obtener lista de operadores con descripciones
     *
     * @return array Array asociativo operador => descripción
     */
    public static function get_operators() {
        return [
            'equals' => 'Igual a',
            'not_equals' => 'Diferente de',
            'contains' => 'Contiene',
            'not_contains' => 'No contiene',
            'starts_with' => 'Empieza con',
            'ends_with' => 'Termina con',
            'greater_than' => 'Mayor que',
            'less_than' => 'Menor que',
            'greater_or_equal' => 'Mayor o igual que',
            'less_or_equal' => 'Menor o igual que',
            'in' => 'Está en (lista)',
            'not_in' => 'No está en (lista)',
            'empty' => 'Está vacío',
            'not_empty' => 'No está vacío'
        ];
    }

    /**
     * Obtener lista de fuentes con descripciones
     *
     * @return array Array asociativo fuente => descripción
     */
    public static function get_sources() {
        return [
            'static' => 'Valor fijo',
            'url' => 'Parámetro URL ({query_var:nombre})',
            'post_meta' => 'Meta del post ({post_meta:key})',
            'user_meta' => 'Meta del usuario ({user_meta:key})',
            'post_field' => 'Campo del post ({post_field:ID})',
            'dynamic' => 'Tag dinámico de Bricks'
        ];
    }
}
