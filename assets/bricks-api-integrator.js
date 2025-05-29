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

  // DESHABILITADO: Este event listener causa duplicación con el JavaScript inline
  // Función para añadir un nuevo endpoint
  /*
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
  */
  // FIN DEL CÓDIGO COMENTADO - Event listener duplicado deshabilitado

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
    
    // Asegurarnos de obtener el botón correcto incluso si se hace clic en un elemento hijo
    const button = e.target.closest('.eliminar-endpoint');
    if (!button) return;
    
    const index = parseInt(button.getAttribute('data-index'));
    
    // Buscar el contenedor del endpoint (.endpoint-card)
    const group = button.closest('.endpoint-card');
    
    console.log('removeEndpointHandler called, index:', index, 'group found:', group);
    
    if (confirm('¿Estás seguro de que quieres eliminar este endpoint?')) {
      // Verificar que el grupo existe antes de intentar eliminarlo
      if (group) {
        // Remover el elemento
        group.remove();
        
        // Reindexar todos los endpoints restantes
        reindexEndpoints();
      } else {
        // Si no se encuentra el grupo, intentar eliminar por AJAX de todos modos
        console.log('No se encontró el contenedor del endpoint, intentando eliminar por AJAX');
        removeEndpointByAjax(index);
      }
    }
  }
  
  // Función para eliminar endpoint por AJAX cuando no se encuentra el elemento DOM
  function removeEndpointByAjax(index) {
    // Mostrar indicador de carga
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-indicator';
    loadingDiv.innerHTML = 'Eliminando endpoint...';
    document.body.appendChild(loadingDiv);
    
    // Enviar solicitud AJAX para eliminar el endpoint
    const data = new FormData();
    data.append('action', 'remove_api_endpoint');
    data.append('index', index);
    data.append('nonce', bricksApiIntegrator.nonce);
    
    fetch(bricksApiIntegrator.ajaxUrl, {
      method: 'POST',
      body: data
    })
    .then(response => response.json())
    .then(data => {
      // Eliminar indicador de carga
      loadingDiv.remove();
      
      if (data.success) {
        // Recargar la página para mostrar los cambios
        window.location.reload();
      } else {
        alert('Error al eliminar el endpoint: ' + (data.message || 'Error desconocido'));
      }
    })
    .catch(error => {
      // Eliminar indicador de carga
      loadingDiv.remove();
      console.error('Error:', error);
      alert('Error al eliminar el endpoint. Consulta la consola para más detalles.');
    });
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
    
    // Realizar llamada AJAX real
    jQuery.ajax({
      url: bricks_api_integrator_vars.ajaxurl,
      type: 'POST',
      data: {
        action: 'test_api_endpoint',
        nonce: bricks_api_integrator_vars.nonce,
        index: index
      },
      success: function(response) {
        button.disabled = false;
        button.textContent = '🧪 Test API';
        
        if (response.success) {
          // Construir HTML para la respuesta exitosa
          let html = `
            <div style="color: green; background: #f0f8ff; padding: 10px; border-radius: 3px; margin-bottom: 15px;">
              <p><strong>✅ Conexión exitosa</strong></p>
              <p><strong>URL:</strong> ${response.data.test_url || response.data.base_url}</p>
              <p><strong>Estado:</strong> API lista para usar</p>
              ${response.data.total_items ? `<p><strong>Total de elementos:</strong> ${response.data.total_items}</p>` : ''}
            </div>
          `;
          
          // Mostrar los parámetros aplicados
          if (response.data.params_applied && Object.keys(response.data.params_applied).length > 0) {
            html += `<div style="margin-bottom: 15px;">
              <p><strong>Parámetros aplicados:</strong></p>
              <ul style="background: #f5f5f5; padding: 10px; border-radius: 3px; margin-top: 5px;">`;
              
            for (const [key, value] of Object.entries(response.data.params_applied)) {
              html += `<li><code>${key}</code>: <strong>${value}</strong></li>`;
            }
            
            html += `</ul>
            </div>`;
          }
          
          // Mostrar campos detectados si están disponibles
          if (response.data.sample_fields && response.data.sample_fields.length > 0) {
            html += `<p><strong>Campos detectados:</strong> ${response.data.sample_fields.join(', ')}</p>`;
          }
          
          // Añadir sección de datos de ejemplo
          if (response.data.sample_data) {
            html += `
              <details class="api-sample-details" style="margin-top: 15px; border: 1px solid #6c757d; border-radius: 5px; padding: 0; overflow: hidden;">
                <summary style="cursor: pointer; font-weight: bold; color: white; background: #6c757d; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                  <span>📋 Ver datos de ejemplo</span>
                  <span class="toggle-icon">▼</span>
                </summary>
                <div style="padding: 15px; border-top: 1px solid #6c757d;">
                  <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Muestra de los datos recibidos:</p>
                  <pre style="background: #f8f8f8; padding: 12px; border-radius: 3px; max-height: 200px; overflow: auto; font-size: 12px; margin: 0; border: 1px solid #dee2e6;">${JSON.stringify(response.data.sample_data, null, 2)}</pre>
                </div>
              </details>
            `;
          }
          
          // Añadir sección de respuesta completa
          if (response.data.full_response) {
            // Intentar formatear el JSON para una mejor visualización
            let formattedJson = response.data.full_response;
            
            // Si el JSON ya está formateado, usarlo directamente
            // Si no, intentar formatearlo nosotros
            try {
              // Verificar si es un string JSON o un objeto ya parseado
              if (typeof formattedJson === 'string') {
                const jsonObj = JSON.parse(formattedJson);
                formattedJson = JSON.stringify(jsonObj, null, 2);
              }
            } catch (e) {
              console.log('Error al formatear JSON:', e);
              // Si hay error, mantener el formato original
            }
            
            html += `
              <details class="api-response-details" style="margin-top: 15px; border: 1px solid #0073aa; border-radius: 5px; padding: 0; overflow: hidden;" open>
                <summary style="cursor: pointer; font-weight: bold; color: white; background: #0073aa; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                  <span>📊 Ver respuesta completa de la API</span>
                  <span class="toggle-icon">▲</span>
                </summary>
                <div style="padding: 15px; border-top: 1px solid #0073aa;">
                  <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Esta es la respuesta completa recibida de la API, útil para diagnóstico y depuración.</p>
                  <pre style="background: #f0f8ff; padding: 12px; border-radius: 3px; max-height: 800px; overflow: auto; font-size: 12px; margin: 0; border: 1px solid #cce5ff; white-space: pre-wrap;">${formattedJson}</pre>
                </div>
              </details>
            `;
          }
          
          // Añadir sección de estructura de datos
          if (response.data.response_structure) {
            html += `
              <details class="api-structure-details" style="margin-top: 15px; border: 1px solid #46b450; border-radius: 5px; padding: 0; overflow: hidden;">
                <summary style="cursor: pointer; font-weight: bold; color: white; background: #46b450; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                  <span>🔍 Ver estructura de datos</span>
                  <span class="toggle-icon">▼</span>
                </summary>
                <div style="padding: 15px; border-top: 1px solid #46b450;">
                  <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Análisis de la estructura de datos recibida:</p>
                  <pre style="background: #f6fff8; padding: 12px; border-radius: 3px; max-height: 300px; overflow: auto; font-size: 12px; margin: 0; border: 1px solid #c3e6cb;">${JSON.stringify(response.data.response_structure, null, 2)}</pre>
                </div>
              </details>
            `;
          }
          
          resultDiv.innerHTML = html;
          
          // Inicializar los desplegables
          initializeDetailsElements(resultDiv);
          
        } else {
          // Mostrar error
          let errorMessage = response.data && response.data.message ? response.data.message : 'Error desconocido';
          let errorUrl = response.data && response.data.test_url ? response.data.test_url : (response.data && response.data.base_url ? response.data.base_url : urlInput.value);
          
          // Construir HTML para mostrar información detallada del error
          let errorHtml = `
            <div style="color: red; background: #fff0f0; padding: 10px; border-radius: 3px; margin-bottom: 15px;">
              <p><strong>❌ Error en la API</strong></p>
              <p><strong>URL:</strong> ${errorUrl}</p>
              <p><strong>Error:</strong> ${errorMessage}</p>
            </div>
          `;
          
          // Añadir información sobre los encabezados enviados si están disponibles
          if (response.data && response.data.headers_sent) {
            const headersSent = response.data.headers_sent;
            errorHtml += `
              <details style="margin-top: 15px; border: 1px solid #dc3545; border-radius: 5px; padding: 0; overflow: hidden;">
                <summary style="cursor: pointer; font-weight: bold; color: white; background: #dc3545; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                  <span>🔍 Ver encabezados enviados</span>
                  <span class="toggle-icon">▼</span>
                </summary>
                <div style="padding: 15px; border-top: 1px solid #dc3545;">
                  <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Estos son los encabezados que se enviaron a la API:</p>
                  <pre style="background: #f8f8f8; padding: 12px; border-radius: 3px; max-height: 200px; overflow: auto; font-size: 12px; margin: 0; border: 1px solid #dee2e6;">${JSON.stringify(headersSent, null, 2)}</pre>
                </div>
              </details>
            `;
          }
          
          // Añadir cuerpo de la respuesta si está disponible
          if (response.data && response.data.response_body) {
            let responseBody = response.data.response_body;
            
            // Intentar formatear el JSON si es posible
            try {
              const jsonBody = JSON.parse(responseBody);
              responseBody = JSON.stringify(jsonBody, null, 2);
            } catch (e) {
              // Si no es JSON, mostrar como texto
            }
            
            errorHtml += `
              <details style="margin-top: 15px; border: 1px solid #dc3545; border-radius: 5px; padding: 0; overflow: hidden;">
                <summary style="cursor: pointer; font-weight: bold; color: white; background: #dc3545; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
                  <span>📄 Ver respuesta de error</span>
                  <span class="toggle-icon">▼</span>
                </summary>
                <div style="padding: 15px; border-top: 1px solid #dc3545;">
                  <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Esta es la respuesta de error recibida de la API:</p>
                  <pre style="background: #f8f8f8; padding: 12px; border-radius: 3px; max-height: 300px; overflow: auto; font-size: 12px; margin: 0; border: 1px solid #dee2e6;">${responseBody}</pre>
                </div>
              </details>
            `;
          }
          
          resultDiv.innerHTML = errorHtml;
          
          // Inicializar los desplegables
          initializeDetailsElements(resultDiv);
        }
      },
      error: function(xhr, status, error) {
        button.disabled = false;
        button.textContent = '🧪 Test API';
        
        resultDiv.innerHTML = `
          <div style="color: red; background: #fff0f0; padding: 10px; border-radius: 3px;">
            <p><strong>❌ Error de conexión con el servidor</strong></p>
            <p><strong>Error:</strong> ${error || 'Error desconocido'}</p>
          </div>
        `;
      }
    });
  }
  
  // Función para inicializar los elementos details
  function initializeDetailsElements(container) {
    const detailsElements = container.querySelectorAll('details');
    
    detailsElements.forEach(function(details) {
      const summary = details.querySelector('summary');
      const toggleIcon = summary.querySelector('.toggle-icon');
      
      summary.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (details.hasAttribute('open')) {
          details.removeAttribute('open');
          if (toggleIcon) toggleIcon.textContent = '▼';
        } else {
          details.setAttribute('open', 'open');
          if (toggleIcon) toggleIcon.textContent = '▲';
        }
      });
    });
    
    console.log('Desplegables inicializados:', detailsElements.length);
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
