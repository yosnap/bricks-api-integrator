-warning show-dynamic-tags" data-index="${index}">
                        🏷️ Ver Dynamic Tags
                    </button>
                    <button type="button" class="btn btn-success refresh-endpoint-data" data-index="${index}">
                        🔄 Actualizar Datos
                    </button>
                    <button type="button" class="btn btn-danger remove-endpoint" data-index="${index}">
                        🗑️ Eliminar
                    </button>
                </div>
            `;
        },
        
        // Generar sección de resultado de pruebas
        generateTestResultSection: function(index) {
            return `<div class="test-result" id="test-result-${index}"></div>`;
        },
        
        // Generar sección de dynamic tags
        generateDynamicTagsSection: function(index) {
            return `
                <div class="dynamic-tags-accordion" id="dynamic-tags-${index}" style="display: none;">
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
            `;
        },
        
        // Obtener parámetros para endpoint relacionado
        getRelatedEndpointParams: function(index) {
            return `
                <div class="dynamic-param-row">
                    <input type="text" name="endpoints[${index}][param_names][]" 
                           value="post_id" placeholder="Nombre del parámetro">
                    <select name="endpoints[${index}][param_sources][]">
                        <option value="post" selected>ID del Post Actual</option>
                        <option value="post_slug">Slug del Post Actual</option>
                        <option value="url">Parámetro URL (?id=123)</option>
                        <option value="meta">Meta Field del Post</option>
                        <option value="user">ID del Usuario Actual</option>
                        <option value="static">Valor Estático</option>
                    </select>
                    <input type="text" name="endpoints[${index}][param_defaults][]" 
                           placeholder="Valor por defecto (opcional)">
                    <button type="button" class="btn btn-danger remove-param">🗑️</button>
                </div>
            `;
        },
        
        // Obtener parámetros para endpoint genérico
        getGenericEndpointParams: function() {
            return '<p style="color: #666; font-style: italic;">No hay parámetros dinámicos configurados</p>';
        },
        
        // Actualizar preview del endpoint
        updateEndpointPreview: function(e) {
            const $input = $(e.currentTarget);
            const $accordion = $input.closest('.endpoint-accordion');
            const name = $accordion.find('.endpoint-name-input').val();
            const url = $accordion.find('.endpoint-url-input').val();
            const isRelated = $accordion.find('.endpoint-header').hasClass('related');
            
            // Actualizar título
            if (name) {
                const prefix = isRelated ? '🔗 ' : '🌐 ';
                $accordion.find('.endpoint-title span').first().text(prefix + name);
            }
            
            // Actualizar preview de URL
            if (url) {
                const shortUrl = url.length > 40 ? url.substring(0, 37) + '...' : url;
                $accordion.find('.endpoint-url-preview').text(shortUrl);
            } else {
                $accordion.find('.endpoint-url-preview').text('Sin configurar');
            }
            
            this.log('Preview actualizado:', { name, url: url.substring(0, 50) + '...' });
        },
        
        // Añadir parámetro dinámico
        addDynamicParam: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const endpointIndex = $button.data('endpoint');
            const $container = $button.siblings('.dynamic-params-container');
            
            this.log('Añadiendo parámetro dinámico para endpoint:', endpointIndex);
            
            // Remover mensaje de "no hay parámetros" si existe
            $container.find('p[style*="italic"]').remove();
            
            const paramHtml = `
                <div class="dynamic-param-row fade-in">
                    <input type="text" name="endpoints[${endpointIndex}][param_names][]" 
                           placeholder="Nombre del parámetro (ej: id, slug)">
                    <select name="endpoints[${endpointIndex}][param_sources][]">
                        <option value="url">Parámetro URL (?id=123)</option>
                        <option value="post">ID del Post Actual</option>
                        <option value="post_slug">Slug del Post Actual</option>
                        <option value="user">ID del Usuario Actual</option>
                        <option value="meta">Meta Field del Post</option>
                        <option value="static">Valor Estático</option>
                    </select>
                    <input type="text" name="endpoints[${endpointIndex}][param_defaults][]" 
                           placeholder="Valor por defecto">
                    <button type="button" class="btn btn-danger remove-param">🗑️</button>
                </div>
            `;
            
            $container.append(paramHtml);
        },
        
        // Eliminar parámetro dinámico
        removeDynamicParam: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $row = $button.closest('.dynamic-param-row');
            const $container = $row.closest('.dynamic-params-container');
            
            this.log('Eliminando parámetro dinámico');
            
            // Animación de salida
            $row.addClass('slide-out');
            setTimeout(() => {
                $row.remove();
                
                // Si no quedan parámetros, mostrar mensaje
                if ($container.find('.dynamic-param-row').length === 0) {
                    $container.html('<p style="color: #666; font-style: italic;">No hay parámetros dinámicos configurados</p>');
                }
            }, this.config.animationDuration);
        },
        
        // Eliminar endpoint
        removeEndpoint: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const $accordion = $button.closest('.endpoint-accordion');
            const index = parseInt($button.data('index'));
            const name = $accordion.find('.endpoint-name-input').val() || `Endpoint ${index + 1}`;
            
            if (!confirm(`¿Estás seguro de que quieres eliminar "${name}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            this.log('Eliminando endpoint:', index, name);
            
            // Animación de salida
            $accordion.addClass('slide-out');
            
            setTimeout(() => {
                $accordion.remove();
                this.reindexEndpoints();
                this.state.endpointCounter = $('.endpoint-accordion').length;
                this.showNotification('✅ Endpoint eliminado correctamente', 'success');
            }, this.config.animationDuration);
        },
        
        // Reindexar endpoints después de eliminar
        reindexEndpoints: function() {
            $('.endpoint-accordion').each((newIndex, element) => {
                const $accordion = $(element);
                
                // Actualizar atributo data-index
                $accordion.attr('data-index', newIndex);
                
                // Actualizar inputs y selects
                $accordion.find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name && name.includes('endpoints[')) {
                        const newName = name.replace(/endpoints\[\d+\]/, `endpoints[${newIndex}]`);
                        $(this).attr('name', newName);
                    }
                });
                
                // Actualizar elementos con data-index y data-endpoint
                $accordion.find('[data-index]').each(function() {
                    $(this).attr('data-index', newIndex);
                });
                
                $accordion.find('[data-endpoint]').each(function() {
                    $(this).attr('data-endpoint', newIndex);
                });
                
                // Actualizar título
                const isRelated = $accordion.find('.endpoint-header').hasClass('related');
                const currentName = $accordion.find('.endpoint-name-input').val();
                const titleText = (isRelated ? '🔗 ' : '🌐 ') + (currentName || `Endpoint ${newIndex + 1}`);
                $accordion.find('.endpoint-title span').first().text(titleText);
            });
            
            this.log('Endpoints reindexados');
        },
        
        // Test de endpoint
        testEndpoint: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const index = $button.data('index');
            const $result = $('#test-result-' + index);
            const $statusIndicator = $(`[data-endpoint="${index}"]`);
            const $accordion = $button.closest('.endpoint-accordion');
            
            // Obtener datos del endpoint
            const name = $accordion.find('.endpoint-name-input').val();
            const url = $accordion.find('.endpoint-url-input').val();
            
            if (!url) {
                this.showTestResult($result, 'error', '❌ URL requerida para realizar la prueba');
                $statusIndicator.removeClass('success').addClass('error');
                return;
            }
            
            this.log('Probando endpoint:', index, name);
            
            // Configurar estado de carga
            this.setLoadingState($button, '🔄 Probando...', true);
            this.showTestResult($result, 'loading', '🔄 Probando conexión con la API...');
            $statusIndicator.removeClass('success error');
            
            // AJAX call
            $.post(ajaxurl, {
                action: 'test_api_endpoint',
                index: index,
                nonce: this.state.currentNonce
            })
            .done((response) => {
                if (response.success) {
                    this.handleTestSuccess(response, $result, $statusIndicator, url);
                } else {
                    this.handleTestError(response, $result, $statusIndicator, url);
                }
            })
            .fail(() => {
                this.showTestResult($result, 'error', '❌ Error de conexión con el servidor');
                $statusIndicator.removeClass('success').addClass('error');
            })
            .always(() => {
                this.setLoadingState($button, '🧪 Test API', false);
            });
        },
        
        // Manejar éxito en test
        handleTestSuccess: function(response, $result, $statusIndicator, url) {
            $statusIndicator.removeClass('error').addClass('success');
            
            let html = `
                <p><strong>✅ API funcionando correctamente</strong></p>
                <p><strong>URL Base:</strong> ${response.data.base_url || url}</p>
            `;
            
            if (response.data.test_url && response.data.test_url !== response.data.base_url) {
                html += `<p><strong>URL con Parámetros:</strong> ${response.data.test_url}</p>`;
            }
            
            html += `<p><strong>Elementos encontrados:</strong> ${response.data.count}</p>`;
            
            if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
            }
            
            if (response.data.sample_data) {
                html += `
                    <details class="api-sample-details" style="margin-top: 15px; border: 1px solid #6c757d; border-radius: 5px; padding: 0; overflow: hidden;">
                        <summary style="cursor: pointer; font-weight: bold; color: white; background: #6c757d; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                            <span>📋 Ver datos de ejemplo</span>
                            <span class="toggle-icon">▼</span>
                        </summary>
                        <div style="padding: 15px; border-top: 1px solid #6c757d;">
                            <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Muestra de los datos recibidos:</p>
                            <pre style="background: #f8f8f8; padding: 12px; border-radius: 3px; max-height: 200px; overflow: auto; font-size: 12px; margin: 0; border: 1px solid #dee2e6;">${JSON.stringify(response.data.sample_data, null, 2)}</pre>
                        </div>
                    </details>
                `;
            }
            
            // Mostrar el payload completo para mejor diagnóstico
            if (response.data.full_response) {
                html += `
                    <details class="api-response-details" style="margin-top: 15px; border: 1px solid #0073aa; border-radius: 5px; padding: 0; overflow: hidden;">
                        <summary style="cursor: pointer; font-weight: bold; color: white; background: #0073aa; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                            <span>📊 Ver respuesta completa de la API</span>
                            <span class="toggle-icon">▼</span>
                        </summary>
                        <div style="padding: 15px; border-top: 1px solid #0073aa;">
                            <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Esta es la respuesta completa recibida de la API, útil para diagnóstico y depuración.</p>
                            <pre style="background: #f0f8ff; padding: 12px; border-radius: 3px; max-height: 400px; overflow: auto; font-size: 12px; border: 1px solid #cce5ff; margin: 0;">${response.data.full_response}</pre>
                        </div>
                    </details>
                `;
            }
            
            // Mostrar estructura de datos para mejor comprensión
            if (response.data.response_structure) {
                html += `
                    <details class="api-structure-details" style="margin-top: 15px; border: 1px solid #46b450; border-radius: 5px; padding: 0; overflow: hidden;">
                        <summary style="cursor: pointer; font-weight: bold; color: white; background: #46b450; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                            <span>🔍 Ver estructura de datos</span>
                            <span class="toggle-icon">▼</span>
                        </summary>
                        <div style="padding: 15px; border-top: 1px solid #46b450;">
                            <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Análisis de la estructura de datos recibida:</p>
                            <pre style="background: #f6fff8; padding: 12px; border-radius: 3px; max-height: 300px; overflow: auto; font-size: 12px; border: 1px solid #c3e6cb; margin: 0;">${JSON.stringify(response.data.response_structure, null, 2)}</pre>
                        </div>
                    </details>
                `;
            }
            
            this.showTestResult($result, 'success', html);
            this.showNotification('✅ API probada correctamente', 'success');
        },
        
        // Manejar error en test
        handleTestError: function(response, $result, $statusIndicator, url) {
            $statusIndicator.removeClass('success').addClass('error');
            
            let html = `
                <p><strong>❌ Error en la API</strong></p>
                <p><strong>URL Base:</strong> ${response.data && response.data.base_url ? response.data.base_url : url}</p>
            `;
            
            if (response.data && response.data.test_url && response.data.test_url !== response.data.base_url) {
                html += `<p><strong>URL con Parámetros:</strong> ${response.data.test_url}</p>`;
            }
            
            html += `
                <p><strong>Error:</strong> ${response.data ? response.data.message : 'Error desconocido'}</p>
                <p><em>💡 Tip: Verifica la URL y configuración de autenticación.</em></p>
            `;
            
            this.showTestResult($result, 'error', html);
            this.showNotification('❌ Error al probar API', 'error');
        },
        
        // Actualizar datos del endpoint
        refreshEndpointData: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const index = $button.data('index');
            const $result = $('#test-result-' + index);
            const $accordion = $button.closest('.endpoint-accordion');
            
            const url = $accordion.find('.endpoint-url-input').val();
            
            if (!url) {
                this.showTestResult($result, 'error', '❌ URL requerida para actualizar datos');
                return;
            }
            
            this.log('Actualizando datos del endpoint:', index);
            
            this.setLoadingState($button, '🔄 Actualizando...', true);
            this.showTestResult($result, 'loading', '🔄 Forzando actualización de datos desde la API...');
            
            $.post(ajaxurl, {
                action: 'refresh_endpoint_data',
                index: index,
                nonce: this.state.currentNonce
            })
            .done((response) => {
                if (response.success) {
                    let html = `
                        <p><strong>✅ Datos actualizados correctamente</strong></p>
                        <p><strong>Elementos encontrados:</strong> ${response.data.count}</p>
                    `;
                    
                    if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                        html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                    }
                    
                    if (response.data.cache_cleared) {
                        html += `<p><strong>🗑️ Caché limpiado:</strong> Los datos se han actualizado desde la API</p>`;
                    }
                    
                    this.showTestResult($result, 'success', html);
                    this.showNotification('✅ Datos actualizados correctamente', 'success');
                } else {
                    this.showTestResult($result, 'error', `❌ Error: ${response.data ? response.data.message : 'Error desconocido'}`);
                    this.showNotification('❌ Error al actualizar datos', 'error');
                }
            })
            .fail(() => {
                this.showTestResult($result, 'error', '❌ Error de conexión');
                this.showNotification('❌ Error de conexión', 'error');
            })
            .always(() => {
                this.setLoadingState($button, '🔄 Actualizar Datos', false);
            });
        },
        
        // Toggle dynamic tags
        toggleDynamicTags: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const index = $button.data('index');
            const $accordion = $('#dynamic-tags-' + index);
            
            this.log('Toggle dynamic tags para endpoint:', index);
            
            if ($accordion.is(':visible')) {
                $accordion.slideUp(this.config.animationDuration);
                $button.html('🏷️ Ver Dynamic Tags');
            } else {
                $accordion.slideDown(this.config.animationDuration);
                $button.html('🏷️ Ocultar Dynamic Tags');
                this.loadDynamicTags(index);
            }
        },
        
        // Cargar dynamic tags via AJAX
        loadDynamicTags: function(index) {
            const $accordion = $('#dynamic-tags-' + index);
            const $loading = $accordion.find('.tags-loading');
            const $tagsList = $accordion.find('.tags-list');
            
            $loading.show();
            $tagsList.hide();
            
            // Obtener datos del endpoint
            const $endpointAccordion = $(`[data-index="${index}"]`);
            const name = $endpointAccordion.find('.endpoint-name-input').val();
            const url = $endpointAccordion.find('.endpoint-url-input').val();
            
            if (!name || !url) {
                $loading.html('<span style="color: #d63638;">❌ Configura el nombre y URL del endpoint primero</span>');
                return;
            }
            
            this.log('Cargando dynamic tags para:', name);
            
            $.post(ajaxurl, {
                action: 'get_dynamic_tags_for_endpoint',
                index: index,
                nonce: this.state.currentNonce
            })
            .done((response) => {
                if (response.success && response.data.tags && response.data.tags.length > 0) {
                    this.displayDynamicTags(response.data, $tagsList, $loading);
                } else {
                    $loading.html('<div style="color: #f0ad4e; padding: 15px;">⚠️ No se generaron dynamic tags. Verifica que el endpoint devuelve datos válidos.</div>');
                }
            })
            .fail(() => {
                $loading.html('<div style="color: #d63638; padding: 15px; background: #ffeaea; border-radius: 3px;"><strong>❌ Error de conexión</strong></div>');
            });
        },
        
        // Mostrar dynamic tags
        displayDynamicTags: function(data, $tagsList, $loading) {
            let html = '';
            
            if (data.tags && data.tags.length > 0) {
                html += '<div style="padding: 15px;">';
                html += '<h4 style="color: #007cba; margin-bottom: 15px;">✅ Dynamic Tags Generados (' + data.tags.length + ')</h4>';
                
                // Información del endpoint
                if (data.endpoint_name) {
                    html += '<div style="background: #e7f3ff; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #0073aa;">';
                    html += '<strong>📊 Endpoint:</strong> ' + data.endpoint_name;
                    if (data.data_count) {
                        html += ' | <strong>Elementos:</strong> ' + data.data_count;
                    }
                    if (data.has_dynamic_params) {
                        html += ' | <strong>✨ Parámetros dinámicos:</strong> Sí';
                    }
                    html += '</div>';
                }
                
                // Grid de tags
                html += '<div style="display: grid; gap: 8px; font-family: monospace; font-size: 12px;">';
                
                data.tags.forEach((tag) => {
                    const fieldMatch = tag.match(/\{snap_[^_]+_(.+)\}/);
                    const fieldName = fieldMatch ? fieldMatch[1] : '';
                    
                    let sampleValue = '';
                    if (fieldName && data.sample_data && typeof data.sample_data === 'object') {
                        sampleValue = this.findSampleValue(data.sample_data, fieldName);
                    }
                    
                    html += '<div class="tag-item" onclick="BricksAPIIntegrator.copyToClipboard(\'' + tag + '\', this)" title="Clic para copiar">';
                    html += '<div><code style="color: #d63384; font-weight: bold;">' + tag + '</code></div>';
                    html += '<div class="tag-sample-value">';
                    if (sampleValue) {
                        html += '<span class="sample-data">📄 ' + this.escapeHtml(sampleValue) + '</span>';
                    } else {
                        html += '<span class="no-sample">sin ejemplo</span>';
                    }
                    html += '</div></div>';
                });
                
                html += '</div>';
                html += '<p style="margin-top: 15px; padding: 10px; background: #d1ecf1; border-radius: 3px; font-size: 13px;">💡 <strong>Cómo usar:</strong> Haz clic en cualquier tag para copiarlo.</p>';
                html += '</div>';
            } else {
                html = '<div style="text-align: center; padding: 30px; color: #666;">⚠️ No se generaron dynamic tags</div>';
            }
            
            $tagsList.html(html);
            $loading.hide();
            $tagsList.show();
        },
        
        // Buscar valor de ejemplo
        findSampleValue: function(data, fieldName) {
            // Búsqueda directa
            if (data[fieldName] !== undefined) {
                return this.formatSampleValue(data[fieldName]);
            }
            
            // Búsqueda normalizada
            const normalizedField = fieldName.toLowerCase().replace(/_/g, '');
            for (const key in data) {
                const normalizedKey = key.toLowerCase().replace(/[_-]/g, '');
                if (normalizedKey === normalizedField) {
                    return this.formatSampleValue(data[key]);
                }
            }
            
            return null;
        },
        
        // Formatear valor de ejemplo
        formatSampleValue: function(value) {
            if (value === null || value === undefined) return '';
            if (typeof value === 'boolean') return value ? 'Sí' : 'No';
            if (typeof value === 'object') {
                if (Array.isArray(value)) {
                    return value.length + ' elementos';
                }
                return 'Objeto (' + Object.keys(value).length + ' campos)';
            }
            const strValue = String(value);
            return strValue.length > 50 ? strValue.substring(0, 47) + '...' : strValue;
        },
        
        // Escapar HTML
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // Copiar al portapapeles
        copyToClipboard: function(text, element) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showCopyFeedback(element, text);
                }).catch(() => {
                    this.fallbackCopyTextToClipboard(text, element);
                });
            } else {
                this.fallbackCopyTextToClipboard(text, element);
            }
        },
        
        // Fallback para copiar
        fallbackCopyTextToClipboard: function(text, element) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.top = "-9999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    this.showCopyFeedback(element, text);
                }
            } catch (err) {
                this.log('Error al copiar:', err);
            }
            
            document.body.removeChild(textArea);
        },
        
        // Mostrar feedback de copia
        showCopyFeedback: function(element, text) {
            // Feedback visual en el elemento
            $(element).addClass('copy-success');
            setTimeout(() => {
                $(element).removeClass('copy-success');
            }, 600);
            
            // Notificación flotante
            this.showNotification(`📋 Copiado: <code>${text}</code>`, 'success');
        },
        
        // Guardar y probar todos
        saveAndTestAll: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            this.setLoadingState($button, '💾 Guardando...', true);
            
            // Simular guardado (el formulario se enviará)
            setTimeout(() => {
                $('.test-endpoint').each((index, button) => {
                    setTimeout(() => {
                        $(button).trigger('click');
                    }, index * 500); // Escalonar las pruebas
                });
                
                this.setLoadingState($button, '💾 Guardar y Probar Todos', false);
            }, 1000);
        },
        
        // Inicializar acordeones existentes
        initializeExistingAccordions: function() {
            $('.endpoint-accordion').each((index, element) => {
                const $accordion = $(element);
                // Colapsar acordeones existentes por defecto
                $accordion.addClass('collapsed').find('.endpoint-content').removeClass('expanded');
            });
            
            this.log('Acordeones existentes inicializados (colapsados por defecto)');
        },
        
        // Inicializar inputs de autenticación existentes
        initializeExistingAuthInputs: function() {
            $('.auth-type-select').each((index, select) => {
                const $select = $(select);
                if ($select.val() !== 'none') {
                    // Trigger change para mostrar inputs existentes
                    $select.trigger('change');
                }
            });
            
            this.log('Inputs de autenticación existentes inicializados');
        },
        
        // Configurar estado de carga
        setLoadingState: function($button, text, isLoading) {
            if (isLoading) {
                $button.prop('disabled', true).html(text).addClass('loading');
                this.state.loadingStates.set($button[0], { originalText: $button.text(), isLoading: true });
            } else {
                $button.prop('disabled', false).html(text).removeClass('loading');
                this.state.loadingStates.delete($button[0]);
            }
        },
        
        // Mostrar resultado de prueba
        showTestResult: function($result, type, content) {
            $result.removeClass('success error loading')
                   .addClass(type)
                   .html(content)
                   .show();
            
            // Asegurarse de que los desplegables funcionen correctamente
            if (type === 'success') {
                // Añadir un pequeño retraso para asegurar que el DOM se ha actualizado
                setTimeout(() => {
                    // Inicializar los desplegables y añadir estilos para mejorar la visualización
                    $result.find('details').each(function() {
                        // Añadir clase para estilos
                        $(this).addClass('api-details-section');
                        
                        // Añadir evento de clic para el summary
                        $(this).find('summary').on('click', function(e) {
                            e.preventDefault();
                            const details = $(this).parent('details');
                            if (details.attr('open')) {
                                details.removeAttr('open');
                            } else {
                                details.attr('open', 'open');
                            }
                        });
                    });
                    
                    console.log('Desplegables inicializados:', $result.find('details').length);
                }, 100);
            }
        },
        
        // Mostrar notificación flotante
        showNotification: function(message, type = 'success') {
            const $notification = $(`
                <div class="floating-notification ${type}">
                    ${message}
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.css({
                    opacity: '0',
                    transition: 'opacity 0.3s'
                });
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, this.config.notificationDuration);
        },
        
        // Función de logging
        log: function(...args) {
            if (this.config.debug || (typeof window.console !== 'undefined' && window.location.search.includes('debug=1'))) {
                console.log('[Bricks API Integrator]', ...args);
            }
        }
    };
    
    // Inicialización cuando el DOM esté listo
    $(document).ready(function() {
        // Hacer el objeto globalmente accesible
        window.BricksAPIIntegrator = BricksAPIIntegrator;
        
        // Inicializar el plugin
        BricksAPIIntegrator.init();
        
        // Exponer función de copia globalmente para compatibilidad
        window.copyToClipboard = function(text, element) {
            BricksAPIIntegrator.copyToClipboard(text, element);
        };
        
        // Asegurar que los botones de eliminar parámetro funcionen siempre
        $(document).on('click', '.remove-param', function(e) {
            if (typeof BricksAPIIntegrator !== 'undefined' && BricksAPIIntegrator.removeDynamicParam) {
                BricksAPIIntegrator.removeDynamicParam(e);
            }
        });
    });
    
})(jQuery);