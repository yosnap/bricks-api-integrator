# 🔌 Bricks API Integrator v2.1.4

Plugin profesional que integra **APIs externas** con **Bricks Builder** de forma dinámica, generando automáticamente **Query Types** y **Dynamic Tags** para uso nativo en Bricks.

## ✅ **Última Actualización v2.1.4**

### 🎯 **Corrección Crítica: Formularios Duplicados**
- ❌ **Problema resuelto**: Eliminada duplicación de formularios al añadir endpoints
- ✅ **Interfaz unificada**: Acordeones únicos con funcionalidad completa
- ✅ **UX mejorada**: Sin confusión, formulario único por endpoint

## 🚀 **Características Principales**

### 🤖 **Sistema Diferenciado AUTO/MANUAL**
- **Query Types Automáticos**: Se generan desde endpoints → `{snap_auto_endpoint_campo}`
- **Query Types Manuales**: Para datos anidados con `items_path` → `{snap_source_campo}`

### 🏷️ **Dynamic Tags Avanzados**
```php
// Tags básicos
{snap_producto_nombre}
{snap_producto_precio}

// Tags para arrays
{snap_especialidades_count}        # Cantidad
{snap_especialidades_first_nombre} # Primer elemento
{snap_especialidades_item_nombre}  # Por índice (bucles)
{snap_especialidades_last_nombre}  # Último elemento
```

### ⚡ **Funcionalidades Técnicas**
- ✅ **Caché inteligente** multi-nivel (estático + transient)
- ✅ **Autenticación robusta** (Bearer Token, Basic Auth, API Key)
- ✅ **URLs dinámicas** con parámetros de WordPress
- ✅ **Field extraction** automático desde estructuras JSON complejas

## 📋 **Casos de Uso Soportados**

### **1. APIs Simples**
```json
[{"id": 1, "nombre": "Producto A", "precio": 100}]
```
**Solución**: Query Type Automático + Tags básicos

### **2. APIs Complejas con Arrays Anidados**
```json
{
  "data": {
    "productos": [
      {
        "nombre": "Producto A",
        "especialidades": [
          {"nombre": "Esp 1", "descripcion": "Desc 1"}
        ]
      }
    ]
  }
}
```
**Solución**: Endpoint + Query Type Manual con `items_path: "data.productos"`

## 🛠️ **Instalación y Configuración**

### **Pasos Básicos**
1. **Instalar** el plugin en WordPress
2. **Ir** a `API Integrator > API Endpoints`
3. **Añadir** tu primer endpoint
4. **Los Query Types y Dynamic Tags se generan automáticamente**

### **Para APIs Simples**
1. Clic en "➕ Añadir Endpoint"
2. Configurar nombre y URL
3. ¡Listo! Ya tienes Query Type automático disponible

### **Para APIs Complejas**
1. Crear el Endpoint base
2. Ir a "Query Types" 
3. Crear Query Type Manual con `items_path`
4. Ejemplo: `data.productos` para acceder a arrays anidados

## 🎨 **Integración con Bricks Builder**

### **Query Loop**
1. **Element**: Query Loop
2. **Query**: Seleccionar tu Query Type generado
   - `Nombre del Endpoint (Auto)` - Para endpoints simples
   - `Nombre del Source (Manual)` - Para datos anidados

### **Dynamic Tags**
```php
// En cualquier elemento de Bricks
{snap_auto_productos_nombre}    # Desde endpoint automático
{snap_productos_descripcion}    # Desde source manual
{snap_especialidades_count}     # Cantidad de especialidades
```

## 🔧 **Configuración Avanzada**

### **Autenticación**
- **Bearer Token**: `Authorization: Bearer {token}`
- **Basic Auth**: `Authorization: Basic {base64(user:pass)}`
- **API Key**: Custom header con tu clave

### **Parámetros Dinámicos**
- **URL Parameters**: `?id=123` desde la URL actual
- **Post ID**: Usar ID del post actual de WordPress
- **Meta Fields**: Campos personalizados del post

### **URLs Dinámicas**
```php
// Ejemplos de URLs dinámicas
https://api.com/posts/{post_id}/comentarios
https://api.com/users/{user_id}/favoritos
```

## 📊 **Arquitectura del Sistema**

```
API Externa → Plugin → Bricks Builder
     ↓           ↓            ↓
  JSON Data → Processing → Query Types + Dynamic Tags
```

### **Componentes Principales**
- **API Manager**: Gestión de peticiones y caché
- **Field Extractor**: Extracción automática de campos desde JSON
- **Query Types Engine**: Generación automática/manual de query types
- **Dynamic Tags System**: Tags nativos para Bricks

## 🐛 **Troubleshooting**

### **Problemas Comunes**
1. **No aparecen Query Types**: Verificar que el endpoint devuelve datos válidos
2. **Dynamic Tags vacíos**: Revisar que los nombres de campos coincidan
3. **Error de autenticación**: Verificar tokens y configuración

### **Debug Mode**
Activar `WP_DEBUG = true` en `wp-config.php` para logs detallados.

## 📚 **Documentación Técnica**

### **Archivos Clave**
- `bricks-api-integrator.php` - Plugin principal
- `includes/endpoints-page.php` - Interfaz de endpoints
- `includes/api-manager.php` - Gestión de APIs
- `includes/field-extractor.php` - Extracción de campos

### **Hooks y Filtros**
```php
// Hooks principales
add_filter('bricks/setup/control_options', [$this, 'add_query_types_dynamic']);
add_filter('bricks/query/run', [$this, 'run_custom_query_dynamic'], 10, 2);
add_filter('bricks/dynamic_tags_list', [$this, 'add_dynamic_tags_dynamic']);
```

## 🚀 **Roadmap**

### **v2.2 (Próxima versión)**
- [ ] Templates predefinidos para APIs comunes
- [ ] Interfaz visual para field mapping
- [ ] Caché avanzado con invalidación inteligente
- [ ] Soporte para GraphQL APIs

## 📝 **Changelog Reciente**

### **v2.1.4 - Corrección Crítica**
- ✅ Eliminada duplicación de formularios en endpoints
- ✅ Interfaz unificada con acordeones únicos
- ✅ Event listeners optimizados sin conflictos

### **v2.1.3 - Consola Limpia**
- ✅ Eliminados console.log para producción
- ✅ Scripts de debug organizados en carpeta bak/

### **v2.1.2 - Dynamic Tags Restaurados**
- ✅ Funcionalidad completa de dynamic tags
- ✅ Sistema de caché inteligente implementado

## 🤝 **Soporte**

- **Documentación**: Ver archivos `.md` en el directorio del plugin
- **Issues**: Reportar en el repositorio del proyecto
- **Email**: Para soporte profesional

## 📄 **Licencia**

GPL v2 - Código abierto y libre para uso comercial y personal.

---

**Desarrollado por sn4p Dev** | Plugin profesional para integración de APIs con Bricks Builder
