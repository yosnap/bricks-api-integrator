// Función para comparar tags generados vs esperados
function compararTags(tagsGenerados) {
    const tagsEsperados = [
        "idclinica", "nombrecentro", "direccion", "cp", "provincia", "poblacion",
        "telefono", "telefonopublico", "tipourgencias", "horario", "horario_idhorario",
        "horario_idclinica", "horario_lunesmanana", "horario_lunestarde",
        "horario_martesmanana", "horario_martestarde", "horario_miercolesmanana",
        "horario_miercolestarde", "horario_juevesmanana", "horario_juevestarde",
        "horario_viernesmanana", "horario_viernestarde", "horario_sabadomañana",
        "horario_sabadotarde", "horario_domingomanana", "horario_domingotarde",
        "especialidades", "especialidades_count", "especialidades_first",
        "especialidades_first_idclinica", "especialidades_first_id",
        "especialidades_first_categoria", "especialidades_first_nombre",
        "especialidades_first_propia", "especialidades_first_ajena"
    ];

    // Extraer nombres de campos de los tags generados (normalizados)
    const camposGenerados = tagsGenerados.map(tag => {
        const match = tag.match(/\{snap_[^_]+_(.+)\}/);
        return match ? match[1].toLowerCase() : "";
    }).filter(campo => campo);

    console.log("📊 COMPARACIÓN DE TAGS:");
    console.log(`Tags esperados: ${tagsEsperados.length}`);
    console.log(`Tags generados: ${camposGenerados.length}`);
    
    console.log("\n📋 Tags generados:");
    camposGenerados.forEach((tag, index) => {
        console.log(`${index + 1}. ${tag}`);
    });

    // Encontrar tags faltantes
    const faltantes = tagsEsperados.filter(tag => !camposGenerados.includes(tag.toLowerCase()));
    if (faltantes.length > 0) {
        console.log("\n❌ Tags faltantes:", faltantes);
    } else {
        console.log("\n✅ Todos los tags esperados están generados");
    }

    // Encontrar tags extras
    const extras = camposGenerados.filter(tag => !tagsEsperados.includes(tag.toLowerCase()));
    if (extras.length > 0) {
        console.log("\n➕ Tags adicionales (no esperados):", extras);
    }

    return { esperados: tagsEsperados.length, generados: camposGenerados.length, faltantes, extras };
}

// Función rápida para probar con los tags actuales
function verificarTagsActuales() {
    console.log('🔍 Ejecutando test completo de tags...');
    
    $.post(ajaxurl, {
        action: 'get_dynamic_tags_for_endpoint',
        index: 1,
        nonce: window.currentNonce
    }, function(response) {
        if (response.success) {
            console.log('📥 Respuesta recibida:', response.data.tags.length, 'tags');
            compararTags(response.data.tags);
        } else {
            console.error('❌ Error:', response.data);
        }
    });
}

// Función de análisis avanzado
function analizarEstructuraCompleta() {
    console.log('🔬 ANÁLISIS AVANZADO DE ESTRUCTURA:');
    
    $.post(ajaxurl, {
        action: 'get_dynamic_tags_for_endpoint',
        index: 1,
        nonce: window.currentNonce
    }, function(response) {
        if (response.success) {
            console.log('📊 Datos del endpoint:');
            console.log('- Tags generados:', response.data.tags.length);
            console.log('- Endpoint:', response.data.endpoint_name);
            console.log('- Tipo de datos:', response.data.data_type);
            console.log('- Cantidad de elementos:', response.data.data_count);
            
            if (response.data.sample_data) {
                console.log('\n🔍 Estructura de datos de ejemplo:');
                console.log('- Campos principales:', Object.keys(response.data.sample_data).length);
                
                if (response.data.sample_data.horario) {
                    console.log('- Campos de horario:', Object.keys(response.data.sample_data.horario).length);
                }
                
                if (response.data.sample_data.especialidades && Array.isArray(response.data.sample_data.especialidades)) {
                    console.log('- Especialidades (array):', response.data.sample_data.especialidades.length, 'elementos');
                    if (response.data.sample_data.especialidades[0]) {
                        console.log('- Campos por especialidad:', Object.keys(response.data.sample_data.especialidades[0]).length);
                    }
                }
            }
            
            compararTags(response.data.tags);
        }
    });
}

window.compararTags = compararTags;
window.verificarTagsActuales = verificarTagsActuales;
window.analizarEstructuraCompleta = analizarEstructuraCompleta;

console.log('🔍 Funciones de análisis cargadas:');
console.log('- verificarTagsActuales() - Test completo automático');
console.log('- analizarEstructuraCompleta() - Análisis detallado');
console.log('- compararTags(arrayDeTags) - Comparación manual');
