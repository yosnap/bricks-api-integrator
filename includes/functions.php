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

        // Estilos para filtros automáticos de Inmovilla
        wp_enqueue_style('inmovilla-filters-style', BRICKS_API_INTEGRATOR_URL . 'assets/inmovilla-filters.css');

        // Fix para visibilidad de selects
        wp_enqueue_style('inmovilla-selects-fix-style', BRICKS_API_INTEGRATOR_URL . 'assets/inmovilla-selects-fix.css');

        // Enqueue JavaScript
        wp_enqueue_script('bricks-api-integrator-script', BRICKS_API_INTEGRATOR_URL . 'assets/bricks-api-integrator.js', ['jquery'], null, true);
        
        // Script para probar items_path
        wp_enqueue_script('bricks-api-items-path-tester', BRICKS_API_INTEGRATOR_URL . 'assets/items-path-tester.js', ['jquery'], null, true);

        // Script para filtros automáticos de Inmovilla
        wp_enqueue_script('inmovilla-auto-filters', BRICKS_API_INTEGRATOR_URL . 'assets/inmovilla-auto-filters.js', [], null, true);

        // Pasar variables al script principal
        wp_localize_script('bricks-api-integrator-script', 'bricks_api_integrator_vars', [
            'nonce' => wp_create_nonce('test_api_endpoint'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        
        // Pasar variables al script de items_path
        wp_localize_script('bricks-api-items-path-tester', 'bricks_api_vars', [
            'nonce' => wp_create_nonce('test_items_path')
        ]);
        
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
            <h1>🔌 Bricks API Integrator v<?php echo defined('BRICKS_API_INTEGRATOR_VERSION') ? BRICKS_API_INTEGRATOR_VERSION : 'Desconocida'; ?></h1>
            <p>Plugin que integra APIs externas con Bricks Builder de forma dinámica y flexible.</p>
            
            <!-- Estadísticas rápidas -->
            <div class="dashboard-stats" style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($endpoints); ?></h3>
                    <p style="margin: 10px 0 0 0;">Endpoints</p>
                    <small style="color: #666;">APIs conectadas</small>
                </div>
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($sources); ?></h3>
                    <p style="margin: 10px 0 0 0;">Query Types (Sources)</p>
                    <small style="color: #666;">Arrays anidados / filtros</small>
                </div>
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo count($templates); ?></h3>
                    <p style="margin: 10px 0 0 0;">Templates</p>
                    <small style="color: #666;">Plantillas reutilizables</small>
                </div>
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo $total_dynamic_tags; ?></h3>
                    <p style="margin: 10px 0 0 0;">Dynamic Tags</p>
                    <small style="color: #666;">Prefijo: snap_</small>
                </div>
                <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo function_exists('bricks_is_builder') ? '✅' : '❌'; ?></h3>
                    <p style="margin: 10px 0 0 0;">Bricks Builder</p>
                    <small style="color: #666;">Compatibilidad</small>
                </div>
            </div>
            
            <!-- Explicación del sistema -->
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">🧩 ¿Cómo funciona el sistema?</h3>
                <ul style="font-size: 15px; margin-bottom: 0;">
                    <li><strong>Endpoints:</strong> Conecta cualquier API REST. Define la URL, autenticación y parámetros. <a href=\"<?php echo admin_url('admin.php?page=bricks-api-integrator-endpoints'); ?>\">Ir a Endpoints</a></li>
                    <li><strong>Sources (Query Types):</strong> Permiten acceder a arrays anidados, datos específicos, URLs relativas o filtrar por parámetros. Úsalos para APIs complejas. <a href=\"<?php echo admin_url('admin.php?page=bricks-api-integrator-sources'); ?>\">Ir a Query Types</a></li>
                    <li><strong>Templates:</strong> Asocia endpoints o sources a páginas de WordPress para reutilizar layouts en listados o detalles. <a href=\"<?php echo admin_url('admin.php?page=bricks-api-templates'); ?>\">Ir a Templates</a></li>
                    <li><strong>Dynamic Tags:</strong> Siempre debes generarlos desde la sección correspondiente para que estén disponibles en Bricks.</li>
                </ul>
            </div>
            
            <!-- Gestión del Plugin: limpieza de caché, reset y configuración de caché -->
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
            
            <!-- Mini-flujo visual -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">🚦 Flujo de trabajo recomendado</h3>
                <ol style="margin-left: 20px;">
                    <li><strong>1. Crear Endpoint:</strong> <a href=\"<?php echo admin_url('admin.php?page=bricks-api-integrator-endpoints'); ?>\">Configura tu API</a></li>
                    <li><strong>2. (Opcional) Crear Source:</strong> <a href=\"<?php echo admin_url('admin.php?page=bricks-api-integrator-sources'); ?>\">Para arrays anidados o filtros</a></li>
                    <li><strong>3. (Opcional) Crear Template:</strong> <a href=\"<?php echo admin_url('admin.php?page=bricks-api-templates'); ?>\">Para layouts reutilizables</a></li>
                    <li><strong>4. Generar Dynamic Tags:</strong> Desde la sección correspondiente</li>
                    <li><strong>5. Usar en Bricks:</strong> Selecciona el Query Type y usa los tags dinámicos <code>snap_</code></li>
                </ol>
                <div style="margin: 20px 0 0 0;">
                    <h4 style="color: #28a745;">💡 Ejemplo Práctico</h4>
                    <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                        <strong>API Response:</strong><br>
                        {<br>
                        &nbsp;&nbsp;"data": {<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;"vehiculos": [<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{"marca": "Toyota", "modelo": "Corolla"},<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{"marca": "Honda", "modelo": "Civic"}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;]<br>
                        &nbsp;&nbsp;}<br>
                        }
                    </div>
                    <ul style="margin-left: 20px;">
                        <li><strong>Endpoint:</strong> "Vehículos Motor"</li>
                        <li><strong>Query Type (Source):</strong> Si necesitas acceder a <code>data.vehiculos</code></li>
                        <li><strong>Tags:</strong> <code>{snap_vehiculos_marca}</code>, <code>{snap_vehiculos_modelo}</code></li>
                    </ul>
                </div>
                <div style="margin-top: 20px; color: #888; font-size: 13px;">
                    <strong>¿Dudas?</strong> Consulta la documentación o revisa cada sección para ver ejemplos y configuraciones recomendadas.
                </div>
            </div>
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

/**
 * Generar información de debug para soporte de Inmovilla
 * Accesible en: wp-admin/admin.php?page=bricks-api-integrator&debug_inmovilla=1
 */
if (!function_exists('inmovilla_debug_info')) {
    function inmovilla_debug_info() {
        if (!current_user_can('manage_options') || empty($_GET['debug_inmovilla'])) {
            return;
        }

        // Obtener el endpoint de Inmovilla
        $endpoints = get_option('bricks_api_endpoints', []);
        $inmovilla_endpoint = null;

        foreach ($endpoints as $endpoint) {
            if (strpos($endpoint['url'] ?? '', 'apiweb.inmovilla.com') !== false) {
                $inmovilla_endpoint = $endpoint;
                break;
            }
        }

        if (!$inmovilla_endpoint) {
            wp_die('No se encontró endpoint de Inmovilla');
        }

        // Construir la llamada de ejemplo
        $params = [];
        if (!empty($inmovilla_endpoint['dynamic_params'])) {
            foreach ($inmovilla_endpoint['dynamic_params'] as $param) {
                if (!empty($param['default'])) {
                    $params[$param['name']] = $param['default'];
                }
            }
        }

        $agencia = $params['agencia'] ?? '';
        $password = $params['password'] ?? '';
        $idioma = $params['idioma'] ?? '1';
        $lostipos = $params['lostipos'] ?? 'lostipos';
        $tipo = $params['tipo'] ?? 'paginacion';
        $pos = $params['pos'] ?? '1';
        $num = $params['num_elementos'] ?? '20';
        $where = $params['where'] ?? '';
        $orden = $params['orden'] ?? '';
        $ip = $params['ip'] ?? '127.0.0.1';

        $texto = $agencia . ';' . $password . ';' . $idioma . ';' . $lostipos . ';' . $tipo . ';' . $pos . ';' . $num . ';' . $where . ';' . $orden;
        $dominio = $_SERVER['SERVER_NAME'] ?? '';

        $body = 'param=' . rawurlencode($texto) . '&elDominio=' . urlencode($dominio) . '&ia=' . urlencode($ip) . '&ib=&json=1';

        ?>
        <div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #2563eb;">
            <h2>🔧 Información de Debug para Soporte Inmovilla</h2>
            <p style="color: #666; margin-bottom: 20px;">Copia esta información y envíala a <strong>soporte@inmovilla.com</strong></p>

            <h3>Llamada a la API</h3>
            <div style="background: white; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #ddd;">
                <p><strong>URL:</strong></p>
                <code style="display: block; padding: 10px; background: #f9f9f9; border-radius: 4px; word-wrap: break-word;"><?php echo esc_html($inmovilla_endpoint['url']); ?></code>

                <p style="margin-top: 15px;"><strong>Método:</strong></p>
                <code style="display: block; padding: 10px; background: #f9f9f9; border-radius: 4px;">POST</code>

                <p style="margin-top: 15px;"><strong>Body (parámetros):</strong></p>
                <code style="display: block; padding: 10px; background: #f9f9f9; border-radius: 4px; word-wrap: break-word; white-space: normal;">
                    param=<?php echo esc_html(rawurlencode($texto)); ?>&elDominio=<?php echo esc_html(urlencode($dominio)); ?>&ia=<?php echo esc_html(urlencode($ip)); ?>&ib=&json=1
                </code>

                <p style="margin-top: 15px;"><strong>Desglose de parámetros:</strong></p>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="background: #f0f0f0;">
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Parámetro</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Valor</th>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">agencia</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($agencia); ?></code></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">password</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($password); ?></code></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">idioma</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($idioma); ?></code></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">tipo</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($tipo); ?></code></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">IP (ia)</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($ip); ?></code></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">dominio (elDominio)</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($dominio); ?></code></td>
                    </tr>
                </table>
            </div>

            <h3>Información del Servidor</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                <tr style="background: #f0f0f0;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Propiedad</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Valor</th>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">Servidor</td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($dominio); ?></code></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">IP del Servidor</td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html($ip); ?></code></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">WordPress Version</td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><code><?php echo esc_html(get_bloginfo('version')); ?></code></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">Plugin Version</td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><code>2.1.1</code></td>
                </tr>
            </table>

            <p style="margin-top: 20px; color: #888; font-size: 12px;">
                <strong>Instrucciones:</strong> Copia toda la información de "Llamada a la API" y envíala a soporte@inmovilla.com explicando que necesitas autorizar tu IP para esta agencia.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'inmovilla_debug_info');
