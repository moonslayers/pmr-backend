<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu correo electrónico - SAGEM</title>
</head>

<body
    style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa;">

    <div
        style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

        <!-- CABECERA CON MARCA -->
        <div style="background-color: #6a1c32; color: white; padding: 30px 20px; text-align: center;">
            <!-- Logo del cliente -->
            <img src="https://www.bajacalifornia.gob.mx/sei/Content/img/logo-Economiabc.png" alt="Logo SAGEM"
                style="height: auto; width: 100%; margin-bottom: 15px;">
            <!-- Aquí irá el logo que explicamos abajo -->
            <h1 style="margin: 0; font-size: 24px; font-weight: 600;">🔔 Confirma tu correo electrónico</h1>
            <p style="margin: 5px 0 0 0; font-size: 16px;">Hola {{ $user->name }},</p>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div style="padding: 40px 30px;">

            <!-- MENSAJE DE BIENVENIDA -->
            <div
                style="background-color: #e8f4f8; border-left: 5px solid #17a2b8; padding: 20px; margin-bottom: 25px; border-radius: 0 5px 5px 0;">
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #343a40; font-weight: 600;">¡Bienvenido a SAGEM!</p>
                <p style="margin: 0; font-size: 16px; color: #495057;">
                    Gracias por registrarte en nuestro sistema. Para completar tu registro, por favor confirma tu dirección de correo electrónico haciendo clic en el botón de abajo.
                </p>
            </div>

            <!-- INFORMACIÓN DE LA CUENTA -->
            <div style="margin-bottom: 25px;">
                <h2
                    style="color: #17a2b8; font-size: 20px; border-bottom: 2px solid #17a2b8; padding-bottom: 5px; margin-top: 0;">
                    Información de tu cuenta
                </h2>

                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 15px;">
                    <p style="margin: 5px 0;"><strong style="color: #343a40;">Nombre:</strong>
                        {{ $user->name }}</p>
                    <p style="margin: 5px 0;"><strong style="color: #343a40;">Correo electrónico:</strong>
                        {{ $user->email }}</p>
                    <p style="margin: 5px 0;"><strong style="color: #343a40;">RFC:</strong>
                        {{ $user->rfc }}</p>
                    <p style="margin: 5px 0;"><strong style="color: #343a40;">Tipo de usuario:</strong>
                        {{ $user->user_type === 'EXTERNO' ? 'Usuario Externo' : 'Usuario Interno' }}</p>
                    <p style="margin: 5px 0;"><strong style="color: #343a40;">Rol asignado:</strong>
                        Solicitante</p>
                </div>
            </div>

            <!-- INSTRUCCIONES DE VERIFICACIÓN -->
            <div
                style="background-color: #ffc107; color: #212529; padding: 20px; border-radius: 5px; text-align: center; margin-bottom: 25px;">
                <p style="margin: 0; font-size: 18px; font-weight: bold;">⚠️ Importante: Verifica tu correo electrónico</p>
                <p style="margin: 5px 0 0 0; font-size: 16px;">
                    Este enlace expirará en <strong>1 hora</strong>. Si no confirmas tu correo, no podrás acceder a todas las funcionalidades del sistema.
                </p>
            </div>

            <!-- BOTÓN DE VERIFICACIÓN -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $verificationUrl }}"
                    style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; font-weight: bold; font-size: 16px; border-radius: 5px; display: inline-block;">
                    ✅ Confirmar Correo Electrónico
                </a>
                <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                    O copia y pega este enlace en tu navegador:<br>
                    <span style="word-break: break-all; color: #17a2b8; font-weight: 500;">{{ $verificationUrl }}</span>
                </p>
            </div>

            <!-- INFORMACIÓN DE EXPIRACIÓN -->
            <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; text-align: center;">
                <p style="margin: 0; font-size: 14px; color: #6c757d;">
                    <strong>Este enlace expira:</strong> {{ $expiresAt }}<br>
                    <small>Si el enlace ha expirado, puedes solicitar un nuevo correo de confirmación desde el sistema.</small>
                </p>
            </div>

        </div>

        <!-- PIE DE PÁGINA -->
        <div style="background-color: #343a40; color: #f8f9fa; padding: 20px; text-align: center; font-size: 14px;">
            <p style="margin: 0;">Este es un mensaje automático del Sistema SAGEM. Por favor, no responder a este
                correo.</p>
            <p style="margin: 5px 0 0 0;">&copy; {{ date('Y') }} SAGEM - Todos los derechos reservados.</p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #adb5bd;">
                Si no has creado esta cuenta, por favor ignora este mensaje.
            </p>
        </div>

    </div>

</body>

</html>