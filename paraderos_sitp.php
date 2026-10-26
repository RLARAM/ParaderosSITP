```php
<?php
// ==================================================
// 1. CONFIGURACION INICIAL
// ==================================================


// Lista de localidades y sus nombres para la consulta.
$localidades = [
    'Chapinero' => 'Chapinero',
    'Suba' => 'Suba',
    'Kennedy' => 'Kennedy',
    'Engativá' => 'Engativá',
    'Usaquén' => 'Usaquén'
];


// Variables iniciales.
$localidad = '';
$paraderos = [];
$error = '';
$consultaRealizada = false;


// ==================================================
// 2. VALIDACION DEL FORMULARIO
// ==================================================

if (isset($_GET['localidad'])) {

    $localidad = trim($_GET['localidad']);

    // Comprobamos que la localidad exista en nuestra lista.
    if (!array_key_exists($localidad, $localidades)) {

        $error = 'La localidad seleccionada no es valida.';

    } else {

        $consultaRealizada = true;

        // ==================================================
        // 3. CONSUMO DE LA API REST
        // ==================================================

     // Servicio oficial de mobiliario urbano de Bogotá.
         $endpoint = 'https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8/query';        
     
     
      // Construimos los parámetros para consultar
      // los paraderos de la localidad seleccionada.
  
      // Construimos los parámetros para consultar
      // los paraderos de la localidad seleccionada.
      $parametros = [
          'where' => "LOCALIDAD='" . $localidades[$localidad] . "'",
          'outFields' => 'NOMBRE,DIRECCION_,LOCALIDAD,LATITUD,LONGITUD',
          'returnGeometry' => 'false',
          'resultRecordCount' => 100,
          'f' => 'json'
      ];

      $url = $endpoint . '?' . http_build_query($parametros);


        // Configuramos la solicitud HTTP y el tiempo de espera.
        $contexto = stream_context_create([
            'http' => [
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "User-Agent: ConsultaSITP/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);

        // Solicitamos los datos a la API.
        $respuesta = @file_get_contents(
            $url,
            false,
            $contexto
        );

        // ==================================================
        // 4. DECODIFICACION DE LA RESPUESTA JSON
        // ==================================================

        if ($respuesta === false) {

            $error = 'No fue posible conectar con el servicio.
                      Intenta nuevamente mas tarde.';

        } else {

            // Convertimos el JSON en un objeto PHP.
            $datos = json_decode($respuesta);

            // Verificamos que la respuesta sea valida.
            if (
                json_last_error() !== JSON_ERROR_NONE ||
                !is_object($datos)
            ) {

                $error = 'El servicio devolvio una respuesta no valida.';

            } elseif (isset($datos->error)) {

                // La API puede responder con un mensaje de error.
                $error = 'El servicio no pudo completar la consulta.';

            } elseif (
                !isset($datos->features) ||
                !is_array($datos->features)
            ) {

                $error = 'La respuesta no contiene una lista de paraderos.';

            } else {

                // ==================================================
                // 5. RECORRIDO DE LOS RESULTADOS
                // ==================================================


                // Recorremos los paraderos devueltos por la API.
                foreach ($datos->features as $elemento) {

                    // La respuesta de esta API guarda los datos en attributes.
                    $propiedades = $elemento->attributes ?? null;

                    // Verificamos que existan los atributos.
                    if (!is_object($propiedades)) {
                        continue;
                    }

                    // Comparamos el nombre de la localidad seleccionada.
                    if (
                        !isset($propiedades->LOCALIDAD) ||
                        strcasecmp(
                            trim($propiedades->LOCALIDAD),
                            trim($localidades[$localidad])
                        ) !== 0
                    ) {
                        continue;
                    }

                     // Guardamos la información del paradero.
                    $paraderos[] = [
                        'nombre' => $propiedades->NOMBRE
                            ?? $propiedades->CENEFA
                            ?? 'Sin nombre',

                        'direccion' => $propiedades->DIRECCION_
                            ?? 'No disponible',

                        'latitud' => $propiedades->LATITUD ?? null,

                        'longitud' => $propiedades->LONGITUD ?? null
                    ];
                }

                // ==================================================
                // 6. VERIFICACION DE RESULTADOS
                // ==================================================

                if (empty($paraderos)) {
                    $error = 'No se encontraron paraderos para esta localidad.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consulta de Paraderos SITP</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: #006b54;
            color: white;
            text-align: center;
            padding: 25px;
        }

        .contenedor {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select,
        button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 16px;
        }

        select {
            border: 1px solid #ccc;
        }

        button {
            background-color: #006b54;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #004d3c;
        }

        .mensaje {
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            background-color: #fff3cd;
            color: #664d03;
        }

        .exito {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .tabla-contenedor {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #006b54;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        footer {
            text-align: center;
            padding: 15px;
            color: #555;
        }
    </style>
</head>

<body>

    <header>
        <h1>Consulta de Paraderos SITP</h1>
        <p>Consulta los paraderos por localidad en Bogota</p>
    </header>

    <main class="contenedor">

        <h2>Buscar paraderos</h2>

        <!-- Formulario para seleccionar la localidad. -->
        <form method="GET" action="paraderos_sitp.php">

            <label for="localidad">Selecciona una localidad:</label>

            <select name="localidad" id="localidad" required>

                <option value="">-- Selecciona una localidad --</option>

                <?php foreach ($localidades as $nombre => $codigo): ?>

                    <option
                        value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $localidad === $nombre ? 'selected' : '' ?>>

                        <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">Consultar paraderos</button>

        </form>

        <?php if ($error !== ''): ?>

            <!-- Mostramos los mensajes de error de forma amigable. -->
            <div class="mensaje">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($paraderos)): ?>

            <div class="mensaje exito">
                Se encontraron <?= count($paraderos) ?>
                paraderos para <?= htmlspecialchars(
                    $localidad,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>.
                Se muestran hasta 100 registros.
            </div>

            <!-- Tabla para presentar los resultados. -->
            <div class="tabla-contenedor">

                <table>

                    <thead>
                        <tr>
                            <th>Nombre del paradero</th>
                            <th>Direccion</th>
                            <th>Latitud</th>
                            <th>Longitud</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($paraderos as $paradero): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $paradero['nombre'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $paradero['direccion'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $paradero['latitud'] !== null
                                        ? htmlspecialchars(
                                            (string) $paradero['latitud'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : 'No disponible' ?>
                                </td>

                                <td>
                                    <?= $paradero['longitud'] !== null
                                        ? htmlspecialchars(
                                            (string) $paradero['longitud'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : 'No disponible' ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php elseif ($consultaRealizada && $error === ''): ?>

            <div class="mensaje">
                No hay registros para mostrar.
            </div>

        <?php endif; ?>

    </main>

    <footer>
        <p>Proyecto de formacion SENA - ADSO</p>
        <p>Fuente de datos: Catastro Bogota</p>
    </footer>

</body>
</html>
```