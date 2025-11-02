# Bricks API Integrator (v0.2-beta)

**Integración avanzada de APIs externas en Bricks Builder**

> 📚 **[Ver Documentación Completa](docs/README.md)** | 📋 **[CHANGELOG](CHANGELOG.md)** | 📝 **[TODO](TODO.md)**

---

## 🚀 Novedades principales (v0.2.0-beta)

### ✨ **Nuevo: Field Transformers**
- **Transformación automática de campos**: Convierte IDs de imagen en URLs completas
- **UI visual**: Sistema de repetidores clave-valor para configurar transformadores
- **4 tipos de transformadores**:
  - 🔗 **Related Endpoint**: Construir URLs desde referencias de API
  - 🌐 **URL Template**: Aplicar plantillas personalizadas
  - ⬅️ **Prefix**: Añadir prefijos a valores
  - ➡️ **Suffix**: Añadir sufijos a valores
- **Persistencia completa**: Los transformadores se guardan y aplican automáticamente
- **Aplicación en Query Loop**: Funciona en Bricks Builder sin configuración adicional
- 📚 **[Guía completa de Field Transformers](docs/guias/FIELD-TRANSFORMERS.md)**

### 🔒 **Sistema de Autenticación Unificado**
- **4 tipos soportados**: None, Bearer Token, API Key, Basic Auth
- **Función centralizada**: `prepare_auth_headers()` unifica toda la lógica
- **100% retrocompatible**: Endpoints existentes funcionan sin cambios

### 🎨 **Mejoras de UX**
- **Normalización automática**: El sistema detecta y adapta automáticamente si la API devuelve un array o un objeto único
- **Generación manual** de Query Types y Dynamic Tags desde la UI
- **Estructura de tags:** `{snap_{slug}_{campo}}` con soporte para notación de punto
- **Visualización avanzada**: Estructura, ejemplos y lista de tags con copia rápida
- **Selección de tags habilitados/deshabilitados** y guardado persistente
- **Renderizado de tags anidados** en Bricks para arrays y objetos
- **Detección automática de arrays de traducciones** con tags por idioma

---

## 🛠️ Procedimiento actualizado (flujo robusto)

1. **Añade o edita un endpoint** en la sección "API Endpoints".
2. Pulsa **"Generar Query Type y Tags Dinámicos"** para analizar la respuesta de la API y generar los tags.
3. Visualiza la estructura, ejemplo y lista de tags generados. Puedes deshabilitar los que no necesites.
4. Usa el botón **"Copiar tag"** para pegarlo directamente en Bricks.
5. Elige el Query Type generado en el Query Loop de Bricks y usa los tags en tus elementos.
6. Si la API cambia, puedes eliminar y regenerar los tags fácilmente.

**No necesitas preocuparte por el tipo de respuesta de la API:**
- Si la API devuelve un array, Bricks lo recorre en loop.
- Si la API devuelve un objeto único, Bricks lo trata como un solo elemento.
- El sistema se encarga de la normalización automáticamente.

---

## 🧩 Ejemplo de uso de tags

- Para un endpoint llamado "Países Europa":
  - Tag para el nombre común: `{snap_countries-europe_name.common}`
  - Tag para el símbolo de la moneda: `{snap_countries-europe_currencies.CZK.symbol}`

- Para un endpoint de productos:
  - Tag para el nombre: `{snap_productos_nombre}`
  - Tag para el precio: `{snap_productos_precio}`

---

## ⚠️ Notas importantes

- **Solo se generan y usan los tags habilitados** en la UI.
- El plugin detecta automáticamente si la respuesta es un array o un objeto único y lo adapta para Bricks. **No es necesario crear lógica extra ni preocuparse por el formato de la respuesta.**
- Si tienes endpoints antiguos, regenera los tags para usar el nuevo formato.
- El sistema es robusto y compatible con cualquier estructura de API (arrays, objetos, arrays anidados, etc.).
- El sistema sigue en **versión beta**: reporta cualquier bug o sugerencia internamente.

---

## 📋 Roadmap inmediato
- Integración avanzada de Sources: endpoints relacionados y arrays anidados.
- Mejoras en la documentación y ejemplos visuales.

---

## 👨‍💻 Equipo y soporte
Este repositorio es privado y en beta. Para dudas, mejoras o bugs, contacta directamente con el equipo de desarrollo.

## 🎯 ¿Qué es Bricks API Integrator?

**Transforma cualquier API en un sitio web completo** con WordPress y Bricks Builder. Crea automáticamente:

- ✅ **Query Types Diferenciados** - Automáticos y Manuales
- ✅ **Dynamic Tags Inteligentes** - Prefijos diferenciados para máxima claridad
- ✅ **URLs limpias** como `/productos/123/` para páginas de detalle
- ✅ **Parámetros dinámicos** que se adaptan al contexto actual
- ✅ **Arrays anidados** con Items Path para estructuras complejas
- ✅ **Interfaz moderna** con sistema explicativo y guías paso a paso

## 🆕 **Novedades v0.1-beta**

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

---

## 📚 Documentación

### **Documentación Completa**
Toda la documentación está organizada en la carpeta [`/docs`](docs/README.md):

```
docs/
├── README.md .................... Índice de documentación
├── REFACTORING-AUTH-SYSTEM.md ... Refactoring de autenticación
├── RESUMEN-REFACTORING-AUTH.md .. Resumen ejecutivo
├── release-notes/ ............... Notas de versión
├── correcciones/ ................ Fixes implementados
├── guias/ ....................... Tutoriales paso a paso
├── diagnosticos/ ................ Análisis de problemas
└── testing/ ..................... Guías de testing
```

### **Enlaces Rápidos**

#### **Para Empezar**
- 📖 [Guía de Testing de Autenticación](docs/testing/auth-testing-guide.md)
- 📋 [Checklist de Implementación](docs/guias/CHECKLIST_IMPLEMENTACION.md)
- 🧪 [Guía de Testing de Sources](docs/guias/GUIA-TESTING-SOURCES.md)

#### **Referencias Técnicas**
- 🔧 [Refactoring Sistema de Autenticación](docs/REFACTORING-AUTH-SYSTEM.md)
- 📊 [Diagnósticos y Fixes](docs/diagnosticos/DIAGNOSTIC_AND_FIXES.md)
- 📝 [Release Notes](docs/release-notes/)

#### **Cambios Recientes**
- 🆕 [v0.2-beta: Sistema de Autenticación Unificado](CHANGELOG.md#02-beta---2025-11-02)
- 📦 [v2.1.4: Corrección Formularios Duplicados](docs/release-notes/RELEASE-NOTES-v2.1.4.md)

### **Soporte y Debugging**

```php
// Activar debug en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Ver logs: `wp-content/debug.log`
