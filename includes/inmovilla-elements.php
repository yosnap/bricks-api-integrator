<?php
/**
 * Elementos UI para Inmovilla (Paginación, Filtros, Ordenamiento)
 *
 * Shortcodes para usar en Bricks que funcionan con parámetros URL
 * Incluye 4 templates de estilos: classic, modern, minimal, custom
 *
 * @package Bricks_API_Integrator
 * @version 0.3-beta
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: Paginación de Inmovilla
 * Uso: [inmovilla_pagination template="modern"]
 */
add_shortcode('inmovilla_pagination', function($atts) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'show_info' => 'true',
        'prev_text' => 'Anterior',
        'next_text' => 'Siguiente',
        'max_links' => 5,
    ], $atts);

    if (!class_exists('Inmovilla_Query_State')) {
        return '<!-- Inmovilla Query State no disponible -->';
    }

    $total = Inmovilla_Query_State::get_total();
    $max_pages = Inmovilla_Query_State::get_max_pages();
    $current_page = Inmovilla_Query_State::get_current_page();
    $per_page = Inmovilla_Query_State::get_per_page();

    if ($max_pages <= 1) {
        return '';
    }

    $current_url = remove_query_arg('paged');
    $template = esc_attr($atts['template']);

    $output = '<nav class="inmovilla-pagination inmovilla-template-' . $template . '" aria-label="Paginación">';

    // Info de página
    if ($atts['show_info'] === 'true') {
        $from = (($current_page - 1) * $per_page) + 1;
        $to = min($current_page * $per_page, $total);
        $output .= '<div class="inmovilla-pagination-info">';
        $output .= sprintf('Mostrando %d-%d de %d resultados', $from, $to, $total);
        $output .= '</div>';
    }

    $output .= '<ul class="inmovilla-pagination-links">';

    // Botón Anterior
    if ($current_page > 1) {
        $prev_url = add_query_arg('paged', $current_page - 1, $current_url);
        $output .= '<li class="inmovilla-page-item inmovilla-prev">';
        $output .= '<a href="' . esc_url($prev_url) . '" class="inmovilla-page-link">';
        $output .= '<span class="inmovilla-icon">&#8249;</span>';
        $output .= '<span class="inmovilla-text">' . esc_html($atts['prev_text']) . '</span>';
        $output .= '</a></li>';
    }

    // Enlaces de páginas
    $max_links = intval($atts['max_links']);
    $half = floor($max_links / 2);
    $start = max(1, $current_page - $half);
    $end = min($max_pages, $start + $max_links - 1);

    if ($end - $start < $max_links - 1) {
        $start = max(1, $end - $max_links + 1);
    }

    if ($start > 1) {
        $output .= '<li class="inmovilla-page-item">';
        $output .= '<a href="' . esc_url(add_query_arg('paged', 1, $current_url)) . '" class="inmovilla-page-link">1</a>';
        $output .= '</li>';
        if ($start > 2) {
            $output .= '<li class="inmovilla-page-item inmovilla-ellipsis"><span>...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $is_current = ($i === $current_page);
        $output .= '<li class="inmovilla-page-item' . ($is_current ? ' inmovilla-current' : '') . '">';
        if ($is_current) {
            $output .= '<span class="inmovilla-page-link">' . $i . '</span>';
        } else {
            $output .= '<a href="' . esc_url(add_query_arg('paged', $i, $current_url)) . '" class="inmovilla-page-link">' . $i . '</a>';
        }
        $output .= '</li>';
    }

    if ($end < $max_pages) {
        if ($end < $max_pages - 1) {
            $output .= '<li class="inmovilla-page-item inmovilla-ellipsis"><span>...</span></li>';
        }
        $output .= '<li class="inmovilla-page-item">';
        $output .= '<a href="' . esc_url(add_query_arg('paged', $max_pages, $current_url)) . '" class="inmovilla-page-link">' . $max_pages . '</a>';
        $output .= '</li>';
    }

    // Botón Siguiente
    if ($current_page < $max_pages) {
        $next_url = add_query_arg('paged', $current_page + 1, $current_url);
        $output .= '<li class="inmovilla-page-item inmovilla-next">';
        $output .= '<a href="' . esc_url($next_url) . '" class="inmovilla-page-link">';
        $output .= '<span class="inmovilla-text">' . esc_html($atts['next_text']) . '</span>';
        $output .= '<span class="inmovilla-icon">&#8250;</span>';
        $output .= '</a></li>';
    }

    $output .= '</ul></nav>';

    return $output;
});

/**
 * Shortcode: Resumen de resultados
 * Uso: [inmovilla_results_summary template="modern"]
 */
add_shortcode('inmovilla_results_summary', function($atts) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'format' => 'Mostrando {from}-{to} de {total} inmuebles',
        'empty_text' => 'No se encontraron resultados',
    ], $atts);

    if (!class_exists('Inmovilla_Query_State')) {
        return '';
    }

    $total = Inmovilla_Query_State::get_total();
    $current_page = Inmovilla_Query_State::get_current_page();
    $per_page = Inmovilla_Query_State::get_per_page();
    $template = esc_attr($atts['template']);

    if ($total === 0) {
        return '<div class="inmovilla-results-summary inmovilla-template-' . $template . ' inmovilla-no-results">' . esc_html($atts['empty_text']) . '</div>';
    }

    $from = (($current_page - 1) * $per_page) + 1;
    $to = min($current_page * $per_page, $total);

    $text = str_replace(
        ['{from}', '{to}', '{total}', '{page}', '{pages}'],
        [$from, $to, number_format($total, 0, ',', '.'), $current_page, Inmovilla_Query_State::get_max_pages()],
        $atts['format']
    );

    return '<div class="inmovilla-results-summary inmovilla-template-' . $template . '">' . esc_html($text) . '</div>';
});

/**
 * Shortcode: Selector de ordenamiento
 * Uso: [inmovilla_order_select template="modern" label="Ordenar por:"]
 */
add_shortcode('inmovilla_order_select', function($atts) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'label' => 'Ordenar por:',
        'default' => '',
        'show_label' => 'true',
    ], $atts);

    $order_fields = inmovilla_get_order_fields();
    $current_order = $_GET['orderby'] ?? $atts['default'];
    $template = esc_attr($atts['template']);

    $output = '<div class="inmovilla-order-select inmovilla-template-' . $template . '">';

    if ($atts['show_label'] === 'true' && !empty($atts['label'])) {
        $output .= '<label for="inmovilla-orderby">' . esc_html($atts['label']) . '</label>';
    }

    $output .= '<select id="inmovilla-orderby" name="orderby" onchange="inmovilla_apply_filter(this)">';
    $output .= '<option value="">-- Seleccionar --</option>';

    foreach ($order_fields as $key => $config) {
        $selected = ($current_order === $key) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($config['label']) . '</option>';
    }

    $output .= '</select></div>';

    return $output;
});

/**
 * Shortcode: Filtro select
 * Uso: [inmovilla_filter_select field="keyacci" template="modern"]
 */
add_shortcode('inmovilla_filter_select', function($atts) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'field' => '',
        'label' => '',
        'placeholder' => 'Todos',
        'source' => '',
        'show_label' => 'true',
    ], $atts);

    if (empty($atts['field'])) {
        return '<!-- Campo de filtro no especificado -->';
    }

    $filter_fields = inmovilla_get_filter_fields();
    $field_config = $filter_fields[$atts['field']] ?? null;
    $current_value = $_GET[$atts['field']] ?? '';
    $label = !empty($atts['label']) ? $atts['label'] : ($field_config['label'] ?? ucfirst($atts['field']));
    $template = esc_attr($atts['template']);

    $output = '<div class="inmovilla-filter-select inmovilla-template-' . $template . '">';

    if ($atts['show_label'] === 'true' && !empty($label)) {
        $output .= '<label for="inmovilla-filter-' . esc_attr($atts['field']) . '">' . esc_html($label) . '</label>';
    }

    $output .= '<select id="inmovilla-filter-' . esc_attr($atts['field']) . '" name="' . esc_attr($atts['field']) . '" onchange="inmovilla_apply_filter(this)">';
    $output .= '<option value="">' . esc_html($atts['placeholder']) . '</option>';

    $options = [];
    if (!empty($field_config['options'])) {
        $options = $field_config['options'];
    } elseif (!empty($atts['source']) || !empty($field_config['source_type'])) {
        $source_type = !empty($atts['source']) ? $atts['source'] : $field_config['source_type'];
        $options = inmovilla_get_filter_options($source_type);
    }

    foreach ($options as $value => $text) {
        $selected = ((string)$current_value === (string)$value) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($text) . '</option>';
    }

    $output .= '</select></div>';

    return $output;
});

/**
 * Shortcode: Filtro de rango numérico
 * Uso: [inmovilla_filter_range field="precio" template="modern"]
 */
add_shortcode('inmovilla_filter_range', function($atts) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'field' => '',
        'label' => '',
        'min' => '0',
        'max' => '1000000',
        'step' => '1000',
        'from_placeholder' => 'Desde',
        'to_placeholder' => 'Hasta',
        'show_label' => 'true',
    ], $atts);

    if (empty($atts['field'])) {
        return '<!-- Campo de filtro no especificado -->';
    }

    $field_from = $atts['field'] . '_desde';
    $field_to = $atts['field'] . '_hasta';
    $current_from = $_GET[$field_from] ?? '';
    $current_to = $_GET[$field_to] ?? '';
    $template = esc_attr($atts['template']);

    $output = '<div class="inmovilla-filter-range inmovilla-template-' . $template . '">';

    if ($atts['show_label'] === 'true' && !empty($atts['label'])) {
        $output .= '<label>' . esc_html($atts['label']) . '</label>';
    }

    $output .= '<div class="inmovilla-range-inputs">';
    $output .= '<input type="number" name="' . esc_attr($field_from) . '" value="' . esc_attr($current_from) . '" placeholder="' . esc_attr($atts['from_placeholder']) . '" min="' . esc_attr($atts['min']) . '" max="' . esc_attr($atts['max']) . '" step="' . esc_attr($atts['step']) . '" onchange="inmovilla_apply_filter(this)">';
    $output .= '<span class="inmovilla-range-separator">-</span>';
    $output .= '<input type="number" name="' . esc_attr($field_to) . '" value="' . esc_attr($current_to) . '" placeholder="' . esc_attr($atts['to_placeholder']) . '" min="' . esc_attr($atts['min']) . '" max="' . esc_attr($atts['max']) . '" step="' . esc_attr($atts['step']) . '" onchange="inmovilla_apply_filter(this)">';
    $output .= '</div></div>';

    return $output;
});

/**
 * Shortcode: Contenedor de filtros
 * Uso: [inmovilla_filters_form template="modern"]...[/inmovilla_filters_form]
 */
add_shortcode('inmovilla_filters_form', function($atts, $content = null) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'layout' => 'horizontal', // horizontal, vertical, grid
        'submit_text' => 'Buscar',
        'show_submit' => 'true',
        'show_clear' => 'true',
        'clear_text' => 'Limpiar',
    ], $atts);

    $template = esc_attr($atts['template']);
    $layout = esc_attr($atts['layout']);

    $output = '<form class="inmovilla-filters-form inmovilla-template-' . $template . ' inmovilla-layout-' . $layout . '" method="get">';
    $output .= '<div class="inmovilla-filters-wrapper">';
    $output .= do_shortcode($content);
    $output .= '</div>';

    if ($atts['show_submit'] === 'true' || $atts['show_clear'] === 'true') {
        $output .= '<div class="inmovilla-filters-actions">';
        if ($atts['show_submit'] === 'true') {
            $output .= '<button type="submit" class="inmovilla-btn inmovilla-btn-primary">' . esc_html($atts['submit_text']) . '</button>';
        }
        if ($atts['show_clear'] === 'true') {
            $base_url = strtok($_SERVER['REQUEST_URI'], '?');
            $output .= '<a href="' . esc_url($base_url) . '" class="inmovilla-btn inmovilla-btn-secondary">' . esc_html($atts['clear_text']) . '</a>';
        }
        $output .= '</div>';
    }

    $output .= '</form>';

    return $output;
});

/**
 * Shortcode: Botón limpiar filtros
 */
add_shortcode('inmovilla_clear_filters', function($atts) {
    $atts = shortcode_atts([
        'template' => 'modern',
        'text' => 'Limpiar filtros',
    ], $atts);

    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
    $template = esc_attr($atts['template']);

    return '<a href="' . esc_url($base_url) . '" class="inmovilla-clear-filters inmovilla-template-' . $template . '">' . esc_html($atts['text']) . '</a>';
});

/**
 * Obtener opciones de filtro desde la API
 */
function inmovilla_get_filter_options($source_type) {
    $cache_key = 'inmovilla_filter_options_' . $source_type;
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $options = [];
    $sources = get_option('bricks_api_sources', []);
    $source_key = 'inmovilla_' . $source_type;

    if (!isset($sources[$source_key])) {
        return $options;
    }

    $handler = Inmovilla_Query_Handler::get_instance();
    $result = $handler->execute_query($sources[$source_key], ['per_page' => 1000]);

    if (empty($result['error']) && !empty($result['items'])) {
        foreach ($result['items'] as $item) {
            $item = (array) $item;
            switch ($source_type) {
                case 'tipos':
                    if (isset($item['cod_tipo']) && isset($item['tipo'])) {
                        $options[$item['cod_tipo']] = $item['tipo'];
                    }
                    break;
                case 'ciudades':
                    if (isset($item['cod_ciu']) && isset($item['ciudad'])) {
                        $options[$item['cod_ciu']] = $item['ciudad'];
                    }
                    break;
                case 'zonas':
                    if (isset($item['cod_zona']) && isset($item['zona'])) {
                        $options[$item['cod_zona']] = $item['zona'];
                    }
                    break;
            }
        }
    }

    set_transient($cache_key, $options, HOUR_IN_SECONDS);
    return $options;
}

/**
 * Registrar estilos CSS de los templates
 */
add_action('wp_head', function() {
    ?>
    <style id="inmovilla-elements-styles">
    /* ============================================
       VARIABLES CSS PERSONALIZABLES
       ============================================ */
    :root {
        --inmovilla-primary: #2563eb;
        --inmovilla-primary-hover: #1d4ed8;
        --inmovilla-secondary: #64748b;
        --inmovilla-border: #e2e8f0;
        --inmovilla-bg: #ffffff;
        --inmovilla-bg-hover: #f8fafc;
        --inmovilla-text: #334155;
        --inmovilla-text-light: #64748b;
        --inmovilla-radius: 8px;
        --inmovilla-shadow: 0 1px 3px rgba(0,0,0,0.1);
        --inmovilla-transition: all 0.2s ease;
    }

    /* ============================================
       ESTILOS BASE (Compartidos)
       ============================================ */
    .inmovilla-pagination,
    .inmovilla-results-summary,
    .inmovilla-filter-select,
    .inmovilla-order-select,
    .inmovilla-filter-range,
    .inmovilla-filters-form {
        font-family: inherit;
        box-sizing: border-box;
    }

    .inmovilla-pagination-links {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        list-style: none;
        padding: 0;
        margin: 0;
        align-items: center;
    }

    .inmovilla-page-item {
        display: inline-flex;
    }

    /* ============================================
       TEMPLATE: CLASSIC
       ============================================ */
    .inmovilla-template-classic {
        --t-primary: #333333;
        --t-border: #cccccc;
        --t-bg: #f5f5f5;
        --t-radius: 3px;
    }

    .inmovilla-template-classic.inmovilla-pagination {
        margin: 20px 0;
    }

    .inmovilla-template-classic .inmovilla-pagination-info {
        margin-bottom: 10px;
        color: #666;
        font-size: 13px;
    }

    .inmovilla-template-classic .inmovilla-page-link {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border: 1px solid var(--t-border);
        background: var(--t-bg);
        color: var(--t-primary);
        text-decoration: none;
        font-size: 13px;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-template-classic .inmovilla-page-link:hover {
        background: #e5e5e5;
    }

    .inmovilla-template-classic .inmovilla-current .inmovilla-page-link {
        background: var(--t-primary);
        border-color: var(--t-primary);
        color: #fff;
    }

    .inmovilla-template-classic .inmovilla-ellipsis span {
        padding: 6px 8px;
    }

    .inmovilla-template-classic.inmovilla-results-summary {
        color: #666;
        font-size: 13px;
        padding: 8px 0;
        border-bottom: 1px solid var(--t-border);
        margin-bottom: 15px;
    }

    .inmovilla-template-classic select,
    .inmovilla-template-classic input[type="number"] {
        padding: 8px 10px;
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius);
        font-size: 13px;
        background: #fff;
    }

    .inmovilla-template-classic label {
        display: block;
        margin-bottom: 4px;
        font-size: 13px;
        font-weight: 600;
        color: #333;
    }

    /* ============================================
       TEMPLATE: MODERN (Default)
       ============================================ */
    .inmovilla-template-modern {
        --t-primary: var(--inmovilla-primary);
        --t-radius: 8px;
    }

    .inmovilla-template-modern.inmovilla-pagination {
        margin: 24px 0;
    }

    .inmovilla-template-modern .inmovilla-pagination-info {
        margin-bottom: 12px;
        color: var(--inmovilla-text-light);
        font-size: 14px;
        font-weight: 500;
    }

    .inmovilla-template-modern .inmovilla-page-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border: 1px solid var(--inmovilla-border);
        border-radius: var(--t-radius);
        background: var(--inmovilla-bg);
        color: var(--inmovilla-text);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: var(--inmovilla-transition);
        box-shadow: var(--inmovilla-shadow);
    }

    .inmovilla-template-modern .inmovilla-page-link:hover {
        background: var(--inmovilla-bg-hover);
        border-color: var(--t-primary);
        color: var(--t-primary);
    }

    .inmovilla-template-modern .inmovilla-current .inmovilla-page-link {
        background: var(--t-primary);
        border-color: var(--t-primary);
        color: #fff;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
    }

    .inmovilla-template-modern .inmovilla-ellipsis span {
        padding: 10px 8px;
        color: var(--inmovilla-text-light);
    }

    .inmovilla-template-modern .inmovilla-icon {
        font-size: 18px;
        line-height: 1;
    }

    .inmovilla-template-modern.inmovilla-results-summary {
        color: var(--inmovilla-text);
        font-size: 15px;
        font-weight: 500;
        padding: 12px 16px;
        background: var(--inmovilla-bg-hover);
        border-radius: var(--t-radius);
        margin-bottom: 20px;
    }

    .inmovilla-template-modern select,
    .inmovilla-template-modern input[type="number"] {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--inmovilla-border);
        border-radius: var(--t-radius);
        font-size: 14px;
        background: var(--inmovilla-bg);
        transition: var(--inmovilla-transition);
        box-shadow: var(--inmovilla-shadow);
    }

    .inmovilla-template-modern select:focus,
    .inmovilla-template-modern input[type="number"]:focus {
        outline: none;
        border-color: var(--t-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .inmovilla-template-modern label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--inmovilla-text);
    }

    .inmovilla-template-modern .inmovilla-filter-select,
    .inmovilla-template-modern .inmovilla-order-select,
    .inmovilla-template-modern .inmovilla-filter-range {
        margin-bottom: 16px;
    }

    .inmovilla-template-modern .inmovilla-range-inputs {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .inmovilla-template-modern .inmovilla-range-inputs input {
        flex: 1;
    }

    .inmovilla-template-modern .inmovilla-range-separator {
        color: var(--inmovilla-text-light);
        font-weight: 500;
    }

    /* ============================================
       TEMPLATE: MINIMAL
       ============================================ */
    .inmovilla-template-minimal {
        --t-primary: #18181b;
    }

    .inmovilla-template-minimal.inmovilla-pagination {
        margin: 20px 0;
    }

    .inmovilla-template-minimal .inmovilla-pagination-info {
        margin-bottom: 8px;
        color: #71717a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .inmovilla-template-minimal .inmovilla-page-link {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border: none;
        background: transparent;
        color: #71717a;
        text-decoration: none;
        font-size: 14px;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-template-minimal .inmovilla-page-link:hover {
        color: var(--t-primary);
    }

    .inmovilla-template-minimal .inmovilla-current .inmovilla-page-link {
        color: var(--t-primary);
        font-weight: 700;
        border-bottom: 2px solid var(--t-primary);
        border-radius: 0;
    }

    .inmovilla-template-minimal .inmovilla-ellipsis span {
        padding: 8px 4px;
        color: #a1a1aa;
    }

    .inmovilla-template-minimal.inmovilla-results-summary {
        color: #71717a;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .inmovilla-template-minimal select,
    .inmovilla-template-minimal input[type="number"] {
        padding: 10px 12px;
        border: none;
        border-bottom: 1px solid #e4e4e7;
        border-radius: 0;
        font-size: 14px;
        background: transparent;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-template-minimal select:focus,
    .inmovilla-template-minimal input[type="number"]:focus {
        outline: none;
        border-color: var(--t-primary);
    }

    .inmovilla-template-minimal label {
        display: block;
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #71717a;
    }

    .inmovilla-template-minimal .inmovilla-range-inputs {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .inmovilla-template-minimal .inmovilla-range-separator {
        color: #a1a1aa;
    }

    /* ============================================
       TEMPLATE: CUSTOM (Base para personalizar)
       ============================================ */
    .inmovilla-template-custom {
        /* Usa las variables CSS de :root */
    }

    /* ============================================
       FILTROS FORM - Layouts
       ============================================ */
    .inmovilla-filters-form {
        margin-bottom: 24px;
    }

    .inmovilla-layout-horizontal .inmovilla-filters-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }

    .inmovilla-layout-horizontal .inmovilla-filter-select,
    .inmovilla-layout-horizontal .inmovilla-order-select,
    .inmovilla-layout-horizontal .inmovilla-filter-range {
        flex: 1;
        min-width: 180px;
        margin-bottom: 0;
    }

    .inmovilla-layout-vertical .inmovilla-filters-wrapper {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .inmovilla-layout-grid .inmovilla-filters-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .inmovilla-filters-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--inmovilla-border);
    }

    .inmovilla-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        border: none;
        border-radius: var(--inmovilla-radius);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-btn-primary {
        background: var(--inmovilla-primary);
        color: #fff;
    }

    .inmovilla-btn-primary:hover {
        background: var(--inmovilla-primary-hover);
    }

    .inmovilla-btn-secondary {
        background: var(--inmovilla-bg-hover);
        color: var(--inmovilla-text);
        border: 1px solid var(--inmovilla-border);
    }

    .inmovilla-btn-secondary:hover {
        background: #e2e8f0;
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 640px) {
        .inmovilla-pagination-links {
            justify-content: center;
        }

        .inmovilla-template-modern .inmovilla-page-link,
        .inmovilla-template-classic .inmovilla-page-link {
            padding: 8px 12px;
            font-size: 13px;
        }

        .inmovilla-template-modern .inmovilla-text,
        .inmovilla-template-classic .inmovilla-text {
            display: none;
        }

        .inmovilla-layout-horizontal .inmovilla-filters-wrapper {
            flex-direction: column;
        }

        .inmovilla-layout-horizontal .inmovilla-filter-select,
        .inmovilla-layout-horizontal .inmovilla-order-select {
            min-width: 100%;
        }

        .inmovilla-filters-actions {
            flex-direction: column;
        }

        .inmovilla-btn {
            width: 100%;
        }
    }
    </style>
    <?php
});

/**
 * Registrar JavaScript
 */
add_action('wp_footer', function() {
    ?>
    <script id="inmovilla-elements-scripts">
    function inmovilla_apply_filter(element) {
        var url = new URL(window.location.href);
        var name = element.name;
        var value = element.value;

        // Reset página al filtrar
        url.searchParams.delete('paged');

        if (value) {
            url.searchParams.set(name, value);
        } else {
            url.searchParams.delete(name);
        }

        window.location.href = url.toString();
    }

    // Submit form sin recargar con enter en inputs
    document.querySelectorAll('.inmovilla-filters-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var url = new URL(window.location.origin + window.location.pathname);

            form.querySelectorAll('select, input').forEach(function(input) {
                if (input.value && input.name) {
                    url.searchParams.set(input.name, input.value);
                }
            });

            window.location.href = url.toString();
        });
    });
    </script>
    <?php
});
