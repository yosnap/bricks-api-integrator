# 🔄 Field Transformers - Guía de Uso

**Versión:** 0.2-beta
**Fecha:** 2025-11-02

---

## 📖 ¿Qué son los Field Transformers?

Los **Field Transformers** permiten transformar automáticamente los valores de campos específicos al generar tags dinámicos. Son especialmente útiles para:

- ✅ Convertir IDs de imágenes en URLs completas
- ✅ Resolver referencias a endpoints relacionados
- ✅ Añadir prefijos/sufijos a valores
- ✅ Construir URLs personalizadas

---

## 🎯 Caso de Uso: Imágenes de Inventrip

### **Problema:**
La API de POIs devuelve IDs de imágenes como:
```json
{
  "image": ["image/55464", "image/55462", "image/55463"]
}
```

Pero necesitas URLs completas para mostrar las imágenes:
```
https://api.inventrip.com/v100/image/55464?api_key=XXX&image_quality=medium
```

### **Solución con Field Transformers:**

---

## 🛠️ Configuración

### **Opción 1: En functions.php del theme**

```php
<?php
// Transformador para imágenes de Inventrip
add_filter('bricks_api_field_transformers', function($transformers) {
    $transformers[] = [
        'field' => 'image',  // Campo a transformar
        'type' => 'related_endpoint',  // Tipo de transformación
        'endpoint_url' => 'https://api.inventrip.com/v100/image/{id_image}',
        'params' => [
            'api_key' => 'Tur!sm0V@ll-2025',
            'image_quality' => 'medium'  // Opcional
        ]
    ];

    return $transformers;
});
```

### **Opción 2: En wp-config.php**

```php
<?php
// Configurar transformadores globalmente
define('BRICKS_API_TRANSFORMERS', json_encode([
    [
        'field' => 'image',
        'type' => 'related_endpoint',
        'endpoint_url' => 'https://api.inventrip.com/v100/image/{id_image}',
        'params' => [
            'api_key' => 'Tur!sm0V@ll-2025',
            'image_quality' => 'medium'
        ]
    ]
]));
```

---

## ✨ Resultado

### **Antes (sin transformador):**
```
{snap_pois_image.0} → "image/55464"
{snap_pois_image.1} → "image/55462"
```

### **Después (con transformador):**
```
{snap_pois_image.0} → "https://api.inventrip.com/v100/image/55464?api_key=Tur!sm0V@ll-2025&image_quality=medium"
{snap_pois_image.1} → "https://api.inventrip.com/v100/image/55462?api_key=Tur!sm0V@ll-2025&image_quality=medium"
```

---

## 📋 Tipos de Transformadores

### **1. Related Endpoint**

Transforma IDs usando otro endpoint relacionado.

```php
[
    'field' => 'image',
    'type' => 'related_endpoint',
    'endpoint_url' => 'https://api.example.com/resource/{id}',
    'params' => [
        'api_key' => 'YOUR_KEY',
        'format' => 'json'
    ]
]
```

**Características:**
- Extrae automáticamente el ID de paths como `image/55464` → `55464`
- Reemplaza placeholders `{id}`, `{id_image}`, `{id_resource}`, etc.
- Añade parámetros de query automáticamente

---

### **2. URL Template**

Transforma valores usando un template simple.

```php
[
    'field' => 'thumbnail',
    'type' => 'url_template',
    'template' => 'https://cdn.example.com/{value}'
]
```

**Ejemplo:**
```
Valor original: "photo123.jpg"
Transformado: "https://cdn.example.com/photo123.jpg"
```

---

### **3. Prefix**

Añade un prefijo a los valores.

```php
[
    'field' => 'category',
    'type' => 'prefix',
    'prefix_value' => 'cat_'
]
```

**Ejemplo:**
```
Valor original: "electronics"
Transformado: "cat_electronics"
```

---

### **4. Suffix**

Añade un sufijo a los valores.

```php
[
    'field' => 'filename',
    'type' => 'suffix',
    'suffix_value' => '.webp'
]
```

**Ejemplo:**
```
Valor original: "photo123"
Transformado: "photo123.webp"
```

---

## 🔧 Configuración Avanzada

### **Múltiples Transformadores**

Puedes configurar transformadores para diferentes campos:

```php
add_filter('bricks_api_field_transformers', function($transformers) {
    // Transformador para imágenes
    $transformers[] = [
        'field' => 'image',
        'type' => 'related_endpoint',
        'endpoint_url' => 'https://api.inventrip.com/v100/image/{id_image}',
        'params' => ['api_key' => 'XXX']
    ];

    // Transformador para archivos PDF
    $transformers[] = [
        'field' => 'document',
        'type' => 'related_endpoint',
        'endpoint_url' => 'https://api.inventrip.com/v100/document/{id}',
        'params' => ['api_key' => 'XXX', 'format' => 'pdf']
    ];

    // Transformador para categorías
    $transformers[] = [
        'field' => 'category',
        'type' => 'prefix',
        'prefix_value' => 'category-'
    ];

    return $transformers;
});
```

---

## 🎨 Uso en Bricks Builder

Una vez configurados los transformadores, los tags se generarán automáticamente transformados:

### **En un Query Loop:**

```html
<!-- Mostrar imagen -->
<img src="{snap_pois_image.0}" alt="{snap_pois_name_es}">

<!-- Galería de imágenes -->
<div class="gallery">
  <img src="{snap_pois_image.0}">
  <img src="{snap_pois_image.1}">
  <img src="{snap_pois_image.2}">
</div>
```

### **Con elemento Image de Bricks:**

1. Añade un elemento **Image**
2. En **Dynamic Data** selecciona el tag `{snap_pois_image.0}`
3. La URL ya estará transformada automáticamente

---

## 🐛 Troubleshooting

### **Las URLs no se transforman**

**Causas posibles:**
1. El filtro no está añadido correctamente
2. El campo no coincide (sensible a mayúsculas/minúsculas)
3. Los tags no se han regenerado después de añadir el filtro

**Solución:**
1. Verifica que el código esté en `functions.php` o `wp-config.php`
2. Asegúrate de que el campo sea exactamente `'image'` (en minúsculas)
3. Elimina y regenera los tags del endpoint:
   - Ve a **API Integrator → Endpoints**
   - Edita el endpoint
   - Click en **"🗑️ Eliminar tags y query type"**
   - Click en **"⚡ Generar Query Type y Tags Dinámicos"**

### **El ID no se extrae correctamente**

Si tu path tiene un formato diferente a `image/12345`, puedes usar `url_template`:

```php
[
    'field' => 'image',
    'type' => 'url_template',
    'template' => 'https://api.example.com/v100/image/{value}?api_key=XXX'
]
```

---

## 📚 Ejemplos Reales

### **Ejemplo 1: Galería de Imágenes Inventrip**

```php
// functions.php
add_filter('bricks_api_field_transformers', function($transformers) {
    $transformers[] = [
        'field' => 'image',
        'type' => 'related_endpoint',
        'endpoint_url' => 'https://api.inventrip.com/v100/image/{id_image}',
        'params' => [
            'api_key' => 'Tur!sm0V@ll-2025',
            'image_quality' => 'high'  // Alta calidad para galería
        ]
    ];
    return $transformers;
});
```

**En Bricks:**
```html
<div class="poi-gallery">
  <img src="{snap_pois_image.0}" loading="lazy">
  <img src="{snap_pois_image.1}" loading="lazy">
  <img src="{snap_pois_image.2}" loading="lazy">
</div>
```

### **Ejemplo 2: CDN para Assets**

```php
add_filter('bricks_api_field_transformers', function($transformers) {
    $transformers[] = [
        'field' => 'asset',
        'type' => 'url_template',
        'template' => 'https://cdn.myproject.com/assets/{value}'
    ];
    return $transformers;
});
```

---

## ⚠️ Notas Importantes

1. **Los transformadores se aplican al generar los tags**, no al renderizar
2. **Debes regenerar los tags** después de añadir/modificar transformadores
3. **Los transformadores son globales**, afectan a todos los endpoints
4. **El campo debe coincidir exactamente** (case sensitive)
5. **Solo se transforma el valor de ejemplo**, no todos los registros de la API

---

## 🔜 Próximas Mejoras

- [ ] UI visual para configurar transformadores sin código
- [ ] Transformadores específicos por endpoint
- [ ] Cache de peticiones a endpoints relacionados
- [ ] Transformadores condicionales

---

**Elaborado por:** Claude
**Versión del documento:** 1.0
**Última actualización:** 2025-11-02
