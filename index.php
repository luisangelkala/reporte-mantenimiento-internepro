<?php
/**
* Password generator
*
* This is the php index file to load all mini site.
*
* @author LAGC
 */
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

    <main>
	<div class="container bg-gris">
            <a href="index.php"><img class="" src="images/logo-internepro.png" width="300" height="86"></a>
            <div class="head">
                <h2>Registro de Mantenimiento</h2>
		<input type="button" class="reporte" value="Nuevo Reporte">
		<input type="button" class="alimak" value="Nuevo Reporte ALIMAK">
            </div>
            <div class="status" style="height:40px; color:red;"></div>

            <table class="table" id="reporttable">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col" class="text-left">Reporte</th>
                        <th scope="col">-</th>
                    </tr>
                </thead>
                <tbody id="tableresults">

                </tbody>
            </table>

        </div>
    </main>
<script>
(function($) {
    $doc = $(document);

    $doc.ready( function(){

        function list(){
            $container = $('.container');
            $status = $container.find('.status');
            $status.text('Cargando...');

            $.ajax({
                url: "process.php",
                data: 'list',
                type: 'post',
                dataType: 'json',
                success: function(data, XMLHttpRequest) {
                            if (data.status === 200) {
                                $status.html(data.message);
                                /*location.reload();*/
                                // Adding a row inside the tbody.
                                $('#tableresults').html(data.content);
                                $('#tableresults tr.red a.delete')
                                    .removeAttr('data-filter')
                                    .attr('title', 'Un reporte aprobado no puede ser borrado')
                                    .css({ color: '#888', cursor: 'not-allowed', pointerEvents: 'none' });
                                ;
                            }
                            else {
                                $status.html(data.message);
                            }

                            console.log(data);
                            console.log(XMLHttpRequest);
                }
            });
        }

        function send($formData){
            $container = $('.container');
            $status = $container.find('.status');
            $status.text('Cargando...');

            $.ajax({
                url: "process.php",
                data: $formData,
                type: 'post',
                dataType: 'json',
                success: function(data, XMLHttpRequest) {
                            if (data.status === 200) {
                                $status.html(data.message);
                                /*location.reload();*/
                                list();
                            }
                            else {
                                $status.html(data.message);
                            }

                            console.log(data);
                            console.log(XMLHttpRequest);
                }
            });
        }

        /** CREAR REPORTE */
        $('.head').on('click', '.reporte', function(event) {
            if(event.preventDefault) { event.preventDefault(); }

            $params = {
	   	    'type'  : 'create',
		    'reporte' : 'elevador',
            };

            // Run query
            send($params);
        });
	/** CREAR REPORTE ALIMAK*/
	$('.head').on('click', '.alimak', function(event) {
            if(event.preventDefault) { event.preventDefault(); }

            $params = {
                'type'  : 'create',
                'reporte' : 'alimak',
            };

            // Run query
            send($params);
        });
	
        /** DELETE REPORTE */
        $('#tableresults').on('click', 'a[data-filter]', function(event) {
            if(event.preventDefault) { event.preventDefault(); }

            $this = $(this);

            $params = {
                'type'  : 'delete',
                'id'    : $this.data('filter')
            };


            var checkstr =  confirm('Esta seguro de borrar el reporte?');
                if(checkstr == true){
                    // Run query
                    send($params);
                }else{
                return false;
                }

        });

        setTimeout(function () {
            list();
        }, 1000);

    });

})(jQuery);


</script>

<script src="assets/js/report-gallery.js?ver=1.0"></script>
<script src="assets/css/bootstrap5/bootstrap.min.js?v=0.4"></script>
</body>
</html>
