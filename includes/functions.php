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
 * Callback para la página de API Sources
 */
function bricks_api_integrator_sources_page() {
    // Esta función debe estar definida en sources.php
    if (function_exists('render_api_sources_page')) {
        render_api_sources_page();
    } else {
        echo '<div class="wrap"><h1>API Sources</h1><p>Error: No se pudo cargar la página de API Sources.</p></div>';
    }
}

/**
 * Callback para la página de API Launcher
 */
function bricks_api_integrator_launcher_page() {
    // Esta función debe estar definida en launcher.php
    if (function_exists('render_api_launcher_page')) {
        render_api_launcher_page();
    } else {
        echo '<div class="wrap"><h1>API Launcher</h1><p>Error: No se pudo cargar la página de API Launcher.</p></div>';
    }
}

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
        
        // Submenús existentes
        add_submenu_page(
            'bricks-api-integrator',
            __('API Sources', 'bricks-api-integrator'),
            __('API Sources', 'bricks-api-integrator'),
            'manage_options',
            'bricks-api-integrator-sources',
            'render_api_sources_page'
        );
        
        add_submenu_page(
            'bricks-api-integrator',
            __('API Launcher', 'bricks-api-integrator'),
            __('API Launcher', 'bricks-api-integrator'),
            'manage_options',
            'bricks-api-integrator-launcher',
            'render_api_launcher_page'
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
        $launchers = get_option('bricks_api_launchers', []);
        $templates = get_option('bricks_api_templates', []);
        
        // Obtener información detallada
        $total_dynamic_tags = 0;
        if (class_exists('BricksAPIIntegrator')) {
            try {
                $integrator_instance = new BricksAPIIntegrator();
                $total_dynamic_tags = $integrator_instance->count_dynamic_tags();
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
                    <?php if (!empty($endpoints)): ?>
                        <small style="color: #666;">
                            <?php 
                            $endpoint_names = array_column($endpoints, 'name');
                            echo implode(', ', array_slice($endpoint_names, 0, 2));
                            if (count($endpoint_names) > 2) echo '...';
                            ?>
                        </small>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($sources); ?></h3>
                    <p style="margin: 10px 0 0 0;">Sources Configurados</p>
                    <?php if (!empty($sources)): ?>
                        <small style="color: #666;">
                            <?php 
                            $source_names = array_column($sources, 'name');
                            echo implode(', ', array_slice($source_names, 0, 2));
                            if (count($source_names) > 2) echo '...';
                            ?>
                        </small>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($launchers); ?></h3>
                    <p style="margin: 10px 0 0 0;">Launchers Configurados</p>
                    <?php if (!empty($launchers)): ?>
                        <small style="color: #666;">
                            <?php 
                            $launcher_names = array_column($launchers, 'name');
                            echo implode(', ', array_slice($launcher_names, 0, 2));
                            if (count($launcher_names) > 2) echo '...';
                            ?>
                        </small>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($templates); ?></h3>
                    <p style="margin: 10px 0 0 0;">Templates Configurados</p>
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
                <button type="button" id="reset-plugin-data" class="button button-secondary" style="margin-left: 10px; background: #dc3232; color: white;">⚠️ Reset Completo</button>
                <div id="regenerate-result" style="margin-top: 10px;"></div>
            </div>
            
            <h2>📋 Funcionalidades Nuevas v2.0</h2>
            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;">
                <ul style="margin: 0;">
                    <li><strong>Query Types Dinámicos:</strong> Los endpoints aparecen automáticamente como tipos de consulta en Bricks</li>
                    <li><strong>Dynamic Tags Automáticos:</strong> Se generan tags dinámicos basados en la estructura de datos de la API</li>
                    <li><strong>Cache Inteligente:</strong> Sistema de cache optimizado para mejorar la velocidad</li>
                    <li><strong>Integración Nativa:</strong> Funciona directamente con Query Loop de Bricks sin configuración adicional</li>
                </ul>
            </div>
            
            <h2>🚀 Primeros Pasos</h2>
            <ol>
                <li><strong>Configurar Endpoints:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-integrator-sources'); ?>">API Sources</a> para añadir tus APIs</li>
                <li><strong>Configurar Launchers:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-integrator-launcher'); ?>">API Launcher</a> para configurar launchers</li>
                <li><strong>Configurar Templates:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-templates'); ?>">API Templates</a> para gestionar templates</li>
                <li><strong>Usar en Bricks:</strong> Los Query Types aparecerán automáticamente en el Query Loop de Bricks</li>
                <li><strong>Dynamic Tags:</strong> Los tags se generan automáticamente desde las respuestas de API</li>
            </ol>
            
            <!-- Gestor de Endpoints Básico -->
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">⚡ Gestor Rápido de Endpoints</h3>
                <p>Si no puedes acceder a la página de endpoints, úsalo aquí:</p>
                
                <form method="post" id="quick-endpoint-form">
                    <?php wp_nonce_field('save_quick_endpoint', 'quick_endpoint_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="endpoint_name">Nombre del Endpoint</label></th>
                            <td><input type="text" id="endpoint_name" name="endpoint_name" class="regular-text" placeholder="Ej: API Clínicas" required></td>
                        </tr>
                        <tr>
                            <th><label for="endpoint_url">URL del Endpoint</label></th>
                            <td><input type="url" id="endpoint_url" name="endpoint_url" class="regular-text" placeholder="https://api.ejemplo.com/datos" required></td>
                        </tr>
                        <tr>
                            <th><label for="auth_type">Autenticación</label></th>
                            <td>
                                <select id="auth_type" name="auth_type">
                                    <option value="none">Sin Autenticación</option>
                                    <option value="token">Bearer Token</option>
                                    <option value="basic">Basic Auth</option>
                                    <option value="api_key">API Key</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="token_field" style="display: none;">
                            <th><label for="token">Token</label></th>
                            <td><input type="text" id="token" name="token" class="regular-text"></td>
                        </tr>
                        <tr id="basic_fields" style="display: none;">
                            <th><label>Usuario/Contraseña</label></th>
                            <td>
                                <input type="text" name="basic_user" placeholder="Usuario" style="width: 48%;">
                                <input type="password" name="basic_password" placeholder="Contraseña" style="width: 48%; margin-left: 2%;">
                            </td>
                        </tr>
                        <tr id="api_key_fields" style="display: none;">
                            <th><label>API Key</label></th>
                            <td>
                                <input type="text" name="api_key" placeholder="API Key" style="width: 48%;">
                                <input type="text" name="api_key_header" placeholder="Header (ej: X-API-Key)" value="X-API-Key" style="width: 48%; margin-left: 2%;">
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="save_quick_endpoint" class="button-primary" value="💾 Guardar Endpoint">
                        <button type="button" id="test_endpoint" class="button" style="margin-left: 10px;">🧪 Test</button>
                    </p>
                </form>
                
                <div id="test_result" style="margin-top: 10px;"></div>
            </div>
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <h2>🔧 Debug</h2>
            <p>Modo debug activo. Usa el shortcode: <code>[debug_api_integrator]</code></p>
            <?php endif; ?>
            
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
                
                // Reset completo
                $('#reset-plugin-data').click(function() {
                    if (!confirm('⚠️ ADVERTENCIA: Esto eliminará TODOS los datos del plugin (endpoints, sources, launchers, templates). ¿Estás seguro?')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $result = $('#regenerate-result');
                    
                    $button.prop('disabled', true).text('⚠️ Reseteando...');
                    $result.html('<p style="color: orange;">Eliminando todos los datos del plugin...</p>');
                    
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
 * Gestión simplificada de endpoints desde el dashboard
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
            
            // Limpiar cache después de actualizar
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%' OR option_name LIKE '_transient_timeout_api_data_%'");
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>✅ Endpoint guardado correctamente y cache limpiado</p></div>';
            });
        }
        
        // Procesar formulario original si existe
        if (isset($_POST['save_simple_endpoints']) && wp_verify_nonce($_POST['_wpnonce'], 'save_simple_endpoints')) {
            $endpoints = [];
            
            if (isset($_POST['endpoints']) && is_array($_POST['endpoints'])) {
                foreach ($_POST['endpoints'] as $index => $endpoint) {
                    if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                        $endpoints[$index] = [
                            'name' => sanitize_text_field($endpoint['name']),
                            'url' => esc_url_raw($endpoint['url']),
                            'auth_type' => sanitize_text_field($endpoint['auth_type'] ?? 'none'),
                            'token' => sanitize_text_field($endpoint['token'] ?? ''),
                            'basic_user' => sanitize_text_field($endpoint['basic_user'] ?? ''),
                            'basic_password' => sanitize_text_field($endpoint['basic_password'] ?? ''),
                            'api_key' => sanitize_text_field($endpoint['api_key'] ?? ''),
                            'api_key_header' => sanitize_text_field($endpoint['api_key_header'] ?? 'X-API-Key')
                        ];
                    }
                }
            }
            
            update_option('bricks_api_endpoints', $endpoints);
            
            // Limpiar cache después de actualizar
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%' OR option_name LIKE '_transient_timeout_api_data_%'");
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>✅ Endpoints guardados y cache limpiado correctamente</p></div>';
            });
        }
    }
}
add_action('admin_init', 'handle_endpoint_management');
            
            <script>
            jQuery(document).ready(function($) {
                // Manejar cambio de tipo de autenticación
                $('#auth_type').change(function() {
                    var type = $(this).val();
                    $('#token_field, #basic_fields, #api_key_fields').hide();
                    
                    if (type === 'token') {
                        $('#token_field').show();
                    } else if (type === 'basic') {
                        $('#basic_fields').show();
                    } else if (type === 'api_key') {
                        $('#api_key_fields').show();
                    }
                });
                
                // Test endpoint
                $('#test_endpoint').click(function() {
                    var url = $('#endpoint_url').val();
                    var $result = $('#test_result');
                    
                    if (!url) {
                        $result.html('<p style="color: red;">❌ Por favor, ingresa una URL</p>');
                        return;
                    }
                    
                    $result.html('<p style="color: orange;">🔄 Probando conexión...</p>');
                    
                    // Hacer una petición simple de test
                    $.ajax({
                        url: url,
                        method: 'GET',
                        timeout: 10000,
                        success: function(data) {
                            $result.html('<p style="color: green;">✅ Conexión exitosa. Respuesta recibida.</p>');
                        },
                        error: function(xhr, status, error) {
                            var message = 'Error de conexión';
                            if (status === 'timeout') {
                                message = 'Timeout - La API tardó demasiado en responder';
                            } else if (xhr.status) {
                                message = 'Error HTTP ' + xhr.status + ': ' + error;
                            }
                            $result.html('<p style="color: red;">❌ ' + message + '</p>');
                        }
                    });
                });
            });
            </script>
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <h2>🔧 Debug</h2>
            <p>Modo debug activo. Usa el shortcode: <code>[debug_api_integrator]</code></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
