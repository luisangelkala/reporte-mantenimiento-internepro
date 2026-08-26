<?php

function db(){
    return mysqli_connect(
        'localhost',
        'REEMPLAZAR_USUARIO_BD',
        'REEMPLAZAR_CLAVE_BD',
        'REEMPLAZAR_BASE_DE_DATOS'
    );
}
