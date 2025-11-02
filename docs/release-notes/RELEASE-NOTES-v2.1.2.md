# Release Notes v2.1.2

## 🔧 Corrección Crítica - Dynamic Tags Restaurados

Esta versión restaura completamente la funcionalidad de **Dynamic Tags** que había sido deshabilitada previamente.

### ✅ Funcionalidades Restauradas

#### **Botones de Endpoints Funcionales**
- **"Ver Dynamic Tags"**: Ahora funciona correctamente y muestra todos los campos disponibles
- **"Actualizar Datos"**: Funcionalidad de actualización restaurada
- **Vista previa de datos**: Sistema de previsualización completamente operativo

#### **Backend Robusto**
- **`get_api_data_by_endpoint_name()`**: Función implementada para obtener datos por nombre
- **`get_api_data_with_cache()`**: Sistema de cache inteligente de 5 minutos
- **`fetch_api_data_direct()`**: Peticiones HTTP directas con soporte completo de autenticación

#### **Frontend Mejorado**
- **Selectores JavaScript robustos**: 4 estrategias diferentes para encontrar datos del endpoint
- **Gestión de errores mejorada**: Mejor manejo de casos edge
- **Compatibilidad preservada**: Toda la funcionalidad existente mantenida

### 🚀 Mejoras Técnicas

#### **Sistema de Cache**
```php
// Cache automático de datos de API por 5 minutos
$cache_key = 'bricks_api_cache_' . md5($url . serialize($endpoint_config));
set_transient($cache_key, $fresh_data, 300);
```

#### **Autenticación Completa**
- ☑️ **Bearer Token**: `Authorization: Bearer {token}`
- ☑️ **API Key**: Headers personalizados
- ☑️ **Basic Auth**: Usuario y contraseña

#### **Búsqueda de Datos Multi-Estrategia**
```javascript
// 4 estrategias para encontrar datos del endpoint:
// 1. Por patrón de nombres [name], [url]
// 2. Por placeholder y tipo de input
// 3. Por texto de labels
// 4. Búsqueda global por índice
```

### 🐛 Bugs Corregidos

- ✅ **Error "Datos del endpoint incompletos"**: Selectores JavaScript mejorados
- ✅ **Error "$button is not defined"**: Variable pasada correctamente como parámetro
- ✅ **Funciones faltantes**: `get_api_data_by_endpoint_name()` y `get_api_data_with_cache()` implementadas
- ✅ **Recursión infinita**: Evitada con `fetch_api_data_direct()`

### 📋 Notas de Actualización

Esta actualización es **totalmente compatible** con versiones anteriores. No requiere ningún cambio en la configuración existente.

**Funcionalidades que siguen funcionando:**
- ✅ Test API (botón verde)
- ✅ Configuración de endpoints
- ✅ Query types automáticos
- ✅ Sistema de autenticación
- ✅ Integración con Bricks Builder

### 🔍 Para Desarrolladores

Si necesitas debug adicional, activa `WP_DEBUG` para ver logs detallados:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Los logs aparecerán en `/wp-content/debug.log` con prefijo `AJAX:` para las operaciones de dynamic tags.
