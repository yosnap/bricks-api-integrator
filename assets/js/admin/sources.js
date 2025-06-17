// assets/js/admin/sources.js
// JavaScript for managing dynamic parameters in Sources (Query Types)
jQuery(document).ready(function($) {
    // Add a new dynamic parameter row
    function addParamRow(name = '', source = 'url', def = '') {
        const row = $(
            `<div class="dynamic-param-row" style="margin-bottom:6px;display:flex;gap:6px;align-items:center;">
                <input type="text" name="param_names[]" class="regular-text param-name" placeholder="Nombre" value="${name}">
                <select name="param_sources[]" class="param-source">
                    <option value="url" ${source === 'url' ? 'selected' : ''}>Parámetro URL</option>
                    <option value="post" ${source === 'post' ? 'selected' : ''}>ID Post</option>
                    <option value="user" ${source === 'user' ? 'selected' : ''}>ID Usuario</option>
                    <option value="static" ${source === 'static' ? 'selected' : ''}>Valor Estático</option>
                </select>
                <input type="text" name="param_defaults[]" class="regular-text param-default" placeholder="Valor por defecto" value="${def}">
                <button type="button" class="button button-delete-param" style="background:#dc3232;color:#fff;">Eliminar</button>
            </div>`
        );
        row.find('.button-delete-param').click(function(){ row.remove(); });
        $('#dynamic-params-container').append(row);
    }

    // Add parameter on button click (corregido para evitar duplicados)
    $('#add-param').off('click').on('click', function(){ addParamRow(); });

    // Ensure delete buttons work for existing rows
    $(document).on('click', '.button-delete-param', function(){ 
        $(this).closest('.dynamic-param-row').remove(); 
    });

    // Optionally, you can initialize with existing params if needed
    // Example: window.initialDynamicParams = [{name:'id',source:'url',default:'1'}];
    if (window.initialDynamicParams && Array.isArray(window.initialDynamicParams)) {
        window.initialDynamicParams.forEach(function(param) {
            addParamRow(param.name, param.source, param.default);
        });
    }

    // --- Función para mostrar mensajes visuales debajo de los botones ---
    function showSourceMessage(msg, type) {
        var $msg = $('#source-form-message');
        $msg.removeClass('success error').addClass(type).html(msg).show().delay(3000).fadeOut();
    }

    // --- Renderizado avanzado de tags dinámicos (idéntico a Endpoints) ---
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
            // Eliminado el return para que siempre se muestren los datos y campos
            $('#source-test-result').html(html);
        }
        html += '<div style="margin-bottom:18px;"><strong>🏷️ Tags dinámicos generados:</strong><form id="tags-enable-form"><div style="display:grid;gap:8px;margin-top:10px;">';
        tags.forEach(function(tag, idx){
            // Buscar el objeto del tag en tagObjs
            let tagObj = tagObjs.find(t => t.tag === tag) || {};
            let example = tagObj.example || '';
            let checked = disabledTags.includes(tag) ? '' : 'checked';
            let safeId = 'tag-enable-' + idx;
            // Debug para ver el tag y el ejemplo
            console.log('TAG DEBUG:', tag, 'EJEMPLO:', example);
            html += '<div style="display:flex;align-items:center;gap:10px;background:#f8f9fa;padding:8px 12px;border-radius:5px;">';
            html += '<input type="checkbox" id="'+safeId+'" class="tag-enable-checkbox" data-tag="'+tag+'" '+checked+' style="margin-right:6px;">';
            html += '<label for="'+safeId+'" style="margin:0;cursor:pointer;"><code style="font-size:14px;color:#e67e22;font-weight:bold;">'+tag+'</code></label>';
            html += '<button type="button" class="button button-small copy-tag-btn" data-copy="'+tag+'" style="margin-left:10px;">Copiar tag</button>';
            html += '<span style="color:#888;">→</span>';
            // Mostrar el valor de ejemplo: textarea para arrays/objetos, input para escalares
            if (typeof example === 'string' && (example.trim().startsWith('[') || example.trim().startsWith('{'))) {
                html += '<textarea readonly style="font-family:monospace;background:#fff;padding:2px 6px;border-radius:3px;border:1px solid #e3e3e3;min-width:80px;max-width:600px;min-height:40px;max-height:120px;resize:vertical;">'+example+'</textarea>';
            } else {
                html += '<input type="text" readonly value="'+example+'" style="font-family:monospace;background:#fff;padding:2px 6px;border-radius:3px;border:1px solid #e3e3e3;min-width:80px;max-width:600px;">';
            }
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

    // --- Mostrar tags dinámicos y ejemplo de campos SIEMPRE que se cargue un Source ---
    function showTagsAndExamples(res) {
        // Obtener ejemplo del primer registro si está disponible
        let example = res.data && res.data.example ? res.data.example : null;
        let exampleJson = '';
        if (example) {
            try {
                exampleJson = JSON.stringify(example, null, 2);
            } catch (e) { exampleJson = ''; }
        }
        renderTagsTableContent(res, example, exampleJson);
    }

    // --- Hook para mostrar los tags dinámicos al cargar o refrescar un Source ---
    window.showTagsAndExamples = showTagsAndExamples;

    // --- Botón eliminar tags y query type ---
    $('#delete-source-tags-btn').off('click').on('click', function(){
        if(!confirm('¿Seguro que quieres eliminar los tags y el query type?')) return;
        var sourceId = $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_');
        $.post(ajaxurl, {action:'delete_source_and_tags', source_id:sourceId, nonce: window.bricksApiSourceNonce}, function(res){
            if(res.success){
                // Limpiar todos los posibles contenedores y botones relacionados
                $('#source-test-result, .source-test-result').empty();
                $('#view-source-tags-btn, .button-view-tags').hide();
                $('#delete-source-tags-btn, .button-delete-tags').hide();
                showSourceMessage('✅ Tags y query type eliminados.', 'success');
            }else{
                showSourceMessage('❌ ' + res.data, 'error');
            }
        });
    });

    // --- Obtener datos actuales del formulario de Source ---
    function getSourceFormData() {
        return {
            source_id: $('input[name="source_id"]').val() || $('#source_name').val().toLowerCase().replace(/\s+/g,'_'),
            endpoint_id: $('#endpoint_id').val(),
            items_path: $('#items_path').val(),
            field_prefix: $('#field_prefix').val(),
            pagination_type: $('#pagination_type').val(),
            pagination_param: $('#pagination_param').val(),
            per_page_param: $('#per_page_param').val(),
            param_names: $("input[name='param_names[]']").map(function(){return $(this).val();}).get(),
            param_sources: $("select[name='param_sources[]']").map(function(){return $(this).val();}).get(),
            param_defaults: $("input[name='param_defaults[]']").map(function(){return $(this).val();}).get(),
            nonce: window.bricksApiSourceNonce
        };
    }

    // --- Renderizado de respuesta simple como en Endpoints ---
    function renderApiTestResult(res) {
        let html = '';
        // Respuesta cruda de la API
        if(res.data && res.data.preview){
            html += '<div style="background:#f8f9fa;padding:12px;border-radius:6px;margin-top:10px;">';
            html += '<b>Respuesta de la API:</b><br><pre style="max-height:300px;overflow:auto;">'+res.data.preview+'</pre>';
            if(res.data.fields && res.data.fields.length){
                html += '<b>Campos detectados:</b> '+res.data.fields.join(', ');
            }
            html += '</div>';
        } else {
            html += '<div style="color:#c00">No se recibió respuesta de la API.</div>';
        }
        $('#source-test-result').html(html);
    }

    // --- Botones Test Source y Actualizar datos ---
    $('#test-source-btn').off('click').on('click', function(){
        const data = getSourceFormData();
        if(!data.endpoint_id){
            $('#source-test-result').html('<span style="color:#c00">Selecciona un endpoint antes de testear.</span>');
            return;
        }
        $('#source-test-result').html('<em>Consultando API...</em>');
        $.post(ajaxurl, {
            action: 'test_source_api_live',
            ...data
        }, function(res){
            if(res.success){
                renderApiTestResult(res);
            }else{
                $('#source-test-result').html('<span style="color:#c00">'+res.data+'</span>');
            }
        });
    });

    // --- Botón Crear Tags y Query Types Dinámicos ---
    $('#generate-source-tags-btn').off('click').on('click', function(){
        const data = getSourceFormData();
        $('#source-test-result').html('<em>Generando tags dinámicos...</em>');
        $.post(ajaxurl, {
            action: 'generate_source_tags',
            nonce: window.bricksApiSourceNonce,
            ...data
        }, function(res){
            if(res.success){
                // Mostrar los tags y ejemplos generados
                showTagsAndExamples(res);
            } else {
                $('#source-test-result').html('<span style="color:#c00">'+(res.data || 'Error al generar tags dinámicos')+'</span>');
            }
        });
    });

    // Eliminar el botón de Actualizar datos de la UI
    $('.source-actions #refresh-source-btn').remove();
    // Eliminar el handler de click para refresh-source-btn
    $('#refresh-source-btn').off('click');
}); 