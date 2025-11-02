# 🔍 Análisis del Sistema Actual de Queries

**Versión:** 0.2-beta
**Fecha:** 2025-11-02
**Propósito:** Identificar limitaciones y diseñar mejoras

---

## 📊 Estado Actual

### **Archivo Principal:** `includes/sources/sources-register.php`

#### **Método `get_results()` - Líneas 129-248**

**Flujo Actual:**
```
1. Validar source_id
2. Obtener configuración de source y endpoint
3. Construir URL con paginación básica
4. Hacer petición HTTP con autenticación ✅
5. Obtener respuesta JSON
6. Aplicar items_path si existe
7. FILTRADO BÁSICO (líneas 206-226) ⚠️
8. Normalizar respuesta
9. Devolver array de objetos
```

---

## ⚠️ Limitaciones Detectadas

### **1. Filtrado Muy Limitado**

**Código Actual** (líneas 206-226):
```php
// Solo filtra por UN campo (id_param) con operador EQUALS
if (isset($items) && $id_value !== null && is_array($items)) {
    $items = array_filter($items, function($item) use ($id_param, $id_value) {
        if (is_array($item) && isset($item[$id_param])) {
            return (string)$item[$id_param] === (string)$id_value;
        }
        // ...
        return false;
    });
}
```

**Problemas:**
- ❌ Solo soporta **1 filtro** (id_param)
- ❌ Solo operador **equals**
- ❌ No permite **múltiples condiciones**
- ❌ No hay **UI para configurar** filtros
- ❌ Valor viene solo de **URL** (`$_GET`)

---

### **2. Paginación Básica**

**Código Actual** (líneas 186-192):
```php
$pagination_type = isset($source['pagination_type']) ? $source['pagination_type'] : 'none';
$pagination_param = isset($source['pagination_param']) ? $source['pagination_param'] : '';
$per_page_param = isset($source['per_page_param']) ? $source['per_page_param'] : '';
$use_pagination = isset($query_args['pagination']) && $query_args['pagination'];
$page = 1;
$items_per_page = isset($query_args['items_per_page']) ? intval($query_args['items_per_page']) : 10;
```

**Problemas:**
- ⚠️ Implementación **incompleta** (comentario "resto de la lógica")
- ❌ No hay diferentes **formatos** de paginación
- ❌ No hay **Load More** o **Infinite Scroll**
- ❌ No calcula **total de páginas**

---

### **3. Sin Ordenación**

**Estado:**
- ❌ **No existe** ningún código de ordenación
- ❌ No hay UI para configurar
- ❌ No se puede ordenar por campos

---

### **4. Sin Búsqueda**

**Estado:**
- ❌ **No existe** funcionalidad de búsqueda
- ❌ No hay búsqueda en múltiples campos
- ❌ No hay parámetro de búsqueda desde URL

---

## 🎯 Mejoras Propuestas

### **1. Sistema de Filtros Avanzado**

#### **Estructura de Datos**

```php
'filters' => [
    [
        'field' => 'category',
        'operator' => 'equals',
        'value' => 'restaurant',
        'source' => 'static',  // static, url, post_meta, user_meta
        'logic' => 'AND'       // AND, OR (para combinar con siguiente filtro)
    ],
    [
        'field' => 'city',
        'operator' => 'contains',
        'value' => '{query_var:ciudad}',
        'source' => 'url',
        'logic' => 'AND'
    ],
    [
        'field' => 'price',
        'operator' => 'less_than',
        'value' => '50',
        'source' => 'static',
        'logic' => 'OR'
    ]
]
```

#### **Operadores Soportados**

| Operador | Descripción | Ejemplo |
|----------|-------------|---------|
| `equals` | Igualdad exacta | `category == 'restaurant'` |
| `not_equals` | Diferente de | `status != 'draft'` |
| `contains` | Contiene texto | `name contains 'hotel'` |
| `not_contains` | No contiene | `description not_contains 'closed'` |
| `starts_with` | Empieza con | `name starts_with 'Hotel'` |
| `ends_with` | Termina con | `name ends_with 'Beach'` |
| `greater_than` | Mayor que | `price > 100` |
| `less_than` | Menor que | `rating < 3` |
| `greater_or_equal` | Mayor o igual | `reviews >= 10` |
| `less_or_equal` | Menor o igual | `distance <= 5` |
| `in` | Está en array | `category in ['hotel','resort']` |
| `not_in` | No está en array | `status not_in ['draft','trash']` |
| `empty` | Campo vacío | `description is empty` |
| `not_empty` | Campo no vacío | `image is not empty` |

#### **Fuentes de Valor**

| Fuente | Descripción | Ejemplo |
|--------|-------------|---------|
| `static` | Valor fijo configurado | `'restaurant'` |
| `url` | Parámetro de URL | `{query_var:categoria}` |
| `post_meta` | Meta del post actual | `{post_meta:ciudad_id}` |
| `user_meta` | Meta del usuario logueado | `{user_meta:preferred_city}` |
| `post_field` | Campo del post | `{post_field:ID}` |
| `dynamic` | Tag dinámico de Bricks | `{dc:post:id}` |

---

### **2. Paginación Avanzada**

#### **Formatos a Implementar**

**A) Page/Limit (más común)**
```php
'pagination' => [
    'type' => 'page_limit',
    'page_param' => 'page',      // ?page=2
    'limit_param' => 'limit',    // &limit=10
    'default_limit' => 10
]
```

**B) Offset/Limit**
```php
'pagination' => [
    'type' => 'offset_limit',
    'offset_param' => 'offset',  // ?offset=20
    'limit_param' => 'limit',    // &limit=10
    'default_limit' => 10
]
```

**C) Cursor-based**
```php
'pagination' => [
    'type' => 'cursor',
    'cursor_param' => 'cursor',       // ?cursor=abc123
    'limit_param' => 'limit',
    'next_cursor_path' => 'pagination.next_cursor'  // Path en respuesta JSON
]
```

**D) Link Header (GitHub)**
```php
'pagination' => [
    'type' => 'link_header',
    'limit_param' => 'per_page',
    'default_limit' => 30
]
```

---

### **3. Ordenación**

#### **Estructura Propuesta**

```php
'sorting' => [
    [
        'field' => 'name',
        'order' => 'ASC'  // ASC, DESC
    ],
    [
        'field' => 'created_at',
        'order' => 'DESC'
    ]
]
```

#### **Fuentes de Ordenación**

- **Static**: Ordenación fija configurada
- **URL**: Parámetro `?sort=name&order=asc`
- **Dynamic**: Desde configuración de Bricks

---

### **4. Búsqueda**

#### **Estructura Propuesta**

```php
'search' => [
    'enabled' => true,
    'param' => 's',                    // ?s=hotel
    'fields' => ['name', 'description', 'tags'],
    'case_sensitive' => false,
    'operator' => 'contains'           // contains, equals, starts_with
]
```

---

## 🏗️ Arquitectura Propuesta

### **Nuevos Archivos**

```
includes/
├── queries/
│   ├── class-query-builder.php      ← Constructor principal
│   ├── class-query-filters.php      ← Lógica de filtros
│   ├── class-query-pagination.php   ← Paginación avanzada
│   ├── class-query-sorting.php      ← Ordenación
│   └── class-query-search.php       ← Búsqueda
```

### **Modificaciones en Archivos Existentes**

**1. sources-register.php - Método `get_results()`**

```php
public function get_results($query_args = []) {
    // ... código existente ...

    // NUEVO: Aplicar filtros configurados
    if (!empty($source['filters'])) {
        require_once BRICKS_API_INTEGRATOR_PATH . 'includes/queries/class-query-filters.php';
        $filter_engine = new Bricks_API_Query_Filters();
        $items = $filter_engine->apply_filters($items, $source['filters']);
    }

    // NUEVO: Aplicar búsqueda
    if (!empty($source['search']) && !empty($_GET[$source['search']['param']])) {
        require_once BRICKS_API_INTEGRATOR_PATH . 'includes/queries/class-query-search.php';
        $search_engine = new Bricks_API_Query_Search();
        $items = $search_engine->apply_search($items, $_GET[$source['search']['param']], $source['search']);
    }

    // NUEVO: Aplicar ordenación
    if (!empty($source['sorting'])) {
        require_once BRICKS_API_INTEGRATOR_PATH . 'includes/queries/class-query-sorting.php';
        $sort_engine = new Bricks_API_Query_Sorting();
        $items = $sort_engine->apply_sorting($items, $source['sorting']);
    }

    // ... resto del código ...
}
```

---

## 📋 Plan de Implementación

### **Fase 1: Filtros (Prioridad Alta)**

1. ✅ Análisis completado
2. ⏭️ Crear `class-query-filters.php`
3. ⏭️ Diseñar UI con repetidores
4. ⏭️ Implementar 14 operadores
5. ⏭️ Implementar 6 fuentes de valores
6. ⏭️ Testing con Inventrip y JSONPlaceholder

**Tiempo estimado:** 3-4 días

### **Fase 2: Paginación (Prioridad Alta)**

1. ⏭️ Crear `class-query-pagination.php`
2. ⏭️ Implementar 4 formatos
3. ⏭️ UI para configurar paginación
4. ⏭️ Load More / Infinite Scroll
5. ⏭️ Testing con diferentes APIs

**Tiempo estimado:** 3-4 días

### **Fase 3: Ordenación y Búsqueda (Prioridad Media)**

1. ⏭️ Crear `class-query-sorting.php` y `class-query-search.php`
2. ⏭️ UI para configurar
3. ⏭️ Testing completo

**Tiempo estimado:** 2-3 días

---

## 🔗 Compatibilidad con APIs Reales

### **JSONPlaceholder**
```
✅ Filtros: Sí (ejemplo: ?userId=1)
✅ Paginación: Sí (?_page=2&_limit=10)
✅ Ordenación: Sí (?_sort=id&_order=desc)
❌ Búsqueda: No
```

### **GitHub REST API**
```
✅ Filtros: Sí (?type=owner&visibility=public)
✅ Paginación: Sí (Link header)
✅ Ordenación: Sí (?sort=created&direction=desc)
✅ Búsqueda: Sí (?q=query)
```

### **Inventrip**
```
✅ Filtros: Sí (?category=poi)
✅ Paginación: Sí (?offset=20&limit=10)
❌ Ordenación: No documentada
❌ Búsqueda: No documentada
```

---

## 📊 Métricas de Mejora

| Funcionalidad | Antes | Después |
|--------------|-------|---------|
| **Filtros** | 1 campo, 1 operador | N campos, 14 operadores |
| **Fuentes de valor** | Solo URL | 6 fuentes diferentes |
| **Paginación** | 1 formato básico | 4 formatos completos |
| **Ordenación** | ❌ No existe | ✅ Simple + múltiple |
| **Búsqueda** | ❌ No existe | ✅ Múltiples campos |
| **UI** | ❌ No configurable | ✅ UI completa |

---

## 🎯 Próximo Paso

**Crear `class-query-filters.php`** con:
- Método `apply_filters($items, $filters_config)`
- Soporte para 14 operadores
- Soporte para 6 fuentes de valores
- Lógica AND/OR entre filtros

---

**Elaborado por:** sn4p Dev
**Versión del documento:** 1.0
**Última actualización:** 2025-11-02
**Estado:** ✅ **ANÁLISIS COMPLETADO**
