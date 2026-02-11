<?php
/**
 * Direct API Source Integration for Bricks Query Loop
 * 
 * Este archivo proporciona una integración directa con el Query Loop de Bricks
 * siguiendo el enfoque de Bricksforge
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

/**
 * Clase principal para la integración con el Query Loop de Bricks
 */
class Bricks_API_Integrator_Query {
    
    /**
     * Prefijo para nuestros tipos de query
     */
    private $query_prefix = 'bai_api_source-';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrar filtros para la integración con Bricks
        add_filter('bricks/query/loop_control_options', [$this, 'add_query_loop_sources']);
        add_filter('bricks/query/run', [$this, 'run_query'], 10, 2);
        add_filter('bricks/query/control_groups', [$this, 'add_api_source_control_group']);
        
    }
    
    /**
     * Agregar nuestros API Sources al selector del Query Loop de Bricks
     */
    public function add_query_loop_sources($control_options) {
        // Obtener los API Sources configurados
        $api_sources = get_option('bricks_api_sources', []);
        
        // Si no hay API Sources configurados, agregar uno de prueba
        if (empty($api_sources)) {
            $api_sources['debug_api_source'] = [
                'name' => 'API Source de Prueba',
                'endpoint_id' => '0',
            ];
        }
        
        // Crear una categoría para nuestros API Sources
        if (!isset($control_options['queryTypes'])) {
            $control_options['queryTypes'] = [];
        }
        
        // Agregar cada API Source como un tipo de query
        foreach ($api_sources as $source_id => $source) {
            $source_name = isset($source['name']) ? $source['name'] : 'API Source ' . $source_id;
            $control_options['queryTypes'][$this->query_prefix . $source_id] = 'API: ' . $source_name;
        }
        
        return $control_options;
    }
    
    /**
     * Procesar las consultas de nuestros API Sources
     */
    public function run_query($results, $query_obj) {
        // Obtener el tipo de query
        $object_type = isset($query_obj->object_type) ? $query_obj->object_type : '';
        
        // Verificar si es uno de nuestros API Sources
        if (strpos($object_type, $this->query_prefix) !== 0) {
            return $results;
        }
        
        // Extraer el ID del API Source
        $source_id = str_replace($this->query_prefix, '', $object_type);
        
        // Obtener los API Sources configurados
        $api_sources = get_option('bricks_api_sources', []);
        
        if (!isset($api_sources[$source_id])) {
            return [
                'count' => 0,
                'items' => [],
                'error' => 'API Source no encontrado: ' . $source_id,
            ];
        }
        
        $source = $api_sources[$source_id];

        // Obtener el endpoint
        $endpoints = get_option('bricks_api_endpoints', []);
        $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
        
        if (!isset($endpoints[$endpoint_id])) {
            return [
                'count' => 0,
                'items' => [],
                'error' => 'Endpoint no encontrado: ' . $endpoint_id,
            ];
        }
        
        $endpoint = $endpoints[$endpoint_id];
        $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
        
        if (empty($endpoint_url)) {
            return [
                'count' => 0,
                'items' => [],
                'error' => 'URL del endpoint vacía',
            ];
        }
        
        // Procesar parámetros dinámicos
        $dynamic_params = isset($source['dynamic_params']) ? $source['dynamic_params'] : [];
        $query_params = [];
        
        foreach ($dynamic_params as $param) {
            $param_name = isset($param['name']) ? $param['name'] : '';
            $param_source = isset($param['source']) ? $param['source'] : 'static';
            $param_default = isset($param['default']) ? $param['default'] : '';
            
            if (empty($param_name)) {
                continue;
            }
            
            // Por ahora solo soportamos valores estáticos
            if ($param_source === 'static') {
                $query_params[$param_name] = $param_default;
            }
        }
        
        // Construir la URL completa con los parámetros
        if (!empty($query_params)) {
            $endpoint_url = add_query_arg($query_params, $endpoint_url);
        }
        
        // Hacer la solicitud a la API
        $args = [];
        
        // Agregar autenticación si está configurada
        $auth_type = isset($endpoint['auth_type']) ? $endpoint['auth_type'] : 'none';
        
        if ($auth_type === 'token') {
            $token = isset($endpoint['token']) ? $endpoint['token'] : '';
            if (!empty($token)) {
                $args['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                ];
            }
        } elseif ($auth_type === 'basic') {
            $username = isset($endpoint['username']) ? $endpoint['username'] : '';
            $password = isset($endpoint['password']) ? $endpoint['password'] : '';
            if (!empty($username) && !empty($password)) {
                $args['headers'] = [
                    'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                ];
            }
        }
        
        // Hacer la solicitud
        $response = wp_remote_get($endpoint_url, $args);
        
        if (is_wp_error($response)) {
            return [
                'count' => 0,
                'items' => [],
                'error' => 'Error en la solicitud: ' . $response->get_error_message(),
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return [
                'count' => 0,
                'items' => [],
                'error' => 'Código de respuesta no válido: ' . $response_code,
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'count' => 0,
                'items' => [],
                'error' => 'Error al decodificar JSON: ' . json_last_error_msg(),
            ];
        }
        
        // --- Normalizar la respuesta: siempre array indexado para renderizado ---
        if (is_object($data)) {
            $data = [ (array)$data ];
        } elseif (is_array($data) && count($data) > 0 && array_keys($data) !== range(0, count($data) - 1)) {
            // Es un array asociativo (objeto plano)
            $data = [ $data ];
        }
        
        // Procesar los datos según la configuración del API Source
        $items = [];
        
        // Extraer los items del resultado
        $items_path = isset($source['items_path']) ? $source['items_path'] : '';
        
        if (!empty($items_path)) {
            $items_data = $this->extract_data_by_path($data, $items_path);
            
            if (is_array($items_data)) {
                $items = $items_data;
            } else {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => 'No se pudieron extraer los items del resultado',
                ];
            }
        } else {
            // Si no hay path de items, usar el resultado completo como un solo item
            $items = [$data];
        }
        
        // Aplicar mapeo de campos
        $field_mapping = isset($source['field_mapping']) ? $source['field_mapping'] : [];
        
        if (!empty($field_mapping)) {
            foreach ($items as &$item) {
                $mapped_item = [];
                
                foreach ($field_mapping as $source_field => $target_field) {
                    if (isset($item[$source_field])) {
                        $mapped_item[$target_field] = $item[$source_field];
                    }
                }
                
                // Reemplazar el item original con el item mapeado
                if (!empty($mapped_item)) {
                    $item = $mapped_item;
                }
            }
        }

        // Aplicar transformaciones de campos
        $field_transformers = isset($endpoint['field_transformers']) ? $endpoint['field_transformers'] : [];

        if (!empty($field_transformers)) {
            require_once plugin_dir_path(__FILE__) . 'field-extractor.php';

            foreach ($items as &$item) {
                foreach ($field_transformers as $transformer) {
                    $field_name = $transformer['field'] ?? '';

                    if (empty($field_name) || !isset($item[$field_name])) {
                        continue;
                    }

                    // Aplicar transformación al campo
                    $item[$field_name] = bricks_api_apply_field_transform(
                        $field_name,
                        $item[$field_name],
                        [$transformer]
                    );
                }
            }
            unset($item);
        }

        // Devolver los resultados
        return [
            'count' => count($items),
            'items' => $items,
            'source_id' => $source_id,
            'endpoint_id' => $endpoint_id,
            'endpoint_url' => $endpoint_url,
        ];
    }
    
    /**
     * Extraer datos de un array según un path
     */
    private function extract_data_by_path($data, $path) {
        if (empty($path)) {
            return $data;
        }
        
        $parts = explode('.', $path);
        $current = $data;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return null;
            }
            
            $current = $current[$part];
        }
        
        return $current;
    }
}

/**
 * Inicializar la integración con el Query Loop de Bricks
 */
function register_direct_api_sources() {
    // Evitar múltiples registros
    static $registered = false;
    if ($registered) {
        return;
    }
    
    // Verificar si estamos en el administrador o en el frontend
    $is_admin = is_admin();
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    $is_builder = isset($_GET['bricks']) && $_GET['bricks'] === 'run';
    
    // Solo continuar si es necesario
    if (!$is_admin && !$is_ajax && !$is_builder) {
        return;
    }
    
    // Marcar como registrado
    $registered = true;
    
    // Crear la instancia de la clase
    $query_integrator = new Bricks_API_Integrator_Query();
    
    // Registrar los filtros con prioridad alta para asegurar que se ejecuten
    add_filter('bricks/query/loop_control_options', [$query_integrator, 'add_query_loop_sources'], 5, 1);
    add_filter('bricks/query/run', [$query_integrator, 'run_query'], 5, 2);
    add_filter('bricks/query/control_groups', [$query_integrator, 'add_api_source_control_group'], 5, 1);
    add_filter('bricks/query/sources', 'add_api_sources_to_bricks_loop', 5, 1);
    add_filter('bricks/query/loop_results', 'process_api_source_results', 5, 2);
    
}

// Registrar en el hook de Bricks para asegurar que esté disponible cuando se necesite
add_action('bricks/before_load', 'register_direct_api_sources', 5);

// También registrar en init para asegurar que se ejecute en el frontend
add_action('init', 'register_direct_api_sources', 5);

/**
 * Agregar API Sources al selector de Query Loop de Bricks
 */
function add_api_sources_to_bricks_loop($sources) {
    // Inicializar el array de fuentes si no existe
    if (!is_array($sources)) {
        $sources = [];
    }
    
    // Obtener los API Sources configurados
    $api_sources = get_option('bricks_api_sources', []);
    
    // Si no hay API Sources configurados, agregar uno de prueba
    if (empty($api_sources)) {
        $api_sources['debug_api_source'] = [
            'name' => 'API Source de Prueba',
            'endpoint_id' => '0',
        ];
    }
    
    // Crear la estructura que Bricks espera para los sources
    // Bricks espera un array donde las claves son los tipos de source
    // y los valores son arrays con 'name' y 'sources'
    
    // 1. Primero, crear una categoría 'api' que agrupará todos los API Sources
    $sources['api'] = [
        'name' => 'API',
        'sources' => [],
    ];
    
    // 2. Agregar cada API Source como una opción dentro de la categoría 'api'
    foreach ($api_sources as $source_id => $source) {
        $source_name = isset($source['name']) ? $source['name'] : 'API Source ' . $source_id;
        $sources['api']['sources'][$source_id] = $source_name;
    }
    
    // 3. También agregar cada API Source como un tipo de query independiente
    // Esto es necesario para que Bricks los muestre en el selector principal
    foreach ($api_sources as $source_id => $source) {
        $source_name = isset($source['name']) ? $source['name'] : 'API Source ' . $source_id;
        
        $sources[$source_id] = [
            'name' => $source_name,
            'sources' => [
                $source_id => $source_name, // Usar el mismo ID y nombre para simplificar
            ],
        ];
        
    }
    
    // Guardar una copia de los sources en una opción para poder verlos en el debug
    update_option('bricks_api_debug_sources', $sources, false);
    
    // Agregar un mensaje en el admin footer para notificar que se ejecutó el filtro
    add_action('admin_footer', function() use ($sources) {
        if (current_user_can('manage_options')) {
            echo '<div style="position: fixed; bottom: 0; right: 0; background: #fff; border: 1px solid #ccc; padding: 10px; z-index: 9999;">';
            echo '<h4>API Sources Debug</h4>';
            echo '<p>El filtro bricks/query/sources se ejecutó a las ' . date('H:i:s') . '</p>';
            echo '<p>Se registraron ' . count($sources) . ' sources</p>';
            echo '</div>';
        }
    });
    
    // Agregar debug para ver la estructura completa en el footer del sitio
    add_action('wp_footer', function() use ($sources) {
        if (current_user_can('manage_options') && isset($_GET['debug_api_sources'])) {
            echo '<div style="position: fixed; bottom: 0; right: 0; background: white; padding: 20px; border: 1px solid #ccc; max-width: 500px; max-height: 400px; overflow: auto; z-index: 9999;">';
            echo '<h3>API Sources para Query Loop</h3>';
            echo '<pre>' . print_r($sources, true) . '</pre>';
            echo '</div>';
        }
    });
    
    return $sources;
}

/**
 * Procesar los resultados para nuestros API Sources
 */
function process_api_source_results($results, $settings) {
    return $results;
}

/**
 * Registrar campos personalizados para el Query Loop
 */
function register_api_source_query_fields() {
    // Solo ejecutar si Bricks está activo
    if (!defined('BRICKS_VERSION')) {
        return;
    }
    
    add_filter('bricks/query/control_groups', 'add_api_source_control_group');
}
add_action('init', 'register_api_source_query_fields', 20);

/**
 * Agregar grupo de controles para API Sources
 */
function add_api_source_control_group($control_groups) {
    // Obtener los API Sources configurados
    $api_sources = get_option('bricks_api_sources', []);
    
    // Preparar las opciones para el selector de API Sources
    $api_source_options = [
        '' => 'Seleccionar API Source', // Opción por defecto
    ];
    
    foreach ($api_sources as $source_id => $source) {
        $source_name = isset($source['name']) ? $source['name'] : 'API Source ' . $source_id;
        $api_source_options[$source_id] = $source_name;
    }
    
    // Si no hay API Sources configurados, agregar uno de prueba
    if (count($api_source_options) === 1) { // Solo la opción por defecto
        $api_source_options['debug_api_source'] = 'API Source de Prueba';
    }
    
    // Asegurarse de que exista el grupo 'query'
    if (!isset($control_groups['query'])) {
        $control_groups['query'] = [
            'title' => 'Query',
            'tab' => 'content',
            'controls' => [],
        ];
    }
    
    // Asegurarse de que exista el control 'source' en el grupo 'query'
    if (!isset($control_groups['query']['controls']['source'])) {
        $control_groups['query']['controls']['source'] = [
            'label' => 'Source',
            'type' => 'select',
            'options' => [],
            'inline' => true,
            'placeholder' => 'Select source',
            'clearable' => false,
        ];
    }
    
    // Agregar 'api' como una opción en el control 'source' si no existe
    if (!isset($control_groups['query']['controls']['source']['options']['api'])) {
        $control_groups['query']['controls']['source']['options']['api'] = 'API';
    }
    
    // Agregar el control para seleccionar el API Source específico
    // Este control solo se mostrará cuando source=api
    $control_groups['query']['controls']['apiSource'] = [
        'label' => 'API Source',
        'type' => 'select',
        'options' => $api_source_options,
        'inline' => true,
        'placeholder' => 'Select API Source',
        'required' => [
            ['source', '=', 'api']
        ],
        'description' => 'Selecciona el API Source configurado',
    ];
    
    // Agregar controles adicionales para el API Source seleccionado
    $control_groups['query']['controls']['apiSourceParams'] = [
        'label' => 'Parámetros',
        'type' => 'repeater',
        'fields' => [
            'param' => [
                'label' => 'Parámetro',
                'type' => 'text',
                'inline' => true,
            ],
            'value' => [
                'label' => 'Valor',
                'type' => 'text',
                'inline' => true,
            ],
        ],
        'required' => [
            ['source', '=', 'api'],
            ['apiSource', '!=', '']
        ],
        'description' => 'Agrega parámetros adicionales para la consulta',
    ];
    
    // Mantener compatibilidad con versiones anteriores
    $control_groups['api'] = [
        'title' => 'API',
        'tab' => 'content',
        'controls' => [
            'apiSource' => [
                'label' => 'API Source',
                'type' => 'select',
                'options' => $api_source_options,
                'required' => ['source', '=', 'api'],
            ],
        ],
    ];
    
    return $control_groups;
}