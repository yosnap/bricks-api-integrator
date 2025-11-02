<?php
/**
 * Página de gestión de API Endpoints - VERSIÓN CORREGIDA CON ACORDEÓN ÚNICO
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('render_api_endpoints_page')) {
    function render_api_endpoints_page() {
        // Procesar formulario
        if (isset($_POST['save_endpoints']) && wp_verify_nonce($_POST['_wpnonce'], 'save_endpoints')) {
            $endpoints = [];
            
            if (isset($_POST['endpoints']) && is_array($_POST['endpoints'])) {
                foreach ($_POST['endpoints'] as $endpoint) {
                    if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                        // Procesar parámetros dinámicos
                        $dynamic_params = [];
                        if (isset($endpoint['param_names']) && is_array($endpoint['param_names'])) {
                            foreach ($endpoint['param_names'] as $param_index => $param_name) {
                                if (!empty($param_name)) {
                                    $dynamic_params[] = [
                                        'name' => sanitize_text_field($param_name),
                                        'source' => sanitize_text_field($endpoint['param_sources'][$param_index] ?? 'url'),
                                        'default' => sanitize_text_field($endpoint['param_defaults'][$param_index] ?? '')
                                    ];
                                }
                            }
                        }
                        
                        $endpoints[] = [
                            'name' => sanitize_text_field($endpoint['name']),
                            'url' => preg_replace('/[^a-zA-Z0-9\-\_\:\/\.\?\=\&\{\}]/', '', $endpoint['url']),
                            'auth_type' => sanitize_text_field($endpoint['auth_type'] ?? 'none'),
                            'token' => sanitize_text_field($endpoint['token'] ?? ''),
                            'basic_user' => sanitize_text_field($endpoint['basic_user'] ?? ''),
                            'basic_password' => trim($endpoint['basic_password'] ?? ''),
                            'api_key' => sanitize_text_field($endpoint['api_key'] ?? ''),
                            'api_key_header' => sanitize_text_field($endpoint['api_key_header'] ?? 'X-API-Key'),
                            'dynamic_params' => $dynamic_params,
                        ];
                    }
                }
            }
            
            update_option('bricks_api_endpoints', $endpoints);
            
            // Limpiar cache
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%'");
            
            echo '<div class="notice notice-success"><p>✅ Endpoints guardados correctamente</p></div>';
        }
        
        $endpoints = get_option('bricks_api_endpoints', []);
        
        // DEBUG: Verificar qué endpoints se están cargando
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        ?>
        <div class="wrap">
            <h1>🔗 API Endpoints</h1>
            <p>Gestiona tus endpoints de API. Los Query Types y Dynamic Tags se generan automáticamente.</p>
            
            <style>
            /* Ajuste visual para igualar a Sources */
            .endpoint-form-container {
              background: none;
              border: none;
              border-radius: 0;
              padding: 0;
              margin-bottom: 32px;
              box-shadow: none;
              max-width: 100%;
            }
            #endpoint-form-title {
              margin-top: 0;
              margin-bottom: 18px;
              color: #222;
              font-size: 1.3em;
              font-weight: 600;
            }
            #endpoint-form {
              width: 100%;
              max-width: 100%;
            }
            .form-table {
              width: 100%;
              max-width: 100%;
              background: none;
              border: none;
            }
            .form-table th, .form-table td {
              padding: 8px 6px;
              vertical-align: middle;
              border: none;
            }
            #dynamic-params-container {
              margin-bottom: 8px;
            }
            .endpoint-actions {
              margin-top: 18px;
              display: flex;
              gap: 8px;
              flex-wrap: wrap;
              align-items: center;
            }
            #endpoint-test-result, #endpoint-form-message {
              margin-top: 15px;
              max-width: 100%;
            }
            #endpoints-table {
              background: #fff;
              border-radius: 8px;
              overflow: hidden;
              box-shadow: 0 2px 8px rgba(0,0,0,0.03);
              max-width: 100%;
              width: 100%;
            }
            #endpoints-table th, #endpoints-table td {
              padding: 12px 10px;
              vertical-align: middle;
            }
            #endpoints-table th {
              background: #f8f9fa;
              color: #333;
              font-weight: 600;
              border-bottom: 2px solid #e5e5e5;
            }
            #endpoints-table tr {
              border-bottom: 1px solid #f0f0f0;
            }
            #endpoints-table tr:last-child {
              border-bottom: none;
            }
            .button-edit {
              background: #0073aa !important;
              color: #fff !important;
              border: none;
              margin-right: 4px;
            }
            .button-edit:hover {
              background: #005177 !important;
            }
            .button-delete {
              background: #dc3232 !important;
              color: #fff !important;
              border: none;
              margin-right: 4px;
            }
            .button-delete:hover {
              background: #a71d2a !important;
            }
            .button-create-tags {
              background: #ff9800 !important;
              color: #fff !important;
              border: none;
              margin-right: 4px;
            }
            .button-create-tags:hover {
              background: #e65100 !important;
            }
            .button-view-tags {
              background: #00b894 !important;
              color: #fff !important;
              border: none;
            }
            .button-view-tags:hover {
              background: #008c6e !important;
            }
            #add-param-btn {
              margin-top: 8px;
            }
            #endpoint-form-message.success {
              background: #e9fbe5;
              color: #256029;
              border: 1px solid #b6e2b3;
            }
            #endpoint-form-message.error {
              background: #ffeaea;
              color: #a71d2a;
              border: 1px solid #f5c6cb;
            }
            .preloader {
              display: flex;
              align-items: center;
              gap: 8px;
              color: #2980b9;
              font-weight: 500;
            }
            .preloader:before {
              content: '';
              display: inline-block;
              width: 18px;
              height: 18px;
              border: 3px solid #b3d4fc;
              border-top: 3px solid #2980b9;
              border-radius: 50%;
              animation: spin 1s linear infinite;
            }
            @keyframes spin {
              0% { transform: rotate(0deg);}
              100% { transform: rotate(360deg);}
            }
            </style>
            
            <!-- Formulario de alta/edición de endpoint -->
            <div class="endpoint-form-container">
              <div style="margin-bottom: 15px;">
                <h2 id="endpoint-form-title" style="margin: 0;">Añadir nuevo endpoint</h2>
              </div>
              <form id="endpoint-form" method="post">
                <?php wp_nonce_field('save_endpoints'); ?>
                        <table class="form-table">
                            <tr>
                    <th><label for="endpoint_name">Nombre del Endpoint</label></th>
                    <td><input type="text" id="endpoint_name" name="endpoint_name" class="regular-text" required></td>
                            </tr>
                            <tr>
                    <th><label for="endpoint_url">URL del Endpoint</label></th>
                    <td><input type="url" id="endpoint_url" name="endpoint_url" class="regular-text" required></td>
                            </tr>
                            <tr>
                    <th><label for="auth_type">Autenticación</label></th>
                                <td>
                      <select id="auth_type" name="auth_type">
                                        <option value="none">Sin Autenticación</option>
                                        <option value="token">Bearer Token</option>
                                        <option value="basic">Basic Auth</option>
                                        <option value="api_key">API Key</option>
                                    </select>
                                </td>
                            </tr>
                  <tr id="auth-fields-row" style="display:none;">
                    <th><label>Datos de autenticación</label></th>
                    <td id="auth-fields-container"></td>
                  </tr>
                            <tr>
                                <th><label>Parámetros Dinámicos</label></th>
                                <td>
                      <div id="dynamic-params-container"></div>
                      <button type="button" class="button" id="add-param-btn">Añadir Parámetro</button>
                                </td>
                            </tr>
                            <tr>
                                <th><label>🔄 Field Transformers</label></th>
                                <td>
                                    <p style="margin:0 0 10px;color:#666;font-size:13px;">Transforma automáticamente campos (ej: convertir IDs de imagen en URLs completas)</p>
                      <div id="field-transformers-container"></div>
                      <button type="button" class="button" id="add-transformer-btn">+ Añadir Transformador</button>
                                </td>
                            </tr>
                        </table>
                <div style="margin-top: 18px;" id="form-buttons-container">
                  <!-- Los botones se mostrarán dinámicamente según el contexto -->
                                </div>
              </form>
              <!-- Botones de acción debajo del formulario -->
              <div class="endpoint-actions" style="margin-top: 18px;">
                <button type="button" class="button button-secondary" id="test-api-btn">🧪 Test API</button>
                <button type="button" class="button button-success" id="generate-tags-btn">⚡ Generar Query Type y Tags Dinámicos</button>
                <button type="button" class="button button-view-tags" id="view-tags-btn" style="display:none;">🏷️ Ver Dynamic Tags</button>
                <button type="button" class="button button-delete-tags" id="delete-tags-btn" style="display:none;background:#dc3232;color:#fff;">🗑️ Eliminar tags y query type</button>
                                </div>
              <div id="endpoint-test-result" style="margin-top: 15px;"></div>
              <div id="endpoint-form-message" style="margin-top: 10px;"></div>
                        </div>
                        
            <!-- Tabla de endpoints configurados -->
            <h2 style="margin-top: 40px;">Endpoints configurados</h2>
            <table class="wp-list-table widefat fixed striped" id="endpoints-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>URL</th>
                  <th>Autenticación</th>
                  <th>Parámetros</th>
                  <th style="text-align:center;">Acciones</th>
                            </tr>
              </thead>
              <tbody>
                <?php foreach ($endpoints as $index => $endpoint): ?>
                  <tr data-index="<?php echo $index; ?>">
                    <td><?php echo esc_html($endpoint['name']); ?></td>
                    <td><?php echo esc_html($endpoint['url']); ?></td>
                    <td><?php echo esc_html(ucfirst($endpoint['auth_type'])); ?></td>
                    <td>
                      <?php if (!empty($endpoint['dynamic_params'])): ?>
                        <?php echo esc_html(implode(', ', array_map(function($p){return $p['name'];}, $endpoint['dynamic_params']))); ?>
                      <?php else: ?>
                        <em>—</em>
                      <?php endif; ?>
                                </td>
                    <td style="text-align:center;">
                      <button class="button button-small button-edit">Editar</button>
                      <button class="button button-small button-delete">Eliminar</button>
                                </td>
                            </tr>
                <?php endforeach; ?>
              </tbody>
                        </table>
                                        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let editIndex = null;
            let endpoints = <?php echo json_encode($endpoints); ?>;
            let tagsGenerated = {};

            // --- Utilidades ---
            function resetForm() {
                $('#endpoint-form')[0].reset();
                $('#dynamic-params-container').empty();
                $('#field-transformers-container').empty();
                $('#auth-fields-row').hide();
                $('#auth-fields-container').empty();
                $('#endpoint-form-title').text('Añadir nuevo endpoint');
                editIndex = null; // Asegurar explícitamente que editIndex sea null
                $('#endpoint-form-message').removeClass('success error').text('');
                $('#endpoint-test-result').html('');

                // Actualizar botones según el contexto
                updateFormButtons();
            }
            
            // Función para actualizar los botones según el contexto
            function updateFormButtons() {
                const $container = $('#form-buttons-container');
                $container.empty();
                
                if (editIndex === null) {
                    // Modo de creación: mostrar solo el botón '+ Nuevo Endpoint'
                    $container.append('<button type="button" class="button button-primary" id="create-endpoint-btn">+ Nuevo Endpoint</button>');
                } else {
                    // Modo de edición: mostrar 'Actualizar Endpoint' y 'Cancelar'
                    $container.append('<button type="submit" class="button button-primary" id="save-endpoint-btn">Actualizar Endpoint</button> ' +
                                     '<button type="button" class="button" id="cancel-edit-btn" style="margin-left: 10px;">Cancelar</button>');
                    // Asignar evento al botón Cancelar
                    $('#cancel-edit-btn').click(function(){ 
                        resetForm(); 
                    });
                }
                // Asignar evento al botón '+ Nuevo Endpoint'
                $('#create-endpoint-btn').off('click').on('click', function(e) {
                    e.preventDefault();
                    
                    // Validación: no permitir parámetros dinámicos sin nombre
                    let hasEmptyParam = false;
                    $('#dynamic-params-container .dynamic-param-row').each(function(){
                        const name = $(this).find('.param-name').val().trim();
                        if (!name) {
                            hasEmptyParam = true;
                            $(this).find('.param-name').css('border','2px solid #dc3232');
                        } else {
                            $(this).find('.param-name').css('border','');
                        }
                    });
                    if (hasEmptyParam) {
                        showMessage('No puedes guardar parámetros dinámicos sin nombre. Corrige los campos en rojo.', 'error');
                        return;
                    }
                    
                    // Forzar la creación de un nuevo endpoint
                    console.log('Creando nuevo endpoint, ignorando editIndex');
                    
                    // Obtener parámetros dinámicos de forma segura
                    let dynamicParams = [];
                    try {
                        dynamicParams = getParamsFromForm() || [];
                    } catch (paramError) {
                        console.error('Error al obtener parámetros:', paramError);
                        dynamicParams = [];
                    }
                    
                    // Importante: NO usar la variable editIndex aquí
                    const data = {
                        action: 'save_api_endpoint',
                        nonce: '<?php echo wp_create_nonce('save_api_endpoint'); ?>',
                        // Enviar explícitamente 'new' en lugar de null o editIndex
                        index: 'new',
                        name: $('#endpoint_name').val().trim(),
                        url: $('#endpoint_url').val().trim(),
                        auth_type: $('#auth_type').val(),
                        token: $('#token').val() || '',
                        basic_user: $('#basic_user').val() || '',
                        basic_password: $('#basic_password').val() || '',
                        api_key: $('#api_key').val() || '',
                        api_key_header: $('#api_key_header').val() || '',
                        dynamic_params: dynamicParams,
                        field_transformers: getTransformersFromForm()
                    };

                    $.post(ajaxurl, data, function(resp){
                        if (resp.success) {
                            endpoints = resp.data.endpoints;
                            renderTable();
                            resetForm();
                            showMessage('¡Nuevo endpoint creado correctamente!', 'success');
                        } else {
                            console.error('Error en la respuesta:', resp);
                            showMessage(resp.data || 'Error al crear el endpoint', 'error');
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Error en la petición AJAX:', error);
                        showMessage('Error en la comunicación con el servidor', 'error');
                    });
                });
            }
            function renderTable() {
                const $tbody = $('#endpoints-table tbody');
                $tbody.empty();
                endpoints.forEach((ep, i) => {
                    $tbody.append(`
                        <tr data-index="${i}">
                            <td>${ep.name}</td>
                            <td>${ep.url}</td>
                            <td>${ep.auth_type ? ep.auth_type.charAt(0).toUpperCase() + ep.auth_type.slice(1) : ''}</td>
                            <td>
                                ${ep.dynamic_params && ep.dynamic_params.length
                                    ? ep.dynamic_params.map(p => p.name).join(', ')
                                    : '<em>—</em>'}
                            </td>
                            <td style="text-align:center;">
                                <button class="button button-small button-edit">Editar</button>
                                <button class="button button-small button-delete">Eliminar</button>
                            </td>
                        </tr>
                    `);
                });
            }
            function renderParams(params) {
                $('#dynamic-params-container').empty();
                (params||[]).forEach((param, idx) => addParamRow(param.name, param.source, param.default));
            }
            function addParamRow(name='', source='url', def='') {
                // Obtener el índice actual del endpoint (editIndex o 0 si es nuevo)
                const idx = (typeof editIndex === 'number' && editIndex !== null) ? editIndex : 0;
                const row = $(
                    `<div class="dynamic-param-row" style="margin-bottom:6px;display:flex;gap:6px;align-items:center;">
                        <input type="text" class="regular-text param-name" name="endpoints[${idx}][param_names][]" placeholder="Nombre" value="${name}">
                        <select class="param-source" name="endpoints[${idx}][param_sources][]">
                            <option value="url" ${source==='url'?'selected':''}>Parámetro URL</option>
                            <option value="post" ${source==='post'?'selected':''}>ID Post</option>
                            <option value="post_slug" ${source==='post_slug'?'selected':''}>Slug Post</option>
                            <option value="user" ${source==='user'?'selected':''}>ID Usuario</option>
                            <option value="meta" ${source==='meta'?'selected':''}>Meta Field</option>
                            <option value="static" ${source==='static'?'selected':''}>Valor Estático</option>
                        </select>
                        <input type="text" class="regular-text param-default" name="endpoints[${idx}][param_defaults][]" placeholder="Valor por defecto" value="${def}">
                        <button type="button" class="button button-delete-param" style="background:#dc3232;color:#fff;">Eliminar</button>
                    </div>`
                );
                row.find('.button-delete-param').click(function(){ row.remove(); });
                $('#dynamic-params-container').append(row);
            }
            function getParamsFromForm() {
                const params = [];
                $('#dynamic-params-container .dynamic-param-row').each(function(){
                    const name = $(this).find('.param-name').val().trim();
                    if (!name) return;
                    params.push({
                        name,
                        source: $(this).find('.param-source').val(),
                        default: $(this).find('.param-default').val()
                    });
                });
                return params;
            }

            // --- Field Transformers ---
            function addTransformerRow(field='', type='related_endpoint', config={}) {
                const idx = (typeof editIndex === 'number' && editIndex !== null) ? editIndex : 0;
                const row = $(`
                    <div class="transformer-row" style="margin-bottom:10px;padding:12px;border:1px solid #ddd;border-radius:4px;background:#f9f9f9;">
                        <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                            <input type="text" class="transformer-field" placeholder="Nombre del campo (ej: image)" value="${field}" style="flex:1;">
                            <select class="transformer-type" style="flex:1;">
                                <option value="related_endpoint" ${type==='related_endpoint'?'selected':''}>🔗 Endpoint Relacionado</option>
                                <option value="url_template" ${type==='url_template'?'selected':''}>🌐 Template URL</option>
                                <option value="prefix" ${type==='prefix'?'selected':''}>⬅️ Prefijo</option>
                                <option value="suffix" ${type==='suffix'?'selected':''}>➡️ Sufijo</option>
                            </select>
                            <button type="button" class="button button-small button-delete-transformer" style="background:#dc3232;color:#fff;">✕</button>
                        </div>
                        <div class="transformer-config"></div>
                    </div>
                `);

                // Función para añadir parámetro clave-valor
                function addParamKeyValue(key='', value='', container) {
                    const paramRow = $(`
                        <div class="transformer-param-row" style="display:flex;gap:6px;margin-bottom:4px;">
                            <input type="text" class="transformer-param-key" placeholder="Clave (ej: api_key)" value="${key}" style="flex:1;">
                            <input type="text" class="transformer-param-value" placeholder="Valor" value="${value}" style="flex:1;">
                            <button type="button" class="button button-small button-delete-param-kv" style="background:#dc3232;color:#fff;padding:0 8px;">✕</button>
                        </div>
                    `);
                    paramRow.find('.button-delete-param-kv').click(function(){ paramRow.remove(); });
                    container.append(paramRow);
                }

                // Función para actualizar los campos de configuración según el tipo
                function updateConfigFields() {
                    const currentType = row.find('.transformer-type').val();
                    const $config = row.find('.transformer-config');
                    $config.empty();

                    if (currentType === 'related_endpoint') {
                        const paramsContainer = $('<div class="transformer-params-container" style="margin-top:8px;"></div>');

                        $config.append(`
                            <input type="text" class="regular-text transformer-endpoint-url" placeholder="URL del endpoint (ej: https://api.example.com/image/{id})" value="${config.endpoint_url||''}" style="width:100%;margin-bottom:8px;">
                            <div style="margin-bottom:4px;font-weight:500;font-size:13px;">Parámetros de URL:</div>
                        `);
                        $config.append(paramsContainer);

                        // Cargar parámetros existentes
                        if (config.params && typeof config.params === 'object') {
                            Object.entries(config.params).forEach(([key, value]) => {
                                addParamKeyValue(key, value, paramsContainer);
                            });
                        }

                        // Botón para añadir más parámetros
                        const addParamBtn = $('<button type="button" class="button button-small" style="margin-top:4px;">+ Añadir parámetro</button>');
                        addParamBtn.click(function(){ addParamKeyValue('', '', paramsContainer); });
                        $config.append(addParamBtn);

                    } else if (currentType === 'url_template') {
                        $config.append(`
                            <input type="text" class="regular-text transformer-template" placeholder="Template (ej: https://cdn.example.com/{value})" value="${config.template||''}" style="width:100%;">
                        `);
                    } else if (currentType === 'prefix') {
                        $config.append(`
                            <input type="text" class="regular-text transformer-prefix" placeholder="Prefijo (ej: img_)" value="${config.prefix_value||''}" style="width:100%;">
                        `);
                    } else if (currentType === 'suffix') {
                        $config.append(`
                            <input type="text" class="regular-text transformer-suffix" placeholder="Sufijo (ej: .webp)" value="${config.suffix_value||''}" style="width:100%;">
                        `);
                    }
                }

                // Inicializar campos
                updateConfigFields();

                // Actualizar cuando cambie el tipo
                row.find('.transformer-type').change(updateConfigFields);

                // Botón eliminar
                row.find('.button-delete-transformer').click(function(){ row.remove(); });

                $('#field-transformers-container').append(row);
            }

            function getTransformersFromForm() {
                const transformers = [];
                $('#field-transformers-container .transformer-row').each(function(){
                    const field = $(this).find('.transformer-field').val().trim();
                    if (!field) return;

                    const type = $(this).find('.transformer-type').val();
                    const transformer = { field, type };

                    if (type === 'related_endpoint') {
                        transformer.endpoint_url = $(this).find('.transformer-endpoint-url').val().trim();

                        // Extraer parámetros del sistema clave-valor
                        transformer.params = {};
                        $(this).find('.transformer-param-row').each(function(){
                            const key = $(this).find('.transformer-param-key').val().trim();
                            const value = $(this).find('.transformer-param-value').val().trim();
                            if (key) {
                                transformer.params[key] = value;
                            }
                        });
                    } else if (type === 'url_template') {
                        transformer.template = $(this).find('.transformer-template').val().trim();
                    } else if (type === 'prefix') {
                        transformer.prefix_value = $(this).find('.transformer-prefix').val().trim();
                    } else if (type === 'suffix') {
                        transformer.suffix_value = $(this).find('.transformer-suffix').val().trim();
                    }

                    transformers.push(transformer);
                });
                return transformers;
            }

            function showMessage(msg, type) {
                $('#endpoint-form-message').removeClass('success error').addClass(type).text(msg);
            }
            function showTestResult(html) {
                $('#endpoint-test-result').html(html);
            }
            function showTagsButton(show) {
                $('#view-tags-btn').toggle(!!show);
                $('#delete-tags-btn').toggle(!!show);
            }

            // --- Eventos de parámetros dinámicos ---
            $('#add-param-btn').click(function(){ addParamRow(); });

            // --- Eventos de transformadores ---
            $('#add-transformer-btn').click(function(){ addTransformerRow(); });

            // --- Autenticación dinámica ---
            $('#auth_type').change(function(){
                const type = $(this).val();
                const $row = $('#auth-fields-row');
                const $container = $('#auth-fields-container');
                $container.empty();
                if (type === 'token') {
                    $container.append('<input type="text" class="regular-text" id="token" placeholder="Bearer Token">');
                    $row.show();
                } else if (type === 'basic') {
                    $container.append('<input type="text" class="regular-text" id="basic_user" placeholder="Usuario"> ' +
                                      '<input type="password" class="regular-text" id="basic_password" placeholder="Contraseña">');
                    $row.show();
                } else if (type === 'api_key') {
                    $container.append('<input type="text" class="regular-text" id="api_key" placeholder="API Key"> ' +
                                      '<input type="text" class="regular-text" id="api_key_header" placeholder="Header (X-API-Key)" value="X-API-Key">');
                    $row.show();
                    } else {
                    $row.hide();
                }
            });

            // --- Actualizar endpoint existente ---
            $('#endpoint-form').submit(function(e){
                e.preventDefault();
                if (editIndex === null) {
                    console.log('No se debería enviar el formulario en modo creación');
                    return;
                }
                // Validación: no permitir parámetros dinámicos sin nombre
                let hasEmptyParam = false;
                $('#dynamic-params-container .dynamic-param-row').each(function(){
                    const name = $(this).find('.param-name').val().trim();
                    if (!name) {
                        hasEmptyParam = true;
                        $(this).find('.param-name').css('border','2px solid #dc3232');
                    } else {
                        $(this).find('.param-name').css('border','');
                    }
                });
                if (hasEmptyParam) {
                    showMessage('No puedes guardar parámetros dinámicos sin nombre. Corrige los campos en rojo.', 'error');
                    return;
                }

                const data = {
                    action: 'save_api_endpoint',
                    nonce: '<?php echo wp_create_nonce('save_api_endpoint'); ?>',
                    index: editIndex, // Actualizar endpoint existente
                    name: $('#endpoint_name').val().trim(),
                    url: $('#endpoint_url').val().trim(),
                    auth_type: $('#auth_type').val(),
                    token: $('#token').val() || '',
                    basic_user: $('#basic_user').val() || '',
                    basic_password: $('#basic_password').val() || '',
                    api_key: $('#api_key').val() || '',
                    api_key_header: $('#api_key_header').val() || '',
                    dynamic_params: getParamsFromForm(),
                    field_transformers: getTransformersFromForm()
                };
                $.post(ajaxurl, data, function(resp){
                    if (resp.success) {
                        endpoints = resp.data.endpoints;
                        renderTable();
                        // NO resetear el formulario, mantener en modo edición
                        showMessage('¡Endpoint actualizado correctamente!', 'success');
                    } else {
                        showMessage(resp.data || 'Error al guardar el endpoint', 'error');
                    }
                });
            });
            // --- Editar endpoint ---
            $('#endpoints-table').on('click', '.button-edit', function(){
                const idx = $(this).closest('tr').data('index');
                const ep = endpoints[idx];
                console.log('[DEBUG] Editando endpoint idx:', idx, 'Objeto:', ep);
                editIndex = idx;
                console.log('Editando endpoint con índice:', editIndex); // Debug
                $('#endpoint-form-title').text('Editar endpoint: ' + ep.name);
                $('#endpoint_name').val(ep.name);
                $('#endpoint_url').val(ep.url);
                $('#auth_type').val(ep.auth_type).trigger('change');
                setTimeout(function(){
                    $('#token').val(ep.token||'');
                    $('#basic_user').val(ep.basic_user||'');
                    $('#basic_password').val(ep.basic_password||'');
                    $('#api_key').val(ep.api_key||'');
                    $('#api_key_header').val(ep.api_key_header||'');
                }, 100);
                // Reconstruir los parámetros dinámicos en los inputs con el índice real
                $('#dynamic-params-container').empty();
                console.log('[DEBUG] Parámetros dinámicos a rellenar:', ep.dynamic_params);
                (ep.dynamic_params||[]).forEach((param, pidx) => {
                    const row = $(
                        `<div class="dynamic-param-row" style="margin-bottom:6px;display:flex;gap:6px;align-items:center;">
                            <input type="text" class="regular-text param-name" name="endpoints[${idx}][param_names][]" placeholder="Nombre" value="${param.name}">
                            <select class="param-source" name="endpoints[${idx}][param_sources][]">
                                <option value="url" ${param.source==='url'?'selected':''}>Parámetro URL</option>
                                <option value="post" ${param.source==='post'?'selected':''}>ID Post</option>
                                <option value="post_slug" ${param.source==='post_slug'?'selected':''}>Slug Post</option>
                                <option value="user" ${param.source==='user'?'selected':''}>ID Usuario</option>
                                <option value="meta" ${param.source==='meta'?'selected':''}>Meta Field</option>
                                <option value="static" ${param.source==='static'?'selected':''}>Valor Estático</option>
                            </select>
                            <input type="text" class="regular-text param-default" name="endpoints[${idx}][param_defaults][]" placeholder="Valor por defecto" value="${param.default}">
                            <button type="button" class="button button-delete-param" style="background:#dc3232;color:#fff;">Eliminar</button>
                        </div>`);
                    row.find('.button-delete-param').click(function(){ row.remove(); });
                    $('#dynamic-params-container').append(row);
                });

                // Reconstruir los transformadores de campos
                $('#field-transformers-container').empty();
                (ep.field_transformers||[]).forEach((transformer) => {
                    addTransformerRow(transformer.field, transformer.type, transformer);
                });

                $('#save-endpoint-btn').text('Actualizar Endpoint');
                $('#cancel-edit-btn').show();
                $('#endpoint-form-message').removeClass('success error').text('');
                $('#endpoint-test-result').html('');

                // --- Comprobar y mostrar tags generados al editar/cargar endpoint ---
                const url = $('#endpoint_url').val().trim();
                const name = $('#endpoint_name').val().trim();
                if (name) {
                    $.post(ajaxurl, {
                        action: 'get_saved_tags_for_endpoint',
                        nonce: '<?php echo wp_create_nonce('get_saved_tags_for_endpoint'); ?>',
                        url: url,
                        name: name
                    }, function(resp){
                        if (resp.success && resp.data && resp.data.tags && resp.data.tags.length) {
                            showTagsButton(true); // Muestra los botones de tags
                            // Visualización avanzada igual que tras generar
                            const tags = resp.data.tags || [];
                            const example = resp.data.example || {};
                            const exampleJson = resp.data.example_json || '';
                            let html = '';
                            html += `<div style="margin-bottom:18px;"><strong>📦 Respuesta de la API (primer registro o detalle):</strong><pre style="background:#f8f9fa;padding:10px;border-radius:5px;max-height:300px;overflow:auto;font-size:13px;">${exampleJson}</pre></div>`;
                            html += `<div style="margin-bottom:18px;"><strong>🧩 Estructura detectada:</strong><table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;"><thead><tr style="background:#e7f3ff;"><th style="padding:6px 8px;border:1px solid #e3e3e3;">Campo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Tipo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Valor de ejemplo</th></tr></thead><tbody>`;
                            Object.entries(example).forEach(([key, value]) => {
                                let tipo = Array.isArray(value) ? 'Array' : typeof value;
                                let valEjemplo = (typeof value === 'object' && value !== null)
                                    ? JSON.stringify(value, null, 2)
                                    : value;
                                html += `<tr><td style="padding:6px 8px;border:1px solid #e3e3e3;">${key}</td><td style="padding:6px 8px;border:1px solid #e3e3e3;">${tipo}</td><td style="padding:6px 8px;border:1px solid #e3e3e3;font-family:monospace;">${valEjemplo}</td></tr>`;
                            });
                            html += '</tbody></table></div>';
                            html += `<div style="margin-bottom:18px;"><strong>🏷️ Tags dinámicos generados:</strong><form id="tags-enable-form"><div style="display:grid;gap:8px;margin-top:10px;">`;
                            tags.forEach((tagObj, idx) => {
                                const tagId = 'tag-enable-' + idx;
                                html += `<div style="display:flex;align-items:center;gap:10px;background:#f8f9fa;padding:8px 12px;border-radius:5px;">
                                    <input type="checkbox" id="${tagId}" class="tag-enable-checkbox" data-tag="${tagObj.tag}" checked style="margin-right:6px;">
                                    <label for="${tagId}" style="margin:0;cursor:pointer;"><code style="font-size:14px;color:#e67e22;font-weight:bold;">${tagObj.tag}</code></label>
                                    <button type="button" class="button button-small" style="margin-left:10px;" onclick="navigator.clipboard.writeText('${tagObj.tag}');this.innerText='¡Copiado!';setTimeout(()=>{this.innerText='Copiar tag';},1000);">Copiar tag</button>
                                    <span style="color:#888;">→</span>
                                    <span style="font-family:monospace;background:#fff;padding:2px 6px;border-radius:3px;">${tagObj.example}</span>
                                </div>`;
                            });
                            html += `</div><p style="font-size:12px;color:#6c757d;margin:10px 0 0 0;">💡 Puedes desmarcar los tags que no quieras usar. Haz clic en el tag o en el botón para copiar el valor de ejemplo o el tag completo.</p><button type="submit" class="button button-primary" style="margin-top:12px;">💾 Guardar selección de tags</button></form></div>`;
                            showTestResult(html);
                            // Guardar selección de tags
                            $('#tags-enable-form').off('submit').on('submit', function(e){
                                e.preventDefault();
                                const enabledTags = [];
                                $('.tag-enable-checkbox:checked').each(function(){
                                    enabledTags.push($(this).data('tag'));
                                });
                                $.post(ajaxurl, {
                                    action: 'save_enabled_tags_for_endpoint',
                                    nonce: '<?php echo wp_create_nonce('save_enabled_tags_for_endpoint'); ?>',
                                    url: url,
                                    name: name,
                                    enabled_tags: enabledTags
                                }, function(resp2){
                                    if (resp2.success) {
                                        showMessage('Selección de tags guardada.', 'success');
                } else {
                                        showMessage('Error al guardar la selección de tags.', 'error');
                                    }
                                });
                            });
                        } else {
                            showTagsButton(false);
                            $('#endpoint-test-result').html('');
                        }
                    });
                }
                // --- Refuerzo: asegurar que los botones del formulario se actualizan correctamente en modo edición ---
                updateFormButtons();
            });
            // --- Eliminar endpoint ---
            $('#endpoints-table').on('click', '.button-delete', function(){
                if (!confirm('¿Seguro que quieres eliminar este endpoint?')) return;
                const idx = $(this).closest('tr').data('index');
                $.post(ajaxurl, {
                    action: 'delete_api_endpoint',
                    nonce: '<?php echo wp_create_nonce('delete_api_endpoint'); ?>',
                    index: idx
                }, function(resp){
                    if (resp.success) {
                        endpoints = resp.data.endpoints;
                        renderTable();
                        resetForm();
                        showMessage('Endpoint eliminado.', 'success');
                            } else {
                        showMessage(resp.data || 'Error al eliminar', 'error');
                    }
                });
            });
            // --- Test API ---
            $('#test-api-btn').click(function(){
                // Mostrar preloader antes de la petición
                $('#endpoint-test-result').html('<div class="preloader">Consultando API...</div>');
                const data = {
                    action: 'test_api_endpoint',
                    nonce: '<?php echo wp_create_nonce('test_api_endpoint'); ?>',
                    url: $('#endpoint_url').val().trim(),
                    auth_type: $('#auth_type').val(),
                    token: $('#token').val() || '',
                    basic_user: $('#basic_user').val() || '',
                    basic_password: $('#basic_password').val() || '',
                    api_key: $('#api_key').val() || '',
                    api_key_header: $('#api_key_header').val() || '',
                    dynamic_params: getParamsFromForm()
                };
                $.post(ajaxurl, data, function(resp){
                    if (resp.success) {
                        showTestResult(resp.data.html);
                                    } else {
                        showTestResult('<span style="color:#a71d2a">'+(resp.data||'Error al probar el endpoint')+'</span>');
                    }
                });
            });
            // --- Generar Query Type y Tags Dinámicos ---
            $('#generate-tags-btn').click(function(){
                const data = {
                    action: 'generate_tags_for_endpoint',
                    nonce: '<?php echo wp_create_nonce('generate_tags_for_endpoint'); ?>',
                    url: $('#endpoint_url').val().trim(),
                    name: $('#endpoint_name').val().trim(),
                    auth_type: $('#auth_type').val(),
                    token: $('#token').val() || '',
                    basic_user: $('#basic_user').val() || '',
                    basic_password: $('#basic_password').val() || '',
                    api_key: $('#api_key').val() || '',
                    api_key_header: $('#api_key_header').val() || '',
                    dynamic_params: getParamsFromForm(),
                    field_transformers: getTransformersFromForm(),
                    endpoint_index: editIndex // Añadir índice del endpoint
                };

                showTestResult('<em>Generando tags y query type...</em>');
                $.post(ajaxurl, data, function(resp){
                    if (resp.success && resp.data) {
                        showTagsButton(true);
                        tagsGenerated[data.url] = resp.data.tags;
                        // --- Visualización avanzada ---
                        const tags = resp.data.tags || [];
                        const example = resp.data.example || {};
                        const exampleJson = resp.data.example_json || '';
                let html = '';
                        // 1. JSON formateado
                        html += `<div style="margin-bottom:18px;"><strong>📦 Respuesta de la API (primer registro o detalle):</strong><pre style="background:#f8f9fa;padding:10px;border-radius:5px;max-height:300px;overflow:auto;font-size:13px;">${exampleJson}</pre></div>`;
                        // 2. Estructura detectada
                        html += `<div style="margin-bottom:18px;"><strong>🧩 Estructura detectada:</strong><table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;"><thead><tr style="background:#e7f3ff;"><th style="padding:6px 8px;border:1px solid #e3e3e3;">Campo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Tipo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Valor de ejemplo</th></tr></thead><tbody>`;
                        Object.entries(example).forEach(([key, value]) => {
                            let tipo = Array.isArray(value) ? 'Array' : typeof value;
                            let valEjemplo = (typeof value === 'object' && value !== null)
                                ? JSON.stringify(value, null, 2)
                                : value;
                            html += `<tr><td style="padding:6px 8px;border:1px solid #e3e3e3;">${key}</td><td style="padding:6px 8px;border:1px solid #e3e3e3;">${tipo}</td><td style="padding:6px 8px;border:1px solid #e3e3e3;font-family:monospace;">${valEjemplo}</td></tr>`;
                        });
                        html += `</tbody></table></div>`;
                        // 3. Tags generados con opción de deshabilitar
                        html += `<div style="margin-bottom:18px;"><strong>🏷️ Tags dinámicos generados:</strong><form id="tags-enable-form"><div style="display:grid;gap:8px;margin-top:10px;">`;
                        tags.forEach((tagObj, idx) => {
                            const tagId = 'tag-enable-' + idx;
                            html += `<div style="display:flex;align-items:center;gap:10px;background:#f8f9fa;padding:8px 12px;border-radius:5px;">
                                <input type="checkbox" id="${tagId}" class="tag-enable-checkbox" data-tag="${tagObj.tag}" checked style="margin-right:6px;">
                                <label for="${tagId}" style="margin:0;cursor:pointer;"><code style="font-size:14px;color:#e67e22;font-weight:bold;">${tagObj.tag}</code></label>
                                <button type="button" class="button button-small" style="margin-left:10px;" onclick="navigator.clipboard.writeText('${tagObj.tag}');this.innerText='¡Copiado!';setTimeout(()=>{this.innerText='Copiar tag';},1000);">Copiar tag</button>
                                <span style="color:#888;">→</span>
                                <span style="font-family:monospace;background:#fff;padding:2px 6px;border-radius:3px;">${tagObj.example}</span>
                            </div>`;
                        });
                        html += `</div><p style="font-size:12px;color:#6c757d;margin:10px 0 0 0;">💡 Puedes desmarcar los tags que no quieras usar. Haz clic en el tag o en el botón para copiar el valor de ejemplo o el tag completo.</p><button type="submit" class="button button-primary" style="margin-top:12px;">💾 Guardar selección de tags</button></form></div>`;
                        showTestResult(html);
                        // --- Guardar tags habilitados/deshabilitados ---
                        $('#tags-enable-form').off('submit').on('submit', function(e){
                            e.preventDefault();
                            const enabledTags = [];
                            $('.tag-enable-checkbox:checked').each(function(){
                                enabledTags.push($(this).data('tag'));
                            });
                            // Guardar en la base de datos vía AJAX
                            $.post(ajaxurl, {
                                action: 'save_enabled_tags_for_endpoint',
                                nonce: '<?php echo wp_create_nonce('save_enabled_tags_for_endpoint'); ?>',
                                url: data.url,
                                name: data.name,
                                enabled_tags: enabledTags
                            }, function(resp2){
                                if (resp2.success) {
                                    showMessage('Selección de tags guardada.', 'success');
                } else {
                                    showMessage('Error al guardar la selección de tags.', 'error');
                                }
                            });
                    });
                } else {
                        showTestResult('<span style="color:#a71d2a">'+(resp.data||'Error al generar tags')+'</span>');
                    }
                });
            });
            // --- Ver Dynamic Tags ---
            $('#view-tags-btn').click(function(){
                const url = $('#endpoint_url').val().trim();
                const name = $('#endpoint_name').val().trim();
                
                // Cargar los tags dinámicamente desde el servidor con toda la información
                $.post(ajaxurl, {
                    action: 'get_tags_for_endpoint',
                    nonce: '<?php echo wp_create_nonce('get_tags_for_endpoint'); ?>',
                    url: url,
                    name: name,
                    full_info: true // Solicitar información completa
                }, function(resp) {
                    if (resp.success && resp.data) {
                        // Actualizar la variable local con los tags más recientes
                        if (resp.data.tags && resp.data.tags.length) {
                            tagsGenerated[url] = resp.data.tags;
                        }
                        
                        // Mostrar la información completa (3 secciones)
                        let html = '';
                        
                        // 1. JSON formateado
                        if (resp.data.example_json) {
                            html += `<div style="margin-bottom:18px;"><strong>📦 Respuesta de la API (primer registro o detalle):</strong><pre style="background:#f8f9fa;padding:10px;border-radius:5px;max-height:300px;overflow:auto;font-size:13px;">${resp.data.example_json}</pre></div>`;
                        }
                        
                        // 2. Estructura detectada
                        if (resp.data.example && Object.keys(resp.data.example).length > 0) {
                            html += `<div style="margin-bottom:18px;"><strong>🧩 Estructura detectada:</strong><table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;"><thead><tr style="background:#e7f3ff;"><th style="padding:6px 8px;border:1px solid #e3e3e3;">Campo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Tipo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Valor de ejemplo</th></tr></thead><tbody>`;
                            
                            Object.entries(resp.data.example).forEach(([key, value]) => {
                                let tipo = Array.isArray(value) ? 'Array' : typeof value;
                                let valEjemplo = (typeof value === 'object' && value !== null)
                                    ? JSON.stringify(value, null, 2)
                                    : value;
                                html += `<tr><td style="padding:6px 8px;border:1px solid #e3e3e3;">${key}</td><td style="padding:6px 8px;border:1px solid #e3e3e3;">${tipo}</td><td style="padding:6px 8px;border:1px solid #e3e3e3;font-family:monospace;">${valEjemplo}</td></tr>`;
                            });
                            
                            html += `</tbody></table></div>`;
                        }
                        
                        // 3. Tags dinámicos generados con checkboxes
                        if (resp.data.tags && resp.data.tags.length) {
                            const slug = resp.data.slug || '';
                            html += `<div style="margin-bottom:18px;"><strong>🏷️ Tags dinámicos generados:</strong><form id="tags-enable-form"><div style="display:grid;gap:8px;margin-top:10px;">`;
                            
                            resp.data.tags.forEach(tag => {
                                const tagObj = resp.data.tag_objects ? resp.data.tag_objects.find(t => t.tag === tag) : null;
                                const example = tagObj && tagObj.example ? tagObj.example : '';
                                
                                html += `
                                <div style="display:flex;align-items:center;">
                                    <input type="checkbox" id="tag_${tag}" name="enabled_tags[]" value="${tag}" checked style="margin-right:8px;">
                                    <label for="tag_${tag}" style="margin-right:auto;"><code>${tag}</code></label>
                                    <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('${tag}');this.innerText='¡Copiado!';setTimeout(()=>{this.innerText='Copiar tag';},1000);">Copiar tag</button>
                                </div>
                                ${example ? `<div style="margin-left:28px;margin-top:-4px;margin-bottom:8px;color:#666;font-size:12px;">Ejemplo: ${example}</div>` : ''}`;
                            });
                            
                            html += `</div><div style="margin-top:15px;"><button type="button" id="save-tags-selection" class="button button-primary">Guardar selección de tags</button></div></form></div>`;
                            
                            // Agregar nota informativa
                            html += `<p style="font-size:12px;color:#666;margin-top:5px;">✓ Puedes desmarcar los tags que no quieras usar. Haz clic en el botón para copiar el valor de ejemplo o el tag completo.</p>`;
                        }
                        
                        if (html) {
                            showTestResult(html);
                            
                            // Reiniciar el evento para guardar selección de tags
                            $('#save-tags-selection').off('click').on('click', function() {
                                const enabledTags = [];
                                $('#tags-enable-form input:checked').each(function() {
                                    enabledTags.push($(this).val());
                                });
                                
                                $.post(ajaxurl, {
                                    action: 'save_enabled_tags',
                                    nonce: '<?php echo wp_create_nonce('save_enabled_tags'); ?>',
                                    url: url,
                                    name: name,
                                    enabled_tags: enabledTags
                                }, function(saveResp) {
                                    if (saveResp.success) {
                                        showMessage('Selección de tags guardada correctamente', 'success');
                                    } else {
                                        showMessage('Error al guardar la selección de tags', 'error');
                                    }
                                });
                            });
                        } else {
                            showTestResult('<em>No hay información disponible para este endpoint.</em>');
                        }
                    } else {
                        // Intentar con los tags almacenados localmente como fallback
                        const localTags = tagsGenerated[url] || [];
                        if (localTags.length) {
                            let html = '<div style="background:#f8f9fa;padding:12px;border-radius:6px;"><strong>Dynamic Tags generados:</strong><ul style="margin:8px 0 0 18px;">';
                            localTags.forEach(tag => { html += `<li><code>${tag}</code></li>`; });
                            html += '</ul></div>';
                            showTestResult(html);
                        } else {
                            showTestResult('<em>No hay tags generados para este endpoint.</em>');
                        }
                    }
                }).fail(function() {
                    // Si falla la petición AJAX, intentar con los tags almacenados localmente
                    const localTags = tagsGenerated[url] || [];
                    if (localTags.length) {
                        let html = '<div style="background:#f8f9fa;padding:12px;border-radius:6px;"><strong>Dynamic Tags generados:</strong><ul style="margin:8px 0 0 18px;">';
                        localTags.forEach(tag => { html += `<li><code>${tag}</code></li>`; });
                        html += '</ul></div>';
                        showTestResult(html);
                    } else {
                        showTestResult('<em>No hay tags generados para este endpoint.</em>');
                    }
                });
            });
            // --- Eliminar tags y query type ---
            $('#delete-tags-btn').click(function(){
                if (!confirm('¿Seguro que quieres eliminar los tags y el query type generados para este endpoint?')) return;
                const url = $('#endpoint_url').val().trim();
                const name = $('#endpoint_name').val().trim();
                if (!name) {
                    showMessage('Debes indicar el nombre del endpoint para eliminar sus tags y query type.', 'error');
                    return;
                }
                $.post(ajaxurl, {
                    action: 'delete_tags_for_endpoint',
                    nonce: '<?php echo wp_create_nonce('delete_tags_for_endpoint'); ?>',
                    url: url,
                    name: name
                }, function(resp){
                    if (resp.success) {
                        showMessage('Tags y query type eliminados.', 'success');
                        showTagsButton(false);
                        $('#endpoint-test-result').html('');
                } else {
                        showMessage(resp.data || 'Error al eliminar tags y query type.', 'error');
                    }
                });
            });

            // --- Inicialización ---
            renderTable();
            resetForm();
            // Asegurar que editIndex sea null al cargar la página
            editIndex = null;
            // Actualizar los botones del formulario
            updateFormButtons();
        });
        </script>
        
        <style>
            /* Estilos simples para acordeones */
            .endpoint-accordion {
                transition: all 0.3s ease;
            }
            .endpoint-header {
                transition: background-color 0.3s ease;
            }
            .endpoint-header:hover {
                background: #e8e8e8 !important;
            }
            .auth-inputs {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #007cba;
            }
            
            .dynamic-tags-accordion {
                border: 1px solid #ddd;
                border-radius: 5px;
                overflow: hidden;
                margin-top: 15px;
                overflow-x: auto;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .dynamic-tags-content {
                background: #f8f9fa !important;
                padding: 15px !important;
                border-radius: 5px !important;
                border-left: 4px solid #007cba !important;
            }
            
            .tag-item {
                transition: all 0.2s ease;
                position: relative;
            }
            
            .tag-item:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border-color: #007cba !important;
            }
            
            .tag-item:active {
                transform: translateY(0);
            }
            
            .tags-grid {
                max-height: 400px;
                overflow-y: auto;
                border: 1px solid #e3e3e3;
                border-radius: 5px;
                padding: 10px;
                background: #fff;
            }
            
            .show-dynamic-tags {
                position: relative;
                margin-left: 10px;
            }
            
            .show-dynamic-tags:hover {
                background-color: #f0f0f1;
            }
            
            .endpoint-accordion:hover {
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            /* Asegurar que el acordeón sea visible */
            .dynamic-tags-accordion[style*="display: block"] {
                display: block !important;
            }
            
            .tags-list[style*="display: block"] {
                display: block !important;
            }
            
            /* Mejorar la apariencia de los códigos */
            .tag-item code {
                font-size: 11px;
                font-weight: bold;
                word-break: break-all;
            }
            
            @media (max-width: 782px) {
                .tags-grid {
                    grid-template-columns: 1fr;
                    max-height: 300px;
                }
                
                .dynamic-tags-content {
                    padding: 10px !important;
                }
                
                .show-dynamic-tags {
                    margin-left: 5px;
                    margin-top: 5px;
                }
            }
            
            .wrap {
                margin-bottom: 0 !important;
            }
            .endpoints-container, #endpoints-container {
                margin-bottom: 0 !important;
            }
            #wpfooter { display: none !important; }
        </style>
        </form>
    </div>
    <script>
    window.bricksApiIntegrator = {
      ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
      saveSingleApiEndpointNonce: '<?php echo wp_create_nonce('save_single_api_endpoint'); ?>'
    };
    </script>
        <?php
    }
}

// === HANDLERS AJAX PARA ENDPOINTS ===
add_action('wp_ajax_save_api_endpoint', function() {
    check_ajax_referer('save_api_endpoint', 'nonce');
    $endpoints = get_option('bricks_api_endpoints', []);
    
    // Manejar el caso especial 'new' para crear siempre un nuevo endpoint
    if (isset($_POST['index']) && $_POST['index'] === 'new') {
        $index = null; // Forzar creación de nuevo endpoint
    } else {
        $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : null;
    }
    
    // --- LOG TEMPORAL: Verificar los datos recibidos ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('ENDPOINT AJAX - dynamic_params recibido: ' . print_r($_POST['dynamic_params'], true));
    }
    $dynamic_params = [];
    if (!empty($_POST['dynamic_params']) && is_array($_POST['dynamic_params'])) {
        foreach ($_POST['dynamic_params'] as $param) {
            if (!empty($param['name'])) {
                $dynamic_params[] = [
                    'name' => sanitize_text_field($param['name']),
                    'source' => sanitize_text_field($param['source'] ?? 'url'),
                    'default' => sanitize_text_field($param['default'] ?? '')
                ];
            }
        }
    }
    // Procesar field transformers
    $field_transformers = [];
    if (!empty($_POST['field_transformers']) && is_array($_POST['field_transformers'])) {
        foreach ($_POST['field_transformers'] as $transformer) {
            if (!empty($transformer['field']) && !empty($transformer['type'])) {
                $field_transformers[] = [
                    'field' => sanitize_text_field($transformer['field']),
                    'type' => sanitize_text_field($transformer['type']),
                    'endpoint_url' => sanitize_text_field($transformer['endpoint_url'] ?? ''),
                    'template' => sanitize_text_field($transformer['template'] ?? ''),
                    'prefix_value' => sanitize_text_field($transformer['prefix_value'] ?? ''),
                    'suffix_value' => sanitize_text_field($transformer['suffix_value'] ?? ''),
                    'params' => !empty($transformer['params']) ? $transformer['params'] : []
                ];
            }
        }
    }

    // Si el array está vacío, se guarda vacío (no se mantienen los anteriores)
    $data = [
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'url' => preg_replace('/[^a-zA-Z0-9\-\_\:\/\.\?\=\&\{\}]/', '', $_POST['url'] ?? ''),
        'auth_type' => sanitize_text_field($_POST['auth_type'] ?? 'none'),
        'token' => sanitize_text_field($_POST['token'] ?? ''),
        'basic_user' => sanitize_text_field($_POST['basic_user'] ?? ''),
        'basic_password' => trim($_POST['basic_password'] ?? ''),
        'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
        'api_key_header' => sanitize_text_field($_POST['api_key_header'] ?? 'X-API-Key'),
        'dynamic_params' => $dynamic_params,
        'field_transformers' => $field_transformers,
    ];
    // Verificar si estamos editando o creando un nuevo endpoint
    if ($index !== null && isset($endpoints[$index])) {
        // Modo edición: actualizar endpoint existente
        $endpoints[$index] = $data;
    } else {
        // Modo creación: agregar nuevo endpoint
        $endpoints[] = $data;
    }
    // Guardar los endpoints actualizados
    update_option('bricks_api_endpoints', $endpoints);
    // --- LOG TEMPORAL: Verificar los endpoints guardados ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('ENDPOINT AJAX - endpoints guardados: ' . print_r($endpoints, true));
    }
    // Verificar que los endpoints se guardaron correctamente
    $saved_endpoints = get_option('bricks_api_endpoints', []);
    wp_send_json_success(['endpoints' => $endpoints]);
});

add_action('wp_ajax_delete_api_endpoint', function() {
    check_ajax_referer('delete_api_endpoint', 'nonce');
    $endpoints = get_option('bricks_api_endpoints', []);
    $index = isset($_POST['index']) ? intval($_POST['index']) : null;
    if ($index !== null && isset($endpoints[$index])) {
        array_splice($endpoints, $index, 1);
        update_option('bricks_api_endpoints', $endpoints);
        wp_send_json_success(['endpoints' => $endpoints]);
    } else {
        wp_send_json_error('No se encontró el endpoint');
    }
});

add_action('wp_ajax_test_api_endpoint', function() {
    check_ajax_referer('test_api_endpoint', 'nonce');
    $url = preg_replace('/[^a-zA-Z0-9\-\_\:\/\.\?\=\&\{\}]/', '', $_POST['url'] ?? '');
    $auth_type = sanitize_text_field($_POST['auth_type'] ?? 'none');
    $args = [ 'headers' => [] , 'timeout' => 15 ];
    if ($auth_type === 'token' && !empty($_POST['token'])) {
        $args['headers']['Authorization'] = 'Bearer ' . sanitize_text_field($_POST['token']);
    } elseif ($auth_type === 'basic' && !empty($_POST['basic_user']) && isset($_POST['basic_password'])) {
        $args['headers']['Authorization'] = 'Basic ' . base64_encode(sanitize_text_field($_POST['basic_user']) . ':' . trim($_POST['basic_password']));
    } elseif ($auth_type === 'api_key' && !empty($_POST['api_key'])) {
        $header = !empty($_POST['api_key_header']) ? sanitize_text_field($_POST['api_key_header']) : 'X-API-Key';
        $args['headers'][$header] = sanitize_text_field($_POST['api_key']);
    }
    // Agregar parámetros dinámicos (solo los de tipo 'static' o 'url' con valor por defecto)
    if (!empty($_POST['dynamic_params']) && is_array($_POST['dynamic_params'])) {
        $params = [];
        foreach ($_POST['dynamic_params'] as $param) {
            if (!empty($param['name']) && !empty($param['default']) && in_array($param['source'], ['static','url'])) {
                $params[$param['name']] = $param['default'];
            }
        }
        if ($params) {
            $url = add_query_arg($params, $url);
        }
    }
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        wp_send_json_error('Error: ' . $response->get_error_message());
    }
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $html = '<div><strong>HTTP ' . esc_html($code) . '</strong></div>';
    if ($code >= 200 && $code < 300) {
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $html .= '<div style="margin-top:8px;"><strong>Respuesta JSON:</strong><pre style="max-height:200px;overflow:auto;background:#f8f9fa;padding:8px;border-radius:4px;">' . esc_html(print_r($data, true)) . '</pre></div>';
            // Mostrar campos detectados correctamente
            if (is_array($data)) {
                $fields = [];
                // Si es un array de objetos, tomar las keys del primer elemento
                if (isset($data[0]) && is_array($data[0])) {
                    $fields = array_keys($data[0]);
                } elseif (!empty($data) && !isset($data[0])) {
                    // Si es un objeto único (array asociativo)
                    $fields = array_keys($data);
                }

                if (count($fields)) {
                    $html .= '<div style="margin-top:8px;"><strong>Campos detectados:</strong> ' . esc_html(implode(', ', $fields)) . '</div>';
                }
            }
        } else {
            $html .= '<div style="color:#a71d2a;">Respuesta no es JSON válido.</div>';
        }
    } else {
        $html .= '<div style="color:#a71d2a;">Respuesta inesperada o error.</div>';
    }
    wp_send_json_success(['html' => $html]);
});

add_action('wp_ajax_generate_tags_for_endpoint', function() {
    check_ajax_referer('generate_tags_for_endpoint', 'nonce');
    require_once __DIR__ . '/field-extractor.php';

    $url = preg_replace('/[^a-zA-Z0-9\-\_\:\/\.\?\=\&\{\}]/', '', $_POST['url'] ?? '');
    $name = sanitize_text_field($_POST['name'] ?? '');
    $auth_type = sanitize_text_field($_POST['auth_type'] ?? 'none');
    $endpoint_index = isset($_POST['endpoint_index']) && $_POST['endpoint_index'] !== '' ? intval($_POST['endpoint_index']) : null;
    $tags = [];
    $query_type = '';
    $group_title = '';
    $slug = '';
    $example = [];
    $example_json = '';

    if ($url && $name) {
        $slug = bricks_api_normalize_slug($name);
        $query_type = '{snap_ep_' . $slug . '}';
        $group_title = $name . ' (Endpoint)';

        // Preparar headers de autenticación (igual que en test_api_endpoint)
        $args = [ 'headers' => [] , 'timeout' => 15 ];
        if ($auth_type === 'token' && !empty($_POST['token'])) {
            $args['headers']['Authorization'] = 'Bearer ' . sanitize_text_field($_POST['token']);
        } elseif ($auth_type === 'basic' && !empty($_POST['basic_user']) && isset($_POST['basic_password'])) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode(sanitize_text_field($_POST['basic_user']) . ':' . trim($_POST['basic_password']));
        } elseif ($auth_type === 'api_key' && !empty($_POST['api_key'])) {
            $header = !empty($_POST['api_key_header']) ? sanitize_text_field($_POST['api_key_header']) : 'X-API-Key';
            $args['headers'][$header] = sanitize_text_field($_POST['api_key']);
        }

        // Agregar parámetros dinámicos (solo los de tipo 'static' o 'url' con valor por defecto)
        if (!empty($_POST['dynamic_params']) && is_array($_POST['dynamic_params'])) {
            $params = [];
            foreach ($_POST['dynamic_params'] as $param) {
                if (!empty($param['name']) && !empty($param['default']) && in_array($param['source'], ['static','url'])) {
                    $params[$param['name']] = $param['default'];
                }
            }
            if ($params) {
                $url = add_query_arg($params, $url);
            }
        }

        // Obtener datos del endpoint CON autenticación
        $response = wp_remote_get($url, $args);
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            // Verificar código de respuesta
            if ($code >= 200 && $code < 300) {
                $data = json_decode($body, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Detectar si es array de objetos o un objeto único
                    if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                        $example = $data[0];
                    } elseif (is_array($data)) {
                        $example = $data;
                    } else {
                        $example = [];
                    }
                    $example_json = json_encode($example, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

                    // Obtener transformadores: primero desde el formulario, luego del filtro global
                    $transformers = [];
                    if (!empty($_POST['field_transformers']) && is_array($_POST['field_transformers'])) {
                        $transformers = $_POST['field_transformers'];
                    } else {
                        $transformers = apply_filters('bricks_api_field_transformers', []);
                    }

                    // Generar tags recursivos a partir del ejemplo con transformadores
                    $tags = function_exists('bricks_api_extract_tags_recursive')
                        ? bricks_api_extract_tags_recursive($example, 'snap_' . $slug, '', 5, $transformers)
                        : [];
                } else {
                    wp_send_json_error('La API no devolvió un JSON válido. Respuesta: ' . substr($body, 0, 200));
                    return;
                }
            } else {
                wp_send_json_error('La API devolvió un error HTTP ' . $code . ': ' . substr($body, 0, 200));
                return;
            }
        } else {
            wp_send_json_error('Error al conectar con la API: ' . $response->get_error_message());
            return;
        }
        // Guardar en opciones dedicadas
        $query_types = get_option('bricks_api_generated_query_types', []);
        $tags_data = get_option('bricks_api_generated_tags', []);
        $query_types[$slug] = [
            'query_type' => $query_type,
            'endpoint_name' => $name,
            'endpoint_id' => $endpoint_index,
            'group_title' => $group_title,
            'url' => $url,
            'fields' => array_column($tags, 'tag'),
            'example' => $example
        ];
        $tags_data[$slug] = [
            'tags' => array_column($tags, 'tag'),
            'group_title' => $group_title,
            'endpoint_name' => $name,
            'example' => $example
        ];
        update_option('bricks_api_generated_query_types', $query_types);
        update_option('bricks_api_generated_tags', $tags_data);
    }
    if ($tags) {
        wp_send_json_success([
            'message' => 'Query Type y tags generados.',
            'tags' => $tags,
            'query_type' => $query_type,
            'group_title' => $group_title,
            'slug' => $slug,
            'example' => $example,
            'example_json' => $example_json
        ]);
    } else {
        wp_send_json_error('No se pudieron generar tags.');
    }
});

add_action('wp_ajax_get_tags_for_endpoint', function() {
    check_ajax_referer('get_tags_for_endpoint', 'nonce');
    $url = isset($_POST['url']) ? preg_replace('/[^a-zA-Z0-9\-\_\:\/\.\?\=\&\{\}]/', '', $_POST['url']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $full_info = isset($_POST['full_info']) && $_POST['full_info'];
    
    if (!$name && !$url) {
        wp_send_json_error('Nombre o URL de endpoint no especificado');
        return;
    }
    
    // Buscar tags dinámicos para este endpoint
    $dynamic_tags = get_option('bricks_api_generated_tags', []);
    $query_types = get_option('bricks_api_generated_query_types', []);
    $tags = [];
    $slug = '';
    $example = [];
    $example_json = '';
    $tag_objects = [];
    
    // Primero intentar buscar por nombre
    if ($name) {
        $slug = bricks_api_normalize_slug($name);
        if (isset($dynamic_tags[$slug]) && !empty($dynamic_tags[$slug]['tags'])) {
            $tags = $dynamic_tags[$slug]['tags'];
            if ($full_info && isset($dynamic_tags[$slug]['example'])) {
                $example = $dynamic_tags[$slug]['example'];
            }
        }
    }
    
    // Si no se encontraron tags por nombre, intentar buscar por URL en los query types
    if ((empty($tags) || empty($example)) && $url) {
        foreach ($query_types as $qt_slug => $qt) {
            if (isset($qt['url']) && $qt['url'] === $url) {
                if (isset($dynamic_tags[$qt_slug]) && !empty($dynamic_tags[$qt_slug]['tags'])) {
                    $tags = $dynamic_tags[$qt_slug]['tags'];
                    $slug = $qt_slug;
                    if ($full_info && isset($qt['example'])) {
                        $example = $qt['example'];
                    }
                    break;
                }
            }
        }
    }
    
    if (empty($tags)) {
        wp_send_json_error('No se encontraron tags para este endpoint');
        return;
    }
    
    // Si se solicita información completa, preparar los datos adicionales
    if ($full_info && !empty($example)) {
        // Generar JSON formateado
        $example_json = json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Preparar objetos de tag con ejemplos
        if (function_exists('bricks_api_extract_tags_recursive')) {
            $tag_objects = bricks_api_extract_tags_recursive($example, 'snap_' . $slug);
        }
        
        wp_send_json_success([
            'tags' => $tags,
            'example' => $example,
            'example_json' => $example_json,
            'tag_objects' => $tag_objects,
            'slug' => $slug
        ]);
    } else {
        wp_send_json_success(['tags' => $tags]);
    }
});

add_action('wp_ajax_delete_tags_for_endpoint', function() {
    check_ajax_referer('delete_tags_for_endpoint', 'nonce');
    require_once __DIR__ . '/field-extractor.php';
    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = '';
    if ($name) {
        $slug = bricks_api_normalize_slug($name);
        $query_types = get_option('bricks_api_generated_query_types', []);
        $tags_data = get_option('bricks_api_generated_tags', []);
        unset($query_types[$slug]);
        unset($tags_data[$slug]);
        update_option('bricks_api_generated_query_types', $query_types);
        update_option('bricks_api_generated_tags', $tags_data);
        wp_send_json_success('Query Type y tags eliminados.');
    } else {
        wp_send_json_error('No se pudo eliminar: nombre de endpoint vacío.');
    }
});

add_action('wp_ajax_save_enabled_tags_for_endpoint', function() {
    check_ajax_referer('save_enabled_tags_for_endpoint', 'nonce');
    require_once __DIR__ . '/field-extractor.php';
    $name = sanitize_text_field($_POST['name'] ?? '');
    $enabled_tags = isset($_POST['enabled_tags']) && is_array($_POST['enabled_tags']) ? array_map('sanitize_text_field', $_POST['enabled_tags']) : [];
    if (!$name) {
        wp_send_json_error('Nombre de endpoint vacío.');
    }
    $slug = bricks_api_normalize_slug($name);
    $tags_data = get_option('bricks_api_generated_tags', []);
    if (!isset($tags_data[$slug])) {
        wp_send_json_error('No se encontró el endpoint para guardar los tags.');
    }
    // Guardar solo los tags habilitados
    $tags_data[$slug]['tags'] = $enabled_tags;
    update_option('bricks_api_generated_tags', $tags_data);
    // También actualizar en query_types para coherencia
    $query_types = get_option('bricks_api_generated_query_types', []);
    if (isset($query_types[$slug])) {
        $query_types[$slug]['fields'] = $enabled_tags;
        update_option('bricks_api_generated_query_types', $query_types);
    }
    wp_send_json_success('Tags habilitados guardados correctamente.');
});

add_action('wp_ajax_get_saved_tags_for_endpoint', function() {
    check_ajax_referer('get_saved_tags_for_endpoint', 'nonce');
    require_once __DIR__ . '/field-extractor.php';
    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = '';
    if ($name) {
        $slug = bricks_api_normalize_slug($name);
        $tags_data = get_option('bricks_api_generated_tags', []);
        if (isset($tags_data[$slug])) {
            $tags = $tags_data[$slug]['tags'] ?? [];
            $example = $tags_data[$slug]['example'] ?? [];
            $example_json = json_encode($example, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

            // Obtener transformadores del endpoint
            $transformers = [];
            $endpoints = get_option('bricks_api_endpoints', []);
            foreach ($endpoints as $endpoint) {
                if (isset($endpoint['name']) && bricks_api_normalize_slug($endpoint['name']) === $slug) {
                    $transformers = $endpoint['field_transformers'] ?? [];
                    break;
                }
            }

            // Si los tags guardados son solo strings, reconstruir la estructura avanzada con transformadores
            if ($tags && is_string($tags[0])) {
                // Volver a extraer la estructura avanzada de tags CON transformadores
                $tags = function_exists('bricks_api_extract_tags_recursive') ? bricks_api_extract_tags_recursive($example, 'snap_' . $slug, '', 5, $transformers) : [];
            }
            wp_send_json_success([
                'tags' => $tags,
                'example' => $example,
                'example_json' => $example_json
            ]);
        }
    }
    wp_send_json_error('No hay tags guardados para este endpoint.');
});
