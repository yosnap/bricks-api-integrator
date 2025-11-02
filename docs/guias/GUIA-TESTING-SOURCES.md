# 🧪 GUÍA DE TESTING - Query Loop Sources

## Objetivo
Verificar que los Sources ahora funcionan correctamente en el Query Loop de Bricks después de la corrección implementada.

## ⚡ PRUEBA RÁPIDA

### Paso 1: Verificar la corrección
1. Activar `WP_DEBUG = true` en wp-config.php
2. Acceder al admin de WordPress
3. Ir a **API Integrator → Query Types**

### Paso 2: Crear un Source de prueba

**Configuración recomendada:**
- **Nombre:** `Productos Test`
- **Endpoint:** Seleccionar uno existente (o crear uno primero)
- **Items Path:** `data.productos` (ajustar según tu API)
- **Field Prefix:** Dejar vacío (usa 'snap_' por defecto)

### Paso 3: Generar Tags
1. Hacer clic en **"🧪 Test Source"** para verificar datos
2. Hacer clic en **"⚡ Crear tags y query types dinámicos"**
3. Verificar que aparecen los tags con checkboxes
4. Hacer clic en **"💾 Guardar selección de tags"**

### Paso 4: Probar en Bricks
1. Ir a **Bricks Builder**
2. Añadir elemento con **Query Loop**
3. En **Query Type** buscar **"Productos Test (Source)"**
4. Seleccionarlo
5. **¡Verificar que aparecen los elementos en loop!**

## 🔍 DEBUGGING

### Logs esperados en wp-content/debug.log:

```
QUERY LOOP DEBUG - object_type: snap_source_productos-test
QUERY LOOP DEBUG - is_endpoint: false  
QUERY LOOP DEBUG - is_source: true
QUERY LOOP DEBUG - source endpoint_id: 0
QUERY LOOP DEBUG - source_config found: yes
QUERY LOOP DEBUG - items_path: data.productos
QUERY LOOP DEBUG - extracted data count: 3
```

### Si NO aparecen logs:
- Verificar que `WP_DEBUG = true`
- Verificar que el Query Type tiene "(Source)" en el nombre
- Limpiar caché de Bricks y WordPress

## 🐛 PROBLEMAS COMUNES

### ❌ "No aparecen elementos en el loop"
**Causa:** items_path incorrecto
**Solución:** 
1. Ir al Source → Editar
2. Usar botón "Probar Ruta" para verificar items_path
3. Ajustar la ruta hasta que encuentre datos

### ❌ "Query Type no aparece en Bricks"
**Causa:** Tags no generados
**Solución:**
1. Asegurarse que el Source tiene un endpoint válido
2. Generar tags manualmente con el botón ⚡
3. Recargar Bricks Builder

### ❌ "Error al obtener datos"
**Causa:** Endpoint no funciona
**Solución:**
1. Probar el endpoint individual primero
2. Verificar autenticación y URL
3. Comprobar que el endpoint devuelve datos

## 📋 CHECKLIST DE VERIFICACIÓN

- [ ] Source creado correctamente
- [ ] Endpoint asociado funciona
- [ ] Items path configurado (ej: `data.productos`)
- [ ] Tags generados y guardados
- [ ] Query Type aparece en Bricks como "(Source)"
- [ ] Debug logs muestran `is_source: true`
- [ ] Loop funciona y muestra elementos

## 🎯 CASOS DE PRUEBA

### Caso 1: API con estructura simple
```json
{
  "productos": [
    {"id": 1, "nombre": "Producto 1"},
    {"id": 2, "nombre": "Producto 2"}
  ]
}
```
**Items Path:** `productos`

### Caso 2: API con wrapper
```json
{
  "status": "success",
  "data": {
    "productos": [
      {"id": 1, "nombre": "Producto 1"}
    ]
  }
}
```
**Items Path:** `data.productos`

### Caso 3: API con múltiples niveles
```json
{
  "response": {
    "result": {
      "items": {
        "productos": [
          {"id": 1, "nombre": "Producto 1"}
        ]
      }
    }
  }
}
```
**Items Path:** `response.result.items.productos`

## ✅ RESULTADO ESPERADO

Después de la corrección:
- ✅ **Endpoints** siguen funcionando (no afectados)
- ✅ **Sources** ahora funcionan en Query Loop
- ✅ **Items path** se aplica correctamente
- ✅ **Tags dinámicos** disponibles para ambos

## 📞 SOPORTE

Si algo no funciona:
1. Revisar los logs de debug
2. Verificar que la corrección está aplicada en `bricks-api-integrator.php`
3. Comprobar que `bricks_api_normalize_slug()` existe en field-extractor.php
4. Limpiar cachés de WordPress y Bricks
