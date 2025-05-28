/**
 * Script de análisis del JSON para contar campos esperados
 * Usar en la consola para analizar la estructura completa
 */

function analyzeJsonStructure(jsonData) {
    console.log('=== ANÁLISIS ESTRUCTURA JSON ===');
    
    if (Array.isArray(jsonData) && jsonData.length > 0) {
        jsonData = jsonData[0]; // Tomar el primer elemento si es array
    }
    
    function countFields(obj, prefix = '', depth = 0, maxDepth = 3) {
        let fieldCount = 0;
        let fields = [];
        
        if (depth > maxDepth || !obj || typeof obj !== 'object') {
            return { count: fieldCount, fields: fields };
        }
        
        for (const [key, value] of Object.entries(obj)) {
            const fieldKey = prefix ? `${prefix}_${key}` : key;
            
            if (Array.isArray(value)) {
                if (value.length > 0) {
                    // Array con elementos
                    fieldCount += 1; // Campo base del array
                    fields.push(`${fieldKey} (Lista)`);
                    
                    fieldCount += 2; // _count y _first
                    fields.push(`${fieldKey}_count (Cantidad)`);
                    fields.push(`${fieldKey}_first (Primero)`);
                    
                    // Si es array de objetos, contar campos del primer objeto
                    if (typeof value[0] === 'object' && !Array.isArray(value[0])) {
                        const subResult = countFields(value[0], `${fieldKey}_first`, depth + 1, maxDepth);
                        fieldCount += subResult.count;
                        fields = fields.concat(subResult.fields);
                    }
                } else {
                    fieldCount += 1;
                    fields.push(`${fieldKey} (Array vacío)`);
                }
            } else if (typeof value === 'object' && value !== null) {
                // Objeto anidado
                fieldCount += 1; // Campo base del objeto
                fields.push(`${fieldKey} (Objeto)`);
                
                const subResult = countFields(value, fieldKey, depth + 1, maxDepth);
                fieldCount += subResult.count;
                fields = fields.concat(subResult.fields);
            } else {
                // Campo simple
                fieldCount += 1;
                fields.push(`${fieldKey} (${typeof value})`);
            }
        }
        
        return { count: fieldCount, fields: fields };
    }
    
    const result = countFields(jsonData);
    
    console.log('📊 Campos esperados:', result.count);
    console.log('📋 Lista de campos:');
    result.fields.forEach((field, index) => {
        console.log(`${index + 1}. ${field}`);
    });
    
    // Análisis por secciones
    console.log('\n🔍 Análisis por secciones:');
    
    // Campos principales
    const mainFields = Object.keys(jsonData).filter(key => 
        !Array.isArray(jsonData[key]) && typeof jsonData[key] !== 'object'
    );
    console.log(`- Campos principales: ${mainFields.length} (${mainFields.join(', ')})`);
    
    // Objetos anidados
    const nestedObjects = Object.keys(jsonData).filter(key => 
        typeof jsonData[key] === 'object' && !Array.isArray(jsonData[key]) && jsonData[key] !== null
    );
    console.log(`- Objetos anidados: ${nestedObjects.length} (${nestedObjects.join(', ')})`);
    
    // Arrays
    const arrays = Object.keys(jsonData).filter(key => Array.isArray(jsonData[key]));
    console.log(`- Arrays: ${arrays.length} (${arrays.join(', ')})`);
    
    // Detalles de arrays
    arrays.forEach(arrayKey => {
        const arrayData = jsonData[arrayKey];
        console.log(`  - ${arrayKey}: ${arrayData.length} elementos`);
        if (arrayData.length > 0 && typeof arrayData[0] === 'object') {
            const firstObjKeys = Object.keys(arrayData[0]);
            console.log(`    Campos por objeto: ${firstObjKeys.length} (${firstObjKeys.slice(0, 5).join(', ')}${firstObjKeys.length > 5 ? '...' : ''})`);
        }
    });
    
    return result;
}

// Análisis específico para el JSON de clínicas
function analyzeClinicasJson() {
    const sampleData = [
        {
            "idClinica": 12,
            "nombreCentro": "CLINICA VETERINARIA PREVET",
            "direccion": "C/ del general Amocha",
            "cp": "28910",
            "provincia": "MADRID",
            "poblacion": "Leganes",
            "telefono": "659659659",
            "telefonoPublico": "917065898",
            "tipoUrgencias": "Horario comercial - baremo",
            "horario": {
                "idHorario": 7,
                "idClinica": 12,
                "lunesManana": "08:00/14:30",
                "lunesTarde": "17:00/20:00",
                "martesManana": "08:00/14:30",
                "martesTarde": "17:00/20:00"
            },
            "especialidades": [
                {
                    "idClinica": 12,
                    "id": 1,
                    "categoria": "Consultas",
                    "nombre": "Consulta",
                    "propia": true,
                    "ajena": false
                }
            ]
        }
    ];
    
    return analyzeJsonStructure(sampleData);
}

// Función para comparar con los tags actuales generados
function compareWithCurrentTags(currentTags) {
    console.log('\n🔄 COMPARACIÓN CON TAGS ACTUALES');
    console.log(`Tags generados actualmente: ${currentTags.length}`);
    
    const expectedResult = analyzeClinicasJson();
    console.log(`Tags esperados: ${expectedResult.count}`);
    console.log(`Diferencia: ${expectedResult.count - currentTags.length} tags faltantes`);
    
    if (expectedResult.count > currentTags.length) {
        console.log('\n❌ Faltan tags. Posibles causas:');
        console.log('1. Límites en el field extractor');
        console.log('2. Max depth insuficiente');
        console.log('3. Filtros que eliminan campos');
    }
}

// Exportar funciones
window.analyzeJsonStructure = analyzeJsonStructure;
window.analyzeClinicasJson = analyzeClinicasJson;
window.compareWithCurrentTags = compareWithCurrentTags;

console.log('📈 Análisis JSON cargado. Usa analyzeClinicasJson() para analizar la estructura.');
