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

        // Agregar los tags dinámicos relacionados con los datos del endpoint
        $tags[] = [
            'name'  => '{' . sanitize_title($group_name) . '_name}',
            'label' => 'Nombre del Endpoint',
            'group' => $group_name,
        ];
        $tags[] = [
            'name'  => '{' . sanitize_title($group_name) . '_description}',
            'label' => 'Descripción del Endpoint',
            'group' => $group_name,
        ];
        $tags[] = [
            'name'  => '{' . sanitize_title($group_name) . '_images}',
            'label' => 'Imágenes del Endpoint',
            'group' => $group_name,
        ];
        $tags[] = [
            'name'  => '{' . sanitize_title($group_name) . '_featured_image}',
            'label' => 'Imagen Destacada',
            'group' => $group_name,
        ];
        // Agregar otros tags según los datos que tu API ofrezca
    }

    return $tags;
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

        // Verificar el tag dinámico y retornar el valor correspondiente
        if ($tag === $group_name . '_name') {
            return isset($data['name']) ? esc_html($data['name']) : 'Nombre no disponible';
        }
        if ($tag === $group_name . '_description') {
            return isset($data['description']) ? esc_html($data['description']) : 'Descripción no disponible';
        }
        if ($tag === $group_name . '_images') {
            return get_api_images_html($data['images']);
        }
        if ($tag === $group_name . '_featured_image') {
            return get_api_featured_image_html($data['images']);
        }
        // Agregar más tags según sea necesario
    }

    return $tag; // Devolver el tag original si no coincide
}

// Función para obtener los datos de la API desde el endpoint
function get_api_data($endpoint_url)
{
    $response = wp_remote_get($endpoint_url);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data;
}

// Funciones auxiliares para obtener HTML de las imágenes y la imagen destacada
function get_api_images_html($images)
{
    if (empty($images)) {
        return 'No hay imágenes disponibles.';
    }

    $html = '<div class="api-images">';
    foreach ($images as $image_url) {
        $html .= '<img src="' . esc_url($image_url) . '" alt="Imagen">';
    }
    $html .= '</div>';

    return $html;
}

function get_api_featured_image_html($images)
{
    if (empty($images)) {
        return 'No hay imagen destacada disponible.';
    }

    return '<img src="' . esc_url($images[0]) . '" alt="Imagen destacada" style="max-width:100%;"/>';
}
