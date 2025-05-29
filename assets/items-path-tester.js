/**
 * Items Path Tester - Prueba de rutas de elementos anidados
 * 
 * Este script maneja la funcionalidad para probar el items_path en la página de Query Types
 */
jQuery(document).ready(function($) {
    
    // Manejar clic en botón de prueba de items_path
    $(document).on('click', '.test-items-path', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const endpointId = button.data('endpoint-id');
        const itemsPath = $('#items_path').val();
        const filterField = $('#filter_field').val() || '';
        const filterValue = $('#filter_value').val() || '';
        
        // Mostrar indicador de carga
        button.prop('disabled', true).text('Probando...');
        $('#items-path-result').hide();
        
        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_items_path',
                nonce: bricks_api_vars.nonce,
                endpoint_id: endpointId,
                items_path: itemsPath,
                filter_field: filterField,
                filter_value: filterValue
            },
            success: function(response) {
                button.prop('disabled', false).text('Probar Ruta');
                
                if (response.success) {
                    // Mostrar resultados
                    $('#items-path-message').html(response.data.message);
                    
                    // Mostrar vista previa de datos
                    if (response.data.sample_data) {
                        $('#items-path-preview').html(response.data.sample_data).show();
                    } else {
                        $('#items-path-preview').hide();
                    }
                    
                    // Mostrar parámetros aplicados si están disponibles
                    if (response.data.params_applied && Object.keys(response.data.params_applied).length > 0) {
                        let paramsHtml = '<div class="params-applied" style="margin-top: 10px; padding: 10px; background: #e9ffe9; border: 1px solid #afa; border-radius: 4px;">';
                        paramsHtml += '<h4 style="margin-top: 0; color: #060;">Parámetros aplicados del endpoint:</h4>';
                        paramsHtml += '<ul style="margin: 5px 0; padding-left: 20px;">';
                        
                        for (const [key, value] of Object.entries(response.data.params_applied)) {
                            paramsHtml += '<li><strong>' + key + '</strong>: ' + JSON.stringify(value) + '</li>';
                        }
                        
                        paramsHtml += '</ul>';
                        
                        if (response.data.url_used) {
                            paramsHtml += '<div style="margin-top: 5px;">';
                            paramsHtml += '<strong>URL utilizada:</strong>';
                            paramsHtml += '<code style="display: block; word-break: break-all; padding: 5px; background: #f5f5f5; margin-top: 5px; font-size: 12px;">' + response.data.url_used + '</code>';
                            paramsHtml += '</div>';
                        }
                        
                        paramsHtml += '</div>';
                        
                        $('#items-path-preview').after(paramsHtml);
                    }
                    
                    // Mostrar campos disponibles
                    if (response.data.fields && response.data.fields.length > 0) {
                        let fieldsHtml = '<div class="available-fields" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #eee;">';
                        fieldsHtml += '<h4 style="margin-top: 0;">Campos disponibles:</h4>';
                        
                        // Mostrar campos en formato de lista para mejor legibilidad
                        fieldsHtml += '<ul style="margin: 5px 0; padding-left: 20px;">';
                        const fieldsToShow = response.data.fields.slice(0, 15);
                        fieldsToShow.forEach(field => {
                            fieldsHtml += '<li><code>' + field + '</code></li>';
                        });
                        
                        if (response.data.fields.length > 15) {
                            fieldsHtml += '<li>Y ' + (response.data.fields.length - 15) + ' campos más...</li>';
                        }
                        fieldsHtml += '</ul>';
                        
                        // Añadir información sobre el uso de estos campos
                        fieldsHtml += '<p style="margin-bottom: 0;"><strong>Uso:</strong> Estos campos estarán disponibles como tags dinámicos en Bricks Builder.</p>';
                        fieldsHtml += '</div>';
                        
                        $('.params-applied').length ? $('.params-applied').after(fieldsHtml) : $('#items-path-preview').after(fieldsHtml);
                    }
                    
                    // Mostrar información adicional sobre filtrado
                    if (response.data.has_filter) {
                        let filterInfo = '<div class="filter-info" style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #cce5ff;">';
                        filterInfo += '<h4 style="margin-top: 0;">Información de filtrado:</h4>';
                        filterInfo += '<p>Se aplicó el filtro <code>' + $('#filter_field').val() + ' = ' + $('#filter_value').val() + '</code></p>';
                        filterInfo += '<p>Elementos que cumplen el criterio: <strong>' + response.data.filter_count + '</strong></p>';
                        filterInfo += '</div>';
                        
                        $('.available-fields').after(filterInfo);
                    }
                    
                    // Añadir herramienta de exploración de datos
                    let explorerHtml = '<div class="data-explorer" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
                    explorerHtml += '<h4 style="margin-top: 0;">Explorador de datos</h4>';
                    explorerHtml += '<p>Prueba estas rutas comunes o escribe una personalizada:</p>';
                    
                    // Sugerir rutas comunes basadas en las claves disponibles
                    const commonPaths = ['items', 'data', 'results', 'content', 'items.data', 'data.items', 'status.items'];
                    
                    explorerHtml += '<div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px;">';
                    commonPaths.forEach(path => {
                        explorerHtml += '<button type="button" class="button try-path-button" data-path="' + path + '">' + path + '</button>';
                    });
                    explorerHtml += '</div>';
                    
                    // Añadir campo para ruta personalizada
                    explorerHtml += '<div style="display: flex; gap: 10px; margin-top: 10px;">';
                    explorerHtml += '<input type="text" id="custom_path" placeholder="Ruta personalizada (ej: data.results)" class="regular-text" style="flex: 1;">';
                    explorerHtml += '<button type="button" class="button button-primary" id="try_custom_path">Probar ruta</button>';
                    explorerHtml += '</div>';
                    
                    // Añadir sugerencia para explorar la estructura completa
                    explorerHtml += '<p style="margin-top: 10px;"><strong>Consejo:</strong> Si la API devuelve un objeto con una propiedad "items" que contiene un array, prueba con la ruta <code>items</code>.</p>';
                    
                    explorerHtml += '</div>';
                    
                    $('#items-path-result').append(explorerHtml);
                    
                    // Manejar clic en botones de ruta
                    $('.try-path-button').on('click', function() {
                        $('#items_path').val($(this).data('path'));
                        $('.test-items-path').click();
                    });
                    
                    // Manejar clic en botón de ruta personalizada
                    $('#try_custom_path').on('click', function() {
                        const customPath = $('#custom_path').val();
                        if (customPath) {
                            $('#items_path').val(customPath);
                            $('.test-items-path').click();
                        }
                    });
                    
                    // Cambiar clase de notificación según resultado
                    $('#items-path-result').find('.notice')
                        .removeClass('notice-error notice-warning')
                        .addClass('notice-success')
                        .end()
                        .show();
                    
                } else {
                    // Mostrar error
                    $('#items-path-message').html(response.data.message);
                    
                    // Crear contenido para mostrar la estructura y sugerencias
                    let debugHtml = '';
                    
                    // Mostrar vista previa de datos si está disponible
                    if (response.data.raw_data_preview) {
                        debugHtml += '<h4>Claves disponibles en la respuesta:</h4>';
                        debugHtml += '<pre>' + response.data.raw_data_preview + '</pre>';
                    }
                    
                    // Mostrar ruta auto-detectada si está disponible
                    if (response.data.auto_detected_path) {
                        debugHtml += '<div style="margin-top: 15px; padding: 10px; background: #e9ffe9; border: 1px solid #afa; border-radius: 4px;">';
                        debugHtml += '<h4 style="margin-top: 0; color: #060;">¡Ruta detectada automáticamente!</h4>';
                        debugHtml += '<p>Se ha detectado automáticamente la ruta: <code>' + response.data.auto_detected_path + '</code></p>';
                        debugHtml += '<button type="button" class="button button-primary try-suggested-path" data-path="' + response.data.auto_detected_path + '">Probar esta ruta</button>';
                        debugHtml += '</div>';
                    }
                    
                    // Mostrar estructura completa de la respuesta para depuración avanzada
                    if (response.data.full_response) {
                        debugHtml += '<div style="margin-top: 15px;">';
                        debugHtml += '<details>';
                        debugHtml += '<summary style="cursor: pointer; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; font-weight: bold;">Ver respuesta completa (para depuración avanzada)</summary>';
                        debugHtml += '<div style="padding: 10px; border: 1px solid #ddd; border-top: none; max-height: 400px; overflow: auto;">';
                        debugHtml += '<pre>' + response.data.full_response + '</pre>';
                        debugHtml += '</div>';
                        debugHtml += '</details>';
                        debugHtml += '</div>';
                    }
                    
                    // Mostrar información de estructura si está disponible
                    if (response.data.structure_info) {
                        const info = response.data.structure_info;
                        
                        debugHtml += '<div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">';
                        debugHtml += '<h4 style="margin-top: 0;">Análisis de la estructura:</h4>';
                        
                        // Tipo de datos
                        debugHtml += '<p><strong>Tipo:</strong> ' + info.type;
                        if (info.type === 'array') {
                            debugHtml += ' (' + (info.is_associative ? 'asociativo' : 'indexado') + ')';
                        }
                        debugHtml += ' con ' + info.count + ' elementos</p>';
                        
                        // Mostrar claves o propiedades
                        if (info.keys || info.properties) {
                            const keyList = info.keys || info.properties;
                            debugHtml += '<p><strong>Claves principales:</strong> ';
                            debugHtml += keyList.join(', ');
                            if (info.keys_truncated || info.properties_truncated) {
                                debugHtml += ' ... (y más)';
                            }
                            debugHtml += '</p>';
                        }
                        
                        // Mostrar sugerencias de rutas
                        if (info.suggested_paths && info.suggested_paths.length > 0) {
                            debugHtml += '<div style="margin-top: 10px; padding: 10px; background: #e9f7fe; border: 1px solid #a8d7f3;">';
                            debugHtml += '<h4 style="margin-top: 0; color: #0073aa;">Rutas sugeridas:</h4>';
                            debugHtml += '<ul>';
                            
                            info.suggested_paths.forEach(path => {
                                debugHtml += '<li><a href="#" class="try-suggested-path" data-path="' + path + '">' + 
                                    path + '</a> <small>(haz clic para probar)</small></li>';
                            });
                            
                            debugHtml += '</ul>';
                            debugHtml += '</div>';
                        }
                        
                        debugHtml += '</div>';
                    }
                    
                    // Mostrar muestra de datos si está disponible
                    if (response.data.raw_data_sample) {
                        debugHtml += '<div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">';
                        debugHtml += '<h4 style="margin-top: 0;">Muestra de datos:</h4>';
                        debugHtml += '<pre>' + JSON.stringify(response.data.raw_data_sample, null, 2) + '</pre>';
                        debugHtml += '</div>';
                    }
                    
                    // Actualizar la vista previa con el contenido de depuración
                    $('#items-path-preview').html(debugHtml).show();
                    
                    // Cambiar clase de notificación a advertencia (no error)
                    $('#items-path-result').find('.notice')
                        .removeClass('notice-success notice-error')
                        .addClass('notice-warning')
                        .end()
                        .show();
                }
            },
            error: function() {
                button.prop('disabled', false).text('Probar Ruta');
                $('#items-path-message').html('Error de conexión al servidor');
                $('#items-path-result').find('.notice')
                    .removeClass('notice-success notice-warning')
                    .addClass('notice-error')
                    .end()
                    .show();
            }
        });
    });
    
    // Añadir valores predefinidos para el caso de vehículos
    if ($('#filter_field').length && $('#filter_field').val() === '') {
        // No sugerir ningún campo de filtro predeterminado
        $('#filter_field').val('');
        $('#filter_value').val('');
    }
    
    // Manejar clic en las rutas sugeridas
    $(document).on('click', '.try-suggested-path', function(e) {
        e.preventDefault();
        const suggestedPath = $(this).data('path');
        
        // Actualizar el campo de ruta
        $('#items_path').val(suggestedPath);
        
        // Ejecutar la prueba automáticamente
        $('.test-items-path').click();
    });
});