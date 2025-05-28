/**
 * Query Preview JavaScript - Version Corregida
 * Archivo: assets/query-preview.js
 */

(function($) {
    'use strict';
    
    // Verificar que jQuery está disponible
    if (typeof $ === 'undefined') {
        console.error('jQuery no está disponible para query-preview.js');
        return;
    }
    
    // Objeto principal para preview de queries
    window.BricksApiQueryPreview = {
        
        init: function() {
            console.log('BricksApiQueryPreview: Inicializando...');
            this.bindEvents();
            this.addPreviewButtons();
        },
        
        bindEvents: function() {
            console.log('BricksApiQueryPreview: Binding events...');
            
            // Event listener para botones de preview
            $(document).on('click', '.bricks-api-preview-btn', this.handlePreviewClick.bind(this));
            
            // Event listener para cerrar preview  
            $(document).on('click', '.preview-modal-close', this.closePreview.bind(this));
            
            // Cerrar con ESC
            var self = this;
            $(document).keyup(function(e) {
                if (e.keyCode === 27) {
                    self.closePreview();
                }
            });
        },
        
        addPreviewButtons: function() {
            console.log('BricksApiQueryPreview: Añadiendo botones de preview...');
            
            // Solo ejecutar si estamos en una página de admin relevante
            if (this.isRelevantAdminPage()) {
                this.addAdminPreviewButtons();
            }
        },
        
        isRelevantAdminPage: function() {
            if (typeof window.location === 'undefined' || !window.location.href) {
                return false;
            }
            
            return window.location.href.includes('api-endpoints') || 
                   window.location.href.includes('bricks-api') ||
                   window.location.href.includes('page=bricks-api-integrator');
        },
        
        addAdminPreviewButtons: function() {
            // No añadir botones en endpoints - solo funcionalidad básica
            console.log('Query preview: Admin buttons disabled for endpoints');
        },
        
        handlePreviewClick: function(e) {
            console.log('BricksApiQueryPreview: Preview button clicked');
            
            if (e && e.preventDefault) {
                e.preventDefault();
            }
            if (e && e.stopPropagation) {
                e.stopPropagation();
            }
            
            var $btn = $(e.currentTarget);
            var queryType = $btn.data('query-type');
            
            if (!queryType) {
                alert('Error: No se pudo determinar el query type');
                return;
            }
            
            console.log('Query type:', queryType);
            
            // Por ahora, mostrar un mensaje simple
            alert('Preview funcionando para: ' + queryType + '\n\nEsta funcionalidad se expandirá pronto con el modal completo.');
        },
        
        closePreview: function() {
            console.log('BricksApiQueryPreview: Closing preview');
            
            var $modal = $('#bricks-api-preview-modal');
            if ($modal.length) {
                $modal.removeClass('active');
                $('body').removeClass('preview-modal-open');
            }
        },
        
        // Utilidades
        sanitizeKey: function(str) {
            if (typeof str !== 'string') {
                return 'default';
            }
            
            return str.toLowerCase()
                     .replace(/[^a-z0-9]/g, '_')
                     .replace(/_+/g, '_')
                     .replace(/^_|_$/g, '');
        },
        
        sanitizeHtml: function(str) {
            if (typeof str !== 'string') {
                return '';
            }
            
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        console.log('DOM ready - Inicializando BricksApiQueryPreview');
        
        // Verificar que el objeto bricksApiPreview está disponible
        if (typeof bricksApiPreview === 'undefined') {
            console.log('bricksApiPreview no está definido, creando objeto por defecto');
            window.bricksApiPreview = {
                ajaxUrl: (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php',
                nonce: 'default-nonce'
            };
        }
        
        // Inicializar nuestro objeto
        if (window.BricksApiQueryPreview) {
            window.BricksApiQueryPreview.init();
        } else {
            console.error('BricksApiQueryPreview no se pudo inicializar');
        }
    });
    
})(jQuery);