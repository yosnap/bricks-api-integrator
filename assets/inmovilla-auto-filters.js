/**
 * Filtros automáticos para Inmovilla
 * Actualiza la URL automáticamente cuando cambia un filtro (con debounce)
 * Funciona tanto con formularios como con inputs/selects individuales
 *
 * @version 2.0
 */

(function() {
    'use strict';

    // Configuración
    const DEBOUNCE_DELAY = 500; // ms
    let debounceTimer = null;
    const activeFilters = new Map(); // Rastrear filtros activos

    /**
     * Inicializar filtros automáticos
     */
    function initAutoFilters() {
        // Primero, restaurar valores desde URL
        restoreFilterValuesFromUrl();

        // Opción 1: Formularios con la clase inmovilla-filters-form
        const filterForms = document.querySelectorAll('.inmovilla-filters-form');
        if (filterForms.length > 0) {
            filterForms.forEach(attachFormListeners);
        }

        // Opción 2: Selects/inputs individuales con clase inmovilla-filter-select o inmovilla-filter-input
        const filterInputs = document.querySelectorAll(
            '.inmovilla-filter-select, .inmovilla-filter-input, input[name^="key_"], select[name^="key_"]'
        );
        if (filterInputs.length > 0) {
            filterInputs.forEach(attachInputListener);
        }
    }

    /**
     * Restaurar valores de filtros desde parámetros de URL
     */
    function restoreFilterValuesFromUrl() {
        // Obtener parámetros de URL
        const params = new URLSearchParams(window.location.search);

        // Iterar sobre todos los parámetros
        for (const [key, value] of params) {
            // Buscar inputs/selects con este nombre
            const inputs = document.querySelectorAll(`input[name="${key}"], select[name="${key}"]`);

            inputs.forEach(input => {
                // Establecer el valor
                input.value = value;

                // Actualizar el mapa de filtros activos
                activeFilters.set(key, value);

                // Disparar evento change para actualizar la UI
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            });
        }
    }

    /**
     * Adjuntar listeners a formulario
     */
    function attachFormListeners(form) {
        const inputs = form.querySelectorAll('input[type="text"], input[type="number"], input[type="range"], select');
        inputs.forEach((input) => {
            input.addEventListener('change', () => handleFilterChange(form));
            if (input.type === 'text') {
                input.addEventListener('input', () => handleFilterChange(form));
            }
            if (input.type === 'range') {
                input.addEventListener('input', () => handleFilterChange(form));
            }
        });
    }

    /**
     * Adjuntar listeners a inputs individuales
     */
    function attachInputListener(input) {
        input.addEventListener('change', () => {
            handleIndividualFilterChange(input);
        });

        if (input.tagName === 'INPUT' && input.type === 'text') {
            input.addEventListener('input', () => {
                handleIndividualFilterChange(input);
            });
        }

        if (input.type === 'range') {
            input.addEventListener('input', () => {
                handleIndividualFilterChange(input);
            });
        }
    }

    /**
     * Manejar cambio de filtro individual
     */
    function handleIndividualFilterChange(input) {
        // Actualizar mapa de filtros activos
        const filterName = input.name || input.id;
        const filterValue = input.value;

        if (filterValue && filterValue.trim() !== '') {
            activeFilters.set(filterName, filterValue);
        } else {
            activeFilters.delete(filterName);
        }

        // Ejecutar con debounce
        submitWithDebounce();
    }

    /**
     * Manejar cambio en formulario
     */
    function handleFilterChange(form) {
        submitFormWithDebounce(form);
    }

    /**
     * Enviar con debounce (para inputs individuales)
     */
    function submitWithDebounce() {
        // Cancelar el timer anterior si existe
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        // Mostrar indicador de carga en los filtros
        showLoadingIndicator();

        // Esperar el debounce_delay antes de enviar
        debounceTimer = setTimeout(() => {
            submitFilters();
        }, DEBOUNCE_DELAY);
    }

    /**
     * Enviar con debounce (para formularios)
     */
    function submitFormWithDebounce(form) {
        // Cancelar el timer anterior si existe
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        // Mostrar indicador de carga
        showLoadingIndicator(form);

        // Esperar el debounce_delay antes de enviar
        debounceTimer = setTimeout(() => {
            submitForm(form);
        }, DEBOUNCE_DELAY);
    }

    /**
     * Enviar filtros individuales
     */
    function submitFilters() {
        try {
            // Construir URL con parámetros
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            // Agregar filtros activos
            for (const [key, value] of activeFilters) {
                if (value && value.trim() !== '') {
                    params.append(key, value);
                }
            }

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            // Recargar la página con los nuevos parámetros
            // Esto es necesario para que Bricks recargue los queries correctamente
            window.location.href = newUrl;

        } catch (error) {
            console.error('Error en filtros automáticos:', error);
            hideLoadingIndicator();
        }
    }

    /**
     * Enviar formulario
     */
    function submitForm(form) {
        try {
            // Crear FormData del formulario
            const formData = new FormData(form);

            // Construir URL con parámetros
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams(formData);

            // Filtrar parámetros vacíos
            const filteredParams = new URLSearchParams();
            for (const [key, value] of params) {
                if (value && value.trim() !== '') {
                    filteredParams.append(key, value);
                }
            }

            const newUrl = baseUrl + (filteredParams.toString() ? '?' + filteredParams.toString() : '');

            // Cambiar URL sin recargar
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Disparar evento
            document.dispatchEvent(new CustomEvent('inmovilla-filters-changed', {
                detail: { url: newUrl, filters: Object.fromEntries(formData) }
            }));

            // Trigger para Bricks
            triggerBricksReload();

        } catch (error) {
            console.error('Error en filtros automáticos:', error);
            hideLoadingIndicator(form);
        }
    }

    /**
     * Mostrar indicador de carga
     */
    function showLoadingIndicator(form = null) {
        if (form) {
            const wrapper = form.querySelector('.inmovilla-filters-wrapper');
            if (wrapper) {
                wrapper.classList.add('inmovilla-loading');
                wrapper.style.opacity = '0.6';
            }
        } else {
            // Para inputs individuales
            const filterGroups = document.querySelectorAll('.inmovilla-filter-group');
            filterGroups.forEach(group => {
                group.classList.add('inmovilla-loading');
                group.style.opacity = '0.6';
            });
        }
    }

    /**
     * Ocultar indicador de carga
     */
    function hideLoadingIndicator(form = null) {
        if (form) {
            const wrapper = form.querySelector('.inmovilla-filters-wrapper');
            if (wrapper) {
                wrapper.classList.remove('inmovilla-loading');
                wrapper.style.opacity = '1';
            }
        } else {
            // Para inputs individuales
            const filterGroups = document.querySelectorAll('.inmovilla-filter-group');
            filterGroups.forEach(group => {
                group.classList.remove('inmovilla-loading');
                group.style.opacity = '1';
            });
        }
    }

    /**
     * Trigger para que Bricks recargue las queries
     */
    function triggerBricksReload() {
        // Opción 1: Evento personalizado
        window.dispatchEvent(new CustomEvent('inmovilla-reload-queries'));

        // Opción 2: jQuery events para Bricks
        if (typeof window.jQuery !== 'undefined') {
            window.jQuery(document).trigger('bricks/query/reload');
            window.jQuery(window).trigger('bricks/query/changed');
        }

        // Opción 3: Events estándar
        setTimeout(() => {
            window.dispatchEvent(new CustomEvent('bricks/query/changed'));
        }, 100);
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoFilters);
    } else {
        initAutoFilters();
    }

    // Observer para contenido dinámico
    if (typeof window.MutationObserver !== 'undefined') {
        const observer = new MutationObserver((mutations) => {
            let shouldReinit = false;
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            if (node.classList && (
                                node.classList.contains('inmovilla-filters-form') ||
                                node.classList.contains('inmovilla-filter-select') ||
                                node.classList.contains('inmovilla-filter-input')
                            )) {
                                shouldReinit = true;
                            }
                            if (node.querySelector && (
                                node.querySelector('.inmovilla-filters-form') ||
                                node.querySelector('.inmovilla-filter-select') ||
                                node.querySelector('.inmovilla-filter-input')
                            )) {
                                shouldReinit = true;
                            }
                        }
                    });
                }
            });
            if (shouldReinit) {
                initAutoFilters();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();
