<?php
if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

// Registrar los dynamic tags cuando Bricks esté activo
add_filter('bricks/dynamic_tags_list', 'add_api_tags_to_builder');
function add_api_tags_to_builder($tags)
{
    // Obtener los endpoints configurados desde el panel del plugin
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }

    // Iterar sobre cada endpoint y crear un grupo de tags dinámicos basado en el nombre del endpoint
    foreach ($endpoints as $endpoint) {
        $group_name = isset($endpoint['name']) ? esc_html($endpoint['name']) : 'Grupo sin nombre';

        // Obtener los datos del API para este endpoint
        $endpoint_url = isset($endpoint['url']) ? esc_url($endpoint['url']) : null;
        if (!$endpoint_url) {
            continue; // Saltar si no hay URL
        }

        $data = get_api_data($endpoint_url);

        // Validar que los datos obtenidos sean un array antes de pasarlo a foreach
        if (is_array($data)) {
            // Obtener todas las variables PHP y registrar los tags dinámicos
            $tags = array_merge($tags, generate_dynamic_tags($data, $group_name));
        } else {
            // Si no es un array, registrar un tag de advertencia
            $tags[] = [
                'name'  => '{' . sanitize_title($group_name) . '_error}',
                'label' => 'Error al obtener datos',
                'group' => $group_name,
            ];
        }
    }

    return $tags;
}

// Función para generar tags dinámicos a partir de las claves de los datos
function generate_dynamic_tags($data, $group_name, $parent_key = '$payload', $depth = 0)
{
    $tags = [];

    foreach ($data as $key => $value) {
        // Generar la clave completa en formato PHP
        $variable_key = $parent_key . "['$key']";

        // Si el valor es un array o un objeto, recursivamente obtener los valores internos
        if (is_array($value) || is_object($value)) {
            foreach ($value as $subkey => $subvalue) {
                $sub_variable_key = $variable_key . "['$subkey']";

                // Generar el label basado en la clave
                $label = generate_label($key, $subkey);

                // Registrar el tag dinámico
                $tags[] = [
                    'name'  => '{' . sanitize_title($group_name) . '_' . $label . '}',
                    'label' => $label,
                    'group' => $group_name,
                ];

                // Si es un array, continuar la recursividad
                if (is_array($subvalue) || is_object($subvalue)) {
                    $tags = array_merge($tags, generate_dynamic_tags($subvalue, $group_name, $sub_variable_key, $depth + 1));
                }
            }
        } else {
            // Generar el label y registrar el tag si no es un array
            $label = sanitize_title($key);
            $tags[] = [
                'name'  => '{' . sanitize_title($group_name) . '_' . $label . '}',
                'label' => $label,
                'group' => $group_name,
            ];
        }
    }

    return $tags;
}

// Función para generar el label basado en el formato
function generate_label($key, $index)
{
    $label = sanitize_title($key);

    // Si el índice es numérico y mayor o igual a 1, se añade al final del label
    if (is_numeric($index) && intval($index) >= 1) {
        $label .= '_' . intval($index);
    }

    return $label;
}

// Función para obtener los datos de la API desde el endpoint
function get_api_data($endpoint_url)
{
    $response = wp_remote_get($endpoint_url);

    if (is_wp_error($response)) {
        return null; // Si hay error, retornar null
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Asegurarse de que la respuesta JSON es válida
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null; // Si hay error en la decodificación del JSON, retornar null
    }

    return $data;
}

// Renderizar los valores de las etiquetas dinámicas
add_filter('bricks/dynamic_data/render_tag', 'get_api_tag_value', 10, 3);
function get_api_tag_value($tag, $post, $context = 'text')
{
    // Obtener los endpoints configurados desde el panel del plugin
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }

    // Iterar sobre cada endpoint para verificar si el tag pertenece a un grupo
    foreach ($endpoints as $endpoint) {
        $group_name = isset($endpoint['name']) ? sanitize_title($endpoint['name']) : null;
        $endpoint_url = isset($endpoint['url']) ? esc_url($endpoint['url']) : null;

        if (!$group_name || !$endpoint_url) {
            continue; // Saltar si no hay nombre o URL
        }

        // Obtener los datos del API actual
        $data = get_api_data($endpoint_url);

        // Validar que los datos sean un array antes de buscar el valor del tag
        if (is_array($data)) {
            // Buscar y devolver el valor del tag dinámico correspondiente
            if (strpos($tag, $group_name . '_') === 0) {
                $tag_key = str_replace($group_name . '_', '', $tag);
                return get_nested_value($data, explode('_', $tag_key));
            }
        }
    }

    return $tag; // Devolver el tag original si no coincide
}

// Función para obtener el valor anidado basado en la estructura de la clave
function get_nested_value($data, $keys)
{
    foreach ($keys as $key) {
        if (isset($data[$key])) {
            $data = $data[$key];
        } else {
            return 'Dato no disponible';
        }
    }
    return is_array($data) ? json_encode($data) : esc_html($data);
}
