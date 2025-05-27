// Test específico para verificar especialidades
function testEspecialidades() {
    console.log('🧪 Testing especialidades extraction...');
    
    $.post(ajaxurl, {
        action: 'get_dynamic_tags_for_endpoint',
        index: 1,
        nonce: window.currentNonce
    }, function(response) {
        if (response.success) {
            console.log('📊 Tags generados:', response.data.tags.length);
            
            // Filtrar tags de especialidades
            const especialidadesTags = response.data.tags.filter(tag => 
                tag.includes('especialidades')
            );
            
            console.log('🏷️ Tags de especialidades encontrados:', especialidadesTags.length);
            console.log('📋 Lista de tags de especialidades:');
            especialidadesTags.forEach((tag, index) => {
                console.log(`${index + 1}. ${tag}`);
            });
            
            // Verificar datos de ejemplo
            if (response.data.sample_data && response.data.sample_data.especialidades) {
                console.log('📄 Especialidades en sample_data:', response.data.sample_data.especialidades.length);
                console.log('🔍 Primera especialidad:', response.data.sample_data.especialidades[0]);
                console.log('🔍 Última especialidad:', response.data.sample_data.especialidades[response.data.sample_data.especialidades.length - 1]);
            }
            
            // Análisis de lo que falta
            const expectedEspecialidadesTags = [
                'especialidades',
                'especialidades_count', 
                'especialidades_join',
                'especialidades_first_idclinica',
                'especialidades_first_id',
                'especialidades_first_categoria',
                'especialidades_first_nombre',
                'especialidades_first_propia',
                'especialidades_first_ajena',
                'especialidades_item_idclinica',
                'especialidades_item_id', 
                'especialidades_item_categoria',
                'especialidades_item_nombre',
                'especialidades_item_propia',
                'especialidades_item_ajena',
                'especialidades_last_idclinica',
                'especialidades_last_id',
                'especialidades_last_categoria', 
                'especialidades_last_nombre',
                'especialidades_last_propia',
                'especialidades_last_ajena'
            ];
            
            const currentTagsNormalized = especialidadesTags.map(tag => {
                const match = tag.match(/\{snap_[^_]+_(.+)\}/);
                return match ? match[1] : '';
            });
            
            const missing = expectedEspecialidadesTags.filter(expected => 
                !currentTagsNormalized.includes(expected)
            );
            
            if (missing.length > 0) {
                console.log('❌ Tags de especialidades faltantes:', missing);
            } else {
                console.log('✅ Todos los tags de especialidades están presentes');
            }
            
        } else {
            console.error('❌ Error:', response.data);
        }
    });
}

window.testEspecialidades = testEspecialidades;
console.log('🧪 Test especialidades cargado. Usa testEspecialidades() para probar.');
