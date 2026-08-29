<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_photos.php';
session_start();
/**
* Password generator
*
* This is the php index file to load all mini site.
*
* @author LAGC
*/


if (!isset($_GET['id']) || !is_string($_GET['id']) || !ctype_digit($_GET['id']) || (int) $_GET['id'] < 1) {
    header('Location: index.php');
    exit;
}

$ID = (int) $_GET['id'];
if (!isset($_SESSION['reopen_csrf']) || !is_string($_SESSION['reopen_csrf'])) {
    $_SESSION['reopen_csrf'] = bin2hex(random_bytes(32));
}
$reopenCsrf = $_SESSION['reopen_csrf'];
$reportPhotos = [];
$status = [];

$db = db();
$statement = $db->prepare('SELECT * FROM reporte WHERE id = ?');
$statement->bind_param('i', $ID);
$statement->execute();
$data = $statement->get_result();
$statement->close();
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
    <link rel="stylesheet" type="text/css" href="assets/css/style.css?ver=0.61">
    <script type="text/javascript" src="assets/js/jquery-3.2.1.min.js"></script>

</head>
<body>

    <main style="background-image:none">
        <div class="container bg-gris">
            <a href="index.php"><img src="images/logo-internepro.jpg" height="100" style="width:auto;max-width:100%;object-fit:contain" alt="Internepro S.A."></a>

            <?php
            if ($data->num_rows > 0) {
                while ($row = $data->fetch_assoc()){
                    ?>

                    <div class="head-edit" style="text-align: center;">
                        <h2><?php echo $row['title_reporte']?></h2>
                    </div>
                    <div class="head-edit">
                        <h4 style="text-align: center;">INTERNEPRO SA / ALIMAK -PANAMÁ</h4>
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
                        $reportPhotos = report_photo_entries($row['data_reporte']);

                //print_r($data_reporte);

                        $obs_reporte = ($row['obs_reporte'] == null) ? null : json_decode($row['obs_reporte'], true);

                        ?>

                        <div style="margin: 20px 0; text-align:left;">
                            <div class="general-q">
                                <label><strong>
                                    Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                                Inspeccionado y en óptimas condiciones</strong></label>
                                <span><?php echo (!isset($data_reporte['a_0_a'])) ? '' : $data_reporte['a_0_a']; ?></span>
                            </div>
                            <div class="general-q">
                                <label><strong>
                                   Compruebe el funcionamiento del ascensor (aceleración/desaceleración, vibración y ruido).<br>
                               Inspeccionado y necesita realizar acciones</strong></label>
                               <span><?php echo (!isset($data_reporte['a_0_b'])) ? '' : $data_reporte['a_0_b']; ?></span>
                           </div>
                           <div class="general-q">
                               <label><strong>
                                 Pregunte por el comportamiento del ascensor a la persona a cargo.<br>
                             Inspeccionado y en óptimas condiciones</strong></label>
                             <span><?php echo (!isset($data_reporte['a_0_c'])) ? '' : $data_reporte['a_0_c']; ?> </span>
                         </div>

                         <?php echo report_photo_gallery_markup($ID, $reportPhotos); ?>

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
                                                    <span><?php echo (!isset($data_reporte['a_1_a'])) ? '' : $data_reporte['a_1_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_1'])) ? '' : $obs_reporte['ab_1'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_2_a'])) ? '' : $data_reporte['a_2_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_2'])) ? '' : $obs_reporte['ab_2'];?></p>
                                                </div>
                                            </div>
                                            <?php echo report_photo_gallery_group_markup($ID, $reportPhotos, 'section', 'a_2', 'Fotografías de CABINA'); ?>
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
                                                    <span><?php echo (!isset($data_reporte['a_3_a'])) ? '' : $data_reporte['a_3_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Puerta de escotilla</label>
                                                    <span><?php echo (!isset($data_reporte['a_3_b'])) ? '' : $data_reporte['a_3_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Interlocks</label>
                                                    <span><?php echo (!isset($data_reporte['a_3_c'])) ? '' : $data_reporte['a_3_c']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Switch actuador</label>
                                                    <span><?php echo (!isset($data_reporte['a_3_d'])) ? '' : $data_reporte['a_3_d']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Actuador</label>
                                                    <span><?php echo (!isset($data_reporte['a_3_e'])) ? '' : $data_reporte['a_3_e']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>switch de puerta</label>
                                                    <span><?php echo (!isset($data_reporte['a_3_f'])) ? '' : $data_reporte['a_3_f']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_3'])) ? '' : $obs_reporte['ab_3'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_4_a'])) ? '' : $data_reporte['a_4_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Abanicos</label>
                                                    <span><?php echo (!isset($data_reporte['a_4_b'])) ? '' : $data_reporte['a_4_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Alarma</label>
                                                    <span><?php echo (!isset($data_reporte['a_4_c'])) ? '' : $data_reporte['a_4_c']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Display</label>
                                                    <span><?php echo (!isset($data_reporte['a_4_d'])) ? '' : $data_reporte['a_4_d']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Stop</label>
                                                    <span><?php echo (!isset($data_reporte['a_4_e'])) ? '' : $data_reporte['a_4_e']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Intercom</label>
                                                    <span><?php echo (!isset($data_reporte['a_4_f'])) ? '' : $data_reporte['a_4_f']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Botones de llamada</label>
                                                    <span><?php echo (!isset($data_reporte['a_4_g'])) ? '' : $data_reporte['a_4_g']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_4'])) ? '' : $obs_reporte['ab_4'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_5_a'])) ? '' : $data_reporte['a_5_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>SW Final</label>
                                                    <span><?php echo (!isset($data_reporte['a_5_b'])) ? '' : $data_reporte['a_5_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>SW up dw</label>
                                                    <span><?php echo (!isset($data_reporte['a_5_c'])) ? '' : $data_reporte['a_5_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_5'])) ? '' : $obs_reporte['ab_5'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_6_a'])) ? '' : $data_reporte['a_6_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Stop</label>
                                                    <span><?php echo (!isset($data_reporte['a_6_b'])) ? '' : $data_reporte['a_6_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Switch inspección</label>
                                                    <span><?php echo (!isset($data_reporte['a_6_c'])) ? '' : $data_reporte['a_6_c']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>SW emergencia</label>
                                                    <span><?php echo (!isset($data_reporte['a_6_d'])) ? '' : $data_reporte['a_6_d']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Boton up</label>
                                                    <span><?php echo (!isset($data_reporte['a_6_e'])) ? '' : $data_reporte['a_6_e']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Dw</label>
                                                    <span><?php echo (!isset($data_reporte['a_6_f'])) ? '' : $data_reporte['a_6_f']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_6'])) ? '' : $obs_reporte['ab_6'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_7_a'])) ? '' : $data_reporte['a_7_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Tapa de regletas</label>
                                                    <span><?php echo (!isset($data_reporte['a_7_b'])) ? '' : $data_reporte['a_7_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Estado cable viajero</label>
                                                    <span><?php echo (!isset($data_reporte['a_7_c'])) ? '' : $data_reporte['a_7_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_7'])) ? '' : $obs_reporte['ab_7'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_8_a'])) ? '' : $data_reporte['a_8_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>ACL Base</label>
                                                    <span><?php echo (!isset($data_reporte['a_8_b'])) ? '' : $data_reporte['a_8_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Switch</label>
                                                    <span><?php echo (!isset($data_reporte['a_8_c'])) ? '' : $data_reporte['a_8_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_8'])) ? '' : $obs_reporte['ab_8'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_9_a'])) ? '' : $data_reporte['a_9_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Auxiliares</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_b'])) ? '' : $data_reporte['a_9_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Breaker</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_c'])) ? '' : $data_reporte['a_9_c']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Relay</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_d'])) ? '' : $data_reporte['a_9_d']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Temporizadores</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_e'])) ? '' : $data_reporte['a_9_e']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Conexiones</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_f'])) ? '' : $data_reporte['a_9_f']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>ACL</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_g'])) ? '' : $data_reporte['a_9_g']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Tarjeta com</label>
                                                    <span><?php echo (!isset($data_reporte['a_9_h'])) ? '' : $data_reporte['a_9_h']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_9'])) ? '' : $obs_reporte['ab_9'];?></p>
                                                </div>
                                            </div>
                                            <?php echo report_photo_gallery_group_markup($ID, $reportPhotos, 'section', 'a_9', 'Fotografías de CONTROL'); ?>
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
                                                    <span><?php echo (!isset($data_reporte['a_10_a'])) ? '' : $data_reporte['a_10_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Temperatura</label>
                                                    <span><?php echo (!isset($data_reporte['a_10_b'])) ? '' : $data_reporte['a_10_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Filtro</label>
                                                    <span><?php echo (!isset($data_reporte['a_10_c'])) ? '' : $data_reporte['a_10_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_10'])) ? '' : $obs_reporte['ab_10'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_11_a'])) ? '' : $data_reporte['a_11_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Vibraciones</label>
                                                    <span><?php echo (!isset($data_reporte['a_11_b'])) ? '' : $data_reporte['a_11_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Pintura</label>
                                                    <span><?php echo (!isset($data_reporte['a_11_a'])) ? '' : $data_reporte['a_11_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_11'])) ? '' : $obs_reporte['ab_11'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_12_a'])) ? '' : $data_reporte['a_12_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Desgaste</label>
                                                    <span><?php echo (!isset($data_reporte['a_12_b'])) ? '' : $data_reporte['a_12_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Parada</label>
                                                    <span><?php echo (!isset($data_reporte['a_12_c'])) ? '' : $data_reporte['a_12_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_12'])) ? '' : $obs_reporte['ab_12'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_13_a'])) ? '' : $data_reporte['a_13_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_13'])) ? '' : $obs_reporte['ab_13'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_14_a'])) ? '' : $data_reporte['a_14_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Bloque conex</label>
                                                    <span><?php echo (!isset($data_reporte['a_14_b'])) ? '' : $data_reporte['a_14_b']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_14'])) ? '' : $obs_reporte['ab_14'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_15_a'])) ? '' : $data_reporte['a_15_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Cremallera</label>
                                                    <span><?php echo (!isset($data_reporte['a_15_b'])) ? '' : $data_reporte['a_15_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Contrarueda</label>
                                                    <span><?php echo (!isset($data_reporte['a_15_c'])) ? '' : $data_reporte['a_15_c']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_15'])) ? '' : $obs_reporte['ab_15'];?></p>
                                                </div>
                                            </div>
                                            <?php echo report_photo_gallery_group_markup($ID, $reportPhotos, 'section', 'a_15', 'Fotografías de CREMALLERA'); ?>
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
                                                    <span><?php echo (!isset($data_reporte['a_16_a'])) ? '' : $data_reporte['a_16_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_16'])) ? '' : $obs_reporte['ab_16'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_17_a'])) ? '' : $data_reporte['a_17_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_17'])) ? '' : $obs_reporte['ab_17'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_18_a'])) ? '' : $data_reporte['a_18_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_18'])) ? '' : $obs_reporte['ab_18'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_19_a'])) ? '' : $data_reporte['a_19_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Cables</label>
                                                    <span><?php echo (!isset($data_reporte['a_19_b'])) ? '' : $data_reporte['a_19_b']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Resortes</label>
                                                    <span><?php echo (!isset($data_reporte['a_19_c'])) ? '' : $data_reporte['a_19_c']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Varillas</label>
                                                    <span><?php echo (!isset($data_reporte['a_19_d'])) ? '' : $data_reporte['a_19_d']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Ajuste</label>
                                                    <span><?php echo (!isset($data_reporte['a_19_e'])) ? '' : $data_reporte['a_19_e']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Coopling monitor</label>
                                                    <span><?php echo (!isset($data_reporte['a_19_f'])) ? '' : $data_reporte['a_19_f']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_19'])) ? '' : $obs_reporte['ab_19'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_20_a'])) ? '' : $data_reporte['a_20_a']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Contrarueda</label>
                                                    <span><?php echo (!isset($data_reporte['a_20_b'])) ? '' : $data_reporte['a_20_b']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_20'])) ? '' : $obs_reporte['ab_20'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_21_a'])) ? '' : $data_reporte['a_21_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_21'])) ? '' : $obs_reporte['ab_21'];?></p>
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
                                                    <span><?php echo (!isset($data_reporte['a_22_a'])) ? '' : $data_reporte['a_22_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_22'])) ? '' : $obs_reporte['ab_22'];?></p>
                                                </div>
                                            </div>
                                            <?php echo report_photo_gallery_group_markup($ID, $reportPhotos, 'section', 'a_22', 'Fotografías de PARACAÍDAS'); ?>
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
                                                    <span><?php echo (!isset($data_reporte['a_23_a'])) ? '' : $data_reporte['a_23_a']; ?></span>
                                                </div>
                                                <div class="col-md-12">
                                                    <label>Observaciones</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <p><?php
                                                    echo (!isset($obs_reporte['ab_23'])) ? '' : $obs_reporte['ab_23'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_24_a'])) ? '' : $data_reporte['a_24_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_24'])) ? '' : $obs_reporte['ab_24'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_25_a'])) ? '' : $data_reporte['a_25_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_25'])) ? '' : $obs_reporte['ab_25'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_26_a'])) ? '' : $data_reporte['a_26_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_26'])) ? '' : $obs_reporte['ab_26'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_27_a'])) ? '' : $data_reporte['a_27_a']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Cam</label>
                                        <span><?php echo (!isset($data_reporte['a_27_b'])) ? '' : $data_reporte['a_27_b']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Bisagras</label>
                                        <span><?php echo (!isset($data_reporte['a_27_c'])) ? '' : $data_reporte['a_27_c']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Lock flap</label>
                                        <span><?php echo (!isset($data_reporte['a_27_d'])) ? '' : $data_reporte['a_27_d']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Swuitch pasillo</label>
                                        <span><?php echo (!isset($data_reporte['a_27_e'])) ? '' : $data_reporte['a_27_e']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_27'])) ? '' : $obs_reporte['ab_27'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_28_a'])) ? '' : $data_reporte['a_28_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_28'])) ? '' : $obs_reporte['ab_28'];?></p>
                                    </div>
                                </div>
                                <?php echo report_photo_gallery_group_markup($ID, $reportPhotos, 'section', 'a_28', 'Fotografías de PUERTAS DE PASILLO'); ?>
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
                                        <span><?php echo (!isset($data_reporte['a_29_a'])) ? '' : $data_reporte['a_29_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_29'])) ? '' : $obs_reporte['ab_29'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_30_a'])) ? '' : $data_reporte['a_30_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_30'])) ? '' : $obs_reporte['ab_30'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_31_a'])) ? '' : $data_reporte['a_31_a']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Botones</label>
                                        <span><?php echo (!isset($data_reporte['a_31_b'])) ? '' : $data_reporte['a_31_b']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Stop</label>
                                        <span><?php echo (!isset($data_reporte['a_31_c'])) ? '' : $data_reporte['a_31_c']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_31'])) ? '' : $obs_reporte['ab_31'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_32_a'])) ? '' : $data_reporte['a_32_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_32'])) ? '' : $obs_reporte['ab_32'];?></p>
                                    </div>
                                </div>
                                <?php echo report_photo_gallery_group_markup($ID, $reportPhotos, 'section', 'a_32', 'Fotografías de FOSO'); ?>
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
                                        <span><?php echo (!isset($data_reporte['a_33_a'])) ? '' : $data_reporte['a_33_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_33'])) ? '' : $obs_reporte['ab_33'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_34_a'])) ? '' : $data_reporte['a_34_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_33'])) ? '' : $obs_reporte['ab_33'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_35_a'])) ? '' : $data_reporte['a_35_a']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Rolley de cable</label>
                                        <span><?php echo (!isset($data_reporte['a_35_b'])) ? '' : $data_reporte['a_35_b']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_35'])) ? '' : $obs_reporte['ab_35'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_36_a'])) ? '' : $data_reporte['a_36_a']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_36'])) ? '' : $obs_reporte['ab_36'];?></p>
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
                                        <span><?php echo (!isset($data_reporte['a_37_a'])) ? '' : $data_reporte['a_37_a']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Roller</label>
                                        <span><?php echo (!isset($data_reporte['a_37_b'])) ? '' : $data_reporte['a_37_b']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Puertas</label>
                                        <span><?php echo (!isset($data_reporte['a_37_c'])) ? '' : $data_reporte['a_37_c']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Inter lock</label>
                                        <span><?php echo (!isset($data_reporte['a_37_d'])) ? '' : $data_reporte['a_37_d']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Dispositivo de seguridad</label>
                                        <span><?php echo (!isset($data_reporte['a_37_e'])) ? '' : $data_reporte['a_37_e']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Mecanismos freno centrifugo</label>
                                        <span><?php echo (!isset($data_reporte['a_37_f'])) ? '' : $data_reporte['a_37_f']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Trolley</label>
                                        <span><?php echo (!isset($data_reporte['a_37_g'])) ? '' : $data_reporte['a_37_g']; ?></span>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Observaciones</label>
                                    </div>
                                    <div class="col-md-12">
                                        <p><?php
                                        echo (!isset($obs_reporte['ab_37'])) ? '' : $obs_reporte['ab_37'];?></p>
                                    </div>
                                </div>
                            </th>
                            <th scope="col">
                            </th>
                        </tr>
                                </tbody>-
                            </table>
                        </div>

                        <div class="general-q">
                            <label><strong>COMENTARIOS: </strong></label> &nbsp; <?php echo (!isset($obs_reporte['ob_comentario'])) ? '' : $obs_reporte['ob_comentario']; ?>
                        </div>
                        <div class="general-q">
                            <label><strong>RECOMENDACIONES: </strong></label> &nbsp;<?php echo (!isset($obs_reporte['ob_recomendacion'])) ? '' : $obs_reporte['ob_recomendacion']; ?>
                        </div>

                        <?php
                        $status = json_decode($row['state_reporte'], true) ?: [];
                    }
                }
                ?>
            </div>

        </div>

        <div class="head-edit">
            <div class="status">
                <?php
                if (($status['status'] ?? '') == 'close') {
                    $fecha = $status['fecha'] ?? '';
                    $cliente = $status['aprobado'] ?? '';

                    echo 'El Reporte ha sido aprobado: '.$fecha.' por: '.$cliente;
                    ?>
                    <input type="button" class="form-control reopen-report report-reopen-button" name="reopen" value="Volver a PENDIENTE">
                    <?php
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
                    alert('Introduzca el nombre del cliente que aprueba el registro');
                    return;
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
                            window.location.reload();
                        }
                        else {
                            $status.html(data.message);
                        }

                        console.log(data);
                        console.log(XMLHttpRequest);
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        $status.text(response.message || 'No se pudo generar el PDF. El reporte permanece PENDIENTE.');
                    }
                });
            });

            $('.head-edit').on('click', '.reopen-report', function(event) {
                if(event.preventDefault) { event.preventDefault(); }
                if (!confirm('¿Seguro que desea volver este reporte a PENDIENTE? Podrá editarse nuevamente desde la APK.')) {
                    return;
                }

                $status = $('.head-edit .status');
                $status.text('Cambiando reporte a PENDIENTE...');
                $.ajax({
                    url: 'process.php',
                    data: {
                        type: 'reopen',
                        id: <?php echo $ID; ?>,
                        csrf_token: <?php echo json_encode($reopenCsrf); ?>
                    },
                    type: 'post',
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 200) {
                            window.location.reload();
                        } else {
                            $status.text(data.message || 'No se pudo cambiar el estado del reporte.');
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        $status.text(response.message || 'No se pudo cambiar el estado del reporte.');
                    }
                });
            });

        });

    })(jQuery);</script>
    <script src="assets/js/report-gallery.js?ver=1.0"></script>
    <script src="assets/css/bootstrap5/bootstrap.min.js?v=0.4"></script>
</body>
</html>
