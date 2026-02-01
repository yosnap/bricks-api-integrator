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
│   └── inmovilla-elements.php    # Shortcodes UI para Inmovilla
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

## Flujo de Datos

### Query Loop de Inmovilla

```
1. Bricks renderiza Query Loop
   ↓
2. Hook bricks/query/run captura query
   ↓
3. Inmovilla_Query_Handler::run_query()
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
