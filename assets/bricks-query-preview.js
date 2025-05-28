/**
 * Bricks Query Type Preview
 * Archivo: assets/bricks-query-preview.js
 */

(function($) {
    'use strict';
    
    // Objeto para preview en Query Types de Bricks
    window.BricksQueryTypePreview = {
        
        init: function() {
            console.log('BricksQueryTypePreview: Inicializando...');
            this.addPreviewToBricksControls();
            this.bindBricksEvents();
        },
        
        addPreviewToBricksControls: function() {
            // Solo ejecutar si estamos en Bricks Builder
            if (typeof bricks === 'undefined' || !bricks.isBuilder) {
                return;
            }
            
            console.log('BricksQueryTypePreview: Añadiendo a controles de Bricks...');
            
            // Observar cambios en el panel de Bricks
            this.observeBricksPanel();
        },
        
        observeBricksPanel: function() {
            // Usar MutationObserver para detectar cuando se abre el panel de Query
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1) { // Element node
                                this.checkForQueryControls(node);
                            }
                        });
                    }
                });
            });
            
            // Observar el panel de Bricks
            const bricksPanel = document.querySelector('#bricks-panel');
            if (bricksPanel) {
                observer.observe(bricksPanel, {
                    childList: true,
                    subtree: true
                });
                console.log('BricksQueryTypePreview: Observer configurado');
            }
        },
        
        checkForQueryControls: function(element) {
            // Buscar selectores de Query Type
            const querySelects = element.querySelectorAll('select[data-key="objectType"], select[name*="objectType"]');
            
            querySelects.forEach((select) => {
                this.addPreviewButtonToSelect(select);
            });
        },
        
        addPreviewButtonToSelect: function(select) {
            // Evitar duplicar botones
            if (select.nextElementSibling && select.nextElementSibling.classList.contains('query-preview-btn')) {
                return;
            }
            
            // Solo añadir botón si es uno de nuestros query types
            const selectedValue = select.value;
            if (!selectedValue || (!selectedValue.startsWith('api_') && !selectedValue.startsWith('source_'))) {
                return;
            }
            
            console.log('BricksQueryTypePreview: Añadiendo botón para', selectedValue);
            
            // Crear botón de preview
            const previewBtn = document.createElement('button');
            previewBtn.type = 'button';
            previewBtn.className = 'query-preview-btn';
            previewBtn.innerHTML = '🔍';
            previewBtn.title = 'Preview del primer elemento';
            previewBtn.style.cssText = `
                margin-left: 5px; 
                padding: 4px 8px; 
                background: #007cba; 
                color: white; 
                border: none; 
                border-radius: 3px; 
                cursor: pointer; 
                font-size: 12px;
            `;
            
            // Añadir event listener
            previewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.showQueryPreview(selectedValue);
            });
            
            // Insertar después del select
            select.parentNode.insertBefore(previewBtn, select.nextSibling);
        },
        
        bindBricksEvents: function() {
            // Escuchar cambios en los selectores de Query Type
            $(document).on('change', 'select[data-key="objectType"], select[name*="objectType"]', (e) => {
                const select = e.target;
                const newValue = select.value;
                
                // Remover botón anterior
                const existingBtn = select.nextElementSibling;
                if (existingBtn && existingBtn.classList.contains('query-preview-btn')) {
                    existingBtn.remove();
                }
                
                // Añadir nuevo botón si es necesario
                if (newValue && (newValue.startsWith('api_') || newValue.startsWith('source_'))) {
                    setTimeout(() => {
                        this.addPreviewButtonToSelect(select);
                    }, 100);
                }
            });
        },
        
        showQueryPreview: function(queryType) {
            console.log('BricksQueryTypePreview: Mostrando preview para', queryType);
            
            // Mostrar modal de preview
            this.createPreviewModal(queryType);
            
            // Hacer petición AJAX para obtener datos
            this.fetchPreviewData(queryType);
        },
        
        createPreviewModal: function(queryType) {
            // Remover modal existente
            const existingModal = document.getElementById('bricks-query-preview-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Crear modal
            const modal = document.createElement('div');
            modal.id = 'bricks-query-preview-modal';
            modal.className = 'bricks-query-preview-modal';
            modal.innerHTML = `
                <div class="modal-backdrop"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>🔍 Preview: ${queryType}</h3>
                        <button class="modal-close" onclick="this.closest('.bricks-query-preview-modal').remove()">✕</button>
                    </div>
                    <div class="modal-content">
                        <div class="loading">⏳ Cargando datos del primer elemento...</div>
                    </div>
                </div>
            `;
            
            // Añadir estilos inline
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            // Añadir estilos para elementos internos
            const style = document.createElement('style');
            style.textContent = `
                .bricks-query-preview-modal .modal-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                }
                .bricks-query-preview-modal .modal-container {
                    position: relative;
                    background: white;
                    border-radius: 8px;
                    max-width: 800px;
                    max-height: 80vh;
                    overflow: auto;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                }
                .bricks-query-preview-modal .modal-header {
                    background: #f8f9fa;
                    padding: 15px 20px;
                    border-bottom: 1px solid #dee2e6;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .bricks-query-preview-modal .modal-header h3 {
                    margin: 0;
                    color: #343a40;
                }
                .bricks-query-preview-modal .modal-close {
                    background: #dc3545;
                    color: white;
                    border: none;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 14px;
                }
                .bricks-query-preview-modal .modal-content {
                    padding: 20px;
                }
                .bricks-query-preview-modal .loading {
                    text-align: center;
                    color: #6c757d;
                    padding: 40px;
                }
                .bricks-query-preview-modal .preview-item {
                    background: #f8f9fa;
                    border-radius: 6px;
                    padding: 15px;
                    margin-bottom: 15px;
                }
                .bricks-query-preview-modal .preview-field {
                    display: grid;
                    grid-template-columns: 150px 1fr;
                    gap: 10px;
                    padding: 8px 0;
                    border-bottom: 1px solid #e9ecef;
                }
                .bricks-query-preview-modal .field-label {
                    font-weight: 600;
                    color: #495057;
                    font-size: 13px;
                }
                .bricks-query-preview-modal .field-value {
                    font-size: 13px;
                    color: #212529;
                    word-break: break-word;
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(modal);
        },
        
        fetchPreviewData: function(queryType) {
            // Hacer petición AJAX
            const data = new FormData();
            data.append('action', 'preview_query_type');
            data.append('query_type', queryType);
            data.append('limit', '1');
            data.append('nonce', bricksApiPreview.nonce || '');
            
            fetch(bricksApiPreview.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                this.displayPreviewData(data);
            })
            .catch(error => {
                console.error('Error al obtener preview:', error);
                this.displayPreviewError('Error al conectar con la API');
            });
        },
        
        displayPreviewData: function(response) {
            const modal = document.getElementById('bricks-query-preview-modal');
            if (!modal) return;
            
            const contentDiv = modal.querySelector('.modal-content');
            
            if (response.success && response.data.preview_data && response.data.preview_data.length > 0) {
                const item = response.data.preview_data[0];
                let html = '<div class="preview-item">';
                html += '<h4>📊 Primer elemento encontrado:</h4>';
                
                // Mostrar campos del item
                Object.entries(item).forEach(([key, value]) => {
                    html += '<div class="preview-field">';
                    html += `<div class="field-label">${key}</div>`;
                    html += `<div class="field-value">${this.formatValue(value)}</div>`;
                    html += '</div>';
                });
                
                html += '</div>';
                
                // Añadir información de dynamic tags
                if (response.data.sample_tags && response.data.sample_tags.length > 0) {
                    html += '<div class="preview-item">';
                    html += '<h4>🏷️ Dynamic Tags disponibles:</h4>';
                    html += '<div style="font-family: monospace; font-size: 12px; line-height: 1.5;">';
                    response.data.sample_tags.slice(0, 10).forEach(tag => {
                        html += `<div style="background: #e7f3ff; padding: 4px 8px; margin: 2px 0; border-radius: 3px;">${tag}</div>`;
                    });
                    html += '</div>';
                    html += '</div>';
                }
                
                contentDiv.innerHTML = html;
            } else {
                this.displayPreviewError(response.data?.message || 'No se encontraron datos');
            }
        },
        
        displayPreviewError: function(message) {
            const modal = document.getElementById('bricks-query-preview-modal');
            if (!modal) return;
            
            const contentDiv = modal.querySelector('.modal-content');
            contentDiv.innerHTML = `
                <div style="text-align: center; color: #dc3545; padding: 40px;">
                    <h4>❌ Error</h4>
                    <p>${message}</p>
                </div>
            `;
        },
        
        formatValue: function(value) {
            if (value === null || value === undefined) {
                return '<em style="color: #6c757d;">null</em>';
            }
            
            if (Array.isArray(value)) {
                return `<span style="color: #17a2b8;">[Array con ${value.length} elementos]</span>`;
            }
            
            if (typeof value === 'object') {
                return `<span style="color: #ffc107;">[Objeto con ${Object.keys(value).length} propiedades]</span>`;
            }
            
            const strValue = String(value);
            if (strValue.length > 100) {
                return strValue.substring(0, 97) + '...';
            }
            
            return strValue;
        }
    };
    
    // Inicializar cuando esté disponible
    function initWhenReady() {
        if (typeof bricks !== 'undefined' && bricks.isBuilder) {
            BricksQueryTypePreview.init();
        } else {
            setTimeout(initWhenReady, 1000);
        }
    }
    
    $(document).ready(function() {
        // Inicializar para Bricks Builder
        initWhenReady();
        
        // También inicializar en cambios de página en Bricks
        $(document).on('bricks/builder/loaded', function() {
            setTimeout(() => {
                BricksQueryTypePreview.init();
            }, 1000);
        });
    });
    
})(jQuery);