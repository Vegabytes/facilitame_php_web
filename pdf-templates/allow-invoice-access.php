<?php
function formatoFecha()
{
    $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    // Convertir la fecha al formato deseado
    $fecha = date("Y-m-d");
    $timestamp = strtotime($fecha);
    $dia = date('j', $timestamp);
    $mes = date('n', $timestamp);
    $año = date('Y', $timestamp);

    return "$dia de " . $meses[$mes] . " de $año";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Acuerdo de permiso para el acceso a datos personales</title>
    <style>
        html {
            -webkit-print-color-adjust: exact;
        }


        body {
            width: 230mm;
            height: 100%;
            font-size: 12pt;
            line-height: 1.5rem;
            background: rgb(204, 204, 204);
            font-family: Verdana, Helvetica, sans-serif;
        }


        * {
            box-sizing: border-box;
            -moz-box-sizing: border-box;
        }


        .main-page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
        }


        .sub-page {
            /* height: 297mm; */
            display: flex;
            flex-direction: column;
            padding: 0 1px 0 0;
        }


        @page {
            size: A4;
            margin: 10mm 13mm;
        }


        @media print {


            html,
            body {
                width: 210mm;
                height: 297mm;
            }


            .main-page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
        }


        p {
            padding: 0;
            margin: 0 0 0.5rem 0;
        }


        table {
            margin-top: 1em;
            margin-bottom: 1em;
            border-collapse: collapse;
            border: 1px solid gray;
        }


        table td,
        table th {
            border: 1px solid gray;
            padding: 0.5em;
        }
    </style>
</head>


<body>
    <div class="main-page">
        <div class="sub-page">

            <div style="width: 100%; display: flex; flex-direction: row; justify-content: center; align-items: center; margin-bottom: 3rem;">
                <img style="display:block; width:75%; " src="<?php echo ROOT_URL . "/" . MEDIA_DIR . "/logo-facilitame-letras-negras.png" ?>" alt="">
            </div>

            <p style="text-align:center; font-size:1.15rem; margin-bottom:2rem;"><b>Acuerdo de permiso para acceder a datos personales</b></p>

            <p style="text-align:justify;">Yo, <b><?php secho(mb_strtoupper($data["name"] . " " . $data["lastname"])) ?></b>, en calidad de usuario de la aplicación FACILÍTAME, otorgo mi consentimiento expreso a MÉTODO ANCORE SL, en adelante referido como "la Empresa", para acceder y utilizar mis datos personales con el propósito exclusivo de extraer facturas y subirlas a la aplicación mencionada.</p>

            <p style="text-align:justify;">Entiendo y acepto que la Empresa requerirá acceso a mi apartado personal dentro de la aplicación para llevar a cabo esta actividad. Este acceso se limitará únicamente a la obtención de facturas y cualquier otra información relevante relacionada con los servicios contratados a través de la aplicación.</p>

            <p style="text-align:justify;">Acepto que la Empresa mantendrá la confidencialidad y seguridad de mis datos personales de acuerdo con las leyes y regulaciones aplicables en materia de protección de datos. Entiendo que la Empresa no compartirá, venderá ni utilizará mis datos personales para ningún otro propósito que no esté expresamente autorizado por mí, a menos que así lo requiera la ley.</p>

            <p style="text-align:justify;">Autorizo a la Empresa a utilizar cualquier medio tecnológico o método necesario para acceder a mis datos personales dentro de la aplicación con el fin de extraer facturas y llevar a cabo las actividades mencionadas anteriormente.</p>

            <p style="text-align:justify;">Entiendo que tengo derecho a revocar este consentimiento en cualquier momento, mediante notificación por escrito a la Empresa. Sin embargo, comprendo que la revocación de este consentimiento puede afectar la capacidad de la Empresa para proporcionarme ciertos servicios a través de la aplicación.</p>

            <p style="text-align:justify;">Al firmar esta autorización, confirmo que he leído y comprendido los términos y condiciones establecidos anteriormente y que otorgo mi consentimiento voluntario para que la Empresa acceda y utilice mis datos personales de la manera descrita.</p>

            <p style="text-align:justify; margin-top:3rem;"><b>Fecha:</b> <?php echo formatoFecha() ?></p>

            <p style="text-align:justify;"><b>Nombre del cliente:</b> <?php secho(mb_strtoupper($data["name"] . " " . $data["lastname"])) ?></p>

        </div>
    </div>
</body>

</html>