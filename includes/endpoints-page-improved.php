<?php
/**
 * Página de gestión de API Endpoints - VERSIÓN MEJORADA CON ACORDEONES Y AUTENTICACIÓN DINÁMICA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('render_api_endpoints_page_improved')) {
    function render_api_endpoints_page_improved() {
        // Procesar formulario
        if (isset($_POST['save_endpoints']) && wp_verify_nonce($_POST['_wpnonce'], 'save_endpoints')) {
            $endpoints = [];
            
            if (isset($_POST['endpoints']) && is_array($_POST['endpoints'])) {
                foreach ($_POST['endpoints'] as $endpoint) {
                    if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                        // Procesar parámetros dinámicos
                        $dynamic_params = [];
                        if (isset($endpoint['param_names']) && is_array($endpoint['param_names'])) {
                            foreach ($endpoint['param_names'] as $param_index => $param_name) {
                                if (!empty($param_name)) {
                                    $dynamic_params[] = [
                                        'name' => sanitize_text_field($param_name),
                                        'source' => sanitize_text_field($endpoint['param_sources'][$param_index] ?? 'url'),
                                        'default' => sanitize_text_field($endpoint['param_defaults'][$param_index] ?? '')
                                    ];
                                }
                            }
                        }
                        
                        $endpoints[] = [
                            'name' => sanitize_text_field($endpoint['name']),
                            'url' => esc_url_raw($endpoint['url']),
                            'auth_type' => sanitize_text_field($endpoint['auth_type'] ?? 'none'),
                            'token' => sanitize_text_field($endpoint['token'] ?? ''),
                            'basic_user' => sanitize_text_field($endpoint['basic_user'] ?? ''),
                            'basic_password' => sanitize_text_field($endpoint['basic_password'] ?? ''),
                            'api_key' => sanitize_text_field($endpoint['api_key'] ?? ''),
                            'api_key_header' => sanitize_text_field($endpoint['api_key_header'] ?? 'X-API-Key'),
                            'dynamic_params' => $dynamic_params
                        ];
                    }
                }
            }
            
            update_option('bricks_api_endpoints', $endpoints);
            
            // Limpiar cache
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%'");
            
            echo '<div class="notice notice-success"><p>✅ Endpoints guardados correctamente</p></div>';
        }
        
        $endpoints = get_option('bricks_api_endpoints', []);
        ?>
        <div class="wrap">
            <h1>🔗 API Endpoints</h1>
            <p>Gestiona tus endpoints de API. Los Query Types y Dynamic Tags se generan automáticamente.</p>
            
            <form method="post" id="endpoints-form">
                <?php wp_nonce_field('save_endpoints'); ?>
                
                <div id="endpoints-container">
                    <?php foreach ($endpoints as $index => $endpoint): ?>
                        <?php render_endpoint_accordion($endpoint, $index); ?>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="add-endpoint" class="button">➕ Añadir Endpoint</button>
                    <button type="button" id="add-related-endpoint" class="button" style="background: #6c757d; color: white; margin-left: 10px;">🔗 Añadir Endpoint Relacionado</button>
                </div>
                
                <p class="description" style="margin-top: 10px;">
                    <strong>💡 Tipos de Endpoints:</strong><br>
                    • <strong>"➕ Añadir Endpoint"</strong> - API genérica independiente<br>
                    • <strong>"🔗 Endpoint Relacionado"</strong> - API que usa el ID/slug del post actual (comentarios, reviews, etc.)
                </p>
                
                <p class="submit">
                    <input type="submit" name="save_endpoints" class="button-primary" value="💾 Guardar Endpoints">
                    <button type="button" id="save-and-test" class="button" style="background: #28a745; color: white; margin-left: 10px;">💾 Guardar y Probar Todos</button>
                </p>
            </form>
        </div>
        <?php
    }
    
    // Función helper para renderizar un acordeón de endpoint
    function render_endpoint_accordion($endpoint, $index) {
        $is_related = !empty($endpoint['dynamic_params']) && 
                     count($endpoint['dynamic_params']) > 0 && 
                     $endpoint['dynamic_params'][0]['source'] === 'post';
        
        $related_class = $is_related ? 'related' : '';
        $related_title = $is_related ? '🔗 Endpoint Relacionado' : '🌐 Endpoint';
        ?>
        
        <div class="endpoint-accordion" data-index="<?php echo $index; ?>">
            <div class="endpoint-header <?php echo $related_class; ?>">
                <div class="endpoint-title">
                    <span><?php echo $related_title . ' ' . ($index + 1) . (!empty($endpoint['name']) ? ' - ' . esc_html($endpoint['name']) : ''); ?></span>
                    <div class="status-indicator" data-endpoint="<?php echo $index; ?>"></div>
                </div>
                <div class="endpoint-status">
                    <span class="endpoint-url-preview">
                        <?php 
                        if (!empty($endpoint['url'])) {
                            $short_url = strlen($endpoint['url']) > 40 ? substr($endpoint['url'], 0, 37) . '...' : $endpoint['url'];
                            echo esc_html($short_url);
                        } else {
                            echo 'Sin configurar';
                        }
                        ?>
                    </span>
                    <span class="collapse-icon">🔽</span>
                </div>
            </div>
            
            <div class="endpoint-content expanded">
                <!-- Configuración Básica -->
                <div class="form-section">
                    <h4>📊 Configuración Básica</h4>
                    <div class="input-group">
                        <label>Nombre del Endpoint</label>
                        <input type="text" name="endpoints[<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($endpoint['name']); ?>" 
                               placeholder="Nombre descriptivo del endpoint" 
                               class="endpoint-name-input" required>
                    </div>
                    <div class="input-group">
                        <label>URL del Endpoint</label>
                        <input type="url" name="endpoints[<?php echo $index; ?>][url]" 
                               value="<?php echo esc_attr($endpoint['url']); ?>" 
                               placeholder="https://api.ejemplo.com/datos" 
                               class="endpoint-url-input" required>
                        <?php if ($is_related): ?>
                            <p class="description">Placeholders disponibles: <code>{post_id}</code>, <code>{post_slug}</code>, <code>{user_id}</code></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Autenticación -->
                <div class="form-section auth-section <?php echo ($endpoint['auth_type'] !== 'none') ? 'show-inputs' : ''; ?>">
                    <h4>🔐 Autenticación</h4>
                    <div class="input-group">
                        <label>Tipo de Autenticación</label>
                        <select name="endpoints[<?php echo $index; ?>][auth_type]" class="auth-type-select">
                            <option value="none" <?php selected($endpoint['auth_type'], 'none'); ?>>Sin Autenticación</option>
                            <option value="token" <?php selected($endpoint['auth_type'], 'token'); ?>>Bearer Token</option>
                            <option value="basic" <?php selected($endpoint['auth_type'], 'basic'); ?>>Basic Auth</option>
                            <option value="api_key" <?php selected($endpoint['auth_type'], 'api_key'); ?>>API Key</option>
                        </select>
                    </div>
                    
                    <div class="auth-inputs <?php echo ($endpoint['auth_type'] !== 'none') ? 'show' : ''; ?>">
                        <?php if ($endpoint['auth_type'] === 'token'): ?>
                            <div class="input-group">
                                <label>Bearer Token</label>
                                <input type="text" name="endpoints[<?php echo $index; ?>][token]" 
                                       value="<?php echo esc_attr($endpoint['token']); ?>" 
                                       placeholder="Ingresa tu Bearer Token" class="regular-text">
                                <p class="description">Token de autorización para la API</p>
                            </div>
                        <?php elseif ($endpoint['auth_type'] === 'basic'): ?>
                            <div class="input-group">
                                <label>Usuario</label>
                                <input type="text" name="endpoints[<?php echo $index; ?>][basic_user]" 
                                       value="<?php echo esc_attr($endpoint['basic_user']); ?>" 
                                       placeholder="Usuario" class="regular-text">
                            </div>
                            <div class="input-group">
                                <label>Contraseña</label>
                                <input type="password" name="endpoints[<?php echo $index; ?>][basic_password]" 
                                       value="<?php echo esc_attr($endpoint['basic_password']); ?>" 
                                       placeholder="Contraseña" class="regular-text">
                            </div>
                        <?php elseif ($endpoint['auth_type'] === 'api_key'): ?>
                            <div class="input-group">
                                <label>API Key</label>
                                <input type="text" name="endpoints[<?php echo $index; ?>][api_key]" 
                                       value="<?php echo esc_attr($endpoint['api_key']); ?>" 
                                       placeholder="Ingresa tu API Key" class="regular-text">
                            </div>
                            <div class="input-group">
                                <label>Header Name</label>
                                <input type="text" name="endpoints[<?php echo $index; ?>][api_key_header]" 
                                       value="<?php echo esc_attr($endpoint['api_key_header'] ?: 'X-API-Key'); ?>" 
                                       placeholder="X-API-Key" class="regular-text">
                                <p class="description">Nombre del header donde se enviará la API Key</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>                
                <!-- Parámetros Dinámicos -->
                <div class="form-section params-section">
                    <h4>🎯 Parámetros Dinámicos</h4>
                    <div class="dynamic-params-container" data-endpoint="<?php echo $index; ?>">
                        <?php if (!empty($endpoint['dynamic_params'])): ?>
                            <?php foreach ($endpoint['dynamic_params'] as $param_index => $param): ?>
                                <div class="dynamic-param-row">
                                    <input type="text" name="endpoints[<?php echo $index; ?>][param_names][]" 
                                           value="<?php echo esc_attr($param['name']); ?>" 
                                           placeholder="Nombre del parámetro (ej: id, slug)">
                                    
                                    <select name="endpoints[<?php echo $index; ?>][param_sources][]">
                                        <option value="url" <?php selected($param['source'], 'url'); ?>>Parámetro URL (?id=123)</option>
                                        <option value="post" <?php selected($param['source'], 'post'); ?>>ID del Post Actual</option>
                                        <option value="post_slug" <?php selected($param['source'], 'post_slug'); ?>>Slug del Post Actual</option>
                                        <option value="user" <?php selected($param['source'], 'user'); ?>>ID del Usuario Actual</option>
                                        <option value="meta" <?php selected($param['source'], 'meta'); ?>>Meta Field del Post</option>
                                        <option value="static" <?php selected($param['source'], 'static'); ?>>Valor Estático</option>
                                    </select>
                                    
                                    <input type="text" name="endpoints[<?php echo $index; ?>][param_defaults][]" 
                                           value="<?php echo esc_attr($param['default']); ?>" 
                                           placeholder="Valor por defecto">
                                    
                                    <button type="button" class="btn btn-danger remove-param">🗑️</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic;">No hay parámetros dinámicos configurados</p>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary add-param" data-endpoint="<?php echo $index; ?>" style="margin-top: 10px;">
                        ➕ Añadir Parámetro
                    </button>
                    <p class="description" style="margin-top: 10px;">
                        <?php if ($is_related): ?>
                            <strong>🔗 Endpoint Relacionado:</strong> Este endpoint usará automáticamente datos del post actual de WordPress.
                            <br><strong>Ejemplos de uso:</strong> Comentarios de un producto, reviews de un servicio, items relacionados, etc.
                        <?php else: ?>
                            <strong>Para páginas de detalle:</strong> Configura parámetros como "id" o "slug" que se tomarán de la URL actual o del post.
                            <br><strong>Ejemplo:</strong> Si tu API necesita <code>?id=123</code>, agrega parámetro "id" con fuente "Parámetro URL".
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Acciones -->
                <div class="form-section actions-section">
                    <button type="button" class="btn btn-primary test-endpoint" data-index="<?php echo $index; ?>">
                        🧪 Test API
                    </button>
                    <button type="button" class="btn btn-warning show-dynamic-tags" data-index="<?php echo $index; ?>">
                        🏷️ Ver Dynamic Tags
                    </button>
                    <button type="button" class="btn btn-success refresh-endpoint-data" data-index="<?php echo $index; ?>">
                        🔄 Actualizar Datos
                    </button>
                    <button type="button" class="btn btn-danger remove-endpoint" data-index="<?php echo $index; ?>">
                        🗑️ Eliminar
                    </button>
                </div>
                
                <!-- Resultado de pruebas -->
                <div class="test-result" id="test-result-<?php echo $index; ?>"></div>
                
                <!-- Accordion para Dynamic Tags -->
                <div class="dynamic-tags-accordion" id="dynamic-tags-<?php echo $index; ?>" style="display: none;">
                    <div class="dynamic-tags-content">
                        <h4 style="margin: 0 0 10px 0; color: #007cba;">🏷️ Dynamic Tags Disponibles</h4>
                        <div class="tags-loading" style="text-align: center; padding: 20px;">
                            <span style="color: #666;">⏳ Generando dynamic tags...</span>
                        </div>
                        <div class="tags-list" style="display: none;">
                            <!-- Se llenará con AJAX -->
                        </div>
                        <div class="tags-help" style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 3px;">
                            <p style="margin: 0; font-size: 13px; color: #0073aa;">
                                💡 <strong>Cómo usar:</strong> Copia y pega estos tags en tus elementos de Bricks Builder. 
                                Los tags con <code>_count</code>, <code>_first</code>, <code>_join</code> son especiales para arrays.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
    }
}
?>