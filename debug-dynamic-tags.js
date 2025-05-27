/**
 * Script de debug para Dynamic Tags - Bricks API Integrator
 * Usar en la consola del navegador para diagnosticar problemas
 */

function debugDynamicTags(endpointIndex = 1) {
    console.log('=== DEBUG DYNAMIC TAGS SYSTEM ===');
    console.log('Endpoint Index:', endpointIndex);
    
    // 1. Verificar jQuery y elementos DOM
    console.log('1. Verificando jQuery y DOM...');
    const $ = jQuery;
    if (!$) {
        console.error('❌ jQuery no disponible');
        return;
    }
    console.log('✅ jQuery disponible');
    
    // 2. Verificar elementos DOM específicos
    const $accordion = $('#dynamic-tags-' + endpointIndex);
    const $loading = $accordion.find('.tags-loading');
    const $tagsList = $accordion.find('.tags-list');
    const $card = $('.endpoint-card').eq(endpointIndex);
    
    console.log('2. Elementos DOM encontrados:');
    console.log('- Accordion:', $accordion.length, $accordion.is(':visible') ? '(visible)' : '(hidden)');
    console.log('- Loading:', $loading.length, $loading.is(':visible') ? '(visible)' : '(hidden)');
    console.log('- Tags List:', $tagsList.length, $tagsList.is(':visible') ? '(visible)' : '(hidden)');
    console.log('- Card:', $card.length);
    
    if ($accordion.length === 0) {
        console.error('❌ Accordion no encontrado. Verifica que el endpoint existe.');
        return;
    }
    
    // 3. Verificar datos del endpoint
    console.log('3. Datos del endpoint:');
    const name = $card.find('input[name*="[name]"]').val();
    const url = $card.find('input[name*="[url]"]').val();
    console.log('- Nombre:', name || 'NO DEFINIDO');
    console.log('- URL:', url || 'NO DEFINIDO');
    
    if (!name || !url) {
        console.error('❌ Datos del endpoint incompletos');
        return;
    }
    
    // 4. Verificar nonce
    console.log('4. Verificando nonce...');
    console.log('- window.currentNonce:', window.currentNonce || 'NO DEFINIDO');
    console.log('- ajaxurl:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'NO DISPONIBLE');
    
    // 5. Mostrar accordion si está oculto
    if (!$accordion.is(':visible')) {
        console.log('5. Mostrando accordion...');
        $accordion.show();
    }
    
    // 6. Test AJAX real
    console.log('6. Ejecutando test AJAX real...');
    $loading.show().html('<span style="color: #666;">🔍 Test de debug en progreso...</span>');
    $tagsList.hide();
    
    const ajaxData = {
        action: 'get_dynamic_tags_for_endpoint',
        index: endpointIndex,
        nonce: window.currentNonce
    };
    
    console.log('Datos AJAX:', ajaxData);
    
    $.post(ajaxurl, ajaxData)
        .done(function(response) {
            console.log('✅ AJAX Success Response:', response);
            
            if (response.success) {
                console.log('✅ Server responded successfully');
                console.log('- Tags count:', response.data.tags ? response.data.tags.length : 0);
                console.log('- Endpoint name:', response.data.endpoint_name);
                console.log('- Sample tags:', response.data.tags ? response.data.tags.slice(0, 3) : []);
                
                // Mostrar tags manualmente
                if (response.data.tags && response.data.tags.length > 0) {
                    let html = '<div style="padding: 15px; background: #f0f8ff;">';
                    html += '<h4 style="color: #007cba;">🎉 DEBUG: Tags generados correctamente (' + response.data.tags.length + ')</h4>';
                    
                    response.data.tags.slice(0, 5).forEach(function(tag) {
                        html += '<div style="margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #007cba;">';
                        html += '<code style="color: #d63384;">' + tag + '</code>';
                        html += '</div>';
                    });
                    
                    if (response.data.tags.length > 5) {
                        html += '<p><em>... y ' + (response.data.tags.length - 5) + ' tags más</em></p>';
                    }
                    
                    html += '<p style="color: #28a745;"><strong>✅ Sistema funcionando correctamente</strong></p>';
                    html += '</div>';
                    
                    $loading.hide();
                    $tagsList.html(html).show();
                    
                    console.log('✅ Tags mostrados en interfaz');
                } else {
                    console.warn('⚠️ Response exitosa pero sin tags');
                    $loading.html('<span style="color: orange;">⚠️ Response exitosa pero sin tags generados</span>');
                }
            } else {
                console.error('❌ Server error:', response.data);
                $loading.html('<span style="color: red;">❌ Error del servidor: ' + (response.data ? response.data.message : 'desconocido') + '</span>');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('❌ AJAX Failed:', {
                status: status,
                error: error,
                statusCode: xhr.status,
                responseText: xhr.responseText.substring(0, 500)
            });
            $loading.html('<span style="color: red;">❌ Error de conexión: ' + status + '</span>');
        });
    
    console.log('=== FIN DEBUG ===');
}

// Función auxiliar para mostrar información general
function debugSystemInfo() {
    console.log('=== SYSTEM INFO ===');
    console.log('- jQuery version:', jQuery ? jQuery.fn.jquery : 'No disponible');
    console.log('- Current URL:', window.location.href);
    console.log('- ajaxurl:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'No disponible');
    console.log('- window.currentNonce:', window.currentNonce || 'No definido');
    
    // Contar endpoints en la página
    const endpointCount = jQuery('.endpoint-card').length;
    console.log('- Endpoints en página:', endpointCount);
    
    // Contar acordeones de dynamic tags
    const accordionCount = jQuery('[id^="dynamic-tags-"]').length;
    console.log('- Acordeones de tags:', accordionCount);
    
    console.log('=== END SYSTEM INFO ===');
}

// Exportar funciones globalmente para uso en consola
window.debugDynamicTags = debugDynamicTags;
window.debugSystemInfo = debugSystemInfo;

console.log('🔧 Debug script cargado. Usa debugDynamicTags(1) o debugSystemInfo() en la consola.');
