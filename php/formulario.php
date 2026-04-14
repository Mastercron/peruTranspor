<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$config = [
    'recipient_email' => 'gerencia@peruaduanas.com',
    'recipient_name' => 'PERU TRANSPORT & LOGISTIC E.I.R.L.',
    'confirmation_sender' => 'gerencia@peruaduanas.com',
    'timezone' => 'America/Lima',
];

date_default_timezone_set($config['timezone']);

function respondJson(bool $success, string $message, array $extra = []): void
{
    http_response_code($success ? 200 : 422);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c'),
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function cleanField(string $value): string
{
    return trim(filter_var($value, FILTER_UNSAFE_RAW));
}

function safeHtml(string $value): string
{
    return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function sendMailMessage(string $toEmail, string $toName, string $subject, string $htmlBody, string $replyToEmail, string $replyToName, string $fromEmail, string $fromName): bool
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyToName . ' <' . $replyToEmail . '>',
        'X-Mailer: PHP/' . phpversion(),
    ];

    return mail(
        $toName !== '' ? sprintf('%s <%s>', $toName, $toEmail) : $toEmail,
        $subject,
        $htmlBody,
        implode("\r\n", $headers)
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(false, 'Método no permitido.');
}

$empresa = cleanField($_POST['empresa'] ?? '');
$nombre = cleanField($_POST['nombre'] ?? '');
$telefono = cleanField($_POST['telefono'] ?? '');
$correo = cleanField($_POST['correo'] ?? '');
$mensaje = cleanField($_POST['mensaje'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'IP no disponible';
$fecha = date('d/m/Y H:i:s');

if ($nombre === '') {
    respondJson(false, 'Por favor, ingresa tu nombre completo.');
}

if ($telefono === '') {
    respondJson(false, 'Por favor, ingresa tu número de teléfono.');
}

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    respondJson(false, 'Por favor, ingresa un correo electrónico válido.');
}

$empresaTexto = $empresa !== '' ? $empresa : 'No especificada';
$mensajeTexto = $mensaje !== '' ? $mensaje : 'Sin mensaje adicional.';

$subjectCompany = 'Nueva solicitud desde la web - ' . $nombre;
$bodyCompany = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nueva solicitud de contacto</title>
</head>
<body style="margin:0;padding:24px;background:#f3f6fb;font-family:Arial,sans-serif;color:#16304d;">
  <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dce6f2;">
    <div style="background:#0b3d78;color:#ffffff;padding:24px;">
      <h1 style="margin:0;font-size:24px;">Nueva solicitud de contacto</h1>
      <p style="margin:10px 0 0;opacity:.9;">PERU TRANSPORT & LOGISTIC E.I.R.L.</p>
    </div>
    <div style="padding:24px;line-height:1.6;">
      <p><strong>Empresa:</strong> ' . safeHtml($empresaTexto) . '</p>
      <p><strong>Nombre:</strong> ' . safeHtml($nombre) . '</p>
      <p><strong>Teléfono:</strong> ' . safeHtml($telefono) . '</p>
      <p><strong>Correo:</strong> ' . safeHtml($correo) . '</p>
      <p><strong>Mensaje:</strong><br>' . safeHtml($mensajeTexto) . '</p>
      <hr style="border:none;border-top:1px solid #dce6f2;margin:24px 0;">
      <p><strong>Fecha:</strong> ' . safeHtml($fecha) . '</p>
      <p><strong>IP:</strong> ' . safeHtml($ip) . '</p>
    </div>
  </div>
</body>
</html>';

$subjectCustomer = 'Hemos recibido tu solicitud - PERU TRANSPORT';
$bodyCustomer = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Confirmación de recepción</title>
</head>
<body style="margin:0;padding:24px;background:#f3f6fb;font-family:Arial,sans-serif;color:#16304d;">
  <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dce6f2;">
    <div style="background:#f97316;color:#ffffff;padding:24px;">
      <h1 style="margin:0;font-size:24px;">Gracias por contactarnos</h1>
      <p style="margin:10px 0 0;opacity:.9;">PERU TRANSPORT & LOGISTIC E.I.R.L.</p>
    </div>
    <div style="padding:24px;line-height:1.7;">
      <p>Hola ' . safeHtml($nombre) . ',</p>
      <p>Recibimos tu solicitud correctamente. Nuestro equipo revisará tu mensaje y te responderá a la brevedad.</p>
      <p><strong>Resumen enviado:</strong></p>
      <p><strong>Empresa:</strong> ' . safeHtml($empresaTexto) . '</p>
      <p><strong>Teléfono:</strong> ' . safeHtml($telefono) . '</p>
      <p><strong>Mensaje:</strong><br>' . safeHtml($mensajeTexto) . '</p>
      <p style="margin-top:24px;">Si necesitas atención inmediata, también puedes comunicarte al +51 958 954 165.</p>
    </div>
  </div>
</body>
</html>';

$sentToCompany = sendMailMessage(
    $config['recipient_email'],
    $config['recipient_name'],
    $subjectCompany,
    $bodyCompany,
    $correo,
    $nombre,
    $config['confirmation_sender'],
    $config['recipient_name']
);

$sentToCustomer = false;
if ($sentToCompany) {
    $sentToCustomer = sendMailMessage(
        $correo,
        $nombre,
        $subjectCustomer,
        $bodyCustomer,
        $config['recipient_email'],
        $config['recipient_name'],
        $config['confirmation_sender'],
        $config['recipient_name']
    );
}

$logDir = __DIR__ . '/../logs';
$logFile = $logDir . '/contactos.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logData = [
    'fecha' => date('c'),
    'empresa' => $empresaTexto,
    'nombre' => $nombre,
    'telefono' => $telefono,
    'correo' => $correo,
    'mensaje' => $mensajeTexto,
    'ip' => $ip,
    'enviado_empresa' => $sentToCompany ? 'SI' : 'NO',
    'enviado_cliente' => $sentToCustomer ? 'SI' : 'NO',
];

file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

if (!$sentToCompany) {
    respondJson(false, 'No se pudo enviar el correo desde el servidor. Revisa la configuración de mail del hosting o consulta el log en /logs/contactos.log.');
}

$message = 'Tu mensaje fue enviado correctamente. También enviamos una confirmación a tu correo.';
if (!$sentToCustomer) {
    $message = 'Tu mensaje fue enviado correctamente. No pudimos enviar la confirmación automática al cliente, pero la empresa sí recibió la solicitud.';
}

respondJson(true, $message, [
    'company_mail_sent' => $sentToCompany,
    'customer_mail_sent' => $sentToCustomer,
]);
?>
