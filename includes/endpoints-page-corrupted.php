        function executeAdvancedTest(index) {
            const $result = $('#test-result-' + index);
            const $button = $(`.execute-test[data-index="${index}"]`);
            const finalUrl = $('#preview-url-' + index).text();
            
            $button.prop('disabled', true).text('🔄 Ejecutando...');
            $result.html('<p style="color: orange;">🔬 Ejecutando test avanzado...</p>');
            
            $.post(ajaxurl, {
                action: 'test_advanced_api_endpoint',
                index: index,
                url: finalUrl,
                nonce: '<?php echo wp_create_nonce('test_advanced_api_endpoint'); ?>'
            }, function(response) {
                if (response.success) {
                    let html = `<div style="color: green;">
                        <p><strong>✅ Test Avanzado Exitoso</strong></p>
                        <p><strong>URL:</strong> <code>${finalUrl}</code></p>
                        <p><strong>Elementos:</strong> ${response.data.count}</p>
                    `;
                    
                    if (response.data.sample_fields && response.data.sample_fields.length > 0) {
                        html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
                    }
                    
                    html += '</div>';
                    $result.html(html);
                } else {
                    $result.html(`<div style="color: red;">
                        <p><strong>❌ Error en Test Avanzado</strong></p>
                        <p><strong>URL:</strong> <code>${finalUrl}</code></p>
                        <p><strong>Error:</strong> ${response.data.message}</p>
                    </div>`);
                }
            }).always(function() {
                $button.prop('disabled', false).text('🚀 Ejecutar Test');
            });
        }
        </script>
        
        <style>
        .endpoint-card {
            transition: all 0.3s ease;
        }
        .endpoint-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .toggle-icon {
            float: right;
            transition: transform 0.3s ease;
        }
        .advanced-test-panel {
            border-left: 4px solid #0073aa;
        }
        .remove-endpoint:hover {
            color: #dc3232 !important;
            background-color: #f8d7da;
        }
        </style>
        <?php

/**
 * Función para renderizar los datos de un objeto o array en tabla
 */
if (!function_exists('render_api_item_table')) {
    function render_api_item_table($item, $parent_key = '$payload') {
        if (!is_array($item) && !is_object($item)) {
            return;
        }
        
        foreach ((array)$item as $key => $value) {
            $variable_key = $parent_key . "['$key']";
            $php_variable = $variable_key;

            if (is_array($value)) {
                // Si el array es simple (strings/números), concatenar valores
                if (array_is_list($value) && all_items_are_strings_or_numbers($value)) {
                    $concatenated_values = implode(', ', array_slice($value, 0, 5)); // Limitar a 5 elementos
                    echo '<tr>';
                    echo '<td><code>' . esc_html($key) . '</code></td>';
                    echo '<td>' . esc_html($concatenated_values) . '</td>';
                    echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                    echo '</tr>';
                } else {
                    // Si es array complejo, mostrar como JSON
                    echo '<tr>';
                    echo '<td><code>' . esc_html($key) . '</code></td>';
                    echo '<td><pre style="max-height: 100px; overflow: auto; font-size: 11px;">' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre></td>';
                    echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                    echo '</tr>';
                }
            } elseif (is_object($value)) {
                // Objeto como JSON
                echo '<tr>';
                echo '<td><code>' . esc_html($key) . '</code></td>';
                echo '<td><pre style="max-height: 100px; overflow: auto; font-size: 11px;">' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre></td>';
                echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                echo '</tr>';
            } else {
                // Valores simples
                echo '<tr>';
                echo '<td><code>' . esc_html($key) . '</code></td>';
                echo '<td>' . esc_html(is_bool($value) ? ($value ? 'true' : 'false') : $value) . '</td>';
                echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                echo '</tr>';
            }
        }
    }
}

/**
 * Helper function para verificar si todos los elementos del array son strings o números
 */
if (!function_exists('all_items_are_strings_or_numbers')) {
    function all_items_are_strings_or_numbers($array) {
        foreach ($array as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                return false;
            }
        }
        return true;
    }
}
