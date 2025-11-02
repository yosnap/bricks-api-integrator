# 🧪 Instrucciones de Testing - Paso a Paso

## ✅ TEST 1: API Sin Autenticación

### **Configuración**
1. Ve a: **WordPress Admin → API Integrator → API Endpoints**
2. Click en **"Añadir Nuevo Endpoint"**
3. Configura:
   ```
   Nombre: Test No Auth
   URL: https://jsonplaceholder.typicode.com/posts
   Auth Type: None
   ```
4. Click en **"🧪 Test API"**

### **Resultado Esperado**
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "count": 100,
  "sample_fields": ["userId", "id", "title", "body"]
}
```

### **Verificar**
- [ ] Status 200
- [ ] Mensaje de éxito
- [ ] Datos recibidos (100 posts)
- [ ] Sin errores en consola

---

## 🔐 TEST 2: Bearer Token (GitHub API)

### **Preparación**
1. Ir a: https://github.com/settings/tokens
2. Click en **"Generate new token (classic)"**
3. Seleccionar scope: `read:user`
4. Copiar token generado

### **Configuración**
1. Ve a: **WordPress Admin → API Integrator → API Endpoints**
2. Click en **"Añadir Nuevo Endpoint"**
3. Configura:
   ```
   Nombre: Test Bearer Auth
   URL: https://api.github.com/user
   Auth Type: Bearer
   Token: [TU_TOKEN_AQUI]
   ```
4. Click en **"🧪 Test API"**

### **Resultado Esperado**
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "sample_fields": ["login", "id", "avatar_url", "name", "email"]
}
```

### **Verificar**
- [ ] Status 200
- [ ] Datos de tu usuario GitHub recibidos
- [ ] Header `Authorization: Bearer TOKEN` enviado
- [ ] Sin errores en debug.log

### **Error Esperado (si token inválido)**
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

## 🔑 TEST 3: API Key (OpenWeather)

### **Preparación**
1. Ir a: https://home.openweathermap.org/users/sign_up
2. Crear cuenta gratuita
3. Ir a: https://home.openweathermap.org/api_keys
4. Copiar API Key generada

### **Configuración**
1. Ve a: **WordPress Admin → API Integrator → API Endpoints**
2. Click en **"Añadir Nuevo Endpoint"**
3. Configura:
   ```
   Nombre: Test API Key Auth
   URL: https://api.openweathermap.org/data/2.5/weather?q=London
   Auth Type: API Key
   Key Name: appid
   Key Value: [TU_API_KEY_AQUI]
   ```
4. Click en **"🧪 Test API"**

### **Resultado Esperado**
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "sample_fields": ["coord", "weather", "main", "wind", "name"]
}
```

### **Verificar**
- [ ] Status 200
- [ ] Datos del clima de Londres recibidos
- [ ] Header `appid: YOUR_KEY` enviado
- [ ] Sin errores

### **Nota Importante**
⚠️ Las API keys de OpenWeather pueden tardar 10-30 minutos en activarse después de crearlas.

---

## 👤 TEST 4: Basic Authentication (httpbin)

### **Configuración**
1. Ve a: **WordPress Admin → API Integrator → API Endpoints**
2. Click en **"Añadir Nuevo Endpoint"**
3. Configura:
   ```
   Nombre: Test Basic Auth
   URL: https://httpbin.org/basic-auth/user/pass
   Auth Type: Basic Auth
   Username: user
   Password: pass
   ```
4. Click en **"🧪 Test API"**

### **Resultado Esperado**
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "sample_fields": ["authenticated", "user"],
  "sample_data": {
    "authenticated": true,
    "user": "user"
  }
}
```

### **Verificar**
- [ ] Status 200
- [ ] `authenticated: true` en respuesta
- [ ] Header `Authorization: Basic dXNlcjpwYXNz` enviado
- [ ] Sin errores

### **Error Esperado (si credenciales incorrectas)**
```json
{
  "success": false,
  "message": "La API devolvió un código de estado no válido: 401"
}
```

---

## 🔄 TEST 5: Source con Autenticación

### **Configuración**
1. Primero crea un endpoint con auth (ej: Test Bearer Auth de GitHub)
2. Ve a: **WordPress Admin → API Integrator → Sources**
3. Click en **"Crear Nuevo Source"**
4. Configura:
   ```
   Nombre: GitHub Repos
   Endpoint: Test Bearer Auth
   Items Path: [dejar vacío]
   ```
5. Click en **"⚡ Crear tags y query types dinámicos"**

### **Resultado Esperado**
- ✅ Query Type creado: `source_github_repos`
- ✅ Tags generados: `{snap_github_repos_login}`, etc.
- ✅ Source visible en lista

### **Verificar en Bricks**
1. Editar una página en Bricks
2. Añadir elemento **Query Loop**
3. Seleccionar tipo: **"GitHub Repos (Source)"**
4. Verificar que se muestran datos

---

## 📊 CHECKLIST FINAL

### **Funcionalidad Básica**
- [ ] Test 1: None - Funciona ✅
- [ ] Test 2: Bearer - Funciona ✅
- [ ] Test 3: API Key - Funciona ✅
- [ ] Test 4: Basic - Funciona ✅

### **Integración con Sources**
- [ ] Source con auth creado
- [ ] Query Type visible en Bricks
- [ ] Tags dinámicos funcionan
- [ ] Datos se muestran en Query Loop

### **Debugging**
- [ ] WP_DEBUG activado
- [ ] Logs en debug.log son claros
- [ ] No hay errores PHP
- [ ] Headers correctos enviados

---

## 🐛 TROUBLESHOOTING

### **Problema: "Error de conexión"**
**Solución:**
1. Verificar URL en navegador
2. Revisar firewall/proxy
3. Comprobar que el servidor puede hacer peticiones HTTP externas

### **Problema: "Status 401"**
**Solución:**
1. Verificar credenciales (Bearer token, API key, user/pass)
2. Revisar que el tipo de auth es correcto
3. Activar WP_DEBUG y revisar headers enviados

### **Problema: "No aparecen datos en Query Loop"**
**Solución:**
1. Hacer test del endpoint primero
2. Verificar que auth funciona
3. Regenerar tags dinámicos
4. Verificar items_path si es Source

### **Ver Logs**
```bash
# En tu terminal
tail -f wp-content/debug.log | grep "AJAX TEST"
```

O desde el admin de WordPress:
1. Plugins → File Manager
2. Ir a `wp-content/`
3. Ver archivo `debug.log`

---

## ✅ CRITERIOS DE ÉXITO

**El testing es exitoso cuando:**

1. ✅ Todos los 4 tipos de auth funcionan
2. ✅ Sources integran correctamente con endpoints
3. ✅ Query Loops muestran datos en Bricks
4. ✅ No hay errores en debug.log
5. ✅ Headers se envían correctamente

---

**Tiempo estimado:** 30-40 minutos
**Dificultad:** Media

**Siguiente paso:** Una vez completado, revisar TODO.md para planificar próximas mejoras.
