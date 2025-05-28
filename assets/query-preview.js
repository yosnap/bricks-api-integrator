/**
 * Query Preview JavaScript
 * Archivo: assets/query-preview.js
 */

(function($) {
    'use strict';
    
    // Objeto principal para preview de queries
    window.BricksApiQueryPreview = {
        
        init: function() {
            this.bindEvents();
            this.addPreviewButtons();
        },
        
        bindEvents: function() {
            // Event listener para botones de preview
            $(document).on('click', '.bricks-api-preview-btn', this.handlePreviewClick.bind(this));
            
            // Event listener para cerrar preview
            $(document).on('click', '.preview-modal-close', this.closePreview.bind(this));
            
            // Cerrar con ESC
            $(document).keyup(function(e) {
                if (e.keyCode === 27) {
                    BricksApiQueryPreview.closePreview();
                }
            });
        },
        
        addPreviewButtons: function() {
            // Añadir botones de preview en admin
            this.addAdminPreviewButtons();
        },
        
        addAdminPreviewButtons: function() {
            // Añadir botones en la página de configuración de endpoints
            if (window.location.href.includes('api-endpoints') || window.location.href.includes('bricks-api')) {
                $('.endpoint-card, .endpoint-accordion').each(function() {
                    const $card = $(this);
                    const endpointName = $card.find('input[name*="[name]"]').val();
                    
                    if (endpointName && !$card.find('.admin-preview-btn').length) {
                        const queryType = 'api_' + BricksApiQueryPreview.sanitizeKey(endpointName);
                        const $previewBtn = $('<button type="button" class="button admin-preview-btn bricks-api-preview-btn" data-query-type="' + queryType + '" style="margin-left: 10px;">🔍 Preview Datos</button>');
                        
                        // Añadir después del botón de test
                        const $testBtn = $card.find('.test-endpoint');
                        if ($testBtn.length) {
                            $testBtn.after($previewBtn);
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
        
        // Utilidades básicas
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
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        BricksApiQueryPreview.init();
    });
    
})(jQuery);