/**
 * Mostrador automático de filtros activos
 * Funciona sin necesidad de shortcode si existe el elemento container
 * @version 1.0
 */

(function() {
    'use strict';

    /**
     * Renderizar filtros activos en un contenedor
     */
    function renderActiveFilters(container) {
        if (!container) return;

        // Obtener parámetros de la URL
        const params = new URLSearchParams(window.location.search);
        const activeFilters = {};

        // Filtrar solo parámetros que comienzan con key_
        for (const [key, value] of params) {
            if (key.startsWith('key_') && value.trim() !== '') {
                activeFilters[key] = value;
            }
        }

        // Si no hay filtros, vaciar el contenedor
        if (Object.keys(activeFilters).length === 0) {
            container.innerHTML = '';
            container.classList.add('empty');
            return;
        }

        container.classList.remove('empty');

        // Etiquetas de filtros
        const filterLabels = {
            'key_tipo': 'Tipo de Propiedad',
            'key_loca': 'Ubicación',
            'key_zona': 'Zona',
            'key_precio_min': 'Precio Mínimo',
            'key_precio_max': 'Precio Máximo',
            'key_hab': 'Habitaciones',
            'key_banos': 'Baños',
            'key_suelo': 'Superficie',
            'key_estado': 'Estado',
            'key_buscar': 'Búsqueda'
        };

        // Construir HTML
        let html = '<div class="inmovilla-active-filters">';
        html += '<div class="inmovilla-active-filters-title">Filtros activos:</div>';
        html += '<div class="inmovilla-active-filters-list">';

        // Añadir cada filtro
        for (const [filterKey, filterValue] of Object.entries(activeFilters)) {
            const filterLabel = filterLabels[filterKey] || filterKey.replace('key_', '').replace(/_/g, ' ');

            // Crear URL sin este filtro
            const newParams = new URLSearchParams(window.location.search);
            newParams.delete(filterKey);
            const newUrl = window.location.pathname + (newParams.toString() ? '?' + newParams.toString() : '');

            html += `<div class="inmovilla-filter-badge" data-filter="${filterKey}">
                <span class="inmovilla-filter-label">${escapeHtml(filterLabel)}:</span>
                <span class="inmovilla-filter-value">${escapeHtml(filterValue)}</span>
                <button class="inmovilla-filter-remove" onclick="window.location.href='${escapeHtml(newUrl)}'; return false;" title="Eliminar este filtro">×</button>
            </div>`;
        }

        // Botón para limpiar todos
        html += `<button class="inmovilla-filters-clear-all" onclick="window.location.href='${window.location.pathname}'; return false;">Limpiar todos</button>`;

        html += '</div></div>';

        container.innerHTML = html;
    }

    /**
     * Escapar HTML para evitar XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Inicializar mostrador de filtros
     */
    function initFilterDisplay() {
        // Buscar contenedor específico (ideal para uso manual)
        const specificContainer = document.querySelector('[data-inmovilla-filters]');
        if (specificContainer) {
            renderActiveFilters(specificContainer);
        }

        // También buscar dentro de shortcode si existe
        const shortcodeContainer = document.querySelector('.inmovilla-active-filters');
        if (shortcodeContainer && shortcodeContainer.innerHTML.trim() === '') {
            renderActiveFilters(shortcodeContainer);
        }
    }

    // Inicializar cuando el DOM está listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilterDisplay);
    } else {
        initFilterDisplay();
    }

    // Re-inicializar si el contenido dinámico se carga
    if (typeof window.MutationObserver !== 'undefined') {
        const observer = new MutationObserver((mutations) => {
            let shouldReinit = false;
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.matches && (
                            node.matches('[data-inmovilla-filters]') ||
                            node.querySelector && node.querySelector('[data-inmovilla-filters]')
                        )) {
                            shouldReinit = true;
                        }
                    });
                }
            });
            if (shouldReinit) {
                initFilterDisplay();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Exponer en API global
    window.InmnovillaFilterDisplay = {
        render: renderActiveFilters,
        init: initFilterDisplay
    };
})();
