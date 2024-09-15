document.addEventListener("DOMContentLoaded", function () {
  let endpointWrapper = document.getElementById("endpoints-wrapper");
  let addEndpointButton = document.getElementById("add-endpoint");

  // Function to add a new endpoint group
  addEndpointButton.addEventListener("click", function (e) {
    e.preventDefault();
    let index = endpointWrapper.children.length;
    let newGroup = document.createElement("div");
    newGroup.classList.add("endpoint-group");
    newGroup.setAttribute("data-index", index);
    newGroup.innerHTML = `
            <h4>Endpoint ${index + 1}</h4>
            <label>Nombre del Endpoint:</label>
            <input type="text" name="bricks_api_endpoints[${index}][name]" style="width: 100%;" placeholder="Nombre del Endpoint" />
            <label>URL del Endpoint:</label>
            <input type="url" name="bricks_api_endpoints[${index}][url]" style="width: 100%;" placeholder="URL del Endpoint" />
            <label>Autenticación:</label>
            <select name="bricks_api_endpoints[${index}][auth_type]" class="auth-type-select">
                <option value="none">Sin Autenticación</option>
                <option value="basic">Autenticación Básica</option>
                <option value="token">Token</option>
            </select>
            <div class="auth-fields"></div>
            <button type="button" class="remove-endpoint button">Eliminar Endpoint</button>
            <hr>
        `;
    endpointWrapper.appendChild(newGroup);
    assignRemoveEvents();
    handleAuthFields();
    handleAccordion();
  });

  function assignRemoveEvents() {
    let removeButtons = document.querySelectorAll(".remove-endpoint");
    removeButtons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        let group = button.closest(".endpoint-group");
        group.remove();
      });
    });
  }

  function handleAccordion() {
    const toggles = document.querySelectorAll(".accordion-toggle");
    toggles.forEach((toggle) => {
      toggle.addEventListener("click", function () {
        this.classList.toggle("active");
        let content = this.nextElementSibling;
        let icon = this.querySelector(".toggle-icon");
        if (content.style.display === "block") {
          content.style.display = "none";
          icon.classList.remove("active");
          icon.textContent = "+";
        } else {
          content.style.display = "block";
          icon.classList.add("active");
          icon.textContent = "-";
        }
      });
    });
  }

  function handleAuthFields() {
    document.querySelectorAll(".auth-type-select").forEach(function (select) {
      select.addEventListener("change", function () {
        const authType = this.value;
        const authFields =
          this.closest(".endpoint-group").querySelector(".auth-fields");
        authFields.innerHTML = ""; // Clear previous fields
        if (authType === "basic") {
          authFields.innerHTML = `
                        <label>Usuario:</label>
                        <input type="text" name="bricks_api_endpoints[${this.closest(".endpoint-group").getAttribute("data-index")}][basic_user]" style="width: 100%;" placeholder="Usuario" />
                        <label>Contraseña:</label>
                        <input type="password" name="bricks_api_endpoints[${this.closest(".endpoint-group").getAttribute("data-index")}][basic_password]" style="width: 100%;" placeholder="Contraseña" />
                    `;
        } else if (authType === "token") {
          authFields.innerHTML = `
                        <label>Token:</label>
                        <input type="text" name="bricks_api_endpoints[${this.closest(".endpoint-group").getAttribute("data-index")}][token]" style="width: 100%;" placeholder="Token" />
                    `;
        }
      });
    });
  }

  assignRemoveEvents();
  handleAuthFields();
  handleAccordion();
});
