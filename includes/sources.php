<?php
/**
 * Query Types Manager for Bricks Builder
 * 
 * Este archivo maneja la creación y gestión de query types para Bricks Query Loop
 * 
 * IMPORTANTE: Los Query Types NO generan dynamic tags para evitar duplicación.
 * Los dynamic tags se generan automáticamente SOLO desde los endpoints configurados.
 * Los Query Types son únicamente para usar en Bricks Query Loop.
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

/**
 * Query Types para Bricks Builder
 */

/**
 * Render the Query Types admin page
 */
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
                                                <button type="button" class="button button-delete-param" style="background:#dc3232;color:#fff;"><?php esc_html_e('Eliminar', 'bricks-api-integrator'); ?></button>
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
                        <button type="button" class="button button-refresh-source" id="refresh-source-btn">🔄 Actualizar datos</button>
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
        
        // Asegurarse de que los botones "Eliminar" existentes funcionen
        $('.button-delete-param').click(function(){ 
            $(this).closest('.dynamic-param-row').remove(); 
        });
        
        // --- Mostrar/ocultar campos de paginación ---
        function togglePaginationFields() {
            const type = $('#pagination_type').val();
            $('.pagination-param-row').toggle(type !== 'none');
            $('.per-page-param-row').toggle(type !== 'none');
        }
        $('#pagination_type').change(togglePaginationFields);
        togglePaginationFields();
        // --- Botones de acción alineados ---
        $('.source-actions').css({display:'flex',gap:'8px',alignItems:'center'});
        // --- Mostrar tags y botón eliminar al editar si existen ---
        function autoShowTagsIfExist() {
            var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            
            // Primero verificar si el source tiene tags generados
            $.post(ajaxurl, {action:'get_source_tags', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
                if(res.success && res.data.tags && res.data.tags.length){
                    $('#view-source-tags-btn').show();
                    $('#delete-source-tags-btn').show();
                    console.log('Source tiene tags:', res.data.tags.length);
                } else {
                    // Si no hay tags en el source, verificar en las opciones globales
                    $.post(ajaxurl, {action:'get_tags_for_source', source_id:sourceId, source_name:$('#source_name').val(), nonce: window.bricksApiSourceNonce}, function(res2){
                        if(res2.success && res2.data.tags && res2.data.tags.length){
                            $('#view-source-tags-btn').show();
                            $('#delete-source-tags-btn').show();
                            console.log('Source tiene tags globales:', res2.data.tags.length);
                        } else {
                            $('#view-source-tags-btn').hide();
                            $('#delete-source-tags-btn').hide();
                            console.log('Source no tiene tags');
                        }
                    });
                }
            });
        }
        // Llamar al cargar si estamos editando
        if ($('input[name="source_id"]').length) {
            autoShowTagsIfExist();
        }
        // --- Mejorar visualización de tags dinámicos ---
        function renderTagsTable(res, sourceId) {
            // Usar los datos de ejemplo proporcionados por el servidor o hacer una solicitud adicional
            var example = res.data.example || {};
            var exampleJson = res.data.example_json || '';
            
            // Si no tenemos datos de ejemplo, intentar obtenerlos
            if (!example || Object.keys(example).length === 0) {
            $.post(ajaxurl, {action:'preview_source_api', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(previewRes){
                if(previewRes.success && previewRes.data && previewRes.data.preview){
                    var preview = previewRes.data.preview;
                    if(Array.isArray(preview)){
                        example = preview[0] || {};
                    } else if(typeof preview === 'object'){
                        example = preview;
                    } else if(typeof preview === 'string'){
                        try { example = JSON.parse(preview); } catch(e) { example = {}; }
                    }
                    try { exampleJson = JSON.stringify(example, null, 2); } catch(e) { exampleJson = ''; }
                        // Continuar con la renderización después de obtener los datos
                        renderTagsTableContent(res, example, exampleJson);
                    }
                });
            } else {
                // Si ya tenemos los datos, renderizar directamente
                renderTagsTableContent(res, example, exampleJson);
            }
        }
        
        function renderTagsTableContent(res, example, exampleJson) {
            console.log('renderTagsTableContent NUEVO');
            let html = '';
            // 1. JSON formateado
            if (exampleJson) {
                html += '<div style="margin-bottom:18px;"><strong>📦 Respuesta de la API (primer registro o detalle):</strong><pre style="background:#f8f9fa;padding:10px;border-radius:5px;max-height:300px;overflow:auto;font-size:13px;">'+exampleJson+'</pre></div>';
            }
            // 2. Estructura detectada
            if (example && typeof example === 'object' && Object.keys(example).length > 0) {
                html += '<div style="margin-bottom:18px;"><strong>🧩 Estructura detectada:</strong><table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;"><thead><tr style="background:#e7f3ff;"><th style="padding:6px 8px;border:1px solid #e3e3e3;">Campo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Tipo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Valor de ejemplo</th></tr></thead><tbody>';
                Object.entries(example).forEach(function([key, value]) {
                        let tipo = Array.isArray(value) ? 'Array' : typeof value;
                        let valEjemplo = (typeof value === 'object' && value !== null)
                            ? JSON.stringify(value, null, 2)
                            : value;
                    html += '<tr><td style="padding:6px 8px;border:1px solid #e3e3e3;">'+key+'</td><td style="padding:6px 8px;border:1px solid #e3e3e3;">'+tipo+'</td><td style="padding:6px 8px;border:1px solid #e3e3e3;font-family:monospace;">'+valEjemplo+'</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            // 3. Tags dinámicos generados con checkboxes y ejemplos
            let tags = [];
            let tagObjs = res.data.tag_objects || [];
            let disabledTags = Array.isArray(res.data.disabled_tags) ? res.data.disabled_tags : [];
            if (Array.isArray(res.data.tags)) {
                tags = res.data.tags;
            }
            if (!tags.length) {
                html += '<div style="margin-bottom:18px;"><em>No se han generado tags dinámicos para este Source.</em></div>';
                $('#source-test-result').html(html);
                return;
            }
                html += '<div style="margin-bottom:18px;"><strong>🏷️ Tags dinámicos generados:</strong><form id="tags-enable-form"><div style="display:grid;gap:8px;margin-top:10px;">';
            tags.forEach(function(tag, idx){
                let tagStr = '';
                let example = '';
                if (typeof tag === 'object' && tag.tag) {
                    tagStr = tag.tag;
                    example = tag.example || '';
                } else {
                    tagStr = tag;
                    // Buscar el ejemplo en tagObjs si existe
                    let obj = tagObjs.find(t => t.tag === tag);
                    example = obj && obj.example ? obj.example : '';
                }
                let checked = disabledTags.includes(tagStr) ? '' : 'checked';
                let safeId = 'tag-enable-' + idx;
                html += '<div style="display:flex;align-items:center;gap:10px;background:#f8f9fa;padding:8px 12px;border-radius:5px;">';
                html += '<input type="checkbox" id="'+safeId+'" class="tag-enable-checkbox" data-tag="'+tagStr+'" '+checked+' style="margin-right:6px;">';
                html += '<label for="'+safeId+'" style="margin:0;cursor:pointer;"><code style="font-size:14px;color:#e67e22;font-weight:bold;">'+tagStr+'</code></label>';
                html += '<button type="button" class="button button-small copy-tag-btn" data-copy="'+tagStr+'" style="margin-left:10px;">Copiar tag</button>';
                html += '<span style="color:#888;">→</span>';
                html += '<span style="font-family:monospace;background:#fff;padding:2px 6px;border-radius:3px;">'+example+'</span>';
                    html += '</div>';
                });
            html += '</div><div style="margin-top:15px;"><button type="submit" class="button button-primary">💾 Guardar selección de tags</button></div></form></div>';
            html += '<p style="font-size:12px;color:#6c757d;margin:10px 0 0 0;">💡 Puedes desmarcar los tags que no quieras usar. Haz clic en el tag o en el botón para copiar el valor de ejemplo o el tag completo.</p>';
                $('#source-test-result').html(html);
                // --- Guardar selección de tags habilitados ---
                $('#tags-enable-form').off('submit').on('submit', function(e){
            e.preventDefault();
                    const enabledTags = [];
                    $('.tag-enable-checkbox:checked').each(function(){
                        enabledTags.push($(this).data('tag'));
                    });
            $.post(ajaxurl, {
                        action: 'save_enabled_tags_for_source',
                        nonce: window.bricksApiSourceNonce,
                    source_id: $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_'),
                        enabled_tags: enabledTags
                    }, function(resp2){
                        if (resp2.success) {
                        showSourceMessage('✅ Selección de tags guardada.', 'success');
                } else {
                        showSourceMessage('❌ Error al guardar la selección de tags.', 'error');
                }
            });
        });
            // --- Copiar tag al portapapeles ---
            $('.copy-tag-btn').off('click').on('click', function(){
                const tag = $(this).data('copy');
                navigator.clipboard.writeText(tag);
                $(this).text('¡Copiado!');
                setTimeout(()=>{$(this).text('Copiar tag');},1000);
            });
        }
        // --- Ocultar botón crear tags al crear nuevo Source ---
        if (!$('input[name="source_id"]').length) {
            $('#generate-source-tags-btn').hide();
        }
        
        // Función para obtener y mostrar los tags desde el servidor
        function renderAdvancedSections(sourceId) {
            // Obtener los tags directamente desde el servidor
            $.post(ajaxurl, {
                action: 'get_source_tags',
                source_id: sourceId,
                nonce: window.bricksApiSourceNonce
            }, function(res) {
                if (res.success && res.data && res.data.tags) {
                    // Obtener datos de ejemplo para las secciones avanzadas
                    $.post(ajaxurl, {
                        action: 'preview_source_api',
                        source_id: sourceId,
                        nonce: window.bricksApiSourceNonce
                    }, function(previewRes) {
                        renderTagsTable(res, sourceId);
                    });
                }
            });
        }
        
        // --- Ver tags dinámicos ---
        $('#view-source-tags-btn').off('click').on('click', function(){
          var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            var sourceName = $('#source_name').val();
            
            // Cargar los tags dinámicamente desde el servidor con toda la información
            $.post(ajaxurl, {
                action: 'get_tags_for_source',
                nonce: window.bricksApiSourceNonce,
                source_id: sourceId,
                source_name: sourceName,
                full_info: true // Solicitar información completa
            }, function(resp) {
                if (resp.success && resp.data) {
                    // Mostrar la información completa (3 secciones)
                    renderTagsTable(resp, sourceId);
                } else {
                    // Intentar con la función anterior como fallback
                    renderAdvancedSections(sourceId);
                }
            }).fail(function() {
                // Si falla la petición AJAX, intentar con la función anterior
          renderAdvancedSections(sourceId);
        });
        });
        
        // --- Copiar al portapapeles ---
        $(document).on('click', '.copy-tag-btn', function(){
            var val = $(this).data('copy');
            navigator.clipboard.writeText(val);
            $(this).text('¡Copiado!');
            var btn = $(this);
            setTimeout(function(){ btn.text('Copiar tag'); }, 1200);
        });
        
        $(document).on('click', '.copy-val-btn', function(){
            var val = $(this).data('copy');
            navigator.clipboard.writeText(val);
            $(this).text('¡Copiado!');
            var btn = $(this);
            setTimeout(function(){ btn.text('Copiar valor'); }, 1200);
        });
        
        // --- Toggle habilitar/deshabilitar tag ---
        $('#source-test-result').on('change', '.tag-enable-checkbox', function(){
            var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            var tag = $(this).data('tag');
            var enabled = $(this).is(':checked');
            $.post(ajaxurl, {
                action: 'toggle_source_tag', 
                source_id: sourceId, 
                tag: tag, 
                enabled: enabled, 
                nonce: window.bricksApiSourceNonce
            }, function(res){
                if(!res.success){
                    alert('Error: '+res.data);
                }
            });
        });
        // --- Botón eliminar tags y source ---
        $('#delete-source-tags-btn').click(function(){
            if(!confirm('¿Seguro que quieres eliminar los tags y el query type?')) return;
            var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            $.post(ajaxurl, {action:'delete_source_and_tags', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
                if(res.success){
                    alert('Source y tags eliminados');
                    location.reload();
                }else{
                    alert('Error: '+res.data);
                }
            });
        });
        // --- Botón Test Source ---
        function getSourceFormData() {
            const endpoint_id = $('#endpoint_id').val();
            const items_path = $('#items_path').val();
            const field_prefix = $('#field_prefix').val();
            const pagination_type = $('#pagination_type').val();
            const pagination_param = $('#pagination_param').val();
            const per_page_param = $('#per_page_param').val();
            // Parámetros dinámicos como arrays
            const param_names = [];
            const param_sources = [];
            const param_defaults = [];
            $('#dynamic-params-container .dynamic-param-row').each(function(){
                const name = $(this).find('input, [name^="param_names"]').val();
                const source = $(this).find('select, [name^="param_sources"]').val();
                const def = $(this).find('input.param-default, [name^="param_defaults"]').val();
                if(name) {
                    param_names.push(name);
                    param_sources.push(source);
                    param_defaults.push(def);
                }
            });
            return {
                endpoint_id,
                items_path,
                field_prefix,
                pagination_type,
                pagination_param,
                per_page_param,
                param_names,
                param_sources,
                param_defaults,
                nonce: window.bricksApiSourceNonce
            };
        }
        $('#test-source-btn, #refresh-source-btn').click(function(){
            const isRefresh = $(this).attr('id') === 'refresh-source-btn';
            const data = getSourceFormData();
            if(!data.endpoint_id){
                $('#source-test-result').html('<span style="color:#c00">Selecciona un endpoint antes de testear.</span>');
                return;
            }
            $('#source-test-result').html(isRefresh ? '<em>Actualizando datos...</em>' : '<em>Consultando API...</em>');
            console.log('Test/Refresh AJAX nonce:', window.bricksApiSourceNonce);
            $.post(ajaxurl, {
                action: 'test_source_api_live',
                ...data,
                force_refresh: isRefresh ? 1 : 0
            }, function(res){
                if(res.success){
                    var html = '<div style="background:#f8f9fa;padding:12px;border-radius:6px;margin-top:10px;">';
                    html += '<b>Respuesta de la API'+(isRefresh?' (actualizada)':'')+':</b><br><pre style="max-height:300px;overflow:auto;">'+res.data.preview+'</pre>';
                    if(res.data.fields && res.data.fields.length){
                        html += '<b>Campos detectados:</b> '+res.data.fields.join(', ');
                    }
                    html += '</div>';
                    $('#source-test-result').html(html);
                }else{
                    $('#source-test-result').html('<span style="color:#c00">'+res.data+'</span>');
                }
            });
        });
        // --- Modal Preview centrado y cierre robusto ---
        if($('#bricks-source-preview-modal').length === 0){
            $('body').append('<div id="bricks-source-preview-modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;"><div style="background:#fff;padding:28px 24px 18px 24px;border-radius:10px;max-width:600px;max-height:80vh;overflow:auto;position:relative;"><button id="close-source-preview-modal" style="position:absolute;top:10px;right:10px;font-size:18px;background:none;border:none;cursor:pointer;">✖</button><div id="bricks-source-preview-content"></div></div></div>');
        }
        // Siempre ocultar el modal al cargar la página
        $('#bricks-source-preview-modal').hide();
        // Mostrar modal solo si hay contenido
        function showSourcePreviewModal(contentHtml) {
            $('#bricks-source-preview-content').html(contentHtml);
            $('#bricks-source-preview-modal').fadeIn(120);
        }
        // Cerrar modal y limpiar contenido
        function closeSourcePreviewModal() {
            $('#bricks-source-preview-modal').fadeOut(120, function(){
                $('#bricks-source-preview-content').html('');
            });
        }
        $(document).on('click','#close-source-preview-modal',function(){
            closeSourcePreviewModal();
        });
        // Cerrar modal al hacer click fuera del contenido
        $(document).on('mousedown',function(e){
            var $modal = $('#bricks-source-preview-modal');
            if($modal.is(':visible')){
                var $content = $modal.find('>div');
                if(!$content.is(e.target) && $content.has(e.target).length === 0){
                    closeSourcePreviewModal();
                }
            }
        });
        // Usar showSourcePreviewModal en vez de fadeIn directo
        $(document).on('click','.query-type-preview-btn',function(){
            var sourceId = $(this).data('source-id');
            var sourceName = $(this).data('source-name');
            showSourcePreviewModal('<em>Consultando API...</em>');
            console.log('Preview AJAX nonce:', window.bricksApiSourceNonce);
            $.post(ajaxurl, {action:'preview_source_api', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
                if(res.success){
                    var html = '<h3 style="margin-top:0">Preview: '+sourceName+'</h3>';
                    
                    // Información del endpoint
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
                    
                    // Información de la ruta de elementos
                    html += '<div style="background:#f7f7f7;padding:12px;border-radius:6px;margin-top:10px;border-left:3px solid #555;">';
                    html += '<h4 style="margin-top:0;margin-bottom:8px;">Configuración de Datos</h4>';
                    html += '<table style="width:100%;border-collapse:collapse;">';
                    html += '<tr><td style="padding:4px 0;font-weight:bold;width:120px;">Ruta de elementos:</td><td><code style="background:#eee;padding:2px 4px;border-radius:3px;">'+(res.data.items_path || 'Raíz de la respuesta')+'</code></td></tr>';
                    if (res.data.total_items !== undefined) {
                        html += '<tr><td style="padding:4px 0;font-weight:bold;">Total de elementos:</td><td>'+res.data.total_items+'</td></tr>';
                    }
                    html += '</table>';
                    html += '</div>';
                    
                    // Datos del primer elemento
                    html += '<div style="background:#f8f9fa;padding:12px;border-radius:6px;margin-top:10px;border-left:3px solid #4caf50;">';
                    html += '<h4 style="margin-top:0;margin-bottom:8px;color:#2e7d32;">Datos del Primer Elemento</h4>';
                    html += '<pre style="max-height:300px;overflow:auto;background:#fff;padding:10px;border-radius:4px;margin-top:5px;border:1px solid #e0e0e0;">'+JSON.stringify(res.data.preview, null, 2)+'</pre>';
                    html += '</div>';
                    
                    // Campos detectados
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
        // --- Botón Probar Ruta ---
        $('.test-items-path').off('click').on('click', function(){
            var endpointId = $(this).data('endpoint-id');
            var sourceId = $(this).data('source-id');
            var itemsPath = $('#items_path').val();
            var nonce = $(this).data('nonce');
            
            if (!itemsPath) {
                alert('Por favor, introduce una ruta de elementos para probar');
                return;
            }
            
            // Mostrar un modal o área con el resultado de la prueba
            if ($('#items-path-test-result').length === 0) {
                $('#items_path').after('<div id="items-path-test-result" style="margin-top:15px;padding:15px;background:#f8f9fa;border-radius:6px;"></div>');
            }
            
            $('#items-path-test-result').html('<em>Probando ruta de elementos...</em>');
            
            console.log('Test Items Path AJAX nonce:', nonce);
            
            $.post(ajaxurl, {
                action: 'test_items_path',
                endpoint_id: endpointId,
                source_id: sourceId,
                items_path: itemsPath,
                nonce: nonce
            }, function(res){
                console.log('Respuesta del servidor:', res); // Añadir log para depuración
                
                if (res.success) {
                    let html = '<div><strong>Resultado de la prueba:</strong> ';
                    
                    if (res.data && res.data.found) {
                        html += '<span style="color:#27ae60">Se encontraron datos en la ruta especificada</span>';
                        
                        // Si hay un mensaje específico, mostrarlo
                        if (res.data.message) {
                            html += '<div style="margin-top:5px;">' + res.data.message + '</div>';
                        }
                        
                        // Mostrar los datos encontrados
                        if (res.data.data) {
                            html += '<pre style="max-height:200px;overflow:auto;background:#fff;padding:10px;border-radius:4px;margin-top:10px;">' + 
                                    JSON.stringify(res.data.data, null, 2) + '</pre>';
                        }
                        
                        // Mostrar el total de elementos si está disponible
                        if (res.data.total_items && res.data.total_items > 1) {
                            html += '<div style="margin-top:5px;"><em>Total de elementos: ' + res.data.total_items + '</em></div>';
                        }
                    } else {
                        html += '<span style="color:#e74c3c">No se encontraron datos en la ruta especificada</span>';
                        
                        // Si hay un mensaje de error específico, mostrarlo
                        if (res.data && res.data.error) {
                            html += '<div style="margin-top:5px;">' + res.data.error + '</div>';
                        }
                        
                        // Mostrar las claves disponibles en la respuesta
                        if (res.data && res.data.available_keys && res.data.available_keys.length > 0) {
                            html += '<div style="margin-top:10px;"><strong>Claves disponibles en la respuesta:</strong>';
                            html += '<pre style="max-height:200px;overflow:auto;background:#fff;padding:10px;border-radius:4px;margin-top:5px;">' + 
                                    JSON.stringify(res.data.available_keys, null, 2) + '</pre></div>';
                        }
                        
                        // Mostrar sugerencias si hay alguna
                        if (res.data && res.data.suggestions && res.data.suggestions.length > 0) {
                            html += '<div style="margin-top:10px;"><strong>Sugerencias de rutas:</strong>';
                            html += '<ul style="margin-top:5px;">';
                            res.data.suggestions.forEach(function(suggestion) {
                                html += '<li>' + suggestion + '</li>';
                            });
                            html += '</ul></div>';
                        }
                    }
                    
                    html += '</div>';
                    $('#items-path-test-result').html(html);
                } else {
                    // Si hay un mensaje específico en la respuesta de error, usarlo
                    let errorMsg = res.data && typeof res.data === 'string' ? res.data : 'Error desconocido';
                    $('#items-path-test-result').html('<span style="color:#e74c3c">Error: ' + errorMsg + '</span>');
                }
            }).fail(function(xhr, textStatus, errorThrown){
                console.error('Error AJAX:', textStatus, errorThrown);
                $('#items-path-test-result').html('<span style="color:#e74c3c">Error de conexión al probar la ruta</span>');
            });
        });
        
        // --- Botón crear tags dinámicos ---
        $('#generate-source-tags-btn').off('click').on('click', function(){
            var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            $('#generate-source-tags-btn').prop('disabled', true).text('Generando...');
            
            $.post(ajaxurl, {
                action: 'generate_source_tags', 
                source_id: sourceId, 
                nonce: window.bricksApiSourceNonce
            }, function(res){
                $('#generate-source-tags-btn').prop('disabled', false).text('⚡ Crear tags y query types dinámicos');
                if(res.success){
                    alert('Tags generados correctamente');
                    // Mostrar botones de forma persistente
                    $('#view-source-tags-btn').show();
                    $('#delete-source-tags-btn').show();
                    // Forzar que se mantengan visibles
                    setTimeout(function(){
                        $('#view-source-tags-btn').show();
                        $('#delete-source-tags-btn').show();
                        console.log('Botones forzados a permanecer visibles');
                    }, 1000);
                    // Mostrar tabla avanzada automáticamente
                    autoShowTagsIfExist();
                } else {
                    alert('Error: ' + res.data);
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
    </style>
    <?php
}

/**
 * Save Query Type
 */
function save_api_source() {
    // Validate and sanitize inputs
    $source_name = sanitize_text_field($_POST['source_name']);
    $endpoint_id = sanitize_text_field($_POST['endpoint_id']);
    $field_prefix = sanitize_text_field($_POST['field_prefix']);
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $pagination_type = sanitize_text_field($_POST['pagination_type']);
    $pagination_param = sanitize_text_field($_POST['pagination_param'] ?? '');
    $per_page_param = sanitize_text_field($_POST['per_page_param'] ?? '');
    
    // Process dynamic parameters
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
    
    // Get existing sources
    $api_sources = get_option('bricks_api_sources', []);
    
    // Check if we're editing an existing query type
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
    
    // Prepare query type data - Asegurarse de que solo se guarden los datos proporcionados explícitamente
    $source_data = [
        'name' => $source_name,
        'endpoint_id' => $endpoint_id,
        'field_prefix' => $field_prefix,
        'items_path' => $items_path,
        'pagination_type' => $pagination_type,
        'pagination_param' => $pagination_param,
        'per_page_param' => $per_page_param,
        'dynamic_params' => $dynamic_params,
        'query_type_name' => !empty($field_prefix) ? $field_prefix . $source_name : $source_name, // Aplicar prefijo al nombre del query type
        'last_updated' => current_time('mysql') // Añadir timestamp para seguimiento
    ];
    
    // Add or update query type
    $api_sources[$source_id] = $source_data;
    
    // Save updated query types
    update_option('bricks_api_sources', $api_sources);
    
    // Add success message
    add_settings_error(
        'bricks_api_sources',
        $editing ? 'source_updated' : 'source_added',
        $editing ? __('Query Type actualizado correctamente.', 'bricks-api-integrator') : __('Query Type añadido correctamente.', 'bricks-api-integrator'),
        'updated'
    );
    
    // Set a flag to trigger JavaScript redirect instead of PHP redirect
    if ($editing) {
        // We'll use JavaScript to redirect instead of wp_redirect
        update_option('bricks_api_redirect_after_save', true);
    }
}

/**
 * Handle delete action for query types
 */
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

/**
 * Limpiar caché de parámetros dinámicos
 * Esta función elimina cualquier rastro de parámetros dinámicos persistentes
 */
function clean_dynamic_params_cache() {
    // Limpiar transients que puedan contener parámetros dinámicos
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

/**
 * Limpiar específicamente el parámetro anunci-actiu que está causando problemas
 * Esta función busca y elimina cualquier referencia al parámetro anunci-actiu en la base de datos
 */
function clean_anunci_actiu_parameter() {
    global $wpdb;
    
    // Buscar opciones que contengan el parámetro anunci-actiu
    $options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} 
        WHERE option_value LIKE '%anunci-actiu%'"
    );
    
    foreach ($options as $option) {
        if ($option->option_name === 'bricks_api_sources') {
            // Para la opción principal de sources, necesitamos actualizar en lugar de eliminar
            $sources = get_option('bricks_api_sources', []);
            
            // Recorrer cada source y limpiar el parámetro anunci-actiu
            foreach ($sources as $source_id => $source) {
                if (isset($source['dynamic_params']) && is_array($source['dynamic_params'])) {
                    foreach ($source['dynamic_params'] as $key => $param) {
                        if ($param['name'] === 'anunci-actiu') {
                            // Eliminar este parámetro
                            unset($sources[$source_id]['dynamic_params'][$key]);
                        }
                    }
                    
                    // Reindexar el array de parámetros
                    if (isset($sources[$source_id]['dynamic_params'])) {
                        $sources[$source_id]['dynamic_params'] = array_values($sources[$source_id]['dynamic_params']);
                    }
                }
            }
            
            // Actualizar la opción
            update_option('bricks_api_sources', $sources);
        } else {
            // Para otras opciones, verificar si son transients o opciones normales
            if (strpos($option->option_name, '_transient_') === 0) {
                $transient_name = str_replace('_transient_', '', $option->option_name);
                delete_transient($transient_name);
            } else {
                // Intentar limpiar el valor si es un array serializado
                $value = get_option($option->option_name);
                if (is_array($value)) {
                    $modified = false;
                    
                    // Función recursiva para limpiar arrays anidados
                    $clean_array = function($array) use (&$clean_array, &$modified) {
                        foreach ($array as $key => $val) {
                            if ($key === 'anunci-actiu') {
                                unset($array[$key]);
                                $modified = true;
                            } elseif (is_array($val)) {
                                $array[$key] = $clean_array($val);
                            } elseif (is_string($val) && strpos($val, 'anunci-actiu') !== false) {
                                // Limpiar strings que contengan el parámetro
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
    
    // Limpiar la caché de WordPress
    wp_cache_flush();
}

/**
 * Register Query Types with Bricks Query Loop
 */
function register_api_sources_with_bricks($sources) {
    // Get configured Query Types
    $api_sources = get_option('bricks_api_sources', []);
    
    // Log para depuración
    
    if (empty($api_sources)) {
        return $sources;
    }
    
    // Add each Query Type to Bricks sources
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        
        $sources[$source_id] = [
            'name'  => $display_name,
            'class' => 'Bricks_API_Source_Query', // Custom query class we'll create
        ];
    }
    
    // Log para depuración
    
    return $sources;
}
add_filter('bricks/query/sources', 'register_api_sources_with_bricks');

/**
 * Create custom query class for Query Types
 */
function register_api_source_query_class() {
    // Verificar si Bricks está activo
    if (!defined('BRICKS_VERSION')) {
        return;
    }
    
    // Verificar si la clase Bricks_Query_Provider existe
    if (!class_exists('Bricks_Query_Provider')) {
        return;
    }
    
    
    class Bricks_API_Source_Query extends Bricks_Query_Provider {
        public function __construct() {
            $this->name = 'api_source';
            $this->label = esc_html__('Query Type API', 'bricks-api-integrator');
            
            // Set controls (pagination, etc.)
            $this->controls = [
                'source_id' => [
                    'type'        => 'select',
                    'label'       => esc_html__('Query Type', 'bricks-api-integrator'),
                    'options'     => $this->get_api_sources_options(),
                    'description' => esc_html__('Selecciona un Query Type configurado en API Integrator.', 'bricks-api-integrator'),
                    'required'    => true,
                ],
                'pagination' => [
                    'type'  => 'checkbox',
                    'label' => esc_html__('Pagination', 'bricks-api-integrator'),
                ],
                'items_per_page' => [
                    'type'        => 'number',
                    'label'       => esc_html__('Items per page', 'bricks-api-integrator'),
                    'min'         => 1,
                    'max'         => 100,
                    'placeholder' => 10,
                    'required'    => ['pagination', '!=', ''],
                ],
                'cache_results' => [
                    'type'        => 'checkbox',
                    'label'       => esc_html__('Cache results', 'bricks-api-integrator'),
                    'description' => esc_html__('Cache API results to improve performance.', 'bricks-api-integrator'),
                ],
                'cache_duration' => [
                    'type'        => 'number',
                    'label'       => esc_html__('Cache duration (minutes)', 'bricks-api-integrator'),
                    'min'         => 1,
                    'max'         => 1440, // 24 hours
                    'placeholder' => 15,
                    'required'    => ['cache_results', '!=', ''],
                ],
                'dynamic_params' => [
                    'type'        => 'repeater',
                    'label'       => esc_html__('Dynamic Parameters', 'bricks-api-integrator'),
                    'description' => esc_html__('Add custom parameters to override source configuration.', 'bricks-api-integrator'),
                    'fields'      => [
                        'param_name' => [
                            'type'        => 'text',
                            'label'       => esc_html__('Parameter Name', 'bricks-api-integrator'),
                            'placeholder' => 'id',
                        ],
                        'param_value' => [
                            'type'        => 'text',
                            'label'       => esc_html__('Parameter Value', 'bricks-api-integrator'),
                            'placeholder' => '123',
                        ],
                    ],
                ],
            ];
            
            parent::__construct();
        }
        
        /**
         * Get Query Types as options for the select control
         */
        private function get_api_sources_options() {
            $options = [];
            $api_sources = get_option('bricks_api_sources', []);
            
            if (!empty($api_sources)) {
                foreach ($api_sources as $source_id => $source) {
                    $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
                    $options[$source_id] = $display_name;
                }
            } else {
                $options['no_sources'] = esc_html__('No hay Query Types configurados', 'bricks-api-integrator');
            }
            
            return $options;
        }
        
        /**
         * Get query results
         */
        public function get_results($query_args = []) {
            // Get the selected source ID from query settings
            $source_id = isset($query_args['source_id']) ? $query_args['source_id'] : '';
            
            if (empty($source_id) || $source_id === 'no_sources') {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('No se ha seleccionado ningún Query Type o no hay Query Types disponibles.', 'bricks-api-integrator'),
                ];
            }
            
            // Get Query Types
            $api_sources = get_option('bricks_api_sources', []);
            
            if (!isset($api_sources[$source_id])) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('El Query Type seleccionado no se encontró.', 'bricks-api-integrator'),
                ];
            }
            
            $source = $api_sources[$source_id];
            
            // Check if we should use cache
            $use_cache = isset($query_args['cache_results']) && $query_args['cache_results'];
            $cache_duration = isset($query_args['cache_duration']) ? intval($query_args['cache_duration']) * 60 : 900; // Default 15 minutes in seconds
            
            // Generate a cache key based on the query
            $cache_key = 'bricks_api_' . md5(serialize([$source_id, $query_args, $_GET]));
            
            // Try to get cached results
            if ($use_cache) {
                $cached_results = get_transient($cache_key);
                if ($cached_results !== false) {
                    return $cached_results;
                }
            }
            
            // Get the endpoint data
            $endpoints = get_option('bricks_api_endpoints', []);
            $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
            
            if (!isset($endpoints[$endpoint_id])) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('Endpoint configuration not found.', 'bricks-api-integrator'),
                ];
            }
            
            $endpoint = $endpoints[$endpoint_id];
            $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
            
            if (empty($endpoint_url)) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('Endpoint URL is empty.', 'bricks-api-integrator'),
                ];
            }
            
            // Inicializar la URL de la petición
            $request_url = $endpoint_url;
            
            // Handle pagination
            $pagination_type = isset($source['pagination_type']) ? $source['pagination_type'] : 'none';
            $pagination_param = isset($source['pagination_param']) ? $source['pagination_param'] : '';
            $per_page_param = isset($source['per_page_param']) ? $source['per_page_param'] : '';
            
            // Get pagination settings from Bricks Query Loop
            $use_pagination = isset($query_args['pagination']) && $query_args['pagination'];
            $page = 1;
            $items_per_page = isset($query_args['items_per_page']) ? intval($query_args['items_per_page']) : 10;
            
            // Check if we're on a paginated page
            if ($use_pagination) {
                // Get current page from query var or default to 1
                $page_key = isset($query_args['page_key']) ? $query_args['page_key'] : 'page';
                $current_page = get_query_var($page_key, 1);
                
                // Fallback to 'paged' query var
                if ($current_page <= 1) {
                    $current_page = get_query_var('paged', 1);
                }
                
                // Fallback to 'page' query var
                if ($current_page <= 1) {
                    $current_page = get_query_var('page', 1);
                }
                
                // Fallback to $_GET parameter
                if ($current_page <= 1 && isset($_GET[$page_key])) {
                    $current_page = intval($_GET[$page_key]);
                }
                
                $page = max(1, $current_page);
            } else {
                // If not using pagination, use page from query args if provided
                $page = isset($query_args['page']) ? intval($query_args['page']) : 1;
            }
            
            // Process dynamic parameters from source configuration
            $dynamic_params = isset($source['dynamic_params']) ? $source['dynamic_params'] : [];
            if (!empty($dynamic_params)) {
                foreach ($dynamic_params as $param) {
                    $param_name = $param['name'];
                    $param_source = $param['source'];
                    $param_default = $param['default'];
                    $param_value = $param_default; // Default value
                    
                    // Get parameter value based on source
                    switch ($param_source) {
                        case 'url':
                            // Get from URL parameter
                            if (isset($_GET[$param_name])) {
                                $param_value = sanitize_text_field($_GET[$param_name]);
                            }
                            break;
                            
                        case 'post':
                            // Get current post ID
                            global $post;
                            if (isset($post) && is_object($post)) {
                                $param_value = $post->ID;
                            }
                            break;
                            
                        case 'user':
                            // Get current user ID
                            $current_user = wp_get_current_user();
                            if ($current_user->exists()) {
                                $param_value = $current_user->ID;
                            }
                            break;
                            
                        case 'static':
                            // Use the default value
                            $param_value = $param_default;
                            break;
                    }
                    
                    // Add parameter to URL if value is not empty
                    if ($param_value !== '') {
                        $request_url = add_query_arg($param_name, $param_value, $request_url);
                    }
                }
            }
            
            // Process dynamic parameters from query settings (override source configuration)
            if (isset($query_args['dynamic_params']) && is_array($query_args['dynamic_params'])) {
                foreach ($query_args['dynamic_params'] as $param) {
                    if (isset($param['param_name']) && isset($param['param_value']) && !empty($param['param_name'])) {
                        $param_name = $param['param_name'];
                        $param_value = $param['param_value'];
                        
                        // Support for dynamic data in parameter values
                        if (strpos($param_value, '{') !== false && strpos($param_value, '}') !== false) {
                            // This might be a dynamic tag, let Bricks handle it
                            if (function_exists('bricks_render_dynamic_data')) {
                                $param_value = bricks_render_dynamic_data($param_value);
                            }
                        }
                        
                        // Add parameter to URL if value is not empty
                        if ($param_value !== '') {
                            $request_url = add_query_arg($param_name, $param_value, $request_url);
                        }
                    }
                }
            }
            
            // Add pagination parameters
            if ($pagination_type === 'page_param' && !empty($pagination_param)) {
                $request_url = add_query_arg($pagination_param, $page, $request_url);
            } elseif ($pagination_type === 'offset_param' && !empty($pagination_param)) {
                $offset = ($page - 1) * $items_per_page;
                $request_url = add_query_arg($pagination_param, $offset, $request_url);
            }
            
            if (!empty($per_page_param)) {
                $request_url = add_query_arg($per_page_param, $items_per_page, $request_url);
            }
            
            // Prepare request arguments
            $args = [];
            
            // Add authentication if needed
            $auth_type = isset($endpoint['auth_type']) ? $endpoint['auth_type'] : 'none';
            
            if ($auth_type === 'token') {
                $token = isset($endpoint['token']) ? $endpoint['token'] : '';
                $args['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                ];
            } elseif ($auth_type === 'basic') {
                $user = isset($endpoint['basic_user']) ? $endpoint['basic_user'] : '';
                $password = isset($endpoint['basic_password']) ? $endpoint['basic_password'] : '';
                $args['headers'] = [
                    'Authorization' => 'Basic ' . base64_encode("$user:$password"),
                ];
            }
            
            // Log request information for debugging
            
            // --- NUEVO: Construcción de URL dinámica y paginación centralizada ---
            if (!trait_exists('APIManager')) {
                require_once __DIR__ . '/api-manager.php';
            }
            // Instanciar trait (usando $this si la clase lo usa, si no, crear helper temporal)
            $api_manager = $this;
            // Preparar overrides desde query_args
            $overrides = [];
            if (isset($query_args['dynamic_params']) && is_array($query_args['dynamic_params'])) {
                foreach ($query_args['dynamic_params'] as $param) {
                    if (isset($param['param_name']) && isset($param['param_value'])) {
                        $overrides[$param['param_name']] = $param['param_value'];
                    }
                }
            }
            // Configuración de paginación
            $pagination_config = [
                'type' => $pagination_type,
                'param' => $pagination_param,
                'per_page_param' => $per_page_param,
                'page' => $page,
                'per_page' => $items_per_page
            ];
            // Construir la URL final
            $request_url = $api_manager->build_dynamic_api_url(
                $endpoint_url,
                $dynamic_params,
                $pagination_config,
                $overrides
            );
            // --- FIN NUEVO ---
            
            // Make the API request
            $response = wp_remote_get($request_url, $args);
            
            if (is_wp_error($response)) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => $response->get_error_message(),
                ];
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => sprintf(esc_html__('API returned error code: %d', 'bricks-api-integrator'), $status_code),
                ];
            }
            
            $body = wp_remote_retrieve_body($response);
            
            $data = json_decode($body, true);
            
            if (empty($data)) {
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => esc_html__('API returned empty or invalid data.', 'bricks-api-integrator'),
                ];
            }
            
            // Extract items based on items path
            $items_path = isset($source['items_path']) ? $source['items_path'] : '';
            $items = $data;
            
            if (!empty($items_path)) {
                $path_parts = explode('.', $items_path);
                
                foreach ($path_parts as $part) {
                    if (isset($items[$part])) {
                        $items = $items[$part];
                    } else {
                        // Path not found
                        return [
                            'count' => 0,
                            'items' => [],
                            'error' => sprintf(esc_html__('Items path "%s" not found in API response.', 'bricks-api-integrator'), $items_path),
                        ];
                    }
                }
            }
            
            // Ensure items is an array
            if (!is_array($items)) {
                $items = [$items];
            }
            
            // Add field prefix to item keys if specified
            $field_prefix = isset($source['field_prefix']) ? $source['field_prefix'] : '';
            
            if (!empty($field_prefix)) {
                foreach ($items as $key => $item) {
                    if (is_array($item)) {
                        $prefixed_item = [];
                        
                        foreach ($item as $item_key => $item_value) {
                            $prefixed_item[$field_prefix . $item_key] = $item_value;
                        }
                        
                        $items[$key] = $prefixed_item;
                    }
                }
            }
            
            // Get total count for pagination
            $total_count = count($items);
            $total_pages = 1;
            
            // Try to get total count from response headers if available
            $response_headers = isset($response['headers']) ? $response['headers'] : null;
            if ($response_headers) {
                // Check for WordPress headers (X-WP-Total, X-WP-TotalPages)
                if (isset($response_headers['X-WP-Total']) && isset($response_headers['X-WP-TotalPages'])) {
                    $total_count = intval($response_headers['X-WP-Total']);
                    $total_pages = intval($response_headers['X-WP-TotalPages']);
                } 
                // Check for standard pagination headers
                elseif (isset($response_headers['X-Pagination-Total']) && isset($response_headers['X-Pagination-Pages'])) {
                    $total_count = intval($response_headers['X-Pagination-Total']);
                    $total_pages = intval($response_headers['X-Pagination-Pages']);
                }
                // Try to find total count in response data
                elseif (isset($data['total']) && is_numeric($data['total'])) {
                    $total_count = intval($data['total']);
                    $total_pages = ceil($total_count / $items_per_page);
                }
                elseif (isset($data['meta']['total']) && is_numeric($data['meta']['total'])) {
                    $total_count = intval($data['meta']['total']);
                    $total_pages = ceil($total_count / $items_per_page);
                }
            }
            
            // Prepare results
            $results = [
                'count' => $total_count,
                'items' => $items,
                'source_id' => $source_id,
                'endpoint_id' => $endpoint_id,
                'request_url' => $request_url,
                'page' => $page,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'items_per_page' => $items_per_page,
            ];
            
            // Cache results if enabled
            if ($use_cache) {
                set_transient($cache_key, $results, $cache_duration);
            }
            
            // Forzar que el array de items sea siempre indexado
            if (!empty($items) && is_array($items)) {
                $items = array_values($items);
            }
            // Limitar el número de items si per_page_param está definido
            if (!empty($per_page_param) && is_numeric($items_per_page) && $items_per_page > 0) {
                $items = array_slice($items, 0, $items_per_page);
            }
            
            return $results;
        }
        
        /**
         * Render item data for use in dynamic tags
         */
        public function render_item_data($item, $tag, $post_id, $context) {
            // Check if tag exists in item
            if (isset($item[$tag])) {
                return $item[$tag];
            }
            
            // Check for nested data using dot notation
            if (strpos($tag, '.') !== false) {
                $parts = explode('.', $tag);
                $value = $item;
                
                foreach ($parts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        return '';
                    }
                }
                
                return $value;
            }
            
            // Handle special tags
            if ($tag === 'api_url') {
                return isset($item['_api_url']) ? $item['_api_url'] : '';
            }
            
            if ($tag === 'api_id') {
                return isset($item['id']) ? $item['id'] : (isset($item['ID']) ? $item['ID'] : '');
            }
            
            // Return empty string if tag not found
            return '';
        }
    }
}
add_action('init', 'register_api_source_query_class');

/**
 * Register sample data for Bricks Query Loop preview
 */
function register_api_source_sample_data() {
    // Only run in Bricks builder context
    if (!function_exists('bricks_is_builder') || !bricks_is_builder()) {
        return;
    }
    
    // TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
    // add_filter('bricks/query/sample_results', 'get_api_source_sample_data', 10, 2);
}
// TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
// add_action('init', 'register_api_source_sample_data');

/**
 * Get sample data for API sources in Bricks builder
 */
function get_api_source_sample_data($results, $query_obj) {
    // Only process API sources
    if (!isset($query_obj->settings['source']) || strpos($query_obj->settings['source'], 'api_source') !== 0) {
        return $results;
    }
    
    // Get the source ID
    $source_id = isset($query_obj->settings['source_id']) ? $query_obj->settings['source_id'] : '';
    
    if (empty($source_id)) {
        return $results;
    }
    
    // Get API sources
    $api_sources = get_option('bricks_api_sources', []);
    
    if (!isset($api_sources[$source_id])) {
        return $results;
    }
    
    $source = $api_sources[$source_id];
    
    // Get the endpoint data
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
    
    if (!isset($endpoints[$endpoint_id])) {
        return $results;
    }
    
    $endpoint = $endpoints[$endpoint_id];
    $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
    
    if (empty($endpoint_url)) {
        return $results;
    }
    
    // Get API data
    $data = get_api_data($endpoint_url, $endpoint);
    
    if (empty($data) || !is_array($data)) {
        return $results;
    }
    
    // Extract items based on items path
    $items_path = isset($source['items_path']) ? $source['items_path'] : '';
    $items = $data;
    
    if (!empty($items_path)) {
        $path_parts = explode('.', $items_path);
        
        foreach ($path_parts as $part) {
            if (isset($items[$part])) {
                $items = $items[$part];
            } else {
                // Path not found
                return $results;
            }
        }
    }
    
    // Ensure items is an array
    if (!is_array($items)) {
        $items = [$items];
    }
    
    // Add field prefix to item keys if specified
    $field_prefix = isset($source['field_prefix']) ? $source['field_prefix'] : '';
    
    if (!empty($field_prefix)) {
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $prefixed_item = [];
                
                foreach ($item as $item_key => $item_value) {
                    $prefixed_item[$field_prefix . $item_key] = $item_value;
                }
                
                $items[$key] = $prefixed_item;
            }
        }
    }
    
    // Limit to 10 items for preview
    $items = array_slice($items, 0, 10);
    
    // Add request URL to each item
    foreach ($items as $key => $item) {
        if (is_array($item)) {
            $items[$key]['_api_url'] = $endpoint_url;
        }
    }
    
    return [
        'count' => count($items),
        'items' => $items,
    ];
}

// === FORMULARIO DE SOURCES AVANZADO ===
function render_sources_page() {
    $endpoints = get_option('bricks_api_endpoints', []);
    ?>
    <div class="wrap">
        <h1>🔗 API Sources</h1>
        <p>Gestiona tus fuentes de datos avanzadas para Bricks Builder. Los Query Types y Dynamic Tags se generan automáticamente.</p>
        <style>
        .source-form-container {
          background: none;
          border: none;
          border-radius: 0;
          padding: 0;
          margin-bottom: 32px;
          box-shadow: none;
          max-width: 100%;
        }
        #source-form-title {
          margin-top: 0;
          margin-bottom: 18px;
          color: #222;
          font-size: 1.3em;
          font-weight: 600;
        }
        #source-form {
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
        .source-actions {
          margin-top: 18px;
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          align-items: center;
        }
        #source-test-result, #source-form-message {
          margin-top: 15px;
          max-width: 100%;
        }
        #sources-table {
          background: #fff;
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 2px 8px rgba(0,0,0,0.03);
          max-width: 100%;
          width: 100%;
        }
        #sources-table th, #sources-table td {
          padding: 12px 10px;
          vertical-align: middle;
        }
        #sources-table th {
          background: #f8f9fa;
          color: #333;
          font-weight: 600;
          border-bottom: 2px solid #e5e5e5;
        }
        #sources-table tr {
          border-bottom: 1px solid #f0f0f0;
        }
        #sources-table tr:last-child {
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
        #source-form-message.success {
          background: #e9fbe5;
          color: #256029;
          border: 1px solid #b6e2b3;
        }
        #source-form-message.error {
          background: #ffeaea;
          color: #a71d2a;
          border: 1px solid #f5c6cb;
        }
        </style>
        <!-- Formulario de alta/edición de Source -->
        <div class="source-form-container">
          <h2 id="source-form-title">Añadir nuevo Source</h2>
          <form id="source-form" method="post">
            <table class="form-table">
              <tr>
                <th><label for="source_name">Nombre del Source</label></th>
                <td><input type="text" id="source_name" name="source_name" class="regular-text" required></td>
              </tr>
              <tr>
                <th><label for="source_endpoint">Endpoint principal</label></th>
                <td><input type="text" id="source_endpoint" name="source_endpoint" class="regular-text" required></td>
              </tr>
              <tr>
                <th><label>Parámetros Dinámicos</label></th>
                <td>
                  <div id="dynamic-params-container"></div>
                  <button type="button" class="button" id="add-param-btn">Añadir Parámetro</button>
                </td>
              </tr>
            </table>
            <div style="margin-top: 18px;">
              <button type="submit" class="button button-primary" id="save-source-btn">Guardar Source</button>
              <button type="button" class="button" id="cancel-edit-btn" style="display:none;">Cancelar</button>
            </div>
          </form>
          <!-- Botones de acción debajo del formulario -->
          <div class="source-actions" style="margin-top: 18px;">
            <button type="button" class="button button-secondary" id="test-source-btn">🧪 Test Source</button>
            <button type="button" class="button button-success" id="refresh-source-btn">🔄 Actualizar Datos</button>
            <button type="button" class="button button-create-tags" id="generate-tags-btn">⚡ Generar Query Type y Tags Dinámicos</button>
            <button type="button" class="button button-view-tags" id="view-tags-btn" style="display:none;">🏷️ Ver Dynamic Tags</button>
            <button type="button" class="button button-delete-tags" id="delete-tags-btn" style="display:none;background:#dc3232;color:#fff;">🗑️ Eliminar tags y query type</button>
          </div>
          <div id="source-test-result" style="margin-top: 15px;"></div>
          <div id="source-form-message" style="margin-top: 10px;"></div>
        </div>
        <!-- Tabla de Sources configurados -->
        <h2 style="margin-top: 40px;">Sources configurados</h2>
        <table class="wp-list-table widefat fixed striped" id="sources-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Endpoint</th>
              <th>Parámetros</th>
              <th style="text-align:center;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Aquí se poblarán los Sources vía JS -->
          </tbody>
        </table>
    </div>
    <?php
}

// --- AJAX para guardar Source ---
add_action('wp_ajax_save_api_source', function() {
    check_ajax_referer('save_api_source', 'nonce');
    require_once __DIR__ . '/field-extractor.php';
    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = bricks_api_normalize_slug($name);
    $endpoint = sanitize_text_field($_POST['endpoint'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $params = $_POST['dynamic_params'] ?? [];
    $related = $_POST['related_endpoints'] ?? [];
    if (!$name || !$endpoint) {
        wp_send_json_error('Nombre y endpoint principal son obligatorios.');
    }
    $sources = get_option('bricks_api_sources', []);
    $sources[$slug] = [
        'name' => $name,
        'endpoint' => $endpoint,
        'items_path' => $items_path,
        'dynamic_params' => $params,
        'related_endpoints' => $related
    ];
    update_option('bricks_api_sources', $sources);
    wp_send_json_success(['sources' => $sources]);
});

// --- LÓGICA AJAX PARA TAGS DINÁMICOS DE SOURCES - CORREGIDO ---
add_action('wp_ajax_generate_source_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    
    // Verificar nonce
    check_ajax_referer('bricks_api_source_nonce', 'nonce');
    
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    
    $source = $sources[$source_id];
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Obtener datos usando la misma lógica que el query loop corregido
    $endpoint_id = $source['endpoint_id'] ?? '';
    $endpoints = get_option('bricks_api_endpoints', []);
    
    if (!isset($endpoints[$endpoint_id])) {
        wp_send_json_error('Endpoint no encontrado para este source');
    }
    
    $endpoint = $endpoints[$endpoint_id];
    
    // Añadir parámetros de paginación por defecto si no existen en la URL - IGUAL QUE TEST SOURCE
    $url = $endpoint['url'];
    $parsed_url = parse_url($url);
    $query_params = [];
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
    }
    
    // Añadir parámetros necesarios para obtener datos
    if (!isset($query_params['per_page'])) {
        $url = add_query_arg('per_page', 10, $url);
    }
    if (!isset($query_params['page'])) {
        $url = add_query_arg('page', 1, $url);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Configurar headers de autenticación si es necesario
    $args = [
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Bricks API Integrator/2.1.1'
        ]
    ];
    
    // Añadir autenticación básica si está configurada
    if (!empty($endpoint['auth_type']) && $endpoint['auth_type'] === 'basic') {
        $username = $endpoint['basic_user'] ?? $endpoint['auth_username'] ?? '';
        $password = $endpoint['basic_password'] ?? $endpoint['auth_password'] ?? '';
        if (!empty($username) && !empty($password)) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        }
    }
    
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        wp_send_json_error('Error de API: ' . $response->get_error_message());
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        wp_send_json_error('Error HTTP: ' . $status_code);
    }
    
    $body = wp_remote_retrieve_body($response);
    $raw_data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Error de JSON: ' . json_last_error_msg());
    }
    
    // Aplicar items_path usando la función corregida
    $items = $raw_data;
    if (!empty($source['items_path'])) {
        $path_parts = explode('.', $source['items_path']);
        $current_data = $raw_data;
        
        foreach ($path_parts as $part) {
            if (is_array($current_data) && isset($current_data[$part])) {
                $current_data = $current_data[$part];
            } elseif (is_object($current_data) && isset($current_data->$part)) {
                $current_data = $current_data->$part;
            } else {
                wp_send_json_error('Items path "' . $source['items_path'] . '" no encontrado en la respuesta.');
            }
        }
        
        $items = is_array($current_data) ? $current_data : [$current_data];
    }
    
    if (empty($items) || !is_array($items)) {
        wp_send_json_error('No se pudo obtener datos de la API o el array de items está vacío.');
    }
    
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    if (empty($first_item) || !is_array($first_item)) {
        wp_send_json_error('No se pudo extraer ningún campo del primer item de la API.');
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    // Recursivo: extraer todos los paths y valores
    function extract_tags_with_examples($item, $prefix = '') {
        $tags = [];
        foreach ($item as $key => $value) {
            $path = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $tags = array_merge($tags, extract_tags_with_examples($value, $path));
            } else if (is_array($value) && !empty($value)) {
                if (is_array($value[0] ?? null)) {
                    $tags = array_merge($tags, extract_tags_with_examples($value[0], $path . '[0]'));
                } else {
                    $tags[] = ['tag' => $path, 'example' => json_encode($value)];
                }
            } else {
                $tags[] = ['tag' => $path, 'example' => is_scalar($value) ? $value : json_encode($value)];
            }
        }
        return $tags;
    }
    $tags_with_examples = extract_tags_with_examples($first_item);
    // --- Construcción de tags idéntica a Endpoints ---
    $slug = bricks_api_normalize_slug($source['name']);
    $field_prefix = 'snap_'; // Igual que en Endpoints
    $tags_final = array_map(function($tagObj) use ($field_prefix, $slug) {
        $tag = is_array($tagObj) ? $tagObj['tag'] : $tagObj;
        // Normalizar el campo: puntos y corchetes a guiones bajos
        $normalized = preg_replace('/[.\[\]]+/', '_', $tag);
        $normalized = preg_replace('/_+/', '_', $normalized);
        $normalized = trim($normalized, '_');
        return '{' . $field_prefix . $slug . '_' . $normalized . '}';
    }, $tags_with_examples);
    $query_type = '{' . $field_prefix . $slug . '}';
    $group_title = $source['name'] . ' (Source)';
    $query_types = get_option('bricks_api_generated_query_types', []);
    $tags_data = get_option('bricks_api_generated_tags', []);
    $query_types[$slug] = [
        'query_type' => $query_type,
        'endpoint_name' => $source['name'],
        'group_title' => $group_title,
        'url' => $source['endpoint_id'] ?? '',
        'fields' => array_column($tags_with_examples, 'tag'),
        'example' => $first_item,
        'field_prefix' => $field_prefix
    ];
    $tags_data[$slug] = [
        'tags' => $tags_final,
        'group_title' => $group_title,
        'endpoint_name' => $source['name'],
        'example' => $first_item,
        'field_prefix' => $field_prefix
    ];
    update_option('bricks_api_generated_query_types', $query_types);
    update_option('bricks_api_generated_tags', $tags_data);
    
    // NUEVO: También guardar los tags en el source específico para mostrar botones
    $sources = get_option('bricks_api_sources', []);
    if (isset($sources[$source_id])) {
        // Obtener el primer ítem real de la API para este Source
        if (!trait_exists('APIManager')) {
            require_once __DIR__ . '/api-manager.php';
        }
        $api_manager = new class { public static $api_cache = []; use APIManager; };
        $items = $api_manager->get_api_data_by_source_id($source_id, true);
        $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        $sources[$source_id]['tags'] = $tags_final;
        $sources[$source_id]['example'] = $first_item;
        $sources[$source_id]['tags_generated'] = true;
        $sources[$source_id]['last_tag_generation'] = current_time('mysql');
        update_option('bricks_api_sources', $sources);
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
    }
    
    wp_send_json_success(['tags' => $tags_final]);
});
add_action('wp_ajax_get_tags_for_source', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    
    check_ajax_referer('bricks_api_source_nonce', 'nonce');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $source_name = sanitize_text_field($_POST['source_name'] ?? '');
    $full_info = isset($_POST['full_info']) && $_POST['full_info'];
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
        return;
    }
    
    $source = $sources[$source_id];
    $tags = $source['tags'] ?? [];
    $example = $source['example'] ?? [];
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    $disabled = $source['disabled_tags'] ?? [];
    $tag_objects = [];
    // Si hay tags y ejemplo, construir tag_objects igual que en endpoints usando bricks_api_extract_tags_recursive
    if ($full_info && !empty($tags) && !empty($example) && is_array($example)) {
        if (function_exists('bricks_api_extract_tags_recursive')) {
            $slug = bricks_api_normalize_slug($source['name']);
            $tag_objects = bricks_api_extract_tags_recursive($example, 'snap_' . $slug);
        }
    }
    // Asegurarse de que items_path esté definido en todos los casos
    $items_path = $source['items_path'] ?? '';
    $example_json = !empty($example) ? json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
    // --- DEBUG FINAL: log de example y tag_objects antes de enviar respuesta ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    wp_send_json_success([
        'tags' => $tags,
        'disabled_tags' => $disabled,
        'example' => $example,
        'example_json' => $example_json,
        'items_path' => $items_path,
        'tag_objects' => $tag_objects
    ]);
    // --- DEBUG: log de tag_objects antes de enviar respuesta ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    // --- DEBUG: log de variables antes de construir tag_objects ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    // --- DEBUG: log de $tags y $example antes de construir tag_objects ---
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    // Si $example está vacío, obtener el primer ítem real de la API y actualizar el Source
    if (empty($example)) {
        if (!trait_exists('APIManager')) {
            require_once __DIR__ . '/api-manager.php';
        }
        $api_manager = new class { public static $api_cache = []; use APIManager; };
        $items = $api_manager->get_api_data_by_source_id($source_id, true);
        $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
        $example = $first_item;
        // Actualizar el campo example en el Source
        $sources[$source_id]['example'] = $first_item;
        update_option('bricks_api_sources', $sources);
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
    }
});

add_action('wp_ajax_get_source_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    
    // Verificar nonce
    check_ajax_referer('bricks_api_source_nonce', 'nonce');
    
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $tags = $source['tags'] ?? [];
    $disabled = $source['disabled_tags'] ?? [];
    wp_send_json_success(['tags' => $tags, 'disabled_tags' => $disabled]);
});
add_action('wp_ajax_toggle_source_tag', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $tag = sanitize_text_field($_POST['tag'] ?? '');
    $enabled = $_POST['enabled'] === 'true';
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $disabled = $source['disabled_tags'] ?? [];
    if ($enabled) {
        $disabled = array_diff($disabled, [$tag]);
    } else {
        if (!in_array($tag, $disabled)) $disabled[] = $tag;
    }
    $source['disabled_tags'] = array_values($disabled);
    $sources[$source_id] = $source;
    update_option('bricks_api_sources', $sources);
    wp_send_json_success(['disabled_tags' => $source['disabled_tags']]);
});
add_action('wp_ajax_delete_source_and_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $slug = bricks_api_normalize_slug($source['name']);
    $query_types = get_option('bricks_api_generated_query_types', []);
    $tags_data = get_option('bricks_api_generated_tags', []);
    unset($query_types[$slug]);
    unset($tags_data[$slug]);
    update_option('bricks_api_generated_query_types', $query_types);
    update_option('bricks_api_generated_tags', $tags_data);
    wp_send_json_success('Tags y query type eliminados.');
});

// --- LÓGICA AJAX PARA TEST Y REFRESH DE SOURCE ---
add_action('wp_ajax_test_source_api', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $force_refresh = !empty($_POST['force_refresh']);
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    $items = $api_manager->get_api_data_by_source_id($source_id);
    if ($force_refresh) {
        // Forzar refresh de caché
        $endpoints = get_option('bricks_api_endpoints', []);
        $endpoint_id = $source['endpoint_id'] ?? '';
        $endpoint = $endpoints[$endpoint_id] ?? [];
        $url = $endpoint['url'] ?? '';
        $api_manager->get_api_data_with_cache($url, $endpoint, true); // true = forzar refresh
        $items = $api_manager->get_api_data_by_source_id($source_id); // Recargar
    }
    if (empty($items) || !is_array($items)) {
        wp_send_json_error('No se pudo obtener datos de la API o el array de items está vacío.');
    }
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    $fields = is_array($first_item) ? array_keys($first_item) : [];
    $preview = esc_html(print_r($first_item, true));
    wp_send_json_success(['fields' => $fields, 'preview' => $preview]);
});

// --- LÓGICA AJAX PARA TEST Y REFRESH DE SOURCE EN VIVO - CORREGIDO ---
add_action('wp_ajax_test_source_api_live', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bricks_api_source_nonce')) {
        wp_send_json_error('Nonce inválido.');
    }
    
    $endpoint_id = sanitize_text_field($_POST['endpoint_id'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $force_refresh = !empty($_POST['force_refresh']);
    
    $endpoints = get_option('bricks_api_endpoints', []);
    if(!$endpoint_id || !isset($endpoints[$endpoint_id])){
        wp_send_json_error('Endpoint no encontrado.');
    }
    
    $endpoint = $endpoints[$endpoint_id];
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Añadir parámetros de paginación por defecto si no existen en la URL
    $url = $endpoint['url'];
    $parsed_url = parse_url($url);
    $query_params = [];
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
    }
    
    // Añadir parámetros necesarios para obtener datos
    if (!isset($query_params['per_page'])) {
        $url = add_query_arg('per_page', 10, $url);
    }
    if (!isset($query_params['page'])) {
        $url = add_query_arg('page', 1, $url);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Configurar headers de autenticación si es necesario
    $args = [
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Bricks API Integrator/2.1.1'
        ]
    ];
    
    // Añadir autenticación básica si está configurada
    if (!empty($endpoint['auth_type']) && $endpoint['auth_type'] === 'basic') {
        $username = $endpoint['basic_user'] ?? $endpoint['auth_username'] ?? '';
        $password = $endpoint['basic_password'] ?? $endpoint['auth_password'] ?? '';
        if (!empty($username) && !empty($password)) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        wp_send_json_error('Error de API: ' . $response->get_error_message());
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        wp_send_json_error('Error HTTP: ' . $status_code);
    }
    
    $body = wp_remote_retrieve_body($response);
    $raw_data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        wp_send_json_error('Error de JSON: ' . json_last_error_msg());
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    // Aplicar items_path usando la función corregida
    $items = $raw_data;
    if (!empty($items_path)) {
        // Función corregida de extracción
        $path_parts = explode('.', $items_path);
        $current_data = $raw_data;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        
        foreach ($path_parts as $part) {
            if (is_array($current_data) && isset($current_data[$part])) {
                $current_data = $current_data[$part];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    if (is_array($current_data)) {
                        if (count($current_data) == 0) {
                        } else {
                        }
                    }
                }
            } elseif (is_object($current_data) && isset($current_data->$part)) {
                $current_data = $current_data->$part;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $available_keys = is_array($current_data) ? array_keys($current_data) : 
                                     (is_object($current_data) ? array_keys(get_object_vars($current_data)) : 'not array or object');
                }
                wp_send_json_error('Items path "' . $items_path . '" no encontrado en la respuesta.');
            }
        }
        
        $items = is_array($current_data) ? $current_data : [$current_data];
    }
    
    if (empty($items) || !is_array($items)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    $fields = is_array($first_item) ? array_keys($first_item) : [];
    $preview = esc_html(print_r($first_item, true));
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    
    wp_send_json_success(['fields' => $fields, 'preview' => $preview]);
});

// --- LÓGICA AJAX PARA PROBAR RUTA DE ITEMS ---
add_action('wp_ajax_test_items_path', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    
    // Omitir verificación de nonce temporalmente para depuración
    // Registrar la información recibida para depuración
    
    /* Comentado temporalmente para depuración
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'bricks_api_source_nonce')) {
        wp_send_json_error('Nonce inválido. Por favor, recarga la página e intenta de nuevo.');
    }
    */
    
    $endpoint_id = sanitize_text_field($_POST['endpoint_id'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    
    if (empty($endpoint_id)) {
        wp_send_json_error('ID de endpoint no proporcionado');
        return;
    }
    
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!isset($endpoints[$endpoint_id])) {
        wp_send_json_error('Endpoint no encontrado');
        return;
    }
    
    $endpoint = $endpoints[$endpoint_id];
    
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/api-manager.php';
    }
    
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    
    // Registrar información detallada para depuración
    // No registramos la URL base aquí porque la URL final se construirá con todos los parámetros
    
    // Preparar los headers para la solicitud
    $headers = [];
    if (!empty($endpoint['auth_type']) && $endpoint['auth_type'] === 'basic' && !empty($endpoint['basic_user']) && !empty($endpoint['basic_password'])) {
        $headers['Authorization'] = 'Basic ' . base64_encode($endpoint['basic_user'] . ':' . $endpoint['basic_password']);
    }
    
    if (!empty($endpoint['headers'])) {
        $headers = array_merge($headers, $endpoint['headers']);
    }
    
    
    // Obtener datos de la API con actualización forzada
    $url = $endpoint['url'];
    
    // Construir la URL con parámetros dinámicos exactamente como lo hace el endpoint
    if (!empty($endpoint['dynamic_params'])) {
        $params = [];
        foreach ($endpoint['dynamic_params'] as $param) {
            if (!empty($param['name'])) {
                // Obtener el valor del parámetro según su origen
                $param_value = '';
                
                if ($param['source'] === 'static' && isset($param['default'])) {
                    $param_value = $param['default'];
                } elseif ($param['source'] === 'url' && isset($param['url_param'])) {
                    // En este caso, usamos el valor por defecto ya que no tenemos acceso a la URL actual
                    $param_value = $param['default'] ?? '';
                } elseif ($param['source'] === 'post' && isset($param['post_meta'])) {
                    // En este caso, usamos el valor por defecto ya que no tenemos un post específico
                    $param_value = $param['default'] ?? '';
                }
                
                if ($param_value !== '') {
                    $params[$param['name']] = $param_value;
                }
            }
        }
        
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
    }
    
    // Si hay parámetros adicionales en el endpoint, agregarlos también
    if (!empty($endpoint['params'])) {
        $url = add_query_arg($endpoint['params'], $url);
    }
    
    // Para la API de Motoraldia o APIs similares, asegurarnos de que haya parámetros mínimos
    // para obtener datos reales (basado en la documentación de la API)
    if (strpos($url, 'api-motor/v1/vehicles') !== false) {
        // Verificar si ya existen los parámetros necesarios
        $parsed_url = parse_url($url);
        $query_params = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }
        
        // Asegurar que haya parámetros de paginación adecuados
        $params_to_add = [];
        if (!isset($query_params['per_page'])) {
            $params_to_add['per_page'] = 10;
        }
        if (!isset($query_params['page'])) {
            $params_to_add['page'] = 1;
        }
        
        // Añadir parámetros faltantes
        if (!empty($params_to_add)) {
            $url = add_query_arg($params_to_add, $url);
        }
    }
    
    // Registrar la URL final
    
    // Registrar información adicional en el log en lugar de imprimir en la respuesta
    
    // Realizar la solicitud HTTP directamente para tener más control
    $response = wp_remote_request($url, [
        'method' => $endpoint['method'] ?? 'GET',
        'headers' => $headers,
        'timeout' => 30,
        'sslverify' => false
    ]);
    
    // Verificar si hay errores en la solicitud
    if (is_wp_error($response)) {
        wp_send_json_error('Error de conexión: ' . $response->get_error_message());
        return;
    }
    
    // Obtener el código de respuesta
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        wp_send_json_error('Error en la respuesta del servidor: Código ' . $response_code);
        return;
    }
    
    // Obtener el cuerpo de la respuesta
    $body = wp_remote_retrieve_body($response);
    $api_data = json_decode($body, true);
    
    // Verificar si se pudo decodificar el JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Error al procesar la respuesta: ' . json_last_error_msg());
        return;
    }
    
    // Registrar el resultado para depuración
    
    if (empty($api_data)) {
        wp_send_json_error('No se pudieron obtener datos de la API');
        return;
    }
    
    // Preparar las claves disponibles en la respuesta para ayudar al usuario
    $available_keys = [];
    if (is_array($api_data)) {
        $available_keys = array_keys($api_data);
    } elseif (is_object($api_data)) {
        $available_keys = array_keys(get_object_vars($api_data));
    }
    
    // Si no hay ruta específica pero los datos están en una estructura común como 'items', 'data', 'results', etc.
    // mostrar esas claves como opciones disponibles pero no seleccionar ninguna automáticamente
    $common_data_keys = ['items', 'data', 'results', 'content', 'records', 'list'];
    $data_containers = array_intersect($available_keys, $common_data_keys);
    
    if (!empty($data_containers)) {
    }
    
    // Si no hay ruta de items, devolver la respuesta completa
    if (empty($items_path)) {
        wp_send_json_success([
            'found' => true,
            'data' => $api_data,
            'available_keys' => $available_keys
        ]);
        return;
    }
    
    // Usamos la ruta especificada en el campo de entrada
    $path_to_test = $items_path;
    
    // Registrar información para depuración
    
    // Extraer datos según la ruta especificada
    $items_data = $api_data;
    
    if (!empty($path_to_test)) {
        $path_parts = explode('.', $path_to_test);
        
        // Registrar el proceso de extracción para depuración
        
        // Registrar la estructura inicial de los datos
        if (is_array($items_data)) {
        } elseif (is_object($items_data)) {
        }
        
        $current_path = '';
        foreach ($path_parts as $part) {
            $current_path = $current_path ? $current_path . '.' . $part : $part;
            
            // Registrar el paso actual
            
            if (is_array($items_data) && isset($items_data[$part])) {
                $items_data = $items_data[$part];
            } elseif (is_object($items_data) && isset($items_data->$part)) {
                $items_data = $items_data->$part;
            } else {
                
                // Registrar las claves disponibles en este nivel para ayudar a depurar
                if (is_array($items_data)) {
                } elseif (is_object($items_data)) {
                }
                
                wp_send_json_success([
                    'found' => false,
                    'error' => "No se encontró la ruta '{$current_path}' en la respuesta",
                    'available_keys' => $available_keys,
                    'current_data' => $api_data // Mostrar los datos originales para ayudar a depurar
                ]);
                return;
            }
        }
    }
    
    // Registrar el resultado final
    
    // Verificar si se encontraron datos en la ruta
    if ($items_data !== null) {
        // Si los datos son un array vacío, proporcionar información útil sobre la estructura
        if (is_array($items_data) && empty($items_data)) {
            
            // Analizar la estructura de la respuesta para proporcionar sugerencias útiles
            $suggestions = [];
            $data_paths = [];
            
            // Función recursiva para encontrar arrays no vacíos en la respuesta
            $find_data_arrays = function($data, $path = '') use (&$find_data_arrays, &$data_paths) {
                if (is_array($data) || is_object($data)) {
                    $data = (array)$data;
                    foreach ($data as $key => $value) {
                        $current_path = $path ? $path . '.' . $key : $key;
                        if (is_array($value) && !empty($value)) {
                            $data_paths[] = [
                                'path' => $current_path,
                                'count' => count($value),
                                'sample' => array_slice($value, 0, 1)
                            ];
                        }
                        if (is_array($value) || is_object($value)) {
                            $find_data_arrays($value, $current_path);
                        }
                    }
                }
            };
            
            // Buscar arrays no vacíos en la respuesta original
            $find_data_arrays($api_data);
            
            if (!empty($data_paths)) {
                
                // Ordenar por cantidad de elementos (descendente)
                usort($data_paths, function($a, $b) {
                    return $b['count'] - $a['count'];
                });
                
                // Sugerir las rutas más prometedoras
                foreach ($data_paths as $data_path) {
                    $suggestions[] = $data_path['path'] . ' (' . $data_path['count'] . ' elementos)';
                }
            }
            
            // Agregar sugerencias a la respuesta
            if (!empty($suggestions)) {
                $items_data = [
                    '_empty_array' => true,
                    '_suggestions' => $suggestions
                ];
            }
        }
        
        // Preparar mensaje según el tipo de datos encontrados
        $message = 'Ruta encontrada correctamente.';
        $suggestions = [];
        $display_data = $items_data; // Por defecto, mostrar todos los datos
        
        if (is_array($items_data)) {
            if (empty($items_data)) {
                $message = 'Se encontró la ruta pero no contiene datos. Esto puede ser normal si no hay resultados o si se necesitan parámetros adicionales.';
            } else if (isset($items_data['_suggestions'])) {
                $message = 'La ruta especificada contiene un array vacío. Prueba con alguna de estas rutas alternativas:';
                $suggestions = $items_data['_suggestions'];
                unset($items_data['_suggestions']);
                unset($items_data['_empty_array']);
            } else {
                $count = count($items_data);
                $message = "Ruta encontrada correctamente. Contiene {$count} " . ($count == 1 ? 'elemento.' : 'elementos.');
                
                // Si es un array de objetos o arrays (lista), mostrar solo el primer elemento
                if ($count > 0) {
                    $first_item = $items_data[0];
                    
                    // Verificar si es una lista de elementos (array indexado con objetos o arrays)
                    $is_list = true;
                    foreach ($items_data as $key => $value) {
                        if (!is_numeric($key) || (!is_array($value) && !is_object($value))) {
                            $is_list = false;
                            break;
                        }
                    }
                    
                    if ($is_list && $count > 1) {
                        $display_data = $first_item;
                        $message .= " Mostrando el primer elemento como ejemplo.";
                    }
                }
            }
        } elseif (is_object($items_data)) {
            $message = 'Ruta encontrada correctamente. Contiene un objeto con ' . count(get_object_vars($items_data)) . ' propiedades.';
        } elseif (is_string($items_data)) {
            $message = 'Ruta encontrada correctamente. Contiene un valor de texto.';
        } elseif (is_numeric($items_data)) {
            $message = 'Ruta encontrada correctamente. Contiene un valor numérico.';
        } elseif (is_bool($items_data)) {
            $message = 'Ruta encontrada correctamente. Contiene un valor booleano (' . ($items_data ? 'true' : 'false') . ').';
        }
        
        // Limpiar y sanitizar los datos para asegurar que sean JSON válido
        $clean_data = $display_data;
        
        // Asegurarnos de que no haya salida previa que pueda corromper el JSON
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Eliminar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Definir el tipo de contenido como JSON
        header('Content-Type: application/json');
        
        // Registrar la respuesta final que se enviará al frontend
        
        // Usar wp_send_json_success que maneja correctamente la estructura que espera el frontend
        wp_send_json_success([
            'found' => true,
            'data' => $clean_data,
            'message' => $message
        ]);
    } else {
        wp_send_json_success([
            'found' => false,
            'error' => "La ruta '{$items_path}' existe pero no contiene datos",
            'available_keys' => $available_keys
        ]);
    }
});

// --- LÓGICA AJAX PARA PREVIEW DE SOURCE ---
add_action('wp_ajax_preview_source_api', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bricks_api_source_nonce')) {
        wp_send_json_error('Nonce inválido.');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    // Registrar información de depuración
    
    // Obtener datos de la API con forzado de refresco para asegurar datos actualizados
    $items = $api_manager->get_api_data_by_source_id($source_id, true);
    
    // Registrar los primeros caracteres de la respuesta para depuración
    
    // Detectar posibles claves que podrían contener los datos
    $available_keys = [];
    if (is_array($items)) {
        $available_keys = array_keys($items);
    } elseif (is_object($items)) {
        $available_keys = array_keys(get_object_vars($items));
    } else {
    }
    
    // Procesar items_path de manera robusta
    $items_path = !empty($source['items_path']) ? $source['items_path'] : '';
    $items_data = $items; // Por defecto, usar todos los datos
    
    if (!empty($items_path) && (is_array($items) || is_object($items))) {
        
        // Dividir la ruta en partes
        $path_parts = explode('.', $items_path);
        
        // Navegar por la estructura de datos según la ruta
        $current_data = $items;
        $found = true;
        
        foreach ($path_parts as $part) {
            // Registrar las claves disponibles en el nivel actual
            if (is_array($current_data)) {
            } elseif (is_object($current_data)) {
            }
            
            
            if ((is_array($current_data) && isset($current_data[$part])) || 
                (is_object($current_data) && isset($current_data->$part))) {
                
                $current_data = is_array($current_data) ? $current_data[$part] : $current_data->$part;
            } else {
                $found = false;
                break;
            }
        }
        
        if ($found) {
            $items_data = $current_data;
        } else {
            // Si no se encontró la ruta especificada, intentamos algunas estrategias comunes
            
            // Estrategia 1: Buscar datos en un campo 'data' común en muchas APIs
            if (isset($items['data']) && !empty($items['data'])) {
                $items_data = $items['data'];
            } 
            // Estrategia 2: Buscar un array en la respuesta principal
            else if (is_array($items) && count($items) > 0 && isset($items[0])) {
                $items_data = $items;
            }
            // Estrategia 3: Usar la respuesta completa como último recurso
            else if (!empty($items)) {
                $items_data = $items;
            }
            // Si no se pudo encontrar ninguna estructura de datos utilizable
            else {
                $keys_info = !empty($available_keys) ? implode(', ', $available_keys) : 'No hay claves disponibles';
                wp_send_json_error('Items path "'.$items_path.'" no encontrado en la respuesta. Claves disponibles: ' . $keys_info);
                return;
            }
        }
    }
    
    // Verificar que tenemos datos para mostrar
    if (empty($items_data)) {
        wp_send_json_error('La API devolvió datos pero la ruta especificada está vacía.');
        return;
    }
    
    // Determinar si es una lista de elementos o un objeto único
    $is_list = false;
    $count = 0;
    $first_item = null;
    
    if (is_array($items_data)) {
        $count = count($items_data);
        
        // Verificar si es una lista de elementos (array indexado con objetos o arrays)
        $is_list = true;
        foreach ($items_data as $key => $value) {
            if (!is_numeric($key) || (!is_array($value) && !is_object($value))) {
                $is_list = false;
                break;
            }
        }
        
        if ($is_list && $count > 0) {
            $first_item = $items_data[0];
        } else {
            $first_item = $items_data;
        }
    } elseif (is_object($items_data)) {
        $first_item = $items_data;
    } else {
        $first_item = ['value' => $items_data];
    }
    
    // Convertir objetos a arrays para consistencia
    if (is_object($first_item)) {
        $first_item = json_decode(json_encode($first_item), true);
    }
    
    // Extraer campos disponibles
    $fields = is_array($first_item) ? array_keys($first_item) : [];
    // Preparar información adicional para la vista previa
    $total_items = is_array($items) ? count($items) : 0;
    $endpoint_info = [];
    
    // Obtener información del endpoint asociado
    if (!empty($source['endpoint_id'])) {
        $endpoints = get_option('bricks_api_endpoints', []);
        if (isset($endpoints[$source['endpoint_id']])) {
            $endpoint = $endpoints[$source['endpoint_id']];
            $endpoint_info = [
                'name' => $endpoint['name'] ?? '',
                'url' => $endpoint['url'] ?? '',
                'method' => $endpoint['method'] ?? 'GET',
                'auth_type' => $endpoint['auth_type'] ?? 'none'
            ];
        }
    }
    
    // Obtener información de la ruta de elementos
    $items_path_info = !empty($source['items_path']) ? $source['items_path'] : 'Raíz de la respuesta';
    
    // Devolver el objeto real con información adicional
    wp_send_json_success([
        'fields' => $fields, 
        'preview' => $first_item,
        'total_items' => $total_items,
        'endpoint' => $endpoint_info,
        'items_path' => $items_path_info
    ]);
});

// --- Registro seguro de la clase y filtro solo si Bricks está cargado ---
add_action('init', function() {
    if (defined('BRICKS_VERSION') && class_exists('Bricks_Query_Provider')) {
        add_filter('bricks/query/sources', 'register_api_sources_with_bricks');
        register_api_source_query_class();
    }
}, 20);

// --- Registro seguro de la clase y filtro SOLO cuando Bricks ha cargado completamente ---
if (has_action('bricks/loaded')) {
    add_action('bricks/loaded', function() {
        if (class_exists('Bricks_Query_Provider')) {
            add_filter('bricks/query/sources', 'register_api_sources_with_bricks');
            register_api_source_query_class();
        }
    }, 20);
} else {
    add_action('after_setup_theme', function() {
        if (defined('BRICKS_VERSION') && class_exists('Bricks_Query_Provider')) {
            add_filter('bricks/query/sources', 'register_api_sources_with_bricks');
            register_api_source_query_class();
        }
    }, 20);
}

// --- REGISTRO DE QUERY TYPES DE SOURCES COMO TIPOS ESTÁNDAR (SIN CLASE PERSONALIZADA) ---
add_filter('bricks/query/sources', function($sources) {
    $api_sources = get_option('bricks_api_sources', []);
    if (empty($api_sources)) return $sources;
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        $sources['source_' . $source_id] = [
            'name' => $display_name,
        ];
    }
    return $sources;
}, 20);

// --- DEVOLVER DATOS PARA EL LOOP DE BRICKS DESDE SOURCES ---
add_filter('bricks/query/run', function($results, $query_obj) {
    $object_type = isset($query_obj->object_type) ? $query_obj->object_type : '';
    if (strpos($object_type, 'source_') !== 0) return $results;
    $source_id = str_replace('source_', '', $object_type);
    $api_sources = get_option('bricks_api_sources', []);
    if (!isset($api_sources[$source_id])) return $results;
    $source = $api_sources[$source_id];
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
    if (!isset($endpoints[$endpoint_id])) return $results;
    $endpoint = $endpoints[$endpoint_id];
    $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
    if (empty($endpoint_url)) return $results;
    // Obtener datos de la API
    if (!function_exists('get_api_data')) require_once __DIR__ . '/api-manager.php';
    $data = get_api_data($endpoint_url, $endpoint);
    if (empty($data) || !is_array($data)) return $results;
    // Extraer items según items_path
    $items_path = isset($source['items_path']) ? $source['items_path'] : '';
    $items = $data;
    if (!empty($items_path)) {
        $path_parts = explode('.', $items_path);
        foreach ($path_parts as $part) {
            if (isset($items[$part])) {
                $items = $items[$part];
            } else {
                return $results;
            }
        }
    }
    if (!is_array($items)) $items = [$items];
    // Formatear items para Bricks
    $formatted = [];
    foreach ($items as $item) {
        $formatted[] = is_array($item) ? (object)$item : $item;
    }
    return [
        'items' => $formatted,
        'count' => count($formatted),
        'found_posts' => count($formatted),
        'post_count' => count($formatted),
    ];
}, 20, 2);
