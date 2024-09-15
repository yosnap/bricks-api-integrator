<?php

// Registro de la configuración del endpoint
add_action('admin_init', 'bricks_api_integrator_settings_init');
function bricks_api_integrator_settings_init()
{
    register_setting('bricks_api_integrator_settings', 'bricks_api_endpoints');

    add_settings_section(
        'bricks_api_integrator_section',
        __('Configuración de Endpoints', 'bricks-api-integrator'),
        null,
        'bricks-api-integrator'
    );

    add_settings_field(
        'bricks_api_endpoints',
        __('Endpoints', 'bricks-api-integrator'),
        'bricks_api_endpoints_render',
        'bricks-api-integrator',
        'bricks_api_integrator_section'
    );
}

// Campo para agregar múltiples endpoints
function bricks_api_endpoints_render()
{
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }
?>
    <div id="endpoints-wrapper">
        <?php foreach ($endpoints as $index => $endpoint): ?>
            <div class="endpoint-group" data-index="<?php echo esc_attr($index); ?>">
                <h4><?php esc_html_e('Endpoint', 'bricks-api-integrator'); ?> <?php echo esc_html($index + 1); ?></h4>

                <label><?php esc_html_e('Nombre del Endpoint:', 'bricks-api-integrator'); ?></label>
                <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($endpoint['name']); ?>" style="width: 100%;" placeholder="Nombre del Endpoint" />

                <label><?php esc_html_e('URL del Endpoint:', 'bricks-api-integrator'); ?></label>
                <input type="url" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($endpoint['url']); ?>" style="width: 100%;" placeholder="URL del Endpoint" />

                <label><?php esc_html_e('Autenticación:', 'bricks-api-integrator'); ?></label>
                <select name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][auth_type]" class="auth-type-select">
                    <option value="none" <?php selected($endpoint['auth_type'], 'none'); ?>><?php esc_html_e('Sin Autenticación', 'bricks-api-integrator'); ?></option>
                    <option value="basic" <?php selected($endpoint['auth_type'], 'basic'); ?>><?php esc_html_e('Autenticación Básica', 'bricks-api-integrator'); ?></option>
                    <option value="token" <?php selected($endpoint['auth_type'], 'token'); ?>><?php esc_html_e('Token', 'bricks-api-integrator'); ?></option>
                </select>

                <div class="auth-fields">
                    <?php if ($endpoint['auth_type'] === 'basic') : ?>
                        <label><?php esc_html_e('Usuario:', 'bricks-api-integrator'); ?></label>
                        <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][basic_user]" value="<?php echo esc_attr($endpoint['basic_user']); ?>" style="width: 100%;" placeholder="Usuario" />

                        <label><?php esc_html_e('Contraseña:', 'bricks-api-integrator'); ?></label>
                        <input type="password" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][basic_password]" value="<?php echo esc_attr($endpoint['basic_password']); ?>" style="width: 100%;" placeholder="Contraseña" />
                    <?php elseif ($endpoint['auth_type'] === 'token') : ?>
                        <label><?php esc_html_e('Token:', 'bricks-api-integrator'); ?></label>
                        <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][token]" value="<?php echo esc_attr($endpoint['token']); ?>" style="width: 100%;" placeholder="Token" />
                    <?php endif; ?>
                </div>

                <button type="button" class="remove-endpoint button"><?php esc_html_e('Eliminar Endpoint', 'bricks-api-integrator'); ?></button>
                <hr>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}
