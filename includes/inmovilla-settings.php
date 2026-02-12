<?php
/**
 * Configuración de estilos UI para Inmovilla
 *
 * @package Bricks_API_Integrator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar submenú de configuración Inmovilla
 */
add_action('admin_menu', 'inmovilla_register_settings_menu', 20);
function inmovilla_register_settings_menu() {
    add_submenu_page(
        'bricks-api-integrator',
        __('Inmovilla UI', 'bricks-api-integrator'),
        __('Inmovilla UI', 'bricks-api-integrator'),
        'manage_options',
        'bricks-api-inmovilla-ui',
        'inmovilla_render_settings_page'
    );
}

/**
 * Registrar opciones
 */
add_action('admin_init', 'inmovilla_register_settings');
function inmovilla_register_settings() {
    register_setting('inmovilla_ui_settings', 'inmovilla_ui_options', [
        'sanitize_callback' => 'inmovilla_sanitize_options'
    ]);
}

/**
 * Sanitizar opciones
 */
function inmovilla_sanitize_options($input) {
    $sanitized = [];

    // Template por defecto
    $valid_templates = ['modern', 'classic', 'minimal', 'custom'];
    $sanitized['default_template'] = in_array($input['default_template'] ?? '', $valid_templates)
        ? $input['default_template']
        : 'modern';

    // Colores (sanitizar como hex)
    $color_fields = [
        'primary_color',
        'primary_hover_color',
        'secondary_color',
        'border_color',
        'bg_color',
        'bg_hover_color',
        'text_color',
        'text_light_color'
    ];

    foreach ($color_fields as $field) {
        $sanitized[$field] = sanitize_hex_color($input[$field] ?? '');
    }

    // Valores numéricos
    $sanitized['border_radius'] = absint($input['border_radius'] ?? 8);

    // CSS personalizado
    $sanitized['custom_css'] = wp_strip_all_tags($input['custom_css'] ?? '');

    // URL base para página de detalle
    $sanitized['detail_url_base'] = sanitize_title($input['detail_url_base'] ?? 'inmuebles');

    // Textos personalizados
    $sanitized['pagination_prev_text'] = sanitize_text_field($input['pagination_prev_text'] ?? 'Anterior');
    $sanitized['pagination_next_text'] = sanitize_text_field($input['pagination_next_text'] ?? 'Siguiente');
    $sanitized['results_format'] = sanitize_text_field($input['results_format'] ?? 'Mostrando {from}-{to} de {total} inmuebles');
    $sanitized['results_empty_text'] = sanitize_text_field($input['results_empty_text'] ?? 'No se encontraron resultados');
    $sanitized['filter_submit_text'] = sanitize_text_field($input['filter_submit_text'] ?? 'Buscar');
    $sanitized['filter_clear_text'] = sanitize_text_field($input['filter_clear_text'] ?? 'Limpiar');

    return $sanitized;
}

/**
 * Obtener opciones con valores por defecto
 */
function inmovilla_get_ui_options() {
    $defaults = [
        'default_template' => 'modern',
        'primary_color' => '#2563eb',
        'primary_hover_color' => '#1d4ed8',
        'secondary_color' => '#64748b',
        'border_color' => '#e2e8f0',
        'bg_color' => '#ffffff',
        'bg_hover_color' => '#f8fafc',
        'text_color' => '#334155',
        'text_light_color' => '#64748b',
        'border_radius' => 8,
        'custom_css' => '',
        'pagination_prev_text' => 'Anterior',
        'pagination_next_text' => 'Siguiente',
        'results_format' => 'Mostrando {from}-{to} de {total} inmuebles',
        'results_empty_text' => 'No se encontraron resultados',
        'filter_submit_text' => 'Buscar',
        'filter_clear_text' => 'Limpiar',
        'detail_url_base' => 'inmuebles',
    ];

    $options = get_option('inmovilla_ui_options', []);
    return wp_parse_args($options, $defaults);
}

/**
 * Renderizar página de configuración
 */
function inmovilla_render_settings_page() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        return;
    }

    // Obtener tab actual
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

    // Guardar opciones
    if (isset($_POST['inmovilla_ui_save']) && check_admin_referer('inmovilla_ui_settings_nonce', 'inmovilla_ui_nonce')) {
        $options = inmovilla_sanitize_options($_POST['inmovilla_ui']);
        update_option('inmovilla_ui_options', $options);
        echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
    }

    $options = inmovilla_get_ui_options();
    $base_url = admin_url('admin.php?page=bricks-api-inmovilla-ui');
    ?>
    <div class="wrap">
        <h1>Inmovilla UI - Configuración de Estilos</h1>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url($base_url . '&tab=general'); ?>"
               class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                General
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=colors'); ?>"
               class="nav-tab <?php echo $current_tab === 'colors' ? 'nav-tab-active' : ''; ?>">
                Colores
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=texts'); ?>"
               class="nav-tab <?php echo $current_tab === 'texts' ? 'nav-tab-active' : ''; ?>">
                Textos
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=css'); ?>"
               class="nav-tab <?php echo $current_tab === 'css' ? 'nav-tab-active' : ''; ?>">
                CSS Personalizado
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=shortcodes'); ?>"
               class="nav-tab <?php echo $current_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
                Shortcodes
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=preview'); ?>"
               class="nav-tab <?php echo $current_tab === 'preview' ? 'nav-tab-active' : ''; ?>">
                Vista Previa
            </a>
        </nav>

        <form method="post" action="">
            <?php wp_nonce_field('inmovilla_ui_settings_nonce', 'inmovilla_ui_nonce'); ?>

            <div class="tab-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">

                <?php if ($current_tab === 'general'): ?>
                <!-- Tab General -->
                <h2>Configuración General</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_template">Plantilla por defecto</label>
                        </th>
                        <td>
                            <select name="inmovilla_ui[default_template]" id="default_template">
                                <option value="modern" <?php selected($options['default_template'], 'modern'); ?>>
                                    Modern - Diseño moderno con sombras y bordes redondeados
                                </option>
                                <option value="classic" <?php selected($options['default_template'], 'classic'); ?>>
                                    Classic - Diseño tradicional con bordes y fondos sutiles
                                </option>
                                <option value="minimal" <?php selected($options['default_template'], 'minimal'); ?>>
                                    Minimal - Diseño minimalista sin bordes
                                </option>
                                <option value="custom" <?php selected($options['default_template'], 'custom'); ?>>
                                    Custom - Base para personalización con variables CSS
                                </option>
                            </select>
                            <p class="description">
                                Esta plantilla se aplicará a todos los shortcodes que no especifiquen una plantilla.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="border_radius">Border Radius (px)</label>
                        </th>
                        <td>
                            <input type="number"
                                   name="inmovilla_ui[border_radius]"
                                   id="border_radius"
                                   value="<?php echo esc_attr($options['border_radius']); ?>"
                                   min="0"
                                   max="50"
                                   class="small-text">
                            <p class="description">Radio de las esquinas en píxeles (0-50).</p>
                        </td>
                    </tr>
                </table>

                <?php elseif ($current_tab === 'colors'): ?>
                <!-- Tab Colores -->
                <h2>Personalización de Colores</h2>
                <p class="description">Estos colores se aplican cuando usas la plantilla "Custom" o cuando sobrescribes los estilos.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="primary_color">Color Principal</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[primary_color]"
                                   id="primary_color"
                                   value="<?php echo esc_attr($options['primary_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['primary_color']); ?>"
                                   class="color-hex-input"
                                   data-target="primary_color"
                                   style="width: 80px; margin-left: 10px;">
                            <span class="description">Botones, enlaces activos, elementos destacados</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="primary_hover_color">Color Principal (Hover)</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[primary_hover_color]"
                                   id="primary_hover_color"
                                   value="<?php echo esc_attr($options['primary_hover_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['primary_hover_color']); ?>"
                                   class="color-hex-input"
                                   data-target="primary_hover_color"
                                   style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="secondary_color">Color Secundario</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[secondary_color]"
                                   id="secondary_color"
                                   value="<?php echo esc_attr($options['secondary_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['secondary_color']); ?>"
                                   class="color-hex-input"
                                   data-target="secondary_color"
                                   style="width: 80px; margin-left: 10px;">
                            <span class="description">Textos secundarios, iconos</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="border_color">Color de Bordes</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[border_color]"
                                   id="border_color"
                                   value="<?php echo esc_attr($options['border_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['border_color']); ?>"
                                   class="color-hex-input"
                                   data-target="border_color"
                                   style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bg_color">Color de Fondo</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[bg_color]"
                                   id="bg_color"
                                   value="<?php echo esc_attr($options['bg_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['bg_color']); ?>"
                                   class="color-hex-input"
                                   data-target="bg_color"
                                   style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bg_hover_color">Color de Fondo (Hover)</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[bg_hover_color]"
                                   id="bg_hover_color"
                                   value="<?php echo esc_attr($options['bg_hover_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['bg_hover_color']); ?>"
                                   class="color-hex-input"
                                   data-target="bg_hover_color"
                                   style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="text_color">Color de Texto</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[text_color]"
                                   id="text_color"
                                   value="<?php echo esc_attr($options['text_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['text_color']); ?>"
                                   class="color-hex-input"
                                   data-target="text_color"
                                   style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="text_light_color">Color de Texto Secundario</label></th>
                        <td>
                            <input type="color"
                                   name="inmovilla_ui[text_light_color]"
                                   id="text_light_color"
                                   value="<?php echo esc_attr($options['text_light_color']); ?>">
                            <input type="text"
                                   value="<?php echo esc_attr($options['text_light_color']); ?>"
                                   class="color-hex-input"
                                   data-target="text_light_color"
                                   style="width: 80px; margin-left: 10px;">
                        </td>
                    </tr>
                </table>

                <script>
                jQuery(document).ready(function($) {
                    // Sincronizar color picker con input de texto
                    $('input[type="color"]').on('input', function() {
                        var id = $(this).attr('id');
                        $('input.color-hex-input[data-target="' + id + '"]').val($(this).val());
                    });

                    $('input.color-hex-input').on('input', function() {
                        var target = $(this).data('target');
                        var val = $(this).val();
                        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                            $('#' + target).val(val);
                        }
                    });
                });
                </script>

                <?php elseif ($current_tab === 'texts'): ?>
                <!-- Tab Textos -->
                <h2>Textos Personalizados</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pagination_prev_text">Texto "Anterior"</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[pagination_prev_text]"
                                   id="pagination_prev_text"
                                   value="<?php echo esc_attr($options['pagination_prev_text']); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pagination_next_text">Texto "Siguiente"</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[pagination_next_text]"
                                   id="pagination_next_text"
                                   value="<?php echo esc_attr($options['pagination_next_text']); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="results_format">Formato de Resultados</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[results_format]"
                                   id="results_format"
                                   value="<?php echo esc_attr($options['results_format']); ?>"
                                   class="large-text">
                            <p class="description">
                                Variables disponibles: <code>{from}</code>, <code>{to}</code>, <code>{total}</code>, <code>{page}</code>, <code>{pages}</code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="results_empty_text">Texto Sin Resultados</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[results_empty_text]"
                                   id="results_empty_text"
                                   value="<?php echo esc_attr($options['results_empty_text']); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="filter_submit_text">Texto Botón Buscar</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[filter_submit_text]"
                                   id="filter_submit_text"
                                   value="<?php echo esc_attr($options['filter_submit_text']); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="filter_clear_text">Texto Botón Limpiar</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[filter_clear_text]"
                                   id="filter_clear_text"
                                   value="<?php echo esc_attr($options['filter_clear_text']); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>

                <h2>Página de Detalle</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="detail_url_base">URL Base Detalle</label></th>
                        <td>
                            <input type="text"
                                   name="inmovilla_ui[detail_url_base]"
                                   id="detail_url_base"
                                   value="<?php echo esc_attr($options['detail_url_base']); ?>"
                                   class="regular-text">
                            <p class="description">
                                URL base para la página de detalle de inmuebles. Ejemplo: <code>inmuebles</code> genera URLs como <code>/inmuebles/REF123/</code><br>
                                Usa el tag <code>{snap_inmovilla-inmuebles_detail_url}</code> en Bricks para enlazar las cards al detalle.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php elseif ($current_tab === 'css'): ?>
                <!-- Tab CSS -->
                <h2>CSS Personalizado</h2>
                <p class="description">
                    Añade CSS personalizado para modificar los estilos de los elementos de Inmovilla.
                    Este CSS se cargará en todas las páginas donde se usen los shortcodes.
                </p>

                <div style="margin: 20px 0;">
                    <label for="custom_css"><strong>CSS Personalizado:</strong></label>
                    <textarea name="inmovilla_ui[custom_css]"
                              id="custom_css"
                              rows="20"
                              class="large-text code"
                              style="font-family: monospace;"><?php echo esc_textarea($options['custom_css']); ?></textarea>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
                    <h4 style="margin-top: 0;">Variables CSS disponibles:</h4>
                    <pre style="background: #fff; padding: 15px; overflow: auto; margin: 0;">:root {
    --inmovilla-primary: <?php echo esc_html($options['primary_color']); ?>;
    --inmovilla-primary-hover: <?php echo esc_html($options['primary_hover_color']); ?>;
    --inmovilla-secondary: <?php echo esc_html($options['secondary_color']); ?>;
    --inmovilla-border: <?php echo esc_html($options['border_color']); ?>;
    --inmovilla-bg: <?php echo esc_html($options['bg_color']); ?>;
    --inmovilla-bg-hover: <?php echo esc_html($options['bg_hover_color']); ?>;
    --inmovilla-text: <?php echo esc_html($options['text_color']); ?>;
    --inmovilla-text-light: <?php echo esc_html($options['text_light_color']); ?>;
    --inmovilla-radius: <?php echo esc_html($options['border_radius']); ?>px;
}</pre>
                </div>

                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                    <h4 style="margin-top: 0;">Clases CSS principales:</h4>
                    <ul style="margin-bottom: 0;">
                        <li><code>.inmovilla-pagination</code> - Contenedor de paginación</li>
                        <li><code>.inmovilla-results-summary</code> - Resumen de resultados</li>
                        <li><code>.inmovilla-order-select</code> - Selector de ordenamiento</li>
                        <li><code>.inmovilla-filter-select</code> - Filtro select</li>
                        <li><code>.inmovilla-filter-range</code> - Filtro de rango</li>
                        <li><code>.inmovilla-filters-form</code> - Formulario de filtros</li>
                        <li><code>.inmovilla-clear-filters</code> - Botón limpiar filtros</li>
                    </ul>
                </div>

                <?php elseif ($current_tab === 'shortcodes'): ?>
                <!-- Tab Shortcodes -->
                <?php inmovilla_render_shortcodes_tab(); ?>

                <?php elseif ($current_tab === 'preview'): ?>
                <!-- Tab Preview -->
                <h2>Vista Previa de Estilos</h2>
                <p class="description">Vista previa de cómo se verán los elementos con la configuración actual.</p>

                <?php
                // Cargar estilos de preview
                inmovilla_output_custom_styles();
                ?>

                <div style="background: #f5f5f5; padding: 30px; border-radius: 8px; margin-top: 20px;">

                    <h3>Paginación</h3>
                    <div class="inmovilla-pagination inmovilla-template-<?php echo esc_attr($options['default_template']); ?>" style="margin-bottom: 30px;">
                        <div class="inmovilla-pagination-info">Mostrando 1-20 de 150 inmuebles</div>
                        <div class="inmovilla-pagination-links">
                            <span class="inmovilla-page-link inmovilla-disabled"><?php echo esc_html($options['pagination_prev_text']); ?></span>
                            <a href="#" class="inmovilla-page-link inmovilla-current">1</a>
                            <a href="#" class="inmovilla-page-link">2</a>
                            <a href="#" class="inmovilla-page-link">3</a>
                            <span class="inmovilla-page-dots">...</span>
                            <a href="#" class="inmovilla-page-link">8</a>
                            <a href="#" class="inmovilla-page-link"><?php echo esc_html($options['pagination_next_text']); ?></a>
                        </div>
                    </div>

                    <h3>Resumen de Resultados</h3>
                    <div class="inmovilla-results-summary inmovilla-template-<?php echo esc_attr($options['default_template']); ?>" style="margin-bottom: 30px;">
                        <?php
                        $format = $options['results_format'];
                        $format = str_replace(
                            ['{from}', '{to}', '{total}', '{page}', '{pages}'],
                            ['1', '20', '150', '1', '8'],
                            $format
                        );
                        echo esc_html($format);
                        ?>
                    </div>

                    <h3>Selector de Ordenamiento</h3>
                    <div class="inmovilla-order-select inmovilla-template-<?php echo esc_attr($options['default_template']); ?>" style="margin-bottom: 30px;">
                        <label class="inmovilla-order-label">Ordenar por:</label>
                        <select class="inmovilla-order-dropdown">
                            <option>Por defecto</option>
                            <option>Precio: menor a mayor</option>
                            <option>Precio: mayor a menor</option>
                            <option>Más recientes</option>
                        </select>
                    </div>

                    <h3>Filtros</h3>
                    <div class="inmovilla-filters-form inmovilla-template-<?php echo esc_attr($options['default_template']); ?> inmovilla-layout-horizontal">
                        <div class="inmovilla-filters-wrapper">
                            <div class="inmovilla-filter-select">
                                <label class="inmovilla-filter-label">Operación</label>
                                <select class="inmovilla-filter-dropdown">
                                    <option>Todos</option>
                                    <option>Venta</option>
                                    <option>Alquiler</option>
                                </select>
                            </div>
                            <div class="inmovilla-filter-select">
                                <label class="inmovilla-filter-label">Tipo</label>
                                <select class="inmovilla-filter-dropdown">
                                    <option>Todos los tipos</option>
                                    <option>Piso</option>
                                    <option>Casa</option>
                                    <option>Local</option>
                                </select>
                            </div>
                            <div class="inmovilla-filter-range">
                                <label class="inmovilla-filter-label">Precio</label>
                                <div class="inmovilla-range-inputs">
                                    <input type="number" placeholder="Desde" class="inmovilla-range-input">
                                    <span class="inmovilla-range-separator">-</span>
                                    <input type="number" placeholder="Hasta" class="inmovilla-range-input">
                                </div>
                            </div>
                        </div>
                        <div class="inmovilla-filters-actions">
                            <button type="button" class="inmovilla-filter-submit"><?php echo esc_html($options['filter_submit_text']); ?></button>
                            <a href="#" class="inmovilla-filter-clear"><?php echo esc_html($options['filter_clear_text']); ?></a>
                        </div>
                    </div>

                </div>

                <?php endif; ?>

            </div>

            <?php if (!in_array($current_tab, ['preview', 'shortcodes'])): ?>
            <p class="submit">
                <input type="submit" name="inmovilla_ui_save" class="button button-primary" value="Guardar Cambios">
                <a href="<?php echo esc_url($base_url . '&tab=preview'); ?>" class="button">Ver Vista Previa</a>
            </p>
            <?php endif; ?>

        </form>
    </div>
    <?php
}

/**
 * Renderizar pestaña de Shortcodes con referencia y botón copiar
 */
function inmovilla_render_shortcodes_tab() {
    $shortcodes = inmovilla_get_shortcodes_reference();
    ?>
    <style>
    .inmovilla-sc-ref { margin-top: 10px; }
    .inmovilla-sc-card {
        background: #fff; border: 1px solid #ddd; border-radius: 6px;
        padding: 20px; margin-bottom: 20px; position: relative;
    }
    .inmovilla-sc-card h3 { margin: 0 0 8px; font-size: 16px; color: #1d2327; }
    .inmovilla-sc-card .description { margin-bottom: 12px; color: #646970; }
    .inmovilla-sc-code {
        background: #f0f0f1; border: 1px solid #ddd; border-radius: 4px;
        padding: 10px 14px; font-family: monospace; font-size: 13px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 10px; word-break: break-all;
    }
    .inmovilla-sc-code code { flex: 1; color: #2271b1; }
    .inmovilla-sc-copy {
        background: #2271b1; color: #fff; border: none; border-radius: 3px;
        padding: 4px 12px; cursor: pointer; font-size: 12px; margin-left: 10px;
        white-space: nowrap;
    }
    .inmovilla-sc-copy:hover { background: #135e96; }
    .inmovilla-sc-copy.copied { background: #00a32a; }
    .inmovilla-sc-params { margin-top: 12px; }
    .inmovilla-sc-params table {
        width: 100%; border-collapse: collapse; font-size: 13px;
    }
    .inmovilla-sc-params th {
        text-align: left; padding: 6px 10px; background: #f6f7f7;
        border: 1px solid #ddd; font-weight: 600;
    }
    .inmovilla-sc-params td {
        padding: 6px 10px; border: 1px solid #ddd;
    }
    .inmovilla-sc-params code { background: #f0f0f1; padding: 2px 5px; border-radius: 3px; font-size: 12px; }
    .inmovilla-sc-example {
        background: #fef8ee; border: 1px solid #f0c36d; border-radius: 4px;
        padding: 10px 14px; margin-top: 10px; font-size: 13px;
    }
    .inmovilla-sc-example strong { display: block; margin-bottom: 4px; color: #826200; }
    .inmovilla-sc-fields {
        background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 6px;
        padding: 16px 20px; margin-bottom: 20px;
    }
    .inmovilla-sc-fields h3 { margin: 0 0 10px; }
    .inmovilla-sc-fields ul { margin: 0; columns: 2; }
    .inmovilla-sc-fields li { padding: 3px 0; font-size: 13px; }
    .inmovilla-sc-fields li code { background: #e7f0f9; padding: 2px 5px; border-radius: 3px; }
    </style>

    <h2>Referencia de Shortcodes</h2>
    <p class="description">Todos los shortcodes disponibles para Inmovilla. Haz clic en "Copiar" para copiar el shortcode al portapapeles.</p>

    <div class="inmovilla-sc-ref">

        <!-- Campos de filtro disponibles -->
        <div class="inmovilla-sc-fields">
            <h3>Campos de filtro disponibles (para <code>field=""</code>)</h3>
            <ul>
                <li><code>key_tipo</code> &mdash; Tipo de inmueble (select, desde API)</li>
                <li><code>key_loca</code> &mdash; Ciudad (select, desde API)</li>
                <li><code>key_zona</code> &mdash; Zona (select, requiere ciudad)</li>
                <li><code>keyacci</code> &mdash; Acción: Venta, Alquiler... (select)</li>
                <li><code>keyprov</code> &mdash; Provincia (texto)</li>
                <li><code>precio</code> &mdash; Precio (rango: precio_desde, precio_hasta)</li>
                <li><code>habitaciones</code> &mdash; Habitaciones mínimas (number)</li>
                <li><code>banyos</code> &mdash; Baños mínimos (number)</li>
                <li><code>metros</code> &mdash; Metros construidos (rango: metros_desde, metros_hasta)</li>
            </ul>
        </div>

        <?php foreach ($shortcodes as $sc): ?>
        <div class="inmovilla-sc-card">
            <h3><?php echo esc_html($sc['name']); ?></h3>
            <p class="description"><?php echo esc_html($sc['description']); ?></p>

            <div class="inmovilla-sc-code">
                <code><?php echo esc_html($sc['usage']); ?></code>
                <button type="button" class="inmovilla-sc-copy" data-code="<?php echo esc_attr($sc['usage']); ?>">Copiar</button>
            </div>

            <?php if (!empty($sc['params'])): ?>
            <div class="inmovilla-sc-params">
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Por defecto</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sc['params'] as $param): ?>
                        <tr>
                            <td><code><?php echo esc_html($param['name']); ?></code></td>
                            <td><code><?php echo esc_html($param['default']); ?></code></td>
                            <td><?php echo esc_html($param['desc']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($sc['examples'])): ?>
            <div class="inmovilla-sc-example">
                <strong>Ejemplos:</strong>
                <?php foreach ($sc['examples'] as $ex): ?>
                <div style="margin-bottom: 4px;">
                    <code><?php echo esc_html($ex); ?></code>
                    <button type="button" class="inmovilla-sc-copy" data-code="<?php echo esc_attr($ex); ?>" style="font-size:11px; padding:2px 8px;">Copiar</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div>

    <script>
    document.querySelectorAll('.inmovilla-sc-copy').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var code = this.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(function() {
                btn.textContent = 'Copiado!';
                btn.classList.add('copied');
                setTimeout(function() {
                    btn.textContent = 'Copiar';
                    btn.classList.remove('copied');
                }, 2000);
            });
        });
    });
    </script>
    <?php
}

/**
 * Datos de referencia de shortcodes
 */
function inmovilla_get_shortcodes_reference() {
    return [
        [
            'name' => 'Formulario de Filtros',
            'description' => 'Contenedor de filtros con botón de buscar. Envuelve otros shortcodes de filtro.',
            'usage' => '[inmovilla_filters_form][inmovilla_filter_select field="keyacci"][inmovilla_filter_select field="key_tipo"][inmovilla_filter_select field="key_loca"][inmovilla_filter_select field="key_zona"][/inmovilla_filters_form]',
            'params' => [
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla: modern, classic, minimal, custom'],
                ['name' => 'layout', 'default' => 'horizontal', 'desc' => 'Distribución: horizontal, vertical, grid'],
                ['name' => 'submit_text', 'default' => 'Buscar', 'desc' => 'Texto del botón de buscar'],
                ['name' => 'show_submit', 'default' => 'true', 'desc' => 'Mostrar botón de buscar'],
                ['name' => 'show_clear', 'default' => 'true', 'desc' => 'Mostrar botón de limpiar'],
                ['name' => 'clear_text', 'default' => 'Limpiar', 'desc' => 'Texto del botón de limpiar'],
            ],
            'examples' => [
                '[inmovilla_filters_form layout="grid"][inmovilla_filter_select field="keyacci"][inmovilla_filter_select field="key_tipo"][/inmovilla_filters_form]',
                '[inmovilla_filters_form template="classic" show_clear="false"]...[/inmovilla_filters_form]',
            ],
        ],
        [
            'name' => 'Filtro Select',
            'description' => 'Desplegable para filtrar. Si se usa fuera de un formulario, aplica el filtro inmediatamente.',
            'usage' => '[inmovilla_filter_select field="key_tipo"]',
            'params' => [
                ['name' => 'field', 'default' => '(requerido)', 'desc' => 'Campo de filtro: key_tipo, key_loca, key_zona, keyacci'],
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla: modern, classic, minimal, custom'],
                ['name' => 'label', 'default' => '(auto)', 'desc' => 'Etiqueta del filtro. Si se omite, usa la del campo'],
                ['name' => 'placeholder', 'default' => 'Todos', 'desc' => 'Texto de la opción vacía'],
                ['name' => 'show_label', 'default' => 'true', 'desc' => 'Mostrar etiqueta'],
            ],
            'examples' => [
                '[inmovilla_filter_select field="keyacci" label="Operación"]',
                '[inmovilla_filter_select field="key_tipo" placeholder="Selecciona tipo"]',
                '[inmovilla_filter_select field="key_loca"]',
                '[inmovilla_filter_select field="key_zona"]',
            ],
        ],
        [
            'name' => 'Filtro de Rango',
            'description' => 'Dos inputs numéricos (desde/hasta) para filtrar por rango de valores.',
            'usage' => '[inmovilla_filter_range field="precio"]',
            'params' => [
                ['name' => 'field', 'default' => '(requerido)', 'desc' => 'Campo base: precio, metros, habitaciones, banyos'],
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla visual'],
                ['name' => 'label', 'default' => '(auto)', 'desc' => 'Etiqueta del filtro'],
                ['name' => 'min', 'default' => '0', 'desc' => 'Valor mínimo'],
                ['name' => 'max', 'default' => '1000000', 'desc' => 'Valor máximo'],
                ['name' => 'step', 'default' => '1000', 'desc' => 'Incremento'],
                ['name' => 'from_placeholder', 'default' => 'Desde', 'desc' => 'Placeholder input desde'],
                ['name' => 'to_placeholder', 'default' => 'Hasta', 'desc' => 'Placeholder input hasta'],
            ],
            'examples' => [
                '[inmovilla_filter_range field="precio" min="50000" max="500000" step="10000"]',
                '[inmovilla_filter_range field="metros" label="Superficie (m2)" max="500" step="10"]',
            ],
        ],
        [
            'name' => 'Paginación',
            'description' => 'Controles de paginación con info de resultados y enlaces de página.',
            'usage' => '[inmovilla_pagination]',
            'params' => [
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla visual'],
                ['name' => 'show_info', 'default' => 'true', 'desc' => 'Mostrar "Mostrando X-Y de Z"'],
                ['name' => 'prev_text', 'default' => 'Anterior', 'desc' => 'Texto botón anterior'],
                ['name' => 'next_text', 'default' => 'Siguiente', 'desc' => 'Texto botón siguiente'],
                ['name' => 'max_links', 'default' => '5', 'desc' => 'Máximo de enlaces de página visibles'],
            ],
            'examples' => [
                '[inmovilla_pagination max_links="3" show_info="false"]',
                '[inmovilla_pagination template="classic" prev_text="Ant" next_text="Sig"]',
            ],
        ],
        [
            'name' => 'Resumen de Resultados',
            'description' => 'Muestra texto con el conteo de resultados: "Mostrando X-Y de Z inmuebles".',
            'usage' => '[inmovilla_results_summary]',
            'params' => [
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla visual'],
                ['name' => 'format', 'default' => 'Mostrando {from}-{to} de {total} inmuebles', 'desc' => 'Formato. Variables: {from}, {to}, {total}, {page}, {pages}'],
                ['name' => 'empty_text', 'default' => 'No se encontraron resultados', 'desc' => 'Texto cuando no hay resultados'],
            ],
            'examples' => [
                '[inmovilla_results_summary format="{total} propiedades encontradas"]',
                '[inmovilla_results_summary empty_text="Sin resultados para tu búsqueda"]',
            ],
        ],
        [
            'name' => 'Selector de Ordenamiento',
            'description' => 'Desplegable para ordenar los resultados por precio, fecha, etc.',
            'usage' => '[inmovilla_order_select]',
            'params' => [
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla visual'],
                ['name' => 'label', 'default' => 'Ordenar por:', 'desc' => 'Etiqueta del selector'],
                ['name' => 'show_label', 'default' => 'true', 'desc' => 'Mostrar etiqueta'],
            ],
            'examples' => [
                '[inmovilla_order_select label="Ordenar:" template="minimal"]',
            ],
        ],
        [
            'name' => 'Limpiar Filtros',
            'description' => 'Botón/enlace para eliminar todos los filtros activos de la URL.',
            'usage' => '[inmovilla_clear_filters]',
            'params' => [
                ['name' => 'template', 'default' => 'modern', 'desc' => 'Plantilla visual'],
                ['name' => 'text', 'default' => 'Limpiar', 'desc' => 'Texto del botón'],
            ],
            'examples' => [
                '[inmovilla_clear_filters text="Borrar filtros"]',
            ],
        ],
        [
            'name' => 'Filtros Activos',
            'description' => 'Muestra los filtros actualmente aplicados como etiquetas con opción de eliminar.',
            'usage' => '[inmovilla_active_filters]',
            'params' => [],
            'examples' => [],
        ],
    ];
}

/**
 * Generar y cargar estilos personalizados en el frontend
 */
add_action('wp_head', 'inmovilla_output_custom_styles', 100);
add_action('admin_head', 'inmovilla_output_custom_styles_admin', 100);

function inmovilla_output_custom_styles() {
    $options = inmovilla_get_ui_options();

    ?>
    <style id="inmovilla-custom-styles">
    :root {
        --inmovilla-primary: <?php echo esc_html($options['primary_color']); ?>;
        --inmovilla-primary-hover: <?php echo esc_html($options['primary_hover_color']); ?>;
        --inmovilla-secondary: <?php echo esc_html($options['secondary_color']); ?>;
        --inmovilla-border: <?php echo esc_html($options['border_color']); ?>;
        --inmovilla-bg: <?php echo esc_html($options['bg_color']); ?>;
        --inmovilla-bg-hover: <?php echo esc_html($options['bg_hover_color']); ?>;
        --inmovilla-text: <?php echo esc_html($options['text_color']); ?>;
        --inmovilla-text-light: <?php echo esc_html($options['text_light_color']); ?>;
        --inmovilla-radius: <?php echo esc_html($options['border_radius']); ?>px;
        --inmovilla-shadow: 0 1px 3px rgba(0,0,0,0.1);
        --inmovilla-transition: all 0.2s ease;
    }
    <?php
    if (!empty($options['custom_css'])) {
        echo "\n" . $options['custom_css'];
    }
    ?>
    </style>
    <?php
}

function inmovilla_output_custom_styles_admin() {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'bricks-api-inmovilla-ui') !== false) {
        inmovilla_output_custom_styles();
        // Cargar también los estilos base de los elementos
        if (function_exists('inmovilla_get_element_styles')) {
            echo '<style id="inmovilla-elements-styles">' . inmovilla_get_element_styles() . '</style>';
        }
    }
}
