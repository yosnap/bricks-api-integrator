# 📚 Documentación - Bricks API Integrator

Bienvenido a la documentación completa del plugin **Bricks API Integrator**.

---

## 📂 Estructura de Documentación

### 🔧 **Refactoring y Arquitectura**
Documentación técnica sobre cambios estructurales del código:

- [**REFACTORING-AUTH-SYSTEM.md**](REFACTORING-AUTH-SYSTEM.md) - Refactoring completo del sistema de autenticación
- [**RESUMEN-REFACTORING-AUTH.md**](RESUMEN-REFACTORING-AUTH.md) - Resumen ejecutivo del refactoring

---

### 📋 **Release Notes**
Notas de versión detalladas por release:

- [**RELEASE-NOTES-v2.1.4.md**](release-notes/RELEASE-NOTES-v2.1.4.md) - v2.1.4: Corrección formularios duplicados
- [**RELEASE-NOTES-v2.1.2.md**](release-notes/RELEASE-NOTES-v2.1.2.md) - v2.1.2: Dynamic Tags restaurados
- [**RELEASE-NOTES-v2.1.1.md**](release-notes/RELEASE-NOTES-v2.1.1.md) - v2.1.1: Sistema AUTO/MANUAL
- [**RELEASE-NOTES-v2.1.md**](release-notes/RELEASE-NOTES-v2.1.md) - v2.1: Acordeones y auth dinámica

---

### 🔨 **Correcciones**
Documentación sobre fixes y soluciones implementadas:

- [**CORRECCION-FINAL-COMPLETADA.md**](correcciones/CORRECCION-FINAL-COMPLETADA.md) - Correcciones finales completadas
- [**CORRECCION-QUERY-LOOP-SOURCES.md**](correcciones/CORRECCION-QUERY-LOOP-SOURCES.md) - Fix Query Loop con Sources
- [**CORRECCION_FINAL_ARRAYS.md**](correcciones/CORRECCION_FINAL_ARRAYS.md) - Corrección manejo de arrays
- [**SOLUCION-COMPLETA.md**](correcciones/SOLUCION-COMPLETA.md) - Solución completa general
- [**SOLUCION-DUPLICACION-ACORDEONES.md**](correcciones/SOLUCION-DUPLICACION-ACORDEONES.md) - Fix acordeones duplicados
- [**SOLUCION-ENDPOINTS-ACORDEON.md**](correcciones/SOLUCION-ENDPOINTS-ACORDEON.md) - Solución endpoints acordeón

---

### 📖 **Guías de Uso**
Manuales y guías paso a paso:

- [**GUIA-TESTING-SOURCES.md**](guias/GUIA-TESTING-SOURCES.md) - Guía de testing de Sources
- [**CHECKLIST_IMPLEMENTACION.md**](guias/CHECKLIST_IMPLEMENTACION.md) - Checklist de implementación

---

### 🔍 **Diagnósticos**
Análisis de problemas y debugging:

- [**DIAGNOSTICO_VEHICULOS_MOTOR.md**](diagnosticos/DIAGNOSTICO_VEHICULOS_MOTOR.md) - Diagnóstico caso vehículos
- [**DIAGNOSTIC_AND_FIXES.md**](diagnosticos/DIAGNOSTIC_AND_FIXES.md) - Diagnósticos y fixes generales

---

### 🧪 **Testing**
Documentación de pruebas y validación:

- [**auth-testing-guide.md**](testing/auth-testing-guide.md) - Guía completa de testing de autenticación
- [**PRUEBA_BOTONES.md**](testing/PRUEBA_BOTONES.md) - Pruebas de botones

---

### 📊 **Resúmenes Ejecutivos**
Documentos de alto nivel sobre el estado del proyecto:

- [**RESUMEN-EJECUTIVO.md**](RESUMEN-EJECUTIVO.md) - Resumen ejecutivo general
- [**RESUMEN-FINAL-REVISION.md**](RESUMEN-FINAL-REVISION.md) - Resumen revisión final
- [**RESUMEN-FINAL-v2.1.1.md**](RESUMEN-FINAL-v2.1.1.md) - Resumen final v2.1.1
- [**RESUMEN_EJECUTIVO_SOLUCION.md**](RESUMEN_EJECUTIVO_SOLUCION.md) - Resumen solución ejecutiva
- [**CAMBIOS-MINIMOS.md**](CAMBIOS-MINIMOS.md) - Cambios mínimos aplicados

---

## 🚀 **Documentación por Casos de Uso**

### **Quiero empezar a usar el plugin**
1. Leer: [README principal](../README.md)
2. Seguir: [CHECKLIST_IMPLEMENTACION.md](guias/CHECKLIST_IMPLEMENTACION.md)

### **Quiero configurar autenticación**
1. Leer: [auth-testing-guide.md](testing/auth-testing-guide.md)
2. Referencia: [REFACTORING-AUTH-SYSTEM.md](REFACTORING-AUTH-SYSTEM.md)

### **Tengo problemas con Sources**
1. Leer: [GUIA-TESTING-SOURCES.md](guias/GUIA-TESTING-SOURCES.md)
2. Diagnosticar: [DIAGNOSTIC_AND_FIXES.md](diagnosticos/DIAGNOSTIC_AND_FIXES.md)

### **Quiero entender los cambios recientes**
1. Ver: [CHANGELOG](../CHANGELOG.md)
2. Detalles: [Release Notes](release-notes/)

---

## 📝 **Convenciones de Documentación**

### **Tipos de Documentos**

| Tipo | Prefijo | Propósito |
|------|---------|-----------|
| **Guías** | `GUIA-` | Tutoriales paso a paso |
| **Correcciones** | `CORRECCION-` / `SOLUCION-` | Fixes implementados |
| **Diagnósticos** | `DIAGNOSTICO-` / `DIAGNOSTIC-` | Análisis de problemas |
| **Resúmenes** | `RESUMEN-` | Documentos ejecutivos |
| **Release Notes** | `RELEASE-NOTES-` | Notas de versión |

### **Estados de Documentación**

- ✅ **Actualizado** - Información vigente
- ⚠️ **Legacy** - Información histórica pero válida
- 🗂️ **Archivo** - Solo para referencia histórica

---

## 🔄 **Historial de Versiones Documentadas**

### **v0.2-beta** (2025-11-02) - Actual
- Refactoring sistema de autenticación
- Nueva documentación de testing

### **v0.1-beta** (2024-12-27)
- Release inicial beta
- Documentación base

### **v2.1.x** (2025-05-28)
- Sistema AUTO/MANUAL
- Correcciones UI

---

## 📞 **Soporte**

¿No encuentras lo que buscas?

1. **Buscar en docs:** Usa Ctrl+F en el navegador de archivos
2. **Ver CHANGELOG:** [../CHANGELOG.md](../CHANGELOG.md)
3. **Ver README:** [../README.md](../README.md)
4. **Ver TODO:** [../TODO.md](../TODO.md)

---

## 🎯 **Mapa Rápido**

```
docs/
├── README.md (este archivo)
├── REFACTORING-AUTH-SYSTEM.md
├── RESUMEN-REFACTORING-AUTH.md
├── release-notes/
│   ├── RELEASE-NOTES-v2.1.4.md
│   ├── RELEASE-NOTES-v2.1.2.md
│   ├── RELEASE-NOTES-v2.1.1.md
│   └── RELEASE-NOTES-v2.1.md
├── correcciones/
│   ├── CORRECCION-FINAL-COMPLETADA.md
│   ├── CORRECCION-QUERY-LOOP-SOURCES.md
│   ├── CORRECCION_FINAL_ARRAYS.md
│   ├── SOLUCION-COMPLETA.md
│   ├── SOLUCION-DUPLICACION-ACORDEONES.md
│   └── SOLUCION-ENDPOINTS-ACORDEON.md
├── guias/
│   ├── GUIA-TESTING-SOURCES.md
│   └── CHECKLIST_IMPLEMENTACION.md
├── diagnosticos/
│   ├── DIAGNOSTICO_VEHICULOS_MOTOR.md
│   └── DIAGNOSTIC_AND_FIXES.md
├── testing/
│   ├── auth-testing-guide.md
│   └── PRUEBA_BOTONES.md
└── resúmenes ejecutivos (nivel raíz)
```

---

**Última actualización:** 2025-11-02
**Versión del plugin:** 0.2-beta
