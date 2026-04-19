<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
ob_start();

date_default_timezone_set('America/Lima');

function respondJson(bool $success, string $message, array $extra = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c'),
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function appendContactLog(array $data): void
{
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/contactos.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    appendContactLog([
        'fecha' => date('c'),
        'tipo' => 'fatal_error',
        'detalle' => $error,
    ]);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => 'El servidor encontró un error interno al procesar la solicitud.',
        'timestamp' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
});

function cleanField(string $value): string
{
    return trim(filter_var($value, FILTER_UNSAFE_RAW));
}

function safeHtml(string $value): string
{
    return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function getEnvValue(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, '\'') && str_ends_with($value, '\''))
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function getMailConfig(): array
{
    $smtpUsername = (string) getEnvValue('MAIL_USERNAME', '');
    $smtpPassword = (string) getEnvValue('MAIL_PASSWORD', '');

    return [
        'recipient_email' => getEnvValue('CONTACT_RECIPIENT_EMAIL', 'hflores07@gmail.com'),
        'recipient_name' => getEnvValue('CONTACT_RECIPIENT_NAME', 'PERU TRANSPORT & LOGISTIC E.I.R.L.'),
        'smtp_host' => getEnvValue('MAIL_HOST', 'smtp.gmail.com'),
        'smtp_port' => (int) getEnvValue('MAIL_PORT', '587'),
        'smtp_secure' => strtolower((string) getEnvValue('MAIL_ENCRYPTION', 'tls')),
        'smtp_username' => $smtpUsername,
        'smtp_password' => $smtpPassword,
        'from_email' => getEnvValue('MAIL_FROM_ADDRESS', $smtpUsername),
        'from_name' => getEnvValue('MAIL_FROM_NAME', 'PERU TRANSPORT & LOGISTIC E.I.R.L.'),
        'timezone' => getEnvValue('APP_TIMEZONE', 'America/Lima'),
    ];
}

function isPlaceholderSecret(string $value): bool
{
    if ($value === '') {
        return true;
    }

    $normalized = strtoupper(trim($value));

    return str_contains($normalized, 'REEMPLAZA_AQUI')
        || str_contains($normalized, 'APP_PASSWORD')
        || str_contains($normalized, 'TU_APP_PASSWORD');
}

function resolveEncryption(string $value): string
{
    if ($value === 'ssl') {
        return PHPMailer::ENCRYPTION_SMTPS;
    }

    return PHPMailer::ENCRYPTION_STARTTLS;
}

function createMailer(array $config): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->Port = $config['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = resolveEncryption($config['smtp_secure']);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Timeout = 20;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isHTML(true);
    $mail->setFrom($config['from_email'], $config['from_name']);

    return $mail;
}

function sendMailMessage(
    array $config,
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $altBody,
    string $replyToEmail,
    string $replyToName
): array {
    try {
        $mail = createMailer($config);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($replyToEmail, $replyToName);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody;
        $mail->send();

        return [
            'success' => true,
            'error' => null,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    respondJson(
        false,
        'Falta Composer o PHPMailer. Ejecuta `composer require phpmailer/phpmailer` en la raíz del proyecto.',
        [],
        500
    );
}

require_once $autoloadPath;

loadEnvFile(__DIR__ . '/../.env');

if (!class_exists(PHPMailer::class) || !class_exists(Exception::class) || !class_exists(SMTP::class)) {
    respondJson(
        false,
        'PHPMailer no está disponible correctamente en `vendor/`. Reinstala las dependencias con Composer.',
        [],
        500
    );
}

$config = getMailConfig();
date_default_timezone_set($config['timezone']);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(false, 'Método no permitido.', [], 405);
}

if ($config['smtp_username'] === '' || $config['smtp_password'] === '' || $config['from_email'] === '') {
    respondJson(false, 'La configuración SMTP está incompleta en el servidor.', [], 500);
}

if (isPlaceholderSecret($config['smtp_password'])) {
    respondJson(false, 'Falta colocar la App Password real de Gmail en el archivo `.env`.', [], 500);
}

$empresa = cleanField($_POST['empresa'] ?? '');
$nombre = cleanField($_POST['nombre'] ?? '');
$telefono = cleanField($_POST['telefono'] ?? '');
$correo = cleanField($_POST['correo'] ?? '');
$mensaje = cleanField($_POST['mensaje'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'IP no disponible';
$fecha = date('d/m/Y H:i:s');

if ($nombre === '') {
    respondJson(false, 'Por favor, ingresa tu nombre completo.', [], 422);
}

if ($telefono === '') {
    respondJson(false, 'Por favor, ingresa tu número de teléfono.', [], 422);
}

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    respondJson(false, 'Por favor, ingresa un correo electrónico válido.', [], 422);
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

$plainCompany = "Nueva solicitud de contacto\n\n";
$plainCompany .= "Empresa: {$empresaTexto}\n";
$plainCompany .= "Nombre: {$nombre}\n";
$plainCompany .= "Teléfono: {$telefono}\n";
$plainCompany .= "Correo: {$correo}\n";
$plainCompany .= "Mensaje: {$mensajeTexto}\n";
$plainCompany .= "Fecha: {$fecha}\n";
$plainCompany .= "IP: {$ip}\n";

$plainCustomer = "Hola {$nombre},\n\n";
$plainCustomer .= "Recibimos tu solicitud correctamente.\n";
$plainCustomer .= "Empresa: {$empresaTexto}\n";
$plainCustomer .= "Teléfono: {$telefono}\n";
$plainCustomer .= "Mensaje: {$mensajeTexto}\n";
$plainCustomer .= "Si necesitas atención inmediata, también puedes comunicarte al +51 958 954 165.\n";

$companyResult = sendMailMessage(
    $config,
    $config['recipient_email'],
    $config['recipient_name'],
    $subjectCompany,
    $bodyCompany,
    $plainCompany,
    $correo,
    $nombre
);

$customerResult = [
    'success' => false,
    'error' => 'No se intentó enviar porque falló el correo principal.',
];

if ($companyResult['success']) {
    $customerResult = sendMailMessage(
        $config,
        $correo,
        $nombre,
        $subjectCustomer,
        $bodyCustomer,
        $plainCustomer,
        $config['recipient_email'],
        $config['recipient_name']
    );
}

appendContactLog([
    'fecha' => date('c'),
    'empresa' => $empresaTexto,
    'nombre' => $nombre,
    'telefono' => $telefono,
    'correo' => $correo,
    'mensaje' => $mensajeTexto,
    'ip' => $ip,
    'smtp_host' => $config['smtp_host'],
    'smtp_port' => $config['smtp_port'],
    'smtp_secure' => $config['smtp_secure'],
    'enviado_empresa' => $companyResult['success'] ? 'SI' : 'NO',
    'error_empresa' => $companyResult['error'],
    'enviado_cliente' => $customerResult['success'] ? 'SI' : 'NO',
    'error_cliente' => $customerResult['error'],
]);

if (!$companyResult['success']) {
    respondJson(
        false,
        'No se pudo enviar el correo principal. Verifica usuario, contraseña de aplicación de Gmail y configuración SMTP.',
        [
            'company_mail_sent' => false,
            'customer_mail_sent' => false,
        ],
        422
    );
}

$message = 'Tu mensaje fue enviado correctamente. También enviamos una confirmación a tu correo.';
if (!$customerResult['success']) {
    $message = 'Tu mensaje fue enviado correctamente. No pudimos enviar la confirmación automática al cliente, pero la empresa sí recibió la solicitud.';
}

respondJson(true, $message, [
    'company_mail_sent' => $companyResult['success'],
    'customer_mail_sent' => $customerResult['success'],
]);
