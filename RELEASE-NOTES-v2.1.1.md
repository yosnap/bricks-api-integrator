# 🚀 Release Notes - Bricks API Integrator v2.1.1

## 📅 Fecha de Lanzamiento
**28 de Mayo, 2025**

## ✨ Nuevas Funcionalidades

### 🎯 Sistema Diferenciado AUTO/MANUAL
Hemos restaurado y mejorado el sistema de diferenciación entre Query Types automáticos y manuales, proporcionando mayor flexibilidad y claridad en el uso del plugin.

#### 🤖 Query Types Automáticos
- **Origen**: Se crean automáticamente al configurar un Endpoint
- **Identificación**: Aparecen en Bricks como `Nombre (Auto)`
- **Dynamic Tags**: `{snap_auto_endpoint_campo}`
- **Uso ideal**: APIs simples y directas

#### ⚙️ Query Types Manuales  
- **Origen**: Se crean manualmente en la página Query Types
- **Identificación**: Aparecen en Bricks como `Nombre (Manual)`
- **Dynamic Tags**: `{snap_source_campo}`
- **Uso ideal**: APIs con ítems anidados usando `items_path`

### 🏷️ Sistema de Dynamic Tags Mejorado
- **Tags Automáticos**: `{snap_auto_nombre_campo}` para endpoints directos
- **Tags Manuales**: `{snap_nombre_campo}` para sources con items_path
- **Renderizado Inteligente**: Detección automática del tipo de tag
- **Contexto de Loop**: Funciona perfectamente dentro de Query Loops de Bricks

### 📊 Dashboard Renovado
- **Estadísticas Diferenciadas**: Muestra endpoints vs query types manuales por separado
- **Explicación Visual**: Diferencia clara entre automáticos y manuales
- **Guía Paso a Paso**: Flujo de trabajo recomendado
- **Ejemplo Práctico**: Cómo manejar APIs con arrays anidados

## 🔧 Mejoras Técnicas

### ⚡ Renderizado Optimizado
- Procesamiento inteligente de tags según su tipo (auto/manual)
- Mejor manejo de contextos de Bricks Query Loops
- Soporte mejorado para arrays anidados y estructuras complejas

### 🛠️ Arquitectura Limpia
- Código refactorizado sin errores de sintaxis
- Funciones bien estructuradas y documentadas
- Debug logging mejorado para troubleshooting

## 📋 Cómo Usar las Nuevas Funcionalidades

### Para APIs Simples (Automático)
```
1. Configurar Endpoint en "API Endpoints"
   ↓
2. Se crea automáticamente Query Type "Mi API (Auto)"
   ↓
3. En Bricks: Query Loop → Seleccionar "Mi API (Auto)"
   ↓
4. Usar tags: {snap_auto_miapi_titulo}, {snap_auto_miapi_contenido}
```

### Para APIs con Arrays Anidados (Manual)
```
1. Configurar Endpoint base en "API Endpoints"
   ↓
2. Crear Query Type manual en "Query Types"
   ↓
3. Configurar Items Path: "data.productos"
   ↓
4. En Bricks: Query Loop → Seleccionar "Productos (Manual)"
   ↓
5. Usar tags: {snap_productos_nombre}, {snap_productos_precio}
```

## 🌟 Ejemplo Práctico

### API Response:
```json
{
  "data": {
    "productos": [
      {"nombre": "Producto 1", "precio": 100, "categoria": "Electrónicos"},
      {"nombre": "Producto 2", "precio": 200, "categoria": "Hogar"}
    ],
    "total": 2,
    "pagina": 1
  }
}
```

### Configuración:
1. **Endpoint**: `https://api.ejemplo.com/productos` → Query Type `Productos API (Auto)`
2. **Query Type Manual**: Items Path `data.productos` → Query Type `Lista Productos (Manual)`

### Tags Disponibles:
- **Automático**: `{snap_auto_productos_total}`, `{snap_auto_productos_pagina}`
- **Manual**: `{snap_productos_nombre}`, `{snap_productos_precio}`, `{snap_productos_categoria}`

## 🐛 Correcciones de Bugs
- ✅ Corregidos errores de sintaxis en `render_dynamic_tags_dynamic`
- ✅ Eliminado código duplicado y bloques catch huérfanos
- ✅ Validación PHP sin errores

## 📚 Documentación Actualizada
- README principal actualizado
- Guías de uso en el dashboard
- Ejemplos prácticos añadidos
- Release notes detalladas

## 🔄 Compatibilidad
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Bricks Builder**: 1.8+

## 🚨 Notas Importantes
- Los Query Types existentes seguirán funcionando
- No se requiere reconfiguración de endpoints existentes
- Los dynamic tags antiguos siguen siendo compatibles

---

## 👨‍💻 Para Desarrolladores

### Hooks Principales
- `bricks/setup/control_options` - Registro de Query Types
- `bricks/query/run` - Ejecución de queries
- `bricks/dynamic_tags_list` - Registro de Dynamic Tags
- `bricks/dynamic_data/render_content` - Renderizado de tags

### Estructura de Tags
```php
// Automáticos
{snap_auto_[endpoint_name]_[field]}

// Manuales  
{snap_[source_name]_[field]}
```

### Debug
Activar `WP_DEBUG` para ver logs detallados del procesamiento de tags y queries.

---

**¡Gracias por usar Bricks API Integrator!** 🎉
