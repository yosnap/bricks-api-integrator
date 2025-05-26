<?php
/**
 * API Launcher for Bricks Builder
 * 
 * Este archivo maneja la creación y gestión del launcher para los API sources
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

/**
 * Render the API Launcher admin page
 */
function render_api_launcher_page() {
    // Manejar acciones de eliminación directamente desde la URL
    handle_api_launcher_actions();
    
    // Obtener launchers existentes
    $api_launchers = get_option('bricks_api_launchers', []);
    if (!is_array($api_launchers)) {
        $api_launchers = [];
    }
    
    // Obtener endpoints existentes
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }
    
    // Debug: Mostrar launchers existentes
    error_log('Launchers existentes: ' . print_r($api_launchers, true));
    error_log('Endpoints existentes: ' . print_r(array_keys($endpoints), true));
    
    // Check if form was submitted
    $form_submitted = isset($_POST['submit_api_launcher']);
    if ($form_submitted) {
        error_log('Formulario enviado');
        
        // Verificar nonce
        if (check_admin_referer('bricks_api_launcher_nonce')) {
            error_log('Nonce verificado correctamente');
            
            // Intentar guardar el launcher
            if (save_api_launcher()) {
                // Recargar launchers después de guardar
                $api_launchers = get_option('bricks_api_launchers', []);
                if (!is_array($api_launchers)) {
                    $api_launchers = [];
                }
                error_log('Launcher guardado correctamente');
            } else {
                error_log('Error al guardar el launcher');
            }
        } else {
            error_log('Error de verificación de nonce');
        }
    }
    
    // Comprobar si estamos editando un launcher
    $editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['launcher_id']);
    $launcher_id = $editing ? sanitize_text_field($_GET['launcher_id']) : '';
    $current_launcher = [];
    
    if ($editing) {
        if (isset($api_launchers[$launcher_id])) {
            $current_launcher = $api_launchers[$launcher_id];
        } else {
            add_settings_error(
                'bricks_api_launcher',
                'launcher_not_found',
                __('Error: El launcher que intentas editar no existe.', 'bricks-api-integrator'),
                'error'
            );
            $editing = false;
        }
    }
    
    // Display admin notices
    settings_errors('bricks_api_launcher');
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('API Launcher para Bricks Builder', 'bricks-api-integrator'); ?></h1>
        
        <?php if (empty($endpoints)): ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('No hay endpoints API configurados todavía. Por favor, añade endpoints primero.', 'bricks-api-integrator'); ?></p>
                <p><a href="<?php echo admin_url('admin.php?page=bricks-api-integrator'); ?>" class="button"><?php esc_html_e('Configurar Endpoints', 'bricks-api-integrator'); ?></a></p>
            </div>
        <?php else: ?>
            <!-- Formulario para añadir nuevo launcher -->
            <div class="api-launcher-form-container">
                <h2><?php echo $editing ? esc_html__('Editar API Launcher', 'bricks-api-integrator') : esc_html__('Añadir Nuevo API Launcher', 'bricks-api-integrator'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('bricks_api_launcher_nonce'); ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="launcher_id" value="<?php echo esc_attr($launcher_id); ?>">
                    <?php endif; ?>
                    
                    <!-- Debug para ver los datos del formulario -->
                    <?php error_log('Datos del formulario al renderizar:'); ?>
                    <?php error_log('Editing: ' . ($editing ? 'true' : 'false')); ?>
                    <?php if ($editing): ?>
                        <?php error_log('Current launcher: ' . print_r($current_launcher, true)); ?>
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="launcher_name"><?php esc_html_e('Nombre del Launcher', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="launcher_name" name="launcher_name" class="regular-text" value="<?php echo $editing && isset($current_launcher['name']) ? esc_attr($current_launcher['name']) : ''; ?>" required="required">
                                <p class="description"><?php esc_html_e('Este nombre se mostrará en el selector de Bricks.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="endpoint_id"><?php esc_html_e('Endpoint API', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="endpoint_id" name="endpoint_id" required="required">
                                    <option value=""><?php esc_html_e('-- Seleccionar Endpoint --', 'bricks-api-integrator'); ?></option>
                                    <?php foreach ($endpoints as $index => $endpoint): ?>
                                        <?php $endpoint_name = isset($endpoint['name']) ? $endpoint['name'] : __('Endpoint sin nombre', 'bricks-api-integrator'); ?>
                                        <option value="<?php echo esc_attr($index); ?>" <?php selected($editing && isset($current_launcher['endpoint_id']) ? $current_launcher['endpoint_id'] : '', $index); ?>>
                                            <?php echo esc_html($endpoint_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field_prefix"><?php esc_html_e('Prefijo de Campo', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="field_prefix" name="field_prefix" class="regular-text" value="<?php echo $editing && isset($current_launcher['field_prefix']) ? esc_attr($current_launcher['field_prefix']) : 'snap_'; ?>">
                                <p class="description"><?php esc_html_e('Prefijo opcional para los nombres de campo en Bricks. Dejar vacío para no usar prefijo.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dynamic_tag_group"><?php esc_html_e('Grupo de Dynamic Tags', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="dynamic_tag_group" name="dynamic_tag_group" class="regular-text" value="<?php echo $editing && isset($current_launcher['dynamic_tag_group']) ? esc_attr($current_launcher['dynamic_tag_group']) : 'Lista Clínicas'; ?>">
                                <p class="description"><?php esc_html_e('Nombre del grupo para los dynamic tags en Bricks.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit_api_launcher" class="button button-primary" value="<?php echo $editing ? esc_attr__('Actualizar Launcher', 'bricks-api-integrator') : esc_attr__('Guardar API Launcher', 'bricks-api-integrator'); ?>">
                        <?php if ($editing): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bricks-api-integrator-launcher')); ?>" class="button"><?php esc_html_e('Cancelar', 'bricks-api-integrator'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Lista de launchers existentes -->
            <div class="api-launchers-list">
                <h2><?php esc_html_e('Launchers API Configurados', 'bricks-api-integrator'); ?></h2>
                
                <?php if (empty($api_launchers)): ?>
                    <p><?php esc_html_e('No hay launchers API configurados todavía.', 'bricks-api-integrator'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Nombre del Launcher', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Endpoint', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Prefijo de Campo', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Grupo de Dynamic Tags', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Acciones', 'bricks-api-integrator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_launchers as $launcher_id => $launcher): ?>
                                <tr>
                                    <td><?php echo esc_html($launcher['name']); ?></td>
                                    <td>
                                        <?php 
                                        $endpoint_index = isset($launcher['endpoint_id']) ? $launcher['endpoint_id'] : '';
                                        echo isset($endpoints[$endpoint_index]['name']) ? esc_html($endpoints[$endpoint_index]['name']) : esc_html__('Endpoint Desconocido', 'bricks-api-integrator');
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($launcher['field_prefix'] ?? ''); ?></td>
                                    <td><?php echo esc_html($launcher['dynamic_tag_group'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=bricks-api-integrator-launcher&action=edit&launcher_id=' . $launcher_id)); ?>" class="button button-small"><?php esc_html_e('Editar', 'bricks-api-integrator'); ?></a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=bricks-api-integrator-launcher&action=delete&launcher_id=' . $launcher_id), 'delete_api_launcher_' . $launcher_id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres eliminar este launcher?', 'bricks-api-integrator'); ?>')"><?php esc_html_e('Eliminar', 'bricks-api-integrator'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Guardar API Launcher
 */
function save_api_launcher() {
    error_log('Iniciando save_api_launcher');
    
    // Validar nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bricks_api_launcher_nonce')) {
        error_log('Fallo en la verificación del nonce');
        wp_die(__('Comprobación de seguridad fallida.', 'bricks-api-integrator'));
    }
    
    // Dump completo de POST para depuración
    error_log('POST completo: ' . print_r($_POST, true));
    
    // Obtener y validar datos del formulario
    $launcher_name = isset($_POST['launcher_name']) ? trim($_POST['launcher_name']) : '';
    $endpoint_id = isset($_POST['endpoint_id']) ? trim($_POST['endpoint_id']) : '';
    $field_prefix = isset($_POST['field_prefix']) ? trim($_POST['field_prefix']) : '';
    $dynamic_tag_group = isset($_POST['dynamic_tag_group']) ? trim($_POST['dynamic_tag_group']) : '';
    
    // Sanitizar los datos
    $launcher_name = sanitize_text_field($launcher_name);
    $endpoint_id = sanitize_text_field($endpoint_id);
    $field_prefix = sanitize_text_field($field_prefix);
    $dynamic_tag_group = sanitize_text_field($dynamic_tag_group);
    
    // Debug: Registrar los valores procesados
    error_log('Valores procesados:');
    error_log('launcher_name: "' . $launcher_name . '" (empty: ' . (empty($launcher_name) ? 'true' : 'false') . ')');
    error_log('endpoint_id: "' . $endpoint_id . '" (empty: ' . (empty($endpoint_id) ? 'true' : 'false') . ')');
    error_log('field_prefix: "' . $field_prefix . '"');
    error_log('dynamic_tag_group: "' . $dynamic_tag_group . '"');
    
    // Verificar campos obligatorios
    $has_errors = false;
    
    if (empty($launcher_name)) {
        error_log('Error: Nombre del launcher vacío');
        add_settings_error(
            'bricks_api_launcher',
            'missing_launcher_name',
            __('Error: El nombre del launcher es obligatorio.', 'bricks-api-integrator'),
            'error'
        );
        $has_errors = true;
    }
    
    // Verificar si el endpoint_id es realmente vacío (teniendo en cuenta que "0" es un valor válido)
    if ($endpoint_id === '') {
        error_log('Error: Endpoint ID vacío');
        add_settings_error(
            'bricks_api_launcher',
            'missing_endpoint_id',
            __('Error: Debes seleccionar un endpoint.', 'bricks-api-integrator'),
            'error'
        );
        $has_errors = true;
    } else {
        error_log('Endpoint ID válido: ' . $endpoint_id);
    }
    
    if ($has_errors) {
        error_log('Hay errores de validación, abortando');
        return false;
    }
    
    error_log('Validación exitosa, procediendo a guardar');
    
    // Si el campo dynamic_tag_group está vacío, asignarle un valor predeterminado
    if (empty($dynamic_tag_group)) {
        $dynamic_tag_group = $launcher_name;
        error_log('Dynamic tag group vacío, usando nombre del launcher: ' . $dynamic_tag_group);
    }
    
    // Determinar si es una edición o un nuevo launcher
    $is_edit = isset($_POST['launcher_id']) && !empty($_POST['launcher_id']);
    $launcher_id = $is_edit ? sanitize_text_field($_POST['launcher_id']) : 'launcher_' . time() . '_' . wp_rand(100, 999);
    
    error_log('Modo: ' . ($is_edit ? 'Edición (ID: ' . $launcher_id . ')' : 'Nuevo launcher'));
    
    // Preparar datos del launcher
    $launcher_data = [
        'name' => $launcher_name,
        'endpoint_id' => $endpoint_id,
        'field_prefix' => $field_prefix,
        'dynamic_tag_group' => $dynamic_tag_group
    ];
    
    error_log('Datos del launcher preparados: ' . print_r($launcher_data, true));
    
    // Obtener launchers existentes
    $api_launchers = get_option('bricks_api_launchers', []);
    if (!is_array($api_launchers)) {
        error_log('Los launchers existentes no son un array, inicializando array vacío');
        $api_launchers = [];
    }
    
    // Guardar el launcher
    $api_launchers[$launcher_id] = $launcher_data;
    error_log('Launcher agregado al array: ' . print_r($api_launchers, true));
    
    // Método 1: Usar update_option
    error_log('Intentando guardar con update_option');
    $update_result = update_option('bricks_api_launchers', $api_launchers);
    
    if ($update_result) {
        error_log('Guardado exitoso con update_option');
        add_settings_error(
            'bricks_api_launcher',
            'launcher_saved',
            $is_edit ? __('API Launcher actualizado correctamente.', 'bricks-api-integrator') : __('API Launcher añadido correctamente.', 'bricks-api-integrator'),
            'success'
        );
        return true;
    }
    
    // Método 2: Eliminar y volver a crear la opción
    error_log('update_option falló, intentando delete_option + add_option');
    delete_option('bricks_api_launchers');
    $add_result = add_option('bricks_api_launchers', $api_launchers, '', 'yes');
    
    if ($add_result) {
        error_log('Guardado exitoso con delete_option + add_option');
        add_settings_error(
            'bricks_api_launcher',
            'launcher_saved',
            $is_edit ? __('API Launcher actualizado correctamente (método alternativo).', 'bricks-api-integrator') : __('API Launcher añadido correctamente (método alternativo).', 'bricks-api-integrator'),
            'success'
        );
        return true;
    }
    
    // Método 3: Usar el método directo con la base de datos
    error_log('add_option falló, intentando método directo con la base de datos');
    global $wpdb;
    $option_name = 'bricks_api_launchers';
    $serialized_value = maybe_serialize($api_launchers);
    
    $wpdb_result = $wpdb->query($wpdb->prepare(
        "REPLACE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
        $option_name, $serialized_value, 'yes'
    ));
    
    // Limpiar la caché de opciones
    wp_cache_delete($option_name, 'options');
    
    if ($wpdb_result !== false) {
        error_log('Guardado exitoso con método directo de base de datos');
        add_settings_error(
            'bricks_api_launcher',
            'launcher_saved',
            $is_edit ? __('API Launcher actualizado correctamente (método directo).', 'bricks-api-integrator') : __('API Launcher añadido correctamente (método directo).', 'bricks-api-integrator'),
            'success'
        );
        return true;
    }
    
    // Si llegamos aquí, todos los métodos fallaron
    error_log('Todos los métodos de guardado fallaron');
    add_settings_error(
        'bricks_api_launcher',
        'save_failed',
        __('Error: No se pudo guardar el launcher. Por favor, inténtalo de nuevo.', 'bricks-api-integrator'),
        'error'
    );
    return false;
}

/**
 * Manejar acciones del launcher
 */
function handle_api_launcher_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'bricks-api-integrator-launcher') {
        return;
    }
    
    // Manejar acción de eliminar
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['launcher_id'])) {
        $launcher_id = sanitize_text_field($_GET['launcher_id']);
        
        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_api_launcher_' . $launcher_id)) {
            wp_die(__('Comprobación de seguridad fallida.', 'bricks-api-integrator'));
        }
        
        // Obtener launchers existentes
        $api_launchers = get_option('bricks_api_launchers', []);
        if (!is_array($api_launchers)) {
            $api_launchers = [];
        }
        
        // Eliminar el launcher
        if (isset($api_launchers[$launcher_id])) {
            unset($api_launchers[$launcher_id]);
            $update_result = update_option('bricks_api_launchers', $api_launchers);
            
            if ($update_result) {
                // Añadir mensaje de éxito
                add_settings_error(
                    'bricks_api_launcher',
                    'launcher_deleted',
                    __('API Launcher eliminado correctamente.', 'bricks-api-integrator'),
                    'success'
                );
            } else {
                // Añadir mensaje de error
                add_settings_error(
                    'bricks_api_launcher',
                    'delete_error',
                    __('Error: No se pudo eliminar el launcher. Por favor, inténtalo de nuevo.', 'bricks-api-integrator'),
                    'error'
                );
            }
        } else {
            // Añadir mensaje de error si el launcher no existe
            add_settings_error(
                'bricks_api_launcher',
                'launcher_not_found',
                __('Error: El launcher que intentas eliminar no existe.', 'bricks-api-integrator'),
                'error'
            );
        }
        
        // Redireccionar para eliminar la acción de la URL
        echo '<script>window.location.href = "' . esc_url(admin_url('admin.php?page=bricks-api-integrator-launcher')) . '";</script>';
        exit;
    }
}
add_action('admin_init', 'handle_api_launcher_actions');

/**
 * Registrar API Launchers con Bricks
 */
function register_api_launchers_with_bricks() {
    // Obtener launchers API configurados
    $api_launchers = get_option('bricks_api_launchers', []);
    
    if (empty($api_launchers)) {
        return;
    }
    
    // Obtener endpoints configurados
    $endpoints = get_option('bricks_api_endpoints', []);
    
    // Registrar cada launcher
    foreach ($api_launchers as $launcher_id => $launcher) {
        $endpoint_id = isset($launcher['endpoint_id']) ? $launcher['endpoint_id'] : '';
        
        if (!isset($endpoints[$endpoint_id])) {
            continue;
        }
        
        $endpoint = $endpoints[$endpoint_id];
        $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
        
        if (empty($endpoint_url)) {
            continue;
        }
        
        // Registrar el launcher con Bricks
        // Aquí se implementará la integración con Bricks
    }
}
add_action('init', 'register_api_launchers_with_bricks');
