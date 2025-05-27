<?php
/**
 * Página de gestión de API Endpoints - VERSIÓN SIMPLE Y FUNCIONAL
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('render_api_endpoints_page')) {
    function render_api_endpoints_page() {
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
            
            <form method="post">
                <?php wp_nonce_field('save_endpoints'); ?>
                
                <div id="endpoints-container">
                    <?php foreach ($endpoints as $index => $endpoint): ?>
                        <div class="endpoint-card" style="background: #fff; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                            <h3>Endpoint <?php echo ($index + 1); ?></h3>
                            
                            <table class="form-table">
                                <tr>
                                    <th><label>Nombre del Endpoint</label></th>
                                    <td>
                                        <input type="text" name="endpoints[<?php echo $index; ?>][name]" 
                                               value="<?php echo esc_attr($endpoint['name']); ?>" 
                                               class="regular-text" placeholder="Nombre del Endpoint" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>URL del Endpoint</label></th>
                                    <td>
                                        <input type="url" name="endpoints[<?php echo $index; ?>][url]" 
                                               value="<?php echo esc_attr($endpoint['url']); ?>" 
                                               class="regular-text" placeholder="https://api.ejemplo.com/datos" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Autenticación</label></th>
                                    <td>
                                        <select name="endpoints[<?php echo $index; ?>][auth_type]">
                                            <option value="none" <?php selected($endpoint['auth_type'], 'none'); ?>>Sin Autenticación</option>
                                            <option value="token" <?php selected($endpoint['auth_type'], 'token'); ?>>Bearer Token</option>
                                            <option value="basic" <?php selected($endpoint['auth_type'], 'basic'); ?>>Basic Auth</option>
                                            <option value="api_key" <?php selected($endpoint['auth_type'], 'api_key'); ?>>API Key</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php if ($endpoint['auth_type'] === 'token'): ?>
                                <table class="form-table">
                                    <tr>
                                        <th><label>Bearer Token</label></th>
                                        <td><input type="text" name="endpoints[<?php echo $index; ?>][token]" value="<?php echo esc_attr($endpoint['token']); ?>" class="regular-text"></td>
                                    </tr>
                                </table>
                            <?php elseif ($endpoint['auth_type'] === 'basic'): ?>
                                <table class="form-table">
                                    <tr>
                                        <th><label>Usuario</label></th>
                                        <td><input type="text" name="endpoints[<?php echo $index; ?>][basic_user]" value="<?php echo esc_attr($endpoint['basic_user']); ?>" class="regular-text"></td>
                                    </tr>
                                    <tr>
                                        <th><label>Contraseña</label></th>
                                        <td><input type="password" name="endpoints[<?php echo $index; ?>][basic_password]" value="<?php echo esc_attr($endpoint['basic_password']); ?>" class="regular-text"></td>
                                    </tr>
                                </table>
                            <?php elseif ($endpoint['auth_type'] === 'api_key'): ?>
                                <table class="form-table">
                                    <tr>
                                        <th><label>API Key</label></th>
                                        <td><input type="text" name="endpoints[<?php echo $index; ?>][api_key]" value="<?php echo esc_attr($endpoint['api_key']); ?>" class="regular-text"></td>
                                    </tr>
                                    <tr>
                                        <th><label>Header Name</label></th>
                                        <td><input type="text" name="endpoints[<?php echo $index; ?>][api_key_header]" value="<?php echo esc_attr($endpoint['api_key_header'] ?: 'X-API-Key'); ?>" class="regular-text"></td>
                                    </tr>
                                </table>
                            <?php endif; ?>
                            
                            <!-- Parámetros Dinámicos -->
                            <table class="form-table">
                                <tr>
                                    <th><label>Parámetros Dinámicos</label></th>
                                    <td>
                                        <div id="dynamic-params-container-<?php echo $index; ?>">
                                            <?php if (!empty($endpoint['dynamic_params'])): ?>
                                                <?php foreach ($endpoint['dynamic_params'] as $param_index => $param): ?>
                                                    <div class="dynamic-param-row" style="margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px;">
                                                        <input type="text" name="endpoints[<?php echo $index; ?>][param_names][]" 
                                                               placeholder="Nombre del parámetro (ej: id, slug)" 
                                                               class="regular-text" 
                                                               value="<?php echo esc_attr($param['name']); ?>" style="width: 200px;">
                                                        
                                                        <select name="endpoints[<?php echo $index; ?>][param_sources][]" style="width: 180px;">
                                                            <option value="url" <?php selected($param['source'], 'url'); ?>>Parámetro URL (?id=123)</option>
                                                            <option value="post" <?php selected($param['source'], 'post'); ?>>ID del Post Actual</option>
                                                            <option value="post_slug" <?php selected($param['source'], 'post_slug'); ?>>Slug del Post Actual</option>
                                                            <option value="user" <?php selected($param['source'], 'user'); ?>>ID del Usuario Actual</option>
                                                            <option value="meta" <?php selected($param['source'], 'meta'); ?>>Meta Field del Post</option>
                                                            <option value="static" <?php selected($param['source'], 'static'); ?>>Valor Estático</option>
                                                        </select>
                                                        
                                                        <input type="text" name="endpoints[<?php echo $index; ?>][param_defaults][]" 
                                                               placeholder="Valor por defecto" 
                                                               class="regular-text" 
                                                               value="<?php echo esc_attr($param['default']); ?>" style="width: 150px;">
                                                        
                                                        <button type="button" class="button remove-param" style="color: #a00;">Eliminar</button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p style="color: #666; font-style: italic;">No hay parámetros dinámicos configurados</p>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="button add-param" data-endpoint="<?php echo $index; ?>">➕ Añadir Parámetro</button>
                                        <p class="description" style="margin-top: 10px;">
                                            <strong>Para páginas de detalle:</strong> Configura parámetros como "id" o "slug" que se tomarán de la URL actual o del post.<br>
                                            <strong>Ejemplo:</strong> Si tu API necesita <code>?id=123</code>, agrega parámetro "id" con fuente "Parámetro URL".
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <button type="button" class="button test-endpoint" data-index="<?php echo $index; ?>">🧪 Test API</button>
                            <button type="button" class="button button-link-delete remove-endpoint" data-index="<?php echo $index; ?>" style="color: #a00;">🗑️ Eliminar</button>
                            <div class="test-result" id="test-result-<?php echo $index; ?>" style="margin-top: 10px;"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="add-endpoint" class="button">➕ Añadir Endpoint</button>
                
                <p class="submit">
                    <input type="submit" name="save_endpoints" class="button-primary" value="💾 Guardar Endpoints">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let endpointCounter = <?php echo count($endpoints); ?>;
            
            // Añadir nuevo endpoint
            $('#add-endpoint').click(function() {
                const html = `
                    <div class="endpoint-card" style="background: #fff; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Endpoint ${endpointCounter + 1}</h3>
                        
                        <table class="form-table">
                            <tr>
                                <th><label>Nombre del Endpoint</label></th>
                                <td>
                                    <input type="text" name="endpoints[${endpointCounter}][name]" class="regular-text" placeholder="Nombre del Endpoint" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label>URL del Endpoint</label></th>
                                <td>
                                    <input type="url" name="endpoints[${endpointCounter}][url]" class="regular-text" placeholder="https://api.ejemplo.com/datos" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Autenticación</label></th>
                                <td>
                                    <select name="endpoints[${endpointCounter}][auth_type]">
                                        <option value="none">Sin Autenticación</option>
                                        <option value="token">Bearer Token</option>
                                        <option value="basic">Basic Auth</option>
                                        <option value="api_key">API Key</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Parámetros Dinámicos para nuevos endpoints -->
                        <table class="form-table">
                            <tr>
                                <th><label>Parámetros Dinámicos</label></th>
                                <td>
                                    <div id="dynamic-params-container-${endpointCounter}">
                                        <p style="color: #666; font-style: italic;">No hay parámetros dinámicos configurados</p>
                                    </div>
                                    <button type="button" class="button add-param" data-endpoint="${endpointCounter}">➕ Añadir Parámetro</button>
                                    <p class="description" style="margin-top: 10px;">
                                        <strong>Para páginas de detalle:</strong> Configura parámetros como "id" o "slug" que se tomarán de la URL actual o del post.<br>
                                        <strong>Ejemplo:</strong> Si tu API necesita <code>?id=123</code>, agrega parámetro "id" con fuente "Parámetro URL".
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button button-link-delete remove-endpoint" data-index="${endpointCounter}" style="color: #a00;">🗑️ Eliminar</button>
                    </div>
                `;
                
                $('#endpoints-container').append(html);
                endpointCounter++;
            });
            
            // Event listener para eliminar endpoints
            $(document).on('click', '.remove-endpoint', function(e) {
                e.preventDefault();
                
                const $card = $(this).closest('.endpoint-card');
                const index = parseInt($(this).data('index'));
                
                if (confirm('¿Estás seguro de que quieres eliminar este endpoint?')) {
                    $card.remove();
                    
                    // Reindexar los endpoints restantes
                    $('.endpoint-card').each(function(newIndex) {
                        $(this).find('input, select').each(function() {
                            const name = $(this).attr('name');
                            if (name && name.includes('endpoints[')) {
                                const newName = name.replace(/endpoints\[\d+\]/, `endpoints[${newIndex}]`);
                                $(this).attr('name', newName);
                            }
                        });
                        
                        $(this).find('.remove-endpoint').attr('data-index', newIndex);
                        $(this).find('h3').text(`Endpoint ${newIndex + 1}`);
                    });
                    
                    endpointCounter = $('.endpoint-card').length;
                }
            });
            
            // Test de API endpoint
            $(document).on('click', '.test-endpoint', function() {
                const index = $(this).data('index');
                const $button = $(this);
                const $result = $('#test-result-' + index);
                const $card = $(this).closest('.endpoint-card');
                
                // Obtener datos del endpoint
                const name = $card.find('input[name*="[name]"]').val();
                const url = $card.find('input[name*="[url]"]').val();
                const authType = $card.find('select[name*="[auth_type]"]').val();
                
                if (!url) {
                    $result.html('<p style="color: red;">❌ URL requerida</p>');
                    return;
                }
                
                $button.prop('disabled', true).text('🔄 Probando...');
                $result.html('<p style="color: orange;">🔄 Probando conexión con la API...</p>');
                
                // AJAX call para probar endpoint
                $.post(ajaxurl, {
                    action: 'test_api_endpoint',
                    index: index,
                    nonce: '<?php echo wp_create_nonce('test_api_endpoint'); ?>'
                }, function(response) {
                    if (response.success) {
                        let html = `<div style="color: green; background: #f0f8ff; padding: 10px; border-radius: 3px;">
                            <p><strong>✅ API funcionando correctamente</strong></p>
                            <p><strong>URL Base:</strong> ${response.data.base_url || url}</p>`;
                        
                        if (response.data.test_url && response.data.test_url !== response.data.base_url) {
                            html += `<p><strong>URL con Parámetros:</strong> ${response.data.test_url}</p>`;
                        }
                        
                        html += `<p><strong>Elementos encontrados:</strong> ${response.data.count}</p>`;
                        
                        if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                            html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                        }
                        
                        if (response.data.sample_data) {
                            html += `<p><strong>Datos de ejemplo:</strong></p>`;
                            html += `<pre style="background: #f8f8f8; padding: 10px; border-radius: 3px; max-height: 200px; overflow: auto; font-size: 12px;">${JSON.stringify(response.data.sample_data, null, 2)}</pre>`;
                        }
                        
                        html += '</div>';
                        $result.html(html);
                    } else {
                        let errorHtml = `<div style="color: red; background: #ffeaea; padding: 10px; border-radius: 3px;">
                            <p><strong>❌ Error en la API</strong></p>
                            <p><strong>URL Base:</strong> ${response.data && response.data.base_url ? response.data.base_url : url}</p>`;
                        
                        if (response.data && response.data.test_url && response.data.test_url !== response.data.base_url) {
                            errorHtml += `<p><strong>URL con Parámetros:</strong> ${response.data.test_url}</p>`;
                        }
                        
                        errorHtml += `<p><strong>Error:</strong> ${response.data.message}</p>
                            <p><em>💡 Tip: Si el endpoint requiere parámetros específicos, prueba la URL completa directamente en el navegador.</em></p>
                        </div>`;
                        
                        $result.html(errorHtml);
                    }
                }).fail(function() {
                    $result.html('<p style="color: red;">❌ Error de conexión</p>');
                }).always(function() {
                    $button.prop('disabled', false).text('🧪 Test API');
                });
            });
            
            // Manejar parámetros dinámicos
            $(document).on('click', '.add-param', function() {
                const endpointIndex = $(this).data('endpoint');
                const container = $(`#dynamic-params-container-${endpointIndex}`);
                
                // Remover mensaje de "no hay parámetros" si existe
                container.find('p[style*="italic"]').remove();
                
                const paramHtml = `
                    <div class="dynamic-param-row" style="margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px;">
                        <input type="text" name="endpoints[${endpointIndex}][param_names][]" 
                               placeholder="Nombre del parámetro (ej: id, slug)" 
                               class="regular-text" style="width: 200px;">
                        
                        <select name="endpoints[${endpointIndex}][param_sources][]" style="width: 180px;">
                            <option value="url">Parámetro URL (?id=123)</option>
                            <option value="post">ID del Post Actual</option>
                            <option value="post_slug">Slug del Post Actual</option>
                            <option value="user">ID del Usuario Actual</option>
                            <option value="meta">Meta Field del Post</option>
                            <option value="static">Valor Estático</option>
                        </select>
                        
                        <input type="text" name="endpoints[${endpointIndex}][param_defaults][]" 
                               placeholder="Valor por defecto" 
                               class="regular-text" style="width: 150px;">
                        
                        <button type="button" class="button remove-param" style="color: #a00;">Eliminar</button>
                    </div>
                `;
                
                container.append(paramHtml);
            });
            
            // Eliminar parámetro dinámico
            $(document).on('click', '.remove-param', function() {
                const $row = $(this).closest('.dynamic-param-row');
                const $container = $row.closest('[id*="dynamic-params-container"]');
                
                $row.remove();
                
                // Si no quedan parámetros, mostrar mensaje
                if ($container.find('.dynamic-param-row').length === 0) {
                    $container.html('<p style="color: #666; font-style: italic;">No hay parámetros dinámicos configurados</p>');
                }
            });
        });
        </script>
        <?php
    }
}
