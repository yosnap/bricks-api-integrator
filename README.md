# Bricks API Integrator v2.0

> Sistema completo para integrar APIs externas con Bricks Builder - Crea sitios web dinámicos con páginas de detalle automáticas y URLs limpias.

[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)]()
[![Bricks Builder](https://img.shields.io/badge/Bricks%20Builder-1.5+-green.svg)]()
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)]()
[![License](https://img.shields.io/badge/License-GPL%202-red.svg)]()

## 🎯 ¿Qué es Bricks API Integrator?

**Transforma cualquier API en un sitio web completo** con WordPress y Bricks Builder. Crea automáticamente:

- ✅ **Dynamic Tags** para mostrar datos de API en Bricks
- ✅ **Query Types** para Query Loop de listados
- ✅ **URLs limpias** como `/productos/123/` para páginas de detalle
- ✅ **Parámetros dinámicos** que se adaptan al contexto actual
- ✅ **Templates automáticas** con rewrite rules de WordPress

## 🚀 Características Principales

### **Sistema Único de Dynamic Tags**
```html
<!-- Automáticamente disponibles en Bricks -->
<h1>{snap_productos_titulo}</h1>
<p>{snap_productos_descripcion}</p>
<img src="{snap_productos_imagen}" alt="{snap_productos_titulo}">
<p>Precio: ${snap_productos_precio}</p>
```

### **Parámetros Dinámicos Avanzados**
- **URL Parameters**: `?id=123` → Obtiene datos del producto 123
- **Post Context**: Usa ID del post actual para datos relacionados
- **User Context**: Datos específicos del usuario logueado
- **Meta Fields**: Integra campos personalizados de WordPress
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
