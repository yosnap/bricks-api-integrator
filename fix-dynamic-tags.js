// Fix para Dynamic Tags - JavaScript puro
// Ejecuta esto en la consola del navegador

// Función para mostrar dynamic tags forzadamente
window.showDynamicTagsForce = function(index) {
    console.log('Forzando mostrar dynamic tags para endpoint:', index);
    
    // Hacer AJAX request directo
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    var params = 'action=get_dynamic_tags_for_endpoint&index=' + index + '&nonce=' + '<?php echo wp_create_nonce('get_dynamic_tags'); ?>';
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                console.log('Response recibida:', response);
                
                if (response.success && response.data.tags) {
                    var html = '<div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">';
                    html += '<h4 style="color: #007cba; margin-bottom: 15px;">🏷️ Dynamic Tags (' + response.data.tags.length + ')</h4>';
                    
                    response.data.tags.forEach(function(tag, i) {
                        html += '<div style="background: #fff; margin: 5px 0; padding: 10px; border-radius: 3px; border-left: 3px solid #007cba; font-family: monospace; cursor: pointer; transition: all 0.2s;" onclick="copyTagDirect(\'' + tag + '\', this)">';
                        html += '<code style="color: #d63384; font-weight: bold;">' + tag + '</code>';
                        html += '</div>';
                    });
                    
                    html += '<p style="margin-top: 15px; padding: 10px; background: #d1ecf1; border-radius: 3px; font-size: 13px; color: #0c5460;">💡 <strong>Cómo usar:</strong> Haz clic en cualquier tag para copiarlo al portapapeles.</p>';
                    html += '</div>';
                    
                    // Encontrar el contenedor y mostrar
                    var accordion = document.getElementById('dynamic-tags-' + index);
                    if (accordion) {
                        accordion.style.display = 'block';
                        var tagsList = accordion.querySelector('.tags-list');
                        var loading = accordion.querySelector('.tags-loading');
                        
                        if (loading) loading.style.display = 'none';
                        if (tagsList) {
                            tagsList.innerHTML = html;
                            tagsList.style.display = 'block';
                        }
                    }
                    
                    console.log('✅ Tags mostrados correctamente');
                } else {
                    console.log('❌ Error en la respuesta:', response);
                }
            } catch (e) {
                console.log('❌ Error parseando JSON:', e);
            }
        }
    };
    
    xhr.send(params);
};

// Función para copiar al portapapeles - JavaScript puro
window.copyTagDirect = function(text, element) {
    console.log('Copiando:', text);
    
    // Crear elemento temporal
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "-9999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            // Feedback visual
            element.style.backgroundColor = '#28a745';
            element.style.color = '#fff';
            element.style.transform = 'scale(1.02)';
            
            setTimeout(function() {
                element.style.backgroundColor = '';
                element.style.color = '';
                element.style.transform = '';
            }, 500);
            
            // Mensaje
            var msg = document.createElement('div');
            msg.innerHTML = '📋 Copiado: ' + text;
            msg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
            document.body.appendChild(msg);
            
            setTimeout(function() {
                if (msg.parentNode) {
                    msg.parentNode.removeChild(msg);
                }
            }, 2000);
            
            console.log('✅ Copiado correctamente:', text);
        }
    } catch (err) {
        console.log('❌ Error copiando:', err);
    }
    
    document.body.removeChild(textArea);
};

console.log('🔧 Fix cargado. Usa: showDynamicTagsForce(0) para endpoint 0, showDynamicTagsForce(1) para endpoint 1, etc.');
