# 🧪 Guía de Testing de Autenticación - Bricks API Integrator

## 📋 Resumen de Correcciones Aplicadas

### ✅ Problemas Resueltos

#### 1. **Función Unificada de Autenticación**
Se creó la función `prepare_auth_headers()` que centraliza toda la lógica de autenticación:

```php
private function prepare_auth_headers($endpoint_config = [])
```

**Beneficios:**
- ✅ Código DRY (Don't Repeat Yourself)
- ✅ Consistencia en todos los métodos
- ✅ Fácil mantenimiento y debugging
- ✅ Soporte unificado para todos los tipos de auth

#### 2. **Código Duplicado Eliminado**
- ❌ Antes: 3 bloques duplicados en `ajax_test_api_endpoint()`
- ✅ Ahora: 1 llamada a función unificada

#### 3. **Bug de Variable Corregido**
- ❌ Antes: `$endpoint_config` (variable inexistente)
- ✅ Ahora: `$endpoint` (variable correcta)

#### 4. **Funciones Refactorizadas**
Todas estas funciones ahora usan `prepare_auth_headers()`:
- ✅ `fetch_api_data_direct()`
- ✅ `get_auth_headers()` (deprecated, ahora es wrapper)
- ✅ `run_custom_query_dynamic()` (sección sources)
- ✅ `ajax_test_api_endpoint()`

---

## 🧪 PLAN DE TESTING MANUAL

### **Test 1: Sin Autenticación (None)**

#### Endpoint de Prueba
```
URL: https://jsonplaceholder.typicode.com/posts
Auth Type: None
```

#### Pasos
1. Ir a **API Integrator → API Endpoints**
2. Crear nuevo endpoint:
   - Nombre: `Test No Auth`
   - URL: `https://jsonplaceholder.typicode.com/posts`
   - Auth Type: `None`
3. Click en **"🧪 Test API"**

#### Resultado Esperado
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "count": 100,
  "sample_fields": ["userId", "id", "title", "body"]
}
```

#### Verificación
- ✅ Status 200
- ✅ Datos recibidos correctamente
- ✅ Headers básicos enviados (User-Agent, Accept, etc.)

---

### **Test 2: Bearer Token**

#### Endpoint de Prueba
```
URL: https://api.github.com/user
Auth Type: Bearer
Token: ghp_YOUR_GITHUB_TOKEN
```

#### Pasos
1. Obtener un token de GitHub: https://github.com/settings/tokens
2. Crear endpoint:
   - Nombre: `Test Bearer Auth`
   - URL: `https://api.github.com/user`
   - Auth Type: `Bearer`
   - Token: `tu_token_aqui`
3. Click en **"🧪 Test API"**

#### Resultado Esperado
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "sample_fields": ["login", "id", "avatar_url", "name", "email"]
}
```

#### Verificación
- ✅ Status 200
- ✅ Header `Authorization: Bearer TOKEN` enviado
- ✅ Datos del usuario recibidos

#### Error Esperado (Token Inválido)
```json
{
  "success": false,
  "message": "La API devolvió un código de estado no válido: 401",
  "auth_config": {
    "type": "bearer",
    "configured": true
  }
}
```

---

### **Test 3: API Key**

#### Endpoint de Prueba (ejemplo con API pública)
```
URL: https://api.openweathermap.org/data/2.5/weather?q=London
Auth Type: API Key
Key Name: appid
Key Value: YOUR_API_KEY
```

#### Pasos
1. Obtener API key gratuita de OpenWeather: https://openweathermap.org/api
2. Crear endpoint:
   - Nombre: `Test API Key Auth`
   - URL: `https://api.openweathermap.org/data/2.5/weather?q=London`
   - Auth Type: `API Key`
   - Key Name: `appid`
   - Key Value: `tu_api_key_aqui`
3. Click en **"🧪 Test API"**

#### Resultado Esperado
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "sample_fields": ["coord", "weather", "main", "wind", "name"]
}
```

#### Verificación
- ✅ Status 200
- ✅ Header `appid: YOUR_KEY` enviado
- ✅ Datos del clima recibidos

#### Error Esperado (API Key Inválida)
```json
{
  "success": false,
  "message": "La API devolvió un código de estado no válido: 401",
  "headers_sent": {
    "appid": "invalid_key"
  }
}
```

---

### **Test 4: Basic Authentication**

#### Endpoint de Prueba
```
URL: https://httpbin.org/basic-auth/user/pass
Auth Type: Basic Auth
Username: user
Password: pass
```

#### Pasos
1. Crear endpoint:
   - Nombre: `Test Basic Auth`
   - URL: `https://httpbin.org/basic-auth/user/pass`
   - Auth Type: `Basic Auth`
   - Username: `user`
   - Password: `pass`
2. Click en **"🧪 Test API"**

#### Resultado Esperado
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "sample_fields": ["authenticated", "user"]
}
```

#### Verificación
- ✅ Status 200
- ✅ Header `Authorization: Basic dXNlcjpwYXNz` enviado
- ✅ Respuesta: `{"authenticated": true, "user": "user"}`

#### Error Esperado (Credenciales Incorrectas)
```json
{
  "success": false,
  "message": "La API devolvió un código de estado no válido: 401"
}
```

---

## 🔍 TESTING DE SOURCES (Query Loop)

### **Test 5: Source con Bearer Auth**

#### Pasos
1. Crear endpoint con Bearer auth (Test 2)
2. Ir a **API Integrator → Sources**
3. Crear nuevo source:
   - Nombre: `GitHub Repos`
   - Endpoint: `Test Bearer Auth`
   - Items Path: (vacío si es objeto único)
4. Click en **"⚡ Crear tags y query types dinámicos"**

#### Verificación
- ✅ Query Type creado: `source_github_repos`
- ✅ Tags generados con prefijo `{snap_github_repos_*}`
- ✅ Datos visibles en Bricks Query Loop

---

### **Test 6: Source con API Key**

#### Pasos
1. Crear endpoint con API Key (Test 3)
2. Crear source asociado
3. Generar tags dinámicos

#### Verificación
- ✅ Autenticación funciona en Query Loop
- ✅ Tags renderiza datos correctamente
- ✅ Headers de API key se envían en cada petición

---

## 📊 MATRIZ DE COMPATIBILIDAD

| Función | None | Bearer | API Key | Basic | Estado |
|---------|------|--------|---------|-------|--------|
| `prepare_auth_headers()` | ✅ | ✅ | ✅ | ✅ | ✅ CORE |
| `fetch_api_data_direct()` | ✅ | ✅ | ✅ | ✅ | ✅ REFACTORIZADO |
| `get_auth_headers()` | ✅ | ✅ | ✅ | ✅ | ⚠️ DEPRECATED |
| `run_custom_query_dynamic()` | ✅ | ✅ | ✅ | ✅ | ✅ REFACTORIZADO |
| `ajax_test_api_endpoint()` | ✅ | ✅ | ✅ | ✅ | ✅ REFACTORIZADO |

---

## 🐛 TROUBLESHOOTING

### Problema: "Error de conexión"
**Causa:** URL incorrecta o servidor caído
**Solución:** Verificar URL en navegador primero

### Problema: "Status 401 Unauthorized"
**Causa:** Credenciales incorrectas o tipo de auth incorrecto
**Solución:**
1. Verificar tipo de auth seleccionado
2. Verificar credenciales (Bearer token, API key, username/password)
3. Activar WP_DEBUG y revisar logs: `wp-content/debug.log`

### Problema: "Status 403 Forbidden"
**Causa:** API key válida pero sin permisos
**Solución:** Verificar permisos de la API key en el servicio externo

### Problema: "No aparecen datos en Query Loop"
**Causa:** items_path incorrecto o autenticación fallida
**Solución:**
1. Hacer test del endpoint primero
2. Verificar estructura de respuesta en "Test API"
3. Ajustar items_path si es necesario
4. Regenerar tags dinámicos

---

## 📝 LOGS DE DEBUG

### Activar Debug
Editar `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Verificar Logs
```bash
tail -f /wp-content/debug.log | grep "AJAX TEST"
```

### Mensajes Esperados
```
AJAX TEST - URL: https://api.example.com/endpoint
AJAX TEST - Headers: Array ( [User-Agent] => ... [Authorization] => Bearer ... )
AJAX TEST ERROR - Status: 401 (solo si falla)
```

---

## ✅ CHECKLIST DE VALIDACIÓN

### Pre-Testing
- [ ] WP_DEBUG activado
- [ ] Plugin actualizado con últimos cambios
- [ ] Cache limpiado (si aplica)

### Testing por Tipo de Auth
- [ ] None - API pública sin auth funciona
- [ ] Bearer - GitHub API funciona con token
- [ ] API Key - OpenWeather funciona con appid
- [ ] Basic - httpbin funciona con user/pass

### Testing de Sources
- [ ] Source con endpoint auth funciona
- [ ] Query Loop muestra datos
- [ ] Tags dinámicos renderizan correctamente

### Verificación Final
- [ ] No hay errores en debug.log
- [ ] No hay errores en browser console
- [ ] Todos los tipos de auth funcionan
- [ ] Sources integran correctamente con endpoints

---

## 🎯 CRITERIOS DE ÉXITO

✅ **100% de tests pasados**
- None, Bearer, API Key, Basic funcionan
- Sources usan autenticación correctamente
- Query Loops renderizan datos
- Tags dinámicos muestran información

✅ **Código limpio**
- Sin código duplicado
- Función unificada usada en todos lados
- Logs informativos cuando WP_DEBUG activo

✅ **Experiencia de usuario**
- Test de API da feedback claro
- Errores muestran información útil
- Headers enviados visibles en respuesta de error

---

## 📞 SOPORTE

Si encuentras problemas durante el testing:

1. **Revisar logs:** `wp-content/debug.log`
2. **Verificar headers:** Respuesta de error muestra `headers_sent`
3. **Probar en Postman:** Usar misma URL y headers
4. **Documentar:** Guardar respuesta completa del test

---

**Versión:** 0.1-beta (Post-Refactoring)
**Fecha:** 2025-11-02
**Cambios:** Sistema unificado de autenticación implementado
