<?php
/**
 * Sources automáticos para Inmovilla
 * Crea query types predefinidos con soporte completo para filtros, paginación y sorting
 */

if (!defined('ABSPATH')) {
    exit;
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
        if (strpos($endpoint['url'], 'apiweb.inmovilla.com') !== false) {
            $inmovilla_endpoint_id = $id;
            break;
        }
    }

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
 * Clase para manejar las consultas de Inmovilla
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
     * Ejecutar consulta a Inmovilla
     */
    public function execute_query($source_config, $query_args = []) {
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

        // Sobrescribir con configuración del source
        $tipo = $source_config['tipo'] ?? 'paginacion';

        // Parámetros de paginación
        $page = isset($query_args['page']) ? intval($query_args['page']) : 1;
        $per_page = isset($query_args['per_page']) ? intval($query_args['per_page']) : 20;
        $pos = (($page - 1) * $per_page) + 1;

        // Filtros (where)
        $where = '';
        if (!empty($query_args['filters'])) {
            $where_parts = [];
            foreach ($query_args['filters'] as $field => $value) {
                if (!empty($value)) {
                    $where_parts[] = $field . '=' . $value;
                }
            }
            $where = implode(' AND ', $where_parts);
        }

        // Orden
        $orden = $query_args['order'] ?? '';

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
        $body = 'param=' . rawurlencode($texto) . '&elDominio=' . urlencode($dominio) . '&ia=' . urlencode($ip) . '&json=1';

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

        if (!empty($items_path) && isset($data[$items_path])) {
            $raw_items = $data[$items_path];

            // Para paginacion, el primer elemento es metadata
            if ($items_path === 'paginacion' && isset($raw_items[0]['total'])) {
                $total = intval($raw_items[0]['total']);
                // Quitar el primer elemento (metadata)
                array_shift($raw_items);
            }

            $items = $raw_items;
        } elseif (is_array($data)) {
            $items = $data;
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

        return [
            'items' => $formatted,
            'total' => $total ?: count($formatted),
            'page' => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        if (function_exists('bricks_api_get_client_ip')) {
            return bricks_api_get_client_ip();
        }

        $proxy_headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP'];
        foreach ($proxy_headers as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $local_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Si es localhost, intentar obtener IP pública
        if (in_array($local_ip, ['127.0.0.1', '::1'])) {
            $cached = get_transient('bricks_api_public_ip');
            if ($cached) return $cached;

            $response = wp_remote_get('https://api.ipify.org', ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($response)) {
                $ip = trim(wp_remote_retrieve_body($response));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    set_transient('bricks_api_public_ip', $ip, HOUR_IN_SECONDS);
                    return $ip;
                }
            }
        }

        return $local_ip;
    }
}

/**
 * Filtro para ejecutar queries de Inmovilla en Bricks
 */
add_filter('bricks/query/run', function($results, $query) {
    $object_type = $query->object_type ?? '';

    // Verificar si es un source de Inmovilla
    $source_key = str_replace('source_', '', $object_type);
    if (strpos($source_key, 'inmovilla_') !== 0) {
        return $results;
    }

    $sources = get_option('bricks_api_sources', []);
    if (!isset($sources[$source_key])) {
        return $results;
    }

    $source_config = $sources[$source_key];

    // Obtener parámetros de la query
    $query_args = [
        'page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
        'per_page' => $query->query_vars['posts_per_page'] ?? 20,
        'filters' => [],
        'order' => '',
    ];

    // Procesar filtros desde URL
    $filter_fields = ['key_tipo', 'key_loca', 'key_zona', 'keyprov', 'keyacci'];
    foreach ($filter_fields as $field) {
        if (!empty($_GET[$field])) {
            $query_args['filters'][$field] = sanitize_text_field($_GET[$field]);
        }
    }

    // Procesar orden desde URL
    if (!empty($_GET['orden'])) {
        $query_args['order'] = sanitize_text_field($_GET['orden']);
    }

    $handler = Inmovilla_Query_Handler::get_instance();
    $result = $handler->execute_query($source_config, $query_args);

    if (!empty($result['error'])) {
        error_log('Inmovilla Query Error: ' . $result['error']);
        return $results;
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
