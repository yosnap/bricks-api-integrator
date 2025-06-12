 12px; border-radius: 6px; margin-top: 15px;">🔄 Forzando actualización de datos desde la API...</div>');
                
                // AJAX call para actualizar endpoint
                $.post(ajaxurl, {
                    action: 'refresh_endpoint_data',
                    index: index,
                    nonce: '<?php echo wp_create_nonce('refresh_endpoint_data'); ?>'
                }, function(response) {
                    if (response.success) {
                        let html = `<div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #28a745;">
                            <h4 style="margin: 0 0 10px 0;">✅ Datos actualizados correctamente</h4>
                            <p style="margin: 5px 0;"><strong>Elementos encontrados:</strong> ${response.data.count}</p>`;
                        
                        if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                            html += `<p style="margin: 5px 0;"><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                        }
                        
                        if (response.data.cache_cleared) {
                            html += `<p style="margin: 5px 0;"><strong>🗑️ Caché limpiado:</strong> Los datos se han actualizado desde la API</p>`;
                        }
                        
                        html += '</div>';
                        $result.html(html);
                    } else {
                        $result.html(`<div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #dc3545;">
                            <h4 style="margin: 0 0 10px 0;">❌ Error al actualizar datos</h4>
                            <p style="margin: 5px 0;"><strong>Error:</strong> ${response.data ? response.data.message : 'Error desconocido'}</p>
                        </div>`);
                    }
                }).fail(function() {
                    $result.html('<div style="color: #721c24; background: #f8d7da; padding: 12px; border-radius: 6px; margin-top: 15px;">❌ Error de conexión con el servidor</div>');
                }).always(function() {
                    $button.prop('disabled', false).text('🔄 Actualizar Datos');
                });
            });
            
            // Mostrar/ocultar dynamic tags
            $(document).on('click', '.show-dynamic-tags', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const index = $(this).data('index');
                const $accordion = $('#dynamic-tags-' + index);
                const $button = $(this);
                
                if ($accordion.is(':visible')) {
                    $accordion.slideUp(300);
                    $button.text('🏷️ Ver Dynamic Tags');
                } else {
                    $accordion.slideDown(300);
                    $button.text('🏷️ Ocultar Dynamic Tags');
                    loadDynamicTags(index, $button);
                }
            });
            
            // Función para cargar dynamic tags via AJAX
            function loadDynamicTags(index, $button) {
                const $accordion = $('#dynamic-tags-' + index);
                const $loading = $accordion.find('.tags-loading');
                const $tagsList = $accordion.find('.tags-list');
                const $endpointAccordion = $button.closest('.endpoint-accordion');
                
                $loading.show();
                $tagsList.hide();
                
                const name = $endpointAccordion.find('input[name*="[name]"]').val();
                const url = $endpointAccordion.find('input[name*="[url]"]').val();
                
                if (!name || !url) {
                    $loading.html('<div style="color: #dc3545; padding: 20px; text-align: center;">❌ Configura el nombre y URL del endpoint primero</div>');
                    return;
                }
                
                // AJAX call para obtener dynamic tags
                $.post(ajaxurl, {
                    action: 'get_dynamic_tags_for_endpoint',
                    index: index,
                    nonce: window.currentNonce
                }, function(response) {
                    if (response.success && response.data.tags && response.data.tags.length > 0) {
                        let html = '<div style="padding: 20px;">';
                        html += '<h4 style="color: #007cba; margin-bottom: 20px;">✅ Dynamic Tags Generados (' + response.data.tags.length + ')</h4>';
                        
                        if (response.data.endpoint_name) {
                            html += '<div style="background: #e7f3ff; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #0969da;">';
                            html += '<strong>📊 Endpoint:</strong> ' + response.data.endpoint_name;
                            if (response.data.data_count) {
                                html += ' | <strong>Elementos:</strong> ' + response.data.data_count;
                            }
                            if (response.data.has_dynamic_params) {
                                html += ' | <strong>✨ Parámetros dinámicos:</strong> Sí';
                            }
                            html += '</div>';
                        }
                        
                        html += '<div style="display: grid; gap: 8px; font-family: monospace; font-size: 13px;">';
                        
                        response.data.tags.forEach(function(tag) {
                            html += '<div class="copy-tag" style="background: #f8f9fa; padding: 12px 15px; border-radius: 6px; border-left: 3px solid #007cba; cursor: pointer; transition: all 0.2s ease; border: 1px solid #e9ecef;" onclick="copyToClipboard(\'' + tag + '\', this)" title="Clic para copiar al portapapeles">';
                            html += '<code style="color: #e83e8c; font-weight: bold;">' + tag + '</code>';
                            html += '</div>';
                        });
                        
                        html += '</div>';
                        html += '<div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 6px; border-left: 4px solid #0969da;">';
                        html += '<p style="margin: 0; font-size: 14px; color: #0c5460;"><strong>💡 Cómo usar:</strong> Haz clic en cualquier tag para copiarlo al portapapeles. Después pégalo en tus elementos de Bricks Builder donde necesites mostrar los datos de la API.</p>';
                        html += '</div>';
                        html += '</div>';
                        
                        $loading.hide();
                        $tagsList.html(html);
                        $tagsList.show();
                        
                        // Agregar efecto hover a los tags
                        $tagsList.find('.copy-tag').hover(
                            function() {
                                $(this).css({
                                    'background': '#e9ecef',
                                    'border-color': '#007cba',
                                    'transform': 'translateY(-2px)',
                                    'box-shadow': '0 4px 8px rgba(0,0,0,0.1)'
                                });
                            },
                            function() {
                                $(this).css({
                                    'background': '#f8f9fa',
                                    'border-color': '#e9ecef',
                                    'transform': 'translateY(0)',
                                    'box-shadow': 'none'
                                });
                            }
                        );
                        
                    } else if (response.success) {
                        $loading.html('<div style="color: #856404; background: #fff3cd; padding: 20px; border-radius: 6px; text-align: center;">⚠️ No se generaron dynamic tags. Verifica que el endpoint devuelve datos válidos y que la URL es correcta.</div>');
                    } else {
                        let errorMessage = response.data ? response.data.message : 'Error desconocido';
                        $loading.html('<div style="color: #721c24; background: #f8d7da; padding: 20px; border-radius: 6px; text-align: center;"><strong>❌ Error:</strong> ' + errorMessage + '</div>');
                    }
                }).fail(function(xhr, status, error) {
                    $loading.html('<div style="color: #721c24; background: #f8d7da; padding: 20px; border-radius: 6px; text-align: center;"><strong>❌ Error de conexión:</strong> ' + status + '</div>');
                });
            }
            
            // Función para copiar al portapapeles
            window.copyToClipboard = function(text, element) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyFeedback(element, '✅ Copiado');
                    }).catch(function() {
                        fallbackCopyTextToClipboard(text, element);
                    });
                } else {
                    fallbackCopyTextToClipboard(text, element);
                }
            };
            
            function fallbackCopyTextToClipboard(text, element) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    showCopyFeedback(element, '✅ Copiado');
                } catch (err) {
                    showCopyFeedback(element, '❌ Error al copiar');
                }
                
                document.body.removeChild(textArea);
            }
            
            function showCopyFeedback(element, message) {
                const $element = $(element);
                const originalBg = $element.css('background-color');
                const originalBorder = $element.css('border-left-color');
                const originalText = $element.html();
                
                $element.css({
                    'background-color': '#d4edda',
                    'border-left-color': '#28a745',
                    'transform': 'scale(1.02)'
                });
                $element.html('<span style="color: #155724; font-weight: bold;">' + message + '</span>');
                
                setTimeout(function() {
                    $element.css({
                        'background-color': originalBg,
                        'border-left-color': originalBorder,
                        'transform': 'scale(1)'
                    });
                    $element.html(originalText);
                }, 2000);
            }
            
            // Validación del formulario antes de enviar
            $('form').on('submit', function(e) {
                let hasErrors = false;
                let errorMessages = [];
                
                $('.endpoint-accordion').each(function(index) {
                    const $accordion = $(this);
                    const name = $accordion.find('input[name*="[name]"]').val().trim();
                    const url = $accordion.find('input[name*="[url]"]').val().trim();
                    
                    if (!name) {
                        hasErrors = true;
                        errorMessages.push(`Endpoint ${index + 1}: Falta el nombre`);
                    }
                    
                    if (!url) {
                        hasErrors = true;
                        errorMessages.push(`Endpoint ${index + 1}: Falta la URL`);
                    } else if (!isValidUrl(url)) {
                        hasErrors = true;
                        errorMessages.push(`Endpoint ${index + 1}: URL no válida`);
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                    
                    // Mostrar errores en un modal más elegante
                    const errorHtml = `
                        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 500px; width: 90%;">
                                <h3 style="color: #dc3545; margin-top: 0;">⚠️ Errores de validación</h3>
                                <p>Por favor corrige los siguientes errores:</p>
                                <ul style="color: #721c24; margin: 20px 0;">
                                    ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                                </ul>
                                <button type="button" onclick="$(this).closest('div').parent().remove()" class="button button-primary" style="width: 100%;">Entendido</button>
                            </div>
                        </div>
                    `;
                    
                    $('body').append(errorHtml);
                    return false;
                }
                
                // Mostrar indicador de guardado
                const $submitBtn = $('input[type="submit"]');
                $submitBtn.prop('disabled', true).val('💾 Guardando...');
                
                // Mostrar spinner
                const spinnerHtml = '<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9998; display: flex; align-items: center; justify-content: center;"><div style="text-align: center;"><div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div><p style="color: #007cba; font-weight: bold;">Guardando endpoints...</p></div></div>';
                $('body').append(spinnerHtml);
                
                // Agregar CSS de animación si no existe
                if (!$('#spinner-css').length) {
                    $('head').append('<style id="spinner-css">@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
                }
            });
            
            // Función para validar URL
            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }
            
            // Inicializar acordeones al cargar la página
            initAccordions();
            
            // Mensaje de bienvenida si no hay endpoints
            if ($('.endpoint-accordion').length === 0) {
                $('#endpoints-container').html(`
                    <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🔗</div>
                        <h3 style="margin: 0 0 10px 0;">¡Bienvenido a Bricks API Integrator!</h3>
                        <p style="margin: 0 0 30px 0; font-size: 16px;">Comienza añadiendo tu primer endpoint para integrar APIs externas con Bricks Builder.</p>
                        <button type="button" onclick="$('#add-endpoint').click()" class="button button-primary button-large" style="margin-right: 10px;">➕ Añadir Primer Endpoint</button>
                        <button type="button" onclick="$('#add-related-endpoint').click()" class="button button-large">🔗 Endpoint Relacionado</button>
                    </div>
                `);
            }
        });
        
        // Función para toggle de acordeones (compatibilidad legacy)
        function toggleEndpoint(index) {
            const $accordion = $('.endpoint-accordion').eq(index);
            const $header = $accordion.find('.endpoint-header');
            $header.trigger('click');
        }
        </script>
        <?php
    }
}

/**
 * Función helper para renderizar un acordeón de endpoint
 */
function render_endpoint_accordion($index, $endpoint) {
    $isRelated = !empty($endpoint['related']) || 
                 (isset($endpoint['dynamic_params']) && 
                  count($endpoint['dynamic_params']) > 0 && 
                  $endpoint['dynamic_params'][0]['source'] === 'post');
    
    $relatedClass = $isRelated ? ' related' : '';
    $titlePrefix = $isRelated ? '🔗 ' : '';
    
    ob_start();
    ?>
    <div class="endpoint-accordion<?php echo $relatedClass; ?>">
        <div class="endpoint-header">
            <h3><?php echo $titlePrefix; ?>Endpoint <?php echo ($index + 1); ?> - <?php echo esc_html($endpoint['name'] ?: 'Sin nombre'); ?></h3>
            <span class="endpoint-toggle collapsed">🔽</span>
        </div>
        
        <div class="endpoint-content">
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
                        <?php if ($isRelated): ?>
                        <p class="description" style="margin-top: 8px;">
                            <strong>Placeholders disponibles:</strong> 
                            <code>{post_id}</code> (ID del post), 
                            <code>{post_slug}</code> (slug del post), 
                            <code>{user_id}</code> (ID del usuario)
                        </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label>Autenticación</label></th>
                    <td>
                        <select name="endpoints[<?php echo $index; ?>][auth_type]" class="auth-type-select">
                            <option value="none" <?php selected($endpoint['auth_type'], 'none'); ?>>Sin Autenticación</option>
                            <option value="token" <?php selected($endpoint['auth_type'], 'token'); ?>>Bearer Token</option>
                            <option value="basic" <?php selected($endpoint['auth_type'], 'basic'); ?>>Basic Auth</option>
                            <option value="api_key" <?php selected($endpoint['auth_type'], 'api_key'); ?>>API Key</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <!-- Campos de autenticación -->
            <div class="auth-fields" style="<?php echo ($endpoint['auth_type'] === 'none' || empty($endpoint['auth_type'])) ? 'display: none;' : ''; ?>">
                <?php if ($endpoint['auth_type'] === 'token'): ?>
                    <table class="form-table">
                        <tr>
                            <th><label>Bearer Token</label></th>
                            <td>
                                <input type="text" name="endpoints[<?php echo $index; ?>][token]" 
                                       value="<?php echo esc_attr($endpoint['token']); ?>" class="regular-text">
                                <p class="description">Token de autenticación Bearer para la API</p>
                            </td>
                        </tr>
                    </table>
                <?php elseif ($endpoint['auth_type'] === 'basic'): ?>
                    <table class="form-table">
                        <tr>
                            <th><label>Usuario</label></th>
                            <td><input type="text" name="endpoints[<?php echo $index; ?>][basic_user]" 
                                       value="<?php echo esc_attr($endpoint['basic_user']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Contraseña</label></th>
                            <td><input type="password" name="endpoints[<?php echo $index; ?>][basic_password]" 
                                       value="<?php echo esc_attr($endpoint['basic_password']); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                <?php elseif ($endpoint['auth_type'] === 'api_key'): ?>
                    <table class="form-table">
                        <tr>
                            <th><label>API Key</label></th>
                            <td><input type="text" name="endpoints[<?php echo $index; ?>][api_key]" 
                                       value="<?php echo esc_attr($endpoint['api_key']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Header Name</label></th>
                            <td>
                                <input type="text" name="endpoints[<?php echo $index; ?>][api_key_header]" 
                                       value="<?php echo esc_attr($endpoint['api_key_header'] ?: 'X-API-Key'); ?>" class="regular-text">
                                <p class="description">Nombre del header HTTP para enviar la API Key</p>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Parámetros Dinámicos -->
            <table class="form-table">
                <tr>
                    <th><label>Parámetros Dinámicos</label></th>
                    <td>
                        <div id="dynamic-params-container-<?php echo $index; ?>">
                            <?php if (!empty($endpoint['dynamic_params'])): ?>
                                <?php foreach ($endpoint['dynamic_params'] as $param_index => $param): ?>
                                    <div class="dynamic-param-row">
                                        <input type="text" name="endpoints[<?php echo $index; ?>][param_names][]" 
                                               placeholder="Nombre del parámetro (ej: id, slug)" 
                                               class="regular-text" 
                                               value="<?php echo esc_attr($param['name']); ?>">
                                        
                                        <select name="endpoints[<?php echo $index; ?>][param_sources][]">
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
                                               value="<?php echo esc_attr($param['default']); ?>">
                                        
                                        <button type="button" class="button remove-param" style="color: #dc3545;">Eliminar</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #6c757d; font-style: italic; margin: 0;">No hay parámetros dinámicos configurados</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button add-param" data-endpoint="<?php echo $index; ?>" style="margin-top: 10px;">
                            ➕ Añadir Parámetro
                        </button>
                        <p class="description" style="margin-top: 15px;">
                            <?php if ($isRelated): ?>
                                <strong>🔗 Endpoint Relacionado:</strong> Este endpoint usará automáticamente el ID/slug del post actual de WordPress.
                                <br><strong>Ejemplos de uso:</strong> Comentarios de un producto, reviews de un servicio, items relacionados, etc.
                            <?php else: ?>
                                <strong>Para páginas de detalle:</strong> Configura parámetros como "id" o "slug" que se tomarán de la URL actual o del post.
                                <br><strong>Ejemplo:</strong> Si tu API necesita <code>?id=123</code>, agrega parámetro "id" con fuente "Parámetro URL".
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <!-- Acciones del endpoint -->
            <div class="endpoint-actions">
                <button type="button" class="button test-endpoint" data-index="<?php echo $index; ?>">
                    🧪 Test API
                </button>
                <button type="button" class="button show-dynamic-tags" data-index="<?php echo $index; ?>">
                    🏷️ Ver Dynamic Tags
                </button>
                <button type="button" class="button refresh-endpoint-data" data-index="<?php echo $index; ?>" 
                        style="background: #28a745; color: white; border-color: #28a745;">
                    🔄 Actualizar Datos
                </button>
                <button type="button" class="button button-link-delete remove-endpoint" 
                        data-index="<?php echo $index; ?>" style="color: #dc3545;">
                    🗑️ Eliminar
                </button>
            </div>
            
            <!-- Accordion para Dynamic Tags -->
            <div class="dynamic-tags-accordion" id="dynamic-tags-<?php echo $index; ?>" style="display: none;">
                <div class="dynamic-tags-content">
                    <h4 style="margin: 0 0 15px 0; color: <?php echo $isRelated ? '#6c757d' : '#007cba'; ?>;">
                        <?php echo $isRelated ? '🔗 Dynamic Tags Relacionados' : '🏷️ Dynamic Tags Disponibles'; ?>
                    </h4>
                    <div class="tags-loading" style="text-align: center; padding: 30px;">
                        <span style="color: #6c757d;">⏳ Generando dynamic tags...</span>
                    </div>
                    <div class="tags-list" style="display: none;">
                        <!-- Se llenará con AJAX -->
                    </div>
                    <div class="tags-help" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 6px;">
                        <p style="margin: 0; font-size: 13px; color: #0969da;">
                            💡 <strong>Cómo usar:</strong> Haz clic en cualquier tag para copiarlo al portapapeles. 
                            <?php echo $isRelated ? 
                                'Estos tags se basan en el contenido relacionado al post actual.' : 
                                'Los tags con <code>_count</code>, <code>_first</code>, <code>_join</code> son especiales para arrays.' 
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="test-result" id="test-result-<?php echo $index; ?>"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
auth_type'])) ? 'display: none;' : ''; ?>">
                <?php if ($endpoint['auth_type'] === 'token'): ?>
                    <table class="form-table">
                        <tr>
                            <th><label>Bearer Token</label></th>
                            <td>
                                <input type="text" name="endpoints[<?php echo $index; ?>][token]" 
                                       value="<?php echo esc_attr($endpoint['token']); ?>" class="regular-text">
                                <p class="description">Token de autenticación Bearer para la API</p>
                            </td>
                        </tr>
                    </table>
                <?php elseif ($endpoint['auth_type'] === 'basic'): ?>
                    <table class="form-table">
                        <tr>
                            <th><label>Usuario</label></th>
                            <td><input type="text" name="endpoints[<?php echo $index; ?>][basic_user]" 
                                       value="<?php echo esc_attr($endpoint['basic_user']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Contraseña</label></th>
                            <td><input type="password" name="endpoints[<?php echo $index; ?>][basic_password]" 
                                       value="<?php echo esc_attr($endpoint['basic_password']); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                <?php elseif ($endpoint['auth_type'] === 'api_key'): ?>
                    <table class="form-table">
                        <tr>
                            <th><label>API Key</label></th>
                            <td><input type="text" name="endpoints[<?php echo $index; ?>][api_key]" 
                                       value="<?php echo esc_attr($endpoint['api_key']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Header Name</label></th>
                            <td>
                                <input type="text" name="endpoints[<?php echo $index; ?>][api_key_header]" 
                                       value="<?php echo esc_attr($endpoint['api_key_header'] ?: 'X-API-Key'); ?>" class="regular-text">
                                <p class="description">Nombre del header HTTP para enviar la API Key</p>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Parámetros Dinámicos -->
            <table class="form-table">
                <tr>
                    <th><label>Parámetros Dinámicos</label></th>
                    <td>
                        <div id="dynamic-params-container-<?php echo $index; ?>">
                            <?php if (!empty($endpoint['dynamic_params'])): ?>
                                <?php foreach ($endpoint['dynamic_params'] as $param_index => $param): ?>
                                    <div class="dynamic-param-row">
                                        <input type="text" name="endpoints[<?php echo $index; ?>][param_names][]" 
                                               placeholder="Nombre del parámetro (ej: id, slug)" 
                                               class="regular-text" 
                                               value="<?php echo esc_attr($param['name']); ?>">
                                        
                                        <select name="endpoints[<?php echo $index; ?>][param_sources][]">
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
                                               value="<?php echo esc_attr($param['default']); ?>">
                                        
                                        <button type="button" class="button remove-param" style="color: #dc3545;">Eliminar</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #6c757d; font-style: italic; margin: 0;">No hay parámetros dinámicos configurados</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button add-param" data-endpoint="<?php echo $index; ?>" style="margin-top: 10px;">
                            ➕ Añadir Parámetro
                        </button>
                        <p class="description" style="margin-top: 15px;">
                            <?php if ($isRelated): ?>
                                <strong>🔗 Endpoint Relacionado:</strong> Este endpoint usará automáticamente el ID/slug del post actual de WordPress.
                                <br><strong>Ejemplos de uso:</strong> Comentarios de un producto, reviews de un servicio, items relacionados, etc.
                            <?php else: ?>
                                <strong>Para páginas de detalle:</strong> Configura parámetros como "id" o "slug" que se tomarán de la URL actual o del post.
                                <br><strong>Ejemplo:</strong> Si tu API necesita <code>?id=123</code>, agrega parámetro "id" con fuente "Parámetro URL".
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <!-- Acciones del endpoint -->
            <div class="endpoint-actions">
                <button type="button" class="button test-endpoint" data-index="<?php echo $index; ?>">
                    🧪 Test API
                </button>
                <button type="button" class="button show-dynamic-tags" data-index="<?php echo $index; ?>">
                    🏷️ Ver Dynamic Tags
                </button>
                <button type="button" class="button refresh-endpoint-data" data-index="<?php echo $index; ?>" 
                        style="background: #28a745; color: white; border-color: #28a745;">
                    🔄 Actualizar Datos
                </button>
                <button type="button" class="button button-link-delete remove-endpoint" 
                        data-index="<?php echo $index; ?>" style="color: #dc3545;">
                    🗑️ Eliminar
                </button>
            </div>
            
            <!-- Accordion para Dynamic Tags -->
            <div class="dynamic-tags-accordion" id="dynamic-tags-<?php echo $index; ?>" style="display: none;">
                <div class="dynamic-tags-content">
                    <h4 style="margin: 0 0 15px 0; color: <?php echo $isRelated ? '#6c757d' : '#007cba'; ?>;">
                        <?php echo $isRelated ? '🔗 Dynamic Tags Relacionados' : '🏷️ Dynamic Tags Disponibles'; ?>
                    </h4>
                    <div class="tags-loading" style="text-align: center; padding: 30px;">
                        <span style="color: #6c757d;">⏳ Generando dynamic tags...</span>
                    </div>
                    <div class="tags-list" style="display: none;">
                        <!-- Se llenará con AJAX -->
                    </div>
                    <div class="tags-help" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 6px;">
                        <p style="margin: 0; font-size: 13px; color: #0969da;">
                            💡 <strong>Cómo usar:</strong> Haz clic en cualquier tag para copiarlo al portapapeles. 
                            <?php echo $isRelated ? 
                                'Estos tags se basan en el contenido relacionado al post actual.' : 
                                'Los tags con <code>_count</code>, <code>_first</code>, <code>_join</code> son especiales para arrays.' 
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="test-result" id="test-result-<?php echo $index; ?>"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

<script>
// Continuación del JavaScript - Funciones AJAX y manejo de eventos
jQuery(document).ready(function($) {
    
    // Manejo dinámico de campos de autenticación
    $(document).on('change', '.auth-type-select', function() {
        const authType = $(this).val();
        const $accordion = $(this).closest('.endpoint-accordion');
        const $authFields = $accordion.find('.auth-fields');
        const accordionIndex = $('.endpoint-accordion').index($accordion);
        
        let authHtml = '';
        
        switch(authType) {
            case 'token':
                authHtml = `
                    <table class="form-table">
                        <tr>
                            <th><label>Bearer Token</label></th>
                            <td>
                                <input type="text" name="endpoints[${accordionIndex}][token]" 
                                       class="regular-text" placeholder="tu_bearer_token_aqui">
                                <p class="description">Token de autenticación Bearer para la API</p>
                            </td>
                        </tr>
                    </table>
                `;
                break;
            case 'basic':
                authHtml = `
                    <table class="form-table">
                        <tr>
                            <th><label>Usuario</label></th>
                            <td>
                                <input type="text" name="endpoints[${accordionIndex}][basic_user]" 
                                       class="regular-text" placeholder="usuario">
                            </td>
                        </tr>
                        <tr>
                            <th><label>Contraseña</label></th>
                            <td>
                                <input type="password" name="endpoints[${accordionIndex}][basic_password]" 
                                       class="regular-text" placeholder="contraseña">
                            </td>
                        </tr>
                    </table>
                `;
                break;
            case 'api_key':
                authHtml = `
                    <table class="form-table">
                        <tr>
                            <th><label>API Key</label></th>
                            <td>
                                <input type="text" name="endpoints[${accordionIndex}][api_key]" 
                                       class="regular-text" placeholder="tu_api_key_aqui">
                            </td>
                        </tr>
                        <tr>
                            <th><label>Header Name</label></th>
                            <td>
                                <input type="text" name="endpoints[${accordionIndex}][api_key_header]" 
                                       value="X-API-Key" class="regular-text">
                                <p class="description">Nombre del header HTTP para enviar la API Key</p>
                            </td>
                        </tr>
                    </table>
                `;
                break;
        }
        
        if (authHtml) {
            $authFields.html(authHtml).slideDown(300);
        } else {
            $authFields.slideUp(300, function() {
                $(this).empty();
            });
        }
    });
    
    // Eliminar endpoint
    $(document).on('click', '.remove-endpoint', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $accordion = $(this).closest('.endpoint-accordion');
        const endpointName = $accordion.find('input[name*="[name]"]').val() || 'Sin nombre';
        
        if (confirm(`¿Estás seguro de que quieres eliminar el endpoint "${endpointName}"?`)) {
            $accordion.slideUp(400, function() {
                $(this).remove();
                reindexEndpoints();
                
                // Si no quedan endpoints, mostrar mensaje de bienvenida
                if ($('.endpoint-accordion').length === 0) {
                    $('#endpoints-container').html(`
                        <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                            <div style="font-size: 48px; margin-bottom: 20px;">🔗</div>
                            <h3 style="margin: 0 0 10px 0;">No hay endpoints configurados</h3>
                            <p style="margin: 0 0 30px 0; font-size: 16px;">Añade tu primer endpoint para comenzar.</p>
                            <button type="button" onclick="$('#add-endpoint').click()" class="button button-primary button-large" style="margin-right: 10px;">➕ Añadir Endpoint</button>
                            <button type="button" onclick="$('#add-related-endpoint').click()" class="button button-large">🔗 Endpoint Relacionado</button>
                        </div>
                    `);
                } else {
                    // Abrir el primer acordeón si quedan endpoints
                    setTimeout(() => {
                        if ($('.endpoint-content.active').length === 0) {
                            $('.endpoint-accordion:first .endpoint-content').addClass('active').slideDown(300);
                            $('.endpoint-accordion:first .endpoint-toggle').removeClass('collapsed');
                        }
                    }, 100);
                }
            });
        }
    });
    
    // Reindexar endpoints después de eliminar
    function reindexEndpoints() {
        $('.endpoint-accordion').each(function(newIndex) {
            const $accordion = $(this);
            
            // Actualizar título
            const isRelated = $accordion.hasClass('related');
            const newTitle = isRelated ? 
                `🔗 Endpoint ${newIndex + 1} - Relacionado` : 
                `Endpoint ${newIndex + 1}`;
            
            const currentName = $accordion.find('input[name*="[name]"]').val() || 'Sin nombre';
            $accordion.find('.endpoint-header h3').text(`${isRelated ? '🔗 ' : ''}Endpoint ${newIndex + 1} - ${currentName}`);
            
            // Actualizar nombres de inputs y selects
            $accordion.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name && name.includes('endpoints[')) {
                    const newName = name.replace(/endpoints\[\d+\]/, `endpoints[${newIndex}]`);
                    $(this).attr('name', newName);
                }
            });
            
            // Actualizar data-index de botones
            $accordion.find('[data-index]').attr('data-index', newIndex);
            $accordion.find('[data-endpoint]').attr('data-endpoint', newIndex);
            
            // Actualizar IDs
            $accordion.find('[id*="dynamic-params-container-"]').attr('id', `dynamic-params-container-${newIndex}`);
            $accordion.find('[id*="dynamic-tags-"]').attr('id', `dynamic-tags-${newIndex}`);
            $accordion.find('[id*="test-result-"]').attr('id', `test-result-${newIndex}`);
        });
        
        // Actualizar contador global
        window.endpointCounter = $('.endpoint-accordion').length;
    }
    
    // Manejar parámetros dinámicos
    $(document).on('click', '.add-param', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const endpointIndex = $(this).data('endpoint');
        const container = $(`#dynamic-params-container-${endpointIndex}`);
        
        // Remover mensaje de "no hay parámetros" si existe
        container.find('p[style*="italic"]').remove();
        
        const paramHtml = `
            <div class="dynamic-param-row">
                <input type="text" name="endpoints[${endpointIndex}][param_names][]" 
                       placeholder="Nombre del parámetro (ej: id, slug)" 
                       class="regular-text">
                
                <select name="endpoints[${endpointIndex}][param_sources][]">
                    <option value="url">Parámetro URL (?id=123)</option>
                    <option value="post">ID del Post Actual</option>
                    <option value="post_slug">Slug del Post Actual</option>
                    <option value="user">ID del Usuario Actual</option>
                    <option value="meta">Meta Field del Post</option>
                    <option value="static">Valor Estático</option>
                </select>
                
                <input type="text" name="endpoints[${endpointIndex}][param_defaults][]" 
                       placeholder="Valor por defecto" 
                       class="regular-text">
                
                <button type="button" class="button remove-param" style="color: #dc3545;">
                    Eliminar
                </button>
            </div>
        `;
        
        container.append(paramHtml);
    });
    
    // Eliminar parámetro dinámico
    $(document).on('click', '.remove-param', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $row = $(this).closest('.dynamic-param-row');
        const $container = $row.closest('[id*="dynamic-params-container"]');
        
        $row.slideUp(300, function() {
            $(this).remove();
            
            // Si no quedan parámetros, mostrar mensaje
            if ($container.find('.dynamic-param-row').length === 0) {
                $container.html('<p style="color: #6c757d; font-style: italic; margin: 0;">No hay parámetros dinámicos configurados</p>');
            }
        });
    });
    
    // FUNCIONES AJAX - Test de API endpoint
    $(document).on('click', '.test-endpoint', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const index = $(this).data('index');
        const $button = $(this);
        const $result = $('#test-result-' + index);
        const $accordion = $(this).closest('.endpoint-accordion');
        
        // Obtener datos del endpoint
        const name = $accordion.find('input[name*="[name]"]').val();
        const url = $accordion.find('input[name*="[url]"]').val();
        
        if (!url) {
            $result.html('<div style="color: #dc3545; background: #f8d7da; padding: 12px; border-radius: 6px; margin-top: 15px;">❌ URL requerida para realizar el test</div>');
            return;
        }
        
        $button.prop('disabled', true).text('🔄 Probando...');
        $result.html('<div style="color: #856404; background: #fff3cd; padding: 12px; border-radius: 6px; margin-top: 15px;">🔄 Probando conexión con la API...</div>');
        
        // AJAX call para probar endpoint
        $.post(ajaxurl, {
            action: 'test_api_endpoint',
            index: index,
            nonce: '<?php echo wp_create_nonce('test_api_endpoint'); ?>'
        }, function(response) {
            if (response.success) {
                let html = `<div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #28a745;">
                    <h4 style="margin: 0 0 10px 0;">✅ API funcionando correctamente</h4>
                    <p style="margin: 5px 0;"><strong>URL Base:</strong> ${response.data.base_url || url}</p>`;
                
                if (response.data.test_url && response.data.test_url !== response.data.base_url) {
                    html += `<p style="margin: 5px 0;"><strong>URL con Parámetros:</strong> ${response.data.test_url}</p>`;
                }
                
                html += `<p style="margin: 5px 0;"><strong>Elementos encontrados:</strong> ${response.data.count}</p>`;
                
                if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                    html += `<p style="margin: 5px 0;"><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                }
                
                if (response.data.sample_data) {
                    html += `<details style="margin-top: 10px;">
                        <summary style="cursor: pointer; font-weight: bold; margin-bottom: 10px;">Ver datos de ejemplo</summary>
                        <pre style="background: #f8f9fa; padding: 12px; border-radius: 4px; max-height: 200px; overflow: auto; font-size: 12px; margin: 0;">${JSON.stringify(response.data.sample_data, null, 2)}</pre>
                    </details>`;
                }
                
                html += '</div>';
                $result.html(html);
            } else {
                let errorHtml = `<div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #dc3545;">
                    <h4 style="margin: 0 0 10px 0;">❌ Error en la API</h4>
                    <p style="margin: 5px 0;"><strong>URL Base:</strong> ${response.data && response.data.base_url ? response.data.base_url : url}</p>`;
                
                if (response.data && response.data.test_url && response.data.test_url !== response.data.base_url) {
                    errorHtml += `<p style="margin: 5px 0;"><strong>URL con Parámetros:</strong> ${response.data.test_url}</p>`;
                }
                
                errorHtml += `<p style="margin: 5px 0;"><strong>Error:</strong> ${response.data ? response.data.message : 'Error desconocido'}</p>
                    <p style="margin: 10px 0 0 0; font-style: italic;">💡 Tip: Si el endpoint requiere parámetros específicos, prueba la URL completa directamente en el navegador.</p>
                </div>`;
                
                $result.html(errorHtml);
            }
        }).fail(function() {
            $result.html('<div style="color: #721c24; background: #f8d7da; padding: 12px; border-radius: 6px; margin-top: 15px;">❌ Error de conexión con el servidor</div>');
        }).always(function() {
            $button.prop('disabled', false).text('🧪 Test API');
        });
    });
    
    // Función para copiar al portapapeles
    window.copyToClipboard = function(text, element) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                showCopyFeedback(element, '✅ Copiado');
            }).catch(function() {
                fallbackCopyTextToClipboard(text, element);
            });
        } else {
            fallbackCopyTextToClipboard(text, element);
        }
    };
    
    function fallbackCopyTextToClipboard(text, element) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyFeedback(element, '✅ Copiado');
        } catch (err) {
            showCopyFeedback(element, '❌ Error al copiar');
        }
        
        document.body.removeChild(textArea);
    }
    
    function showCopyFeedback(element, message) {
        const $element = $(element);
        const originalBg = $element.css('background-color');
        const originalBorder = $element.css('border-left-color');
        const originalText = $element.html();
        
        $element.css({
            'background-color': '#d4edda',
            'border-left-color': '#28a745',
            'transform': 'scale(1.02)'
        });
        $element.html('<span style="color: #155724; font-weight: bold;">' + message + '</span>');
        
        setTimeout(function() {
            $element.css({
                'background-color': originalBg,
                'border-left-color': originalBorder,
                'transform': 'scale(1)'
            });
            $element.html(originalText);
        }, 2000);
    }
    
    // Validación del formulario antes de enviar
    $('form').on('submit', function(e) {
        let hasErrors = false;
        let errorMessages = [];
        
        $('.endpoint-accordion').each(function(index) {
            const $accordion = $(this);
            const name = $accordion.find('input[name*="[name]"]').val().trim();
            const url = $accordion.find('input[name*="[url]"]').val().trim();
            
            if (!name) {
                hasErrors = true;
                errorMessages.push(`Endpoint ${index + 1}: Falta el nombre`);
            }
            
            if (!url) {
                hasErrors = true;
                errorMessages.push(`Endpoint ${index + 1}: Falta la URL`);
            } else if (!isValidUrl(url)) {
                hasErrors = true;
                errorMessages.push(`Endpoint ${index + 1}: URL no válida`);
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            
            // Mostrar errores en un modal elegante
            const errorHtml = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 500px; width: 90%;">
                        <h3 style="color: #dc3545; margin-top: 0;">⚠️ Errores de validación</h3>
                        <p>Por favor corrige los siguientes errores:</p>
                        <ul style="color: #721c24; margin: 20px 0;">
                            ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                        </ul>
                        <button type="button" onclick="$(this).closest('div').parent().remove()" class="button button-primary" style="width: 100%;">Entendido</button>
                    </div>
                </div>
            `;
            
            $('body').append(errorHtml);
            return false;
        }
        
        // Mostrar indicador de guardado
        const $submitBtn = $('input[type="submit"]');
        $submitBtn.prop('disabled', true).val('💾 Guardando...');
    });
    
    // Función para validar URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
});
</script>
