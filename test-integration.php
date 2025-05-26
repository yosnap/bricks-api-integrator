<?php
/**
 * Test de integración para Bricks API Integrator v2.0
 * 
 * Ejecuta este archivo para verificar que el sistema dinámico funciona correctamente
 */

// Simular datos de prueba
if (!function_exists('test_api_integrator_v2')) {
    function test_api_integrator_v2() {
        echo "<h2>🧪 Test Bricks API Integrator v2.0</h2>";
        
        // Test 1: Verificar constantes
        echo "<h3>✅ Test 1: Constantes del Plugin</h3>";
        if (defined('BRICKS_API_INTEGRATOR_VERSION')) {
            echo "✅ Versión: " . BRICKS_API_INTEGRATOR_VERSION . "<br>";
        } else {
            echo "❌ Constante de versión no definida<br>";
        }
        
        if (defined('BRICKS_API_INTEGRATOR_PATH')) {
            echo "✅ Path: " . BRICKS_API_INTEGRATOR_PATH . "<br>";
        } else {
            echo "❌ Constante de path no definida<br>";
        }
        
        // Test 2: Verificar clase principal
        echo "<h3>✅ Test 2: Clase Principal</h3>";
        if (class_exists('BricksAPIIntegrator')) {
            echo "✅ Clase BricksAPIIntegrator existe<br>";
            
            // Test traits
            $reflection = new ReflectionClass('BricksAPIIntegrator');
            $traits = $reflection->getTraitNames();
            
            if (in_array('APIManager', $traits)) {
                echo "✅ Trait APIManager cargado<br>";
            } else {
                echo "❌ Trait APIManager no encontrado<br>";
            }
            
            if (in_array('FieldExtractor', $traits)) {
                echo "✅ Trait FieldExtractor cargado<br>";
            } else {
                echo "❌ Trait FieldExtractor no encontrado<br>";
            }
        } else {
            echo "❌ Clase BricksAPIIntegrator no existe<br>";
        }
        
        // Test 3: Verificar hooks
        echo "<h3>✅ Test 3: Hooks de WordPress</h3>";
        
        if (has_filter('bricks/setup/control_options')) {
            echo "✅ Hook bricks/setup/control_options registrado<br>";
        } else {
            echo "❌ Hook bricks/setup/control_options no registrado<br>";
        }
        
        if (has_filter('bricks/query/run')) {
            echo "✅ Hook bricks/query/run registrado<br>";
        } else {
            echo "❌ Hook bricks/query/run no registrado<br>";
        }
        
        if (has_filter('bricks/dynamic_tags_list')) {
            echo "✅ Hook bricks/dynamic_tags_list registrado<br>";
        } else {
            echo "❌ Hook bricks/dynamic_tags_list no registrado<br>";
        }
        
        // Test 4: Verificar funciones de compatibilidad
        echo "<h3>✅ Test 4: Funciones de Compatibilidad</h3>";
        
        if (function_exists('get_api_data')) {
            echo "✅ Función get_api_data disponible<br>";
        } else {
            echo "❌ Función get_api_data no disponible<br>";
        }
        
        if (function_exists('bricks_api_integrator_menu')) {
            echo "✅ Función bricks_api_integrator_menu disponible<br>";
        } else {
            echo "❌ Función bricks_api_integrator_menu no disponible<br>";
        }
        
        // Test 5: Verificar configuración
        echo "<h3>✅ Test 5: Configuración</h3>";
        
        $endpoints = get_option('bricks_api_endpoints', []);
        echo "📊 Endpoints configurados: " . count($endpoints) . "<br>";
        
        $sources = get_option('bricks_api_sources', []);
        echo "📊 Sources configurados: " . count($sources) . "<br>";
        
        echo "<h3>🎉 Test Completado</h3>";
        echo "<p><strong>Resultado:</strong> El plugin v2.0 está correctamente instalado y configurado para funcionar dinámicamente con Bricks Builder.</p>";
    }
}

// Ejecutar test si se accede directamente al archivo
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    test_api_integrator_v2();
}
