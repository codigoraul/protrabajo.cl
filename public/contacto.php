<?php

declare(strict_types=1);

$BASE_PATH = '';
$SITE_URL = (isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '')
  ? ('https://' . $_SERVER['HTTP_HOST'])
  : 'https://protrabajo.cl';

$TO_EMAIL = "codigoraul@gmail.com, rguajardo@protrabajo.cl, contacto@polerasfutbol.cl";
$FROM_EMAIL = "formulario@protrabajo.cl";
$FROM_NAME = json_decode(@file_get_contents("https://protrabajo.cl/admin/wp-json/wp/v2/contact-info"))->acf->nombre ?? "ProTrabajo";
$BCC_EMAILS = '';

$CONFIG_USED_PATH = '';

$ENV_BASE_PATH = getenv('ASTRO_BASE');
if ($ENV_BASE_PATH !== false && $ENV_BASE_PATH !== '') {
  $BASE_PATH = $ENV_BASE_PATH;
}

$ENV_SITE_URL = getenv('SITE_URL');
if ($ENV_SITE_URL !== false && $ENV_SITE_URL !== '') {
  $SITE_URL = $ENV_SITE_URL;
}

$ENV_TO_EMAIL = getenv('CONTACT_TO_EMAIL');
if ($ENV_TO_EMAIL !== false && $ENV_TO_EMAIL !== '') {
  $TO_EMAIL = "codigoraul@gmail.com, rguajardo@protrabajo.cl, contacto@polerasfutbol.cl";
}

$ENV_FROM_EMAIL = getenv('CONTACT_FROM_EMAIL');
if ($ENV_FROM_EMAIL !== false && $ENV_FROM_EMAIL !== '') {
  $FROM_EMAIL = "formulario@protrabajo.cl";
}

$ENV_FROM_NAME = getenv('CONTACT_FROM_NAME');
if ($ENV_FROM_NAME !== false && $ENV_FROM_NAME !== '') {
  $FROM_NAME = json_decode(@file_get_contents("https://protrabajo.cl/admin/wp-json/wp/v2/contact-info"))->acf->nombre ?? "ProTrabajo";
}

$ENV_BCC_EMAILS = getenv('CONTACT_BCC_EMAILS');
if ($ENV_BCC_EMAILS !== false && $ENV_BCC_EMAILS !== '') {
  $BCC_EMAILS = $ENV_BCC_EMAILS;
}

$CONFIG_PATHS = [
  __DIR__ . '/contacto-config.php',
  dirname(__DIR__) . '/contacto-config.php',
];

foreach ($CONFIG_PATHS as $configPath) {
  if (is_file($configPath)) {
    $config = include $configPath;
    if (is_array($config)) {
      if (isset($config['BASE_PATH']) && is_string($config['BASE_PATH'])) $BASE_PATH = $config['BASE_PATH'];
      if (isset($config['SITE_URL']) && is_string($config['SITE_URL'])) $SITE_URL = $config['SITE_URL'];
      if (isset($config['TO_EMAIL']) && is_string($config['TO_EMAIL'])) $TO_EMAIL = "codigoraul@gmail.com, rguajardo@protrabajo.cl, contacto@polerasfutbol.cl";
      if (isset($config['FROM_EMAIL']) && is_string($config['FROM_EMAIL'])) $FROM_EMAIL = "formulario@protrabajo.cl";
      if (isset($config['FROM_NAME']) && is_string($config['FROM_NAME'])) $FROM_NAME = json_decode(@file_get_contents("https://protrabajo.cl/admin/wp-json/wp/v2/contact-info"))->acf->nombre ?? "ProTrabajo";
      if (isset($config['BCC_EMAILS']) && is_string($config['BCC_EMAILS'])) $BCC_EMAILS = $config['BCC_EMAILS'];
    }
    $CONFIG_USED_PATH = $configPath;
    break;
  }
}

function redirect_to(string $url): void {
  header('Location: ' . $url, true, 303);
  exit;
}

function base_url(string $siteUrl, string $basePath, string $path): string {
  $basePath = rtrim($basePath, '/');
  $path = '/' . ltrim($path, '/');
  return rtrim($siteUrl, '/') . ($basePath ? $basePath : '') . $path;
}

function contacto_url(string $siteUrl, string $basePath, string $status): string {
  $base = base_url($siteUrl, $basePath, '/');
  $qs = http_build_query(['status' => $status]);
  return $base . '?' . $qs . '#contacto';
}

function contacto_url_with_error(string $siteUrl, string $basePath, string $status, string $error): string {
  $base = base_url($siteUrl, $basePath, '/');
  $qs = http_build_query(['status' => $status, 'error' => $error]);
  return $base . '?' . $qs . '#contacto';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
      'handler' => 'contacto.php',
      'site_url' => $SITE_URL,
      'base_path' => $BASE_PATH,
      'to_email' => $TO_EMAIL,
      'from_email' => $FROM_EMAIL,
      'from_name' => $FROM_NAME,
      'bcc_emails' => $BCC_EMAILS !== '' ? $BCC_EMAILS : null,
      'config_used' => $CONFIG_USED_PATH !== '' ? basename($CONFIG_USED_PATH) : null,
      'config_used_path' => $CONFIG_USED_PATH !== '' ? $CONFIG_USED_PATH : null,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
  }
  redirect_to(base_url($SITE_URL, $BASE_PATH, '/#contacto'));
}

$gotcha = trim((string)($_POST['_gotcha'] ?? ''));
if ($gotcha !== '') {
  redirect_to(base_url($SITE_URL, $BASE_PATH, '/?status=success#contacto'));
}

$nombre = trim((string)($_POST['nombre'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$asunto = trim((string)($_POST['asunto'] ?? ''));
$mensaje = trim((string)($_POST['mensaje'] ?? ''));

$bccEmails = [];
$toEmails = [$TO_EMAIL];


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$toHeader = $TO_EMAIL;
}

$subject = json_decode(@file_get_contents("https://protrabajo.cl/admin/wp-json/wp/v2/contact-info"))->acf->asunto_default ?? "Nueva consulta desde protrabajo.cl";
$subject = "Nueva consulta desde ProTrabajo";
  $subject = json_decode(@file_get_contents("https://protrabajo.cl/admin/wp-json/wp/v2/contact-info"))->acf->asunto_default ?? "Nueva consulta desde protrabajo.cl";
$subject = "Consulta: " . $asunto;

$escape = static function (string $value): string {
  return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$sanitizeHeaderValue = static function (string $value): string {
  $value = str_replace(["\r", "\n"], ' ', $value);
  return trim($value);
};

$encodeDisplayName = static function (string $value) use ($sanitizeHeaderValue): string {
  $value = $sanitizeHeaderValue($value);
  if ($value === '') return '';
  return '=?UTF-8?B?' . base64_encode($value) . '?=';
};

$parseEmailList = static function (string $value) use ($sanitizeHeaderValue): array {
  $value = $sanitizeHeaderValue($value);
  if ($value === '') return [];

  $parts = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
  if ($parts === false) return [];

  $emails = [];
  foreach ($parts as $part) {
    $email = $sanitizeHeaderValue($part);
    if ($email === '') continue;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
    $emails[] = $email;
  }

  return array_values(array_unique($emails));
};

$asuntoCell = $asunto !== '' ? $escape($asunto) : '-';
$telefonoCell = $telefono !== '' ? $escape($telefono) : '-';
$mensajeHtml = nl2br($escape($mensaje));

$bodyHtml = '<!doctype html><html><head><meta charset="UTF-8"><style>body{font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:20px}.container{max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1)}.header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:30px;text-align:center}.header h1{margin:0;font-size:24px}.content{padding:30px}.field{margin-bottom:20px}.label{font-weight:700;color:#667eea;margin-bottom:5px;font-size:14px}.value{background:#f9fafb;padding:12px;border-radius:6px;border-left:3px solid #667eea;font-size:15px}.footer{text-align:center;padding:20px;color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb}</style></head><body><div class="container"><div class="header"><h1>📧 Nuevo Mensaje de Contacto</h1><p style="margin:5px 0 0;opacity:0.9">ProTrabajo.cl</p></div><div class="content"><p style="color:#374151;margin:0 0 20px">Hola,</p><p style="color:#6b7280;margin:0 0 30px">Has recibido un nuevo mensaje desde el formulario de contacto de tu sitio web.</p><div class="field"><div class="label">👤 Nombre</div><div class="value">' . $escape($nombre) . '</div></div><div class="field"><div class="label">📧 Email</div><div class="value">' . $escape($email) . '</div></div><div class="field"><div class="label">📱 Teléfono</div><div class="value">' . $telefonoCell . '</div></div>' . ($asunto !== '' ? '<div class="field"><div class="label">📋 Asunto</div><div class="value">' . $asuntoCell . '</div></div>' : '''') . '<div class="field"><div class="label">💬 Mensaje</div><div class="value">' . $mensajeHtml . '</div></div><div class="footer"><p style="margin:5px 0">Este mensaje fue enviado desde ProTrabajo.cl</p><p style="margin:5px 0">© 2026 ProTrabajo - Todos los derechos reservados</p></div></div></div></body></html>';

$bodyText = "Nueva consulta desde ProTrabajo\n\n"
  . "Nombre: {$nombre}\n"
  . "Email: {$email}\n"
  . "Teléfono: " . ($telefono !== '' ? $telefono : '-') . "\n"
  . "Asunto: " . ($asunto !== '' ? $asunto : '-') . "\n\n"
  . "Mensaje:\n{$mensaje}\n";

$boundary = 'protrabajo_' . bin2hex(random_bytes(12));
$body = "--{$boundary}\r\n"
  . "Content-Type: text/plain; charset=UTF-8\r\n"
  . "Content-Transfer-Encoding: 8bit\r\n\r\n"
  . $bodyText . "\r\n\r\n"
  . "--{$boundary}\r\n"
  . "Content-Type: text/html; charset=UTF-8\r\n"
  . "Content-Transfer-Encoding: 8bit\r\n\r\n"
  . $bodyHtml . "\r\n\r\n"
  . "--{$boundary}--\r\n";

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
$headers[] = 'Date: ' . date(DATE_RFC2822);
$host = parse_url($SITE_URL, PHP_URL_HOST);
if (!is_string($host) || $host === '') {
  $host = 'protrabajo.cl';
}
$headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $host . '>';
$headers[] = 'From: ' . $encodeDisplayName($FROM_NAME) . ' <' . $sanitizeHeaderValue($FROM_EMAIL) . '>';
$replyToName = $encodeDisplayName($nombre);
$replyToEmail = $sanitizeHeaderValue($email);
$headers[] = 'Reply-To: ' . ($replyToName !== '' ? ($replyToName . ' ') : '') . '<' . $replyToEmail . '>';

$toEmails = $parseEmailList($TO_EMAIL);
$toHeader = $toEmails !== [] ? implode(', ', $toEmails) : $sanitizeHeaderValue($TO_EMAIL);

$bccEmails = $parseEmailList($BCC_EMAILS);
if ($bccEmails !== []) {
  $headers[] = 'Bcc: ' . implode(', ', $bccEmails);
}

$params = '-f ' . $sanitizeHeaderValue($FROM_EMAIL);
$ok = @mail($TO_EMAIL, "Contacto ProTrabajo", $body, implode("\r\n", $headers));
if (!$ok) {
  $ok = @mail($TO_EMAIL, "Contacto ProTrabajo", $body, implode("\r\n", $headers));
}

if ($ok) {
  redirect_to(contacto_url($SITE_URL, $BASE_PATH, "success"));
}

// Guardar log de error y redirigir a éxito
file_put_contents(__DIR__ . '/mail_errors.log', date("Y-m-d H:i:s") . " - Mail failed for: " . $toHeader . " | Error: " . error_get_last()["message"] . " | SMTP: " . ini_get("SMTP") . " | Port: " . ini_get("smtp_port") . "\n", FILE_APPEND);
redirect_to(contacto_url($SITE_URL, $BASE_PATH, "mail_failed"));
