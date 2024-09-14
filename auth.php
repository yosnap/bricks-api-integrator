<?php
if (!defined('ABSPATH')) {
    exit; // Evita accesos directos
}

// Función para agregar y manejar la autenticación en el dashboard
add_action('admin_init', 'bricks_api_integrator_auth_settings_init');

function bricks_api_integrator_auth_settings_init()
{
    // Registrar las opciones de autenticación
    register_setting('bricks_api_integrator_settings', 'bricks_api_auth_type');
    register_setting('bricks_api_integrator_settings', 'bricks_api_token');
    register_setting('bricks_api_integrator_settings', 'bricks_api_basic_user');
    register_setting('bricks_api_integrator_settings', 'bricks_api_basic_password');

    // Agregar la sección de autenticación en el dashboard
    add_settings_section(
        'bricks_api_integrator_auth_section',
        __('Configuración de Autenticación', 'bricks-api-integrator'),
        null,
        'bricks-api-integrator'
    );

    // Tipo de autenticación
    add_settings_field(
        'bricks_api_auth_type',
        __('Tipo de Autenticación', 'bricks-api-integrator'),
        'bricks_api_auth_type_render',
        'bricks-api-integrator',
        'bricks_api_integrator_auth_section'
    );

    // Token de autenticación
    add_settings_field(
        'bricks_api_token',
        __('Token de Autenticación', 'bricks-api-integrator'),
        'bricks_api_token_render',
        'bricks-api-integrator',
        'bricks_api_integrator_auth_section',
        ['class' => 'auth_field token_field'] // Clase CSS para controlar visibilidad
    );

    // Usuario para Basic Auth
    add_settings_field(
        'bricks_api_basic_user',
        __('Usuario (Basic Auth)', 'bricks-api-integrator'),
        'bricks_api_basic_user_render',
        'bricks-api-integrator',
        'bricks_api_integrator_auth_section',
        ['class' => 'auth_field basic_field'] // Clase CSS para controlar visibilidad
    );

    // Contraseña para Basic Auth
    add_settings_field(
        'bricks_api_basic_password',
        __('Contraseña (Basic Auth)', 'bricks-api-integrator'),
        'bricks_api_basic_password_render',
        'bricks-api-integrator',
        'bricks_api_integrator_auth_section',
        ['class' => 'auth_field basic_field'] // Clase CSS para controlar visibilidad
    );
}

// Renderizar el campo para el tipo de autenticación
function bricks_api_auth_type_render()
{
    $auth_type = get_option('bricks_api_auth_type', 'none');
?>
    <select name="bricks_api_auth_type" id="bricks_api_auth_type">
        <option value="none" <?php selected($auth_type, 'none'); ?>><?php _e('Sin autenticación', 'bricks-api-integrator'); ?></option>
        <option value="token" <?php selected($auth_type, 'token'); ?>><?php _e('Token de Autenticación', 'bricks-api-integrator'); ?></option>
        <option value="basic" <?php selected($auth_type, 'basic'); ?>><?php _e('Basic Auth', 'bricks-api-integrator'); ?></option>
    </select>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var authType = document.getElementById('bricks_api_auth_type');
            var tokenField = document.querySelector('.token_field');
            var basicFields = document.querySelectorAll('.basic_field');

            function toggleAuthFields() {
                // Mostrar/ocultar campos según el tipo de autenticación seleccionado
                if (authType.value === 'token') {
                    tokenField.style.display = 'table-row';
                    basicFields.forEach(function(field) {
                        field.style.display = 'none';
                    });
                } else if (authType.value === 'basic') {
                    tokenField.style.display = 'none';
                    basicFields.forEach(function(field) {
                        field.style.display = 'table-row';
                    });
                } else {
                    tokenField.style.display = 'none';
                    basicFields.forEach(function(field) {
                        field.style.display = 'none';
                    });
                }
            }

            authType.addEventListener('change', toggleAuthFields);
            toggleAuthFields(); // Ejecutar al cargar la página para el estado inicial
        });
    </script>
<?php
}

// Renderizar el campo para el token
function bricks_api_token_render()
{
    $token = get_option('bricks_api_token', '');
?>
    <input type="text" name="bricks_api_token" value="<?php echo esc_attr($token); ?>" style="width: 100%;" />
<?php
}

// Renderizar el campo para el usuario de Basic Auth
function bricks_api_basic_user_render()
{
    $user = get_option('bricks_api_basic_user', '');
?>
    <input type="text" name="bricks_api_basic_user" value="<?php echo esc_attr($user); ?>" style="width: 100%;" />
<?php
}

// Renderizar el campo para la contraseña de Basic Auth
function bricks_api_basic_password_render()
{
    $password = get_option('bricks_api_basic_password', '');
?>
    <input type="password" name="bricks_api_basic_password" value="<?php echo esc_attr($password); ?>" style="width: 100%;" />
<?php
}
