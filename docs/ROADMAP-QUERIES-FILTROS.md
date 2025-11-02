# 🚀 Roadmap: Queries, Filtros y Paginación

**Versión:** 0.3-beta (Planificación)
**Fecha:** 2025-11-02
**Prioridad:** Alta

---

## 📋 Contexto

Actualmente el plugin tiene un **sistema básico de queries** que funciona pero necesita mejoras en:
- 🔍 **Filtrado de datos**: No hay UI para configurar filtros
- 📄 **Paginación**: Implementación básica, falta soporte para múltiples formatos
- 🔢 **Ordenación**: No implementada
- 🔎 **Búsqueda**: No disponible

---

## 🎯 Objetivos de la Fase 2

### **1. Sistema Avanzado de Filtros**

#### **Funcionalidades a Implementar**
- ✅ **Filtros por campo**: Configurables desde UI
- ✅ **Múltiples condiciones**: AND/OR entre filtros
- ✅ **Operadores**: `equals`, `contains`, `greater_than`, `less_than`, `in`, `not_in`
- ✅ **Filtros dinámicos**: Desde URL, post meta, user meta
- ✅ **Filtros estáticos**: Valores fijos configurados

#### **Caso de Uso**
```php
// Ejemplo: Filtrar POIs por categoría y ciudad
'filters' => [
    [
        'field' => 'category',
        'operator' => 'equals',
        'value' => 'restaurante',
        'source' => 'static'
    ],
    [
        'field' => 'city',
        'operator' => 'equals',
        'value' => '{query_var:ciudad}',
        'source' => 'url'
    ]
]
```

#### **UI Propuesta**
```
┌─────────────────────────────────────────────────┐
│ 🔍 Filtros                                      │
├─────────────────────────────────────────────────┤
│ ┌───────────────────────────────────────────┐   │
│ │ Campo: [category ▼]                       │   │
│ │ Operador: [equals ▼]                      │   │
│ │ Valor: [restaurante]                      │   │
│ │ Fuente: [static ▼]                        │   │
│ │                              [✕ Eliminar] │   │
│ └───────────────────────────────────────────┘   │
│ ┌───────────────────────────────────────────┐   │
│ │ Campo: [city ▼]                           │   │
│ │ Operador: [equals ▼]                      │   │
│ │ Valor: [{query_var:ciudad}]               │   │
│ │ Fuente: [url ▼]                           │   │
│ │                              [✕ Eliminar] │   │
│ └───────────────────────────────────────────┘   │
│                                                 │
│ [+ Añadir Filtro]                               │
└─────────────────────────────────────────────────┘
```

---

### **2. Paginación Avanzada**

#### **Formatos Soportados**

**A) Paginación por Página/Límite**
```php
// Formato 1: ?page=2&limit=10
'pagination' => [
    'type' => 'page_limit',
    'page_param' => 'page',
    'limit_param' => 'limit',
    'default_limit' => 10
]
```

**B) Paginación por Offset**
```php
// Formato 2: ?offset=20&limit=10
'pagination' => [
    'type' => 'offset_limit',
    'offset_param' => 'offset',
    'limit_param' => 'limit',
    'default_limit' => 10
]
```

**C) Cursor-based (APIs modernas)**
```php
// Formato 3: ?cursor=eyJpZCI6MTIzfQ==&limit=10
'pagination' => [
    'type' => 'cursor',
    'cursor_param' => 'cursor',
    'limit_param' => 'limit',
    'next_cursor_path' => 'pagination.next_cursor'
]
```

**D) Header-based (GitHub, etc)**
```php
// Formato 4: Link header con rel="next"
'pagination' => [
    'type' => 'link_header',
    'limit_param' => 'per_page',
    'default_limit' => 30
]
```

#### **UI Propuesta**
```
┌─────────────────────────────────────────────────┐
│ 📄 Paginación                                   │
├─────────────────────────────────────────────────┤
│ Tipo: [page_limit ▼]                            │
│                                                 │
│ Parámetro de página: [page]                    │
│ Parámetro de límite: [limit]                   │
│ Elementos por página: [10]                     │
│                                                 │
│ ☑ Habilitar "Load More"                        │
│ ☑ Habilitar "Infinite Scroll"                  │
└─────────────────────────────────────────────────┘
```

---

### **3. Ordenación de Datos**

#### **Funcionalidades**
- ✅ **Ordenar por campo**: Configurar desde UI
- ✅ **Dirección**: ASC/DESC
- ✅ **Múltiples campos**: Ordenación secundaria
- ✅ **Ordenación dinámica**: Desde parámetros URL

#### **Caso de Uso**
```php
// Ordenar POIs por nombre ascendente, luego por fecha descendente
'sorting' => [
    [
        'field' => 'name',
        'order' => 'ASC'
    ],
    [
        'field' => 'created_at',
        'order' => 'DESC'
    ]
]
```

#### **UI Propuesta**
```
┌─────────────────────────────────────────────────┐
│ 🔢 Ordenación                                   │
├─────────────────────────────────────────────────┤
│ ┌───────────────────────────────────────────┐   │
│ │ Campo: [name ▼]                           │   │
│ │ Orden: [ASC ▼]                            │   │
│ │                              [✕ Eliminar] │   │
│ └───────────────────────────────────────────┘   │
│                                                 │
│ [+ Añadir Ordenación]                           │
└─────────────────────────────────────────────────┘
```

---

### **4. Sistema de Búsqueda**

#### **Funcionalidades**
- ✅ **Búsqueda por términos**: En uno o múltiples campos
- ✅ **Búsqueda desde URL**: `?search=restaurante`
- ✅ **Búsqueda en campos específicos**: Configurar campos a buscar
- ✅ **Case-insensitive**: Búsqueda sin distinción de mayúsculas

#### **Caso de Uso**
```php
// Buscar en nombre y descripción
'search' => [
    'enabled' => true,
    'param' => 's',
    'fields' => ['name', 'description', 'tags'],
    'case_sensitive' => false
]
```

---

## 🏗️ Arquitectura Propuesta

### **Estructura de Archivos**

```
includes/
├── queries/
│   ├── query-builder.php      ← Nuevo: Constructor de queries
│   ├── query-filters.php      ← Nuevo: Sistema de filtros
│   ├── query-pagination.php   ← Nuevo: Paginación avanzada
│   ├── query-sorting.php      ← Nuevo: Ordenación
│   └── query-search.php       ← Nuevo: Búsqueda
└── ui/
    └── queries-page.php       ← Nuevo: UI de configuración
```

### **Flujo de Datos**

```
┌─────────────────┐
│ Configuración   │
│ (Admin UI)      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Query Builder   │ ← Construye query completa
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Filtros         │ → Aplica filtros configurados
│ Paginación      │ → Calcula offset/límite
│ Ordenación      │ → Ordena resultados
│ Búsqueda        │ → Filtra por términos
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ API Manager     │ → Ejecuta petición
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Resultados      │
│ (Bricks Loop)   │
└─────────────────┘
```

---

## 📅 Planificación por Etapas

### **Fase 2.1: Sistema de Filtros (Semana 1)**
- [ ] Diseñar estructura de datos para filtros
- [ ] Crear UI con repetidores para configurar filtros
- [ ] Implementar lógica de filtrado en `query-filters.php`
- [ ] Integrar con Query Loop existente
- [ ] Testing con diferentes operadores
- [ ] Documentación de uso

**Tiempo estimado:** 3-4 días

---

### **Fase 2.2: Paginación Avanzada (Semana 2)**
- [ ] Identificar formatos de paginación más comunes
- [ ] Implementar 4 tipos: page/limit, offset, cursor, link-header
- [ ] Crear UI para configurar tipo de paginación
- [ ] Implementar "Load More" y "Infinite Scroll"
- [ ] Testing con APIs reales (GitHub, Inventrip, etc)
- [ ] Documentación con ejemplos

**Tiempo estimado:** 3-4 días

---

### **Fase 2.3: Ordenación y Búsqueda (Semana 3)**
- [ ] Implementar ordenación simple y múltiple
- [ ] UI para configurar ordenación
- [ ] Sistema de búsqueda en múltiples campos
- [ ] Integración con parámetros URL
- [ ] Testing completo
- [ ] Documentación

**Tiempo estimado:** 2-3 días

---

## 🧪 Plan de Testing

### **Tests de Filtros**
1. ✅ Filtro simple con operador `equals`
2. ✅ Filtro con operador `contains`
3. ✅ Múltiples filtros con AND
4. ✅ Filtros dinámicos desde URL
5. ✅ Filtros desde post meta

### **Tests de Paginación**
1. ✅ Paginación page/limit (JSONPlaceholder)
2. ✅ Paginación offset (Inventrip)
3. ✅ Cursor-based (GitHub GraphQL)
4. ✅ Link header (GitHub REST)
5. ✅ Load More funcional
6. ✅ Infinite Scroll funcional

### **Tests de Ordenación**
1. ✅ Ordenar por campo simple ASC
2. ✅ Ordenar por campo simple DESC
3. ✅ Ordenación múltiple
4. ✅ Ordenación dinámica desde URL

### **Tests de Búsqueda**
1. ✅ Búsqueda simple en un campo
2. ✅ Búsqueda en múltiples campos
3. ✅ Búsqueda case-insensitive
4. ✅ Búsqueda desde parámetro URL

---

## 📊 Métricas de Éxito

| Funcionalidad | Estado Actual | Estado Objetivo |
|--------------|---------------|-----------------|
| **Filtros** | ❌ No disponible | ✅ UI completa + 5 operadores |
| **Paginación** | 🟡 Básica | ✅ 4 formatos + Load More |
| **Ordenación** | ❌ No disponible | ✅ Simple + Múltiple |
| **Búsqueda** | ❌ No disponible | ✅ Múltiples campos |
| **Cobertura Tests** | 40% | 80% |

---

## 🔗 Compatibilidad

### **APIs de Referencia para Testing**

| API | Filtros | Paginación | Ordenación | Búsqueda |
|-----|---------|------------|------------|----------|
| **JSONPlaceholder** | ✅ `userId=1` | ✅ `_page=2&_limit=10` | ✅ `_sort=id&_order=desc` | ❌ |
| **GitHub** | ✅ `type=owner` | ✅ Link header | ✅ `sort=created&direction=desc` | ✅ `q=query` |
| **Inventrip** | ✅ `category=poi` | ✅ `offset=20&limit=10` | ❌ | ❌ |
| **WordPress REST** | ✅ Multiple | ✅ `page=2&per_page=10` | ✅ `orderby=date&order=desc` | ✅ `search=term` |

---

## 💡 Consideraciones Técnicas

### **Rendimiento**
- ✅ Cachear resultados filtrados/ordenados
- ✅ Lazy loading para infinite scroll
- ✅ Índices apropiados en búsquedas

### **UX**
- ✅ Preview en tiempo real de filtros
- ✅ Indicadores de carga para paginación
- ✅ Feedback visual en búsquedas

### **Compatibilidad**
- ✅ Bricks Query Loop
- ✅ Custom queries desde código
- ✅ Backward compatibility con configuración actual

---

## 📚 Documentación a Crear

1. **Guía de Filtros** (`docs/guias/FILTROS.md`)
   - Tipos de filtros
   - Operadores disponibles
   - Ejemplos de uso
   - Casos de uso comunes

2. **Guía de Paginación** (`docs/guias/PAGINACION.md`)
   - Formatos soportados
   - Configuración por tipo de API
   - Load More e Infinite Scroll
   - Troubleshooting

3. **Guía de Ordenación** (`docs/guias/ORDENACION.md`)
   - Ordenación simple y múltiple
   - Combinación con filtros
   - Ejemplos prácticos

4. **Guía de Búsqueda** (`docs/guias/BUSQUEDA.md`)
   - Configuración de campos
   - Integración con formularios
   - Optimización

---

## 🎯 Prioridad de Implementación

Basándome en el valor vs complejidad:

```
Alta Prioridad (Implementar primero):
1. 🔍 Sistema de Filtros        → Alto valor, complejidad media
2. 📄 Paginación Avanzada       → Alto valor, complejidad media

Media Prioridad:
3. 🔢 Ordenación                → Medio valor, complejidad baja
4. 🔎 Búsqueda                  → Medio valor, complejidad baja

Baja Prioridad:
5. Load More / Infinite Scroll  → Medio valor, complejidad alta
```

---

## 🚀 Siguiente Paso

**¿Empezamos por el Sistema de Filtros?**

Propongo comenzar con:
1. Analizar el código actual de queries
2. Diseñar la estructura de datos para filtros
3. Crear UI con repetidores (similar a Field Transformers)
4. Implementar lógica de filtrado
5. Testing con API real (Inventrip o JSONPlaceholder)

---

**Elaborado por:** sn4p Dev
**Versión del documento:** 1.0
**Última actualización:** 2025-11-02
**Estado:** 📝 **EN PLANIFICACIÓN**
