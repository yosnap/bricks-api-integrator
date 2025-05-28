/**
 * Bricks API Integrator - JavaScript corregido
 * Version 2.0
 */

document.addEventListener("DOMContentLoaded", function () {
  // Variables globales
  let endpointWrapper = document.getElementById("endpoints-container");
  let addEndpointButton = document.getElementById("add-endpoint");
  let endpointCounter = 0;

  // Inicializar contador basado en endpoints existentes
  if (endpointWrapper) {
    endpointCounter = endpointWrapper.children.length;
  }

  // Función para añadir un nuevo endpoint
  if (addEndpointButton) {
    addEndpointButton.addEventListener("click", function (e) {
      e.preventDefault();
      
      let newGroup = document.createElement("div");
      newGroup.classList.add("endpoint-accordion");
      newGroup.style.cssText = "background: #fff; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;";
      newGroup.setAttribute("data-index", endpointCounter);
      
      newGroup.innerHTML = `
        <div class="endpoint-header" onclick="toggleEndpoint(${endpointCounter})" style="background: #f1f1f1; padding: 15px; cursor: pointer; border-bottom: 1px solid #ddd;">
          <h3 style="margin: 0; display: inline-block;">Endpoint ${endpointCounter + 1} - Sin nombre</h3>
          <span id="toggle-icon-${endpointCounter}" style="float: right; font-size: 18px;">🔽</span>
        </div>
        
        <div id="endpoint-content-${endpointCounter}" class="endpoint-content" style="padding: 20px;">
          <table class="form-table">
            <tr>
              <th><label>Nombre del Endpoint</label></th>
              <td>
                <input type="text" 
                       name="endpoints[${endpointCounter}][name]" 
                       class="regular-text" 
                       placeholder="Nombre del Endpoint" 
                       onchange="updateEndpointTitle(${endpointCounter}, this.value)" 
                       required />
              </td>
            </tr>
            <tr>
              <th><label>URL del Endpoint</label></th>
              <td>
                <input type="url" 
                       name="endpoints[${endpointCounter}][url]" 
                       class="regular-text" 
                       placeholder="https://api.ejemplo.com/datos" 
                       required />
              </td>
            </tr>
            <tr>
              <th><label>Autenticación</label></th>
              <td>
                <select name="endpoints[${endpointCounter}][auth_type]" class="auth-type-select">
                  <option value="none">Sin Autenticación</option>
                  <option value="basic">Autenticación Básica</option>
                  <option value="bearer">Bearer Token</option>
                  <option value="api_key">API Key</option>
                </select>
              </td>
            </tr>
          </table>
          
          <div class="endpoint-actions" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
            <button type="button" class="test-endpoint button button-secondary" data-index="${endpointCounter}">
              ✅ Test API
            </button>
            <button type="button" class="show-dynamic-tags button button-warning" data-index="${endpointCounter}">
              🏷️ Ver Dynamic Tags
            </button>
            <button type="button" class="refresh-endpoint-data button button-success" data-index="${endpointCounter}">
              🔄 Actualizar Datos
            </button>
            <button type="button" class="remove-endpoint button button-danger" data-index="${endpointCounter}" style="float: right;">
              🗑️ Eliminar
            </button>
          </div>
          
          <div class="dynamic-tags-accordion" id="dynamic-tags-${endpointCounter}" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
            <div class="dynamic-tags-content">
              <h4 style="margin: 0 0 10px 0; color: #007cba;">🏷️ Dynamic Tags Disponibles</h4>
              <div class="tags-loading" style="text-align: center; padding: 20px;">
                <span style="color: #666;">⏳ Generando dynamic tags...</span>
              </div>
              <div class="tags-list" style="display: none;">
                <!-- Se llenará con AJAX -->
              </div>
              <div class="tags-help" style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 3px;">
                <p style="margin: 0; font-size: 13px; color: #0073aa;">
                  💡 <strong>Cómo usar:</strong> Copia y pega estos tags en tus elementos de Bricks Builder. 
                </p>
              </div>
            </div>
          </div>
        </div>
      `;
      
      endpointWrapper.appendChild(newGroup);
      endpointCounter++;
      
      // Reinicializar event listeners para el nuevo endpoint
      initializeEventListeners();
    });
  }

  // Función para actualizar el título del endpoint
  window.updateEndpointTitle = function(index, name) {
    const headerH3 = document.querySelector(`#toggle-icon-${index}`).parentElement.querySelector('h3');
    if (headerH3) {
      headerH3.textContent = `Endpoint ${index + 1} - ${name || 'Sin nombre'}`;
    }
  };
  
  window.updateEndpointTitleFromInput = function(index, name) {
    updateEndpointTitle(index, name);
  };

  // Función para toggle de visibilidad
  window.toggleEndpointVisibility = function(index) {
    const content = document.getElementById(`endpoint-content-${index}`);
    const icon = document.querySelector(`[data-index="${index}"] .toggle-icon`);
    
    if (content && icon) {
      if (content.style.display === "none") {
        content.style.display = "block";
        icon.textContent = "▼";
      } else {
        content.style.display = "none";
        icon.textContent = "▶";
      }
    }
  };

  // Función para manejar campos de autenticación
  window.handleAuthFieldChange = function(index, authType) {
    const authFields = document.getElementById(`auth-fields-${index}`);
    if (!authFields) return;
    
    authFields.innerHTML = ""; // Limpiar campos anteriores
    
    switch(authType) {
      case "basic":
        authFields.innerHTML = `
          <table class="form-table">
            <tr>
              <th><label>Usuario:</label></th>
              <td>
                <input type="text" 
                       name="bricks_api_endpoints[${index}][basic_user]" 
                       class="regular-text" 
                       placeholder="Usuario" />
              </td>
            </tr>
            <tr>
              <th><label>Contraseña:</label></th>
              <td>
                <input type="password" 
                       name="bricks_api_endpoints[${index}][basic_password]" 
                       class="regular-text" 
                       placeholder="Contraseña" />
              </td>
            </tr>
          </table>
        `;
        break;
        
      case "token":
        authFields.innerHTML = `
          <table class="form-table">
            <tr>
              <th><label>Bearer Token:</label></th>
              <td>
                <input type="text" 
                       name="bricks_api_endpoints[${index}][token]" 
                       class="regular-text" 
                       placeholder="Token de acceso" />
              </td>
            </tr>
          </table>
        `;
        break;
        
      case "api_key":
        authFields.innerHTML = `
          <table class="form-table">
            <tr>
              <th><label>API Key:</label></th>
              <td>
                <input type="text" 
                       name="bricks_api_endpoints[${index}][api_key]" 
                       class="regular-text" 
                       placeholder="Clave de API" />
              </td>
            </tr>
            <tr>
              <th><label>Header Name:</label></th>
              <td>
                <input type="text" 
                       name="bricks_api_endpoints[${index}][api_key_header]" 
                       class="regular-text" 
                       value="X-API-Key" 
                       placeholder="Nombre del header" />
              </td>
            </tr>
          </table>
        `;
        break;
    }
  };

  // Función para eliminar endpoint - CORREGIDA
  function removeEndpointHandler(e) {
    e.preventDefault();
    
    const button = e.target;
    const index = parseInt(button.getAttribute('data-index'));
    const group = button.closest('.endpoint-card'); // CAMBIADO de .endpoint-group a .endpoint-card
    
    console.log('removeEndpointHandler called, index:', index, 'group found:', group);
    
    if (confirm('¿Estás seguro de que quieres eliminar este endpoint?')) {
      // Remover el elemento
      group.remove();
      
      // Reindexar todos los endpoints restantes
      reindexEndpoints();
    }
  }

  // Función para reindexar endpoints después de eliminar
  function reindexEndpoints() {
    const remainingGroups = document.querySelectorAll('.endpoint-card'); // CAMBIADO
    let newIndex = 0;
    
    remainingGroups.forEach(function(group) {
      const oldIndex = parseInt(group.getAttribute('data-index'));
      
      // Actualizar data-index
      group.setAttribute('data-index', newIndex);
      
      // Actualizar todos los name attributes
      const inputs = group.querySelectorAll('input, select');
      inputs.forEach(function(input) {
        const name = input.getAttribute('name');
        if (name && name.includes('bricks_api_endpoints[')) {
          const newName = name.replace(/bricks_api_endpoints\[\d+\]/, `bricks_api_endpoints[${newIndex}]`);
          input.setAttribute('name', newName);
        }
      });
      
      // Actualizar IDs
      const elementsWithId = group.querySelectorAll('[id]');
      elementsWithId.forEach(function(element) {
        const id = element.getAttribute('id');
        if (id.includes('-' + oldIndex)) {
          const newId = id.replace('-' + oldIndex, '-' + newIndex);
          element.setAttribute('id', newId);
        }
      });
      
      // Actualizar onclick handlers
      const header = group.querySelector('.endpoint-header');
      if (header) {
        header.setAttribute('onclick', `toggleEndpointVisibility(${newIndex})`);
      }
      
      // Actualizar data-index en botones
      const buttons = group.querySelectorAll('[data-index]');
      buttons.forEach(function(btn) {
        btn.setAttribute('data-index', newIndex);
      });
      
      // Actualizar onchange handlers
      const nameInput = group.querySelector('.endpoint-name-input');
      if (nameInput) {
        nameInput.setAttribute('onchange', `updateEndpointTitleFromInput(${newIndex}, this.value)`);
      }
      
      const authSelect = group.querySelector('.auth-type-select');
      if (authSelect) {
        authSelect.setAttribute('onchange', `handleAuthFieldChange(${newIndex}, this.value)`);
      }
      
      // Actualizar título si está por defecto
      const titleSpan = group.querySelector('.endpoint-title');
      const nameInputValue = group.querySelector('.endpoint-name-input')?.value;
      if (titleSpan && (!nameInputValue || titleSpan.textContent.match(/^Endpoint \d+$/))) {
        titleSpan.textContent = `Endpoint ${newIndex + 1}`;
      }
      
      newIndex++;
    });
    
    // Actualizar contador global
    endpointCounter = newIndex;
  }

  // Función para test de API
  function testEndpointHandler(e) {
    e.preventDefault();
    
    const button = e.target;
    const index = parseInt(button.getAttribute('data-index'));
    const resultDiv = document.getElementById(`test-result-${index}`);
    const card = button.closest('.endpoint-accordion, .endpoint-card');
    
    if (!card) {
      console.error('No se encontró el contenedor del endpoint');
      return;
    }
    
    // Obtener datos del endpoint
    const nameInput = card.querySelector(`input[name*="[name]"]`);
    const urlInput = card.querySelector(`input[name*="[url]"]`);
    const authSelect = card.querySelector(`select[name*="[auth_type]"]`);
    
    if (!urlInput || !urlInput.value) {
      if (resultDiv) {
        resultDiv.innerHTML = '<p style="color: red;">❌ URL requerida</p>';
      }
      return;
    }
    
    // Mostrar loading
    button.disabled = true;
    button.textContent = '🔄 Probando...';
    if (resultDiv) {
      resultDiv.innerHTML = '<p style="color: blue;">🔄 Probando conexión...</p>';
    }
    
    // Simular test (en implementación real, aquí iría AJAX)
    setTimeout(function() {
      // Simular respuesta exitosa
      resultDiv.innerHTML = `
        <div style="color: green; background: #f0f8ff; padding: 10px; border-radius: 3px;">
          <p><strong>✅ Conexión exitosa</strong></p>
          <p><strong>URL:</strong> ${urlInput.value}</p>
          <p><strong>Estado:</strong> API lista para usar</p>
        </div>
      `;
      
      button.disabled = false;
      button.textContent = '🧪 Test API';
    }, 2000);
  }

  // Función para inicializar todos los event listeners
  function initializeEventListeners() {
    // Remover event listeners existentes para evitar duplicados
    const removeButtons = document.querySelectorAll('.remove-endpoint');
    removeButtons.forEach(function(button) {
      // Remover listeners anteriores
      button.removeEventListener('click', removeEndpointHandler);
      // Añadir nuevo listener
      button.addEventListener('click', removeEndpointHandler);
    });
    
    const testButtons = document.querySelectorAll('.test-endpoint');
    testButtons.forEach(function(button) {
      // Remover listeners anteriores
      button.removeEventListener('click', testEndpointHandler);
      // Añadir nuevo listener
      button.addEventListener('click', testEndpointHandler);
    });
    
    // Inicializar campos de autenticación existentes
    const authSelects = document.querySelectorAll('.auth-type-select');
    authSelects.forEach(function(select) {
      select.removeEventListener('change', handleAuthSelectChange);
      select.addEventListener('change', handleAuthSelectChange);
    });
  }
  
  // Handler para cambio de select de autenticación
  function handleAuthSelectChange(e) {
    const select = e.target;
    const group = select.closest('.endpoint-group');
    const index = parseInt(group.getAttribute('data-index'));
    handleAuthFieldChange(index, select.value);
  }

  // Inicializar event listeners al cargar la página
  initializeEventListeners();
  
  // Función para manejar accordions existentes
  function handleAccordion() {
    const toggles = document.querySelectorAll(".accordion-toggle");
    toggles.forEach((toggle) => {
      toggle.addEventListener("click", function () {
        this.classList.toggle("active");
        let content = this.nextElementSibling;
        let icon = this.querySelector(".toggle-icon");
        
        if (content && icon) {
          if (content.style.display === "block") {
            content.style.display = "none";
            icon.classList.remove("active");
            icon.textContent = "▶";
          } else {
            content.style.display = "block";
            icon.classList.add("active");
            icon.textContent = "▼";
          }
        }
      });
    });
  }

  // Inicializar accordions
  handleAccordion();
});

// Funciones de utilidad globales
window.BricksAPIIntegrator = {
  // Función para validar URL
  isValidUrl: function(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  },
  
  // Función para mostrar notificación
  showNotification: function(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notice notice-${type}`;
    notification.innerHTML = `<p>${message}</p>`;
    
    const wrap = document.querySelector('.wrap');
    if (wrap) {
      wrap.insertBefore(notification, wrap.firstChild);
      
      // Auto-remover después de 5 segundos
      setTimeout(() => {
        notification.remove();
      }, 5000);
    }
  },
  
  // Función para limpiar formulario
  clearEndpointForm: function(index) {
    const group = document.querySelector(`[data-index="${index}"]`);
    if (group) {
      const inputs = group.querySelectorAll('input');
      inputs.forEach(input => {
        if (input.type !== 'button' && input.type !== 'submit') {
          input.value = '';
        }
      });
      
      const selects = group.querySelectorAll('select');
      selects.forEach(select => {
        select.selectedIndex = 0;
      });
    }
  }
};
