<?php
// Renderizado y lógica de administración de Sources para Bricks API Integrator

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

// --- Render de la página de administración de Sources ---

function render_api_sources_page() {
    // Check if form was submitted
    if (isset($_POST['submit_api_source']) && check_admin_referer('bricks_api_source_nonce')) {
        save_api_source();
    }
    
    // Check if we're editing an existing source
    $editing = false;
    $source_to_edit = null;
    $source_id_to_edit = '';
    
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['source_id'])) {
        $source_id_to_edit = sanitize_text_field($_GET['source_id']);
        $api_sources = get_option('bricks_api_sources', []);
        
        if (isset($api_sources[$source_id_to_edit])) {
            $editing = true;
            $source_to_edit = $api_sources[$source_id_to_edit];
        }
    }

    // Get existing sources
    $api_sources = get_option('bricks_api_sources', []);
    
    // Get configured endpoints for selection
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Query Types para Bricks Builder', 'bricks-api-integrator'); ?></h1>
        
        <?php settings_errors('bricks_api_sources'); ?>
        
        <?php if (empty($endpoints)): ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('No API endpoints configured yet. Please add endpoints first.', 'bricks-api-integrator'); ?></p>
                <p><a href="<?php echo admin_url('admin.php?page=bricks-api-integrator'); ?>" class="button"><?php esc_html_e('Configure Endpoints', 'bricks-api-integrator'); ?></a></p>
            </div>
        <?php else: ?>
            <!-- Add New Query Type Form -->
            <div class="api-source-form-container">
                <h2><?php echo $editing ? esc_html__('Editar Query Type', 'bricks-api-integrator') : esc_html__('Añadir Nuevo Query Type', 'bricks-api-integrator'); ?></h2>
                <form id="source-form" method="post" action="">
                    <?php wp_nonce_field('bricks_api_source_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="source_name"><?php esc_html_e('Nombre del Query Type', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="source_name" name="source_name" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['name']) : ''; ?>" required>
                                <?php if ($editing): ?>
                                    <input type="hidden" name="source_id" value="<?php echo esc_attr($source_id_to_edit); ?>">
                                <?php endif; ?>
                                <p class="description"><?php esc_html_e('Este nombre aparecerá en el selector de Query Loop de Bricks.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="endpoint_id"><?php esc_html_e('API Endpoint', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="endpoint_id" name="endpoint_id" required>
                                    <option value=""><?php esc_html_e('-- Select Endpoint --', 'bricks-api-integrator'); ?></option>
                                    <?php foreach ($endpoints as $index => $endpoint): ?>
                                        <?php $endpoint_name = isset($endpoint['name']) ? $endpoint['name'] : __('Unnamed Endpoint', 'bricks-api-integrator'); ?>
                                        <option value="<?php echo esc_attr($index); ?>" <?php selected($editing && $source_to_edit['endpoint_id'] == $index); ?>><?php echo esc_html($endpoint_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field_prefix"><?php esc_html_e('Prefijo de Campo', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="field_prefix" name="field_prefix" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['field_prefix']) : ''; ?>" placeholder="snap_">
                                <p class="description"><?php esc_html_e('Prefijo opcional para nombres de campo en Bricks. Dejar vacío para no usar prefijo.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="items_path"><?php esc_html_e('Ruta de Elementos', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <div class="items-path-container" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="text" id="items_path" name="items_path" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['items_path']) : ''; ?>" style="flex-grow: 1;">
                                    <?php if ($editing && !empty($source_to_edit['endpoint_id'])): ?>
                                        <?php $test_path_nonce = wp_create_nonce('bricks_api_source_nonce'); ?>
                                        <button type="button" class="button test-items-path" 
                                            data-endpoint-id="<?php echo esc_attr($source_to_edit['endpoint_id']); ?>" 
                                            data-source-id="<?php echo esc_attr($source_id_to_edit); ?>"
                                            data-nonce="<?php echo esc_attr($test_path_nonce); ?>">
                                            <?php esc_html_e('Probar Ruta', 'bricks-api-integrator'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($editing && !empty($source_to_edit['endpoint_id'])): ?>
                                <div class="items-path-filter" style="margin-top: 10px; display: none; gap: 10px;">
                                    <input type="text" id="filter_field" placeholder="<?php esc_attr_e('Campo de filtro (ej: estado)', 'bricks-api-integrator'); ?>" class="regular-text" style="flex: 1;">
                                    <input type="text" id="filter_value" placeholder="<?php esc_attr_e('Valor (ej: true)', 'bricks-api-integrator'); ?>" class="regular-text" style="flex: 1;">
                                </div>
                                <?php endif; ?>
                                
                                <p class="description"><?php esc_html_e('Ruta al array de elementos en la respuesta de la API (ej. "data" o "results"). Dejar vacío si los elementos están en el nivel raíz.', 'bricks-api-integrator'); ?></p>
                                <div id="items-path-result" style="margin-top: 10px; display: none;">
                                    <div class="notice notice-info inline">
                                        <p><strong><?php esc_html_e('Resultado de la prueba:', 'bricks-api-integrator'); ?></strong> <span id="items-path-message"></span></p>
                                        <pre id="items-path-preview" style="max-height: 200px; overflow: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin-top: 10px; display: none;"></pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dynamic_params"><?php esc_html_e('Parámetros Dinámicos', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <div id="dynamic-params-container">
                                    <?php
                                    $pagination_param = $editing && !empty($source_to_edit['pagination_param']) ? $source_to_edit['pagination_param'] : '';
                                    $per_page_param = $editing && !empty($source_to_edit['per_page_param']) ? $source_to_edit['per_page_param'] : '';
                                    if ($editing && !empty($source_to_edit['dynamic_params'])):
                                        foreach ($source_to_edit['dynamic_params'] as $param):
                                            // No mostrar parámetros que coincidan con los de paginación
                                            if ($param['name'] === $pagination_param || $param['name'] === $per_page_param) continue;
                                    ?>
                                            <div class="dynamic-param-row">
                                                <input type="text" name="param_names[]" placeholder="<?php esc_attr_e('Nombre del Parámetro (ej. id)', 'bricks-api-integrator'); ?>" class="regular-text" value="<?php echo esc_attr($param['name']); ?>">
                                                <select name="param_sources[]">
                                                    <option value="url" <?php selected($param['source'], 'url'); ?>><?php esc_html_e('Parámetro URL', 'bricks-api-integrator'); ?></option>
                                                    <option value="post" <?php selected($param['source'], 'post'); ?>><?php esc_html_e('ID del Post', 'bricks-api-integrator'); ?></option>
                                                    <option value="user" <?php selected($param['source'], 'user'); ?>><?php esc_html_e('ID del Usuario', 'bricks-api-integrator'); ?></option>
                                                    <option value="static" <?php selected($param['source'], 'static'); ?>><?php esc_html_e('Valor Estático', 'bricks-api-integrator'); ?></option>
                                                </select>
                                                <input type="text" name="param_defaults[]" placeholder="<?php esc_attr_e('Valor por Defecto (opcional)', 'bricks-api-integrator'); ?>" class="regular-text" value="<?php echo esc_attr($param['default']); ?>">
                                                <button type="button" class="button button-delete-param" style="background:#dc3232;color:#fff;">Eliminar</button>
                                            </div>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                                <button type="button" class="button" id="add-param"><?php esc_html_e('Añadir Parámetro', 'bricks-api-integrator'); ?></button>
                                <p class="description"><?php esc_html_e('Define parámetros para pasar a la API. Para parámetros URL, el valor se tomará de la cadena de consulta de la URL actual.', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pagination_type"><?php esc_html_e('Tipo de Paginación', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <select id="pagination_type" name="pagination_type">
                                    <option value="none" <?php selected($editing && $source_to_edit['pagination_type'] == 'none'); ?>><?php esc_html_e('Ninguno', 'bricks-api-integrator'); ?></option>
                                    <option value="page_param" <?php selected($editing && $source_to_edit['pagination_type'] == 'page_param'); ?>><?php esc_html_e('Parámetro de Página', 'bricks-api-integrator'); ?></option>
                                    <option value="offset_param" <?php selected($editing && $source_to_edit['pagination_type'] == 'offset_param'); ?>><?php esc_html_e('Parámetro de Desplazamiento', 'bricks-api-integrator'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr class="pagination-param-row" style="display: none;">
                            <th scope="row">
                                <label for="pagination_param"><?php esc_html_e('Pagination Parameter', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="pagination_param" name="pagination_param" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['pagination_param']) : ''; ?>">
                                <p class="description"><?php esc_html_e('Parameter name for pagination (e.g., "page" or "offset").', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                        <tr class="per-page-param-row" style="display: none;">
                            <th scope="row">
                                <label for="per_page_param"><?php esc_html_e('Per Page Parameter', 'bricks-api-integrator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="per_page_param" name="per_page_param" class="regular-text" value="<?php echo $editing ? esc_attr($source_to_edit['per_page_param']) : ''; ?>">
                                <p class="description"><?php esc_html_e('Parameter name for items per page (e.g., "per_page" or "limit").', 'bricks-api-integrator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit_api_source" class="button button-primary" value="<?php echo $editing ? esc_attr__('Actualizar Query Type', 'bricks-api-integrator') : esc_attr__('Guardar Query Type', 'bricks-api-integrator'); ?>">
                        <?php if ($editing): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bricks-api-integrator-sources')); ?>" class="button" style="margin-left: 10px;"><?php esc_html_e('Cancelar', 'bricks-api-integrator'); ?></a>
                        <?php endif; ?>
                    </p>
                    <div class="source-actions" style="display:flex;gap:8px;align-items:center;margin-top:18px;">
                        <button type="button" class="button button-test-source" id="test-source-btn">🧪 Test Source</button>
                        <button type="button" class="button button-create-tags" id="generate-source-tags-btn">⚡ Crear tags y query types dinámicos</button>
                        <button type="button" class="button button-view-tags" id="view-source-tags-btn" style="display:none;">🏷️ Ver Dynamic Tags</button>
                        <button type="button" class="button button-delete-tags" id="delete-source-tags-btn" style="display:none;background:#dc3232;color:#fff;">🗑️ Eliminar tags y query type</button>
                    </div>
                </form>
                <div id="source-test-result" style="margin-top:30px;"></div>
            </div>
            
            <!-- Existing Query Types List -->
            <div class="api-sources-list">
                <h2><?php esc_html_e('Query Types Configurados', 'bricks-api-integrator'); ?></h2>
                
                <?php if (empty($api_sources)): ?>
                    <p><?php esc_html_e('No hay query types configurados todavía.', 'bricks-api-integrator'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Nombre del Query Type', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Endpoint', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Prefijo de Campo', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Ruta de Elementos', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Paginación', 'bricks-api-integrator'); ?></th>
                                <th><?php esc_html_e('Acciones', 'bricks-api-integrator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_sources as $source_id => $source): ?>
                                <tr>
                                    <td><?php echo esc_html($source['name']); ?></td>
                                    <td>
                                        <?php 
                                        $endpoint_index = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
                                        echo isset($endpoints[$endpoint_index]['name']) ? esc_html($endpoints[$endpoint_index]['name']) : esc_html__('Endpoint Desconocido', 'bricks-api-integrator');
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($source['field_prefix'] ?? ''); ?></td>
                                    <td><?php echo esc_html($source['items_path'] ?? ''); ?></td>
                                    <td>
                                        <?php 
                                        $pagination_type = isset($source['pagination_type']) ? $source['pagination_type'] : 'none';
                                        if ($pagination_type === 'none') {
                                            esc_html_e('Ninguno', 'bricks-api-integrator');
                                        } else {
                                            echo esc_html($pagination_type) . ': ' . esc_html($source['pagination_param'] ?? '');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=bricks-api-integrator-sources&action=edit&source_id=' . $source_id)); ?>" class="button button-small"><?php esc_html_e('Edit', 'bricks-api-integrator'); ?></a>
                                        <button type="button" class="button button-small query-type-preview-btn" data-source-id="<?php echo esc_attr($source_id); ?>" data-source-name="<?php echo esc_attr($source['name']); ?>" style="background: #007cba; color: white; margin-left: 5px;">🔍 Preview</button>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=bricks-api-integrator-sources&action=delete&source_id=' . $source_id), 'delete_api_source_' . $source_id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres eliminar este query type?', 'bricks-api-integrator'); ?>')"><?php esc_html_e('Eliminar', 'bricks-api-integrator'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php $bricks_api_source_nonce = wp_create_nonce('bricks_api_source_nonce'); ?>
    <script>
    window.bricksApiSourceNonce = '<?php echo esc_js($bricks_api_source_nonce); ?>';
    </script>
    <script>
    jQuery(document).ready(function($){
        // --- Parámetros dinámicos ---
        function addParamRow(name='', source='url', def='') {
            const row = $(
                `<div class="dynamic-param-row" style="margin-bottom:6px;display:flex;gap:6px;align-items:center;">
                    <input type="text" class="regular-text param-name" placeholder="Nombre" value="${name}">
                    <select class="param-source">
                        <option value="url" ${source==='url'?'selected':''}>Parámetro URL</option>
                        <option value="post" ${source==='post'?'selected':''}>ID Post</option>
                        <option value="user" ${source==='user'?'selected':''}>ID Usuario</option>
                        <option value="static" ${source==='static'?'selected':''}>Valor Estático</option>
                    </select>
                    <input type="text" class="regular-text param-default" placeholder="Valor por defecto" value="${def}">
                    <button type="button" class="button button-delete-param" style="background:#dc3232;color:#fff;">Eliminar</button>
                </div>`
            );
            row.find('.button-delete-param').click(function(){ row.remove(); });
            $('#dynamic-params-container').append(row);
        }
        $('#add-param').click(function(){ addParamRow(); });
        // --- Modal Preview centrado y cierre robusto ---
        if($('#bricks-source-preview-modal').length === 0){
            $('body').append('<div id="bricks-source-preview-modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;"><div style="background:#fff;padding:28px 24px 18px 24px;border-radius:10px;max-width:600px;max-height:80vh;overflow:auto;position:relative;"><button id="close-source-preview-modal" style="position:absolute;top:10px;right:10px;font-size:18px;background:none;border:none;cursor:pointer;">✖</button><div id="bricks-source-preview-content"></div></div></div>');
        }
        $('#bricks-source-preview-modal').hide();
        function showSourcePreviewModal(contentHtml) {
            $('#bricks-source-preview-content').html(contentHtml);
            $('#bricks-source-preview-modal').fadeIn(120);
        }
        function closeSourcePreviewModal() {
            $('#bricks-source-preview-modal').fadeOut(120, function(){
                $('#bricks-source-preview-content').html('');
            });
        }
        $(document).on('click','#close-source-preview-modal',function(){
            closeSourcePreviewModal();
        });
        $(document).on('mousedown',function(e){
            var $modal = $('#bricks-source-preview-modal');
            if($modal.is(':visible')){
                var $content = $modal.find('>div');
                if(!$content.is(e.target) && $content.has(e.target).length === 0){
                    closeSourcePreviewModal();
                }
            }
        });
        $(document).on('click','.query-type-preview-btn',function(){
            var sourceId = $(this).data('source-id');
            var sourceName = $(this).data('source-name');
            showSourcePreviewModal('<em>Consultando API...</em>');
            $.post(ajaxurl, {action:'preview_source_api', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
                if(res.success){
                    var html = '<h3 style="margin-top:0">Preview: '+sourceName+'</h3>';
                    if (res.data.endpoint) {
                        var endpoint = res.data.endpoint;
                        html += '<div style="background:#f0f7ff;padding:12px;border-radius:6px;margin-top:10px;border-left:3px solid #007cba;">';
                        html += '<h4 style="margin-top:0;margin-bottom:8px;color:#007cba;">Información del Endpoint</h4>';
                        html += '<table style="width:100%;border-collapse:collapse;">';
                        if (endpoint.name) html += '<tr><td style="padding:4px 0;font-weight:bold;width:120px;">Nombre:</td><td>'+endpoint.name+'</td></tr>';
                        if (endpoint.url) html += '<tr><td style="padding:4px 0;font-weight:bold;">URL:</td><td><code style="background:#e9f5fe;padding:2px 4px;border-radius:3px;word-break:break-all;">'+endpoint.url+'</code></td></tr>';
                        if (endpoint.method) html += '<tr><td style="padding:4px 0;font-weight:bold;">Método:</td><td>'+endpoint.method+'</td></tr>';
                        if (endpoint.auth_type && endpoint.auth_type !== 'none') html += '<tr><td style="padding:4px 0;font-weight:bold;">Autenticación:</td><td>'+endpoint.auth_type+'</td></tr>';
                        html += '</table>';
                        html += '</div>';
                    }
                    html += '<div style="background:#f7f7f7;padding:12px;border-radius:6px;margin-top:10px;border-left:3px solid #555;">';
                    html += '<h4 style="margin-top:0;margin-bottom:8px;">Configuración de Datos</h4>';
                    html += '<table style="width:100%;border-collapse:collapse;">';
                    html += '<tr><td style="padding:4px 0;font-weight:bold;width:120px;">Ruta de elementos:</td><td><code style="background:#eee;padding:2px 4px;border-radius:3px;">'+(res.data.items_path || 'Raíz de la respuesta')+'</code></td></tr>';
                    if (res.data.total_items !== undefined) {
                        html += '<tr><td style="padding:4px 0;font-weight:bold;">Total de elementos:</td><td>'+res.data.total_items+'</td></tr>';
                    }
                    html += '</table>';
                    html += '</div>';
                    html += '<div style="background:#f8f9fa;padding:12px;border-radius:6px;margin-top:10px;border-left:3px solid #4caf50;">';
                    html += '<h4 style="margin-top:0;margin-bottom:8px;color:#2e7d32;">Datos del Primer Elemento</h4>';
                    html += '<pre style="max-height:300px;overflow:auto;background:#fff;padding:10px;border-radius:4px;margin-top:5px;border:1px solid #e0e0e0;">'+(typeof res.data.preview === 'object' ? JSON.stringify(res.data.preview, null, 2) : res.data.preview)+'</pre>';
                    html += '</div>';
                    if(res.data.fields && res.data.fields.length){
                        html += '<div style="background:#fef8e8;padding:12px;border-radius:6px;margin-top:10px;border-left:3px solid #f9a825;">';
                        html += '<h4 style="margin-top:0;margin-bottom:8px;color:#f57c00;">Campos Disponibles</h4>';
                        html += '<div style="display:flex;flex-wrap:wrap;gap:6px;">';
                        res.data.fields.forEach(function(field) {
                            html += '<span style="background:#fff3e0;padding:3px 8px;border-radius:20px;font-size:12px;border:1px solid #ffe0b2;">'+field+'</span>';
                        });
                        html += '</div>';
                        html += '</div>';
                    }
                    showSourcePreviewModal(html);
                }else{
                    showSourcePreviewModal('<span style="color:#c00">'+res.data+'</span>');
                }
            });
        });
    });
    </script>
    <style>
    .button-create-tags { background: #ff9800 !important; color: #fff !important; border: none; }
    .button-create-tags:hover { background: #e65100 !important; }
    .button-view-tags { background: #00b894 !important; color: #fff !important; border: none; }
    .button-view-tags:hover { background: #008c6e !important; }
    .button-delete-tags { background: #dc3232 !important; color: #fff !important; border: none; }
    .button-delete-tags:hover { background: #a71d2a !important; }
    .button-test-source { background: #fff !important; color: #27ae60 !important; border: 1px solid #27ae60 !important; }
    .button-test-source:hover { background: #e9fbe5 !important; }
    .button-refresh-source { background: #fff !important; color: #2980b9 !important; border: 1px solid #2980b9 !important; }
    .button-refresh-source:hover { background: #eaf6fb !important; }
    .source-actions { display: flex; gap: 8px; align-items: center; margin-top: 18px; }
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
    <?php
}

// --- Guardado de Source ---
function save_api_source() {
    // Validar y sanear entradas
    $source_name = sanitize_text_field($_POST['source_name']);
    $endpoint_id = sanitize_text_field($_POST['endpoint_id']);
    $field_prefix = sanitize_text_field($_POST['field_prefix']);
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $pagination_type = sanitize_text_field($_POST['pagination_type']);
    $pagination_param = sanitize_text_field($_POST['pagination_param'] ?? '');
    $per_page_param = sanitize_text_field($_POST['per_page_param'] ?? '');
    // Procesar parámetros dinámicos
    $dynamic_params = [];
    if (isset($_POST['param_names']) && is_array($_POST['param_names'])) {
        $param_names = array_map('sanitize_text_field', $_POST['param_names']);
        $param_sources = isset($_POST['param_sources']) ? array_map('sanitize_text_field', $_POST['param_sources']) : [];
        $param_defaults = isset($_POST['param_defaults']) ? array_map('sanitize_text_field', $_POST['param_defaults']) : [];
        foreach ($param_names as $index => $name) {
            if (!empty($name)) {
                $dynamic_params[] = [
                    'name' => $name,
                    'source' => isset($param_sources[$index]) ? $param_sources[$index] : 'url',
                    'default' => isset($param_defaults[$index]) ? $param_defaults[$index] : ''
                ];
            }
        }
    }
    // Obtener sources existentes
    $api_sources = get_option('bricks_api_sources', []);
    // Comprobar si estamos editando un query type existente
    $editing = isset($_POST['source_id']) && !empty($_POST['source_id']);
    $source_id = $editing ? sanitize_text_field($_POST['source_id']) : 'query_type_' . time();
    // Si estamos editando y no se envían parámetros, mantener los existentes
    if ($editing && empty($dynamic_params) && isset($api_sources[$source_id]['dynamic_params'])) {
        $dynamic_params = $api_sources[$source_id]['dynamic_params'];
    }
    // Si estamos creando un nuevo query type, limpiar cualquier dato residual
    if (!$editing) {
        // Limpiar caché de parámetros dinámicos para evitar persistencia de datos
        clean_dynamic_params_cache();
    }
    // Verificar explícitamente que no haya un parámetro anunci-actiu no deseado
    foreach ($dynamic_params as $key => $param) {
        if ($param['name'] === 'anunci-actiu' && !in_array('anunci-actiu', $_POST['param_names'])) {
            // Si encontramos un parámetro anunci-actiu que no fue enviado por el usuario, lo eliminamos
            unset($dynamic_params[$key]);
        }
    }
    // Reindexar el array después de posibles eliminaciones
    $dynamic_params = array_values($dynamic_params);
    // Preparar datos del query type
    $source_data = [
        'name' => $source_name,
        'endpoint_id' => $endpoint_id,
        'field_prefix' => $field_prefix,
        'items_path' => $items_path,
        'pagination_type' => $pagination_type,
        'pagination_param' => $pagination_param,
        'per_page_param' => $per_page_param,
        'dynamic_params' => $dynamic_params,
        'query_type_name' => !empty($field_prefix) ? $field_prefix . $source_name : $source_name,
        'last_updated' => current_time('mysql')
    ];
    // Añadir o actualizar el query type
    $api_sources[$source_id] = $source_data;
    // Guardar los query types actualizados
    update_option('bricks_api_sources', $api_sources);
    // Mensaje de éxito
    add_settings_error(
        'bricks_api_sources',
        $editing ? 'source_updated' : 'source_added',
        $editing ? __('Query Type actualizado correctamente.', 'bricks-api-integrator') : __('Query Type añadido correctamente.', 'bricks-api-integrator'),
        'updated'
    );
    // Flag para redirección JS si es edición
    if ($editing) {
        update_option('bricks_api_redirect_after_save', true);
    }
}

// --- Acciones de borrado y edición ---
function handle_api_source_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'bricks-api-integrator-sources') {
        return;
    }
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['source_id'])) {
        $source_id = sanitize_text_field($_GET['source_id']);
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_api_source_' . $source_id)) {
            wp_die(__('Security check failed.', 'bricks-api-integrator'));
        }
        // Get existing query types
        $api_sources = get_option('bricks_api_sources', []);
        // Remove the query type
        if (isset($api_sources[$source_id])) {
            // Guardar el nombre del query type para limpieza adicional
            $source_name = isset($api_sources[$source_id]['name']) ? $api_sources[$source_id]['name'] : '';
            // Eliminar el query type
            unset($api_sources[$source_id]);
            update_option('bricks_api_sources', $api_sources);
            // Limpiar caché de WordPress
            wp_cache_delete('bricks_api_sources', 'options');
            // Limpiar transients relacionados
            if (!empty($source_name)) {
                $transient_key = 'bricks_api_source_' . sanitize_key($source_name);
                delete_transient($transient_key);
            }
            // Limpiar parámetros dinámicos persistentes
            clean_dynamic_params_cache();
            // Add success message
            add_settings_error(
                'bricks_api_sources',
                'source_deleted',
                __('Query Type eliminado completamente. Se han limpiado todos los datos relacionados.', 'bricks-api-integrator'),
                'updated'
            );
        }
        // Redirect to remove action from URL
        wp_redirect(admin_url('admin.php?page=bricks-api-integrator-sources'));
        exit;
    }
}
add_action('admin_init', 'handle_api_source_actions');

// --- Helpers administrativos para limpieza de caché y parámetros ---
function clean_dynamic_params_cache() {
    global $wpdb;
    // Eliminar transients relacionados con API
    $transients = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options} 
        WHERE option_name LIKE '%_transient_bricks_api_%' 
        OR option_name LIKE '%_transient_timeout_bricks_api_%'"
    );
    foreach ($transients as $transient) {
        if (strpos($transient, '_transient_timeout_') === 0) {
            $transient_name = str_replace('_transient_timeout_', '', $transient);
            delete_transient($transient_name);
        } elseif (strpos($transient, '_transient_') === 0) {
            $transient_name = str_replace('_transient_', '', $transient);
            delete_transient($transient_name);
        }
    }
    // Limpiar opciones temporales que puedan contener parámetros
    $temp_options = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options} 
        WHERE option_name LIKE 'bricks_api_temp_%'"
    );
    foreach ($temp_options as $option) {
        delete_option($option);
    }
    // Limpiar específicamente el parámetro anunci-actiu que está causando problemas
    clean_anunci_actiu_parameter();
    // Forzar limpieza de caché de objetos
    wp_cache_flush();
}

function clean_anunci_actiu_parameter() {
    global $wpdb;
    // Buscar opciones que contengan el parámetro anunci-actiu
    $options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} 
        WHERE option_value LIKE '%anunci-actiu%'"
    );
    foreach ($options as $option) {
        if ($option->option_name === 'bricks_api_sources') {
            $sources = get_option('bricks_api_sources', []);
            foreach ($sources as $source_id => $source) {
                if (isset($source['dynamic_params']) && is_array($source['dynamic_params'])) {
                    foreach ($source['dynamic_params'] as $key => $param) {
                        if ($param['name'] === 'anunci-actiu') {
                            unset($sources[$source_id]['dynamic_params'][$key]);
                        }
                    }
                    if (isset($sources[$source_id]['dynamic_params'])) {
                        $sources[$source_id]['dynamic_params'] = array_values($sources[$source_id]['dynamic_params']);
                    }
                }
            }
            update_option('bricks_api_sources', $sources);
        } else {
            if (strpos($option->option_name, '_transient_') === 0) {
                $transient_name = str_replace('_transient_', '', $option->option_name);
                delete_transient($transient_name);
            } else {
                $value = get_option($option->option_name);
                if (is_array($value)) {
                    $modified = false;
                    $clean_array = function($array) use (&$clean_array, &$modified) {
                        foreach ($array as $key => $val) {
                            if ($key === 'anunci-actiu') {
                                unset($array[$key]);
                                $modified = true;
                            } elseif (is_array($val)) {
                                $array[$key] = $clean_array($val);
                            } elseif (is_string($val) && strpos($val, 'anunci-actiu') !== false) {
                                $array[$key] = str_replace('anunci-actiu', '', $val);
                                $modified = true;
                            }
                        }
                        return $array;
                    };
                    $value = $clean_array($value);
                    if ($modified) {
                        update_option($option->option_name, $value);
                    }
                }
            }
        }
    }
    wp_cache_flush();
}
