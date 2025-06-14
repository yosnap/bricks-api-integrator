#!/bin/bash

# Script de aplicación automática de correcciones
# Bricks API Integrator - Corrección de Query Loop y Dynamic Tags

echo "=== BRICKS API INTEGRATOR - APLICACIÓN DE CORRECCIONES ==="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar pasos
show_step() {
    echo -e "${BLUE}📋 PASO $1:${NC} $2"
}

# Función para mostrar éxito
show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Función para mostrar advertencia
show_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Función para mostrar error
show_error() {
    echo -e "${RED}❌ $1${NC}"
}

echo "Este script te ayudará a aplicar las correcciones para resolver:"
echo "• Query Loop no renderiza datos"
echo "• Dynamic Tags no aparecen en Bricks"
echo "• Problemas con arrays anidados (items_path)"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "bricks-api-integrator.php" ]; then
    show_error "No se encuentra el archivo bricks-api-integrator.php"
    echo "Asegúrate de ejecutar este script desde el directorio del plugin:"
    echo "/wp-content/plugins/bricks-api-integrator/"
    exit 1
fi

show_step "1" "Verificando archivos..."

# Verificar que existe el archivo de correcciones
if [ ! -f "CORRECCIONES_ESPECIFICAS.php" ]; then
    show_error "No se encuentra el archivo CORRECCIONES_ESPECIFICAS.php"
    echo "Asegúrate de que el archivo está en el mismo directorio."
    exit 1
fi

show_success "Archivos encontrados"

show_step "2" "Creando backup..."

# Crear backup con timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="bricks-api-integrator.php.backup_$TIMESTAMP"

if cp bricks-api-integrator.php "$BACKUP_FILE"; then
    show_success "Backup creado: $BACKUP_FILE"
else
    show_error "Error al crear backup"
    exit 1
fi

show_step "3" "Verificando estructura del archivo principal..."

# Verificar que las funciones existen en el archivo
FUNCTIONS_TO_CHECK=("extract_nested_items" "convert_api_data_for_bricks" "add_dynamic_tags_dynamic" "render_dynamic_tags_dynamic")
MISSING_FUNCTIONS=()

for func in "${FUNCTIONS_TO_CHECK[@]}"; do
    if ! grep -q "function $func\|public function $func\|private function $func" bricks-api-integrator.php; then
        MISSING_FUNCTIONS+=("$func")
    fi
done

if [ ${#MISSING_FUNCTIONS[@]} -gt 0 ]; then
    show_warning "Las siguientes funciones no se encontraron:"
    for func in "${MISSING_FUNCTIONS[@]}"; do
        echo "  - $func"
    done
    echo ""
    echo "Esto es normal si las funciones están definidas como métodos de clase."
fi

show_step "4" "Generando archivo con correcciones aplicadas..."

# Crear un archivo temporal con instrucciones detalladas
cat > INSTRUCCIONES_APLICACION.md << 'EOF'
# INSTRUCCIONES DETALLADAS PARA APLICAR CORRECCIONES

## ⚠️ IMPORTANTE: LEE TODAS LAS INSTRUCCIONES ANTES DE COMENZAR

### 1. VERIFICACIONES PREVIAS
- ✅ Backup creado automáticamente
- ✅ Archivo de correcciones disponible
- ⚠️ WordPress debe estar en modo debug para ver logs

### 2. ACTIVAR DEBUG EN WORDPRESS
Añade estas líneas a `wp-config.php` (antes de "/* That's all, stop editing! */"):

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 3. APLICAR CORRECCIONES MANUALMENTE

#### CORRECCIÓN A: extract_nested_items (línea ~715)
Buscar:
```php
private function extract_nested_items($data, $items_path) {
```

Reemplazar toda la función con la versión `extract_nested_items_FIXED` del archivo CORRECCIONES_ESPECIFICAS.php

#### CORRECCIÓN B: convert_api_data_for_bricks (línea ~730)
Buscar:
```php
private function convert_api_data_for_bricks($api_data) {
```

Reemplazar toda la función con la versión `convert_api_data_for_bricks_FIXED`

#### CORRECCIÓN C: run_custom_query_dynamic - sección sources (línea ~680)
En la función `run_custom_query_dynamic`, buscar la sección que maneja sources:
```php
} else {
    // Para sources: obtener endpoint y aplicar items_path
```

Reemplazar esa sección con `run_custom_query_dynamic_SOURCES_SECTION_FIXED`

#### CORRECCIÓN D: add_dynamic_tags_dynamic (línea ~780)
Buscar:
```php
public function add_dynamic_tags_dynamic($tags) {
```

Reemplazar con `add_dynamic_tags_dynamic_FIXED`

#### CORRECCIÓN E: render_dynamic_tags_dynamic (línea ~800)
Buscar:
```php
public function render_dynamic_tags_dynamic($content, $post, $context) {
```

Reemplazar con `render_dynamic_tags_dynamic_FIXED`

### 4. DESPUÉS DE APLICAR LAS CORRECCIONES

1. **Limpiar caché:**
   - Admin → Bricks API Integrator → "Clear Cache"
   - Limpiar caché de WordPress si usas plugins

2. **Regenerar tags y query types:**
   - Admin → Bricks API Integrator → Sources
   - Editar "Vehículos Motor"
   - Click "Crear tags y query types dinámicos"

3. **Probar en Bricks:**
   - Crear nuevo Query Loop
   - Seleccionar "Vehículos Motor (Source)"
   - Verificar que aparecen dynamic tags

4. **Verificar logs:**
   - Revisar `/wp-content/debug.log`
   - Buscar mensajes que empiecen con "QUERY LOOP DEBUG" o "DYNAMIC TAGS DEBUG"

### 5. VERIFICACIÓN DE FUNCIONAMIENTO

#### Query Loop debería:
- ✅ Mostrar datos en el loop
- ✅ Respetar el items_path configurado ("items")
- ✅ Generar logs de debug con información detallada

#### Dynamic Tags deberían:
- ✅ Aparecer en el selector de Bricks
- ✅ Renderizar valores correctos
- ✅ Funcionar con la estructura de datos anidada

### 6. SI HAY PROBLEMAS

1. **Query Loop vacío:**
   - Verificar logs de debug
   - Comprobar que items_path es correcto ("items")
   - Verificar autenticación de API

2. **Dynamic Tags no aparecen:**
   - Regenerar tags desde Sources
   - Verificar logs "DYNAMIC TAGS DEBUG"
   - Limpiar caché completamente

3. **Errores de PHP:**
   - Restaurar backup: `cp bricks-api-integrator.php.backup_TIMESTAMP bricks-api-integrator.php`
   - Verificar sintaxis de las correcciones aplicadas

### 7. CONTACTO PARA SOPORTE
Si necesitas ayuda adicional, proporciona:
- Contenido del archivo debug.log
- Captura de pantalla del error
- Descripción detallada del problema
EOF

show_success "Instrucciones detalladas creadas: INSTRUCCIONES_APLICACION.md"

show_step "5" "Verificando configuración actual..."

# Verificar si WP_DEBUG está activado
if [ -f "../../../wp-config.php" ]; then
    if grep -q "define('WP_DEBUG', true)" ../../../wp-config.php; then
        show_success "WP_DEBUG ya está activado"
    else
        show_warning "WP_DEBUG no está activado. Sigue las instrucciones para activarlo."
    fi
else
    show_warning "No se encontró wp-config.php en la ubicación esperada"
fi

echo ""
echo "=== RESUMEN ==="
echo ""
show_success "✅ Backup creado: $BACKUP_FILE"
show_success "✅ Instrucciones detalladas disponibles: INSTRUCCIONES_APLICACION.md"
show_success "✅ Archivo de correcciones disponible: CORRECCIONES_ESPECIFICAS.php"
echo ""
echo -e "${BLUE}📖 PRÓXIMOS PASOS:${NC}"
echo "1. Lee INSTRUCCIONES_APLICACION.md"
echo "2. Activa WP_DEBUG en wp-config.php"
echo "3. Aplica las correcciones manualmente siguiendo las instrucciones"
echo "4. Prueba el funcionamiento en Bricks"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
echo "- Las correcciones deben aplicarse manualmente por seguridad"
echo "- Revisa cada cambio antes de guardarlo"
echo "- Si algo falla, usa el backup para restaurar"
echo ""
echo -e "${GREEN}🚀 ¡Listo para aplicar las correcciones!${NC}"
