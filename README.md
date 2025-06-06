# Bricks API Integrator v2.1.5

> Sistema completo para integrar APIs externas con Bricks Builder - Ahora con sistema diferenciado AUTO/MANUAL para máxima flexibilidad.

[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)]()
[![Bricks Builder](https://img.shields.io/badge/Bricks%20Builder-1.8+-green.svg)]()
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)]()
[![License](https://img.shields.io/badge/License-GPL%202-red.svg)]()

## 🎯 ¿Qué es Bricks API Integrator?

**Transforma cualquier API en un sitio web completo** con WordPress y Bricks Builder. Crea automáticamente:

- ✅ **Query Types Diferenciados** - Automáticos y Manuales
- ✅ **Dynamic Tags Inteligentes** - Prefijos diferenciados para máxima claridad
- ✅ **URLs limpias** como `/productos/123/` para páginas de detalle
- ✅ **Parámetros dinámicos** que se adaptan al contexto actual
- ✅ **Arrays anidados** con Items Path para estructuras complejas
- ✅ **Interfaz moderna** con sistema explicativo y guías paso a paso

## 🆕 **Novedades v2.1.5**

### **🚀 Optimización de Rendimiento**
- **Eliminación de logs de inicialización**: Reducción significativa de la escritura en logs durante la inicialización del plugin
- **Mejora de rendimiento**: Menor sobrecarga en el servidor al eliminar registros de debug innecesarios
- **Logs más limpios**: Mantenimiento de logs de errores críticos para facilitar la depuración cuando sea necesario

## 🔄 **Novedades v2.1.1 - Sistema Diferenciado**

### **🎯 Query Types Automáticos vs Manuales**

#### 🤖 **Automáticos (Auto)**
- **Se crean automáticamente** al configurar un Endpoint
- **Aparecen como**: `Mi API (Auto)` en Bricks
- **Tags**: `{snap_auto_miapi_campo}`
- **Ideal para**: APIs simples y directas

#### ⚙️ **Manuales (Manual)**  
- **Se crean manualmente** en página Query Types
- **Aparecen como**: `Mi Lista (Manual)` en Bricks
- **Tags**: `{snap_milista_campo}`
- **Ideal para**: Arrays anidados con `items_path`

### **🏷️ Sistema de Tags Mejorado**
```html
<!-- Tags Automáticos (desde endpoints) -->
<h1>{snap_auto_productos_titulo}</h1>
<p>{snap_auto_productos_descripcion}</p>

<!-- Tags Manuales (desde query types con items_path) -->
<div class="producto">
  <h3>{snap_productos_nombre}</h3>
  <p>Precio: ${snap_productos_precio}</p>
  <span>{snap_productos_categoria}</span>
</div>
```

### **📊 Dashboard Explicativo**
- **Estadísticas diferenciadas**: Endpoints vs Query Types manuales
- **Guías paso a paso**: Flujo de trabajo recomendado
- **Ejemplos prácticos**: Cómo manejar APIs complejas
- **Static Values**: Valores fijos para filtros específicos

### **Templates con URLs Limpias**
- **Archive**: `/productos/` → Lista todos los productos
- **Single**: `/productos/123/` → Detalle del producto 123
- **SEO Friendly**: URLs descriptivas automáticas
- **WordPress Native**: Usa sistema de rewrite rules nativo

## 📦 Instalación

1. **Descarga** el plugin
2. **Sube** a `/wp-content/plugins/bricks-api-integrator/`
3. **Activa** desde WordPress Admin
4. **Ve** a `API Integrator` en el menú admin

## 🎨 Uso Rápido

### Paso 1: Configura tu API
```yaml
Endpoint: Mi API de Productos
URL: https://api.tienda.com/productos
Parámetros Dinámicos:
  - id → Parámetro URL → Valor por defecto: 1
```

### Paso 2: Crea tu Página con Bricks
```html
<h1>{snap_productos_titulo}</h1>
<div class="precio">${snap_productos_precio}</div>
<p>{snap_productos_descripcion}</p>
```

### Paso 3: Configura Template (Opcional)
```yaml
Template: Detalle de Producto
Tipo: Single (Detail)
URL Base: productos
Resultado: /productos/123/ automático
```

### ¡Listo! 🎉
Tienes un sitio completo con páginas de detalle automáticas.

## 🔧 Configuración Avanzada

### **Múltiples Fuentes de Parámetros**

#### API de E-commerce
```yaml
Endpoint: Productos por Categoría
URL: https://api.shop.com/productos
Parámetros:
  - categoria → Meta Field del Post → "electronica"
  - precio_max → Parámetro URL → "1000"
```

#### Directorio de Profesionales
```yaml
Endpoint: Doctores
URL: https://api.salud.com/doctores  
Parámetros:
  - especialidad → Parámetro URL → "cardiologia"
  - ciudad → Meta Field del Post → "madrid"
```

### **Templates Personalizadas**

#### Estructura de URLs
```
/productos/ → Lista de productos
/productos/123/ → Producto ID 123
/productos/page/2/ → Página 2 de productos
/doctores/juan-perez/ → Doctor con slug "juan-perez"
```

## 📊 Casos de Uso Reales

### **🛒 E-commerce**
- **API**: WooCommerce, Shopify, API personalizada
- **URLs**: `/productos/zapatillas-nike-123/`
- **Tags**: `{snap_productos_nombre}`, `{snap_productos_precio}`

### **🏥 Directorio Médico**
- **API**: Sistema de gestión médica
- **URLs**: `/doctores/dr-martinez-cardiologia/`
- **Tags**: `{snap_doctores_nombre}`, `{snap_doctores_especialidad}`

### **🏠 Inmobiliaria**
- **API**: MLS, portal inmobiliario
- **URLs**: `/propiedades/casa-madrid-456/`
- **Tags**: `{snap_propiedades_titulo}`, `{snap_propiedades_precio}`

### **📰 Noticias**
- **API**: CMS headless, RSS avanzado
- **URLs**: `/noticias/breaking-news-today/`
- **Tags**: `{snap_noticias_titulo}`, `{snap_noticias_contenido}`

## 🎯 Ventajas vs Alternativas

| Característica | Bricks API Integrator | Otros Plugins | Custom Code |
|----------------|----------------------|---------------|-------------|
| **URLs Limpias** | ✅ Automático | ❌ Manual | ⚠️ Complejo |
| **Bricks Integration** | ✅ Nativo | ⚠️ Limitado | ❌ Ninguna |
| **Dynamic Parameters** | ✅ Avanzado | ⚠️ Básico | ⚠️ Custom |
| **No Code Solution** | ✅ Visual | ⚠️ Parcial | ❌ Solo código |
| **SEO Ready** | ✅ Optimizado | ⚠️ Básico | ⚠️ Depends |
| **Maintenance** | ✅ Plugin Updates | ⚠️ Manual | ❌ Full Custom |

## 📋 Requisitos

- **WordPress**: 5.0 o superior
- **PHP**: 7.4 o superior  
- **Bricks Builder**: 1.5 o superior (recomendado)
- **Memoria**: 128MB mínimo (256MB recomendado)

## 🆘 Soporte y Debug

### **Logs de Debug**
```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Logs en: /wp-content/debug.log
```

### **Shortcode de Debug**
```
[debug_api_integrator]
```
Muestra estadísticas del plugin en cualquier página.

### **Test de APIs**
Usa el botón "🧪 Test API" en cada endpoint para verificar conectividad.

## 🤝 Contribuir

1. **Fork** el repositorio
2. **Crea** una rama para tu feature: `git checkout -b feature/amazing-feature`
3. **Commit** tus cambios: `git commit -m 'Add amazing feature'`
4. **Push** a la rama: `git push origin feature/amazing-feature`
5. **Abre** un Pull Request

## 📄 Licencia

Este proyecto está bajo la licencia GPL v2. Ver [LICENSE](LICENSE) para más detalles.

## 🙏 Agradecimientos

- **Bricks Builder** por crear el mejor page builder para WordPress
- **WordPress Community** por la base sólida
- **Contributors** que hacen posible este proyecto

---

**Desarrollado con ❤️ por [sn4p.dev](https://sn4p.dev)**

¿Te gusta el plugin? ⭐ **Dale una estrella** en GitHub y **comparte** con la comunidad.

## Uso de Endpoints con Arrays Anidados

Si tu API devuelve un array anidado (por ejemplo, `data.memes`), debes:

1. Crear el endpoint normalmente y poner el `items_path` (ej: `data.memes`).
2. Para usar el array en un Query Loop de Bricks, crea un **Source manual**:
   - Asocia el endpoint.
   - Pon el mismo `items_path`.
   - Guarda el Source.
3. En Bricks, selecciona el Query Type generado por el Source manual (ej: `source_memes_api`).
4. Usa los dynamic tags generados para mostrar los campos.

### Diferencia entre AUTO y MANUAL
- **AUTO:** Para respuestas planas o de detalle (un solo objeto o array raíz).
- **MANUAL (Source):** Para arrays anidados, listados o estructuras complejas.

### Ejemplo
```json
{
  "success": true,
  "data": {
    "memes": [ ... ]
  }
}
```
- `items_path`: `data.memes`
- Query Type: Source manual asociado al endpoint.
