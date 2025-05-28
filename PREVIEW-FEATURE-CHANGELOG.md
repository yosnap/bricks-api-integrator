# Query Preview Feature - Changelog

## Archivos Añadidos

### 1. `/includes/query-preview.php`
- **Trait QueryPreview**: Funcionalidad completa de preview para Query Types
- **AJAX Handler**: `ajax_preview_query_type()` para obtener datos de preview
- **Funciones helper**: Generar tags de muestra, formatear valores
- **Enqueue scripts**: Cargar CSS y JS necesarios

### 2. `/assets/query-preview.js`
- **JavaScript principal**: Manejo de modal de preview
- **Event handlers**: Click en botones de preview, navegación por pestañas
- **Modal dinámico**: Construcción de interfaz con datos reales
- **Copy to clipboard**: Funcionalidad para copiar dynamic tags

### 3. `/assets/query-preview.css`
- **Estilos del modal**: Diseño responsive y moderno
- **Pestañas**: Navegación entre datos, tags y JSON raw
- **Botones de preview**: Estilos para integración en admin

## Archivos Modificados

### 1. `/bricks-api-integrator.php`
- **Línea 24**: Añadido `require_once` para query-preview.php
- **Línea 91**: Añadido trait `QueryPreview` a la clase principal
- **Línea 99**: Añadido `$this->init_query_preview_hooks()` en constructor

## Funcionalidades Implementadas

### ✨ Preview en Admin
- Botón "🔍 Preview Datos" en cada endpoint configurado
- Modal con 3 pestañas: Datos, Dynamic Tags, JSON Raw
- Visualización del primer elemento del API
- Copy to clipboard para dynamic tags

### ✨ Integración con Bricks
- Hooks preparados para botones de preview en Bricks Builder
- Metadatos de query types con información de preview
- Scripts encolados automáticamente

### ✨ AJAX Endpoints
- `wp_ajax_preview_query_type`: Obtener datos de preview
- Manejo de errores y validación de nonce
- Soporte para query types automáticos y manuales

## Próximos Pasos para Implementación

1. **Probar funcionalidad básica**:
   - Configurar endpoint de vehículos
   - Usar botón de preview en admin
   - Verificar que muestra datos correctos

2. **Verificar dynamic tags**:
   - Comprobar que se generan correctamente
   - Probar copy to clipboard
   - Validar formato de tags

3. **Integración con Bricks** (opcional):
   - Añadir botones en Query Loop controls
   - Verificar compatibilidad con Bricks Builder

## Commit Message Sugerido

```
feat: Add Query Preview functionality for API endpoints

- Add QueryPreview trait with modal interface
- Implement AJAX handler for preview data
- Add responsive CSS styles for preview modal
- Include JavaScript for modal management and copy functionality
- Integrate preview buttons in admin endpoint configuration
- Support for both automatic and manual query types
- Display first item data with organized field groups
- Generate and display dynamic tags with copy-to-clipboard
- Show raw JSON data for debugging purposes

Files added:
- includes/query-preview.php
- assets/query-preview.js  
- assets/query-preview.css

Files modified:
- bricks-api-integrator.php (add trait and initialization)
```

## Testing Checklist

- [ ] Plugin se activa sin errores
- [ ] Aparece botón "🔍 Preview Datos" en endpoints
- [ ] Modal se abre al hacer click
- [ ] Se muestran datos del primer elemento
- [ ] Dynamic tags se generan correctamente
- [ ] Copy to clipboard funciona
- [ ] Modal se cierra correctamente
- [ ] Responsive design funciona en móvil
- [ ] No hay errores en consola JavaScript
- [ ] No hay errores PHP en logs
