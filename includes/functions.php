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
        
        // Debug script solo si WP_DEBUG está activo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'debug-dynamic-tags.js')) {
                wp_enqueue_script('bricks-api-integrator-debug', BRICKS_API_INTEGRATOR_URL . 'debug-dynamic-tags.js', ['jquery'], null, true);
            }
            
            if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'analyze-json-structure.js')) {
                wp_enqueue_script('bricks-api-integrator-analyze', BRICKS_API_INTEGRATOR_URL . 'analyze-json-structure.js', ['jquery'], null, true);
            }
            
            if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'test-field-extractor.js')) {
                wp_enqueue_script('bricks-api-integrator-test', BRICKS_API_INTEGRATOR_URL . 'test-field-extractor.js', ['jquery'], null, true);
            }
            
            if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'verificar-tags.js')) {
                wp_enqueue_script('bricks-api-integrator-verificar', BRICKS_API_INTEGRATOR_URL . 'verificar-tags.js', ['jquery'], null, true);
            }
            
            if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'test-especialidades.js')) {
                wp_enqueue_script('bricks-api-integrator-especialidades', BRICKS_API_INTEGRATOR_URL . 'test-especialidades.js', ['jquery'], null, true);
            }
        }
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
            <h1>🔌 Bricks API Integrator v2.1.1</h1>
            <p>Plugin que integra APIs externas con Bricks Builder de forma dinámica.</p>
            
            <div class="dashboard-stats" style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($endpoints); ?></h3>
                    <p style="margin: 10px 0 0 0;">Endpoints Configurados</p>
                    <small style="color: #666;">Generan Query Types (Auto)</small>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($sources); ?></h3>
                    <p style="margin: 10px 0 0 0;">Query Types Manuales</p>
                    <small style="color: #666;">Para ítems anidados</small>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($templates); ?></h3>
                    <p style="margin: 10px 0 0 0;">Templates Configurados</p>
                    <small style="color: #666;">Plantillas reutilizables</small>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($endpoints) + count($sources); ?></h3>
                    <p style="margin: 10px 0 0 0;">Query Types Total</p>
                    <small style="color: #666;">Auto + Manual</small>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo (count($endpoints) * 8) + (count($sources) * 6); ?></h3>
                    <p style="margin: 10px 0 0 0;">Dynamic Tags</p>
                    <small style="color: #666;">Prefijo: snap_</small>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo function_exists('bricks_is_builder') ? '✅' : '❌'; ?></h3>
                    <p style="margin: 10px 0 0 0;">Bricks Builder</p>
                    <small style="color: #666;">Compatibilidad</small>
                </div>
            </div>
            
            <!-- Sistema diferenciado AUTO/MANUAL -->
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">🎯 Sistema Diferenciado</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0;">
                    <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">🤖 Query Types Automáticos</h4>
                        <p style="margin: 0 0 8px 0; font-size: 14px;">Se crean automáticamente al configurar un <strong>Endpoint</strong></p>
                        <ul style="margin: 8px 0; padding-left: 20px; font-size: 13px;">
                            <li>Aparecen como: <code>Nombre (Auto)</code></li>
                            <li>Tags: <code>{snap_auto_endpoint_campo}</code></li>
                            <li>Perfectos para APIs simples</li>
                        </ul>
                    </div>
                    
                    <div style="background: #fff3e0; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800;">
                        <h4 style="margin: 0 0 10px 0; color: #ff9800;">⚙️ Query Types Manuales</h4>
                        <p style="margin: 0 0 8px 0; font-size: 14px;">Se crean manualmente en <strong>Query Types</strong> para ítems anidados</p>
                        <ul style="margin: 8px 0; padding-left: 20px; font-size: 13px;">
                            <li>Aparecen como: <code>Nombre (Manual)</code></li>
                            <li>Tags: <code>{snap_source_campo}</code></li>
                            <li>Ideales para arrays anidados</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Botones de gestión simplificados -->
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">🔧 Gestión del Plugin</h3>
                <p>Herramientas para el mantenimiento y configuración del plugin:</p>
                
                <!-- Configuración de caché -->
                <div style="background: #f8f9fa; padding: 12px; border-radius: 5px; margin-bottom: 15px;">
                    <h4 style="margin: 0 0 8px 0;">⚡ Configuración de Caché</h4>
                    <label style="display: inline-block; margin-right: 15px;">
                        <strong>Duración del caché:</strong>
                        <select id="cache-duration" style="margin-left: 8px;">
                            <option value="0">Sin caché (siempre actualizado)</option>
                            <option value="60">1 minuto</option>
                            <option value="300" selected>5 minutos (recomendado)</option>
                            <option value="900">15 minutos</option>
                            <option value="3600">1 hora</option>
                        </select>
                    </label>
                    <button type="button" id="update-cache-duration" class="button" style="margin-left: 10px;">💾 Aplicar</button>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #666;">
                        <strong>💡 Nota:</strong> Durante desarrollo usa "Sin caché" o "1 minuto". Para producción usa "5 minutos" o más.
                    </p>
                </div>
                
                <button type="button" id="clear-cache-data" class="button button-primary">🗑️ Limpiar Caché Ahora</button>
                <button type="button" id="reset-plugin-data" class="button" style="margin-left: 10px; background: #dc3232; color: white;">⚠️ Reset</button>
                <div id="management-result" style="margin-top: 10px;"></div>
            </div>
            
            <h2>🚀 Primeros Pasos</h2>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">📋 Flujo de Trabajo Recomendado</h3>
                
                <div style="margin: 15px 0;">
                    <h4 style="color: #0073aa;">1️⃣ Para APIs Simples (Query Types Automáticos)</h4>
                    <ol style="margin-left: 20px;">
                        <li><strong>Configurar Endpoint:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-integrator-endpoints'); ?>">API Endpoints</a></li>
                        <li><strong>Se crea automáticamente:</strong> Query Type con sufijo <code>(Auto)</code></li>
                        <li><strong>Dynamic Tags:</strong> Prefijo <code>{snap_auto_nombre_campo}</code></li>
                        <li><strong>Usar en Bricks:</strong> Query Loop → Seleccionar el Query Type automático</li>
                    </ol>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4 style="color: #ff9800;">2️⃣ Para APIs con Ítems Anidados (Query Types Manuales)</h4>
                    <ol style="margin-left: 20px;">
                        <li><strong>Primero:</strong> Configurar el Endpoint base (paso 1)</li>
                        <li><strong>Crear Query Type Manual:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-integrator-sources'); ?>">Query Types</a></li>
                        <li><strong>Configurar Items Path:</strong> Ejemplo: <code>data.productos</code> para acceder a arrays anidados</li>
                        <li><strong>Dynamic Tags:</strong> Prefijo <code>{snap_nombre_campo}</code></li>
                        <li><strong>Usar en Bricks:</strong> Query Loop → Seleccionar el Query Type manual <code>(Manual)</code></li>
                    </ol>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4 style="color: #28a745;">3️⃣ Templates y Reutilización</h4>
                    <ol style="margin-left: 20px;">
                        <li><strong>Crear Templates:</strong> Ve a <a href="<?php echo admin_url('admin.php?page=bricks-api-templates'); ?>">API Templates</a></li>
                        <li><strong>Reutilizar configuraciones:</strong> Para endpoints similares</li>
                    </ol>
                </div>
            </div>
            
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">💡 Ejemplo Práctico</h3>
                <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    <strong>API Response:</strong><br>
                    {<br>
                    &nbsp;&nbsp;"data": {<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;"productos": [<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{"nombre": "Producto 1", "precio": 100},<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{"nombre": "Producto 2", "precio": 200}<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;]<br>
                    &nbsp;&nbsp;}<br>
                    }
                </div>
                <p style="margin: 10px 0;"><strong>Solución:</strong></p>
                <ul style="margin-left: 20px;">
                    <li><strong>Endpoint:</strong> Crea query type automático para acceder a los datos generales</li>
                    <li><strong>Query Type Manual:</strong> Con Items Path <code>data.productos</code> para iterar sobre los productos</li>
                    <li><strong>Tags disponibles:</strong> <code>{snap_productos_nombre}</code>, <code>{snap_productos_precio}</code></li>
                </ul>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Cargar configuración de caché actual
                loadCacheSettings();
                
                // Actualizar duración de caché
                $('#update-cache-duration').click(function() {
                    var duration = $('#cache-duration').val();
                    var $button = $(this);
                    var $result = $('#management-result');
                    
                    $button.prop('disabled', true).text('💾 Aplicando...');
                    
                    $.post(ajaxurl, {
                        action: 'update_cache_duration',
                        duration: duration,
                        nonce: '<?php echo wp_create_nonce('update_cache_duration'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<p style="color: green;">✅ Configuración de caché actualizada</p>');
                            // Limpiar caché automáticamente después de cambiar configuración
                            clearApiCache();
                        } else {
                            $result.html('<p style="color: red;">❌ Error al actualizar configuración</p>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text('💾 Aplicar');
                        setTimeout(function() {
                            $result.html('');
                        }, 3000);
                    });
                });
                
                // Limpiar cache
                $('#clear-cache-data').click(function() {
                    clearApiCache();
                });
                
                // Función para limpiar caché
                function clearApiCache() {
                    var $button = $('#clear-cache-data');
                    var $result = $('#management-result');
                    
                    $button.prop('disabled', true).text('🔄 Limpiando...');
                    $result.html('<p style="color: orange;">🔄 Limpiando caché...</p>');
                    
                    $.post(ajaxurl, {
                        action: 'clear_api_cache',
                        nonce: '<?php echo wp_create_nonce('clear_api_cache'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<p style="color: green;">✅ Caché limpiado correctamente</p>');
                        } else {
                            $result.html('<p style="color: red;">❌ Error al limpiar caché</p>');
                        }
                    }).always(function() {
                        $button.prop('disabled', false).text('🗑️ Limpiar Caché Ahora');
                        setTimeout(function() {
                            $result.html('');
                        }, 3000);
                    });
                }
                
                // Cargar configuración actual
                function loadCacheSettings() {
                    $.post(ajaxurl, {
                        action: 'get_cache_duration',
                        nonce: '<?php echo wp_create_nonce('get_cache_duration'); ?>'
                    }, function(response) {
                        if (response.success && response.data.duration) {
                            $('#cache-duration').val(response.data.duration);
                        }
                    });
                }
                
                // Reset completo con alerta de advertencia
                $('#reset-plugin-data').click(function() {
                    if (!confirm('⚠️ ADVERTENCIA: Esta acción eliminará TODOS los datos de las APIs configuradas.\n\n• Se perderán todos los endpoints configurados\n• Se eliminarán todos los query types\n• Se borrarán todas las configuraciones\n\n¿Estás completamente seguro de que quieres continuar?')) {
                        return;
                    }
                    
                    // Segunda confirmación
                    if (!confirm('🚨 ÚLTIMA ADVERTENCIA\n\nEsta acción NO se puede deshacer.\nTodos los datos del plugin se perderán permanentemente.\n\n¿Proceder con el reset completo?')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $result = $('#management-result');
                    
                    $button.prop('disabled', true).text('⚠️ Reseteando...');
                    $result.html('<p style="color: red;">⚠️ Eliminando todos los datos del plugin...</p>');
                    
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
                        $button.prop('disabled', false).text('⚠️ Reset');
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}


/**
 * Incluir páginas de administración
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
