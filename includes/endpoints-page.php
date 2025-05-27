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
                        <!-- Acordeón Simple - Solo Header y Contenido -->
                        <div class="endpoint-accordion" style="background: #fff; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
                            <div class="endpoint-header" onclick="toggleEndpoint(<?php echo $index; ?>)" style="background: #f1f1f1; padding: 15px; cursor: pointer; border-bottom: 1px solid #ddd;">
                                <h3 style="margin: 0; display: inline-block;">Endpoint <?php echo ($index + 1); ?> - <?php echo esc_html($endpoint['name'] ?: 'Sin nombre'); ?></h3>
                                <span id="toggle-icon-<?php echo $index; ?>" style="float: right; font-size: 18px;">🔽</span>
                            </div>
                            
                            <div id="endpoint-content-<?php echo $index; ?>" class="endpoint-content" style="padding: 20px;">
                            
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
                            <button type="button" class="button show-dynamic-tags" data-index="<?php echo $index; ?>">🏷️ Ver Dynamic Tags</button>
                            <button type="button" class="button refresh-endpoint-data" data-index="<?php echo $index; ?>" style="background: #28a745; color: white; margin-left: 5px;">🔄 Actualizar Datos</button>
                            <button type="button" class="button button-link-delete remove-endpoint" data-index="<?php echo $index; ?>" style="color: #a00;">🗑️ Eliminar</button>
                            
                            <!-- Accordion para Dynamic Tags -->
                            <div class="dynamic-tags-accordion" id="dynamic-tags-<?php echo $index; ?>" style="display: none; margin-top: 15px;">
                                <div class="dynamic-tags-content" style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba;">
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
                            
                            <div class="test-result" id="test-result-<?php echo $index; ?>" style="margin-top: 10px;"></div>
                            </div> <!-- Cierre endpoint-content -->
                        </div> <!-- Cierre endpoint-accordion -->
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="add-endpoint" class="button">➕ Añadir Endpoint</button>
                <button type="button" id="add-related-endpoint" class="button" style="background: #6c757d; color: white; margin-left: 10px;">🔗 Añadir Endpoint Relacionado</button>
                
                <p class="description" style="margin-top: 10px;">
                    <strong>💡 Tipos de Endpoints:</strong><br>
                    • <strong>"➕ Añadir Endpoint"</strong> - API genérica independiente<br>
                    • <strong>"🔗 Endpoint Relacionado"</strong> - API que usa el ID/slug del post actual (comentarios, reviews, etc.)
                </p>
                
                <p class="submit">
                    <input type="submit" name="save_endpoints" class="button-primary" value="💾 Guardar Endpoints">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let endpointCounter = <?php echo count($endpoints); ?>;
            
            // Inicializar nonce inmediatamente para debug
            window.currentNonce = '<?php echo wp_create_nonce('get_dynamic_tags'); ?>';
            console.log('Nonce inicializado:', window.currentNonce);
            
            // Añadir nuevo endpoint
            $('#add-endpoint').click(function() {
                console.log('🔘 Botón Añadir Endpoint clickeado');
                console.log('🔘 endpointCounter actual:', endpointCounter);
                try {
                    addGenericEndpoint();
                    console.log('✅ Endpoint genérico añadido correctamente');
                } catch (error) {
                    console.error('❌ Error al añadir endpoint genérico:', error);
                }
            });
            
            // Añadir endpoint relacionado (genérico con parámetros del post actual)
            $('#add-related-endpoint').click(function() {
                console.log('🔗 Botón Añadir Endpoint Relacionado clickeado');
                try {
                    addRelatedEndpoint();
                    console.log('✅ Endpoint relacionado añadido correctamente');
                } catch (error) {
                    console.error('❌ Error al añadir endpoint relacionado:', error);
                }
            });
            
            // Función para añadir endpoint genérico
            function addGenericEndpoint() {
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
                        
                        <button type="button" class="button test-endpoint" data-index="${endpointCounter}">🧪 Test API</button>
                        <button type="button" class="button show-dynamic-tags" data-index="${endpointCounter}" style="margin-left: 10px;">🏷️ Ver Dynamic Tags</button>
                        <button type="button" class="button refresh-endpoint-data" data-index="${endpointCounter}" style="background: #28a745; color: white; margin-left: 5px;">🔄 Actualizar Datos</button>
                        <button type="button" class="button button-link-delete remove-endpoint" data-index="${endpointCounter}" style="color: #a00; margin-left: 10px;">🗑️ Eliminar</button>
                        
                        <!-- Accordion para Dynamic Tags -->
                        <div class="dynamic-tags-accordion" id="dynamic-tags-${endpointCounter}" style="display: none; margin-top: 15px;">
                            <div class="dynamic-tags-content" style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba;">
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
                `;
                
                $('#endpoints-container').append(html);
                endpointCounter++;
            }
            
            // Función para añadir endpoint relacionado (genérico con parámetros del post)
            function addRelatedEndpoint() {
                const html = `
                    <div class="endpoint-card" style="background: #fff; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; border-left: 4px solid #6c757d;">
                        <h3>🔗 Endpoint ${endpointCounter + 1} - Relacionado</h3>
                        
                        <table class="form-table">
                            <tr>
                                <th><label>Nombre del Endpoint</label></th>
                                <td>
                                    <input type="text" name="endpoints[${endpointCounter}][name]" value="" class="regular-text" placeholder="Ej: Comentarios, Reviews, Productos relacionados" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label>URL del Endpoint</label></th>
                                <td>
                                    <input type="url" name="endpoints[${endpointCounter}][url]" value="" class="regular-text" placeholder="https://api.ejemplo.com/posts/{post_id}/comentarios" required>
                                    <p class="description">
                                        <strong>Placeholders disponibles:</strong> 
                                        <code>{post_id}</code> (ID del post), 
                                        <code>{post_slug}</code> (slug del post), 
                                        <code>{user_id}</code> (ID del usuario)
                                    </p>
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
                        
                        <!-- Parámetros Dinámicos Pre-configurados para post actual -->
                        <table class="form-table">
                            <tr>
                                <th><label>Parámetros Dinámicos</label></th>
                                <td>
                                    <div id="dynamic-params-container-${endpointCounter}">
                                        <div class="dynamic-param-row" style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px;">
                                            <input type="text" name="endpoints[${endpointCounter}][param_names][]" value="post_id" class="regular-text" style="width: 200px;" placeholder="Nombre del parámetro">
                                            <select name="endpoints[${endpointCounter}][param_sources][]" style="width: 180px;">
                                                <option value="post" selected>ID del Post Actual</option>
                                                <option value="post_slug">Slug del Post Actual</option>
                                                <option value="url">Parámetro URL (?id=123)</option>
                                                <option value="meta">Meta Field del Post</option>
                                                <option value="user">ID del Usuario Actual</option>
                                                <option value="static">Valor Estático</option>
                                            </select>
                                            <input type="text" name="endpoints[${endpointCounter}][param_defaults][]" value="" placeholder="Valor por defecto (opcional)" class="regular-text" style="width: 150px;">
                                            <button type="button" class="button remove-param" style="color: #a00;">Eliminar</button>
                                        </div>
                                    </div>
                                    <button type="button" class="button add-param" data-endpoint="${endpointCounter}">➕ Añadir Parámetro</button>
                                    <p class="description" style="margin-top: 10px;">
                                        <strong>🔗 Endpoint Relacionado:</strong> Este endpoint usará automáticamente el ID/slug del post actual de WordPress.
                                        <br><strong>Ejemplos de uso:</strong> Comentarios de un producto, reviews de un servicio, items relacionados, etc.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button test-endpoint" data-index="${endpointCounter}">🧪 Test API</button>
                        <button type="button" class="button show-dynamic-tags" data-index="${endpointCounter}" style="margin-left: 10px;">🏷️ Ver Dynamic Tags</button>
                        <button type="button" class="button refresh-endpoint-data" data-index="${endpointCounter}" style="background: #28a745; color: white; margin-left: 5px;">🔄 Actualizar Datos</button>
                        <button type="button" class="button button-link-delete remove-endpoint" data-index="${endpointCounter}" style="color: #a00; margin-left: 10px;">🗑️ Eliminar</button>
                        
                        <!-- Accordion para Dynamic Tags -->
                        <div class="dynamic-tags-accordion" id="dynamic-tags-${endpointCounter}" style="display: none; margin-top: 15px;">
                            <div class="dynamic-tags-content" style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #6c757d;">
                                <h4 style="margin: 0 0 10px 0; color: #6c757d;">🔗 Dynamic Tags Relacionados</h4>
                                <div class="tags-loading" style="text-align: center; padding: 20px;">
                                    <span style="color: #666;">⏳ Generando dynamic tags...</span>
                                </div>
                                <div class="tags-list" style="display: none;">
                                    <!-- Se llenará con AJAX -->
                                </div>
                                <div class="tags-help" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 3px;">
                                    <p style="margin: 0; font-size: 13px; color: #495057;">
                                        💡 <strong>Uso dinámico:</strong> Estos tags se basan en el contenido relacionado al post actual.
                                        Perfectos para Query Loops de contenido relacionado.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#endpoints-container').append(html);
                endpointCounter++;
            }
            
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
            
            // Actualizar datos del endpoint (forzar refresh)
            $(document).on('click', '.refresh-endpoint-data', function() {
                const index = $(this).data('index');
                const $button = $(this);
                const $result = $('#test-result-' + index);
                const $card = $(this).closest('.endpoint-card');
                
                // Obtener datos del endpoint
                const name = $card.find('input[name*="[name]"]').val();
                const url = $card.find('input[name*="[url]"]').val();
                
                if (!url) {
                    $result.html('<p style="color: red;">❌ URL requerida</p>');
                    return;
                }
                
                $button.prop('disabled', true).text('🔄 Actualizando...');
                $result.html('<p style="color: orange;">🔄 Forzando actualización de datos desde la API...</p>');
                
                // AJAX call para actualizar endpoint con force_refresh=true
                $.post(ajaxurl, {
                    action: 'refresh_endpoint_data',
                    index: index,
                    nonce: '<?php echo wp_create_nonce('refresh_endpoint_data'); ?>'
                }, function(response) {
                    if (response.success) {
                        let html = `<div style="color: green; background: #f0f8ff; padding: 10px; border-radius: 3px;">
                            <p><strong>✅ Datos actualizados correctamente</strong></p>
                            <p><strong>Elementos encontrados:</strong> ${response.data.count}</p>`;
                        
                        if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                            html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                        }
                        
                        if (response.data.cache_cleared) {
                            html += `<p><strong>🗑️ Caché limpiado:</strong> Los datos se han actualizado desde la API</p>`;
                        }
                        
                        html += '</div>';
                        $result.html(html);
                    } else {
                        $result.html(`<div style="color: red; background: #ffeaea; padding: 10px; border-radius: 3px;">
                            <p><strong>❌ Error al actualizar datos</strong></p>
                            <p><strong>Error:</strong> ${response.data.message}</p>
                        </div>`);
                    }
                }).fail(function() {
                    $result.html('<p style="color: red;">❌ Error de conexión</p>');
                }).always(function() {
                    $button.prop('disabled', false).text('🔄 Actualizar Datos');
                });
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
            
            // Mostrar/ocultar dynamic tags - VERSIÓN CORREGIDA
            $(document).on('click', '.show-dynamic-tags', function() {
                const index = $(this).data('index');
                const $accordion = $('#dynamic-tags-' + index);
                const $button = $(this);
                
                console.log('🔘 Botón Dynamic Tags clickeado para index:', index);
                console.log('🔘 Accordion visible:', $accordion.is(':visible'));
                
                if ($accordion.is(':visible')) {
                    // Ocultar accordion
                    console.log('🔘 Ocultando accordion');
                    $accordion.slideUp();
                    $button.text('🏷️ Ver Dynamic Tags');
                } else {
                    // Mostrar accordion y cargar tags
                    console.log('🔘 Mostrando accordion y cargando tags');
                    $accordion.slideDown();
                    $button.text('🏷️ Ocultar Dynamic Tags');
                    
                    // SIEMPRE cargar tags (eliminar verificación problemática)
                    console.log('🔘 Llamando a loadDynamicTags...');
                    loadDynamicTags(index);
                }
            });
            
            // Función para cargar dynamic tags via AJAX - VERSIÓN CORREGIDA
            function loadDynamicTags(index) {
                console.log('🚀 loadDynamicTags called for index:', index);
                
                const $accordion = $('#dynamic-tags-' + index);
                const $loading = $accordion.find('.tags-loading');
                const $tagsList = $accordion.find('.tags-list');
                
                console.log('DOM elements check:', {
                    accordion: $accordion.length,
                    loading: $loading.length,
                    tagsList: $tagsList.length,
                    accordionVisible: $accordion.is(':visible')
                });
                
                // Asegurar que el accordion esté visible
                if (!$accordion.is(':visible')) {
                    $accordion.show();
                    console.log('Accordion mostrado forzadamente');
                }
                
                $loading.show();
                $tagsList.hide();
                
                // Obtener datos del endpoint
                const $card = $('[data-index="' + index + '"]').closest('.endpoint-card');
                const name = $card.find('input[name*="[name]"]').val();
                const url = $card.find('input[name*="[url]"]').val();
                
                console.log('Endpoint data:', {name, url, index});
                
                if (!name || !url) {
                    console.error('❌ Datos del endpoint incompletos');
                    $loading.html('<span style="color: #d63638;">❌ Configura el nombre y URL del endpoint primero</span>');
                    return;
                }
                
                console.log('🔄 Enviando petición AJAX...');
                
                // AJAX call para obtener dynamic tags
                $.post(ajaxurl, {
                    action: 'get_dynamic_tags_for_endpoint',
                    index: index,
                    nonce: window.currentNonce || '<?php echo wp_create_nonce('get_dynamic_tags'); ?>'
                }, function(response) {
                    console.log('📥 AJAX Response recibida:', response);
                    
                    if (response.success && response.data.tags && response.data.tags.length > 0) {
                        console.log('✅ Procesando tags exitosamente - ' + response.data.tags.length + ' tags encontrados');
                        
                        // Obtener datos de ejemplo si están disponibles
                        const sampleData = response.data.sample_data || response.data.fields_info || {};
                        console.log('🔍 Datos de ejemplo disponibles:', sampleData);
                        
                        // Generar HTML con valores de ejemplo en dos columnas
                        let html = '<div style="padding: 15px;">';
                        html += '<h4 style="color: #007cba; margin-bottom: 15px;">✅ Dynamic Tags Generados (' + response.data.tags.length + ')</h4>';
                        
                        // Mostrar información del endpoint
                        if (response.data.endpoint_name) {
                            html += '<div style="background: #e7f3ff; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #0073aa;">';
                            html += '<strong>📊 Endpoint:</strong> ' + response.data.endpoint_name;
                            if (response.data.data_count) {
                                html += ' | <strong>Elementos:</strong> ' + response.data.data_count;
                            }
                            if (response.data.has_dynamic_params) {
                                html += ' | <strong>✨ Parámetros dinámicos:</strong> Sí';
                            }
                            html += '</div>';
                        }
                        
                        html += '<div style="display: grid; gap: 3px; font-family: monospace; font-size: 12px;">';
                        
                        response.data.tags.forEach(function(tag) {
                            // Extraer el nombre del campo del tag
                            const fieldMatch = tag.match(/\{snap_[^_]+_(.+)\}/);
                            const fieldName = fieldMatch ? fieldMatch[1] : '';
                            
                            console.log('🏷️ Procesando tag:', tag, '-> campo:', fieldName);
                            
                            // Buscar valor de ejemplo
                            let sampleValue = '';
                            if (fieldName && sampleData && typeof sampleData === 'object') {
                                sampleValue = findSampleValue(sampleData, fieldName);
                                console.log('📄 Valor encontrado para', fieldName, ':', sampleValue);
                            }
                            
                            html += '<div style="background: #f8f9fa; padding: 8px 10px; border-radius: 3px; border-left: 3px solid #007cba; cursor: pointer; transition: all 0.2s; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; align-items: center;" onclick="copyToClipboard(\'' + tag + '\', this)" title="Clic para copiar">';
                            
                            // Columna 1: Tag
                            html += '<div style="display: flex; align-items: center;">';
                            html += '<code style="color: #d63384; font-weight: bold; font-size: 11px;">' + tag + '</code>';
                            html += '</div>';
                            
                            // Columna 2: Valor de ejemplo
                            html += '<div style="color: #666; font-size: 10px; text-align: right; max-width: 200px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">';
                            if (sampleValue) {
                                html += '<span style="background: #e8f5e8; padding: 2px 6px; border-radius: 2px; color: #2d5a2d;">📄 ' + escapeHtml(sampleValue) + '</span>';
                            } else {
                                html += '<span style="color: #999; font-style: italic;">sin ejemplo</span>';
                            }
                            html += '</div>';
                            
                            html += '</div>';
                        });
                        
                        html += '</div>';
                        html += '<p style="margin-top: 15px; padding: 10px; background: #d1ecf1; border-radius: 3px; font-size: 13px;">💡 <strong>Cómo usar:</strong> Haz clic en cualquier tag para copiarlo. Los valores de ejemplo te ayudan a entender qué datos obtendrás.</p>';
                        html += '</div>';
                        
                        // Función helper para buscar valores de ejemplo - MEJORADA
                        function findSampleValue(data, fieldName) {
                            console.log('🔍 Buscando valor para campo:', fieldName, 'en data:', data);
                            
                            // Búsqueda directa
                            if (data[fieldName] !== undefined) {
                                console.log('✅ Encontrado directo:', data[fieldName]);
                                return formatSampleValue(data[fieldName]);
                            }
                            
                            // Búsqueda normalizada (convertir guiones bajos y mayúsculas)
                            const normalizedField = fieldName.toLowerCase().replace(/_/g, '').replace(/[áéíóúñ]/g, function(match) {
                                const accents = { 'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ñ': 'n' };
                                return accents[match] || match;
                            });
                            
                            for (const key in data) {
                                const normalizedKey = key.toLowerCase().replace(/[_-]/g, '').replace(/[áéíóúñ]/g, function(match) {
                                    const accents = { 'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ñ': 'n' };
                                    return accents[match] || match;
                                });
                                if (normalizedKey === normalizedField) {
                                    console.log('✅ Encontrado normalizado:', key, '=', data[key]);
                                    return formatSampleValue(data[key]);
                                }
                            }
                            
                            // Manejo especial para campos con patrones
                            
                            // 1. Especialidades count
                            if (fieldName.includes('especialidades') && fieldName.includes('count')) {
                                if (data.especialidades && Array.isArray(data.especialidades)) {
                                    console.log('✅ Especialidades count:', data.especialidades.length);
                                    return data.especialidades.length.toString();
                                }
                            }
                            
                            // 2. Especialidades first_*
                            if (fieldName.includes('especialidades_first_')) {
                                const subField = fieldName.replace('especialidades_first_', '');
                                if (data.especialidades && Array.isArray(data.especialidades) && data.especialidades[0]) {
                                    const firstEsp = data.especialidades[0];
                                    console.log('🔍 Buscando en primera especialidad:', subField, 'en', firstEsp);
                                    
                                    // Búsqueda directa en la primera especialidad
                                    if (firstEsp[subField] !== undefined) {
                                        console.log('✅ Encontrado en primera esp:', firstEsp[subField]);
                                        return formatSampleValue(firstEsp[subField]);
                                    }
                                    
                                    // Búsqueda normalizada en la primera especialidad
                                    for (const key in firstEsp) {
                                        const normalizedKey = key.toLowerCase().replace(/[_-]/g, '');
                                        const normalizedSubField = subField.toLowerCase().replace(/[_-]/g, '');
                                        if (normalizedKey === normalizedSubField) {
                                            console.log('✅ Encontrado normalizado en primera esp:', key, '=', firstEsp[key]);
                                            return formatSampleValue(firstEsp[key]);
                                        }
                                    }
                                }
                            }
                            
                            // 3. Especialidades last_*
                            if (fieldName.includes('especialidades_last_')) {
                                const subField = fieldName.replace('especialidades_last_', '');
                                if (data.especialidades && Array.isArray(data.especialidades) && data.especialidades.length > 0) {
                                    const lastEsp = data.especialidades[data.especialidades.length - 1];
                                    console.log('🔍 Buscando en última especialidad:', subField, 'en', lastEsp);
                                    
                                    if (lastEsp[subField] !== undefined) {
                                        console.log('✅ Encontrado en última esp:', lastEsp[subField]);
                                        return formatSampleValue(lastEsp[subField]);
                                    }
                                    
                                    for (const key in lastEsp) {
                                        const normalizedKey = key.toLowerCase().replace(/[_-]/g, '');
                                        const normalizedSubField = subField.toLowerCase().replace(/[_-]/g, '');
                                        if (normalizedKey === normalizedSubField) {
                                            console.log('✅ Encontrado normalizado en última esp:', key, '=', lastEsp[key]);
                                            return formatSampleValue(lastEsp[key]);
                                        }
                                    }
                                }
                            }
                            
                            // 4. Especialidades item_*
                            if (fieldName.includes('especialidades_item_')) {
                                const subField = fieldName.replace('especialidades_item_', '');
                                if (data.especialidades && Array.isArray(data.especialidades) && data.especialidades[0]) {
                                    const itemEsp = data.especialidades[0]; // Usar primera como ejemplo
                                    console.log('🔍 Buscando en item especialidad:', subField, 'en', itemEsp);
                                    
                                    if (itemEsp[subField] !== undefined) {
                                        console.log('✅ Encontrado en item esp:', itemEsp[subField]);
                                        return formatSampleValue(itemEsp[subField]);
                                    }
                                    
                                    for (const key in itemEsp) {
                                        const normalizedKey = key.toLowerCase().replace(/[_-]/g, '');
                                        const normalizedSubField = subField.toLowerCase().replace(/[_-]/g, '');
                                        if (normalizedKey === normalizedSubField) {
                                            console.log('✅ Encontrado normalizado en item esp:', key, '=', itemEsp[key]);
                                            return formatSampleValue(itemEsp[key]);
                                        }
                                    }
                                }
                            }
                            
                            // 5. Horario
                            if (fieldName.includes('horario_')) {
                                const subField = fieldName.replace('horario_', '');
                                if (data.horario && typeof data.horario === 'object') {
                                    console.log('🔍 Buscando en horario:', subField, 'en', data.horario);
                                    
                                    if (data.horario[subField] !== undefined) {
                                        console.log('✅ Encontrado en horario:', data.horario[subField]);
                                        return formatSampleValue(data.horario[subField]);
                                    }
                                    
                                    for (const key in data.horario) {
                                        const normalizedKey = key.toLowerCase().replace(/[_-]/g, '');
                                        const normalizedSubField = subField.toLowerCase().replace(/[_-]/g, '');
                                        if (normalizedKey === normalizedSubField) {
                                            console.log('✅ Encontrado normalizado en horario:', key, '=', data.horario[key]);
                                            return formatSampleValue(data.horario[key]);
                                        }
                                    }
                                }
                            }
                            
                            // Búsqueda en objetos anidados (recursiva)
                            for (const key in data) {
                                if (typeof data[key] === 'object' && data[key] !== null) {
                                    if (Array.isArray(data[key]) && data[key].length > 0) {
                                        // Buscar en el primer elemento del array
                                        const result = findSampleValue(data[key][0], fieldName);
                                        if (result) {
                                            console.log('✅ Encontrado en array anidado:', result);
                                            return result;
                                        }
                                    } else {
                                        // Buscar en objeto anidado
                                        const result = findSampleValue(data[key], fieldName);
                                        if (result) {
                                            console.log('✅ Encontrado en objeto anidado:', result);
                                            return result;
                                        }
                                    }
                                }
                            }
                            
                            console.log('❌ No encontrado valor para:', fieldName);
                            return null;
                        }
                        
                        function formatSampleValue(value) {
                            if (value === null || value === undefined) return '';
                            if (typeof value === 'boolean') return value ? 'Sí' : 'No';
                            if (typeof value === 'object') {
                                if (Array.isArray(value)) {
                                    return value.length + ' elementos';
                                }
                                return 'Objeto (' + Object.keys(value).length + ' campos)';
                            }
                            const strValue = String(value);
                            // Truncar si es muy largo, pero mostrar más caracteres para mejor contexto
                            return strValue.length > 80 ? strValue.substring(0, 77) + '...' : strValue;
                        }
                        
                        function escapeHtml(text) {
                            const div = document.createElement('div');
                            div.textContent = text;
                            return div.innerHTML;
                        }
                        
                        // Actualizar la interfaz
                        console.log('🎨 Actualizando interfaz con valores de ejemplo...');
                        $loading.hide();
                        $tagsList.html(html);
                        $tagsList.show();
                        
                        console.log('✅ Tags con valores de ejemplo mostrados correctamente');
                        
                    } else if (response.success) {
                        console.warn('⚠️ Response exitosa pero sin tags');
                        $loading.html('<div style="color: #f0ad4e; padding: 15px;">⚠️ No se generaron dynamic tags. Verifica que el endpoint devuelve datos válidos.</div>');
                        
                    } else {
                        console.error('❌ Error del servidor:', response.data);
                        let errorMessage = response.data ? response.data.message : 'Error desconocido';
                        $loading.html('<div style="color: #d63638; padding: 15px; background: #ffeaea; border-radius: 3px;"><strong>❌ Error:</strong> ' + errorMessage + '</div>');
                    }
                    
                }).fail(function(xhr, status, error) {
                    console.error('❌ AJAX Failed:', {
                        status: status, 
                        error: error, 
                        responseText: xhr.responseText.substring(0, 200),
                        readyState: xhr.readyState,
                        statusCode: xhr.status
                    });
                    $loading.html('<div style="color: #d63638; padding: 15px; background: #ffeaea; border-radius: 3px;"><strong>❌ Error de conexión:</strong> ' + status + '</div>');
                });
            }
            
            // Función para mostrar los dynamic tags
            function displayDynamicTags(data, $tagsList, $loading) {
                console.log('Displaying dynamic tags:', data); // Debug
                
                let html = '';
                
                if (data.tags && data.tags.length > 0) {
                    // Agrupar tags por tipo
                    const basicTags = [];
                    const arrayTags = [];
                    
                    data.tags.forEach(tag => {
                        if (tag.includes('_count') || tag.includes('_first') || tag.includes('_join') || tag.includes('_json')) {
                            arrayTags.push(tag);
                        } else {
                            basicTags.push(tag);
                        }
                    });
                    
                    // Tags básicos
                    if (basicTags.length > 0) {
                        html += '<div class="tags-group" style="margin-bottom: 15px;">';
                        html += '<h5 style="margin: 0 0 8px 0; color: #2c3e50;">📝 Campos Básicos (' + basicTags.length + ')</h5>';
                        html += '<div class="tags-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 8px;">';
                        
                        basicTags.forEach(tag => {
                            html += `<div class="tag-item" style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 12px; cursor: pointer; transition: all 0.2s ease;" onclick="copyToClipboard('${tag}', this)" title="Clic para copiar">
                                <code style="color: #e74c3c; font-weight: bold;">${tag}</code>
                            </div>`;
                        });
                        
                        html += '</div></div>';
                    }
                    
                    // Tags de arrays
                    if (arrayTags.length > 0) {
                        html += '<div class="tags-group" style="margin-bottom: 15px;">';
                        html += '<h5 style="margin: 0 0 8px 0; color: #e67e22;">🔢 Campos de Arrays (' + arrayTags.length + ')</h5>';
                        html += '<div class="tags-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 8px;">';
                        
                        arrayTags.forEach(tag => {
                            let tagType = '';
                            if (tag.includes('_count')) tagType = ' <small style="color: #856404;">(cantidad)</small>';
                            else if (tag.includes('_first')) tagType = ' <small style="color: #856404;">(primero)</small>';
                            else if (tag.includes('_join')) tagType = ' <small style="color: #856404;">(unidos)</small>';
                            else if (tag.includes('_json')) tagType = ' <small style="color: #856404;">(JSON)</small>';
                            
                            html += `<div class="tag-item" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 12px; cursor: pointer; transition: all 0.2s ease;" onclick="copyToClipboard('${tag}', this)" title="Clic para copiar">
                                <code style="color: #d68910; font-weight: bold;">${tag}</code>${tagType}
                            </div>`;
                        });
                        
                        html += '</div></div>';
                    }
                    
                    // Si no hay arrays, mostrar mensaje informativo
                    if (arrayTags.length === 0) {
                        html += '<div style="margin-top: 10px; padding: 10px; background: #e8f4fd; border-radius: 3px; border-left: 3px solid #0073aa;">';
                        html += '<p style="margin: 0; font-size: 13px; color: #0073aa;"><strong>💡 Tip:</strong> No se detectaron arrays en los datos. Si tu API tiene arrays, agrega parámetros dinámicos para obtener datos más complejos.</p>';
                        html += '</div>';
                    }
                    
                    // Información adicional
                    html += '<div style="margin-top: 15px; padding: 12px; background: #d1ecf1; border-radius: 3px; border-left: 3px solid #17a2b8;">';
                    html += '<h6 style="margin: 0 0 5px 0; color: #0c5460;">📊 Información del Endpoint</h6>';
                    html += '<div style="font-size: 13px; color: #0c5460;">';
                    html += `<strong>Nombre:</strong> ${data.endpoint_name}<br>`;
                    html += `<strong>Tags generados:</strong> ${data.tags.length}<br>`;
                    if (data.data_type) {
                        html += `<strong>Tipo de datos:</strong> ${data.data_type}`;
                        if (data.data_count) {
                            html += ` (${data.data_count} elementos)`;
                        }
                        html += '<br>';
                    }
                    if (data.has_dynamic_params) {
                        html += '<strong>✨ Parámetros dinámicos:</strong> Habilitados<br>';
                    }
                    html += '</div></div>';
                    
                } else {
                    html = '<div style="text-align: center; padding: 30px; color: #666; background: #f8f9fa; border-radius: 5px; border: 2px dashed #ddd;">';
                    html += '<div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>';
                    html += '<h4 style="margin: 0 0 10px 0; color: #495057;">No se generaron dynamic tags</h4>';
                    html += '<p style="margin: 0; font-size: 14px;">Verifica que el endpoint tenga datos válidos y que la API esté respondiendo correctamente.</p>';
                    html += '</div>';
                }
                
                console.log('Generated HTML length:', html.length); // Debug
                
                $tagsList.html(html);
                $loading.hide();
                $tagsList.show();
                
                console.log('Tags displayed successfully'); // Debug
            }
            
            // Función para copiar al clipboard (simplificada)
            window.copyToClipboard = function(text, element) {
                console.log('Copying to clipboard:', text); // Debug
                
                // Método moderno
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyFeedback(element, text);
                    }).catch(function(err) {
                        console.log('Clipboard error:', err);
                        fallbackCopyTextToClipboard(text, element);
                    });
                } else {
                    // Fallback para navegadores más antiguos
                    fallbackCopyTextToClipboard(text, element);
                }
            };
            
            // Fallback para copiar al portapapeles
            function fallbackCopyTextToClipboard(text, element) {
                var textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.top = "-9999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        showCopyFeedback(element, text);
                    }
                } catch (err) {
                    console.log('Fallback copy failed:', err);
                }
                
                document.body.removeChild(textArea);
            }
            
            // Mostrar feedback visual al copiar
            function showCopyFeedback(element, text) {
                // Feedback visual
                const originalBg = element.style.backgroundColor;
                const originalColor = element.style.color;
                
                element.style.backgroundColor = '#28a745';
                element.style.color = '#fff';
                element.style.transform = 'scale(1.02)';
                
                setTimeout(() => {
                    element.style.backgroundColor = originalBg;
                    element.style.color = originalColor;
                    element.style.transform = 'scale(1)';
                }, 500);
                
                // Mostrar mensaje temporal
                const messageDiv = document.createElement('div');
                messageDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 12px 20px; border-radius: 5px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
                messageDiv.innerHTML = '📋 Copiado: ' + text;
                document.body.appendChild(messageDiv);
                
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transition = 'opacity 0.3s';
                    setTimeout(() => {
                        if (messageDiv.parentNode) {
                            messageDiv.parentNode.removeChild(messageDiv);
                        }
                    }, 300);
                }, 2000);
                
                console.log('Copy feedback shown for:', text);
            }
            
            // Función de debug para probar AJAX (solo para desarrollo)
            window.testDynamicTagsAjax = function(index) {
                console.log('Testing AJAX for endpoint index:', index);
                
                $.post(ajaxurl, {
                    action: 'get_dynamic_tags_for_endpoint',
                    index: index,
                    nonce: window.currentNonce || '<?php echo wp_create_nonce('get_dynamic_tags'); ?>'
                }, function(response) {
                    console.log('✅ AJAX Success:', response);
                }).fail(function(xhr, status, error) {
                    console.log('❌ AJAX Failed:', {
                        status: status, 
                        error: error, 
                        response: xhr.responseText,
                        readyState: xhr.readyState,
                        statusCode: xhr.status
                    });
                });
            };
            
            // Función helper para probar la interfaz directamente
            window.testShowDynamicTags = function(index) {
                console.log('🧪 Testing show dynamic tags for index:', index);
                const $accordion = $('#dynamic-tags-' + index);
                console.log('🧪 Accordion found:', $accordion.length);
                
                if ($accordion.length > 0) {
                    $accordion.show();
                    console.log('🧪 Accordion mostrado, llamando loadDynamicTags...');
                    loadDynamicTags(index);
                } else {
                    console.error('🧪 ❌ Accordion no encontrado para index:', index);
                }
            };
            
            // Log para debug
            console.log('🚀 Bricks API Integrator - Dynamic Tags system loaded');
            console.log('💡 Usa testShowDynamicTags(index) para probar tags de un endpoint específico');
            
            // Verificar que los botones existen
            console.log('🔘 Botones encontrados:');
            console.log('- add-endpoint:', $('#add-endpoint').length);
            console.log('- add-related-endpoint:', $('#add-related-endpoint').length);
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            console.log('🔧 Modo debug activo - Funciones adicionales disponibles en la consola');
            <?php endif; ?>
            
            // FUNCIÓN SIMPLE PARA ACORDEONES - Solo toggle mostrar/ocultar
            window.toggleEndpoint = function(index) {
                const content = document.getElementById('endpoint-content-' + index);
                const icon = document.getElementById('toggle-icon-' + index);
                
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.innerHTML = '🔽';
                } else {
                    content.style.display = 'none';
                    icon.innerHTML = '▶️';
                }
            };
            
            // FUNCIÓN SIMPLE PARA AUTENTICACIÓN DINÁMICA
            $(document).on('change', 'select[name*="[auth_type]"]', function() {
                const authType = $(this).val();
                const index = $(this).attr('name').match(/\[(\d+)\]/)[1];
                const container = $(this).closest('.endpoint-content');
                
                // Remover inputs existentes de auth
                container.find('.auth-inputs').remove();
                
                if (authType !== 'none') {
                    let authHTML = '<div class="auth-inputs" style="margin-top: 15px;">';
                    
                    if (authType === 'token') {
                        authHTML += '<table class="form-table"><tr><th><label>Bearer Token</label></th><td><input type="text" name="endpoints[' + index + '][token]" class="regular-text"></td></tr></table>';
                    } else if (authType === 'basic') {
                        authHTML += '<table class="form-table">';
                        authHTML += '<tr><th><label>Usuario</label></th><td><input type="text" name="endpoints[' + index + '][basic_user]" class="regular-text"></td></tr>';
                        authHTML += '<tr><th><label>Contraseña</label></th><td><input type="password" name="endpoints[' + index + '][basic_password]" class="regular-text"></td></tr>';
                        authHTML += '</table>';
                    } else if (authType === 'api_key') {
                        authHTML += '<table class="form-table">';
                        authHTML += '<tr><th><label>API Key</label></th><td><input type="text" name="endpoints[' + index + '][api_key]" class="regular-text"></td></tr>';
                        authHTML += '<tr><th><label>Header Name</label></th><td><input type="text" name="endpoints[' + index + '][api_key_header]" value="X-API-Key" class="regular-text"></td></tr>';
                        authHTML += '</table>';
                    }
                    
                    authHTML += '</div>';
                    $(this).closest('table').after(authHTML);
                }
            });
        });
        </script>
        
        <style>
            /* Estilos simples para acordeones */
            .endpoint-accordion {
                transition: all 0.3s ease;
            }
            .endpoint-header {
                transition: background-color 0.3s ease;
            }
            .endpoint-header:hover {
                background: #e8e8e8 !important;
            }
            .auth-inputs {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #007cba;
            }
            
            .dynamic-tags-accordion {
                border: 1px solid #ddd;
                border-radius: 5px;
                overflow: hidden;
                margin-top: 15px;
            }
            
            .dynamic-tags-content {
                background: #f8f9fa !important;
                padding: 15px !important;
                border-radius: 5px !important;
                border-left: 4px solid #007cba !important;
            }
            
            .tag-item {
                transition: all 0.2s ease;
                position: relative;
            }
            
            .tag-item:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border-color: #007cba !important;
            }
            
            .tag-item:active {
                transform: translateY(0);
            }
            
            .tags-grid {
                max-height: 400px;
                overflow-y: auto;
                border: 1px solid #e3e3e3;
                border-radius: 5px;
                padding: 10px;
                background: #fff;
            }
            
            .show-dynamic-tags {
                position: relative;
                margin-left: 10px;
            }
            
            .show-dynamic-tags:hover {
                background-color: #f0f0f1;
            }
            
            .endpoint-card {
                transition: all 0.3s ease;
                position: relative;
            }
            
            .endpoint-card:hover {
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            /* Asegurar que el acordeón sea visible */
            .dynamic-tags-accordion[style*="display: block"] {
                display: block !important;
            }
            
            .tags-list[style*="display: block"] {
                display: block !important;
            }
            
            /* Mejorar la apariencia de los códigos */
            .tag-item code {
                font-size: 11px;
                font-weight: bold;
                word-break: break-all;
            }
            
            @media (max-width: 782px) {
                .tags-grid {
                    grid-template-columns: 1fr;
                    max-height: 300px;
                }
                
                .dynamic-tags-content {
                    padding: 10px !important;
                }
                
                .show-dynamic-tags {
                    margin-left: 5px;
                    margin-top: 5px;
                }
            }
        </style>
        <?php
    }
}
