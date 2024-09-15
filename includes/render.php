<?php

// Función para renderizar los datos de un objeto o array
function render_api_item_table($item, $parent_key = '$payload')
{
    foreach ($item as $key => $value) {
        // Generar la clave completa
        $variable_key = $parent_key . "['$key']";

        // Generar la variable PHP que puedes usar para acceder a cualquier ítem dinámicamente
        $php_variable = $variable_key;

        if (is_array($value) || is_object($value)) {
            render_api_item_table((array) $value, $variable_key); // Convertir objeto en array si es necesario
        } else {
            echo '<tr>';
            echo '<td><code>' . esc_html($variable_key) . '</code></td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '<td><code>' . esc_html($php_variable) . '</code></td>';
            echo '</tr>';
        }
    }
}
