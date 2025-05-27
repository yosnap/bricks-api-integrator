<?php
/**
 * Página de gestión de API Endpoints
 * 
 * @package BricksAPIIntegrator
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página dedicada para gestión de API Endpoints
 */
if (!function_exists('render_api_endpoints_page')) {
    function render_api_endpoints_page() {
        // Procesar formulario
        if (isset($_POST['save_endpoints']) && wp_verify_nonce($_POST['_wpnonce'], 'save_endpoints')) {
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
            
            // Limpiar cache
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%' OR option_name LIKE '_transient_timeout_api_data_%'");
            
            echo '<div class="notice notice-success"><p>✅ Endpoints guardados y cache limpiado correctamente</p></div>';
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
                        <div class="endpoint-card" style="background: #fff; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
                            <div class="endpoint-header" style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer;" onclick="toggleEndpoint(<?php echo $index; ?>)">
                                <h3 style="margin: 0; display: inline-block;">
                                    <?php echo esc_html($endpoint['name'] ?: 'Endpoint ' . ($index + 1)); ?>
                                    <span class="toggle-icon" id="toggle-<?php echo $index; ?>">▼</span>
                                </h3>
                                <span style="float: right; color: #666;"><?php echo esc_html($endpoint['url']); ?></span>
                            </div>
                            
                            <div class="endpoint-content" id="content-<?php echo $index; ?>" style="padding: 15px;">
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
                                            <p class="description">Usa {parámetro} para parámetros dinámicos</p>
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
                                
                                <!-- Campos de autenticación -->
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
                                
                                <div style="margin: 20px 0;">
                                    <button type="button" class="button test-endpoint" data-index="<?php echo $index; ?>">🧪 Test Básico</button>
                                    <button type="button" class="button test-advanced" data-index="<?php echo $index; ?>">🔬 Test con Parámetros</button>
                                    <button type="button" class="button button-link-delete remove-endpoint" data-index="<?php echo $index; ?>" style="color: #a00;">🗑️ Eliminar</button>
                                    <div class="test-result" id="test-result-<?php echo $index; ?>" style="margin-top: 10px;"></div>
                                </div>
                                
                                <!-- Panel de Test Avanzado -->
                                <div class="advanced-test-panel" id="advanced-panel-<?php echo $index; ?>" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 15px;">
                                    <h4>🔬 Test con Parámetros</h4>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <label><strong>URL Final:</strong></label>
                                        <div style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-family: monospace; margin-top: 5px;">
                                            <span id="preview-url-<?php echo $index; ?>"><?php echo esc_html($endpoint['url']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <label><strong>Parámetros URL (?key=value):</strong></label>
                                        <div id="url-params-<?php echo $index; ?>" style="margin-top: 10px;">
                                            <div class="param-row" style="margin-bottom: 10px;">
                                                <input type="text" placeholder="Clave (ej: id)" class="param-key" style="width: 30%;">
                                                <input type="text" placeholder="Valor (ej: 5)" class="param-value" style="width: 30%; margin-left: 2%;">
                                                <button type="button" class="button-small add-param" data-index="<?php echo $index; ?>">+</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (strpos($endpoint['url'], '{') !== false): ?>
                                    <div style="margin-bottom: 15px;">
                                        <label><strong>Parámetros Dinámicos:</strong></label>
                                        <div id="dynamic-params-<?php echo $index; ?>" style="margin-top: 10px;">
                                            <?php
                                            preg_match_all('/\{([^}]+)\}/', $endpoint['url'], $matches);
                                            foreach ($matches[1] as $param):
                                            ?>
                                                <div style="margin-bottom: 10px;">
                                                    <label><strong><?php echo esc_html($param); ?>:</strong></label>
                                                    <input type="text" class="dynamic-param" data-param="<?php echo esc_attr($param); ?>" placeholder="Valor para <?php echo esc_html($param); ?>" style="width: 200px; margin-left: 10px;">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="margin-top: 15px;">
                                        <button type="button" class="button button-primary execute-test" data-index="<?php echo $index; ?>">🚀 Ejecutar Test</button>
                                        <button type="button" class="button close-panel" data-index="<?php echo $index; ?>">❌ Cerrar</button>
                                    </div>
                                </div>
                                
                                <!-- Mostrar datos de la API si existe -->
                                <?php
                                if (!empty($endpoint['url'])) {
                                    $api_data = null;
                                    try {
                                        if (function_exists('get_api_data')) {
                                            $api_data = get_api_data($endpoint['url'], $endpoint);
                                        }
                                    } catch (Exception $e) {
                                        // Error silencioso
                                    }
                                    
                                    if (!empty($api_data)) {
                                        echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 15px;">';
                                        echo '<h4>📊 Datos de la API</h4>';
                                        echo '<table class="widefat" style="margin-top: 10px;">';
                                        echo '<thead><tr>';
                                        echo '<th>Campo</th>';
                                        echo '<th>Valor</th>';
                                        echo '<th>Variable PHP</th>';
                                        echo '</tr></thead>';
                                        echo '<tbody>';
                                        
                                        // Mostrar datos del primer elemento si es array
                                        $sample_data = is_array($api_data) && isset($api_data[0]) ? $api_data[0] : $api_data;
                                        render_api_item_table($sample_data, '$payload[' . $index . ']');
                                        
                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '<p><small><strong>Total de elementos:</strong> ' . (is_array($api_data) ? count($api_data) : 1) . '</small></p>';
                                        echo '</div>';
                                    } else {
                                        echo '<div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 15px;">';
                                        echo '<p>⚠️ No se pudieron obtener datos de esta API. Verifica la URL y autenticación.</p>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="add-endpoint" class="button">➕ Añadir Endpoint</button>
                
                <p class="submit">
                    <input type="submit" name="save_endpoints" class="button-primary" value="💾 Guardar Todos los Endpoints">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let endpointCounter = <?php echo count($endpoints); ?>;
            
            // Test endpoint básico
            $(document).on('click', '.test-endpoint', function() {
                const index = $(this).data('index');
                const $button = $(this);
                const $result = $('#test-result-' + index);
                $button.prop('disabled', true).text('🔄 Probando...');
                $result.html('<p style="color: orange;">Probando conexión...</p>');
                
                $.post(ajaxurl, {
                    action: 'test_api_endpoint',
                    index: index,
                    nonce: '<?php echo wp_create_nonce('test_api_endpoint'); ?>'
                }, function(response) {
                    if (response.success) {
                        $result.html('<p style="color: green;">✅ OK (' + response.data.count + ' elementos)</p>');
                    } else {
                        $result.html('<p style="color: red;">❌ ' + response.data.message + '</p>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('🧪 Test Básico');
                });
            });
        });
        </script>
                        $result.html('<p style="color: red;">❌ ' + response.data.message + '</p>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('🧪 Test Básico');
                });
            });
            
            // Mostrar panel de test avanzado
            $(document).on('click', '.test-advanced', function() {
                const index = $(this).data('index');
                const panel = $('#advanced-panel-' + index);
                
                if (panel.is(':visible')) {
                    panel.slideUp();
                } else {
                    panel.slideDown();
                    updateUrlPreview(index);
                }
            });
            
            // Cerrar panel
            $(document).on('click', '.close-panel', function() {
                const index = $(this).data('index');
                $('#advanced-panel-' + index).slideUp();
            });
            
            // Añadir parámetro
            $(document).on('click', '.add-param', function() {
                const index = $(this).data('index');
                const container = $('#url-params-' + index);
                const newRow = `
                    <div class="param-row" style="margin-bottom: 10px;">
                        <input type="text" placeholder="Clave" class="param-key" style="width: 30%;">
                        <input type="text" placeholder="Valor" class="param-value" style="width: 30%; margin-left: 2%;">
                        <button type="button" class="button-small remove-param">-</button>
                    </div>
                `;
                container.append(newRow);
                updateUrlPreview(index);
            });
            
            // Eliminar parámetro
            $(document).on('click', '.remove-param', function() {
                $(this).closest('.param-row').remove();
                const index = $(this).closest('.advanced-test-panel').attr('id').split('-')[2];
                updateUrlPreview(index);
            });
            
            // Actualizar preview en tiempo real
            $(document).on('input', '.param-key, .param-value, .dynamic-param', function() {
                const index = $(this).closest('.advanced-test-panel').attr('id').split('-')[2];
                updateUrlPreview(index);
            });
            
            // Ejecutar test avanzado
            $(document).on('click', '.execute-test', function() {
                const index = $(this).data('index');
                executeAdvancedTest(index);
            });
            
            // Añadir nuevo endpoint
            $('#add-endpoint').click(function() {
                const container = $('#endpoints-container');
                const html = `
                    <div class="endpoint-card" style="background: #fff; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
                        <div class="endpoint-header" style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer;" onclick="toggleEndpoint(${endpointCounter})">
                            <h3 style="margin: 0; display: inline-block;">
                                Endpoint ${endpointCounter + 1}
                                <span class="toggle-icon" id="toggle-${endpointCounter}">▼</span>
                            </h3>
                        </div>
                        
                        <div class="endpoint-content" id="content-${endpointCounter}" style="padding: 15px;">
                            <table class="form-table">
                                <tr>
                                    <th><label>Nombre del Endpoint</label></th>
                                    <td><input type="text" name="endpoints[${endpointCounter}][name]" class="regular-text" placeholder="Nombre del Endpoint" required></td>
                                </tr>
                                <tr>
                                    <th><label>URL del Endpoint</label></th>
                                    <td>
                                        <input type="url" name="endpoints[${endpointCounter}][url]" class="regular-text" placeholder="https://api.ejemplo.com/datos" required>
                                        <p class="description">Usa {parámetro} para parámetros dinámicos</p>
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
                            
                            <div style="margin: 20px 0;">
                                <button type="button" class="button test-endpoint" data-index="${endpointCounter}">🧪 Test Básico</button>
                                <button type="button" class="button test-advanced" data-index="${endpointCounter}">🔬 Test con Parámetros</button>
                                <button type="button" class="button remove-endpoint" onclick="removeEndpoint(${endpointCounter})">🗑️ Eliminar</button>
                                <div class="test-result" id="test-result-${endpointCounter}" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.append(html);
                endpointCounter++;
            });
        });
        
        // Funciones de utilidad
        function updateUrlPreview(index) {
            const baseUrl = $(`input[name="endpoints[${index}][url]"]`).val();
            let finalUrl = baseUrl;
            
            // Reemplazar parámetros dinámicos
            $('.dynamic-param').each(function() {
                const param = $(this).data('param');
                const value = $(this).val();
                if (value) {
                    finalUrl = finalUrl.replace(`{${param}}`, encodeURIComponent(value));
                }
            });
            
            // Añadir parámetros de URL
            const params = [];
            $(`#url-params-${index} .param-row`).each(function() {
                const key = $(this).find('.param-key').val();
                const value = $(this).find('.param-value').val();
                if (key && value) {
                    params.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
                }
            });
            
            if (params.length > 0) {
                finalUrl += (finalUrl.includes('?') ? '&' : '?') + params.join('&');
            }
            
            $('#preview-url-' + index).text(finalUrl);
        }
        
        function executeAdvancedTest(index) {
            const $result = $('#test-result-' + index);
            const $button = $(`.execute-test[data-index="${index}"]`);
            const finalUrl = $('#preview-url-' + index).text();
            
            $button.prop('disabled', true).text('🔄 Ejecutando...');
            $result.html('<p style="color: orange;">🔬 Ejecutando test avanzado...</p>');
            
            $.post(ajaxurl, {
                action: 'test_advanced_api_endpoint',
                index: index,
                url: finalUrl,
                nonce: '<?php echo wp_create_nonce('test_advanced_api_endpoint'); ?>'
            }, function(response) {
                if (response.success) {
                    let html = `<div style="color: green;">
                        <p><strong>✅ Test Avanzado Exitoso</strong></p>
                        <p><strong>URL:</strong> <code>${finalUrl}</code></p>
                        <p><strong>Elementos:</strong> ${response.data.count}</p>
                    `;
                    
                    if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                        html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                    }
                    
                    html += '</div>';
                    $result.html(html);
                } else {
                    $result.html(`<div style="color: red;">
                        <p><strong>❌ Error en Test Avanzado</strong></p>
                        <p><strong>URL:</strong> <code>${finalUrl}</code></p>
                        <p><strong>Error:</strong> ${response.data.message}</p>
                    </div>`);
                }
            }).always(function() {
                $button.prop('disabled', false).text('🚀 Ejecutar Test');
            });
        }
        </script>
        
        <style>
        .endpoint-card {
            transition: all 0.3s ease;
        }
        .endpoint-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .toggle-icon {
            float: right;
            transition: transform 0.3s ease;
        }
        .advanced-test-panel {
            border-left: 4px solid #0073aa;
        }
        </style>
        <?php
    }
}

/**
 * Función para renderizar los datos de un objeto o array en tabla
 */
if (!function_exists('render_api_item_table')) {
    function render_api_item_table($item, $parent_key = '$payload') {
        if (!is_array($item) && !is_object($item)) {
            return;
        }
        
        foreach ((array)$item as $key => $value) {
            $variable_key = $parent_key . "['$key']";
            $php_variable = $variable_key;

            if (is_array($value)) {
                // Si el array es simple (strings/números), concatenar valores
                if (array_is_list($value) && all_items_are_strings_or_numbers($value)) {
                    $concatenated_values = implode(', ', array_slice($value, 0, 5)); // Limitar a 5 elementos
                    echo '<tr>';
                    echo '<td><code>' . esc_html($key) . '</code></td>';
                    echo '<td>' . esc_html($concatenated_values) . '</td>';
                    echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                    echo '</tr>';
                } else {
                    // Si es array complejo, mostrar como JSON
                    echo '<tr>';
                    echo '<td><code>' . esc_html($key) . '</code></td>';
                    echo '<td><pre style="max-height: 100px; overflow: auto; font-size: 11px;">' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre></td>';
                    echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                    echo '</tr>';
                }
            } elseif (is_object($value)) {
                // Objeto como JSON
                echo '<tr>';
                echo '<td><code>' . esc_html($key) . '</code></td>';
                echo '<td><pre style="max-height: 100px; overflow: auto; font-size: 11px;">' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre></td>';
                echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                echo '</tr>';
            } else {
                // Valores simples
                echo '<tr>';
                echo '<td><code>' . esc_html($key) . '</code></td>';
                echo '<td>' . esc_html(is_bool($value) ? ($value ? 'true' : 'false') : $value) . '</td>';
                echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                echo '</tr>';
            }
        }
    }
}

/**
 * Helper function para verificar si todos los elementos del array son strings o números
 */
if (!function_exists('all_items_are_strings_or_numbers')) {
    function all_items_are_strings_or_numbers($array) {
        foreach ($array as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                return false;
            }
        }
        return true;
    }
}
