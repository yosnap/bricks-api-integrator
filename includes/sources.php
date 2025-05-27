<?php
/**
 * Query Types Manager for Bricks Builder
 * 
 * Este archivo maneja la creación y gestión de query types para Bricks Query Loop
 * 
 * IMPORTANTE: Los Query Types NO generan dynamic tags para evitar duplicación.
 * Los dynamic tags se generan automáticamente SOLO desde los endpoints configurados.
 * Los Query Types son únicamente para usar en Bricks Query Loop.
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

/**
 * Query Types para Bricks Builder
 */

/**
 * Render the Query Types admin page
 */
function render_api_sources_page() {
    // Check if form was submitted
    if (isset($_POST['submit_api_source']) && check_admin_referer('bricks_api_source_nonce')) {
        save_api_source();
    }
    
    // Check if we're editing an existing source
    $editing = false;
    $source_to_edit = null;
    $source_id_to_edit = '';
    
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['source_id'])) {
        $source_id_to_edit = sanitize_text_field($_GET['source_id']);
        $api_sources = get_option('bricks_api_sources', []);
        
        if (isset($api_sources[$source_id_to_edit])) {
            $editing = true;
            $source_to_edit = $api_sources[$source_id_to_edit];
        }
    }

    // Get existing sources
    $api_sources = get_option('bricks_api_sources', []);
    
    // Get configured endpoints for selection
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Query Types para Bricks Builder', 'bricks-api-integrator'); ?></h1>
        
        <?php if (empty($endpoints)): ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('No API endpoints configured yet. Please add endpoints first.', 'bricks-api-integrator'); ?></p>
                <p><a href="<?php echo admin_url('admin.php?page=bricks-api-integrator'); ?>" class="button"><?php esc_html_e('Configure Endpoints', 'bricks-api-integrator'); ?></a></p>
            </div>
        <?php else: ?>
            <!-- Add New Query Type Form -->
            <div class="api-source-form-container">
                <h2><?php echo $editing ? esc_html__('Editar Query Type', 'bricks-api-integrator') : esc_html__('Añadir Nuevo Query Type', 'bricks-api-integrator'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('bricks_api_source_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="source_name"><?php esc_html_e('Nombre del Query Type', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="source_name" name="source_name" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['name']) : ''; ?>" required>
                                <?php if ($editing): ?>
                                    <input type="hidden" name="source_id" value="<?php echo esc_attr($source_id_to_edit); ?>">
                                <?php endif; ?>
                                <p class="description"><?php esc_html_e('Este nombre aparecerá en el selector de Query Loop de Bricks.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="endpoint_id"><?php esc_html_e('API Endpoint', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="endpoint_id" name="endpoint_id" required>
                                    <option value=""><?php esc_html_e('-- Select Endpoint --', 'bricks-api-integrator'); ?></option>
                                    <?php foreach ($endpoints as $index => $endpoint): ?>
                                        <?php $endpoint_name = isset($endpoint['name']) ? $endpoint['name'] : __('Unnamed Endpoint', 'bricks-api-integrator'); ?>
                                        <option value="<?php echo esc_attr($index); ?>" <?php selected($editing && $source_to_edit['endpoint_id'] == $index); ?>><?php echo esc_html($endpoint_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field_prefix"><?php esc_html_e('Prefijo de Campo', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="field_prefix" name="field_prefix" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['field_prefix']) : ''; ?>" placeholder="snap_">
                                <p class="description"><?php esc_html_e('Prefijo opcional para nombres de campo en Bricks. Dejar vacío para no usar prefijo.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="items_path"><?php esc_html_e('Ruta de Elementos', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="items_path" name="items_path" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['items_path']) : ''; ?>">
                                <p class="description"><?php esc_html_e('Ruta al array de elementos en la respuesta de la API (ej. "data" o "results"). Dejar vacío si los elementos están en el nivel raíz.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dynamic_params"><?php esc_html_e('Parámetros Dinámicos', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <div id="dynamic-params-container">
                                    <?php if ($editing && !empty($source_to_edit['dynamic_params'])): ?>
                                        <?php foreach ($source_to_edit['dynamic_params'] as $param): ?>
                                            <div class="dynamic-param-row">
                                                <input type="text" name="param_names[]" placeholder="<?php esc_attr_e('Nombre del Parámetro (ej. id)', 'bricks-api-integrator'); ?>" class="regular-text" value="<?php echo esc_attr($param['name']); ?>">
                                                <select name="param_sources[]">
                                                    <option value="url" <?php selected($param['source'], 'url'); ?>><?php esc_html_e('Parámetro URL', 'bricks-api-integrator'); ?></option>
                                                    <option value="post" <?php selected($param['source'], 'post'); ?>><?php esc_html_e('ID del Post', 'bricks-api-integrator'); ?></option>
                                                    <option value="user" <?php selected($param['source'], 'user'); ?>><?php esc_html_e('ID del Usuario', 'bricks-api-integrator'); ?></option>
                                                    <option value="static" <?php selected($param['source'], 'static'); ?>><?php esc_html_e('Valor Estático', 'bricks-api-integrator'); ?></option>
                                                </select>
                                                <input type="text" name="param_defaults[]" placeholder="<?php esc_attr_e('Valor por Defecto (opcional)', 'bricks-api-integrator'); ?>" class="regular-text" value="<?php echo esc_attr($param['default']); ?>">
                                                <button type="button" class="button remove-param"><?php esc_html_e('Eliminar', 'bricks-api-integrator'); ?></button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add-param" class="button"><?php esc_html_e('Añadir Parámetro', 'bricks-api-integrator'); ?></button>
                                <p class="description"><?php esc_html_e('Define parámetros para pasar a la API. Para parámetros URL, el valor se tomará de la cadena de consulta de la URL actual.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pagination_type"><?php esc_html_e('Tipo de Paginación', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="pagination_type" name="pagination_type">
                                    <option value="none" <?php selected($editing && $source_to_edit['pagination_type'] == 'none'); ?>><?php esc_html_e('Ninguno', 'bricks-api-integrator'); ?></option>
                                    <option value="page_param" <?php selected($editing && $source_to_edit['pagination_type'] == 'page_param'); ?>><?php esc_html_e('Parámetro de Página', 'bricks-api-integrator'); ?></option>
                                    <option value="offset_param" <?php selected($editing && $source_to_edit['pagination_type'] == 'offset_param'); ?>><?php esc_html_e('Parámetro de Desplazamiento', 'bricks-api-integrator'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr class="pagination-param-row" style="display: none;">
                            <th scope="row">
                                <label for="pagination_param"><?php esc_html_e('Pagination Parameter', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="pagination_param" name="pagination_param" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['pagination_param']) : ''; ?>">
                                <p class="description"><?php esc_html_e('Parameter name for pagination (e.g., "page" or "offset").', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr class="per-page-param-row" style="display: none;">
                            <th scope="row">
                                <label for="per_page_param"><?php esc_html_e('Per Page Parameter', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="per_page_param" name="per_page_param" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['per_page_param']) : ''; ?>">
                                <p class="description"><?php esc_html_e('Parameter name for items per page (e.g., "per_page" or "limit").', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit_api_source" class="button button-primary" value="<?php echo $editing ? esc_attr__('Actualizar Query Type', 'bricks-api-integrator') : esc_attr__('Guardar Query Type', 'bricks-api-integrator'); ?>">
                    </p>
                </form>
            </div>
            
            <!-- Existing Query Types List -->
            <div class="api-sources-list">
                <h2><?php esc_html_e('Query Types Configurados', 'bricks-api-integrator'); ?></h2>
                
                <?php if (empty($api_sources)): ?>
                    <p><?php esc_html_e('No hay query types configurados todavía.', 'bricks-api-integrator'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Nombre del Query Type', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Endpoint', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Prefijo de Campo', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Ruta de Elementos', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Paginación', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Acciones', 'bricks-api-integrator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_sources as $source_id => $source): ?>
                                <tr>
                                    <td><?php echo esc_html($source['name']); ?></td>
                                    <td>
                                        <?php 
                                        $endpoint_index = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
                                        echo isset($endpoints[$endpoint_index]['name']) ? esc_html($endpoints[$endpoint_index]['name']) : esc_html__('Endpoint Desconocido', 'bricks-api-integrator');
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($source['field_prefix'] ?? ''); ?></td>
                                    <td><?php echo esc_html($source['items_path'] ?? ''); ?></td>
                                    <td>
                                        <?php 
                                        $pagination_type = isset($source['pagination_type']) ? $source['pagination_type'] : 'none';
                                        if ($pagination_type === 'none') {
                                            esc_html_e('Ninguno', 'bricks-api-integrator');
                                        } else {
                                            echo esc_html($pagination_type) . ': ' . esc_html($source['pagination_param'] ?? '');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=bricks-api-integrator-sources&action=edit&source_id=' . $source_id)); ?>" class="button button-small"><?php esc_html_e('Edit', 'bricks-api-integrator'); ?></a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=bricks-api-integrator-sources&action=delete&source_id=' . $source_id), 'delete_api_source_' . $source_id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres eliminar este query type?', 'bricks-api-integrator'); ?>')"><?php esc_html_e('Eliminar', 'bricks-api-integrator'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Show/hide pagination fields based on pagination type
        $('#pagination_type').on('change', function() {
            var paginationType = $(this).val();
            if (paginationType === 'none') {
                $('.pagination-param-row, .per-page-param-row').hide();
            } else {
                $('.pagination-param-row, .per-page-param-row').show();
            }
        });
        
        // Handle dynamic parameters
        $('#add-param').on('click', function() {
            var newRow = $('<div class="dynamic-param-row"></div>');
            newRow.html(
                '<input type="text" name="param_names[]" placeholder="Nombre del Parámetro (ej. id)" class="regular-text">' +
                '<select name="param_sources[]">' +
                '  <option value="url">Parámetro URL</option>' +
                '  <option value="post">ID del Post</option>' +
                '  <option value="user">ID del Usuario</option>' +
                '  <option value="static">Valor Estático</option>' +
                '</select>' +
                '<input type="text" name="param_defaults[]" placeholder="Valor por Defecto (opcional)" class="regular-text">' +
                '<button type="button" class="button remove-param">Eliminar</button>'
            );
            $('#dynamic-params-container').append(newRow);
        });
        
        // Remove parameter row
        $(document).on('click', '.remove-param', function() {
            $(this).closest('.dynamic-param-row').remove();
        });
        
        // Check if we need to redirect after saving
        <?php 
        $redirect_after_save = get_option('bricks_api_redirect_after_save', false);
        if ($redirect_after_save) {
            // Clear the flag
            delete_option('bricks_api_redirect_after_save');
            echo "window.location.href = '" . admin_url('admin.php?page=bricks-api-integrator-sources') . "';";
        }
        ?>
    });
    </script>
    <?php
}

/**
 * Save Query Type
 */
function save_api_source() {
    // Validate and sanitize inputs
    $source_name = sanitize_text_field($_POST['source_name']);
    $endpoint_id = sanitize_text_field($_POST['endpoint_id']);
    $field_prefix = sanitize_text_field($_POST['field_prefix']);
    $items_path = sanitize_text_field($_POST['items_path']);
    $pagination_type = sanitize_text_field($_POST['pagination_type']);
    $pagination_param = sanitize_text_field($_POST['pagination_param'] ?? '');
    $per_page_param = sanitize_text_field($_POST['per_page_param'] ?? '');
    
    // Process dynamic parameters
    $dynamic_params = [];
    if (isset($_POST['param_names']) && is_array($_POST['param_names'])) {
        $param_names = array_map('sanitize_text_field', $_POST['param_names']);
        $param_sources = isset($_POST['param_sources']) ? array_map('sanitize_text_field', $_POST['param_sources']) : [];
        $param_defaults = isset($_POST['param_defaults']) ? array_map('sanitize_text_field', $_POST['param_defaults']) : [];
        
        foreach ($param_names as $index => $name) {
            if (!empty($name)) {
                $dynamic_params[] = [
                    'name' => $name,
                    'source' => isset($param_sources[$index]) ? $param_sources[$index] : 'url',
                    'default' => isset($param_defaults[$index]) ? $param_defaults[$index] : ''
                ];
            }
        }
    }
    
    // Get existing sources
    $api_sources = get_option('bricks_api_sources', []);
    
    // Check if we're editing an existing query type
    $editing = isset($_POST['source_id']) && !empty($_POST['source_id']);
    $source_id = $editing ? sanitize_text_field($_POST['source_id']) : 'query_type_' . time();
    
    // Prepare query type data
    $source_data = [
        'name' => $source_name,
        'endpoint_id' => $endpoint_id,
        'field_prefix' => $field_prefix,
        'items_path' => $items_path,
        'pagination_type' => $pagination_type,
        'pagination_param' => $pagination_param,
        'per_page_param' => $per_page_param,
        'dynamic_params' => $dynamic_params,
        'query_type_name' => !empty($field_prefix) ? $field_prefix . $source_name : $source_name // Aplicar prefijo al nombre del query type
    ];
    
    // Add or update query type
    $api_sources[$source_id] = $source_data;
    
    // Save updated query types
    update_option('bricks_api_sources', $api_sources);
    
    // Add success message
    add_settings_error(
        'bricks_api_sources',
        $editing ? 'source_updated' : 'source_added',
        $editing ? __('Query Type actualizado correctamente.', 'bricks-api-integrator') : __('Query Type añadido correctamente.', 'bricks-api-integrator'),
        'updated'
    );
    
    // Set a flag to trigger JavaScript redirect instead of PHP redirect
    if ($editing) {
        // We'll use JavaScript to redirect instead of wp_redirect
        update_option('bricks_api_redirect_after_save', true);
    }
}

/**
 * Handle delete action for query types
 */
function handle_api_source_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'bricks-api-integrator-sources') {
        return;
    }
    
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['source_id'])) {
        $source_id = sanitize_text_field($_GET['source_id']);
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_api_source_' . $source_id)) {
            wp_die(__('Security check failed.', 'bricks-api-integrator'));
        }
        
        // Get existing query types
        $api_sources = get_option('bricks_api_sources', []);
        
        // Remove the query type
        if (isset($api_sources[$source_id])) {
            unset($api_sources[$source_id]);
            update_option('bricks_api_sources', $api_sources);
            
            // Add success message
            add_settings_error(
                'bricks_api_sources',
                'source_deleted',
                __('Query Type eliminado correctamente.', 'bricks-api-integrator'),
                'updated'
            );
        }
        
        // Redirect to remove action from URL
        wp_redirect(admin_url('admin.php?page=bricks-api-integrator-sources'));
        exit;
    }
}
add_action('admin_init', 'handle_api_source_actions');

/**
 * Register Query Types with Bricks Query Loop
 */
function register_api_sources_with_bricks($sources) {
    // Get configured Query Types
    $api_sources = get_option('bricks_api_sources', []);
    
    // Log para depuración
    error_log('Registrando Query Types con Bricks:');
    error_log('Query Types disponibles: ' . print_r($api_sources, true));
    
    if (empty($api_sources)) {
        error_log('No hay Query Types configurados');
        return $sources;
    }
    
    // Add each Query Type to Bricks sources
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        
        $sources[$source_id] = [
            'name'  => $display_name,
            'class' => 'Bricks_API_Source_Query', // Custom query class we'll create
        ];
        error_log('Registrando Query Type: ' . $source_id . ' - ' . $display_name);
    }
    
    // Log para depuración
    error_log('Sources finales registrados con Bricks: ' . print_r($sources, true));
    
    return $sources;
}
// TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
// add_filter('bricks/query/sources', 'register_api_sources_with_bricks');

/**
 * Create custom query class for Query Types
 */
function register_api_source_query_class() {
    // Verificar si Bricks está activo
    if (!defined('BRICKS_VERSION')) {
        error_log('Bricks no está activo, no se puede registrar Bricks_API_Source_Query');
        return;
    }
    
    // Verificar si la clase Bricks_Query_Provider existe
    if (!class_exists('Bricks_Query_Provider')) {
        error_log('Bricks_Query_Provider no existe, no se puede registrar Bricks_API_Source_Query');
        return;
    }
    
    error_log('Registrando clase Bricks_API_Source_Query');
    
    class Bricks_API_Source_Query extends Bricks_Query_Provider {
        public function __construct() {
            $this->name = 'api_source';
            $this->label = esc_html__('Query Type API', 'bricks-api-integrator');
            
            // Set controls (pagination, etc.)
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
                    'max'         => 1440, // 24 hours
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
        
        /**
         * Get Query Types as options for the select control
         */
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
        
        /**
         * Get query results
         */
        public function get_results($query_args = []) {
            // Get the selected source ID from query settings
            $source_id = isset($query_args['source_id']) ? $query_args['source_id'] : '';
            
            if (empty($source_id) || $source_id === 'no_sources') {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('No se ha seleccionado ningún Query Type o no hay Query Types disponibles.', 'bricks-api-integrator'),
                ];
            }
            
            // Get Query Types
            $api_sources = get_option('bricks_api_sources', []);
            
            if (!isset($api_sources[$source_id])) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('El Query Type seleccionado no se encontró.', 'bricks-api-integrator'),
                ];
            }
            
            $source = $api_sources[$source_id];
            
            // Check if we should use cache
            $use_cache = isset($query_args['cache_results']) && $query_args['cache_results'];
            $cache_duration = isset($query_args['cache_duration']) ? intval($query_args['cache_duration']) * 60 : 900; // Default 15 minutes in seconds
            
            // Generate a cache key based on the query
            $cache_key = 'bricks_api_' . md5(serialize([$source_id, $query_args, $_GET]));
            
            // Try to get cached results
            if ($use_cache) {
                $cached_results = get_transient($cache_key);
                if ($cached_results !== false) {
                    return $cached_results;
                }
            }
            
            // Get the endpoint data
            $endpoints = get_option('bricks_api_endpoints', []);
            $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
            
            if (!isset($endpoints[$endpoint_id])) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('Endpoint configuration not found.', 'bricks-api-integrator'),
                ];
            }
            
            $endpoint = $endpoints[$endpoint_id];
            $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
            
            if (empty($endpoint_url)) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('Endpoint URL is empty.', 'bricks-api-integrator'),
                ];
            }
            
            // Handle pagination
            $pagination_type = isset($source['pagination_type']) ? $source['pagination_type'] : 'none';
            $pagination_param = isset($source['pagination_param']) ? $source['pagination_param'] : '';
            $per_page_param = isset($source['per_page_param']) ? $source['per_page_param'] : '';
            
            // Get pagination settings from Bricks Query Loop
            $use_pagination = isset($query_args['pagination']) && $query_args['pagination'];
            $page = 1;
            $items_per_page = isset($query_args['items_per_page']) ? intval($query_args['items_per_page']) : 10;
            
            // Check if we're on a paginated page
            if ($use_pagination) {
                // Get current page from query var or default to 1
                $page_key = isset($query_args['page_key']) ? $query_args['page_key'] : 'page';
                $current_page = get_query_var($page_key, 1);
                
                // Fallback to 'paged' query var
                if ($current_page <= 1) {
                    $current_page = get_query_var('paged', 1);
                }
                
                // Fallback to 'page' query var
                if ($current_page <= 1) {
                    $current_page = get_query_var('page', 1);
                }
                
                // Fallback to $_GET parameter
                if ($current_page <= 1 && isset($_GET[$page_key])) {
                    $current_page = intval($_GET[$page_key]);
                }
                
                $page = max(1, $current_page);
            } else {
                // If not using pagination, use page from query args if provided
                $page = isset($query_args['page']) ? intval($query_args['page']) : 1;
            }
            
            // Build request URL with pagination parameters
            $request_url = $endpoint_url;
            
            // Process dynamic parameters from source configuration
            $dynamic_params = isset($source['dynamic_params']) ? $source['dynamic_params'] : [];
            if (!empty($dynamic_params)) {
                foreach ($dynamic_params as $param) {
                    $param_name = $param['name'];
                    $param_source = $param['source'];
                    $param_default = $param['default'];
                    $param_value = $param_default; // Default value
                    
                    // Get parameter value based on source
                    switch ($param_source) {
                        case 'url':
                            // Get from URL parameter
                            if (isset($_GET[$param_name])) {
                                $param_value = sanitize_text_field($_GET[$param_name]);
                            }
                            break;
                            
                        case 'post':
                            // Get current post ID
                            global $post;
                            if (isset($post) && is_object($post)) {
                                $param_value = $post->ID;
                            }
                            break;
                            
                        case 'user':
                            // Get current user ID
                            $current_user = wp_get_current_user();
                            if ($current_user->exists()) {
                                $param_value = $current_user->ID;
                            }
                            break;
                            
                        case 'static':
                            // Use the default value
                            $param_value = $param_default;
                            break;
                    }
                    
                    // Add parameter to URL if value is not empty
                    if ($param_value !== '') {
                        $request_url = add_query_arg($param_name, $param_value, $request_url);
                    }
                }
            }
            
            // Process dynamic parameters from query settings (override source configuration)
            if (isset($query_args['dynamic_params']) && is_array($query_args['dynamic_params'])) {
                foreach ($query_args['dynamic_params'] as $param) {
                    if (isset($param['param_name']) && isset($param['param_value']) && !empty($param['param_name'])) {
                        $param_name = $param['param_name'];
                        $param_value = $param['param_value'];
                        
                        // Support for dynamic data in parameter values
                        if (strpos($param_value, '{') !== false && strpos($param_value, '}') !== false) {
                            // This might be a dynamic tag, let Bricks handle it
                            if (function_exists('bricks_render_dynamic_data')) {
                                $param_value = bricks_render_dynamic_data($param_value);
                            }
                        }
                        
                        // Add parameter to URL if value is not empty
                        if ($param_value !== '') {
                            $request_url = add_query_arg($param_name, $param_value, $request_url);
                        }
                    }
                }
            }
            
            // Add pagination parameters
            if ($pagination_type === 'page_param' && !empty($pagination_param)) {
                $request_url = add_query_arg($pagination_param, $page, $request_url);
            } elseif ($pagination_type === 'offset_param' && !empty($pagination_param)) {
                $offset = ($page - 1) * $items_per_page;
                $request_url = add_query_arg($pagination_param, $offset, $request_url);
            }
            
            if (!empty($per_page_param)) {
                $request_url = add_query_arg($per_page_param, $items_per_page, $request_url);
            }
            
            // Prepare request arguments
            $args = [];
            
            // Add authentication if needed
            $auth_type = isset($endpoint['auth_type']) ? $endpoint['auth_type'] : 'none';
            
            if ($auth_type === 'token') {
                $token = isset($endpoint['token']) ? $endpoint['token'] : '';
                $args['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                ];
            } elseif ($auth_type === 'basic') {
                $user = isset($endpoint['basic_user']) ? $endpoint['basic_user'] : '';
                $password = isset($endpoint['basic_password']) ? $endpoint['basic_password'] : '';
                $args['headers'] = [
                    'Authorization' => 'Basic ' . base64_encode("$user:$password"),
                ];
            }
            
            // Log request information for debugging
            error_log('API Request URL: ' . $request_url);
            error_log('API Request Args: ' . print_r($args, true));
            error_log('Source ID: ' . $source_id);
            error_log('Source Config: ' . print_r($source, true));
            error_log('Query Args: ' . print_r($query_args, true));
            
            // Make the API request
            $response = wp_remote_get($request_url, $args);
            
            if (is_wp_error($response)) {
                error_log('API Request Error: ' . $response->get_error_message());
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => $response->get_error_message(),
                ];
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                error_log('API Request Error: Status code ' . $status_code);
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => sprintf(esc_html__('API returned error code: %d', 'bricks-api-integrator'), $status_code),
                ];
            }
            
            $body = wp_remote_retrieve_body($response);
            error_log('API Response Body: ' . substr($body, 0, 1000) . (strlen($body) > 1000 ? '... (truncated)' : ''));
            
            $data = json_decode($body, true);
            error_log('API Response Data (decoded): ' . print_r(array_slice($data, 0, 10, true), true) . (count($data) > 10 ? '... (truncated)' : ''));
            
            if (empty($data)) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('API returned empty or invalid data.', 'bricks-api-integrator'),
                ];
            }
            
            // Extract items based on items path
            $items_path = isset($source['items_path']) ? $source['items_path'] : '';
            $items = $data;
            
            if (!empty($items_path)) {
                $path_parts = explode('.', $items_path);
                
                foreach ($path_parts as $part) {
                    if (isset($items[$part])) {
                        $items = $items[$part];
                    } else {
                        // Path not found
                        return [
                            'count' => 0,
                            'items' => [],
                            'error' => sprintf(esc_html__('Items path "%s" not found in API response.', 'bricks-api-integrator'), $items_path),
                        ];
                    }
                }
            }
            
            // Ensure items is an array
            if (!is_array($items)) {
                $items = [$items];
            }
            
            // Add field prefix to item keys if specified
            $field_prefix = isset($source['field_prefix']) ? $source['field_prefix'] : '';
            
            if (!empty($field_prefix)) {
                foreach ($items as $key => $item) {
                    if (is_array($item)) {
                        $prefixed_item = [];
                        
                        foreach ($item as $item_key => $item_value) {
                            $prefixed_item[$field_prefix . $item_key] = $item_value;
                        }
                        
                        $items[$key] = $prefixed_item;
                    }
                }
            }
            
            // Get total count for pagination
            $total_count = count($items);
            $total_pages = 1;
            
            // Try to get total count from response headers if available
            $response_headers = isset($response['headers']) ? $response['headers'] : null;
            if ($response_headers) {
                // Check for WordPress headers (X-WP-Total, X-WP-TotalPages)
                if (isset($response_headers['X-WP-Total']) && isset($response_headers['X-WP-TotalPages'])) {
                    $total_count = intval($response_headers['X-WP-Total']);
                    $total_pages = intval($response_headers['X-WP-TotalPages']);
                } 
                // Check for standard pagination headers
                elseif (isset($response_headers['X-Pagination-Total']) && isset($response_headers['X-Pagination-Pages'])) {
                    $total_count = intval($response_headers['X-Pagination-Total']);
                    $total_pages = intval($response_headers['X-Pagination-Pages']);
                }
                // Try to find total count in response data
                elseif (isset($data['total']) && is_numeric($data['total'])) {
                    $total_count = intval($data['total']);
                    $total_pages = ceil($total_count / $items_per_page);
                }
                elseif (isset($data['meta']['total']) && is_numeric($data['meta']['total'])) {
                    $total_count = intval($data['meta']['total']);
                    $total_pages = ceil($total_count / $items_per_page);
                }
            }
            
            // Prepare results
            $results = [
                'count' => $total_count,
                'items' => $items,
                'source_id' => $source_id,
                'endpoint_id' => $endpoint_id,
                'request_url' => $request_url,
                'page' => $page,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'items_per_page' => $items_per_page,
            ];
            
            // Cache results if enabled
            if ($use_cache) {
                set_transient($cache_key, $results, $cache_duration);
            }
            
            return $results;
        }
        
        /**
         * Render item data for use in dynamic tags
         */
        public function render_item_data($item, $tag, $post_id, $context) {
            // Check if tag exists in item
            if (isset($item[$tag])) {
                return $item[$tag];
            }
            
            // Check for nested data using dot notation
            if (strpos($tag, '.') !== false) {
                $parts = explode('.', $tag);
                $value = $item;
                
                foreach ($parts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        return '';
                    }
                }
                
                return $value;
            }
            
            // Handle special tags
            if ($tag === 'api_url') {
                return isset($item['_api_url']) ? $item['_api_url'] : '';
            }
            
            if ($tag === 'api_id') {
                return isset($item['id']) ? $item['id'] : (isset($item['ID']) ? $item['ID'] : '');
            }
            
            // Return empty string if tag not found
            return '';
        }
    }
}
// TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
// add_action('init', 'register_api_source_query_class');

/**
 * Register sample data for Bricks Query Loop preview
 */
function register_api_source_sample_data() {
    // Only run in Bricks builder context
    if (!function_exists('bricks_is_builder') || !bricks_is_builder()) {
        return;
    }
    
    // TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
    // add_filter('bricks/query/sample_results', 'get_api_source_sample_data', 10, 2);
}
// TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
// add_action('init', 'register_api_source_sample_data');

/**
 * Get sample data for API sources in Bricks builder
 */
function get_api_source_sample_data($results, $query_obj) {
    // Only process API sources
    if (!isset($query_obj->settings['source']) || strpos($query_obj->settings['source'], 'api_source') !== 0) {
        return $results;
    }
    
    // Get the source ID
    $source_id = isset($query_obj->settings['source_id']) ? $query_obj->settings['source_id'] : '';
    
    if (empty($source_id)) {
        return $results;
    }
    
    // Get API sources
    $api_sources = get_option('bricks_api_sources', []);
    
    if (!isset($api_sources[$source_id])) {
        return $results;
    }
    
    $source = $api_sources[$source_id];
    
    // Get the endpoint data
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
    
    if (!isset($endpoints[$endpoint_id])) {
        return $results;
    }
    
    $endpoint = $endpoints[$endpoint_id];
    $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
    
    if (empty($endpoint_url)) {
        return $results;
    }
    
    // Get API data
    $data = get_api_data($endpoint_url, $endpoint);
    
    if (empty($data) || !is_array($data)) {
        return $results;
    }
    
    // Extract items based on items path
    $items_path = isset($source['items_path']) ? $source['items_path'] : '';
    $items = $data;
    
    if (!empty($items_path)) {
        $path_parts = explode('.', $items_path);
        
        foreach ($path_parts as $part) {
            if (isset($items[$part])) {
                $items = $items[$part];
            } else {
                // Path not found
                return $results;
            }
        }
    }
    
    // Ensure items is an array
    if (!is_array($items)) {
        $items = [$items];
    }
    
    // Add field prefix to item keys if specified
    $field_prefix = isset($source['field_prefix']) ? $source['field_prefix'] : '';
    
    if (!empty($field_prefix)) {
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $prefixed_item = [];
                
                foreach ($item as $item_key => $item_value) {
                    $prefixed_item[$field_prefix . $item_key] = $item_value;
                }
                
                $items[$key] = $prefixed_item;
            }
        }
    }
    
    // Limit to 10 items for preview
    $items = array_slice($items, 0, 10);
    
    // Add request URL to each item
    foreach ($items as $key => $item) {
        if (is_array($item)) {
            $items[$key]['_api_url'] = $endpoint_url;
        }
    }
    
    return [
        'count' => count($items),
        'items' => $items,
    ];
}
