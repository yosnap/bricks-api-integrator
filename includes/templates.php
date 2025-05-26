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
    
    // Get available sources for dropdown
    $api_sources = get_option('bricks_api_sources', []);
    
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
    
    ?>
    <div class="wrap">
        <h1><?php echo $editing ? __('Edit API Template', 'bricks-api-integrator') : __('Add New API Template', 'bricks-api-integrator'); ?></h1>
        
        <div class="nav-tab-wrapper">
            <a href="?page=bricks-api-templates" class="nav-tab nav-tab-active"><?php _e('API Templates', 'bricks-api-integrator'); ?></a>
        </div>
        
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
                                <label for="source_id"><?php _e('API Source', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="source_id" name="source_id" required>
                                    <option value=""><?php _e('Select an API source', 'bricks-api-integrator'); ?></option>
                                    <?php foreach ($api_sources as $id => $source): ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected($editing && isset($current_template['source_id']) ? $current_template['source_id'] : '', $id); ?>>
                                            <?php echo esc_html($source['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Select the API source to use for this template.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
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
                                <input type="text" id="url_base" name="url_base" class="regular-text" value="<?php echo $editing && isset($current_template['url_base']) ? esc_attr($current_template['url_base']) : ''; ?>" required>
                                <p class="description"><?php _e('The base URL for this template (e.g., "api-cars" or "api-products").', 'bricks-api-integrator'); ?></p>
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
                                <th><?php _e('Source', 'bricks-api-integrator'); ?></th>
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
                                        if (isset($template['source_id']) && isset($api_sources[$template['source_id']])) {
                                            echo esc_html($api_sources[$template['source_id']]['name']);
                                        } else {
                                            echo '<em>' . __('Source not found', 'bricks-api-integrator') . '</em>';
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
    // Validate and sanitize inputs
    $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
    $source_id = isset($_POST['source_id']) ? sanitize_text_field($_POST['source_id']) : '';
    $template_type = isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : 'archive';
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $url_base = isset($_POST['url_base']) ? sanitize_title($_POST['url_base']) : '';
    $id_param = isset($_POST['id_param']) ? sanitize_text_field($_POST['id_param']) : 'id';
    
    // Check required fields
    if (empty($template_name) || empty($source_id) || empty($page_id) || empty($url_base)) {
        add_settings_error('bricks_api_templates', 'missing_fields', __('All fields are required.', 'bricks-api-integrator'), 'error');
        return;
    }
    
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
        'source_id' => $source_id,
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
    
    // Add success message
    if (isset($_POST['template_id'])) {
        add_settings_error('bricks_api_templates', 'template_updated', __('API Template updated successfully.', 'bricks-api-integrator'), 'success');
    } else {
        add_settings_error('bricks_api_templates', 'template_added', __('API Template added successfully.', 'bricks-api-integrator'), 'success');
    }
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
        
        if ($template_type === 'archive') {
            // Archive template: /url-base/
            add_rewrite_rule(
                '^' . $url_base . '/?$',
                'index.php?pagename=' . $url_base . '&api_template=' . $template_id,
                'top'
            );
            
            // Archive template with pagination: /url-base/page/2/
            add_rewrite_rule(
                '^' . $url_base . '/page/([0-9]+)/?$',
                'index.php?pagename=' . $url_base . '&api_template=' . $template_id . '&paged=$matches[1]',
                'top'
            );
        } else {
            // Single template: /url-base/item-id/
            $id_param = isset($template['id_param']) ? $template['id_param'] : 'id';
            
            add_rewrite_rule(
                '^' . $url_base . '/([^/]+)/?$',
                'index.php?pagename=' . $url_base . '&api_template=' . $template_id . '&' . $id_param . '=$matches[1]',
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
 * Load API template content
 */
function load_api_template_content($template) {
    global $wp_query;
    
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
    
    // Set the page ID for the template
    $wp_query->queried_object_id = $page_id;
    $wp_query->queried_object = get_post($page_id);
    $wp_query->is_page = true;
    $wp_query->is_singular = true;
    $wp_query->is_archive = false;
    
    // Load the page template
    $page_template = get_page_template_slug($page_id);
    
    if ($page_template) {
        return get_theme_file_path($page_template);
    } else {
        return get_page_template();
    }
}
add_filter('template_include', 'load_api_template_content');

/**
 * Set API source for Bricks Query Loop
 */
function set_api_template_source($query_obj) {
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
    $source_id = isset($api_template['source_id']) ? $api_template['source_id'] : '';
    
    if (empty($source_id)) {
        return $query_obj;
    }
    
    // Set the source ID for the Query Loop
    $query_obj->settings['source'] = 'api_source';
    $query_obj->settings['source_id'] = $source_id;
    
    // Set pagination
    $query_obj->settings['pagination'] = true;
    
    // For single templates, add the ID parameter
    if (isset($api_template['template_type']) && $api_template['template_type'] === 'single' && isset($api_template['id_param'])) {
        $id_param = $api_template['id_param'];
        
        if (isset($wp_query->query_vars[$id_param])) {
            $id_value = $wp_query->query_vars[$id_param];
            
            // Add dynamic parameter
            if (!isset($query_obj->settings['dynamic_params'])) {
                $query_obj->settings['dynamic_params'] = [];
            }
            
            $query_obj->settings['dynamic_params'][] = [
                'param_name' => $id_param,
                'param_value' => $id_value,
            ];
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
