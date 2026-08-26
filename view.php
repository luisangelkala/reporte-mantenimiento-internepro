<?php
require_once __DIR__ . '/database.php';
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
<link rel="stylesheet" type="text/css" href="assets/css/style.css?ver=0.5">
<script type="text/javascript" src="assets/js/jquery-3.2.1.min.js"></script>

</head>
<body>

<main style="background-image:none">
<div class="container bg-gris">
    <a href="index.php"><img class="" src="images/logo-internepro.png" width="300" height="86"></a>

    <?php
    if ($data->num_rows > 0) {
        while ($row = $data->fetch_assoc()){
            ?>

            <div class="head-edit">
                <h2><?php echo $row['title_reporte']?></h2>
            </div>

            <div class="data-inicial">

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Cliente:</strong> <?php echo $row['cliente_reporte'] ?></p>
                        <p><strong>Fecha:</strong> <?php echo $row['fecha_reporte'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Equipo:</strong> <?php echo $row['equipo_reporte'] ?></p>
                        <p><strong>Técnico:</strong> <?php echo $row['tecnico_reporte'] ?></p>
                    </div>
                </div>


                <?php
                $data_reporte = ($row['data_reporte'] == null) ? null : json_decode($row['data_reporte'], true);

                //print_r($data_reporte);

                $obs_reporte = ($row['obs_reporte'] == null) ? null : json_decode($row['obs_reporte'], true);

                ?>

                <div class="" style="margin: 20px 0; text-align: left;">
                    <label>NOTA: Comentarios en observaciones</label><br>
                    <label>NOMENCLATURA A UTILIZAR:<br>
                        OK - Fue inspeccionado y quedo en óptimas condiciones.<br>
                        X  - Fue inspeccionado y requiere tomar otras acciones.<br>
                        R  - Fue inspeccionado y se realizó la reparación.
                    </label>
                </div>

                <div style="margin: 20px 0; text-align:left;">
                    <h4>INSTRUCCIONES GENERALES</h4>
                    <div class="general-q">
                        <label><strong>
                        Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                        Inspeccionado y en óptimas condiciones</strong></label>
                        <span><?php echo (!isset($data_reporte['s_0_a'])) ? '' : $data_reporte['s_0_a']; ?></span>
                    </div>
                        <div class="general-q">
                            <label><strong>
                                    Compruebe el funcionamiento del ascensor (aceleración/desaceleración, vibración y ruido).<br>
                                    Inspeccionado y necesita realizar acciones</strong></label>
                                    <span><?php echo (!isset($data_reporte['s_0_b'])) ? '' : $data_reporte['s_0_b']; ?></span>
                            </div>
                            <div class="general-q">
                                    <label><strong>
                                    Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                                    Inspeccionado y en óptimas condiciones</strong></label>
                                    <span><?php echo (!isset($data_reporte['s_0_c'])) ? '' : $data_reporte['s_0_c']; ?> </span>
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
                                                            <span><?php echo (!isset($data_reporte['s_1_a'])) ? '' : $data_reporte['s_1_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Señalización</label>
                                                            <span><?php echo (!isset($data_reporte['s_1_b'])) ? '' : $data_reporte['s_1_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tapa de ductos</label>
                                                            <span><?php echo (!isset($data_reporte['s_1_c'])) ? '' : $data_reporte['s_1_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tapas de pases de cable</label>
                                                            <span><?php echo (!isset($data_reporte['s_1_d'])) ? '' : $data_reporte['s_1_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Climatización</label>
                                                            <span><?php echo (!isset($data_reporte['s_1_e'])) ? '' : $data_reporte['s_1_e'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Filtraciones</label>
                                                            <span><?php echo (!isset($data_reporte['s_1_f'])) ? '' : $data_reporte['s_1_f'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Pintura</label>
                                                            <span><?php echo (!isset($data_reporte['s_1_g'])) ? '' : $data_reporte['s_1_g'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_1'])) ? '' : $obs_reporte['ob_1'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">2</th>
                                                <th scope="col" class="text-left">
                                                    <strong>MAQUINA Y FRENO</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Ruido</label>
                                                            <span><?php echo (!isset($data_reporte['s_2_a'])) ? '' : $data_reporte['s_2_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Vibraciones</label>
                                                            <span><?php echo (!isset($data_reporte['s_2_b'])) ? '' : $data_reporte['s_2_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Conexiones flojas</label>
                                                            <span><?php echo (!isset($data_reporte['s_2_c'])) ? '' : $data_reporte['s_2_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Desgaste de la zapata del freno</label>
                                                            <span><?php echo (!isset($data_reporte['s_2_d'])) ? '' : $data_reporte['s_2_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Frenado de emergencia</label>
                                                            <span><?php echo (!isset($data_reporte['s_2_e'])) ? '' : $data_reporte['s_2_e'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Nivel de aceite</label>
                                                            <span><?php echo (!isset($data_reporte['s_2_f'])) ? '' : $data_reporte['s_2_f'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_2'])) ? '' : $obs_reporte['ob_2'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">3</th>
                                                <th scope="col" class="text-left">
                                                    <strong>GOBERNADOR Y CABLE</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Ruido</label>
                                                            <span><?php echo (!isset($data_reporte['s_3_a'])) ? '' : $data_reporte['s_3_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Switcher</label>
                                                            <span><?php echo (!isset($data_reporte['s_3_b'])) ? '' : $data_reporte['s_3_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Cable</label>
                                                            <span><?php echo (!isset($data_reporte['s_3_c'])) ? '' : $data_reporte['s_3_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Sello de fabrica</label>
                                                            <span><?php echo (!isset($data_reporte['s_3_d'])) ? '' : $data_reporte['s_3_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Velocidad de disparo m/s</label>
                                                            <span><?php echo (!isset($data_reporte['s_3_e'])) ? '' : $data_reporte['s_3_e'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_3'])) ? '' : $obs_reporte['ob_3'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">4</th>
                                                <th scope="col" class="text-left">
                                                    <strong>TERMINALES DE CABLES</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Perros</label>
                                                            <span><?php echo (!isset($data_reporte['s_4_a'])) ? '' : $data_reporte['s_4_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tuercas</label>
                                                            <span><?php echo (!isset($data_reporte['s_4_b'])) ? '' : $data_reporte['s_4_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Pasapuntas</label>
                                                            <span><?php echo (!isset($data_reporte['s_4_c'])) ? '' : $data_reporte['s_4_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Quitavueltas</label>
                                                            <span><?php echo (!isset($data_reporte['s_4_d'])) ? '' : $data_reporte['s_4_d'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_4'])) ? '' : $obs_reporte['ob_4'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">5</th>
                                                <th scope="col" class="text-left">
                                                    <strong>CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Alarma</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_a'])) ? '' : $data_reporte['s_5_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Interfon</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_b'])) ? '' : $data_reporte['s_5_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Iluminación</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_c'])) ? '' : $data_reporte['s_5_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Piso</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_d'])) ? '' : $data_reporte['s_5_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Falso techo paneles flojos</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_e'])) ? '' : $data_reporte['s_5_e'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Abanicos</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_f'])) ? '' : $data_reporte['s_5_f'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Display</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_g'])) ? '' : $data_reporte['s_5_g'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Botones</label>
                                                            <span><?php echo (!isset($data_reporte['s_5_h'])) ? '' : $data_reporte['s_5_h'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_5'])) ? '' : $obs_reporte['ob_5'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">6</th>
                                                <th scope="col" class="text-left">
                                                    <strong>PUERTA DE CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Operador de puerta</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_a'])) ? '' : $data_reporte['s_6_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Correas o cables</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_b'])) ? '' : $data_reporte['s_6_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Ruedas y contrarruedas</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_c'])) ? '' : $data_reporte['s_6_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Zapatos</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_d'])) ? '' : $data_reporte['s_6_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Switch</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_e'])) ? '' : $data_reporte['s_6_e'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Fotocelda</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_f'])) ? '' : $data_reporte['s_6_f'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Velocidad</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_g'])) ? '' : $data_reporte['s_6_g'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Botones</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_h'])) ? '' : $data_reporte['s_6_h'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Ruido</label>
                                                            <span><?php echo (!isset($data_reporte['s_6_i'])) ? '' : $data_reporte['s_6_i'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_6'])) ? '' : $obs_reporte['ob_6'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">7</th>
                                                <th scope="col" class="text-left">
                                                    <strong>SOBRE CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Switch del paracaida</label>
                                                            <span><?php echo (!isset($data_reporte['s_7_a'])) ? '' : $data_reporte['s_7_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Límites de recorrido</label>
                                                            <span><?php echo (!isset($data_reporte['s_7_b'])) ? '' : $data_reporte['s_7_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Inductores</label>
                                                            <span><?php echo (!isset($data_reporte['s_7_c'])) ? '' : $data_reporte['s_7_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Pesacarga</label>
                                                            <span><?php echo (!isset($data_reporte['s_7_d'])) ? '' : $data_reporte['s_7_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Caja de conexiones</label>
                                                            <span><?php echo (!isset($data_reporte['s_7_e'])) ? '' : $data_reporte['s_7_e'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tarjeta de comunicación</label>
                                                            <span><?php echo (!isset($data_reporte['s_7_f'])) ? '' : $data_reporte['s_7_f'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_7'])) ? '' : $obs_reporte['ob_7'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">8</th>
                                                <th scope="col" class="text-left">
                                                    <strong>SOBRE CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Baranda de protección</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_a'])) ? '' : $data_reporte['s_8_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Cover de la polea</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_b'])) ? '' : $data_reporte['s_8_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Polea</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_c'])) ? '' : $data_reporte['s_8_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Terminales de cables</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_d'])) ? '' : $data_reporte['s_8_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tuercas y pasapuntas</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_e'])) ? '' : $data_reporte['s_8_e'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Zapatos de cabina y contrapeso</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_f'])) ? '' : $data_reporte['s_8_f'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Aceiteras</label>
                                                            <span><?php echo (!isset($data_reporte['s_8_f'])) ? '' : $data_reporte['s_8_f'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_8'])) ? '' : $obs_reporte['ob_8'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">9</th>
                                                <th scope="col" class="text-left">
                                                    <strong>SOBRE CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Estado de rieles</label>
                                                            <span><?php echo (!isset($data_reporte['s_9_a'])) ? '' : $data_reporte['s_9_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Empates</label>
                                                            <span><?php echo (!isset($data_reporte['s_9_b'])) ? '' : $data_reporte['s_9_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Brackets</label>
                                                            <span><?php echo (!isset($data_reporte['s_9_c'])) ? '' : $data_reporte['s_9_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Clip</label>
                                                            <span><?php echo (!isset($data_reporte['s_9_d'])) ? '' : $data_reporte['s_9_d'];?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Lubricación</label>
                                                            <span><?php echo (!isset($data_reporte['s_9_e'])) ? '' : $data_reporte['s_9_e'];?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_9'])) ? '' : $obs_reporte['ob_9'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">10</th>
                                                <th scope="col" class="text-left">
                                                    <strong>BAJO CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Bloque seguridad</label>
                                                            <span><?php echo (!isset($data_reporte['s_10_a'])) ? '' : $data_reporte['s_10_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Cadena de compensación</label>
                                                            <span><?php echo (!isset($data_reporte['s_10_b'])) ? '' : $data_reporte['s_10_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tensores</label>
                                                            <span><?php echo (!isset($data_reporte['s_10_c'])) ? '' : $data_reporte['s_10_c']; ?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_10'])) ? '' : $obs_reporte['ob_10'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">11</th>
                                                <th scope="col" class="text-left">
                                                    <strong>BAJO CABINA</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Estado del marco rotura</label>
                                                            <span><?php echo (!isset($data_reporte['s_11_a'])) ? '' : $data_reporte['s_11_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Oxidación</label>
                                                            <span><?php echo (!isset($data_reporte['s_11_b'])) ? '' : $data_reporte['s_11_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Zapatos de cabina</label>
                                                            <span><?php echo (!isset($data_reporte['s_11_c'])) ? '' : $data_reporte['s_11_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Pintura</label>
                                                            <span><?php echo (!isset($data_reporte['s_11_d'])) ? '' : $data_reporte['s_11_d']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Corrosión</label>
                                                            <span><?php echo (!isset($data_reporte['s_11_e'])) ? '' : $data_reporte['s_11_e']; ?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_11'])) ? '' : $obs_reporte['ob_11'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">12</th>
                                                <th scope="col" class="text-left">
                                                    <strong>PIT</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Buffer</label>
                                                            <span><?php echo (!isset($data_reporte['s_12_a'])) ? '' : $data_reporte['s_12_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Pesa del paracaídas</label>
                                                            <span><?php echo (!isset($data_reporte['s_12_b'])) ? '' : $data_reporte['s_12_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Iluminación</label>
                                                            <span><?php echo (!isset($data_reporte['s_12_c'])) ? '' : $data_reporte['s_12_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Switcher</label>
                                                            <span><?php echo (!isset($data_reporte['s_12_d'])) ? '' : $data_reporte['s_12_d']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Limite</label>
                                                            <span><?php echo (!isset($data_reporte['s_12_e'])) ? '' : $data_reporte['s_12_e']; ?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_12'])) ? '' : $obs_reporte['ob_12'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">13</th>
                                                <th scope="col" class="text-left">
                                                    <strong>PUERTAS DE PASILLO</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Ruedas y contrarruedas</label>
                                                            <span><?php echo (!isset($data_reporte['s_13_a'])) ? '' : $data_reporte['s_13_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Zapatos</label>
                                                            <span><?php echo (!isset($data_reporte['s_13_b'])) ? '' : $data_reporte['s_13_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Switches Sill</label>
                                                            <span><?php echo (!isset($data_reporte['s_13_c'])) ? '' : $data_reporte['s_13_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Pintura</label>
                                                            <span><?php echo (!isset($data_reporte['s_13_d'])) ? '' : $data_reporte['s_13_d']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Limpieza y lubricación</label>
                                                            <span><?php echo (!isset($data_reporte['s_13_e'])) ? '' : $data_reporte['s_13_e']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Corrosión</label>
                                                            <span><?php echo (!isset($data_reporte['s_13_f'])) ? '' : $data_reporte['s_13_f']; ?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_13'])) ? '' : $obs_reporte['ob_13'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">14</th>
                                                <th scope="col" class="text-left">
                                                    <strong>PASILLO</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Interfono</label>
                                                            <span><?php echo (!isset($data_reporte['s_14_a'])) ? '' : $data_reporte['s_14_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Bombero</label>
                                                            <span><?php echo (!isset($data_reporte['s_14_b'])) ? '' : $data_reporte['s_14_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Displays</label>
                                                            <span><?php echo (!isset($data_reporte['s_14_c'])) ? '' : $data_reporte['s_14_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Botoneras</label>
                                                            <span><?php echo (!isset($data_reporte['s_14_d'])) ? '' : $data_reporte['s_14_d']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Tarjetas de comunicación</label>
                                                            <span><?php echo (!isset($data_reporte['s_14_e'])) ? '' : $data_reporte['s_14_e']; ?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_14'])) ? '' : $obs_reporte['ob_14'];?></p>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th scope="col">15</th>
                                                <th scope="col" class="text-left">
                                                    <strong>CALIDAD DE RECORRIDO</strong>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Ruidos</label>
                                                            <span><?php echo (!isset($data_reporte['s_15_a'])) ? '' : $data_reporte['s_15_a']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Golpes</label>
                                                            <span><?php echo (!isset($data_reporte['s_15_b'])) ? '' : $data_reporte['s_15_b']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Movimientos</label>
                                                            <span><?php echo (!isset($data_reporte['s_15_c'])) ? '' : $data_reporte['s_15_c']; ?></span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label>Nivel de parada</label>
                                                            <span><?php echo (!isset($data_reporte['s_15_d'])) ? '' : $data_reporte['s_15_d']; ?></span>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th scope="col" class="text-left">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ob_15'])) ? '' : $obs_reporte['ob_15'];?></p>
                                                </th>
                                            </tr>
                                        </tbody>
                                </table>
                        </div>

                        <div class="general-q">
                            <label><strong>COMENTARIOS: </strong></label> &nbsp; <?php echo (!isset($obs_reporte['ob_comentario'])) ? '' : $obs_reporte['ob_comentario']; ?>
                        </div>
                        <div class="general-q">
                            <label><strong>RECOMENDACIONES: </strong></label> &nbsp;<?php echo (!isset($obs_reporte['ob_recomendacion'])) ? '' : $obs_reporte['ob_recomendacion']; ?>
                        </div>

                        <?php
                        $status = json_decode($row['state_reporte'], true); //json_encode(['status' => 'close', 'aprobado' => $cliente, 'fecha' => date("Y-m-d")]);
                        }
                    }
                    ?>
                </div>

            </div>

            <div class="head-edit">
                <div class="status">
                    <?php
                    if($status['status'] == 'close'){
                        $fecha = $status['fecha'];
                        $cliente = $status['aprobado'];

                        echo 'El Reporte ha sido aprobado: '.$fecha.' por: '.$cliente;
                    }else{
                    ?>
                    <label>Cliente: </label>
                    <input type="text" class="form-control" name="cliente" id="cliente" value="" style="margin-left: 20px;" required>
                    <input type="button" class="form-control aprobar" name="aprobar" value="Aprobar">
                <?php } ?>
                </div>
            </div>
            </form>
        </main>
<script>
(function($) {
    $doc = $(document);

    $doc.ready( function(){

        /** CREAR REPORTE */
        $('.head-edit').on('click', '.aprobar', function(event) {
            if(event.preventDefault) { event.preventDefault(); }

            if($('#cliente').val() == ''){
                alert('Introduzca el nombre del cliente que aprueba el registro')
            }else{
                $cliente = $('#cliente').val();
            }

            $params = {
                'type'    : 'aprobando',
                'id'      : <?php echo $ID ?>,
                'cliente' : $cliente
            };

            $status = $('.status');
            $status.text('Aprobando reporte...');

            // Run query
            $.ajax({
                url: "process.php",
                data: $params,
                type: 'post',
                dataType: 'json',
                success: function(data, XMLHttpRequest) {
                            if (data.status === 200) {
                                $status.html(data.message);
                            }
                            else {
                                $status.html(data.message);
                            }

                            console.log(data);
                            console.log(XMLHttpRequest);
                }
            });
        });

    });

})(jQuery);</script>
<script src="assets/css/bootstrap5/bootstrap.min.js?v=0.4"></script>
</body>
    </html>
