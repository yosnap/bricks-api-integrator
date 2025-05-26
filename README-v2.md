# Bricks API Integrator v2.0 - Integración Dinámica

## ✨ Nuevas Funcionalidades

### 🔄 Sistema Dinámico
- **Query Types Automáticos**: Los endpoints configurados aparecen automáticamente como tipos de consulta en Bricks Builder
- **Dynamic Tags Generados**: Se crean tags dinámicamente basándose en la estructura real de datos de la API
- **Cache Inteligente**: Sistema de cache optimizado con invalidación automática
- **Integración Nativa**: Funciona directamente con Query Loop sin configuración adicional

### 📋 Cómo Funciona

#### 1. Configuración de Endpoints
Los endpoints se configuran igual que antes, pero ahora:
- Se registran automáticamente como Query Types en Bricks
- Se analizan para extraer campos dinámicamente
- Se crean Dynamic Tags basándose en la respuesta real de la API

#### 2. Query Types Dinámicos
```php
// Automáticamente se registran query types como:
'api_mi_endpoint' => 'Mi Endpoint'
'source_mi_source' => 'Mi Source'
```

#### 3. Dynamic Tags Automáticos
```php
// Se generan tags como:
'{api_mi_endpoint_nombre}'
'{api_mi_endpoint_descripcion}'
'{source_mi_source_titulo}'
```

### 🎯 Beneficios

1. **Configuración Mínima**: Solo configuras el endpoint, todo lo demás es automático
2. **Flexibilidad**: Se adapta a cualquier estructura de API
3. **Performance**: Cache inteligente mejora la velocidad
4. **Compatibilidad**: Mantiene toda la funcionalidad anterior

### 🔧 Uso en Bricks Builder

1. **Query Loop**: Los nuevos Query Types aparecen automáticamente
2. **Dynamic Data**: Los tags se generan automáticamente
3. **Sin Código**: No necesitas programar nada adicional

### 📁 Estructura de Archivos

- `bricks-api-integrator.php` - Clase principal con lógica dinámica
- `includes/api-manager.php` - Gestión de APIs y cache
- `includes/field-extractor.php` - Extracción dinámica de campos
- `includes/functions.php` - Funciones de compatibilidad
- `dynamic-tags.php` - Compatibilidad con versión anterior

### 🚀 Proceso de Integración

1. **Detección**: El plugin detecta endpoints configurados
2. **Análisis**: Obtiene datos de muestra de cada API
3. **Extracción**: Extrae campos dinámicamente de la respuesta
4. **Registro**: Registra Query Types y Dynamic Tags automáticamente
5. **Renderizado**: Renderiza los datos en tiempo real

## 🎉 Resultado Final

Ahora tienes un sistema completamente dinámico donde:
- Los endpoints se convierten automáticamente en Query Types
- Los campos de la API se convierten automáticamente en Dynamic Tags
- Todo funciona nativamente con Bricks Builder
- Mantiene compatibilidad con la versión anterior
