# Arquitectura del Plugin Bricks API Integrator

## Visión General

Plugin WordPress que integra Bricks Builder con APIs externas, permitiendo usar datos de APIs como fuente de Query Loops y Dynamic Tags.

## Estructura de Archivos

```
bricks-api-integrator/
├── bricks-api-integrator.php     # Archivo principal del plugin
├── dynamic-tags.php              # Dynamic Tags para Bricks
├── CHANGELOG.md                  # Registro de cambios
│
├── docs/                         # Documentación
│   ├── arquitectura.md           # Este archivo
│   └── inmovilla-elementos-ui.md # Guía de shortcodes Inmovilla
│
├── includes/
│   ├── api-manager.php           # Gestión de endpoints API
│   ├── field-extractor.php       # Extracción de campos de respuestas API
│   ├── functions.php             # Funciones utilitarias
│   ├── query-preview.php         # Preview de queries en editor
│   ├── cleaner.php               # Limpieza de datos
│   ├── image-proxy.php           # Proxy para imágenes externas
│   │
│   ├── sources.php               # Sources genéricas para Bricks
│   ├── launcher.php              # Lanzador de queries
│   ├── templates.php             # Gestión de templates
│   │
│   ├── inmovilla-fields.php      # Campos específicos de Inmovilla
│   ├── inmovilla-auto-endpoints.php # Endpoints automáticos Inmovilla
│   ├── inmovilla-sources.php     # Query Handler para Inmovilla
│   ├── inmovilla-query-state.php # Estado de queries Inmovilla
│   ├── inmovilla-elements.php    # Shortcodes UI para Inmovilla
│   └── inmovilla-settings.php    # Panel de configuración UI
│
└── includes/sources/
    └── sources-hooks.php         # Hooks para fuentes de datos
```

## Componentes Principales

### 1. Sistema de APIs Genéricas

**api-manager.php**
- Gestiona endpoints de APIs personalizadas
- Almacena configuración en opciones WordPress
- Métodos CRUD para endpoints

**field-extractor.php**
- Analiza respuestas JSON de APIs
- Extrae campos disponibles para Dynamic Tags
- Genera estructura de mapeo

### 2. Integración con Bricks

**sources.php / sources-hooks.php**
- Registra query types personalizados en Bricks
- Hook `bricks/query/run` para ejecutar queries
- Hook `bricks/setup/control_options` para opciones

**dynamic-tags.php**
- Registra Dynamic Tags para cada source
- Formato: `{source_nombre:campo}`
- Soporte para campos anidados con notación punto

### 3. Sistema Inmovilla

**inmovilla-sources.php**
```php
class Inmovilla_Query_Handler {
    // Ejecuta queries a la API Inmovilla
    public static function run_query($results, $query_obj)

    // Captura filtros desde parámetros URL
    private static function capture_filters_from_url()

    // Construye cláusula WHERE para la API
    private static function build_where_clause($filters)

    // Captura ordenamiento
    private static function capture_order($query_obj)
}
```

**inmovilla-query-state.php**
```php
class Inmovilla_Query_State {
    // Almacena estado de queries para paginación
    public static function set($query_id, $data)
    public static function get($query_id, $key = null)

    // Informa totales a Bricks
    public static function get_total($query_id = null)
    public static function get_max_pages($query_id = null)
}
```

**inmovilla-elements.php**
- Shortcodes de UI para paginación y filtros
- 4 templates CSS: modern, classic, minimal, custom
- JavaScript para manejo de parámetros URL

**inmovilla-settings.php**
- Panel de administración "Inmovilla UI"
- 5 pestañas: General, Colores, Textos, CSS, Vista Previa
- Opciones almacenadas en `inmovilla_ui_options`
- Genera variables CSS personalizadas en wp_head

```php
// Obtener opciones
$options = inmovilla_get_ui_options();

// Opciones disponibles
$options['default_template']     // modern, classic, minimal, custom
$options['primary_color']        // Color principal (#hex)
$options['pagination_prev_text'] // Texto botón anterior
$options['results_format']       // Formato: "Mostrando {from}-{to} de {total}"
$options['custom_css']           // CSS adicional
```

### 4. Shortcodes Disponibles

| Shortcode | Descripción |
|-----------|-------------|
| `[inmovilla_pagination]` | Navegación entre páginas |
| `[inmovilla_results_summary]` | "Mostrando X-Y de Z" |
| `[inmovilla_order_select]` | Selector de ordenamiento |
| `[inmovilla_filter_select]` | Filtro desplegable |
| `[inmovilla_filter_range]` | Filtro de rango numérico |
| `[inmovilla_filters_form]` | Contenedor de filtros |
| `[inmovilla_clear_filters]` | Botón limpiar filtros |

### 5. Página de Detalle (Single)

**templates.php**
- Rewrite rules automáticas: `/inmuebles/{ref}/` → query_var `ref`
- `init_api_template_context()` setea `$bricks_api_current_item_id` en `template_redirect`
- Soporta templates Archive (listado) y Single (detalle)

**bricks-api-integrator.php**
- `build_inmovilla_detail_url()` - Construye URL de detalle desde loop_object
- `get_inmovilla_single_data()` - Obtiene datos del inmueble por ref (con cache estático)
- Dynamic tag `{snap_inmovilla-inmuebles_detail_url}` para enlazar cards

**Flujo de detalle:**
```
1. Card en listado usa {snap_inmovilla-inmuebles_detail_url}
   → Genera: /inmuebles/REF123/
   ↓
2. Rewrite rule captura ref=REF123
   ↓
3. init_api_template_context() setea $bricks_api_current_item_id
   ↓
4. render_dynamic_tags_dynamic() detecta que no hay loop_object
   ↓
5. get_inmovilla_single_data() consulta API con WHERE ref=REF123
   ↓
6. Tags se resuelven con datos del inmueble individual
```

## Flujo de Datos

### Query Loop de Inmovilla (Listado)

```
1. Bricks renderiza Query Loop
   ↓
2. Hook bricks/query/run captura query
   ↓
3. Inmovilla_Query_Handler::execute_query()
   ├── Captura filtros de $_GET
   ├── Captura ordenamiento
   ├── Construye parámetros API
   └── Ejecuta petición a Inmovilla
   ↓
4. Respuesta procesada
   ├── Items retornados a Bricks
   └── Totales almacenados en Query_State
   ↓
5. Hooks result_count/max_num_pages informan totales
   ↓
6. Shortcodes de UI leen Query_State para renderizar
```

### Filtros URL

Los filtros se pasan como parámetros GET:
- `?keyacci=1` → Operación: Venta
- `?key_tipo=2` → Tipo de inmueble
- `?precio_desde=100000&precio_hasta=300000` → Rango de precio
- `?orden=precioinmo+DESC` → Ordenamiento
- `?paged=2` → Página

## Hooks de WordPress/Bricks

| Hook | Archivo | Uso |
|------|---------|-----|
| `bricks/query/run` | inmovilla-sources.php | Ejecutar query Inmovilla |
| `bricks/query/result_count` | inmovilla-query-state.php | Total de resultados |
| `bricks/query/result_max_num_pages` | inmovilla-query-state.php | Total de páginas |
| `bricks/setup/control_options` | sources.php | Registrar query types |
| `bricks/dynamic_tags_list` | dynamic-tags.php | Registrar tags |
| `bricks/query/before_query` | templates.php | Setear source en single |
| `template_redirect` | templates.php | Contexto single + $bricks_api_current_item_id |
| `template_include` | templates.php | Cargar página correcta |

## Variables CSS

El template `custom` usa estas variables:

```css
--inmovilla-primary        /* Color principal */
--inmovilla-primary-hover  /* Color principal hover */
--inmovilla-secondary      /* Color secundario */
--inmovilla-border         /* Color de bordes */
--inmovilla-bg             /* Fondo */
--inmovilla-bg-hover       /* Fondo hover */
--inmovilla-text           /* Color de texto */
--inmovilla-text-light     /* Texto secundario */
--inmovilla-radius         /* Border radius */
--inmovilla-shadow         /* Sombra */
--inmovilla-transition     /* Transiciones */
```

## Dependencias

- WordPress 5.0+
- Bricks Builder (recomendado, no obligatorio)
- PHP 7.4+
