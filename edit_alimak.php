<?php
require_once __DIR__ . '/config/db.php';
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
    <link rel="stylesheet" type="text/css" href="assets/css/style.css?ver=0.321454">
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
                    <input type="hidden" name="reporte" value="alimak">
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
                <h4>INTERNEPRO SA / ALIMAK -PANAMÁ</h4>
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
                            Inspeccionado y en óptimas condiciones</strong></label><?php echo select($data_reporte,'a_0_a');?>
                    </div>
                    <div class="general-q">
                        <label><strong>
                            Compruebe el funcionamiento del ascensor (aceleración/desaceleración, vibración y ruido).<br>
                            Inspeccionado y necesita realizar acciones</strong></label><?php echo select($data_reporte,'a_0_b');?>
                    </div>
                    <div class="general-q">
                        <label><strong>
                            Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                            Inspeccionado y en óptimas condiciones</strong></label><?php echo select($data_reporte,'a_0_c');?>
                    </div>
                <div class="table-responsive table-al">
                <table class="table al-w-50">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col" class="text-left">REVISION</th>
                            <th scope="col" class="text-left">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>CUARTO DE MAQUINAS</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">1</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Información de instrucciones y de seguridad escalera</label>
                                        <?php echo select($data_reporte,'a_1_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_1"><?php
                                        echo (!isset($obs_reporte['ab_1'])) ? '' : $obs_reporte['ab_1'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">2</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Estado de los paneles de la cabina (limpieza, golpes)</label>
                                        <?php echo select($data_reporte,'a_2_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_2"><?php
                                        echo (!isset($obs_reporte['ab_2'])) ? '' : $obs_reporte['ab_2'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">3</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Puerta de cabina</label>
                                        <?php echo select($data_reporte,'a_3_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Puerta de escotilla</label>
                                        <?php echo select($data_reporte,'a_3_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Interlocks</label>
                                        <?php echo select($data_reporte,'a_3_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Switch actuador</label>
                                        <?php echo select($data_reporte,'a_3_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Actuador</label>
                                        <?php echo select($data_reporte,'a_3_e');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>switch de puerta</label>
                                        <?php echo select($data_reporte,'a_3_f');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_3"><?php
                                        echo (!isset($obs_reporte['ab_3'])) ? '' : $obs_reporte['ab_3'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">4</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Luz de cabina</label>
                                        <?php echo select($data_reporte,'a_4_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Abanicos</label>
                                        <?php echo select($data_reporte,'a_4_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Alarma</label>
                                        <?php echo select($data_reporte,'a_4_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Display</label>
                                        <?php echo select($data_reporte,'a_4_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Stop</label>
                                        <?php echo select($data_reporte,'a_4_e');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Intercom</label>
                                        <?php echo select($data_reporte,'a_4_f');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Botones de llamada</label>
                                        <?php echo select($data_reporte,'a_4_g');?>
                                    </div><div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_4"><?php
                                        echo (!isset($obs_reporte['ab_4'])) ? '' : $obs_reporte['ab_4'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>SOBRE CABINA</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">5</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Limit switch ref</label>
                                        <?php echo select($data_reporte,'a_5_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>SW Final</label>
                                        <?php echo select($data_reporte,'a_5_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>SW up dw</label>
                                        <?php echo select($data_reporte,'a_5_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_5"><?php
                                        echo (!isset($obs_reporte['ab_5'])) ? '' : $obs_reporte['ab_5'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">6</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Caja de inspección</label>
                                        <?php echo select($data_reporte,'a_6_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Stop</label>
                                        <?php echo select($data_reporte,'a_6_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Switch inspección</label>
                                        <?php echo select($data_reporte,'a_6_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>SW emergencia</label>
                                        <?php echo select($data_reporte,'a_6_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Boton up</label>
                                        <?php echo select($data_reporte,'a_6_e');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Dw</label>
                                        <?php echo select($data_reporte,'a_6_f');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_6"><?php
                                        echo (!isset($obs_reporte['ab_6'])) ? '' : $obs_reporte['ab_6'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>ELECTRICO</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">7</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Estado de panel</label>
                                        <?php echo select($data_reporte,'a_7_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Tapa de regletas</label>
                                        <?php echo select($data_reporte,'a_7_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Estado cable viajero</label>
                                        <?php echo select($data_reporte,'a_7_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_7"><?php
                                        echo (!isset($obs_reporte['ab_7'])) ? '' : $obs_reporte['ab_7'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">8</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Estado panel Base</label>
                                        <?php echo select($data_reporte,'a_8_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>ACL Base</label>
                                        <?php echo select($data_reporte,'a_8_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Switch</label>
                                        <?php echo select($data_reporte,'a_8_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_8"><?php
                                        echo (!isset($obs_reporte['ab_8'])) ? '' : $obs_reporte['ab_8'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">9</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Contactores</label>
                                        <?php echo select($data_reporte,'a_9_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Auxiliares</label>
                                        <?php echo select($data_reporte,'a_9_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Breaker</label>
                                        <?php echo select($data_reporte,'a_9_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Relay</label>
                                        <?php echo select($data_reporte,'a_9_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Temporizadores</label>
                                        <?php echo select($data_reporte,'a_9_e');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Conexiones</label>
                                        <?php echo select($data_reporte,'a_9_f');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>ACL</label>
                                        <?php echo select($data_reporte,'a_9_g');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Tarjeta com</label>
                                        <?php echo select($data_reporte,'a_9_h');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_9"><?php
                                        echo (!isset($obs_reporte['ab_9'])) ? '' : $obs_reporte['ab_9'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>MAQUINA</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">10</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Nivel de aceite</label>
                                        <?php echo select($data_reporte,'a_10_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Temperatura</label>
                                        <?php echo select($data_reporte,'a_10_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Filtro</label>
                                        <?php echo select($data_reporte,'a_10_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_10"><?php
                                        echo (!isset($obs_reporte['ab_10'])) ? '' : $obs_reporte['ab_10'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">11</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Ruidos</label>
                                        <?php echo select($data_reporte,'a_11_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Vibraciones</label>
                                        <?php echo select($data_reporte,'a_11_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Pintura</label>
                                        <?php echo select($data_reporte,'a_11_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_11"><?php
                                        echo (!isset($obs_reporte['ab_11'])) ? '' : $obs_reporte['ab_11'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">12</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Freno magnético</label>
                                        <?php echo select($data_reporte,'a_12_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Desgaste</label>
                                        <?php echo select($data_reporte,'a_12_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Parada</label>
                                        <?php echo select($data_reporte,'a_12_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_12"><?php
                                        echo (!isset($obs_reporte['ab_12'])) ? '' : $obs_reporte['ab_12'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">13</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Recorrido en parada de emergencia</label>
                                        <?php echo select($data_reporte,'a_13_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_13"><?php
                                        echo (!isset($obs_reporte['ab_13'])) ? '' : $obs_reporte['ab_13'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">14</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Moto eléctrico</label>
                                        <?php echo select($data_reporte,'a_14_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Bloque conex</label>
                                        <?php echo select($data_reporte,'a_14_b');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_14"><?php
                                        echo (!isset($obs_reporte['ab_14'])) ? '' : $obs_reporte['ab_14'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">15</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Piñon</label>
                                        <?php echo select($data_reporte,'a_15_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Cremallera</label>
                                        <?php echo select($data_reporte,'a_15_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Contrarueda</label>
                                        <?php echo select($data_reporte,'a_15_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_15"><?php
                                        echo (!isset($obs_reporte['ab_15'])) ? '' : $obs_reporte['ab_15'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">16</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Roller guide conjunto máquina</label>
                                        <?php echo select($data_reporte,'a_16_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_16"><?php
                                        echo (!isset($obs_reporte['ab_16'])) ? '' : $obs_reporte['ab_16'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">17</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Roller guide cabina</label>
                                        <?php echo select($data_reporte,'a_17_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_17"><?php
                                        echo (!isset($obs_reporte['ab_17'])) ? '' : $obs_reporte['ab_17'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">18</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Soportes de cabina</label>
                                        <?php echo select($data_reporte,'a_18_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_18"><?php
                                        echo (!isset($obs_reporte['ab_18'])) ? '' : $obs_reporte['ab_18'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">19</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Freno centrífugo</label>
                                        <?php echo select($data_reporte,'a_19_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Cables</label>
                                        <?php echo select($data_reporte,'a_19_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Resortes</label>
                                        <?php echo select($data_reporte,'a_19_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Varillas</label>
                                        <?php echo select($data_reporte,'a_19_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Ajuste</label>
                                        <?php echo select($data_reporte,'a_19_e');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Coopling monitor</label>
                                        <?php echo select($data_reporte,'a_19_f');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_19"><?php
                                        echo (!isset($obs_reporte['ab_19'])) ? '' : $obs_reporte['ab_19'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">20</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Bloque seguridad</label>
                                        <?php echo select($data_reporte,'a_20_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Contrarueda</label>
                                        <?php echo select($data_reporte,'a_20_b');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_20"><?php
                                        echo (!isset($obs_reporte['ab_20'])) ? '' : $obs_reporte['ab_20'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">21</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Fecha de vencimiento</label>
                                        <?php echo select($data_reporte,'a_21_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_21"><?php
                                        echo (!isset($obs_reporte['ab_21'])) ? '' : $obs_reporte['ab_21'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">22</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Prueba paracaida fecha</label>
                                        <?php echo select($data_reporte,'a_22_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_22"><?php
                                        echo (!isset($obs_reporte['ab_22'])) ? '' : $obs_reporte['ab_22'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                    </tbody>
                </table>



                <table class="table al-w-50">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col" class="text-left">REVISION</th>
                            <th scope="col" class="text-left">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>RECORRIDO</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">23</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Estado de la cremallera alineación</label>
                                        <?php echo select($data_reporte,'a_23_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_23"><?php
                                        echo (!isset($obs_reporte['ab_23'])) ? '' : $obs_reporte['ab_23'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">24</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Ajuste del mástil de tornillos y tuercas apriete fijación</label>
                                        <?php echo select($data_reporte,'a_24_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_24"><?php
                                        echo (!isset($obs_reporte['ab_24'])) ? '' : $obs_reporte['ab_24'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">25</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Estado del cable viajero</label>
                                        <?php echo select($data_reporte,'a_25_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_25"><?php
                                        echo (!isset($obs_reporte['ab_25'])) ? '' : $obs_reporte['ab_25'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">26</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Soporte del cable viajero y guías</label>
                                        <?php echo select($data_reporte,'a_26_a');?>
                                    </div>
                                    ç<div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_26"><?php
                                        echo (!isset($obs_reporte['ab_26'])) ? '' : $obs_reporte['ab_26'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">27</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Mecanismo de la puerta</label>
                                        <?php echo select($data_reporte,'a_27_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Cam</label>
                                        <?php echo select($data_reporte,'a_27_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Bisagras</label>
                                        <?php echo select($data_reporte,'a_27_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Lock flap</label>
                                        <?php echo select($data_reporte,'a_27_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Swuitch pasillo</label>
                                        <?php echo select($data_reporte,'a_27_e');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_27"><?php
                                        echo (!isset($obs_reporte['ab_27'])) ? '' : $obs_reporte['ab_27'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">28</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Puertas de pasillo estado limpieza</label>
                                        <?php echo select($data_reporte,'a_28_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_28"><?php
                                        echo (!isset($obs_reporte['ab_28'])) ? '' : $obs_reporte['ab_28'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">29</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Buffer superior</label>
                                        <?php echo select($data_reporte,'a_29_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_29"><?php
                                        echo (!isset($obs_reporte['ab_29'])) ? '' : $obs_reporte['ab_29'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">30</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Ajuste de camas de paradas y banderas</label>
                                        <?php echo select($data_reporte,'a_30_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_30"><?php
                                        echo (!isset($obs_reporte['ab_30'])) ? '' : $obs_reporte['ab_30'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">31</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Botoneras de pasillos</label>
                                        <?php echo select($data_reporte,'a_31_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Botones</label>
                                        <?php echo select($data_reporte,'a_31_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Stop</label>
                                        <?php echo select($data_reporte,'a_31_c');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_31"><?php
                                        echo (!isset($obs_reporte['ab_31'])) ? '' : $obs_reporte['ab_31'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>FOSO</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">32</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Stop de foso</label>
                                        <?php echo select($data_reporte,'a_32_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_32"><?php
                                        echo (!isset($obs_reporte['ab_32'])) ? '' : $obs_reporte['ab_32'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">33</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Buffer</label>
                                        <?php echo select($data_reporte,'a_33_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_33"><?php
                                        echo (!isset($obs_reporte['ab_33'])) ? '' : $obs_reporte['ab_33'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">34</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Estado del marco de cabina</label>
                                        <?php echo select($data_reporte,'a_34_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_34"><?php
                                        echo (!isset($obs_reporte['ab_34'])) ? '' : $obs_reporte['ab_34'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">35</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Rolley guías trolley</label>
                                        <?php echo select($data_reporte,'a_35_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Rolley de cable</label>
                                        <?php echo select($data_reporte,'a_35_b');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_35"><?php
                                        echo (!isset($obs_reporte['ab_35'])) ? '' : $obs_reporte['ab_35'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">36</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Distancia del trolley de la base</label>
                                        <?php echo select($data_reporte,'a_36_a');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_36"><?php
                                        echo (!isset($obs_reporte['ab_36'])) ? '' : $obs_reporte['ab_36'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-left">
                                <strong>LUBRICACION</strong>
                            </th>
                            <th scope="col"></th>
                        </tr>
                        <tr>
                            <th scope="col">37</th>
                            <th scope="col" class="text-left">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Cremallera</label>
                                        <?php echo select($data_reporte,'a_37_a');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Roller</label>
                                        <?php echo select($data_reporte,'a_37_b');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Puertas</label>
                                        <?php echo select($data_reporte,'a_37_c');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Inter lock</label>
                                        <?php echo select($data_reporte,'a_37_d');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Dispositivo de seguridad</label>
                                        <?php echo select($data_reporte,'a_37_e');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Mecanismos freno centrifugo</label>
                                        <?php echo select($data_reporte,'a_37_f');?>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Trolley</label>
                                        <?php echo select($data_reporte,'a_37_g');?>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="6" name="ab_37"><?php
                                        echo (!isset($obs_reporte['ab_37'])) ? '' : $obs_reporte['ab_37'];
                                    ?></textarea>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
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
