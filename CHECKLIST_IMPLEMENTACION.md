# ✅ CHECKLIST DE IMPLEMENTACIÓN - BRICKS API INTEGRATOR

## 🎯 OBJETIVO
Hacer que el Query Loop renderice datos y que aparezcan los Dynamic Tags en Bricks Builder.

---

## 📋 PREPARACIÓN (5 minutos)

### ☐ 1. Verificar Ubicación
```bash
# Asegúrate de estar en:
cd /Users/paulo/Local Sites/api-fetch/app/public/wp-content/plugins/bricks-api-integrator/
```

### ☐ 2. Verificar Archivos Creados
- [ ] `CORRECCIONES_ESPECIFICAS.php` ✓
- [ ] `aplicar_correcciones.sh` ✓  
- [ ] `INSTRUCCIONES_APLICACION.md` ✓
- [ ] `DIAGNOSTICO_VEHICULOS_MOTOR.md` ✓

### ☐ 3. Crear Backup Manual
```bash
cp bricks-api-integrator.php bricks-api-integrator.php.backup_manual
```

---

## 🔧 ACTIVAR DEBUG (2 minutos)

### ☐ 4. Editar wp-config.php
Añadir ANTES de `/* That's all, stop editing! */`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### ☐ 5. Verificar Debug Activo
- [ ] Archivo `/wp-content/debug.log` se crea/actualiza

---

## 🛠️ APLICAR CORRECCIONES (15 minutos)

### ☐ 6. Abrir Archivo Principal
```bash
# Abrir en tu editor preferido:
code bricks-api-integrator.php
# o
nano bricks-api-integrator.php
```

### ☐ 7. CORRECCIÓN A - extract_nested_items (línea ~715)
- [ ] Buscar: `private function extract_nested_items($data, $items_path)`
- [ ] Reemplazar TODA la función con `extract_nested_items_FIXED` de `CORRECCIONES_ESPECIFICAS.php`
- [ ] Verificar que no hay errores de sintaxis

### ☐ 8. CORRECCIÓN B - convert_api_data_for_bricks (línea ~730)  
- [ ] Buscar: `private function convert_api_data_for_bricks($api_data)`
- [ ] Reemplazar TODA la función con `convert_api_data_for_bricks_FIXED`
- [ ] Verificar que no hay errores de sintaxis

### ☐ 9. CORRECCIÓN C - run_custom_query_dynamic sección sources (línea ~680)
- [ ] Buscar: `if ($is_source) {` dentro de `run_custom_query_dynamic`
- [ ] Reemplazar esa sección con `run_custom_query_dynamic_SOURCES_SECTION_FIXED`
- [ ] Verificar que no hay errores de sintaxis

### ☐ 10. CORRECCIÓN D - add_dynamic_tags_dynamic (línea ~780)
- [ ] Buscar: `public function add_dynamic_tags_dynamic($tags)`
- [ ] Reemplazar TODA la función con `add_dynamic_tags_dynamic_FIXED`
- [ ] Verificar que no hay errores de sintaxis

### ☐ 11. CORRECCIÓN E - render_dynamic_tags_dynamic (línea ~800)
- [ ] Buscar: `public function render_dynamic_tags_dynamic($content, $post, $context)`
- [ ] Reemplazar TODA la función con `render_dynamic_tags_dynamic_FIXED`
- [ ] Verificar que no hay errores de sintaxis

### ☐ 12. Guardar y Verificar Sintaxis
```bash
php -l bricks-api-integrator.php
# Debe mostrar: "No syntax errors detected"
```

---

## 🔄 REGENERAR Y LIMPIAR (5 minutos)

### ☐ 13. Limpiar Caché
- [ ] Admin WordPress → Bricks API Integrator → "Clear Cache"
- [ ] Si usas caché plugins: limpiar caché del sitio

### ☐ 14. Regenerar Tags y Query Types
- [ ] Admin → Bricks API Integrator → Sources
- [ ] Editar "Vehículos Motor"
- [ ] Click "🔄 Actualizar datos"
- [ ] Click "⚡ Crear tags y query types dinámicos"
- [ ] Verificar mensaje de éxito

---

## 🧪 PROBAR FUNCIONAMIENTO (5 minutos)

### ☐ 15. Verificar Logs de Debug
```bash
tail -f /wp-content/debug.log | grep "QUERY LOOP\|DYNAMIC TAGS\|EXTRACT\|CONVERT"
```
Deberías ver mensajes como:
- `QUERY LOOP DEBUG - Processing source: vehiculos_motor`
- `EXTRACT DEBUG - Extracted data count: 10`  
- `DYNAMIC TAGS DEBUG - Added tag: snap_vehiculos_motor_titol_anunci`

### ☐ 16. Probar en Bricks Builder
- [ ] Ir a una página/template en Bricks
- [ ] Añadir elemento "Query Loop"
- [ ] En Type, seleccionar "Vehículos Motor (Source)"
- [ ] Verificar que aparecen elementos en el loop

### ☐ 17. Verificar Dynamic Tags
- [ ] Dentro del Query Loop, añadir elemento de texto
- [ ] Click en el icono de dynamic data
- [ ] Buscar grupo "Vehículos Motor (Source)"
- [ ] Verificar que aparecen tags como:
  - `snap_vehiculos_motor_titol_anunci`
  - `snap_vehiculos_motor_descripcio_anunci`
  - `snap_vehiculos_motor_data_creacio`

### ☐ 18. Verificar Renderizado
- [ ] Seleccionar un dynamic tag (ej: `titol_anunci`)
- [ ] Ver preview/frontend
- [ ] Confirmar que muestra datos reales de vehículos

---

## ✅ VERIFICACIÓN FINAL

### ☐ 19. Todo Funciona Correctamente
- [ ] ✅ Query Loop muestra vehículos
- [ ] ✅ Dynamic Tags aparecen en selector  
- [ ] ✅ Tags renderizan datos reales
- [ ] ✅ No hay errores en debug.log
- [ ] ✅ No hay errores en browser console

### ☐ 20. Documentar Configuración Final
Anota para referencia futura:
- **Endpoint configurado:** Motor (con Basic Auth)
- **Source configurado:** Vehículos Motor
- **Items path:** `items`
- **Query Type en Bricks:** "Vehículos Motor (Source)"
- **Dynamic Tags prefix:** `snap_vehiculos_motor_`

---

## 🚨 SI ALGO FALLA

### ☐ PLAN B - Restaurar y Debuggear
```bash
# Restaurar backup
cp bricks-api-integrator.php.backup_manual bricks-api-integrator.php

# Revisar logs
tail -50 /wp-content/debug.log

# Verificar sintaxis  
php -l bricks-api-integrator.php
```

### ☐ Problemas Comunes:
| Síntoma | Causa | Solución |
|---------|-------|----------|
| Query Loop vacío | items_path incorrecto | Verificar "items" exacto |
| No aparecen tags | No regenerados | Repetir paso 14 |
| Error PHP | Sintaxis incorrecta | Restaurar y revisar cambios |
| Sin datos | Autenticación | Verificar Basic Auth credentials |

---

## 🎉 ¡COMPLETADO!

Cuando todos los checkboxes estén marcados ✅, tu integración de Bricks con la API de vehículos estará **completamente funcional**.

### **Resultado Final:**
- 🚗 **Query Loop renderizando vehículos reales**
- 🏷️ **Dynamic Tags disponibles para todos los campos**  
- 🔧 **Plugin robusto con debugging detallado**
- 🚀 **Base sólida para futuras integraciones**

---

**¡Tiempo estimado total: 30 minutos!** ⏱️

**¿Necesitas ayuda?** Proporciona el contenido de debug.log y describe en qué paso exacto tienes problemas.
