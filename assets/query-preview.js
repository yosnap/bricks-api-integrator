                        } else {
                            $card.find('.form-table').last().after($previewBtn);
                        }
                    }
                });
            }
        },
        
        handlePreviewClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(e.currentTarget);
            const queryType = $btn.data('query-type');
            
            if (!queryType) {
                alert('Error: No se pudo determinar el query type');
                return;
            }
            
            this.showPreview(queryType, $btn);
        },
        
        showPreview: function(queryType, $triggerBtn) {
            // Cambiar estado del botón
            const originalText = $triggerBtn.text();
            $triggerBtn.prop('disabled', true).text('⏳ Cargando...');
            
            // Hacer petición AJAX
            $.post(bricksApiPreview.ajaxUrl, {
                action: 'preview_query_type',
                query_type: queryType,
                limit: 1,
                nonce: this.generateNonce(queryType)
            }, (response) => {
                if (response.success) {
                    this.displayPreviewModal(response.data);
                } else {
                    alert('Error: ' + (response.data.message || 'Error desconocido'));
                }
            }).fail((xhr, status, error) => {
                alert('Error de conexión: ' + error);
            }).always(() => {
                // Restaurar botón
                $triggerBtn.prop('disabled', false).text(originalText);
            });
        },
        
        displayPreviewModal: function(data) {
            // Crear modal si no existe
            if (!$('#bricks-api-preview-modal').length) {
                this.createPreviewModal();
            }
            
            const $modal = $('#bricks-api-preview-modal');
            const $content = $modal.find('.preview-content');
            
            // Construir contenido
            let html = '<div class="preview-header">';
            html += '<div class="preview-title">';
            html += '<h3>🔍 Preview: ' + this.sanitizeHtml(data.query_type) + '</h3>';
            html += '<p>Total encontrados: <strong>' + data.total_found + '</strong> | Mostrando: <strong>Primer elemento</strong></p>';
            html += '</div>';
            html += '<button class="preview-modal-close">✕</button>';
            html += '</div>';
            
            // Pestañas
            html += '<div class="preview-tabs">';
            html += '<button class="preview-tab active" data-tab="data">📊 Datos</button>';
            html += '<button class="preview-tab" data-tab="tags">🏷️ Dynamic Tags</button>';
            html += '<button class="preview-tab" data-tab="raw">🔧 JSON Raw</button>';
            html += '</div>';
            
            // Contenido de pestañas
            html += '<div class="preview-tab-content">';
            
            // Pestaña de datos
            html += '<div class="preview-tab-pane active" id="preview-data">';
            html += this.buildDataPreview(data.preview_data[0]);
            html += '</div>';
            
            // Pestaña de tags
            html += '<div class="preview-tab-pane" id="preview-tags">';
            html += this.buildTagsPreview(data.sample_tags, data.preview_data[0]);
            html += '</div>';
            
            // Pestaña raw
            html += '<div class="preview-tab-pane" id="preview-raw">';
            html += '<pre class="preview-json">' + JSON.stringify(data.preview_data[0], null, 2) + '</pre>';
            html += '</div>';
            
            html += '</div>';
            
            $content.html(html);
            
            // Mostrar modal
            $modal.addClass('active');
            $('body').addClass('preview-modal-open');
            
            // Bind tab events
            this.bindTabEvents();
        },
        
        buildDataPreview: function(item) {
            let html = '<div class="preview-data-grid">';
            
            const itemObj = typeof item === 'object' ? item : {};
            
            // Construir HTML simple
            html += '<div class="preview-group">';
            html += '<h4 class="preview-group-title">Datos del Item</h4>';
            html += '<div class="preview-fields">';
            
            Object.entries(itemObj).forEach(([key, value]) => {
                html += '<div class="preview-field">';
                html += '<div class="field-label">' + this.sanitizeHtml(key) + '</div>';
                html += '<div class="field-value">' + this.formatPreviewValue(value) + '</div>';
                html += '</div>';
            });
            
            html += '</div></div>';
            html += '</div>';
            return html;
        },
        
        buildTagsPreview: function(tags, itemData) {
            let html = '<div class="preview-tags-container">';
            html += '<div class="tags-info">';
            html += '<p>💡 <strong>Cómo usar:</strong> Copia estos tags y pégalos en tus elementos de Bricks.</p>';
            html += '</div>';
            
            html += '<div class="preview-tags-grid">';
            
            tags.forEach(tag => {
                html += '<div class="preview-tag-item" onclick="BricksApiQueryPreview.copyTag(\'' + tag + '\')">';
                html += '<div class="tag-code">' + this.sanitizeHtml(tag) + '</div>';
                html += '</div>';
            });
            
            html += '</div></div>';
            return html;
        },
        
        copyTag: function(tag) {
            // Copiar al portapapeles
            if (navigator.clipboard) {
                navigator.clipboard.writeText(tag).then(() => {
                    this.showCopyNotification('Tag copiado: ' + tag);
                });
            } else {
                // Fallback para navegadores antiguos
                const textArea = document.createElement('textarea');
                textArea.value = tag;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showCopyNotification('Tag copiado: ' + tag);
            }
        },
        
        showCopyNotification: function(message) {
            // Crear notificación temporal
            const $notification = $('<div class="copy-notification">' + message + '</div>');
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: '#28a745',
                color: 'white',
                padding: '10px 15px',
                borderRadius: '5px',
                zIndex: 999999,
                fontSize: '14px'
            });
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        },
        
        createPreviewModal: function() {
            const modalHtml = `
                <div id="bricks-api-preview-modal" class="preview-modal">
                    <div class="preview-modal-backdrop"></div>
                    <div class="preview-modal-container">
                        <div class="preview-content">
                            <!-- Content will be inserted here -->
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },
        
        bindTabEvents: function() {
            $('.preview-tab').off('click.preview').on('click.preview', function() {
                const $tab = $(this);
                const tabId = $tab.data('tab');
                
                // Cambiar tab activo
                $('.preview-tab').removeClass('active');
                $tab.addClass('active');
                
                // Cambiar contenido activo
                $('.preview-tab-pane').removeClass('active');
                $('#preview-' + tabId).addClass('active');
            });
        },
        
        closePreview: function() {
            $('#bricks-api-preview-modal').removeClass('active');
            $('body').removeClass('preview-modal-open');
        },
        
        // Utilidades
        sanitizeKey: function(str) {
            return str.toLowerCase()
                     .replace(/[^a-z0-9]/g, '_')
                     .replace(/_+/g, '_')
                     .replace(/^_|_$/g, '');
        },
        
        sanitizeHtml: function(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },
        
        formatPreviewValue: function(value) {
            if (value === null || value === undefined) {
                return '<span class="null-value">null</span>';
            }
            
            if (typeof value === 'boolean') {
                return '<span class="boolean-value">' + (value ? 'Sí' : 'No') + '</span>';
            }
            
            if (Array.isArray(value)) {
                const count = value.length;
                const preview = value.slice(0, 3).join(', ');
                return '<span class="array-value">(' + count + ' elementos) ' + this.sanitizeHtml(preview) + (count > 3 ? '...' : '') + '</span>';
            }
            
            if (typeof value === 'object') {
                const keys = Object.keys(value);
                return '<span class="object-value">(Objeto con ' + keys.length + ' propiedades)</span>';
            }
            
            const strValue = String(value);
            
            // Detectar si es una URL de imagen
            if (strValue.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
                return '<div class="image-preview"><img src="' + this.sanitizeHtml(strValue) + '" style="max-width: 100px; max-height: 60px; border-radius: 3px;" onerror="this.style.display=\'none\'"><div class="image-url">' + this.sanitizeHtml(strValue.length > 50 ? strValue.substring(0, 47) + '...' : strValue) + '</div></div>';
            }
            
            // Truncar texto largo
            if (strValue.length > 150) {
                return '<span class="long-text" title="' + this.sanitizeHtml(strValue) + '">' + this.sanitizeHtml(strValue.substring(0, 147)) + '...</span>';
            }
            
            return '<span class="text-value">' + this.sanitizeHtml(strValue) + '</span>';
        },
        
        generateNonce: function(queryType) {
            // En un entorno real, esto debería venir del servidor
            return bricksApiPreview.nonce || '';
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        BricksApiQueryPreview.init();
    });
    
})(jQuery);
