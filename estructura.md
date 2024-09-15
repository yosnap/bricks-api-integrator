Estructura del plugin:

/bricks-api-integrator
|-- /includes
| |-- settings.php
| |-- endpoints.php
| |-- render.php
|
|-- /assets
| |-- /css
| |-- styles.css
| |-- /js
| |-- scripts.js
|
|-- bricks-api-integrator.php

Notas de peticiones API:

1. Esta será la primera versión del plugin. Ahora arrancamos con la versión 1.1 en la que vamos a agregar las variables creadas en a partir de la conexión a los endpoints para usarlas como tags dinámicos en bricks. Para ello crearemos un nuevo archivo tomando en cuenta este código funcionando el cual trae los datos de un endpoint predeterminado, pero la idea es que sirva para cada endpoint configurado en el dashboard. Para ello, crearemos los Grupos de los tags, usando los nombres de cada conexión:
