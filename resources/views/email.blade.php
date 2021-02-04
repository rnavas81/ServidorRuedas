<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>MAIL</title>

        <!-- Estilos -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    </head>
    <body>
        <table style="max-width: 600px; padding: 10px; margin:0 auto; border-collapse: collapse;">
            <tr>
                
            </tr>

            <tr>
                <td style="padding: 0">
                    <img style="padding: 0; display: block" src="https://i.ibb.co/ctBRq1V/Sin-nombre.png" width="100%">
                </td>
            </tr>

            <tr>
                <td style="background-color: #ecf0f1">
                    <div style="color: #34495e; margin: 4% 10% 2%; text-align: justify;font-family: sans-serif">
                        <h2 style="color: #e67e22; margin: 0 0 7px">Saludos desde CarShare!</h2>
                        <p style="margin: 2px; font-size: 15px">
                            Le comunicamos que se ha restablecido la contraseña a una nueva, si usted no la ha solicitado,
                        póngase en contacto a través del email que le facilitamos al final del email</p>
                        <br>
                        <p style="margin: 2px; font-size: 15px">
                            Su nueva contaseña es: <b>{{ $password ?? 'Ha ocurrido un error.' }}</b></p>
                        <br>
                        <p style="margin: 2px; font-size: 15px">
                            Para cualquier problema póngase en contacto con nosotros a través de: CarShare@administracion.com</p>
                        
                        <div style="width: 100%; text-align: center; margin-top: 50px">
                            <a style="text-decoration: none; border-radius: 5px; padding: 11px 23px; color: white; background-color: #3498db" href="{{ env('APP_ROUTE') }}">Ir a la web</a>	
                        </div>
                        <p style="color: #b3b3b3; font-size: 12px; text-align: center;margin: 30px 0 0">CarShare ©</p>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>
