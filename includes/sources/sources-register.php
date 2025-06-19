<?php
// Registro de sources personalizados para Bricks

function register_api_sources_with_bricks($sources) {
    $api_sources = get_option('bricks_api_sources', []);
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS SOURCES DEBUG: api_sources encontrados: ' . print_r($api_sources, true));
    }
    if (empty($api_sources)) {
        return $sources;
    }
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        // Registrar con clave source_{id}
        $sources['source_' . $source_id] = [
            'name'  => $display_name,
            'class' => 'Bricks_API_Source_Query',
        ];
        // Registrar también con clave solo {id}
        $sources[$source_id] = [
            'name'  => $display_name . ' (ID directo)',
            'class' => 'Bricks_API_Source_Query',
        ];
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BRICKS SOURCES DEBUG: Registrando source con claves source_' . $source_id . ' y ' . $source_id);
        }
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS SOURCES DEBUG: sources registrados en Bricks: ' . print_r($sources, true));
    }
    return $sources;
}

add_filter('bricks/query/sources', 'register_api_sources_with_bricks', 1);
add_filter('bricks/query/loop_control_options', function($options) {
    $api_sources = get_option('bricks_api_sources', []);
    if (empty($api_sources)) return $options;
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        $options['source_' . $source_id] = [
            'label' => $display_name,
            'class' => 'Bricks_API_Source_Query',
        ];
        $options[$source_id] = [
            'label' => $display_name . ' (ID directo)',
            'class' => 'Bricks_API_Source_Query',
        ];
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS SOURCES DEBUG: Sources registrados en loop_control_options: ' . print_r($options, true));
    }
    return $options;
}, 1);
if (!class_exists('Bricks_API_Source_Query')) {
    if (defined('BRICKS_VERSION') && class_exists('Bricks_Query_Provider')) {
        class Bricks_API_Source_Query extends Bricks_Query_Provider {
            public function __construct() {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BRICKS SOURCES DEBUG: Constructor de Bricks_API_Source_Query inicializado (registro inmediato)');
                }
                $this->name = 'api_source';
                $this->label = esc_html__('Query Type API', 'bricks-api-integrator');
                $this->controls = [
                    'source_id' => [
                        'type'        => 'select',
                        'label'       => esc_html__('Query Type', 'bricks-api-integrator'),
                        'options'     => $this->get_api_sources_options(),
                        'description' => esc_html__('Selecciona un Query Type configurado en API Integrator.', 'bricks-api-integrator'),
                        'required'    => true,
                    ],
                    'pagination' => [
                        'type'  => 'checkbox',
                        'label' => esc_html__('Pagination', 'bricks-api-integrator'),
                    ],
                    'items_per_page' => [
                        'type'        => 'number',
                        'label'       => esc_html__('Items per page', 'bricks-api-integrator'),
                        'min'         => 1,
                        'max'         => 100,
                        'placeholder' => 10,
                        'required'    => ['pagination', '!=', ''],
                    ],
                    'cache_results' => [
                        'type'        => 'checkbox',
                        'label'       => esc_html__('Cache results', 'bricks-api-integrator'),
                        'description' => esc_html__('Cache API results to improve performance.', 'bricks-api-integrator'),
                    ],
                    'cache_duration' => [
                        'type'        => 'number',
                        'label'       => esc_html__('Cache duration (minutes)', 'bricks-api-integrator'),
                        'min'         => 1,
                        'max'         => 1440,
                        'placeholder' => 15,
                        'required'    => ['cache_results', '!=', ''],
                    ],
                    'dynamic_params' => [
                        'type'        => 'repeater',
                        'label'       => esc_html__('Dynamic Parameters', 'bricks-api-integrator'),
                        'description' => esc_html__('Add custom parameters to override source configuration.', 'bricks-api-integrator'),
                        'fields'      => [
                            'param_name' => [
                                'type'        => 'text',
                                'label'       => esc_html__('Parameter Name', 'bricks-api-integrator'),
                                'placeholder' => 'id',
                            ],
                            'param_value' => [
                                'type'        => 'text',
                                'label'       => esc_html__('Parameter Value', 'bricks-api-integrator'),
                                'placeholder' => '123',
                            ],
                        ],
                    ],
                ];
                parent::__construct();
            }
            private function get_api_sources_options() {
                $options = [];
                $api_sources = get_option('bricks_api_sources', []);
                if (!empty($api_sources)) {
                    foreach ($api_sources as $source_id => $source) {
                        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
                        $options[$source_id] = $display_name;
                    }
                } else {
                    $options['no_sources'] = esc_html__('No hay Query Types configurados', 'bricks-api-integrator');
                }
                return $options;
            }
            public function get_results($query_args = []) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BRICKS SOURCES DEBUG: get_results llamado con query_args: ' . print_r($query_args, true));
                }
                $source_id = isset($query_args['source_id']) ? $query_args['source_id'] : '';
                if (empty($source_id) || $source_id === 'no_sources') {
                    return [
                        'items' => [],
                        'count' => 0,
                        'found_posts' => 0,
                        'post_count' => 0,
                        'error' => esc_html__('No se ha seleccionado ningún Query Type o no hay Query Types disponibles.', 'bricks-api-integrator'),
                    ];
                }
                $api_sources = get_option('bricks_api_sources', []);
                if (!isset($api_sources[$source_id])) {
                    return [
                        'items' => [],
                        'count' => 0,
                        'found_posts' => 0,
                        'post_count' => 0,
                        'error' => esc_html__('El Query Type seleccionado no se encontró.', 'bricks-api-integrator'),
                    ];
                }
                $source = $api_sources[$source_id];
                $use_cache = isset($query_args['cache_results']) && $query_args['cache_results'];
                $cache_duration = isset($query_args['cache_duration']) ? intval($query_args['cache_duration']) * 60 : 900;
                $cache_key = 'bricks_api_' . md5(serialize([$source_id, $query_args, $_GET]));
                if ($use_cache) {
                    $cached_results = get_transient($cache_key);
                    if ($cached_results !== false && isset($cached_results['items'])) {
                        return $cached_results;
                    }
                }
                $endpoints = get_option('bricks_api_endpoints', []);
                $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
                if (!isset($endpoints[$endpoint_id])) {
                    return [
                        'items' => [],
                        'count' => 0,
                        'found_posts' => 0,
                        'post_count' => 0,
                        'error' => esc_html__('Endpoint configuration not found.', 'bricks-api-integrator'),
                    ];
                }
                $endpoint = $endpoints[$endpoint_id];
                $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
                if (empty($endpoint_url)) {
                    return [
                        'items' => [],
                        'count' => 0,
                        'found_posts' => 0,
                        'post_count' => 0,
                        'error' => esc_html__('Endpoint URL is empty.', 'bricks-api-integrator'),
                    ];
                }
                $request_url = $endpoint_url;
                $pagination_type = isset($source['pagination_type']) ? $source['pagination_type'] : 'none';
                $pagination_param = isset($source['pagination_param']) ? $source['pagination_param'] : '';
                $per_page_param = isset($source['per_page_param']) ? $source['per_page_param'] : '';
                $use_pagination = isset($query_args['pagination']) && $query_args['pagination'];
                $page = 1;
                $items_per_page = isset($query_args['items_per_page']) ? intval($query_args['items_per_page']) : 10;
                // ... (resto de la lógica de paginación y obtención de resultados)
                // ...
                // --- DEBUG: Log $_GET and $wp_query->query_vars before filtering ---
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    global $wp_query;
                    error_log('BRICKS SOURCES DEBUG: [DETAIL] $_GET=' . print_r($_GET, true));
                    error_log('BRICKS SOURCES DEBUG: [DETAIL] $wp_query->query_vars=' . print_r($wp_query->query_vars, true));
                }
                // --- CLEAN LOGS: Remove all previous logs and add only relevant debug logs for filtering ---
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BRICKS SOURCES DEBUG: [DETAIL] Filtering by id_param: ' . $id_param . ' | value: ' . print_r($id_value, true));
                    error_log('BRICKS SOURCES DEBUG: [DETAIL] Items BEFORE filter: ' . print_r($items, true));
                }
                // --- FILTER BY id_param: Only return the item that matches the value from the URL ---
                if (isset($items) && $id_value !== null && is_array($items)) {
                    $items = array_filter($items, function($item) use ($id_param, $id_value) {
                        // Support both array and object
                        if (is_array($item) && isset($item[$id_param])) {
                            return (string)$item[$id_param] === (string)$id_value;
                        } elseif (is_object($item) && isset($item->$id_param)) {
                            return (string)$item->$id_param === (string)$id_value;
                        }
                        // Extra: try to match by slug if id_param is 'slug' or similar
                        if ($id_param === 'slug' && is_array($item) && isset($item['slug'])) {
                            return (string)$item['slug'] === (string)$id_value;
                        } elseif ($id_param === 'slug' && is_object($item) && isset($item->slug)) {
                            return (string)$item->slug === (string)$id_value;
                        }
                        return false;
                    });
                    $items = array_values($items); // Reindex
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('BRICKS SOURCES DEBUG: [DETAIL] Items AFTER filter: ' . print_r($items, true));
                    }
                }
                // --- Normalizar la respuesta: siempre array de objetos stdClass ---
                $formatted = [];
                if (isset($items)) {
                    foreach ($items as $item) {
                        if (is_object($item)) {
                            $formatted[] = $item;
                        } elseif (is_array($item)) {
                            // Si es array asociativo, convertir a objeto
                            if (array_keys($item) !== range(0, count($item) - 1)) {
                                $formatted[] = (object)$item;
                            } else {
                                // Si es array indexado, envolver como value
                                $formatted[] = (object)['value' => $item];
                            }
                        } else {
                            // Si es escalar, envolver como value
                            $formatted[] = (object)['value' => $item];
                        }
                    }
                }
                // --- Devolver solo el array plano de objetos ---
                return $formatted;
            }
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BRICKS SOURCES DEBUG: Clase Bricks_API_Source_Query registrada (registro inmediato)');
        }
    }
}
