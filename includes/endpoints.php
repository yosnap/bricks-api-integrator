<?php

// Función para obtener los datos de un endpoint y manejar la autenticación
function bricks_api_fetch_endpoint_data($url, $auth_type, $token = '', $user = '', $password = '')
{
    $args = [];

    if ($auth_type === 'token') {
        $args['headers'] = ['Authorization' => 'Bearer ' . $token];
    } elseif ($auth_type === 'basic') {
        $args['headers'] = ['Authorization' => 'Basic ' . base64_encode("$user:$password")];
    }

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return ['error' => __('Error al conectar con la API', 'bricks-api-integrator')];
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}
