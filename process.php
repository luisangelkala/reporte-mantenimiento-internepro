<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_photos.php';
require_once __DIR__ . '/includes/report_pdf.php';
session_start();
/**
 * Password generator
 *
 * This is the php index file to load all mini site.
 *
 *@author LAGC
 */

function report_list(){
	$sql = "SELECT * FROM reporte ORDER BY created_at DESC";

	$db = db();

	$data = $db->query($sql);

   	mysqli_close($db);

   	$table = '';

   	if ($data->num_rows > 0) {
        while ($row = $data->fetch_assoc()){

        $state = json_decode($row['state_reporte'], true);//json_encode(['status' => 'open', 'aprobado' => '', 'fecha' => '', 'reporte' => $reporte])

        $status = (($state['status'] ?? '') == 'close') ?
        '<a href="#" style="margin:0 5px"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="red" class="bi bi-check-square-fill" viewBox="0 0 16 16"><path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm10.03 4.97a.75.75 0 0 1 .011 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.75.75 0 0 1 1.08-.022z"/></svg></a>'
        :
        ''
        ;
        $red = (($state['status'] ?? '') == 'close') ? 'red' : '';

        $alimak = (($state['reporte'] ?? '') == 'alimak') ? '_alimak' : '';
        $pdfUrl = report_pdf_active_url((int) $row['id'], is_array($state) ? $state : []);
        if ($pdfUrl !== null) {
            $escapedPdfUrl = htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8');
            $whatsappUrl = 'https://api.whatsapp.com/send?text=' . rawurlencode('Reporte de mantenimiento #' . $row['id'] . ': ' . $pdfUrl);
            $pdfActions = '<a href="' . $escapedPdfUrl . '" target="_blank" rel="noopener" title="Abrir PDF" aria-label="Abrir PDF" style="margin:0 5px"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>'
                . '<a href="' . htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" data-action="share/whatsapp/share" class="url" title="Enviar PDF por WhatsApp" aria-label="Enviar PDF por WhatsApp" style="margin:0 5px"><i class="fa fa-whatsapp" aria-hidden="true"></i></a>';
        } else {
            $pdfActions = '<span title="PDF disponible al aprobar" aria-label="PDF no disponible" style="margin:0 5px;color:#aaa"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></span>'
                . '<span title="WhatsApp disponible al aprobar" aria-label="WhatsApp no disponible" style="margin:0 5px;color:#aaa"><i class="fa fa-whatsapp" aria-hidden="true"></i></span>';
        }

   		$table .= '<tr class="'. $red .'">
                   <th scope="row">'. $row['id'] .'</th>
                   <td class="text-left">'.$status.' '. $row['title_reporte'] .'</td>
                   <td>
                   <div>
                   '.$pdfActions.'
                   <a href="view'.$alimak.'.php?id='. $row['id'] .'" class="view" style="margin:0 5px"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16"> <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg></a>
                   <a href="#" data-filter="'. $row['id'] .'" class="delete" style="margin:0 5px"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/></svg></a>
                   </div>
                   </td>
                   </tr>';
   		}
    }

   	return $table;
}

function report_create($reporte){
	$status = json_encode(['status' => 'open', 'aprobado' => '', 'fecha' => '', 'reporte' => $reporte]);

	$sql = "INSERT INTO `reporte`(`title_reporte`,`state_reporte`,`created_at`) VALUES ('Añadir titulo del reporte...', '$status', NOW())";

	$db = db();

	$data = $db->query($sql);

   	mysqli_close($db);
   	return $data;
}

function report_delete($id){
    if (!ctype_digit((string)$id) || (int)$id < 1) {
        return 'invalid';
    }
    $db = db();
    $statement = $db->prepare('SELECT state_reporte FROM reporte WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $report = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$report) {
        mysqli_close($db);
        return 'not_found';
    }
    $state = json_decode($report['state_reporte'], true) ?: [];
    if (($state['status'] ?? '') === 'close') {
        mysqli_close($db);
        return 'approved';
    }
    $statement = $db->prepare('DELETE FROM reporte WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $deleted = $statement->affected_rows === 1;
    $statement->close();
    mysqli_close($db);
    return $deleted ? 'deleted' : 'not_found';
}

function report_insert($id, $reporte){

	$status = json_encode(['status' => 'open', 'aprobado' => '', 'fecha' => '', 'reporte' => $reporte]);

	$title = htmlentities($_POST['title'], ENT_QUOTES,'UTF-8');
	$cliente = htmlentities($_POST["cliente"], ENT_QUOTES,'UTF-8');
	$fecha = $_POST["fecha"];
	$equipo = htmlentities($_POST["equipo"], ENT_QUOTES,'UTF-8');
	$tecnico = htmlentities($_POST["tecnico"], ENT_QUOTES,'UTF-8');


	if($reporte == 'elevador'){
		//data
		$s_0_a = $_POST['s_0_a'];
		$s_0_b = $_POST['s_0_b'];
		$s_0_c = $_POST['s_0_c'];

		$s_1_a = $_POST['s_1_a'];
		$s_1_b = $_POST['s_1_b'];
		$s_1_c = $_POST['s_1_c'];
		$s_1_d = $_POST['s_1_d'];
		$s_1_e = $_POST['s_1_e'];
		$s_1_f = $_POST['s_1_f'];
		$s_1_g = $_POST['s_1_g'];
		$ob_1 = htmlentities($_POST['ob_1'], ENT_QUOTES,'UTF-8');

		$s_2_a = $_POST['s_2_a'];
		$s_2_b = $_POST['s_2_b'];
		$s_2_c = $_POST['s_2_c'];
		$s_2_d = $_POST['s_2_d'];
		$s_2_e = $_POST['s_2_e'];
		$s_2_f = $_POST['s_2_f'];
		$ob_2 = htmlentities($_POST['ob_2'], ENT_QUOTES,'UTF-8');

		$s_3_a = $_POST['s_3_a'];
		$s_3_b = $_POST['s_3_b'];
		$s_3_c = $_POST['s_3_c'];
		$s_3_d = $_POST['s_3_d'];
		$s_3_e = $_POST['s_3_e'];
		$ob_3 = htmlentities($_POST['ob_3'], ENT_QUOTES,'UTF-8');

		$s_4_a = $_POST['s_4_a'];
		$s_4_b = $_POST['s_4_b'];
		$s_4_c = $_POST['s_4_c'];
		$s_4_d = $_POST['s_4_d'];
		$ob_4 = htmlentities($_POST['ob_4'], ENT_QUOTES,'UTF-8');

		$s_5_a = $_POST['s_5_a'];
		$s_5_b = $_POST['s_5_b'];
		$s_5_c = $_POST['s_5_c'];
		$s_5_d = $_POST['s_5_d'];
		$s_5_e = $_POST['s_5_e'];
		$s_5_f = $_POST['s_5_f'];
		$s_5_g = $_POST['s_5_g'];
		$s_5_h = $_POST['s_5_h'];
		$ob_5 = htmlentities($_POST['ob_5'], ENT_QUOTES,'UTF-8');

		$s_6_a = $_POST['s_6_a'];
		$s_6_b = $_POST['s_6_b'];
		$s_6_c = $_POST['s_6_c'];
		$s_6_d = $_POST['s_6_d'];
		$s_6_e = $_POST['s_6_e'];
		$s_6_f = $_POST['s_6_f'];
		$s_6_g = $_POST['s_6_g'];
		$s_6_h = $_POST['s_6_h'];
		$s_6_i = $_POST['s_6_i'];
		$ob_6 = htmlentities($_POST['ob_6'], ENT_QUOTES,'UTF-8');

		$s_7_a = $_POST['s_7_a'];
		$s_7_b = $_POST['s_7_b'];
		$s_7_c = $_POST['s_7_c'];
		$s_7_d = $_POST['s_7_d'];
		$s_7_e = $_POST['s_7_e'];
		$s_7_f = $_POST['s_7_f'];
		$ob_7 = htmlentities($_POST['ob_7'], ENT_QUOTES,'UTF-8');

		$s_8_a = $_POST['s_8_a'];
		$s_8_b = $_POST['s_8_b'];
		$s_8_c = $_POST['s_8_c'];
		$s_8_d = $_POST['s_8_d'];
		$s_8_e = $_POST['s_8_e'];
		$s_8_f = $_POST['s_8_f'];
		$ob_8 = htmlentities($_POST['ob_8'], ENT_QUOTES,'UTF-8');

		$s_9_a = $_POST['s_9_a'];
		$s_9_b = $_POST['s_9_b'];
		$s_9_c = $_POST['s_9_c'];
		$s_9_d = $_POST['s_9_d'];
		$s_9_e = $_POST['s_9_e'];
		$ob_9 = htmlentities($_POST['ob_9'], ENT_QUOTES,'UTF-8');

		$s_10_a = $_POST['s_10_a'];
		$s_10_b = $_POST['s_10_b'];
		$s_10_c = $_POST['s_10_c'];
		$ob_10 = htmlentities($_POST['ob_10'], ENT_QUOTES,'UTF-8');

		$s_11_a = $_POST['s_11_a'];
		$s_11_b = $_POST['s_11_b'];
		$s_11_c = $_POST['s_11_c'];
		$s_11_d = $_POST['s_11_d'];
		$s_11_e = $_POST['s_11_e'];
		$ob_11 = htmlentities($_POST['ob_11'], ENT_QUOTES,'UTF-8');

		$s_12_a = $_POST['s_12_a'];
		$s_12_b = $_POST['s_12_b'];
		$s_12_c = $_POST['s_12_c'];
		$s_12_d = $_POST['s_12_d'];
		$s_12_e = $_POST['s_12_e'];
		$ob_12 = htmlentities($_POST['ob_12'], ENT_QUOTES,'UTF-8');

		$s_13_a = $_POST['s_13_a'];
		$s_13_b = $_POST['s_13_b'];
		$s_13_c = $_POST['s_13_c'];
		$s_13_d = $_POST['s_13_d'];
		$s_13_e = $_POST['s_13_e'];
		$s_13_f = $_POST['s_13_f'];
		$ob_13 = htmlentities($_POST['ob_13'], ENT_QUOTES,'UTF-8');

		$s_14_a = $_POST['s_14_a'];
		$s_14_b = $_POST['s_14_b'];
		$s_14_c = $_POST['s_14_c'];
		$s_14_d = $_POST['s_14_d'];
		$s_14_e = $_POST['s_14_e'];
		$ob_14 = htmlentities($_POST['ob_14'], ENT_QUOTES,'UTF-8');

		$s_15_a = $_POST['s_15_a'];
		$s_15_b = $_POST['s_15_b'];
		$s_15_c = $_POST['s_15_c'];
		$s_15_d = $_POST['s_15_d'];
		$ob_15 = htmlentities($_POST['ob_15'], ENT_QUOTES,'UTF-8');

		$ob_comentario = htmlentities($_POST['ob_comentario'], ENT_QUOTES,'UTF-8');
		$ob_recomendacion = htmlentities($_POST['ob_recomendacion'], ENT_QUOTES,'UTF-8');

		$data_reporte = json_encode(['s_0_a' => $s_0_a, 's_0_b' => $s_0_b, 's_0_c' => $s_0_c, 's_1_a' => $s_1_a, 's_1_b' => $s_1_b, 's_1_c' => $s_1_c, 's_1_d' => $s_1_d, 's_1_e' => $s_1_e, 's_1_f' => $s_1_f, 's_1_g' => $s_1_g, 's_2_a' => $s_2_a, 's_2_b' => $s_2_b, 's_2_c' => $s_2_c, 's_2_d' => $s_2_d, 's_2_e' => $s_2_e, 's_2_f' => $s_2_f, 's_3_a' => $s_3_a, 's_3_b' => $s_3_b, 's_3_c' => $s_3_c, 's_3_d' => $s_3_d, 's_3_e' => $s_3_e, 's_4_a' => $s_4_a, 's_4_b' => $s_4_b, 's_4_c' => $s_4_c, 's_4_d' => $s_4_d, 's_5_a' => $s_5_a, 's_5_b' => $s_5_b, 's_5_c' => $s_5_c, 's_5_d' => $s_5_d, 's_5_e' => $s_5_e, 's_5_f' => $s_5_f, 's_5_g' => $s_5_g, 's_5_h' => $s_5_h, 's_6_a' => $s_6_a, 's_6_b' => $s_6_b, 's_6_c' => $s_6_c, 's_6_d' => $s_6_d, 's_6_e' => $s_6_e, 's_6_f' => $s_6_f, 's_6_g' => $s_6_g, 's_6_h' => $s_6_h, 's_6_i' => $s_6_i, 's_7_a' => $s_7_a, 's_7_b' => $s_7_b, 's_7_c' => $s_7_c, 's_7_d' => $s_7_d, 's_7_e' => $s_7_e, 's_7_f' => $s_7_f, 's_8_a' => $s_8_a, 's_8_b' => $s_8_b, 's_8_c' => $s_8_c, 's_8_d' => $s_8_d, 's_8_e' => $s_8_e, 's_8_f' => $s_8_f, 's_9_a' => $s_9_a, 's_9_b' => $s_9_b, 's_9_c' => $s_9_c, 's_9_d' => $s_9_d, 's_9_e' => $s_9_e, 's_10_a' => $s_10_a, 's_10_b' => $s_10_b, 's_10_c' => $s_10_c, 's_11_a' => $s_11_a, 's_11_b' => $s_11_b, 's_11_c' => $s_11_c, 's_11_d' => $s_11_d, 's_11_e' => $s_11_e, 's_12_a' => $s_12_a, 's_12_b' => $s_12_b, 's_12_c' => $s_12_c, 's_12_d' => $s_12_d, 's_12_e' => $s_12_e, 's_13_a' => $s_13_a, 's_13_b' => $s_13_b, 's_13_c' => $s_13_c, 's_13_d' => $s_13_d, 's_13_e' => $s_13_e, 's_13_f' => $s_13_f, 's_14_a' => $s_14_a, 's_14_b' => $s_14_b, 's_14_c' => $s_14_c, 's_14_d' => $s_14_d, 's_14_e' => $s_14_e, 's_15_a' => $s_15_a, 's_15_b' => $s_15_b, 's_15_c' => $s_15_c, 's_15_d' => $s_15_d]);

		// Obervaciones
		$obs_reporte = json_encode(['ob_1' => $ob_1, 'ob_2' => $ob_2, 'ob_3' => $ob_3, 'ob_4' => $ob_4, 'ob_5' => $ob_5, 'ob_6' => $ob_6, 'ob_7' => $ob_7, 'ob_8' => $ob_8, 'ob_9' => $ob_9, 'ob_10' => $ob_10, 'ob_11' => $ob_11, 'ob_12' => $ob_12, 'ob_13' => $ob_13, 'ob_14' => $ob_14, 'ob_15' => $ob_15, 'ob_comentario' => $ob_comentario, 'ob_recomendacion' => $ob_recomendacion]);
	}
	elseif($reporte == 'alimak'){
		$a_0_a = $_POST['a_0_a'];
		$a_0_b = $_POST['a_0_b'];
		$a_0_c = $_POST['a_0_c'];

		$a_1_a = $_POST['a_1_a'];
		$ab_1 = htmlentities($_POST['ab_1'], ENT_QUOTES,'UTF-8');

		$a_2_a = $_POST['a_2_a'];
		$ab_2 = htmlentities($_POST['ab_2'], ENT_QUOTES,'UTF-8');

		$a_3_a = $_POST['a_3_a'];
		$a_3_b = $_POST['a_3_b'];
		$a_3_c = $_POST['a_3_c'];
		$a_3_d = $_POST['a_3_d'];
		$a_3_e = $_POST['a_3_e'];
		$a_3_f = $_POST['a_3_f'];
		$ab_3 = htmlentities($_POST['ab_3'], ENT_QUOTES,'UTF-8');

		$a_4_a = $_POST['a_4_a'];
		$a_4_b = $_POST['a_4_b'];
		$a_4_c = $_POST['a_4_c'];
		$a_4_d = $_POST['a_4_d'];
		$a_4_e = $_POST['a_4_e'];
		$a_4_f = $_POST['a_4_f'];
		$a_4_g = $_POST['a_4_g'];
		$ab_4 = htmlentities($_POST['ab_4'], ENT_QUOTES,'UTF-8');

		$a_5_a = $_POST['a_5_a'];
		$a_5_b = $_POST['a_5_b'];
		$a_5_c = $_POST['a_5_c'];
		$ab_5 = htmlentities($_POST['ab_5'], ENT_QUOTES,'UTF-8');

		$a_6_a = $_POST['a_6_a'];
		$a_6_b = $_POST['a_6_b'];
		$a_6_c = $_POST['a_6_c'];
		$a_6_d = $_POST['a_6_d'];
		$a_6_e = $_POST['a_6_e'];
		$a_6_f = $_POST['a_6_f'];
		$ab_6 = htmlentities($_POST['ab_6'], ENT_QUOTES,'UTF-8');

		$a_7_a = $_POST['a_7_a'];
		$a_7_b = $_POST['a_7_b'];
		$a_7_c = $_POST['a_7_c'];
		$ab_7 = htmlentities($_POST['ab_7'], ENT_QUOTES,'UTF-8');

		$a_8_a = $_POST['a_8_a'];
		$a_8_b = $_POST['a_8_b'];
		$a_8_c = $_POST['a_8_c'];
		$ab_8 = htmlentities($_POST['ab_8'], ENT_QUOTES,'UTF-8');

		$a_9_a = $_POST['a_9_a'];
		$a_9_b = $_POST['a_9_b'];
		$a_9_c = $_POST['a_9_c'];
		$a_9_d = $_POST['a_9_d'];
		$a_9_e = $_POST['a_9_e'];
		$a_9_f = $_POST['a_9_f'];
		$a_9_g = $_POST['a_9_g'];
		$a_9_h = $_POST['a_9_h'];
		$ab_9 = htmlentities($_POST['ab_9'], ENT_QUOTES,'UTF-8');

		$a_10_a = $_POST['a_10_a'];
		$a_10_b = $_POST['a_10_b'];
		$a_10_c = $_POST['a_10_c'];
		$ab_10 = htmlentities($_POST['ab_10'], ENT_QUOTES,'UTF-8');

		$a_11_a = $_POST['a_11_a'];
		$a_11_b = $_POST['a_11_b'];
		$a_11_c = $_POST['a_11_c'];
		$ab_11 = htmlentities($_POST['ab_11'], ENT_QUOTES,'UTF-8');

		$a_12_a = $_POST['a_12_a'];
		$a_12_b = $_POST['a_12_b'];
		$a_12_c = $_POST['a_12_c'];
		$ab_12 = htmlentities($_POST['ab_12'], ENT_QUOTES,'UTF-8');

		$a_13_a = $_POST['a_13_a'];
		$ab_13 = htmlentities($_POST['ab_13'], ENT_QUOTES,'UTF-8');

		$a_14_a = $_POST['a_14_a'];
		$a_14_b = $_POST['a_14_b'];
		$ab_14 = htmlentities($_POST['ab_14'], ENT_QUOTES,'UTF-8');

		$a_15_a = $_POST['a_15_a'];
		$a_15_b = $_POST['a_15_b'];
		$a_15_c = $_POST['a_15_c'];
		$ab_15 = htmlentities($_POST['ab_15'], ENT_QUOTES,'UTF-8');

		$a_16_a = $_POST['a_16_a'];
		$ab_16 = htmlentities($_POST['ab_16'], ENT_QUOTES,'UTF-8');

		$a_17_a = $_POST['a_17_a'];
		$ab_17 = htmlentities($_POST['ab_17'], ENT_QUOTES,'UTF-8');

		$a_18_a = $_POST['a_18_a'];
		$ab_18 = htmlentities($_POST['ab_18'], ENT_QUOTES,'UTF-8');

		$a_19_a = $_POST['a_19_a'];
		$a_19_b = $_POST['a_19_b'];
		$a_19_c = $_POST['a_19_c'];
		$a_19_d = $_POST['a_19_d'];
		$a_19_e = $_POST['a_19_e'];
		$a_19_f = $_POST['a_19_f'];
		$ab_19 = htmlentities($_POST['ab_19'], ENT_QUOTES,'UTF-8');

		$a_20_a = $_POST['a_20_a'];
		$a_20_b = $_POST['a_20_b'];
		$ab_20 = htmlentities($_POST['ab_20'], ENT_QUOTES,'UTF-8');

		$a_21_a = $_POST['a_21_a'];
		$ab_21 = htmlentities($_POST['ab_21'], ENT_QUOTES,'UTF-8');

		$a_22_a = $_POST['a_22_a'];
		$ab_22 = htmlentities($_POST['ab_22'], ENT_QUOTES,'UTF-8');

		$a_23_a = $_POST['a_23_a'];
		$ab_23 = htmlentities($_POST['ab_23'], ENT_QUOTES,'UTF-8');

		$a_24_a = $_POST['a_24_a'];
		$ab_24 = htmlentities($_POST['ab_24'], ENT_QUOTES,'UTF-8');

		$a_25_a = $_POST['a_25_a'];
		$ab_25 = htmlentities($_POST['ab_25'], ENT_QUOTES,'UTF-8');

		$a_26_a = $_POST['a_26_a'];
		$ab_26 = htmlentities($_POST['ab_26'], ENT_QUOTES,'UTF-8');

		$a_27_a = $_POST['a_27_a'];
		$a_27_b = $_POST['a_27_b'];
		$a_27_c = $_POST['a_27_c'];
		$a_27_d = $_POST['a_27_d'];
		$a_27_e = $_POST['a_27_e'];
		$ab_27 = htmlentities($_POST['ab_27'], ENT_QUOTES,'UTF-8');

		$a_28_a = $_POST['a_28_a'];
		$ab_28 = htmlentities($_POST['ab_28'], ENT_QUOTES,'UTF-8');

		$a_29_a = $_POST['a_29_a'];
		$ab_29 = htmlentities($_POST['ab_29'], ENT_QUOTES,'UTF-8');

		$a_30_a = $_POST['a_30_a'];
		$ab_30 = htmlentities($_POST['ab_30'], ENT_QUOTES,'UTF-8');

		$a_31_a = $_POST['a_31_a'];
		$a_31_b = $_POST['a_31_b'];
		$a_31_c = $_POST['a_31_c'];
		$ab_31 = htmlentities($_POST['ab_31'], ENT_QUOTES,'UTF-8');

		$a_32_a = $_POST['a_32_a'];
		$ab_32 = htmlentities($_POST['ab_32'], ENT_QUOTES,'UTF-8');

		$a_33_a = $_POST['a_33_a'];
		$ab_33 = htmlentities($_POST['ab_33'], ENT_QUOTES,'UTF-8');

		$a_34_a = $_POST['a_34_a'];
		$ab_34 = htmlentities($_POST['ab_34'], ENT_QUOTES,'UTF-8');

		$a_35_a = $_POST['a_35_a'];
		$a_35_b = $_POST['a_35_b'];
		$ab_35 = htmlentities($_POST['ab_35'], ENT_QUOTES,'UTF-8');

		$a_36_a = $_POST['a_36_a'];
		$ab_36 = htmlentities($_POST['ab_36'], ENT_QUOTES,'UTF-8');

		$a_37_a = $_POST['a_37_a'];
		$a_37_b = $_POST['a_37_b'];
		$a_37_c = $_POST['a_37_c'];
		$a_37_d = $_POST['a_37_d'];
		$a_37_e = $_POST['a_37_e'];
		$a_37_f = $_POST['a_37_f'];
		$a_37_g = $_POST['a_37_g'];
		$ab_37 = htmlentities($_POST['ab_37'], ENT_QUOTES,'UTF-8');



		$data_reporte = json_encode(['a_0_a' => $a_0_a, 'a_0_b' => $a_0_b, 'a_0_c' => $a_0_c, 'a_1_a' => $a_1_a, 'a_2_a' => $a_2_a, 'a_3_a' => $a_3_a, 'a_3_b' => $a_3_b, 'a_3_c' => $a_3_c, 'a_3_d' => $a_3_d, 'a_3_e' => $a_3_e, 'a_3_f' => $a_3_f, 'a_4_a' => $a_4_a, 'a_4_b' => $a_4_b, 'a_4_c' => $a_4_c, 'a_4_d' => $a_4_d, 'a_4_e' => $a_4_e, 'a_4_f' => $a_4_f, 'a_4_g' => $a_4_g, 'a_5_a' => $a_5_a, 'a_5_b' => $a_5_b, 'a_5_c' => $a_5_c, 'a_6_a' => $a_6_a, 'a_6_b' => $a_6_b, 'a_6_c' => $a_6_c, 'a_6_d' => $a_6_d, 'a_6_e' => $a_6_e, 'a_6_f' => $a_6_f, 'a_7_a' => $a_7_a, 'a_7_b' => $a_7_b, 'a_7_c' => $a_7_c, 'a_8_a' => $a_8_a, 'a_8_b' => $a_8_b, 'a_8_c' => $a_8_c, 'a_9_a' => $a_9_a, 'a_9_b' => $a_9_b, 'a_9_c' => $a_9_c, 'a_9_d' => $a_9_d, 'a_9_e' => $a_9_e, 'a_9_f' => $a_9_f, 'a_9_g' => $a_9_g, 'a_9_h' => $a_9_h, 'a_10_a' => $a_10_a, 'a_10_b' => $a_10_b, 'a_10_c' => $a_10_c, 'a_11_a' => $a_11_a, 'a_11_b' => $a_11_b, 'a_11_c' => $a_11_c, 'a_12_a' => $a_12_a, 'a_12_b' => $a_12_b, 'a_12_c' => $a_12_c, 'a_13_a' => $a_13_a, 'a_14_a' => $a_14_a, 'a_14_b' => $a_14_b, 'a_15_a' => $a_15_a, 'a_15_b' => $a_15_b, 'a_15_c' => $a_15_c, 'a_16_a' => $a_16_a, 'a_17_a' => $a_17_a, 'a_18_a' => $a_18_a, 'a_19_a' => $a_19_a, 'a_19_b' => $a_19_b, 'a_19_c' => $a_19_c, 'a_19_d' => $a_19_d, 'a_19_e' => $a_19_e, 'a_19_f' => $a_19_f, 'a_20_a' => $a_20_a, 'a_20_b' => $a_20_b, 'a_21_a' => $a_21_a, 'a_22_a' => $a_23_a, 'a_23_a' => $a_23_a, 'a_24_a' => $a_24_a, 'a_25_a' => $a_25_a, 'a_26_a' => $a_26_a, 'a_27_a' => $a_27_a, 'a_27_b' => $a_27_b, 'a_27_c' => $a_27_c, 'a_27_d' => $a_27_d, 'a_27_e' => $a_27_e, 'a_28_a' => $a_28_a, 'a_29_a' => $a_29_a, 'a_30_a' => $a_30_a, 'a_31_a' => $a_31_a, 'a_31_b' => $a_31_b, 'a_31_c' => $a_31_c, 'a_32_a' => $a_32_a, 'a_33_a' => $a_33_a, 'a_34_a' => $a_34_a, 'a_35_a' => $a_35_a, 'a_35_b' => $a_35_b, 'a_36_a' => $a_36_a, 'a_37_a' => $a_37_a, 'a_37_b' => $a_37_b, 'a_37_c' => $a_37_c, 'a_37_d' => $a_37_d, 'a_37_e' => $a_37_e, 'a_37_f' => $a_37_f, 'a_37_g' => $a_37_g]);

		$ob_comentario = htmlentities($_POST['ob_comentario'], ENT_QUOTES,'UTF-8');
		$ob_recomendacion = htmlentities($_POST['ob_recomendacion'], ENT_QUOTES,'UTF-8');

		// Obervaciones
		$obs_reporte = json_encode(['ab_1' => $ab_1, 'ab_2' => $ab_2, 'ab_3' => $ab_3, 'ab_4' => $ab_4, 'ab_5' => $ab_5, 'ab_6' => $ab_6, 'ab_7' => $ab_7, 'ab_8' => $ab_8, 'ab_9' => $ab_9, 'ab_10' => $ab_10, 'ab_11' => $ab_11, 'ab_12' => $ab_12, 'ab_13' => $ab_13, 'ab_14' => $ab_14, 'ab_15' => $ab_15,'ab_16' => $ab_16, 'ab_17' => $ab_17, 'ab_18' => $ab_18, 'ab_19' => $ab_19, 'ab_20' => $ab_20, 'ab_21' => $ab_21, 'ab_22' => $ab_22, 'ab_23' => $ab_23, 'ab_24' => $ab_24, 'ab_25' => $ab_25, 'ab_26' => $ab_26, 'ab_27' => $ab_27, 'ab_28' => $ab_28, 'ab_29' => $ab_29, 'ab_30' => $ab_30, 'ab_31' => $ab_31, 'ab_32' => $ab_32, 'ab_32' => $ab_32, 'ab_33' => $ab_33, 'ab_34' => $ab_34, 'ab_35' => $ab_35, 'ab_36' => $ab_36, 'ab_37' => $ab_37, 'ob_comentario' => $ob_comentario, 'ob_recomendacion' => $ob_recomendacion]);
	}

	$sql = "UPDATE `reporte` SET `title_reporte`='$title', `state_reporte`='$status', `cliente_reporte`='$cliente',`fecha_reporte`='$fecha',`equipo_reporte`='$equipo',`tecnico_reporte`='$tecnico',`data_reporte`='$data_reporte',`obs_reporte`='$obs_reporte' WHERE `id`='$id'";

	$db = db();

	$data = $db->query($sql);

   	mysqli_close($db);
   	return $data;
}

function report_type_from_record(array $state, $dataReporte){
	if (($state['reporte'] ?? '') === 'alimak') {
		return 'alimak';
	}
	if (($state['reporte'] ?? '') === 'elevador') {
		return 'elevador';
	}
	$data = is_string($dataReporte) ? json_decode($dataReporte, true) : $dataReporte;
	if (is_array($data)) {
		foreach (array_keys($data) as $key) {
			if (is_string($key) && strpos($key, 'a_') === 0) {
				return 'alimak';
			}
		}
	}
	return 'elevador';
}

function report_aprobar($id){
	if (!ctype_digit((string) $id) || (int) $id < 1) {
		return ['result' => 'invalid'];
	}
	$db = db();
	$reportId = (int) $id;
	$cliente = trim((string) ($_POST['cliente'] ?? ''));
	try {
		$approved = report_approve_with_pdf($db, $reportId, $cliente);
		mysqli_close($db);
		return ['result' => 'approved', 'state' => $approved['state']];
	} catch (InvalidArgumentException $error) {
		mysqli_close($db);
		return ['result' => 'invalid'];
	} catch (OutOfBoundsException $error) {
		mysqli_close($db);
		return ['result' => 'not_found'];
	} catch (DomainException $error) {
		mysqli_close($db);
		return ['result' => 'already_approved'];
	} catch (Throwable $error) {
		error_log('No se pudo aprobar/generar PDF del reporte ' . $reportId . ': ' . $error->getMessage());
		mysqli_close($db);
		return ['result' => 'pdf_error'];
	}
}

function report_reopen($id, $csrfToken){
	if (!ctype_digit((string) $id) || (int) $id < 1) {
		return 'invalid';
	}
	$sessionToken = $_SESSION['reopen_csrf'] ?? '';
	if (!is_string($csrfToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
		return 'forbidden';
	}

	$db = db();
	$db->begin_transaction();
	$reportId = (int) $id;
	$statement = $db->prepare('SELECT state_reporte, data_reporte FROM reporte WHERE id = ? FOR UPDATE');
	$statement->bind_param('i', $reportId);
	$statement->execute();
	$report = $statement->get_result()->fetch_assoc() ?: null;
	$statement->close();

	if ($report === null) {
		$db->rollback();
		mysqli_close($db);
		return 'not_found';
	}

	$state = json_decode($report['state_reporte'], true) ?: [];
	if (($state['status'] ?? '') !== 'close') {
		$db->rollback();
		mysqli_close($db);
		return 'not_approved';
	}

	$state['status'] = 'open';
	$state['aprobado'] = '';
	$state['fecha'] = '';
	$state['reporte'] = report_type_from_record($state, $report['data_reporte'] ?? null);
	$state = report_pdf_invalidate_state($state);
	$encodedState = json_encode($state, JSON_UNESCAPED_UNICODE);
	$statement = $db->prepare('UPDATE reporte SET state_reporte = ?, updated_at = NOW() WHERE id = ?');
	$statement->bind_param('si', $encodedState, $reportId);
	$statement->execute();
	$updated = $statement->affected_rows === 1;
	$statement->close();

	if ($updated) {
		$db->commit();
	} else {
		$db->rollback();
	}
	unset($_SESSION['reopen_csrf']);
	mysqli_close($db);
	return $updated ? 'reopened' : 'error';
}

/**
 * Receive data
 */

$data = [];
$responseStatus = 200;

$type = isset($_POST["type"]) ? $_POST["type"] : 'list';
$reporte = isset($_POST["reporte"]) ? $_POST["reporte"] : 'elevador';

if ($type == 'list'){
	$message = 'Listado de reportes cargado.';
	$content = report_list();
}

if ($type == 'create'){
	report_create($reporte);
	$message = 'Reporte creado.';
	$content = '';
}

if ($type == 'delete'){
	$id = isset($_POST["id"]) ? $_POST["id"] : '';
	$deleted = report_delete($id);
	$message = ($deleted === 'deleted') ? 'El Reporte ha sido borrado.' : (($deleted === 'approved') ? 'Un reporte aprobado no puede ser borrado.' : 'No se pudo borrar el reporte.');
	$content = '';
}

if ($type == 'insert'){
	$id = $_POST["id"];
	report_insert($id, $reporte);
	$page = ($reporte == 'alimak') ? '_alimak' : '';
	header("Location: view".$page.".php?id=".$id);
}

if ($type == 'aprobando'){
	$id = $_POST["id"];
	$approval = report_aprobar($id);
	if (($approval['result'] ?? '') === 'approved') {
		$state = $approval['state'];
		$message = 'El Reporte ha sido aprobado: '.$state['fecha'].' por: '.$state['aprobado'].'. PDF generado correctamente.';
		$content = '';
	} elseif (($approval['result'] ?? '') === 'not_found') {
		$responseStatus = 404;
		$message = 'El reporte no existe.';
		$content = '';
	} elseif (($approval['result'] ?? '') === 'already_approved') {
		$responseStatus = 409;
		$message = 'El reporte ya esta aprobado.';
		$content = '';
	} elseif (($approval['result'] ?? '') === 'pdf_error') {
		$responseStatus = 500;
		$message = 'No se pudo generar el PDF. El reporte permanece PENDIENTE.';
		$content = '';
	} else {
		$responseStatus = 400;
		$message = 'Debe indicar un reporte valido y el nombre de quien aprueba.';
		$content = '';
	}
}

if ($type == 'reopen'){
	$id = isset($_POST['id']) ? $_POST['id'] : '';
	$csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
	$result = report_reopen($id, $csrfToken);
	if ($result === 'reopened') {
		$message = 'El reporte volvió a estado PENDIENTE y ya puede editarse desde la APK.';
		$content = '';
	} elseif ($result === 'forbidden') {
		$responseStatus = 403;
		$message = 'La solicitud expiró o no es válida. Recargue la página e intente nuevamente.';
		$content = '';
	} elseif ($result === 'not_found') {
		$responseStatus = 404;
		$message = 'El reporte no existe.';
		$content = '';
	} elseif ($result === 'not_approved') {
		$responseStatus = 409;
		$message = 'Solo un reporte aprobado puede volver a PENDIENTE.';
		$content = '';
	} else {
		$responseStatus = 400;
		$message = 'No se pudo cambiar el estado del reporte.';
		$content = '';
	}
}

$data['status']  = $responseStatus;
$data['message'] = $message;
$data['content'] = $content;

http_response_code($responseStatus);
echo json_encode($data);

//var_dump(report_list());

?>
