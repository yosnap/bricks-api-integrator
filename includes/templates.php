<?php
/**
 * API Templates Manager for Bricks Builder
 * 
 * This file handles the creation and management of templates for API sources
 */

// Evitar el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the API Templates admin page
 */
function render_api_templates_page() {
    // Check if form was submitted
    if (isset($_POST['submit_api_template']) && check_admin_referer('bricks_api_template_nonce')) {
        save_api_template();
    }
    
    // Check if we're editing an existing template
    $editing = false;
    $current_template = [];
    $template_id = '';
    
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $template_id = sanitize_text_field($_GET['id']);
        $api_templates = get_option('bricks_api_templates', []);
        
        if (isset($api_templates[$template_id])) {
            $editing = true;
            $current_template = $api_templates[$template_id];
        }
    }
    
    // Check if we're deleting a template
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && check_admin_referer('delete_template')) {
        $template_id = sanitize_text_field($_GET['id']);
        $api_templates = get_option('bricks_api_templates', []);
        
        if (isset($api_templates[$template_id])) {
            unset($api_templates[$template_id]);
            update_option('bricks_api_templates', $api_templates);
            add_settings_error('bricks_api_templates', 'template_deleted', __('API Template deleted successfully.', 'bricks-api-integrator'), 'success');
        }
    }
    
    // Get available endpoints for dropdown (UPDATED FOR ENDPOINT SYSTEM)
    $endpoints = get_option('bricks_api_endpoints', []);
    
    // Get available pages for template selection
    $pages = get_pages([
        'post_status' => 'publish',
        'sort_column' => 'post_title',
        'sort_order' => 'ASC',
    ]);
    
    // Display admin notices
    settings_errors('bricks_api_templates');
    
    // Get existing templates
    $api_templates = get_option('bricks_api_templates', []);
    
    // Get sources (query types)
    $sources = get_option('bricks_api_sources', []);
    
    // Prepare selected values
    $selected_type = '';
    $selected_id = '';
    if ($editing && isset($current_template['endpoint_type']) && isset($current_template['endpoint_id'])) {
        $selected_type = $current_template['endpoint_type'];
        $selected_id = $current_template['endpoint_id'];
    }
    
    // --- En la edición de plantilla, solo mostrar el error si el tipo es endpoint y no existe ---
    if ($editing && isset($current_template['endpoint_type'], $current_template['endpoint_id'])) {
        $endpoint_type = $current_template['endpoint_type'];
        $endpoint_id = $current_template['endpoint_id'];
        if ($endpoint_type === 'endpoint') {
            $endpoints = get_option('bricks_api_endpoints', []);
            if (!isset($endpoints[$endpoint_id])) {
                echo '<div class="notice notice-error"><p>' . __('Selected endpoint does not exist.', 'bricks-api-integrator') . '</p></div>';
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo $editing ? __('Edit API Template', 'bricks-api-integrator') : __('Add New API Template', 'bricks-api-integrator'); ?></h1>
        
        <div class="nav-tab-wrapper">
            <a href="?page=bricks-api-templates" class="nav-tab nav-tab-active"><?php _e('API Templates', 'bricks-api-integrator'); ?></a>
        </div>
        
        <?php if (!$editing): ?>
        <div class="notice notice-info">
            <h3>📝 ¿Cómo funcionan las Templates?</h3>
            <p><strong>Las templates crean URLs automáticas para tus datos de API:</strong></p>
            <ul style="margin-left: 20px;">
                <li><strong>Archive (Lista):</strong> Crea URLs como <code>/clinicas/</code> que muestran listados</li>
                <li><strong>Single (Detalle):</strong> Crea URLs como <code>/clinicas/123/</code> que muestran un elemento específico</li>
            </ul>
            <p><strong>Pasos:</strong></p>
            <ol style="margin-left: 20px;">
                <li>Crea una página en WordPress con Bricks Builder</li>
                <li>Diseña tu layout usando elementos de Bricks</li>
                <li>Usa Dynamic Tags con prefijo <code>snap_</code> para mostrar datos de la API</li>
                <li>Configura la template aquí para activar las URLs automáticas</li>
            </ol>
            <p>💡 <strong>Ejemplo:</strong> Si configuras URL base "clinicas" tipo "Single", se creará automáticamente <code>/clinicas/[id]/</code></p>
        </div>
        <?php endif; ?>
        
        <div class="api-template-container">
            <div class="api-template-form">
                <form method="post" action="">
                    <?php wp_nonce_field('bricks_api_template_nonce'); ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="template_name"><?php _e('Template Name', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="template_name" name="template_name" class="regular-text" value="<?php echo $editing ? esc_attr($current_template['name']) : ''; ?>" required>
                                <p class="description"><?php _e('A descriptive name for this template.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="endpoint_selector"><?php _e('API Endpoint / Source', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="endpoint_selector" name="endpoint_selector" required>
                                    <optgroup label="Endpoints">
                                        <?php foreach ($endpoints as $eid => $endpoint): ?>
                                            <option value="endpoint|<?php echo esc_attr($eid); ?>" <?php selected($selected_type === 'endpoint' && $selected_id == $eid); ?>><?php echo esc_html($endpoint['name']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Sources (Query Types)">
                                        <?php foreach ($sources as $sid => $source): ?>
                                            <option value="source|<?php echo esc_attr($sid); ?>" <?php selected($selected_type === 'source' && $selected_id == $sid); ?>><?php echo esc_html($source['name']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                                <p class="description"><?php _e('Select the API endpoint or Source (Query Type) to use for this template.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <input type="hidden" id="endpoint_type" name="endpoint_type" value="<?php echo esc_attr($selected_type); ?>">
                        <input type="hidden" id="endpoint_id" name="endpoint_id" value="<?php echo esc_attr($selected_id); ?>">
                        <tr>
                            <th scope="row">
                                <label for="template_type"><?php _e('Template Type', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="template_type" name="template_type" required>
                                    <option value="archive" <?php selected($editing && isset($current_template['template_type']) ? $current_template['template_type'] : '', 'archive'); ?>><?php _e('Archive (List)', 'bricks-api-integrator'); ?></option>
                                    <option value="single" <?php selected($editing && isset($current_template['template_type']) ? $current_template['template_type'] : '', 'single'); ?>><?php _e('Single (Detail)', 'bricks-api-integrator'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select whether this template is for a list of items or a single item detail.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="page_id"><?php _e('Template Page', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="page_id" name="page_id" required>
                                    <option value=""><?php _e('Select a page', 'bricks-api-integrator'); ?></option>
                                    <?php foreach ($pages as $page): ?>
                                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($editing && isset($current_template['page_id']) ? $current_template['page_id'] : '', $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Select the page to use as a template for this API source.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="url_base"><?php _e('URL Base', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="url_base" name="url_base" class="regular-text" value="<?php echo $editing && isset($current_template['url_base']) ? esc_attr($current_template['url_base']) : ''; ?>">
                                <p class="description"><?php _e('The base URL for this template (e.g., "api-cars" or "api-products"). Required for Single templates.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr id="id_param_row" style="<?php echo $editing && isset($current_template['template_type']) && $current_template['template_type'] === 'single' ? '' : 'display: none;'; ?>">
                            <th scope="row">
                                <label for="id_param"><?php _e('ID Parameter', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="id_param" name="id_param" class="regular-text" value="<?php echo $editing && isset($current_template['id_param']) ? esc_attr($current_template['id_param']) : 'id'; ?>">
                                <p class="description"><?php _e('The parameter name used to identify a single item (e.g., "id", "slug", "post_id").', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit_api_template" class="button button-primary" value="<?php echo $editing ? __('Update Template', 'bricks-api-integrator') : __('Add Template', 'bricks-api-integrator'); ?>">
                        <?php if ($editing): ?>
                            <a href="?page=bricks-api-templates" class="button"><?php _e('Cancel', 'bricks-api-integrator'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
                
                <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    // Obtener referencias a los elementos del formulario
                    const templateTypeSelect = document.getElementById('template_type');
                    const idParamRow = document.getElementById('id_param_row');
                    const urlBaseInput = document.getElementById('url_base');
                    
                    // Función para actualizar la validación según el tipo de template
                    function updateValidation() {
                        const templateType = templateTypeSelect.value;
                        
                        // Mostrar/ocultar fila de ID Parameter
                        if (templateType === 'single') {
                            idParamRow.style.display = '';
                            urlBaseInput.setAttribute('required', 'required');
                        } else {
                            idParamRow.style.display = 'none';
                            urlBaseInput.removeAttribute('required');
                        }
                    }
                    
                    // Ejecutar al cargar la página
                    updateValidation();
                    
                    // Añadir listener para cambios en el tipo de template
                    templateTypeSelect.addEventListener('change', updateValidation);
                });
                </script>
            </div>
            
            <div class="api-template-list">
                <h2><?php _e('Existing API Templates', 'bricks-api-integrator'); ?></h2>
                
                <?php if (empty($api_templates)): ?>
                    <p><?php _e('No API templates found. Create your first one!', 'bricks-api-integrator'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'bricks-api-integrator'); ?></th>
                                <th><?php _e('Endpoint', 'bricks-api-integrator'); ?></th>
                                <th><?php _e('Type', 'bricks-api-integrator'); ?></th>
                                <th><?php _e('Page', 'bricks-api-integrator'); ?></th>
                                <th><?php _e('URL Base', 'bricks-api-integrator'); ?></th>
                                <th><?php _e('Actions', 'bricks-api-integrator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_templates as $id => $template): ?>
                                <tr>
                                    <td><?php echo esc_html($template['name']); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($template['endpoint_id']) && isset($endpoints[$template['endpoint_id']])) {
                                            echo esc_html($endpoints[$template['endpoint_id']]['name']);
                                        } else {
                                            echo '<em>' . __('Endpoint not found', 'bricks-api-integrator') . '</em>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo isset($template['template_type']) && $template['template_type'] === 'single' ? __('Single', 'bricks-api-integrator') : __('Archive', 'bricks-api-integrator'); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($template['page_id'])) {
                                            $page_title = get_the_title($template['page_id']);
                                            echo $page_title ? esc_html($page_title) : '<em>' . __('Page not found', 'bricks-api-integrator') . '</em>';
                                        } else {
                                            echo '<em>' . __('No page selected', 'bricks-api-integrator') . '</em>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo isset($template['url_base']) ? esc_html($template['url_base']) : ''; ?></td>
                                    <td>
                                        <a href="?page=bricks-api-templates&action=edit&id=<?php echo esc_attr($id); ?>" class="button button-small"><?php _e('Edit', 'bricks-api-integrator'); ?></a>
                                        <a href="<?php echo wp_nonce_url('?page=bricks-api-templates&action=delete&id=' . esc_attr($id), 'delete_template'); ?>" class="button button-small" onclick="return confirm('<?php _e('Are you sure you want to delete this template?', 'bricks-api-integrator'); ?>')"><?php _e('Delete', 'bricks-api-integrator'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Show/hide ID parameter field based on template type
        $('#template_type').on('change', function() {
            if ($(this).val() === 'single') {
                $('#id_param_row').show();
            } else {
                $('#id_param_row').hide();
            }
        });
    });
    </script>
    
    <style>
        .api-template-container {
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }
        
        .api-template-form {
            margin-bottom: 30px;
        }
        
        @media (min-width: 782px) {
            .api-template-container {
                flex-direction: column;
            }
            
            .api-template-form {
                width: 100%;
                margin-right: 0;
            }
            
            .api-template-list {
                width: 100%;
            }
        }
    </style>
    <?php
}

/**
 * Save API template data
 */
function save_api_template() {
    // Debug: Registrar los datos recibidos
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Validate and sanitize inputs
    $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
    $endpoint_selector = isset($_POST['endpoint_selector']) ? $_POST['endpoint_selector'] : '';
    $endpoint_type = '';
    $endpoint_id = '';
    if ($endpoint_selector) {
        list($endpoint_type, $endpoint_id) = explode('|', $endpoint_selector);
    }
    $template_type = isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : 'archive';
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $url_base = isset($_POST['url_base']) ? sanitize_title($_POST['url_base']) : '';
    $id_param = isset($_POST['id_param']) ? sanitize_text_field($_POST['id_param']) : 'id';
    
    // Debug: Registrar los valores procesados
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Registrar los valores para depuración
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Validar cada campo individualmente para mostrar mensajes más específicos
    if (empty($template_name)) {
        add_settings_error('bricks_api_templates', 'missing_name', __('Template name is required.', 'bricks-api-integrator'), 'error');
        return;
    }
    
    // Para el endpoint_id, verificar si es una cadena vacía o si no existe en los endpoints o sources
    if ($endpoint_id === '') {
        add_settings_error('bricks_api_templates', 'missing_endpoint', __('Please select an API endpoint or Source.', 'bricks-api-integrator'), 'error');
        return;
    }
    
    if ($endpoint_type === 'endpoint') {
        $endpoints = get_option('bricks_api_endpoints', []);
        if (!isset($endpoints[$endpoint_id])) {
            add_settings_error('bricks_api_templates', 'invalid_endpoint', __('Selected endpoint does not exist.', 'bricks-api-integrator'), 'error');
            return;
        }
    } elseif ($endpoint_type === 'source') {
        $sources = get_option('bricks_api_sources', []);
        if (!isset($sources[$endpoint_id])) {
            add_settings_error('bricks_api_templates', 'invalid_source', __('Selected source does not exist.', 'bricks-api-integrator'), 'error');
            return;
        }
    }
    
    if (empty($page_id)) {
        add_settings_error('bricks_api_templates', 'missing_page', __('Please select a template page.', 'bricks-api-integrator'), 'error');
        return;
    }
    
    // Para templates de tipo single, el URL base es obligatorio
    if ($template_type === 'single' && empty($url_base)) {
        add_settings_error('bricks_api_templates', 'missing_url_base', __('URL Base is required for single item templates.', 'bricks-api-integrator'), 'error');
        return;
    }
    
    // Este bloque de validación ya se realiza más abajo, así que lo eliminamos para evitar duplicación
    
    // Get existing templates
    $api_templates = get_option('bricks_api_templates', []);
    
    // Check if we're updating an existing template
    if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
        $template_id = sanitize_text_field($_POST['template_id']);
    } else {
        // Generate a unique ID for new template
        $template_id = 'template_' . time() . '_' . wp_rand(1000, 9999);
    }
    
    // Prepare template data
    $template_data = [
        'name' => $template_name,
        'endpoint_type' => $endpoint_type,
        'endpoint_id' => $endpoint_id,
        'template_type' => $template_type,
        'page_id' => $page_id,
        'url_base' => $url_base,
    ];
    
    // Add ID parameter for single templates
    if ($template_type === 'single') {
        $template_data['id_param'] = $id_param;
    }
    
    // Add or update template
    $api_templates[$template_id] = $template_data;
    
    // Save to database
    update_option('bricks_api_templates', $api_templates);
    
    // Set flag to flush rewrite rules
    update_option('bricks_api_flush_rewrite_rules', true);
    
    // Add success message
    if (isset($_POST['template_id'])) {
        add_settings_error('bricks_api_templates', 'template_updated', __('API Template updated successfully. Rewrite rules will be refreshed.', 'bricks-api-integrator'), 'success');
    } else {
        add_settings_error('bricks_api_templates', 'template_added', __('API Template added successfully. Rewrite rules will be refreshed.', 'bricks-api-integrator'), 'success');
    }
    // Forzar flush inmediato de reglas de reescritura
    flush_rewrite_rules();
}

/**
 * Register rewrite rules for API templates
 */
function register_api_template_rewrite_rules() {
    $api_templates = get_option('bricks_api_templates', []);
    if (empty($api_templates)) {
        return;
    }
    foreach ($api_templates as $template_id => $template) {
        $url_base = isset($template['url_base']) ? $template['url_base'] : '';
        $template_type = isset($template['template_type']) ? $template['template_type'] : 'archive';
        if (empty($url_base)) {
            continue;
        }
        // Obtener el slug real de la página plantilla seleccionada
        $page_id = isset($template['page_id']) ? $template['page_id'] : 0;
        $slug_real = $page_id ? get_post_field('post_name', $page_id) : $url_base;
        if ($template_type === 'archive') {
            // Archive template: /url-base/
            add_rewrite_rule(
                '^' . $url_base . '/?$',
                'index.php?pagename=' . $slug_real . '&api_template=' . $template_id,
                'top'
            );
            // Archive template with pagination: /url-base/page/2/
            add_rewrite_rule(
                '^' . $url_base . '/page/([0-9]+)/?$',
                'index.php?pagename=' . $slug_real . '&api_template=' . $template_id . '&paged=$matches[1]',
                'top'
            );
        } else {
            // Single template: /url-base/item-id/
            $id_param = isset($template['id_param']) ? $template['id_param'] : 'id';
            add_rewrite_rule(
                '^' . $url_base . '/([^/]+)/?$',
                'index.php?pagename=' . $slug_real . '&api_template=' . $template_id . '&' . $id_param . '=$matches[1]',
                'top'
            );
        }
    }
}
add_action('init', 'register_api_template_rewrite_rules');

/**
 * Add query vars for API templates
 */
function add_api_template_query_vars($query_vars) {
    $query_vars[] = 'api_template';
    
    // Add all possible ID parameters from templates
    $api_templates = get_option('bricks_api_templates', []);
    
    if (!empty($api_templates)) {
        foreach ($api_templates as $template) {
            if (isset($template['template_type']) && $template['template_type'] === 'single' && isset($template['id_param'])) {
                $query_vars[] = $template['id_param'];
            }
        }
    }
    
    return $query_vars;
}
add_filter('query_vars', 'add_api_template_query_vars');

/**
 * Suppress WordPress warnings for API template pages
 */
function suppress_api_template_warnings() {
    global $wp_query;
    
    // Only suppress warnings on API template pages
    if (isset($wp_query->query_vars['api_template'])) {
        
        // Create a custom error handler for API template pages
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // Suppress specific WordPress warnings related to null post objects
            if (strpos($errstr, 'Attempt to read property') !== false && 
                (strpos($errstr, 'comment_count') !== false || 
                 strpos($errstr, 'post_title') !== false ||
                 strpos($errstr, 'post_content') !== false ||
                 strpos($errfile, 'general-template.php') !== false)) {
                return true; // Suppress this warning
            }
            
            // For other errors, use the default handler
            return false;
        }, E_WARNING | E_NOTICE);
    }
}
/**
 * Initialize API template with proper WordPress context
 */
function init_api_template_context() {
    global $wp_query, $post;
    
    // Check if this is an API template request
    if (!isset($wp_query->query_vars['api_template'])) {
        return;
    }
    // --- DEBUG: Log all query_vars to check if the dynamic parameter arrives ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('API TEMPLATE DEBUG: $wp_query->query_vars (init_api_template_context)=' . print_r($wp_query->query_vars, true));
    }
    
    $template_id = $wp_query->query_vars['api_template'];
    $api_templates = get_option('bricks_api_templates', []);
    
    if (!isset($api_templates[$template_id])) {
        return;
    }
    
    $api_template = $api_templates[$template_id];
    $page_id = isset($api_template['page_id']) ? $api_template['page_id'] : 0;
    
    if (empty($page_id)) {
        return;
    }
    
    // Get the page object
    $page_object = get_post($page_id);
    
    if (!$page_object) {
        return;
    }
    
    // Ensure the page object has all required properties
    if (!isset($page_object->comment_count)) {
        $page_object->comment_count = 0;
    }
    
    if (!isset($page_object->comment_status)) {
        $page_object->comment_status = 'closed';
    }
    
    if (!isset($page_object->ping_status)) {
        $page_object->ping_status = 'closed';
    }
    
    // Set up globals properly
    $post = $page_object;
    $wp_query->post = $page_object;
    $wp_query->posts = [$page_object];
    
    // Set up the loop
    $wp_query->current_post = 0;
    $wp_query->post_count = 1;
    $wp_query->in_the_loop = false; // Will be set to true when the_post() is called
    
    // Set up postdata
    setup_postdata($post);
}
add_action('template_redirect', 'init_api_template_context', 1);

/**
 * Load API template content (FIXED FOR POST OBJECT ISSUES)
 */
function load_api_template_content($template) {
    global $wp_query, $post;
    
    // Check if this is an API template request
    if (!isset($wp_query->query_vars['api_template'])) {
        return $template;
    }
    
    $template_id = $wp_query->query_vars['api_template'];
    $api_templates = get_option('bricks_api_templates', []);
    
    if (!isset($api_templates[$template_id])) {
        return $template;
    }
    
    $api_template = $api_templates[$template_id];
    $page_id = isset($api_template['page_id']) ? $api_template['page_id'] : 0;
    
    if (empty($page_id)) {
        return $template;
    }
    
    // Get the actual page object
    $page_object = get_post($page_id);
    
    if (!$page_object) {
        return $template;
    }
    
    // Set up the global post object properly
    $post = $page_object;
    setup_postdata($post);
    
    // Configure WordPress query
    $wp_query->queried_object_id = $page_id;
    $wp_query->queried_object = $page_object;
    $wp_query->is_page = true;
    $wp_query->is_singular = true;
    $wp_query->is_archive = false;
    $wp_query->is_home = false;
    $wp_query->is_front_page = false;
    $wp_query->post = $page_object;
    $wp_query->posts = [$page_object];
    $wp_query->post_count = 1;
    $wp_query->found_posts = 1;
    
    // Set the current post ID for WordPress functions
    global $wp_query;
    $wp_query->in_the_loop = true;
    
    // Load the page template
    $page_template = get_page_template_slug($page_id);
    
    if ($page_template) {
        $template_file = get_theme_file_path($page_template);
        if (file_exists($template_file)) {
            return $template_file;
        }
    }
    
    // Fallback to default page template
    return get_page_template();
}
add_filter('template_include', 'load_api_template_content');

/**
 * Clean up global state after template rendering
 */
function cleanup_api_template_globals() {
    global $wp_query;
    
    // Check if this was an API template request
    if (isset($wp_query->query_vars['api_template'])) {
        // Reset post data to prevent issues with other parts of the site
        wp_reset_postdata();
    }
}
add_action('wp_footer', 'cleanup_api_template_globals');

/**
 * Set API endpoint for Bricks Query Loop (UPDATED FOR ENDPOINT SYSTEM)
 */
function set_api_template_source($query_obj) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('API TEMPLATE DEBUG: set_api_template_source ejecutándose');
    }
    global $wp_query;
    // Check if this is an API template request
    if (!isset($wp_query->query_vars['api_template'])) {
        return $query_obj;
    }
    $template_id = $wp_query->query_vars['api_template'];
    $api_templates = get_option('bricks_api_templates', []);
    if (!isset($api_templates[$template_id])) {
        return $query_obj;
    }
    $api_template = $api_templates[$template_id];
    $endpoint_type = isset($api_template['endpoint_type']) ? $api_template['endpoint_type'] : 'endpoint';
    $endpoint_id = isset($api_template['endpoint_id']) ? $api_template['endpoint_id'] : '';
    // --- LOG: tipo de plantilla y endpoint/source seleccionado ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('API TEMPLATE DEBUG: template_id=' . $template_id . ' | endpoint_type=' . $endpoint_type . ' | endpoint_id=' . $endpoint_id);
    }
    // --- NUEVO: Si es un Source, usar su lógica ---
    if ($endpoint_type === 'source') {
        $sources = get_option('bricks_api_sources', []);
        if (!isset($sources[$endpoint_id])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('API TEMPLATE DEBUG: Source no encontrado: ' . $endpoint_id);
            }
            return $query_obj;
        }
        $source = $sources[$endpoint_id];
        // Usar el query_type generado por el Source
        $slug = function_exists('bricks_api_normalize_slug') ? bricks_api_normalize_slug($source['name']) : $endpoint_id;
        $query_type = '{snap_' . $slug . '}';
        $query_obj->object_type = $query_type;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('API TEMPLATE DEBUG: object_type generado para Source: ' . $query_type);
        }
        // Pasar parámetros dinámicos si existen
        if (isset($source['dynamic_params']) && is_array($source['dynamic_params'])) {
            foreach ($source['dynamic_params'] as $param) {
                $param_name = $param['name'] ?? '';
                $param_default = $param['default'] ?? '';
                if ($param_name && !isset($_GET[$param_name])) {
                    $_GET[$param_name] = $param_default;
                }
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('API TEMPLATE DEBUG: Param dinámico ' . $param_name . ' = ' . print_r($_GET[$param_name], true));
                }
            }
        }
        // Para single, pasar el id_param desde la URL
        if (isset($api_template['template_type']) && $api_template['template_type'] === 'single' && isset($api_template['id_param'])) {
            $id_param = $api_template['id_param'];
            if (isset($wp_query->query_vars[$id_param])) {
                $id_value = $wp_query->query_vars[$id_param];
                global $bricks_api_current_item_id;
                $bricks_api_current_item_id = [
                    'param' => $id_param,
                    'value' => $id_value,
                    'source_id' => $endpoint_id
                ];
                $_GET[$id_param] = $id_value;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('API TEMPLATE DEBUG: id_param ' . $id_param . ' = ' . $id_value);
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('API TEMPLATE DEBUG: id_param ' . $id_param . ' NO encontrado en query_vars');
                }
            }
        }
        return $query_obj;
    }
    // --- Fin lógica Source ---
    // Lógica original para endpoints
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!isset($endpoints[$endpoint_id])) {
        return $query_obj;
    }
    $endpoint = $endpoints[$endpoint_id];
    $endpoint_name = $endpoint['name'];
    $query_type_key = 'api_' . sanitize_key($endpoint_name);
    $query_obj->object_type = $query_type_key;
    if (!isset($query_obj->settings['posts_per_page'])) {
        $query_obj->settings['posts_per_page'] = 10;
    }
    if (isset($api_template['template_type']) && $api_template['template_type'] === 'single' && isset($api_template['id_param'])) {
        $id_param = $api_template['id_param'];
        if (isset($wp_query->query_vars[$id_param])) {
            $id_value = $wp_query->query_vars[$id_param];
            global $bricks_api_current_item_id;
            $bricks_api_current_item_id = [
                'param' => $id_param,
                'value' => $id_value,
                'endpoint_id' => $endpoint_id
            ];
            $_GET[$id_param] = $id_value;
        }
    }
    return $query_obj;
}
add_filter('bricks/query/before_query', 'set_api_template_source');

/**
 * Flush rewrite rules when templates are updated
 */
function flush_api_template_rewrite_rules() {
    $flush_needed = get_option('bricks_api_flush_rewrite_rules', false);
    
    if ($flush_needed) {
        flush_rewrite_rules();
        update_option('bricks_api_flush_rewrite_rules', false);
    }
}
add_action('init', 'flush_api_template_rewrite_rules', 20);

/**
 * Set flag to flush rewrite rules when templates are updated
 */
function set_api_template_flush_flag() {
    update_option('bricks_api_flush_rewrite_rules', true);
}
add_action('update_option_bricks_api_templates', 'set_api_template_flush_flag');
