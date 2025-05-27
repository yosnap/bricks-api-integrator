<?php
/**
 * Funciones principales para Bricks API Integrator
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

// Incluir archivos de componentes
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php';

/**
 * Función del menú principal
 */
if (!function_exists('bricks_api_integrator_menu')) {
    function bricks_api_integrator_menu() {
        // Menú principal
        add_menu_page(
            'Bricks API Integrator',
            'API Integrator',
            'manage_options',
            'bricks-api-integrator',
            'bricks_api_integrator_dashboard',
            'dashicons-rest-api',
            20
        );
        
        // Submenús
        add_submenu_page(
            'bricks-api-integrator',
            __('API Endpoints', 'bricks-api-integrator'),
            __('API Endpoints', 'bricks-api-integrator'),
            'manage_options',
            'bricks-api-integrator-endpoints',
            'render_api_endpoints_page'
        );
        
        add_submenu_page(
            'bricks-api-integrator',
            __('Query Types', 'bricks-api-integrator'),
            __('Query Types', 'bricks-api-integrator'),
            'manage_options',
            'bricks-api-integrator-sources',
            'render_api_sources_page'
        );
        
        add_submenu_page(
            'bricks-api-integrator',
            __('API Templates', 'bricks-api-integrator'),
            __('API Templates', 'bricks-api-integrator'),
            'manage_options',
            'bricks-api-templates',
            'render_api_templates_page'
        );
    }
}

/**
 * Función para cargar assets
 */
if (!function_exists('bricks_api_integrator_assets')) {
    function bricks_api_integrator_assets() {
        // Enqueue CSS
        wp_enqueue_style('bricks-api-integrator-style', BRICKS_API_INTEGRATOR_URL . 'assets/bricks-api-integrator.css');
        
        // Enqueue JavaScript
        wp_enqueue_script('bricks-api-integrator-script', BRICKS_API_INTEGRATOR_URL . 'assets/bricks-api-integrator.js', ['jquery'], null, true);
    }
}


/**
 * Dashboard principal del plugin
 */
if (!function_exists('bricks_api_integrator_dashboard')) {
    function bricks_api_integrator_dashboard() {
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        $templates = get_option('bricks_api_templates', []);
        
        // Obtener información detallada
        $total_dynamic_tags = 0;
        $total_query_types = 0;
        if (class_exists('BricksAPIIntegrator')) {
            try {
                $integrator_instance = new BricksAPIIntegrator();
                $total_dynamic_tags = $integrator_instance->count_dynamic_tags();
                $total_query_types = $integrator_instance->count_query_types();
            } catch (Exception $e) {
                // Continuar sin contar tags si hay error
            }
        }
        ?>
        <div class="wrap">
            <h1>🔌 Bricks API Integrator v2.0</h1>
            <p>Plugin que integra APIs externas con Bricks Builder de forma dinámica.</p>
            
            <div class="dashboard-stats" style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($endpoints); ?></h3>
                    <p style="margin: 10px 0 0 0;">Endpoints Configurados</p>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($sources); ?></h3>
                    <p style="margin: 10px 0 0 0;">Query Types Configurados</p>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($templates); ?></h3>
                    <p style="margin: 10px 0 0 0;">Templates Configurados</p>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo $total_query_types; ?></h3>
                    <p style="margin: 10px 0 0 0;">Query Types Generados</p>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo $total_dynamic_tags; ?></h3>
                    <p style="margin: 10px 0 0 0;">Dynamic Tags Generados</p>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo function_exists('bricks_is_builder') ? '✅' : '❌'; ?></h3>
                    <p style="margin: 10px 0 0 0;">Bricks Builder</p>
                </div>
            </div>
            
            <!-- Botón de regeneración -->
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">🔄 Sincronización con Bricks</h3>
                <p>Si los datos no se corresponden en Bricks Builder, regenera la configuración:</p>
                <button type="button" id="regenerate-bricks-data" class="button button-primary">🔄 Regenerar Dynamic Tags y Query Types</button>
                <button type="button" id="clear-cache-data" class="button" style="margin-left: 10px;">🗑️ Limpiar Cache</button>
                <button type="button" id="clean-duplicates-data" class="button" style="margin-left: 10px; background: #f39c12; color: white;">🧹 Limpiar Duplicados</button>
                <button type="button" id="clean-query-types-data" class="button" style="margin-left: 10px; background: #8e44ad; color: white;">🔧 Limpiar Query Types</button>
                <button type="button" id="aggressive-cleanup-data" class="button" style="margin-left: 10px; background: #e74c3c; color: white;">💣 Limpieza TOTAL</button>
                <button type="button" id="reset-plugin-data" class="button button-secondary" style="margin-left: 10px; background: #dc3232; color: white;">⚠️ Reset Completo</button>
                <div id="regenerate-result" style="margin-top: 10px;"></div>
            </div>
            
            <h2>🚀 Primeros Pasos</h2>
            <ol>
                <li><strong>Configurar Endpoints:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-integrator-endpoints'); ?>">API Endpoints</a> para gestionar tus APIs</li>
                <li><strong>Configurar Query Types:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-integrator-sources'); ?>">Query Types</a> para configurar query types</li>
                <li><strong>Configurar Templates:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-templates'); ?>">API Templates</a></li>
                <li><strong>Usar en Bricks:</strong> Los Query Types aparecerán automáticamente en el Query Loop de Bricks</li>
                <li><strong>Dynamic Tags:</strong> Los tags se generan automáticamente desde las respuestas de API</li>
            </ol>
            
            <script>
            jQuery(document).ready(function($) {
                // Regenerar datos de Bricks
                $('#regenerate-bricks-data').click(function() {
                    var $button = $(this);
                    var $result = $('#regenerate-result');
                    
                    $button.prop('disabled', true).text('🔄 Regenerando...');
                    $result.html('<p style="color: orange;">Regenerando configuración...</p>');
                    
                    $.post(ajaxurl, {
                        action: 'regenerate_bricks_integration',
                        nonce: '<?php echo wp_create_nonce('regenerate_bricks_integration'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<p style="color: green;">✅ ' + response.data.message + '</p>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<p style="color: red;">❌ Error: ' + response.data.message + '</p>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text('🔄 Regenerar Dynamic Tags y Query Types');
                    });
                });
                
                // Limpiar duplicados
                $('#clean-duplicates-data').click(function() {
                    var $button = $(this);
                    var $result = $('#regenerate-result');
                    
                    $button.prop('disabled', true).text('🧹 Limpiando...');
                    $result.html('<p style="color: orange;">Eliminando duplicados...</p>');
                    
                    // Hacer petición AJAX
                    $.post(ajaxurl, {
                        action: 'clean_duplicates',
                        nonce: '<?php echo wp_create_nonce('clean_duplicates'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<p style="color: green;">✅ ' + response.data.message + '</p>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<p style="color: red;">❌ Error: ' + response.data.message + '</p>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text('🧹 Limpiar Duplicados');
                    });
                });
                
                // Limpieza TOTAL - AGRESIVA
                $('#aggressive-cleanup-data').click(function() {
                    if (!confirm('⚠️ PELIGRO: Esto eliminará TODO el contenido del plugin y empezará desde cero. ¿Estás SEGURO?')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $result = $('#regenerate-result');
                    
                    $button.prop('disabled', true).text('💣 Eliminando TODO...');
                    $result.html('<p style="color: red;">⚠️ Eliminando TODA la configuración del plugin...</p>');
                    
                    // Realizar limpieza agresiva
                    window.location.href = window.location.href + '&aggressive_cleanup=1';
                });
                
                // Limpiar cache
                $('#clear-cache-data').click(function() {
                    var $button = $(this);
                    
                    $button.prop('disabled', true).text('🔄 Limpiando...');
                    
                    $.post(ajaxurl, {
                        action: 'clear_api_cache',
                        nonce: '<?php echo wp_create_nonce('clear_api_cache'); ?>'
                    }, function(response) {
                        alert('✅ Cache limpiado correctamente');
                    }).always(function() {
                        $button.prop('disabled', false).text('🗑️ Limpiar Cache');
                    });
                });
                
                // Limpiar Query Types específicamente
                $('#clean-query-types-data').click(function() {
                    var $button = $(this);
                    var $result = $('#regenerate-result');
                    
                    $button.prop('disabled', true).text('🔧 Limpiando Query Types...');
                    $result.html('<p style="color: orange;">Eliminando Query Types duplicados...</p>');
                    
                    $.post(ajaxurl, {
                        action: 'clean_query_types',
                        nonce: '<?php echo wp_create_nonce('clean_query_types'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<p style="color: green;">✅ ' + response.data.message + '</p>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<p style="color: red;">❌ Error: ' + response.data.message + '</p>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text('🔧 Limpiar Query Types');
                    });
                });
                
                // Reset completo
                $('#reset-plugin-data').click(function() {
                    if (!confirm('⚠️ ADVERTENCIA: Esto eliminará TODOS los datos del plugin. ¿Estás seguro?')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $result = $('#regenerate-result');
                    
                    $button.prop('disabled', true).text('⚠️ Reseteando...');
                    $result.html('<p style="color: orange;">Eliminando todos los datos...</p>');
                    
                    $.post(ajaxurl, {
                        action: 'reset_plugin_data',
                        nonce: '<?php echo wp_create_nonce('reset_plugin_data'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<p style="color: green;">✅ ' + response.data.message + '</p>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<p style="color: red;">❌ Error: ' + response.data.message + '</p>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text('⚠️ Reset Completo');
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}


/**
 * Incluir página de endpoints
 */
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/endpoints-page.php';

/**
 * Gestión de endpoints rápidos
 */
if (!function_exists('handle_endpoint_management')) {
    function handle_endpoint_management() {
        // Procesar formulario rápido de endpoints
        if (isset($_POST['save_quick_endpoint']) && wp_verify_nonce($_POST['quick_endpoint_nonce'], 'save_quick_endpoint')) {
            $endpoints = get_option('bricks_api_endpoints', []);
            
            $new_endpoint = [
                'name' => sanitize_text_field($_POST['endpoint_name']),
                'url' => esc_url_raw($_POST['endpoint_url']),
                'auth_type' => sanitize_text_field($_POST['auth_type'] ?? 'none'),
                'token' => sanitize_text_field($_POST['token'] ?? ''),
                'basic_user' => sanitize_text_field($_POST['basic_user'] ?? ''),
                'basic_password' => sanitize_text_field($_POST['basic_password'] ?? ''),
                'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
                'api_key_header' => sanitize_text_field($_POST['api_key_header'] ?? 'X-API-Key')
            ];
            
            // Añadir el nuevo endpoint
            $endpoints[] = $new_endpoint;
            update_option('bricks_api_endpoints', $endpoints);
            
            // Limpiar cache
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%' OR option_name LIKE '_transient_timeout_api_data_%'");
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>✅ Endpoint guardado correctamente</p></div>';
            });
        }
    }
}
add_action('admin_init', 'handle_endpoint_management');
