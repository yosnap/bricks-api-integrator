# Componente de Filtros Activos de Inmovilla

## Descripción

El componente de filtros activos permite mostrar en la página todos los filtros que están actualmente aplicados, con la posibilidad de eliminarlos individualmente o todos a la vez.

## Formas de Usar el Componente

### Opción 1: Shortcode (Recomendado para Bricks Builder)

Para mostrar los filtros activos en cualquier página, simplemente añade el shortcode:

```
[inmovilla_active_filters]
```

### Características del Shortcode

- **Detección Automática**: Automáticamente detecta todos los parámetros `key_*` en la URL
- **Etiquetas Inteligentes**: Muestra etiquetas legibles para cada filtro (Tipo de Propiedad, Ubicación, etc.)
- **Eliminación Individual**: Cada filtro tiene un botón (×) para eliminarlo sin afectar los otros
- **Limpiar Todo**: Botón para eliminar todos los filtros de una vez
- **Responsive**: Se adapta automáticamente a pantallas móviles y escritorio

### Ejemplo de URL

Si tienes una URL como:
```
https://ejemplo.com/inmovilla/?key_tipo=casa&key_loca=madrid&key_precio_min=100000
```

El shortcode mostrará:
```
Filtros activos:
[Tipo de Propiedad: casa] [Ubicación: madrid] [Precio Mínimo: 100000] [Limpiar todos]
```

## API Pública de JavaScript

El componente expone una API pública a través de `window.InmnovillaFilters` para acceder y modificar filtros desde JavaScript:

### Obtener Filtros Activos

```javascript
const filtros = window.InmnovillaFilters.getActiveFilters();
console.log(filtros);
// Resultado: { key_tipo: "casa", key_loca: "madrid" }
```

### Establecer un Filtro

```javascript
window.InmnovillaFilters.setFilter('key_tipo', 'apartamento');
```

Esto:
1. Establece el filtro en el mapa interno
2. Dispara el debounce de 500ms
3. Actualiza la URL automáticamente
4. Recarga la página para procesar los nuevos parámetros

### Eliminar un Filtro

```javascript
window.InmnovillaFilters.clearFilter('key_tipo');
```

Esto:
1. Elimina el filtro del mapa
2. Dispara la actualización automática
3. Recargar la página sin ese parámetro

### Limpiar Todos los Filtros

```javascript
window.InmnovillaFilters.clearAllFilters();
```

Redirige a la página sin ningún parámetro de filtro.

## Configuración de Etiquetas

### Etiquetas Predefinidas

El sistema incluye etiquetas predefinidas para los filtros más comunes:

```
key_tipo      → Tipo de Propiedad
key_loca      → Ubicación
key_zona      → Zona
key_precio_min → Precio Mínimo
key_precio_max → Precio Máximo
key_hab       → Habitaciones
key_banos     → Baños
key_suelo     → Superficie
key_estado    → Estado
key_buscar    → Búsqueda
```

### Etiquetas Personalizadas

Las etiquetas se pueden personalizar en la configuración de Sources de Inmovilla. Si defines campos de filtro con un `label`, se utilizará automáticamente en el componente.

## Estilos Personalizados

El componente usa clases CSS que pueden personalizarse:

```css
/* Contenedor principal */
.inmovilla-active-filters {}

/* Título "Filtros activos:" */
.inmovilla-active-filters-title {}

/* Lista de filtros */
.inmovilla-active-filters-list {}

/* Badge individual de filtro */
.inmovilla-filter-badge {}

/* Botón de eliminar (×) */
.inmovilla-filter-remove {}

/* Botón "Limpiar todos" */
.inmovilla-filters-clear-all {}
```

## Estructura HTML Generada

```html
<div class="inmovilla-active-filters">
    <div class="inmovilla-active-filters-title">Filtros activos:</div>
    <div class="inmovilla-active-filters-list">
        <div class="inmovilla-filter-badge" data-filter="key_tipo">
            <span class="inmovilla-filter-label">Tipo de Propiedad:</span>
            <span class="inmovilla-filter-value">casa</span>
            <button class="inmovilla-filter-remove">×</button>
        </div>
        <!-- Más filtros... -->
        <button class="inmovilla-filters-clear-all">Limpiar todos</button>
    </div>
</div>
```

## Integración con Bricks Builder

1. **En Bricks Builder** (editor visual):
   - Añade un elemento de texto o HTML
   - Insertar el shortcode: `[inmovilla_active_filters]`
   - Usa Dynamic Tags si necesitas mostrarlos en otros elementos

2. **En PHP** (directamente en plantillas):
   ```php
   echo do_shortcode('[inmovilla_active_filters]');
   ```

3. **En JavaScript** (desde otras funciones):
   ```javascript
   // Obtener estado actual
   const activos = window.InmnovillaFilters.getActiveFilters();

   // Limpiar un filtro específico
   window.InmnovillaFilters.clearFilter('key_precio_max');
   ```

## Comportamiento de Actualización

### Flujo Automático

1. Usuario cambia un filtro (selecciona valor en un select/input)
2. Evento `change` o `input` se dispara
3. Debounce espera 500ms sin cambios
4. Se construye la nueva URL con parámetros
5. Se recargar la página (`window.location.href`)
6. Bricks procesa los nuevos parámetros
7. Los resultados se actualizan
8. `restoreFilterValuesFromUrl()` repone los valores en los inputs
9. Shortcode muestra los filtros activos

## Consideraciones de Rendimiento

- El debounce de 500ms previene llamadas excesivas
- Solo se recargar la página cuando hay cambios reales
- Los filtros vacíos se ignoran automáticamente
- Los parámetros no `key_*` se preservan en la URL

## Troubleshooting

### El shortcode no muestra nada
- Verifica que haya parámetros `key_*` en la URL actual
- Los filtros vacíos se ignoran automáticamente

### Los filtros no se eliminar al clickear
- Verifica que JavaScript está habilitado
- Abre la consola del navegador (F12) para ver errores

### La página se recargar infinitamente
- Verifica que no hay conflictos con otros scripts
- Revisa el debounce en `inmovilla-auto-filters.js`

### Opción 2: Contenedor HTML Automático

Si no deseas usar el shortcode, puedes crear un contenedor HTML con el atributo `data-inmovilla-filters` y el componente se renderizará automáticamente:

```html
<div data-inmovilla-filters></div>
```

El script `inmovilla-display-filters.js` detectará automáticamente este contenedor y lo rellenará con los filtros activos.

**Ventaja**: No requiere shortcode, funciona con HTML puro.

### Opción 3: API de JavaScript

Para usar la API de manera más avanzada desde JavaScript:

```javascript
// Renderizar en un contenedor específico
const container = document.querySelector('.mi-contenedor-filtros');
window.InmnovillaFilterDisplay.render(container);

// Re-inicializar todo
window.InmnovillaFilterDisplay.init();
```

## Archivos de Estilos

El componente carga automáticamente su CSS desde:
```
assets/inmovilla-active-filters.css
```

Que incluye:
- Gradientes de fondo
- Animaciones de entrada
- Estilos responsivos para móvil
- Transiciones suaves
- Efectos hover

## Archivos de Scripts

El componente utiliza dos scripts principales:

1. **inmovilla-auto-filters.js** (v2.0)
   - Detecta cambios en filtros
   - Actualiza la URL automáticamente
   - Expone API `window.InmnovillaFilters`

2. **inmovilla-display-filters.js** (v1.0)
   - Renderiza los filtros activos
   - Soporta shortcode y contenedores HTML
   - Detección automática de contenedores

## Versión

- **Versión**: 1.0
- **Dependencias**:
  - WordPress 5.0+
  - Bricks Builder
  - inmovilla-auto-filters.js (v2.0+)
  - inmovilla-display-filters.js (v1.0+)
