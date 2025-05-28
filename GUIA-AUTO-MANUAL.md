# 🎯 Guía: Sistema AUTO/MANUAL - Bricks API Integrator v2.1.1

## 📖 Conceptos Clave

### ¿Cuándo usar cada tipo?

#### 🤖 Query Types AUTOMÁTICOS
- **APIs simples** con estructura plana
- **Datos únicos** (un solo registro por consulta)  
- **Páginas de detalle** individuales
- **Configuración rápida** sin complejidades

#### ⚙️ Query Types MANUALES
- **APIs con arrays** o listas de elementos
- **Datos anidados** que requieren Items Path
- **Listados dinámicos** para iterar elementos
- **Control granular** sobre qué mostrar

---

## 🔄 Flujo de Trabajo Completo

### Escenario 1: API Simple (Automático)

**API**: `https://api.ejemplo.com/usuario/123`
**Response**:
```json
{
  "id": 123,
  "nombre": "Juan Pérez",
  "email": "juan@ejemplo.com",
  "avatar": "https://ejemplo.com/avatar.jpg"
}
```

**Configuración**:
1. **API Endpoints** → Nuevo Endpoint
2. **Nombre**: `Usuario`
3. **URL**: `https://api.ejemplo.com/usuario/{id}`
4. **Guardar** → Se crea automáticamente `Usuario (Auto)`

**En Bricks**:
```html
<h1>{snap_auto_usuario_nombre}</h1>
<p>{snap_auto_usuario_email}</p>
<img src="{snap_auto_usuario_avatar}" alt="Avatar">
```

### Escenario 2: API con Arrays (Manual)

**API**: `https://api.ejemplo.com/productos`
**Response**:
```json
{
  "status": "success",
  "data": {
    "productos": [
      {
        "id": 1,
        "nombre": "Producto A",
        "precio": 100,
        "categoria": "Electrónicos"
      },
      {
        "id": 2,
        "nombre": "Producto B", 
        "precio": 200,
        "categoria": "Hogar"
      }
    ]
  }
}
```

**Configuración**:
1. **API Endpoints** → Nuevo Endpoint
2. **Nombre**: `Productos API`
3. **URL**: `https://api.ejemplo.com/productos`
4. **Guardar** → Se crea `Productos API (Auto)`

5. **Query Types** → Nuevo Query Type
6. **Nombre**: `Lista Productos`
7. **Endpoint**: Seleccionar `Productos API`
8. **Items Path**: `data.productos`
9. **Guardar** → Se crea `Lista Productos (Manual)`

**En Bricks**:
```html
<!-- Query Loop usando "Lista Productos (Manual)" -->
<div class="producto">
  <h3>{snap_productos_nombre}</h3>
  <p>Precio: ${snap_productos_precio}</p>
  <span class="categoria">{snap_productos_categoria}</span>
</div>
```

---

## 🏷️ Sistema de Dynamic Tags

### Nomenclatura y Prefijos

| Tipo | Prefijo | Ejemplo | Uso |
|------|---------|---------|-----|
| Automático | `snap_auto_` | `{snap_auto_usuario_nombre}` | Datos únicos/simples |
| Manual | `snap_` | `{snap_productos_precio}` | Arrays/listas |

### Tags Especiales para Arrays

#### Para elementos específicos:
```html
{snap_productos_item_nombre}     <!-- Elemento por índice -->
{snap_productos_last_precio}     <!-- Último elemento -->
{snap_productos_first_categoria}  <!-- Primer elemento -->
{snap_productos_count}           <!-- Cantidad total -->
```

---

## 📊 Items Path: Navegando Estructuras Complejas

### Ejemplos de Items Path:

#### Estructura Simple:
```json
{
  "productos": [...]
}
```
**Items Path**: `productos`

#### Estructura Anidada:
```json
{
  "data": {
    "results": {
      "items": [...]
    }
  }
}
```
**Items Path**: `data.results.items`

#### Múltiples Niveles:
```json
{
  "response": {
    "api": {
      "v1": {
        "catalog": {
          "products": [...]
        }
      }
    }
  }
}
```
**Items Path**: `response.api.v1.catalog.products`

---

## 🔧 Troubleshooting

### Problema: No aparecen los Dynamic Tags

**Solución**:
1. Verificar que el endpoint devuelve datos
2. Limpiar caché del plugin
3. Revisar Items Path (si es manual)
4. Activar `WP_DEBUG` para ver logs

### Problema: Query Type no aparece en Bricks

**Solución**:
1. Verificar que el endpoint está configurado correctamente
2. Refrescar la página de Bricks
3. Limpiar caché de Bricks y WordPress

### Problema: Tags muestran contenido vacío

**Solución**:
1. Verificar estructura de la respuesta API
2. Revisar nombres de campos (case sensitive)
3. Comprobar autenticación de API
4. Revisar logs de WordPress

---

## 💡 Mejores Prácticas

### Nomenclatura
- **Endpoints**: Nombres descriptivos (`Productos`, `Usuarios`, `Pedidos`)
- **Query Types**: Especificar propósito (`Lista Productos`, `Detalle Usuario`)
- **Items Path**: Usar notación clara (`data.items`, `response.products`)

### Performance
- **Cache**: Configurar duración apropiada (5 min desarrollo, 1 hora producción)
- **Límites**: Usar pagination en APIs grandes
- **Endpoints**: Separar listados de detalles

### Debugging
- Activar `WP_DEBUG` durante desarrollo
- Usar herramientas de debug de Bricks
- Revisar Network tab del navegador para calls API

---

## 🚀 Casos de Uso Avanzados

### E-commerce con Filtros
```
Endpoint Base: /productos
Query Types Manuales:
- /productos → Lista todos
- /productos?categoria=electronica → Electrónicos  
- /productos?precio_max=100 → Productos baratos
```

### Blog Multi-idioma
```
Endpoint Base: /posts
Query Types:
- /posts?lang=es → Posts en español
- /posts?lang=en → Posts en inglés
- /posts/{id}?lang={current_lang} → Post específico
```

### Dashboard de Usuario
```
Endpoints:
- /user/{id} → Datos generales (Auto)
- /user/{id}/orders → Pedidos (Manual)
- /user/{id}/favorites → Favoritos (Manual)
```

---

**¿Necesitas ayuda?** Consulta los logs de WordPress o crea un issue en GitHub.
