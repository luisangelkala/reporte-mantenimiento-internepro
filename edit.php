<?php
/**
* Password generator
*
* This is the php index file to load all mini site.
*
* @author LAGC
*/


if(!isset($_GET["id"]) || $_GET["id"] == '' || $_GET["id"] == 0){
header("Location: index.php");
}

$ID = $_GET["id"];


/**
*  Connect Database
*/
function db(){
$servername = "localhost";
$database = "db_registros_elevadores";
$username = "us_registro";
$password = "q2H7S98oXeD5";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);
return $conn;
}

$sql = "SELECT * FROM reporte WHERE `id`='$ID'";

$db = db();

$data = $db->query($sql);

mysqli_close($db);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Tags for SEO -->
    <title>Reporte Mantenimiento</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Password generator">
    <meta name="keywords" content="Password generator">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Resources CSS & JS -->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap5/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="assets/plugins/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css?ver=0.54">
    <script type="text/javascript" src="assets/js/jquery-3.2.1.min.js"></script>

</head>
<body>

    <main>
        <div class="container bg-gris">
            <a href="index.php"><img class="" src="images/logo-internepro.png" width="300" height="86"></a>

            <form action="process.php" method="post">

                <?php
                    if ($data->num_rows > 0) {
                        while ($row = $data->fetch_assoc()){
                    ?>
                <div class="head-edit">
                    <h2>Registro: </h2>
                    <input type="text" class="form-control" name="title" value="<?php echo $row['title_reporte']?>" style="margin-left: 20px;">
                </div>

                <div>
		    <input type="hidden" name="type" value="insert">
		    <input type="hidden" name="reporte" value="elevador">
                    <input type="hidden" name="id" value="<?php echo $ID?>">
                </div>

                <div class="data-inicial">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Cliente" name="cliente" value="<?php echo $row['cliente_reporte'] ?>">
                            <input type="date" class="form-control" placeholder="Fecha" name="fecha" value="<?php echo $row['fecha_reporte'] ?>">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Ascensor numero y tipo" name="equipo" value="<?php echo $row['equipo_reporte'] ?>">
                            <input type="text" class="form-control" placeholder="Nombre de técnico" name="tecnico" value="<?php echo $row['tecnico_reporte'] ?>">
                        </div>
                    </div>

                    <?php
                        $data_reporte = ($row['data_reporte'] == null) ? null : json_decode($row['data_reporte'], true);

                        //print_r($data_reporte);

                        $obs_reporte = ($row['obs_reporte'] == null) ? null : json_decode($row['obs_reporte'], true);

                        function select($data_reporte, $name){
                            if(!isset($data_reporte[$name])){
                                $name_array = null;
                            }else{
                                $name_array = $data_reporte[$name];
                                }

                            $select = '';
                            $select .= '<select class="form-control" name="'.$name.'">';
                            $select .= ($name_array == 0 || $name_array == '' || $name_array == null) ? '<option value="0" selected>Marcar</option>' : '<option value="0">Ver</option>';
                            $select .= ($name_array == 'OK' ) ? '<option value="OK" selected>OK</option>' : '<option value="OK">OK</option>';
                            $select .= ($name_array == 'X' ) ? '<option value="X" selected>X</option>' : '<option value="X">X</option>';
                            $select .= ($name_array == 'R' ) ? '<option value="R" selected>R</option>' : '<option value="R">R</option>';
                            $select .= '</select>';

                            return $select;
                        }
                    ?>


                <div class="" style="margin: 20px 0;">
                <label>NOTA: Comentarios en observaciones</label><br>
                <label>NOMENCLATURA A UTILIZAR:<br>
                        OK - Fue inspeccionado y quedo en óptimas condiciones.<br>
                        X  - Fue inspeccionado y requiere tomar otras acciones.<br>
                        R  - Fue inspeccionado y se realizó la reparación.
                    </label>
                </div>

                <div style="margin: 20px 0;">
                    <h4>INSTRUCCIONES GENERALES</h4>
                    <div class="general-q">
                        <label><strong>
                            Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                            Inspeccionado y en óptimas condiciones</strong></label><?php echo select($data_reporte,'s_0_a');?>
                    </div>
                    <div class="general-q">
                        <label><strong>
                            Compruebe el funcionamiento del ascensor (aceleración/desaceleración, vibración y ruido).<br>
                            Inspeccionado y necesita realizar acciones</strong></label><?php echo select($data_reporte,'s_0_b');?>
                    </div>
                    <div class="general-q">
                        <label><strong>
                            Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                            Inspeccionado y en óptimas condiciones</strong></label><?php echo select($data_reporte,'s_0_c');?>
                    </div>

                <div class="table-responsive">
                <table class="table">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col" class="text-left">ACTIVIDAD A REALIZAR</th>
                            <th scope="col" class="text-left">OBSERVACIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="col">1</th>
                            <th scope="col" class="text-left">
                                <strong>CUARTO DE MAQUINAS</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Iluminación</label>
                                        <?php echo select($data_reporte,'s_1_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Señalización</label>
                                        <?php echo select($data_reporte,'s_1_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tapa de ductos</label>
                                        <?php echo select($data_reporte,'s_1_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tapas de pases de cable</label>
                                        <?php echo select($data_reporte,'s_1_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Climatización</label>
                                        <?php echo select($data_reporte,'s_1_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Filtraciones</label>
                                        <?php echo select($data_reporte,'s_1_f');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pintura</label>
                                        <?php echo select($data_reporte,'s_1_g');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_1"><?php
                                        echo (!isset($obs_reporte['ob_1'])) ? '' : $obs_reporte['ob_1'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">2</th>
                            <th scope="col" class="text-left">
                                <strong>MAQUINA Y FRENO</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Ruido</label>
                                        <?php echo select($data_reporte,'s_2_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Vibraciones</label>
                                        <?php echo select($data_reporte,'s_2_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Conexiones flojas</label>
                                        <?php echo select($data_reporte,'s_2_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Desgaste de la zapata del freno</label>
                                        <?php echo select($data_reporte,'s_2_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Frenado de emergencia</label>
                                        <?php echo select($data_reporte,'s_2_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Nivel de aceite</label>
                                        <?php echo select($data_reporte,'s_2_f');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_2"><?php
                                        echo (!isset($obs_reporte['ob_2'])) ? '' : $obs_reporte['ob_2'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">3</th>
                            <th scope="col" class="text-left">
                                <strong>GOBERNADOR Y CABLE</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Ruido</label>
                                        <?php echo select($data_reporte,'s_3_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Switcher</label>
                                        <?php echo select($data_reporte,'s_3_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Cable</label>
                                        <?php echo select($data_reporte,'s_3_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Sello de fabrica</label>
                                        <?php echo select($data_reporte,'s_3_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Velocidad de disparo m/s</label>
                                        <?php echo select($data_reporte,'s_3_e');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_3"><?php
                                        echo (!isset($obs_reporte['ob_3'])) ? '' : $obs_reporte['ob_3'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">4</th>
                            <th scope="col" class="text-left">
                                <strong>TERMINALES DE CABLES</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Perros</label>
                                        <?php echo select($data_reporte,'s_4_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tuercas</label>
                                        <?php echo select($data_reporte,'s_4_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pasapuntas</label>
                                        <?php echo select($data_reporte,'s_4_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Quitavueltas</label>
                                        <?php echo select($data_reporte,'s_4_d');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_4"><?php
                                        echo (!isset($obs_reporte['ob_4'])) ? '' : $obs_reporte['ob_4'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">5</th>
                            <th scope="col" class="text-left">
                                <strong>CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Alarma</label>
                                        <?php echo select($data_reporte,'s_5_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Interfon</label>
                                        <?php echo select($data_reporte,'s_5_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Iluminación</label>
                                        <?php echo select($data_reporte,'s_5_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Piso</label>
                                        <?php echo select($data_reporte,'s_5_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Falso techo paneles flojos</label>
                                        <?php echo select($data_reporte,'s_5_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Abanicos</label>
                                        <?php echo select($data_reporte,'s_5_f');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Display</label>
                                        <?php echo select($data_reporte,'s_5_g');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Botones</label>
                                        <?php echo select($data_reporte,'s_5_h');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_5"><?php
                                        echo (!isset($obs_reporte['ob_5'])) ? '' : $obs_reporte['ob_5'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">6</th>
                            <th scope="col" class="text-left">
                                <strong>PUERTA DE CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Operador de puerta</label>
                                        <?php echo select($data_reporte,'s_6_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Correas o cables</label>
                                        <?php echo select($data_reporte,'s_6_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Ruedas y contrarruedas</label>
                                        <?php echo select($data_reporte,'s_6_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Zapatos</label>
                                        <?php echo select($data_reporte,'s_6_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Switch</label>
                                        <?php echo select($data_reporte,'s_6_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Fotocelda</label>
                                        <?php echo select($data_reporte,'s_6_f');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Velocidad</label>
                                        <?php echo select($data_reporte,'s_6_g');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Botones</label>
                                        <?php echo select($data_reporte,'s_6_h');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Ruido</label>
                                        <?php echo select($data_reporte,'s_6_i');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_6"><?php
                                        echo (!isset($obs_reporte['ob_6'])) ? '' : $obs_reporte['ob_6'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">7</th>
                            <th scope="col" class="text-left">
                                <strong>SOBRE CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Switch del paracaida</label>
                                        <?php echo select($data_reporte,'s_7_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Límites de recorrido</label>
                                        <?php echo select($data_reporte,'s_7_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Inductores</label>
                                        <?php echo select($data_reporte,'s_7_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pesacarga</label>
                                        <?php echo select($data_reporte,'s_7_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Caja de conexiones</label>
                                        <?php echo select($data_reporte,'s_7_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tarjeta de comunicación</label>
                                        <?php echo select($data_reporte,'s_7_f');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_7"><?php
                                        echo (!isset($obs_reporte['ob_7'])) ? '' : $obs_reporte['ob_7'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">8</th>
                            <th scope="col" class="text-left">
                                <strong>SOBRE CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Baranda de protección</label>
                                        <?php echo select($data_reporte,'s_8_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Cover de la polea</label>
                                        <?php echo select($data_reporte,'s_8_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Polea</label>
                                        <?php echo select($data_reporte,'s_8_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Terminales de cables</label>
                                        <?php echo select($data_reporte,'s_8_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tuercas y pasapuntas</label>
                                        <?php echo select($data_reporte,'s_8_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Zapatos de cabina y contrapeso</label>
                                        <?php echo select($data_reporte,'s_8_f');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Aceiteras</label>
                                        <?php echo select($data_reporte,'s_8_g');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_8"><?php
                                        echo (!isset($obs_reporte['ob_8'])) ? '' : $obs_reporte['ob_8'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">9</th>
                            <th scope="col" class="text-left">
                                <strong>SOBRE CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Estado de rieles</label>
                                        <?php echo select($data_reporte,'s_9_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Empates</label>
                                        <?php echo select($data_reporte,'s_9_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Brackets</label>
                                        <?php echo select($data_reporte,'s_9_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Clip</label>
                                        <?php echo select($data_reporte,'s_9_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Lubricación</label>
                                        <?php echo select($data_reporte,'s_9_e');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_9"><?php
                                        echo (!isset($obs_reporte['ob_9'])) ? '' : $obs_reporte['ob_9'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">10</th>
                            <th scope="col" class="text-left">
                                <strong>BAJO CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Bloque seguridad</label>
                                        <?php echo select($data_reporte,'s_10_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Cadena de compensación</label>
                                        <?php echo select($data_reporte,'s_10_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tensores</label>
                                        <?php echo select($data_reporte,'s_10_c');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_10"><?php
                                        echo (!isset($obs_reporte['ob_10'])) ? '' : $obs_reporte['ob_10'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">11</th>
                            <th scope="col" class="text-left">
                                <strong>BAJO CABINA</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Estado del marco rotura</label>
                                        <?php echo select($data_reporte,'s_11_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Oxidación</label>
                                        <?php echo select($data_reporte,'s_11_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Zapatos de cabina</label>
                                        <?php echo select($data_reporte,'s_11_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pintura</label>
                                        <?php echo select($data_reporte,'s_11_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Corrosión</label>
                                        <?php echo select($data_reporte,'s_11_e');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_11"><?php
                                        echo (!isset($obs_reporte['ob_11'])) ? '' : $obs_reporte['ob_11'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">12</th>
                            <th scope="col" class="text-left">
                                <strong>PIT</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Buffer</label>
                                        <?php echo select($data_reporte,'s_12_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pesa del paracaídas</label>
                                        <?php echo select($data_reporte,'s_12_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Iluminación</label>
                                        <?php echo select($data_reporte,'s_12_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Switcher</label>
                                        <?php echo select($data_reporte,'s_12_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Limite</label>
                                        <?php echo select($data_reporte,'s_12_e');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_12"><?php
                                        echo (!isset($obs_reporte['ob_12'])) ? '' : $obs_reporte['ob_12'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">13</th>
                            <th scope="col" class="text-left">
                                <strong>PUERTAS DE PASILLO</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Ruedas y contrarruedas</label>
                                        <?php echo select($data_reporte,'s_13_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Zapatos</label>
                                        <?php echo select($data_reporte,'s_13_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Switches Sill</label>
                                        <?php echo select($data_reporte,'s_13_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pintura</label>
                                        <?php echo select($data_reporte,'s_13_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Limpieza y lubricación</label>
                                        <?php echo select($data_reporte,'s_13_e');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Corrosión</label>
                                        <?php echo select($data_reporte,'s_13_f');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_13"><?php
                                        echo (!isset($obs_reporte['ob_13'])) ? '' : $obs_reporte['ob_13'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">14</th>
                            <th scope="col" class="text-left">
                                <strong>PASILLO</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Interfono</label>
                                        <?php echo select($data_reporte,'s_14_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Bombero</label>
                                        <?php echo select($data_reporte,'s_14_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Displays</label>
                                        <?php echo select($data_reporte,'s_14_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Botoneras</label>
                                        <?php echo select($data_reporte,'s_14_d');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tarjetas de comunicación</label>
                                        <?php echo select($data_reporte,'s_14_e');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_14"><?php
                                        echo (!isset($obs_reporte['ob_14'])) ? '' : $obs_reporte['ob_14'];
                                    ?></textarea>
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">15</th>
                            <th scope="col" class="text-left">
                                <strong>CALIDAD DE RECORRIDO</strong>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Ruidos</label>
                                        <?php echo select($data_reporte,'s_15_a');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Golpes</label>
                                        <?php echo select($data_reporte,'s_15_b');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Movimientos</label>
                                        <?php echo select($data_reporte,'s_15_c');?>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Nivel de parada</label>
                                        <?php echo select($data_reporte,'s_15_d');?>
                                    </div>
                                </div>
                            </th>
                            <th scope="col" class="text-left">
                                <textarea class="form-control" rows="6" name="ob_15"><?php
                                        echo (!isset($obs_reporte['ob_15'])) ? '' : $obs_reporte['ob_15'];
                                    ?></textarea>
                            </th>
                        </tr>
                    </tbody>
                </table>
                </div>
                </div>

                <div class="general-q">
                    <label><strong>COMENTARIOS:</strong></label>&nbsp;&nbsp;&nbsp;
                    <textarea class="form-control" rows="6" name="ob_comentario"><?php echo (!isset($obs_reporte['ob_comentario'])) ? '' : $obs_reporte['ob_comentario'];?></textarea>
                </div>
                <div class="general-q">
                    <label><strong>RECOMENDACIONES:</strong></label> &nbsp;
                    <textarea class="form-control" rows="6" name="ob_recomendacion"><?php echo (!isset($obs_reporte['ob_recomendacion'])) ? '' : $obs_reporte['ob_recomendacion'];?></textarea>
                </div>

                <div class="data-final">
                    <input type="submit" class="form-control" name="guardar" value="Guardar Reporte">
                </div>

                <?php
                        }
                    }
                    ?>
            </div>
        </form>
    </div>
</main>
<script src="assets/css/bootstrap5/bootstrap.min.js?v=0.4"></script>
</body>
</html>
