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
add_action('admin_menu', 'inmovilla_register_settings_menu');
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
        'filter_clear_text' => 'Limpiar'
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

            <?php if ($current_tab !== 'preview'): ?>
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
