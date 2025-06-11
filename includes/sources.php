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
                                        <button type="button" class="button test-items-path" data-endpoint-id="<?php echo esc_attr($source_to_edit['endpoint_id']); ?>" data-source-id="<?php echo esc_attr($source_id_to_edit); ?>">
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
                                    <?php if ($editing && !empty($source_to_edit['dynamic_params'])): ?>
                                        <?php foreach ($source_to_edit['dynamic_params'] as $param): ?>
                                            <div class="dynamic-param-row">
                                                <input type="text" name="param_names[]" placeholder="<?php esc_attr_e('Nombre del Parámetro (ej. id)', 'bricks-api-integrator'); ?>" class="regular-text" value="<?php echo esc_attr($param['name']); ?>">
                                                <select name="param_sources[]">
                                                    <option value="url" <?php selected($param['source'], 'url'); ?>><?php esc_html_e('Parámetro URL', 'bricks-api-integrator'); ?></option>
                                                    <option value="post" <?php selected($param['source'], 'post'); ?>><?php esc_html_e('ID del Post', 'bricks-api-integrator'); ?></option>
                                                    <option value="user" <?php selected($param['source'], 'user'); ?>><?php esc_html_e('ID del Usuario', 'bricks-api-integrator'); ?></option>
                                                    <option value="static" <?php selected($param['source'], 'static'); ?>><?php esc_html_e('Valor Estático', 'bricks-api-integrator'); ?></option>
                                                </select>
                                                <input type="text" name="param_defaults[]" placeholder="<?php esc_attr_e('Valor por Defecto (opcional)', 'bricks-api-integrator'); ?>" class="regular-text" value="<?php echo esc_attr($param['default']); ?>">
                                                <button type="button" class="button remove-param"><?php esc_html_e('Eliminar', 'bricks-api-integrator'); ?></button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add-param" class="button"><?php esc_html_e('Añadir Parámetro', 'bricks-api-integrator'); ?></button>
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
    <?php $bricks_api_source_nonce = wp_create_nonce('bricks_api_source'); ?>
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
            $.post(ajaxurl, {action:'get_source_tags', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
                if(res.success && res.data.tags && res.data.tags.length){
                    $('#view-source-tags-btn').show();
                    $('#delete-source-tags-btn').show();
                    // Mostrar tabla avanzada automáticamente
                    renderTagsTable(res, sourceId);
            } else {
                    $('#view-source-tags-btn').hide();
                    $('#delete-source-tags-btn').hide();
                }
            });
        }
        // Llamar al cargar si estamos editando
        if ($('input[name="source_id"]').length) {
            autoShowTagsIfExist();
        }
        // --- Mejorar visualización de tags dinámicos ---
        function renderTagsTable(res, sourceId) {
            // Obtener el primer item de ejemplo para las secciones avanzadas
            $.post(ajaxurl, {action:'preview_source_api', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(previewRes){
                var example = {};
                var exampleJson = '';
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
                }
                // 1. Respuesta de la API
                var html = '<div style="margin-bottom:18px;"><strong>📦 Respuesta de la API (primer registro o detalle):</strong><pre style="background:#f8f9fa;padding:10px;border-radius:5px;max-height:300px;overflow:auto;font-size:13px;">'+exampleJson+'</pre></div>';
                // 2. Estructura detectada (tabla recursiva)
                function renderStructure(obj, prefix='') {
                    let rows = '';
                    Object.entries(obj).forEach(function([key, value]){
                        let tipo = Array.isArray(value) ? 'Array' : typeof value;
                        let valEjemplo = (typeof value === 'object' && value !== null)
                            ? JSON.stringify(value, null, 2)
                            : value;
                        let fullKey = prefix ? prefix+'.'+key : key;
                        rows += '<tr><td style="padding:6px 8px;border:1px solid #e3e3e3;">'+fullKey+'</td><td style="padding:6px 8px;border:1px solid #e3e3e3;">'+tipo+'</td><td style="padding:6px 8px;border:1px solid #e3e3e3;font-family:monospace;">'+valEjemplo+'</td></tr>';
                        if(typeof value === 'object' && value !== null && !Array.isArray(value)){
                            rows += renderStructure(value, fullKey);
                        }
                    });
                    return rows;
                }
                html += '<div style="margin-bottom:18px;"><strong>🧩 Estructura detectada:</strong><table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;"><thead><tr style="background:#e7f3ff;"><th style="padding:6px 8px;border:1px solid #e3e3e3;">Campo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Tipo</th><th style="padding:6px 8px;border:1px solid #e3e3e3;">Valor de ejemplo</th></tr></thead><tbody>';
                if(example && typeof example === 'object' && Object.keys(example).length){
                    html += renderStructure(example);
                } else {
                    html += '<tr><td colspan="3" style="text-align:center;">Sin datos de ejemplo</td></tr>';
                }
                html += '</tbody></table></div>';
                // 3. Tags dinámicos generados
                html += '<div style="margin-bottom:18px;"><strong>🏷️ Tags dinámicos generados:</strong><form id="tags-enable-form"><div style="display:grid;gap:8px;margin-top:10px;">';
                res.data.tags.forEach(function(tagObj, idx){
                    var tag = tagObj.tag;
                    var exampleVal = tagObj.example;
                    var checked = res.data.disabled_tags.includes(tag) ? '' : 'checked';
                    var tagStr = '{' + tag + '}';
                    var safeId = 'tag-enable-' + idx;
                    html += '<div class="dynamic-tag-item" style="display:flex;align-items:center;gap:10px;background:#f8f9fa;padding:8px 12px;border-radius:5px;">';
                    html += '<input type="checkbox" id="'+safeId+'" class="tag-enable-checkbox" data-tag="'+tag+'" '+checked+' style="margin-right:6px;">';
                    html += '<label for="'+safeId+'" style="margin:0;cursor:pointer;">';
                    html += '<code class="dynamic-tag" style="font-size:14px;color:#e67e22;font-weight:bold;">'+tagStr+'</code>';
                    html += '</label>';
                    html += '<button type="button" class="button button-small copy-tag-btn" data-copy="'+tagStr+'" style="margin-left:10px;">Copiar tag</button>';
                    html += '<span class="arrow" style="color:#888;">→</span>';
                    html += '<span class="dynamic-tag-value" style="font-family:monospace;background:#fff;padding:2px 6px;border-radius:3px;">'+(exampleVal||'')+'</span>';
                    html += '<button type="button" class="button button-small copy-val-btn" data-copy="'+(exampleVal||'')+'" style="margin-left:10px;">Copiar valor</button>';
                    html += '</div>';
                });
                html += '</div></form></div>';
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
                        source_id: sourceId,
                        enabled_tags: enabledTags
                    }, function(resp2){
                        if (resp2.success) {
                            alert('Selección de tags guardada.');
                } else {
                            alert('Error al guardar la selección de tags.');
                }
            });
        });
            });
        }
        // --- Ocultar botón crear tags al crear nuevo Source ---
        if (!$('input[name="source_id"]').length) {
            $('#generate-source-tags-btn').hide();
        }
        $('#view-source-tags-btn').off('click').on('click', function(){
          var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
          renderAdvancedSections(sourceId);
        });
        // --- Copiar al portapapeles ---
        $(document).on('click','.copy-tag-btn',function(){
            var val = $(this).data('copy');
            navigator.clipboard.writeText(val);
            $(this).text('¡Copiado!');
            var btn = $(this);
            setTimeout(function(){ btn.text('Copiar tag'); }, 1200);
        });
        $(document).on('click','.copy-val-btn',function(){
            var val = $(this).data('copy');
            navigator.clipboard.writeText(val);
            $(this).text('¡Copiado!');
            var btn = $(this);
            setTimeout(function(){ btn.text('Copiar valor'); }, 1200);
        });
        // --- Toggle habilitar/deshabilitar tag ---
        $('#source-test-result').on('change','.toggle-tag',function(){
            var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            var tag = $(this).data('tag');
            var enabled = $(this).is(':checked');
            $.post(ajaxurl, {action:'toggle_source_tag', source_id:sourceId, tag:tag, enabled:enabled, nonce: window.bricksApiSourceNonce}, function(res){
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
                    html += '<div style="background:#f8f9fa;padding:12px;border-radius:6px;margin-top:10px;">';
                    html += '<b>Primer item:</b><br><pre style="max-height:300px;overflow:auto;">'+JSON.stringify(res.data.preview, null, 2)+'</pre>';
                    if(res.data.fields && res.data.fields.length){
                        html += '<b>Campos detectados:</b> '+res.data.fields.join(', ');
                    }
                    html += '</div>';
                    showSourcePreviewModal(html);
                }else{
                    showSourcePreviewModal('<span style="color:#c00">'+res.data+'</span>');
                }
            });
        });
        // --- Botón crear tags dinámicos ---
        $('#generate-source-tags-btn').off('click').on('click', function(){
            var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
            $('#generate-source-tags-btn').prop('disabled', true).text('Generando...');
            $.post(ajaxurl, {action:'generate_source_tags', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
                $('#generate-source-tags-btn').prop('disabled', false).text('⚡ Crear tags y query types dinámicos');
                if(res.success){
                    alert('Tags generados correctamente');
                    $('#view-source-tags-btn').show();
                    $('#delete-source-tags-btn').show();
                    // Mostrar tabla avanzada automáticamente
                    autoShowTagsIfExist();
                }else{
                    alert('Error: '+res.data);
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
    // Si estamos editando y no se envían parámetros, mantener los existentes
    if ($editing && empty($dynamic_params) && isset($api_sources[$source_id]['dynamic_params'])) {
        $dynamic_params = $api_sources[$source_id]['dynamic_params'];
    }
    
    // Get existing sources
    $api_sources = get_option('bricks_api_sources', []);
    
    // Check if we're editing an existing query type
    $editing = isset($_POST['source_id']) && !empty($_POST['source_id']);
    $source_id = $editing ? sanitize_text_field($_POST['source_id']) : 'query_type_' . time();
    
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
            error_log('Se eliminó un parámetro anunci-actiu no deseado durante el guardado del Query Type');
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
    error_log('Registrando Query Types con Bricks:');
    error_log('Query Types disponibles: ' . print_r($api_sources, true));
    
    if (empty($api_sources)) {
        error_log('No hay Query Types configurados');
        return $sources;
    }
    
    // Add each Query Type to Bricks sources
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        
        $sources[$source_id] = [
            'name'  => $display_name,
            'class' => 'Bricks_API_Source_Query', // Custom query class we'll create
        ];
        error_log('Registrando Query Type: ' . $source_id . ' - ' . $display_name);
    }
    
    // Log para depuración
    error_log('Sources finales registrados con Bricks: ' . print_r($sources, true));
    
    return $sources;
}
// TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
// add_filter('bricks/query/sources', 'register_api_sources_with_bricks');

/**
 * Create custom query class for Query Types
 */
function register_api_source_query_class() {
    // Verificar si Bricks está activo
    if (!defined('BRICKS_VERSION')) {
        error_log('Bricks no está activo, no se puede registrar Bricks_API_Source_Query');
        return;
    }
    
    // Verificar si la clase Bricks_Query_Provider existe
    if (!class_exists('Bricks_Query_Provider')) {
        error_log('Bricks_Query_Provider no existe, no se puede registrar Bricks_API_Source_Query');
        return;
    }
    
    error_log('Registrando clase Bricks_API_Source_Query');
    
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
            error_log('API Request URL: ' . $request_url);
            error_log('API Request Args: ' . print_r($args, true));
            error_log('Source ID: ' . $source_id);
            error_log('Source Config: ' . print_r($source, true));
            error_log('Query Args: ' . print_r($query_args, true));
            
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
                error_log('API Request Error: ' . $response->get_error_message());
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => $response->get_error_message(),
                ];
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                error_log('API Request Error: Status code ' . $status_code);
                return [
                    'count' => 0,
                    'items' => [],
                    'error' => sprintf(esc_html__('API returned error code: %d', 'bricks-api-integrator'), $status_code),
                ];
            }
            
            $body = wp_remote_retrieve_body($response);
            error_log('API Response Body: ' . substr($body, 0, 1000) . (strlen($body) > 1000 ? '... (truncated)' : ''));
            
            $data = json_decode($body, true);
            error_log('API Response Data (decoded): ' . print_r(array_slice($data, 0, 10, true), true) . (count($data) > 10 ? '... (truncated)' : ''));
            
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
// TEMPORALMENTE DESACTIVADO PARA EVITAR DUPLICACIÓN
// add_action('init', 'register_api_source_query_class');

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

// --- LÓGICA AJAX PARA TAGS DINÁMICOS DE SOURCES ---
add_action('wp_ajax_generate_source_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    // --- Generación real de tags dinámicos con valores de ejemplo ---
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    $items = $api_manager->get_api_data_by_source_id($source_id);
    if (empty($items) || !is_array($items)) {
        wp_send_json_error('No se pudo obtener datos de la API o el array de items está vacío.');
    }
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    if (empty($first_item) || !is_array($first_item)) {
        wp_send_json_error('No se pudo extraer ningún campo del primer item de la API.');
    }
    // Recursivo: extraer todos los paths y valores
    function extract_tags_with_examples($item, $prefix = '') {
        $tags = [];
        foreach ($item as $key => $value) {
            $path = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                // Es un objeto asociativo
                $tags = array_merge($tags, extract_tags_with_examples($value, $path));
            } else if (is_array($value) && !empty($value)) {
                // Es un array indexado, tomar el primer item si es objeto
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
    $source['tags'] = $tags_with_examples;
    $source['disabled_tags'] = $source['disabled_tags'] ?? [];
    $sources[$source_id] = $source;
    update_option('bricks_api_sources', $sources);
    wp_send_json_success(['tags' => $tags_with_examples, 'disabled_tags' => $source['disabled_tags']]);
});
add_action('wp_ajax_get_source_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
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
    unset($sources[$source_id]);
    update_option('bricks_api_sources', $sources);
    wp_send_json_success();
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

// --- LÓGICA AJAX PARA TEST Y REFRESH DE SOURCE EN VIVO ---
add_action('wp_ajax_test_source_api_live', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bricks_api_source')) {
        error_log('Nonce inválido en test_source_api_live: '.print_r($_POST['nonce'],true));
        wp_send_json_error('Nonce inválido.');
    }
    $endpoint_id = sanitize_text_field($_POST['endpoint_id'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $field_prefix = sanitize_text_field($_POST['field_prefix'] ?? '');
    $pagination_type = sanitize_text_field($_POST['pagination_type'] ?? 'none');
    $pagination_param = sanitize_text_field($_POST['pagination_param'] ?? '');
    $per_page_param = sanitize_text_field($_POST['per_page_param'] ?? '');
    // Procesar parámetros dinámicos como arrays
    $param_names = isset($_POST['param_names']) ? (array)$_POST['param_names'] : [];
    $param_sources = isset($_POST['param_sources']) ? (array)$_POST['param_sources'] : [];
    $param_defaults = isset($_POST['param_defaults']) ? (array)$_POST['param_defaults'] : [];
    $dynamic_params = [];
    foreach ($param_names as $i => $name) {
        if (!empty($name)) {
            $dynamic_params[] = [
                'name' => $name,
                'source' => $param_sources[$i] ?? 'url',
                'default' => $param_defaults[$i] ?? ''
            ];
        }
    }
    $force_refresh = !empty($_POST['force_refresh']);
    $endpoints = get_option('bricks_api_endpoints', []);
    if(!$endpoint_id || !isset($endpoints[$endpoint_id])){
        wp_send_json_error('Endpoint no encontrado.');
    }
    $endpoint = $endpoints[$endpoint_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    // Construir la URL dinámica
    $pagination_config = [
        'type' => $pagination_type,
        'param' => $pagination_param,
        'per_page_param' => $per_page_param,
        'page' => 1,
        'per_page' => 10
    ];
    $url = $api_manager->build_dynamic_api_url($endpoint['url'], $dynamic_params, $pagination_config, []);
    // Forzar refresh de caché si se solicita
    if($force_refresh){
        $api_manager->get_api_data_with_cache($url, $endpoint, true);
    }
    $items = $api_manager->get_api_data_with_cache($url, $endpoint, false);
    // Procesar items_path
    if(!empty($items_path) && is_array($items)){
        $path_parts = explode('.', $items_path);
        foreach($path_parts as $part){
            if(isset($items[$part])){
                $items = $items[$part];
            }else{
                wp_send_json_error('Items path "'.$items_path.'" no encontrado en la respuesta.');
            }
        }
    }
    if(empty($items) || !is_array($items)){
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    $fields = is_array($first_item) ? array_keys($first_item) : [];
    $preview = esc_html(print_r($first_item, true));
    wp_send_json_success(['fields' => $fields, 'preview' => $preview]);
});

// --- LÓGICA AJAX PARA PREVIEW DE SOURCE ---
add_action('wp_ajax_preview_source_api', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bricks_api_source')) {
        error_log('Nonce inválido en preview_source_api: '.print_r($_POST['nonce'],true));
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
    $items = $api_manager->get_api_data_by_source_id($source_id);
    // Procesar items_path igual que en Endpoints
    if (!empty($source['items_path']) && is_array($items)) {
        $path_parts = explode('.', $source['items_path']);
        foreach ($path_parts as $part) {
            if (isset($items[$part])) {
                $items = $items[$part];
            } else {
                wp_send_json_error('Items path "'.$source['items_path'].'" no encontrado en la respuesta.');
            }
        }
    }
    if (empty($items) || !is_array($items)) {
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    $fields = is_array($first_item) ? array_keys($first_item) : [];
    // Devolver el objeto real, no string
    wp_send_json_success(['fields' => $fields, 'preview' => $first_item]);
});
