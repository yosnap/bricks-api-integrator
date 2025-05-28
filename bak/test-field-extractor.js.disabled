// Función de test rápido para validar las mejoras del field extractor
window.testFieldExtractor = function() {
    console.log('🧪 Testing field extractor improvements...');
    
    // Simular una petición AJAX para regenerar los tags con las mejoras
    $.post(ajaxurl, {
        action: 'get_dynamic_tags_for_endpoint',
        index: 1,
        nonce: window.currentNonce
    }, function(response) {
        if (response.success) {
            console.log('✅ Tags generados después de la mejora:', response.data.tags.length);
            console.log('📋 Lista de tags:');
            response.data.tags.forEach((tag, index) => {
                console.log(`${index + 1}. ${tag}`);
            });
            
            // Comparar con el análisis esperado
            if (window.compareWithCurrentTags) {
                window.compareWithCurrentTags(response.data.tags);
            }
        } else {
            console.error('❌ Error al generar tags:', response.data);
        }
    });
};

console.log('🧪 Test helper cargado. Usa testFieldExtractor() para probar las mejoras.');
