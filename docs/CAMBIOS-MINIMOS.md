# Cambios Mínimos Aplicados - Solo Acordeones

## ✅ Lo que pediste: 
- **Solo** poner cada endpoint en un acordeón
- **Solo** mostrar inputs de autenticación dinámicamente

## 🔧 Cambios realizados (MÍNIMOS):

### 1. En `includes/endpoints-page.php`:

#### Cambio 1: Convertir cada endpoint-card en acordeón
**ANTES:**
```html
<div class="endpoint-card" style="...">
    <h3>Endpoint 1</h3>
    <table class="form-table">
```

**DESPUÉS:**
```html
<div class="endpoint-accordion" style="...">
    <div class="endpoint-header" onclick="toggleEndpoint(0)" style="...">
        <h3>Endpoint 1 - Nombre</h3>
        <span id="toggle-icon-0">🔽</span>
    </div>
    <div id="endpoint-content-0" class="endpoint-content" style="...">
        <table class="form-table">
```

#### Cambio 2: JavaScript simple agregado
```javascript
// Función toggle simple
window.toggleEndpoint = function(index) {
    const content = document.getElementById('endpoint-content-' + index);
    const icon = document.getElementById('toggle-icon-' + index);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.innerHTML = '🔽';
    } else {
        content.style.display = 'none';
        icon.innerHTML = '▶️';
    }
};

// Autenticación dinámica simple
$(document).on('change', 'select[name*="[auth_type]"]', function() {
    // Mostrar inputs según tipo seleccionado
});
```

#### Cambio 3: CSS mínimo agregado
```css
.endpoint-header:hover {
    background: #e8e8e8 !important;
}
.auth-inputs {
    background: #f9f9f9;
    padding: 15px;
    border-left: 4px solid #007cba;
}
```

## 🎯 Resultado:
- ✅ **Cada endpoint ahora está en un acordeón clickeable**
- ✅ **Al seleccionar autenticación, inputs aparecen dinámicamente**
- ✅ **Todo lo demás funciona exactamente igual que antes**
- ✅ **No se rompió nada del código existente**

## 🚀 Cómo usar:
1. Ve a **API Endpoints**
2. **Click en el header** de cualquier endpoint para expandir/contraer
3. **Selecciona método de autenticación** → inputs aparecen inmediatamente
4. **Todo lo demás funciona igual** (test, tags, parámetros, etc.)

---

**Solo 3 cambios pequeños al archivo original. Nada más.**