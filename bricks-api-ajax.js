jQuery(document).ready(function ($) {
  // Detectar cambios en el campo de URL del endpoint
  $('input[name="bricks_api_endpoint"]').on("change", function () {
    var endpointUrl = $(this).val();

    // Enviar la solicitud AJAX
    $.ajax({
      url: bricksApi.ajaxUrl,
      method: "POST",
      data: {
        action: "update_payload",
        endpoint_url: endpointUrl,
        _ajax_nonce: bricksApi.nonce, // Enviar el nonce para seguridad
      },
      success: function (response) {
        if (response.success) {
          // Limpiar la tabla
          $("tbody").empty();

          // Recorrer y agregar filas a la tabla con los nuevos datos
          var data = response.data;
          $.each(data, function (key, value) {
            var row =
              "<tr><td>" +
              key +
              "</td><td>" +
              JSON.stringify(value) +
              "</td></tr>";
            $("tbody").append(row);
          });
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Error en la solicitud AJAX");
      },
    });
  });
});
