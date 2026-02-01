# Elementos UI de Inmovilla para Bricks

Guía completa para usar los shortcodes de paginación, filtros y ordenamiento de Inmovilla en Bricks Builder.

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Templates Disponibles](#templates-disponibles)
3. [Shortcodes](#shortcodes)
   - [Resumen de Resultados](#resumen-de-resultados)
   - [Paginación](#paginación)
   - [Ordenamiento](#ordenamiento)
   - [Filtros](#filtros)
   - [Formulario de Filtros](#formulario-de-filtros)
4. [Ejemplos de Uso](#ejemplos-de-uso)
5. [Personalización CSS](#personalización-css)
6. [Campos de Filtro Disponibles](#campos-de-filtro-disponibles)

---

## Introducción

Los elementos nativos de Bricks (Pagination, Filter Select, Query Results Summary) están diseñados para WordPress Posts (WP_Query) y **no funcionan** con APIs externas como Inmovilla.

Estos shortcodes proporcionan una alternativa que trabaja directamente con parámetros URL, permitiendo filtrar, ordenar y paginar los resultados de Inmovilla.

### Cómo usar en Bricks

1. Añade un elemento **Shortcode** o **Code** en tu estructura
2. Escribe el shortcode deseado
3. El shortcode se renderizará tanto en el editor como en el frontend

---

## Templates Disponibles

Todos los shortcodes soportan el atributo `template` con 4 opciones:

| Template | Descripción |
|----------|-------------|
| `modern` | (Por defecto) Diseño moderno con sombras y bordes redondeados |
| `classic` | Diseño tradicional con bordes y fondos sutiles |
| `minimal` | Diseño minimalista sin bordes, solo líneas inferiores |
| `custom` | Base para personalización completa con variables CSS |

---

## Shortcodes

### Resumen de Resultados

Muestra información sobre los resultados actuales.

```
[inmovilla_results_summary]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `format` | `Mostrando {from}-{to} de {total} inmuebles` | Formato del texto |
| `empty_text` | `No se encontraron resultados` | Texto cuando no hay resultados |

**Variables disponibles en format:**
- `{from}` - Número del primer resultado
- `{to}` - Número del último resultado
- `{total}` - Total de resultados
- `{page}` - Página actual
- `{pages}` - Total de páginas

**Ejemplos:**

```
[inmovilla_results_summary template="minimal"]

[inmovilla_results_summary format="Página {page} de {pages} ({total} inmuebles)"]

[inmovilla_results_summary format="{total} propiedades encontradas" empty_text="Sin resultados"]
```

---

### Paginación

Muestra la navegación entre páginas.

```
[inmovilla_pagination]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `show_info` | `true` | Mostrar "Mostrando X-Y de Z" |
| `prev_text` | `Anterior` | Texto del botón anterior |
| `next_text` | `Siguiente` | Texto del botón siguiente |
| `max_links` | `5` | Número máximo de enlaces de página visibles |

**Ejemplos:**

```
[inmovilla_pagination template="classic"]

[inmovilla_pagination show_info="false" max_links="7"]

[inmovilla_pagination prev_text="← Anterior" next_text="Siguiente →"]
```

---

### Ordenamiento

Selector desplegable para ordenar resultados.

```
[inmovilla_order_select]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `label` | `Ordenar por:` | Etiqueta del selector |
| `show_label` | `true` | Mostrar/ocultar etiqueta |
| `default` | (vacío) | Valor por defecto |

**Opciones de ordenamiento disponibles:**
- `precio` / `precio_asc` / `precio_desc` - Por precio
- `fecha` / `fecha_asc` / `fecha_desc` - Por fecha de alta
- `metros` - Por metros cuadrados
- `habitaciones` - Por número de habitaciones
- `referencia` - Por referencia

**Ejemplos:**

```
[inmovilla_order_select template="minimal"]

[inmovilla_order_select label="Ordenar:" show_label="true"]

[inmovilla_order_select default="precio_desc"]
```

---

### Filtros

#### Filtro Select

Selector desplegable para filtrar por un campo.

```
[inmovilla_filter_select field="keyacci"]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `field` | (requerido) | Campo de filtro |
| `label` | (auto) | Etiqueta personalizada |
| `show_label` | `true` | Mostrar/ocultar etiqueta |
| `placeholder` | `Todos` | Texto del placeholder |
| `source` | (auto) | Fuente de opciones dinámicas |

**Ejemplos:**

```
[inmovilla_filter_select field="keyacci" label="Operación"]

[inmovilla_filter_select field="key_tipo" placeholder="Todos los tipos"]

[inmovilla_filter_select field="key_loca" label="Ciudad" source="ciudades"]
```

#### Filtro de Rango

Dos campos numéricos para filtrar por rango (desde-hasta).

```
[inmovilla_filter_range field="precio" label="Precio"]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `field` | (requerido) | Campo de filtro |
| `label` | (vacío) | Etiqueta |
| `show_label` | `true` | Mostrar/ocultar etiqueta |
| `min` | `0` | Valor mínimo permitido |
| `max` | `1000000` | Valor máximo permitido |
| `step` | `1000` | Incremento |
| `from_placeholder` | `Desde` | Placeholder del campo "desde" |
| `to_placeholder` | `Hasta` | Placeholder del campo "hasta" |

**Ejemplos:**

```
[inmovilla_filter_range field="precio" label="Precio (€)" min="0" max="2000000" step="10000"]

[inmovilla_filter_range field="metros" label="Superficie (m²)" min="0" max="500" step="10"]

[inmovilla_filter_range field="habitaciones" label="Habitaciones" min="0" max="10" step="1"]
```

#### Botón Limpiar Filtros

Enlace para eliminar todos los filtros activos.

```
[inmovilla_clear_filters]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `text` | `Limpiar filtros` | Texto del botón |

---

### Formulario de Filtros

Contenedor que agrupa múltiples filtros con layout y botones de acción.

```
[inmovilla_filters_form]
  ... filtros aquí ...
[/inmovilla_filters_form]
```

**Atributos:**

| Atributo | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `template` | `modern` | Template de estilos |
| `layout` | `horizontal` | Layout: `horizontal`, `vertical`, `grid` |
| `show_submit` | `true` | Mostrar botón de búsqueda |
| `submit_text` | `Buscar` | Texto del botón de búsqueda |
| `show_clear` | `true` | Mostrar botón de limpiar |
| `clear_text` | `Limpiar` | Texto del botón de limpiar |

**Ejemplos:**

```
[inmovilla_filters_form layout="horizontal"]
  [inmovilla_filter_select field="keyacci"]
  [inmovilla_filter_select field="key_tipo"]
  [inmovilla_order_select]
[/inmovilla_filters_form]
```

```
[inmovilla_filters_form layout="grid" template="minimal"]
  [inmovilla_filter_select field="keyacci" label="Operación"]
  [inmovilla_filter_select field="key_tipo" label="Tipo"]
  [inmovilla_filter_select field="key_loca" label="Ciudad"]
  [inmovilla_filter_range field="precio" label="Precio"]
[/inmovilla_filters_form]
```

---

## Ejemplos de Uso

### Ejemplo 1: Listado Básico

Estructura en Bricks:

```
Section
├── Container (Filtros)
│   └── Shortcode: [inmovilla_filters_form]...[/inmovilla_filters_form]
├── Container (Info)
│   └── Shortcode: [inmovilla_results_summary]
├── Container (Query Loop: Inmovilla - Inmuebles)
│   └── Div (Tarjeta de inmueble)
│       ├── Image
│       ├── Heading (Referencia)
│       └── Text (Precio)
└── Container (Paginación)
    └── Shortcode: [inmovilla_pagination]
```

### Ejemplo 2: Barra de Filtros Horizontal

```
[inmovilla_filters_form layout="horizontal" template="modern"]
  [inmovilla_filter_select field="keyacci" label="Operación"]
  [inmovilla_filter_select field="key_tipo" label="Tipo"]
  [inmovilla_filter_select field="key_loca" label="Ciudad"]
  [inmovilla_order_select label="Ordenar"]
[/inmovilla_filters_form]
```

### Ejemplo 3: Sidebar con Filtros Verticales

```
[inmovilla_filters_form layout="vertical" template="minimal"]
  [inmovilla_filter_select field="keyacci"]
  [inmovilla_filter_select field="key_tipo"]
  [inmovilla_filter_select field="key_loca"]
  [inmovilla_filter_range field="precio" label="Precio (€)"]
  [inmovilla_filter_range field="metros" label="Metros (m²)" max="500" step="10"]
[/inmovilla_filters_form]
```

### Ejemplo 4: Solo Paginación Minimalista

```
[inmovilla_results_summary template="minimal" format="{total} propiedades"]

[inmovilla_pagination template="minimal" show_info="false"]
```

---

## Personalización CSS

### Variables CSS

El template `custom` usa variables CSS que puedes sobrescribir:

```css
:root {
  --inmovilla-primary: #2563eb;       /* Color principal */
  --inmovilla-primary-hover: #1d4ed8; /* Color principal hover */
  --inmovilla-secondary: #64748b;     /* Color secundario */
  --inmovilla-border: #e2e8f0;        /* Color de bordes */
  --inmovilla-bg: #ffffff;            /* Fondo */
  --inmovilla-bg-hover: #f8fafc;      /* Fondo hover */
  --inmovilla-text: #334155;          /* Color de texto */
  --inmovilla-text-light: #64748b;    /* Texto secundario */
  --inmovilla-radius: 8px;            /* Border radius */
  --inmovilla-shadow: 0 1px 3px rgba(0,0,0,0.1); /* Sombra */
  --inmovilla-transition: all 0.2s ease; /* Transiciones */
}
```

### Ejemplo de Personalización

En tu CSS personalizado de Bricks o tema:

```css
/* Cambiar colores a tu marca */
:root {
  --inmovilla-primary: #e63946;
  --inmovilla-primary-hover: #d62839;
}

/* Personalizar paginación */
.inmovilla-pagination .inmovilla-page-link {
  border-radius: 50%;
  width: 40px;
  height: 40px;
  padding: 0;
  justify-content: center;
}

/* Personalizar filtros */
.inmovilla-filter-select select {
  border-radius: 20px;
  padding-left: 20px;
}
```

---

## Campos de Filtro Disponibles

| Campo | Descripción | Tipo |
|-------|-------------|------|
| `keyacci` | Operación (Venta/Alquiler) | Select con opciones fijas |
| `key_tipo` | Tipo de inmueble | Select dinámico (API) |
| `key_loca` | Ciudad | Select dinámico (API) |
| `key_zona` | Zona | Select dinámico (API) |
| `keyprov` | Provincia | Texto |
| `precio_desde` | Precio mínimo | Número |
| `precio_hasta` | Precio máximo | Número |
| `habitaciones` | Habitaciones mínimas | Número |
| `banyos` | Baños mínimos | Número |
| `metros_desde` | Metros mínimos | Número |
| `metros_hasta` | Metros máximos | Número |

---

## Notas Importantes

1. **Los filtros se aplican mediante parámetros URL**, lo que permite compartir enlaces con filtros aplicados.

2. **Al cambiar cualquier filtro, la página se resetea a 1** para evitar mostrar una página que no existe.

3. **Las opciones dinámicas (tipos, ciudades, zonas) se cachean por 1 hora** para mejorar el rendimiento.

4. **Los shortcodes funcionan tanto en el editor de Bricks como en el frontend**.

5. **Para el template `custom`**, usa CSS personalizado para definir los estilos según tu diseño.
