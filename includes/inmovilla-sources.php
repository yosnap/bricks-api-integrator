<?php
/**
 * Sources automáticos para Inmovilla
 * Crea query types predefinidos con soporte completo para filtros, paginación y sorting
 *
 * @package Bricks_API_Integrator
 * @version 0.3.0-beta
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuración de campos de filtro de Inmovilla
 * Mapea nombres de parámetros a configuración para integración con Bricks
 */
function inmovilla_get_filter_fields() {
    return [
        'key_tipo' => [
            'label' => 'Tipo de inmueble',
            'inmovilla_field' => 'key_tipo',
            'source_type' => 'tipos',
            'type' => 'select',
        ],
        'key_loca' => [
            'label' => 'Ciudad',
            'inmovilla_field' => 'key_loca',
            'source_type' => 'ciudades',
            'type' => 'select',
        ],
        'key_zona' => [
            'label' => 'Zona',
            'inmovilla_field' => 'key_zona',
            'source_type' => 'zonas',
            'type' => 'select',
        ],
        'keyprov' => [
            'label' => 'Provincia',
            'inmovilla_field' => 'keyprov',
            'type' => 'text',
        ],
        'keyacci' => [
            'label' => 'Acción',
            'inmovilla_field' => 'keyacci',
            'type' => 'select',
            'options' => [
                '1' => 'Venta',
                '2' => 'Alquiler',
                '3' => 'Alquiler con opción a compra',
                '4' => 'Traspaso',
            ],
        ],
        'precio_desde' => [
            'label' => 'Precio desde',
            'inmovilla_field' => 'precioinmo',
            'operator' => '>=',
            'type' => 'number',
        ],
        'precio_hasta' => [
            'label' => 'Precio hasta',
            'inmovilla_field' => 'precioinmo',
            'operator' => '<=',
            'type' => 'number',
        ],
        'habitaciones' => [
            'label' => 'Habitaciones mínimas',
            'inmovilla_field' => 'habitaciones',
            'operator' => '>=',
            'type' => 'number',
        ],
        'banyos' => [
            'label' => 'Baños mínimos',
            'inmovilla_field' => 'banyos',
            'operator' => '>=',
            'type' => 'number',
        ],
        'metros_desde' => [
            'label' => 'Metros desde',
            'inmovilla_field' => 'm_cons',
            'operator' => '>=',
            'type' => 'number',
        ],
        'metros_hasta' => [
            'label' => 'Metros hasta',
            'inmovilla_field' => 'm_cons',
            'operator' => '<=',
            'type' => 'number',
        ],
    ];
}

/**
 * Configuración de campos de ordenamiento de Inmovilla
 */
function inmovilla_get_order_fields() {
    return [
        'precio' => [
            'label' => 'Precio',
            'inmovilla_field' => 'precioinmo',
        ],
        'precio_asc' => [
            'label' => 'Precio (menor a mayor)',
            'inmovilla_field' => 'precioinmo',
            'direction' => 'ASC',
        ],
        'precio_desc' => [
            'label' => 'Precio (mayor a menor)',
            'inmovilla_field' => 'precioinmo',
            'direction' => 'DESC',
        ],
        'fecha' => [
            'label' => 'Fecha',
            'inmovilla_field' => 'fecha_alta',
        ],
        'fecha_asc' => [
            'label' => 'Fecha (más antiguos)',
            'inmovilla_field' => 'fecha_alta',
            'direction' => 'ASC',
        ],
        'fecha_desc' => [
            'label' => 'Fecha (más recientes)',
            'inmovilla_field' => 'fecha_alta',
            'direction' => 'DESC',
        ],
        'metros' => [
            'label' => 'Metros cuadrados',
            'inmovilla_field' => 'm_cons',
        ],
        'habitaciones' => [
            'label' => 'Habitaciones',
            'inmovilla_field' => 'habitaciones',
        ],
        'referencia' => [
            'label' => 'Referencia',
            'inmovilla_field' => 'ref',
        ],
    ];
}

/**
 * Registrar Sources predefinidos de Inmovilla
 */
function register_inmovilla_sources() {
    $sources = get_option('bricks_api_sources', []);
    $endpoints = get_option('bricks_api_endpoints', []);

    // Buscar el endpoint de Inmovilla
    $inmovilla_endpoint_id = null;
    foreach ($endpoints as $id => $endpoint) {
        if (!is_array($endpoint)) {
            continue;
        }
        if (strpos($endpoint['url'] ?? '', 'apiweb.inmovilla.com') !== false) {
            $inmovilla_endpoint_id = $id;
            break;
        }
    }

    // Si no hay endpoint, no registrar sources (debe existir primero)
    if ($inmovilla_endpoint_id === null) {
        return;
    }

    // Verificar si ya existen los sources de Inmovilla
    $existing_slugs = array_column($sources, 'name');

    // Sources predefinidos para Inmovilla
    $inmovilla_sources = [
        'inmovilla_inmuebles' => [
            'name' => 'Inmovilla - Inmuebles',
            'query_type_name' => 'Inmuebles (Inmovilla)',
            'endpoint_id' => $inmovilla_endpoint_id,
            'items_path' => 'paginacion',
            'tipo' => 'paginacion',
            'pagination_type' => 'offset',
            'pagination_param' => 'pos',
            'per_page_param' => 'num_elementos',
            'supports_filters' => true,
            'supports_sorting' => true,
            'supports_pagination' => true,
        ],
        'inmovilla_destacados' => [
            'name' => 'Inmovilla - Destacados',
            'query_type_name' => 'Destacados (Inmovilla)',
            'endpoint_id' => $inmovilla_endpoint_id,
            'items_path' => 'destacados',
            'tipo' => 'destacados',
            'pagination_type' => 'offset',
            'pagination_param' => 'pos',
            'per_page_param' => 'num_elementos',
            'supports_filters' => false,
            'supports_sorting' => true,
            'supports_pagination' => true,
        ],
        'inmovilla_tipos' => [
            'name' => 'Inmovilla - Tipos',
            'query_type_name' => 'Tipos de Inmueble (Inmovilla)',
            'endpoint_id' => $inmovilla_endpoint_id,
            'items_path' => 'tipos',
            'tipo' => 'tipos',
            'pagination_type' => 'none',
            'supports_filters' => false,
            'supports_sorting' => false,
            'supports_pagination' => false,
        ],
        'inmovilla_ciudades' => [
            'name' => 'Inmovilla - Ciudades',
            'query_type_name' => 'Ciudades (Inmovilla)',
            'endpoint_id' => $inmovilla_endpoint_id,
            'items_path' => 'ciudades',
            'tipo' => 'ciudades',
            'pagination_type' => 'none',
            'supports_filters' => false,
            'supports_sorting' => false,
            'supports_pagination' => false,
        ],
        'inmovilla_zonas' => [
            'name' => 'Inmovilla - Zonas',
            'query_type_name' => 'Zonas (Inmovilla)',
            'endpoint_id' => $inmovilla_endpoint_id,
            'items_path' => 'zonas',
            'tipo' => 'zonas',
            'pagination_type' => 'none',
            'supports_filters' => true,
            'supports_sorting' => false,
            'supports_pagination' => false,
            'required_filter' => 'cod_ciu',
            'filter_note' => 'Requiere código de ciudad (cod_ciu). Primero obtén las ciudades.',
            'dynamic_params' => [
                ['name' => 'cod_ciu', 'source' => 'static', 'default' => ''],
            ],
        ],
    ];

    $updated = false;
    foreach ($inmovilla_sources as $key => $source_config) {
        if (!in_array($source_config['name'], $existing_slugs)) {
            $sources[$key] = $source_config;
            $updated = true;
        }
    }

    if ($updated) {
        update_option('bricks_api_sources', $sources);
    }
}

// Ejecutar al cargar el plugin
add_action('plugins_loaded', 'register_inmovilla_sources', 15);
add_action('admin_init', 'register_inmovilla_sources', 15);

/**
 * Forzar actualización del source de Zonas (para añadir parámetro cod_ciu)
 * NOTA: Solo actualiza campos de metadatos, NO sobrescribe dynamic_params si ya existen
 */
function force_update_inmovilla_zonas_source() {
    $sources = get_option('bricks_api_sources', []);

    if (isset($sources['inmovilla_zonas'])) {
        $updated = false;

        // Solo actualizar metadatos si no existen
        if (!isset($sources['inmovilla_zonas']['required_filter'])) {
            $sources['inmovilla_zonas']['required_filter'] = 'cod_ciu';
            $updated = true;
        }
        if (!isset($sources['inmovilla_zonas']['filter_note'])) {
            $sources['inmovilla_zonas']['filter_note'] = 'Requiere código de ciudad (cod_ciu). Primero obtén las ciudades.';
            $updated = true;
        }

        // Solo agregar dynamic_params si NO existen o están vacíos
        // NO sobrescribir valores que el usuario haya guardado
        if (empty($sources['inmovilla_zonas']['dynamic_params'])) {
            $sources['inmovilla_zonas']['dynamic_params'] = [
                ['name' => 'cod_ciu', 'source' => 'static', 'default' => ''],
            ];
            $updated = true;
        }

        if ($updated) {
            update_option('bricks_api_sources', $sources);
        }
    }
}
add_action('admin_init', 'force_update_inmovilla_zonas_source', 20);

/**
 * Inyectar tags virtuales (detail_url) en los sources de Inmovilla
 * Estos tags no vienen de la API sino que son calculados por el plugin
 */
function inject_inmovilla_virtual_tags() {
    $sources = get_option('bricks_api_sources', []);
    $updated = false;

    // Sources que soportan detail_url
    $sources_with_detail = ['inmovilla_inmuebles', 'inmovilla_destacados'];

    foreach ($sources_with_detail as $source_key) {
        if (!isset($sources[$source_key])) continue;

        $tags = $sources[$source_key]['tags'] ?? [];
        $source_slug = str_replace('_', '-', $source_key);
        $detail_tag = '{snap_' . $source_slug . '_detail_url}';

        if (!in_array($detail_tag, $tags)) {
            $sources[$source_key]['tags'][] = $detail_tag;
            $updated = true;
        }
    }

    if ($updated) {
        update_option('bricks_api_sources', $sources);
    }
}
add_action('plugins_loaded', 'inject_inmovilla_virtual_tags', 16);
add_action('admin_init', 'inject_inmovilla_virtual_tags', 21);

/**
 * Forzar que todos los sources de Inmovilla tengan el campo 'tipo' correcto
 */
function force_update_inmovilla_sources_tipo() {
    $sources = get_option('bricks_api_sources', []);
    $expected_tipos = [
        'inmovilla_inmuebles' => 'paginacion',
        'inmovilla_destacados' => 'destacados',
        'inmovilla_tipos' => 'tipos',
        'inmovilla_ciudades' => 'ciudades',
        'inmovilla_zonas' => 'zonas',
    ];

    $updated = false;
    foreach ($expected_tipos as $key => $tipo) {
        if (isset($sources[$key]) && ($sources[$key]['tipo'] ?? '') !== $tipo) {
            $sources[$key]['tipo'] = $tipo;
            $updated = true;
        }
    }

    if ($updated) {
        update_option('bricks_api_sources', $sources);
    }
}
add_action('admin_init', 'force_update_inmovilla_sources_tipo', 21);
add_action('plugins_loaded', 'force_update_inmovilla_sources_tipo', 16);

/**
 * Limpiar transients de filtros al cargar el plugin (para asegurar datos frescos)
 * También se ejecuta en admin_init para limpiar después de actualizaciones
 */
function clear_inmovilla_filter_transients() {
    // Limpiar todos los transients de filtros
    delete_transient('inmovilla_filter_options_ciudades');
    delete_transient('inmovilla_filter_options_tipos');
    delete_transient('inmovilla_filter_options_zonas');

    // Limpiar zonas con parámetros de ciudad (0-99)
    for ($i = 0; $i < 100; $i++) {
        delete_transient('inmovilla_filter_options_zonas_' . $i);
    }
}
add_action('plugins_loaded', 'clear_inmovilla_filter_transients', 17);
add_action('admin_init', 'clear_inmovilla_filter_transients', 22);

/**
 * Registrar template Single automática para detalle de inmuebles
 * Solo crea la template si no existe ninguna Single para Inmovilla
 */
function register_inmovilla_single_template() {
    $api_templates = get_option('bricks_api_templates', []);

    // Verificar si ya existe una template Single para Inmovilla
    foreach ($api_templates as $template) {
        if (
            isset($template['template_type']) && $template['template_type'] === 'single' &&
            isset($template['endpoint_type']) && $template['endpoint_type'] === 'source' &&
            isset($template['endpoint_id']) && strpos($template['endpoint_id'], 'inmovilla') !== false
        ) {
            return; // Ya existe, no crear duplicado
        }
    }

    // Obtener URL base desde configuración
    $ui_options = get_option('inmovilla_ui_options', []);
    $url_base = $ui_options['detail_url_base'] ?? 'inmuebles';

    // Registrar template Single (sin page_id, el usuario debe asignar la página en admin)
    $template_id = 'inmovilla_single_' . time();
    $api_templates[$template_id] = [
        'name' => 'Inmovilla - Detalle Inmueble',
        'endpoint_type' => 'source',
        'endpoint_id' => 'inmovilla_inmuebles',
        'template_type' => 'single',
        'page_id' => 0,
        'url_base' => $url_base,
        'id_param' => 'ref',
    ];

    update_option('bricks_api_templates', $api_templates);
    update_option('bricks_api_flush_rewrite_rules', true);
}
add_action('admin_init', 'register_inmovilla_single_template', 25);

/**
 * Clase para manejar las consultas de Inmovilla con soporte completo de paginación,
 * filtros y ordenamiento integrado con Bricks Builder
 */
class Inmovilla_Query_Handler {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Capturar filtros desde parámetros URL
     * Soporta tanto nombres directos como prefijo brx_ de Bricks
     *
     * @return array Filtros capturados
     */
    public function capture_filters_from_url() {
        $filter_fields = inmovilla_get_filter_fields();
        $filters = [];

        foreach ($_GET as $key => $value) {
            if (empty($value)) {
                continue;
            }

            // Remover prefijo brx_ si existe
            $filter_key = preg_replace('/^brx_/', '', $key);

            // Verificar si es un campo de filtro conocido
            if (isset($filter_fields[$filter_key])) {
                $filters[$filter_key] = sanitize_text_field($value);
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG && !empty($filters)) {
            error_log('INMOVILLA FILTERS: Capturados desde URL: ' . print_r($filters, true));
        }

        return $filters;
    }

    /**
     * Construir cláusula WHERE para Inmovilla
     *
     * @param array $filters Filtros a aplicar
     * @return string Cláusula WHERE
     */
    public function build_where_clause($filters) {
        if (empty($filters)) {
            return '';
        }

        $filter_fields = inmovilla_get_filter_fields();
        $where_parts = [];

        foreach ($filters as $key => $value) {
            if (empty($value) || !isset($filter_fields[$key])) {
                continue;
            }

            $config = $filter_fields[$key];
            $field = $config['inmovilla_field'];
            $operator = $config['operator'] ?? '=';

            // Construir condición según operador
            switch ($operator) {
                case '>=':
                    $where_parts[] = $field . '>=' . $value;
                    break;
                case '<=':
                    $where_parts[] = $field . '<=' . $value;
                    break;
                case '>':
                    $where_parts[] = $field . '>' . $value;
                    break;
                case '<':
                    $where_parts[] = $field . '<' . $value;
                    break;
                case '=':
                default:
                    $where_parts[] = $field . '=' . $value;
                    break;
            }
        }

        $where = implode(' AND ', $where_parts);

        if (defined('WP_DEBUG') && WP_DEBUG && !empty($where)) {
            error_log('INMOVILLA WHERE: ' . $where);
        }

        return $where;
    }

    /**
     * Capturar y construir ordenamiento
     *
     * @param object|null $query Objeto query de Bricks
     * @return string Parámetro de orden para Inmovilla
     */
    public function capture_order($query = null) {
        $order_fields = inmovilla_get_order_fields();

        // Prioridad 1: Parámetro orden de URL
        if (!empty($_GET['orden'])) {
            return sanitize_text_field($_GET['orden']);
        }

        // Prioridad 2: Parámetro orderby de URL (compatible con Bricks)
        $orderby = $_GET['orderby'] ?? $_GET['brx_orderby'] ?? '';
        $order = strtoupper($_GET['order'] ?? $_GET['brx_order'] ?? 'ASC');

        if (!empty($orderby)) {
            // Buscar en campos predefinidos
            if (isset($order_fields[$orderby])) {
                $config = $order_fields[$orderby];
                $field = $config['inmovilla_field'];
                $direction = $config['direction'] ?? $order;
                return $field . ' ' . $direction;
            }

            // Si no está en predefinidos, usar directamente
            return $orderby . ' ' . $order;
        }

        // Prioridad 3: Query vars de Bricks
        if ($query && isset($query->query_vars['orderby'])) {
            $bricks_orderby = $query->query_vars['orderby'];
            $bricks_order = strtoupper($query->query_vars['order'] ?? 'ASC');

            if (isset($order_fields[$bricks_orderby])) {
                $config = $order_fields[$bricks_orderby];
                $field = $config['inmovilla_field'];
                $direction = $config['direction'] ?? $bricks_order;
                return $field . ' ' . $direction;
            }
        }

        return '';
    }

    /**
     * Obtener página actual desde múltiples fuentes
     *
     * @return int Número de página (basado en 1)
     */
    public function get_current_page() {
        // Prioridad 1: Parámetro paged de WordPress
        if (!empty($_GET['paged'])) {
            return max(1, intval($_GET['paged']));
        }

        // Prioridad 2: Parámetro page
        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            return max(1, intval($_GET['page']));
        }

        // Prioridad 3: Query var de WordPress
        $paged = get_query_var('paged');
        if ($paged > 0) {
            return intval($paged);
        }

        return 1;
    }

    /**
     * Ejecutar consulta a Inmovilla con soporte completo
     *
     * @param array $source_config Configuración del source
     * @param array $query_args Argumentos de la query
     * @param object|null $bricks_query Objeto query de Bricks (opcional)
     * @return array Resultado con items, total, página, etc.
     */
    public function execute_query($source_config, $query_args = [], $bricks_query = null) {
        $endpoints = get_option('bricks_api_endpoints', []);
        $endpoint_id = $source_config['endpoint_id'] ?? '';

        if (!isset($endpoints[$endpoint_id])) {
            return ['items' => [], 'total' => 0, 'error' => 'Endpoint no encontrado'];
        }

        $endpoint = $endpoints[$endpoint_id];

        // Obtener parámetros del endpoint
        $params = [];
        if (!empty($endpoint['dynamic_params'])) {
            foreach ($endpoint['dynamic_params'] as $param) {
                if (!empty($param['name']) && isset($param['default'])) {
                    $params[$param['name']] = $param['default'];
                }
            }
        }

        // Tipo de consulta
        $tipo = $source_config['tipo'] ?? 'paginacion';

        // Paginación
        $page = $query_args['page'] ?? $this->get_current_page();
        $per_page = $query_args['per_page'] ?? 20;
        $pos = (($page - 1) * $per_page) + 1;

        // Filtros - combinar de query_args y URL (salvo que se pida omitir URL)
        $skip_url = !empty($query_args['skip_url_filters']);
        $url_filters = $skip_url ? [] : $this->capture_filters_from_url();
        $arg_filters = $query_args['filters'] ?? [];
        $all_filters = array_merge($arg_filters, $url_filters);

        // Construir WHERE
        $where = $this->build_where_clause($all_filters);

        // Si hay where adicional en query_args, combinarlo
        if (!empty($query_args['where'])) {
            if (!empty($where)) {
                $where .= ' AND ' . $query_args['where'];
            } else {
                $where = $query_args['where'];
            }
        }

        // Ordenamiento
        $orden = $query_args['order'] ?? $this->capture_order($bricks_query);

        // Construir string de parámetros Inmovilla
        $agencia = $params['agencia'] ?? '';
        $password = $params['password'] ?? '';
        $idioma = $params['idioma'] ?? '1';
        $lostipos = $params['lostipos'] ?? 'lostipos';

        $texto = $agencia . ';' . $password . ';' . $idioma . ';' . $lostipos . ';' . $tipo . ';' . $pos . ';' . $per_page . ';' . $where . ';' . $orden;

        $dominio = $_SERVER['SERVER_NAME'] ?? '';
        $ip = !empty($params['ip']) ? $params['ip'] : $this->get_client_ip();

        // Realizar petición
        $url = $endpoint['url'];
        $body = 'param=' . rawurlencode($texto) . '&elDominio=' . urlencode($dominio) . '&ia=' . urlencode($ip) . '&ib=&json=1';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('INMOVILLA QUERY: param=' . $texto);
        }

        $response = wp_remote_post($url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
            ],
            'timeout' => 30,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return ['items' => [], 'total' => 0, 'error' => $response->get_error_message()];
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            return ['items' => [], 'total' => 0, 'error' => 'HTTP ' . $status];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['items' => [], 'total' => 0, 'error' => 'JSON inválido: ' . $body];
        }

        // Extraer items según items_path
        $items_path = $source_config['items_path'] ?? '';
        $items = [];
        $total = 0;

        if (!empty($items_path) && array_key_exists($items_path, $data)) {
            $raw_items = $data[$items_path];

            // Si el items_path existe pero es null/vacío, no hay resultados
            if (empty($raw_items) || !is_array($raw_items)) {
                $items = [];
            } else {
                // El primer elemento es siempre metadata (posicion, elementos, total)
                // Aplica para: paginacion, ciudades, tipos, zonas, destacados, etc.
                if (isset($raw_items[0]['total']) && isset($raw_items[0]['posicion'])) {
                    $total = intval($raw_items[0]['total']);
                    // Quitar el primer elemento (metadata)
                    array_shift($raw_items);
                }

                $items = $raw_items;
            }
        } elseif (is_array($data)) {
            // Fallback: usar $data directamente, pero solo items numéricos con datos reales
            $items = array_filter($data, function($item, $key) {
                return is_numeric($key) && is_array($item) && !empty($item);
            }, ARRAY_FILTER_USE_BOTH);
            $items = array_values($items); // Reindexar
        }

        // Convertir a objetos
        $formatted = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $formatted[] = (object) $item;
            } else {
                $formatted[] = $item;
            }
        }

        // Calcular total de páginas
        $max_pages = ($total > 0 && $per_page > 0) ? ceil($total / $per_page) : 1;

        return [
            'items' => $formatted,
            'total' => $total ?: count($formatted),
            'page' => $page,
            'per_page' => $per_page,
            'max_pages' => $max_pages,
        ];
    }

    /**
     * Obtener IP del cliente para Inmovilla
     * IMPORTANTE: Retorna 127.0.0.1 como fallback para evitar errores de "NECESITAMOS RECIBIR LA IP"
     */
    private function get_client_ip() {
        // Para Inmovilla, siempre usar 127.0.0.1 como fallback por defecto
        // Esto evita problemas con IPs no autorizadas o no detectadas correctamente
        return '127.0.0.1';
    }
}

/**
 * Filtro para ejecutar queries de Inmovilla en Bricks
 * Con soporte completo de paginación, filtros y ordenamiento
 */
add_filter('bricks/query/run', function($results, $query) {
    $object_type = $query->object_type ?? '';

    // Verificar si es un source de Inmovilla
    // IMPORTANTE: El orden importa - primero el prefijo más largo
    $source_key = str_replace(['snap_source_', 'source_'], '', $object_type);

    if (strpos($source_key, 'inmovilla_') !== 0 && strpos($source_key, 'inmovilla-') !== 0) {
        return $results;
    }

    // Normalizar key (guiones a guiones bajos)
    $source_key = str_replace('-', '_', $source_key);

    $sources = get_option('bricks_api_sources', []);

    if (!isset($sources[$source_key])) {
        return $results;
    }

    $source_config = $sources[$source_key];
    $handler = Inmovilla_Query_Handler::get_instance();

    // Obtener parámetros de la query de Bricks
    $per_page = $query->query_vars['posts_per_page'] ?? 20;
    if ($per_page < 1 || $per_page > 100) {
        $per_page = 20;
    }

    // Determinar la página actual
    // Si hay filtros activos en la URL, resetear a página 1
    // (evita pedir pos=41 cuando el filtro reduce resultados a 2)
    $current_page = $handler->get_current_page();
    $url_filters = $handler->capture_filters_from_url();
    if (!empty($url_filters) && $current_page > 1) {
        // Verificar si 'paged' está explícitamente en la URL
        // Si hay filtros pero no hay paged explícito, usar página 1
        if (empty($_GET['paged'])) {
            $current_page = 1;
        }
    }

    $query_args = [
        'page' => $current_page,
        'per_page' => $per_page,
        'filters' => [],
        'order' => '',
    ];

    // Detectar si estamos en página de detalle (single)
    global $bricks_api_current_item_id;
    if (!empty($bricks_api_current_item_id) && isset($bricks_api_current_item_id['value'])) {
        $id_param = $bricks_api_current_item_id['param'] ?? 'ref';
        $id_value = sanitize_text_field($bricks_api_current_item_id['value']);
        if (!empty($id_value)) {
            $query_args['where'] = $id_param . '=' . $id_value;
            $query_args['per_page'] = 1;
            $query_args['skip_url_filters'] = true;
        }
    }

    // Ejecutar consulta
    $result = $handler->execute_query($source_config, $query_args, $query);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('INMOVILLA SOURCES: Resultado - items=' . count($result['items'] ?? []) . ', total=' . ($result['total'] ?? 0) . ', error=' . ($result['error'] ?? 'ninguno'));
    }

    if (!empty($result['error'])) {
        error_log('Inmovilla Query Error: ' . $result['error']);
        return $results;
    }

    // Guardar estado para paginación de Bricks
    if (class_exists('Inmovilla_Query_State')) {
        $query_id = inmovilla_generate_query_id($query);
        Inmovilla_Query_State::set($query_id, [
            'total' => $result['total'],
            'max_pages' => $result['max_pages'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    return $result['items'];
}, 10, 2);

/**
 * Registrar dynamic tags para los sources de Inmovilla
 * Formato unificado: {snap_source_field} para consistencia con el sistema general
 */
add_filter('bricks/dynamic_tags_list', function($tags) {
    $sources = get_option('bricks_api_sources', []);

    foreach ($sources as $key => $source) {
        if (strpos($key, 'inmovilla_') !== 0) continue;

        // Usar el key completo normalizado para el prefijo
        $source_slug = str_replace('_', '-', $key); // inmovilla_inmuebles -> inmovilla-inmuebles
        $group_name = $source['query_type_name'] ?? $source['name'];

        // Campos comunes de Inmovilla
        $fields = [
            'cod_ofer' => 'Código',
            'ref' => 'Referencia',
            'precioinmo' => 'Precio Venta',
            'precioalq' => 'Precio Alquiler',
            'ciudad' => 'Ciudad',
            'zona' => 'Zona',
            'nbtipo' => 'Tipo',
            'habitaciones' => 'Habitaciones',
            'banyos' => 'Baños',
            'm_cons' => 'M² Construidos',
            'm_uties' => 'M² Útiles',
            'm_parcela' => 'M² Parcela',
            'foto' => 'Foto Principal',
            'numfotos' => 'Núm. Fotos',
            'latitud' => 'Latitud',
            'altitud' => 'Longitud',
            'nbconservacion' => 'Conservación',
            'terraza' => 'Terraza',
            'ascensor' => 'Ascensor',
            'piscina_com' => 'Piscina Comunitaria',
            'piscina_prop' => 'Piscina Privada',
            'aire_con' => 'Aire Acondicionado',
            'calefaccion' => 'Calefacción',
            'plaza_gara' => 'Plazas Garaje',
            'agencia' => 'Agencia',
            'detail_url' => 'URL Detalle',
        ];

        foreach ($fields as $field => $label) {
            // Formato unificado: {snap_source-slug_field}
            $tags[] = [
                'name' => '{snap_' . $source_slug . '_' . $field . '}',
                'label' => $label,
                'group' => $group_name,
            ];
        }
    }

    return $tags;
});
