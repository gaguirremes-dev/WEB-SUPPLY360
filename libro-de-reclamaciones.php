<?php
/**
 * Libro de Reclamaciones Virtual - Supply360
 * Conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor)
 * y el D.S. N° 011-2011-PCM (modificado por D.S. 101-2021-PCM).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
date_default_timezone_set('America/Lima');

define('DOMINIO_PERMITIDO', 'supplylogistic360.com');
define('EMPRESA_RAZON_SOCIAL', 'SUPPLY360 S.A.C.S.');
define('EMPRESA_RUC', '20615946207');
define('EMPRESA_DIRECCION', 'Av. República de Chile Nro. 324 Int. 601 Urb. Santa Beatriz (Edificio Polaris), Lima - Lima - Jesús María');
define('EMPRESA_EMAIL', 'info@supplylogistic360.com');

$smtpConfigFile = __DIR__ . '/config.smtp.php';
if (file_exists($smtpConfigFile)) {
    require $smtpConfigFile;
}

if (!defined('SMTP_PORT'))           define('SMTP_PORT', 465);
if (!defined('SMTP_FROM_NAME'))      define('SMTP_FROM_NAME', 'Supply360');
if (!defined('EMPRESA_NOTIF_EMAIL')) define('EMPRESA_NOTIF_EMAIL', 'reclamaciones@supplylogistic360.com');

define('RATE_LIMIT_MAX',    1);
define('RATE_LIMIT_WINDOW', 1800);
if (!defined('HCAPTCHA_SITE_KEY'))   define('HCAPTCHA_SITE_KEY',   '');
if (!defined('HCAPTCHA_SECRET_KEY')) define('HCAPTCHA_SECRET_KEY', '');

$recordsDir  = __DIR__ . '/reclamaciones';
$recordsFile = $recordsDir . '/records.json';
$lockFile    = $recordsDir . '/records.lock';

if (!is_dir($recordsDir)) {
    if (!@mkdir($recordsDir, 0755, true)) {
        $errorMsg = 'Error de configuración del servidor: no se puede crear el directorio de registros. Contacte al administrador.';
    }
}

$rateFile     = $recordsDir . '/rate_limits.json';
$logFile      = $recordsDir . '/security.log';
$htaccessPath = $recordsDir . '/.htaccess';
if (!file_exists($htaccessPath)) {
    @file_put_contents($htaccessPath, "Order Deny,Allow\nDeny from all\n");
}

$fpdfPath      = __DIR__ . '/lib/fpdf.php';
$fpdfAvailable = file_exists($fpdfPath);
if ($fpdfAvailable) require_once $fpdfPath;

// ── Generar PDF ───────────────────────────────────────────────────────────────
function generarPDFReclamo(array $d): string {
    if (!class_exists('FPDF')) return '';

    $c = function(string $s): string {
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    };

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // Cabecera
    $pdf->SetFillColor(10, 22, 40);   // navy #0A1628
    $pdf->Rect(0, 0, 210, 38, 'F');
    $pdf->SetTextColor(255, 208, 0);  // gold accent
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->SetXY(10, 7);
    $pdf->Cell(0, 8, $c('HOJA DE RECLAMACIÓN VIRTUAL'), 0, 1, 'C');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10, 17);
    $pdf->Cell(0, 6, $c('Conforme a la Ley N 29571 - Codigo de Proteccion y Defensa del Consumidor'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 26);
    $pdf->Cell(0, 6, $c('Codigo: ' . $d['codigo'] . '     Fecha: ' . $d['fecha']), 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(44);

    // Proveedor
    $pdf->SetFillColor(255, 247, 210);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $c('PROVEEDOR'), 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, $c('Razon Social: ' . EMPRESA_RAZON_SOCIAL . '   RUC: ' . EMPRESA_RUC), 0, 'L');
    $pdf->MultiCell(0, 5, $c('Domicilio: ' . EMPRESA_DIRECCION), 0, 'L');
    $pdf->Ln(2);

    $col1W = 45; $col2W = 135;

    // Sección 1
    $pdf->SetFillColor(10, 22, 40);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $c('1. IDENTIFICACION DEL CONSUMIDOR RECLAMANTE'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);

    $rows = [
        ['Nombre completo:', $d['nombres']],
        ['Documento:', $d['doc_tipo'] . ' N ' . $d['doc_nro']],
        ['Domicilio:', $d['direccion'] . ', ' . $d['distrito'] . ' - ' . $d['provincia'] . ' (' . $d['departamento'] . ')'],
        ['Telefono:', $d['telefono'] ?: '-'],
        ['Email:', $d['email']],
    ];
    if ($d['menor_edad']) {
        $rows[] = ['Apoderado:', $d['apoderado_nombres'] . ' (' . $d['apoderado_doc_tipo'] . ' ' . $d['apoderado_doc_nro'] . ')'];
    }
    foreach ($rows as [$label, $val]) {
        $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell($col1W, 5.5, $c($label), 'B', 0, 'L');
        $pdf->SetFont('Arial', '', 8.5);  $pdf->Cell($col2W, 5.5, $c($val),   'B', 1, 'L');
    }
    $pdf->Ln(3);

    // Sección 2
    $pdf->SetFillColor(10, 22, 40);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $c('2. IDENTIFICACION DEL BIEN CONTRATADO'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell($col1W, 5.5, $c('Tipo de bien:'), 'B', 0, 'L');
    $pdf->SetFont('Arial', '', 8.5);  $pdf->Cell($col2W, 5.5, $c(ucfirst($d['bien_tipo'])), 'B', 1, 'L');
    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell($col1W, 5.5, $c('Monto reclamado:'), 'B', 0, 'L');
    $pdf->SetFont('Arial', '', 8.5);  $pdf->Cell($col2W, 5.5, $c('S/. ' . $d['monto']), 'B', 1, 'L');
    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell($col1W, 5.5, $c('Descripcion:'), 'B', 0, 'L');
    $pdf->SetFont('Arial', '', 8.5);  $pdf->Cell($col2W, 5.5, $c($d['bien_desc'] ?: '-'), 'B', 1, 'L');
    $pdf->Ln(3);

    // Sección 3
    $pdf->SetFillColor(10, 22, 40);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $c('3. DETALLE DE LA RECLAMACION Y PEDIDO DEL CONSUMIDOR'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(40, 6, $c('Tipo de incidencia:'), 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $esReclamo = strtolower($d['reclamo_tipo']) === 'reclamo';
    $pdf->Cell(5, 6, $esReclamo ? 'X' : 'O', 1, 0, 'C');
    $pdf->Cell(22, 6, $c(' Reclamo'), 0, 0);
    $pdf->Cell(5, 6, !$esReclamo ? 'X' : 'O', 1, 0, 'C');
    $pdf->Cell(22, 6, $c(' Queja'), 0, 1);
    $pdf->Ln(1);

    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell(0, 5, $c('Detalle del hecho:'), 0, 1);
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->SetFillColor(248, 248, 248);
    $pdf->MultiCell(0, 5, $c($d['detalle']), 1, 'L', true);
    $pdf->Ln(2);

    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell(0, 5, $c('Pedido del consumidor:'), 0, 1);
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->MultiCell(0, 5, $c($d['pedido']), 1, 'L', true);
    $pdf->Ln(4);

    // Firmas
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->Cell(90, 5, $c('Firma del Consumidor'), 'T', 0, 'C');
    $pdf->Cell(10, 5, '', 0, 0);
    $pdf->Cell(90, 5, $c('Firma del Proveedor'), 'T', 1, 'C');
    $pdf->Ln(2);

    // Pie legal
    $pdf->SetFont('Arial', 'I', 7.5);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 4, $c('La formulacion del reclamo no impide acudir a otras vias de solucion de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.'), 0, 'L');
    $pdf->SetFont('Arial', 'BI', 7.5);
    $pdf->MultiCell(0, 4, $c('El proveedor debe dar respuesta al reclamo o queja en un plazo no mayor a quince (15) dias habiles improrrogables (D.S. 101-2021-PCM).'), 0, 'L');

    return $pdf->Output('', 'S');
}

// ── Enviar correo SMTP ────────────────────────────────────────────────────────
function enviarCorreoSMTP(string $toEmail, string $subject, string $htmlBody, string $pdfB64 = '', string $pdfName = '', string &$err = ''): bool {
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        $err = 'Configuración SMTP no encontrada (falta config.smtp.php).';
        return false;
    }
    $toEmail   = sanitizarCabecera($toEmail);
    $subject   = sanitizarCabecera($subject);
    $enc = function (string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; };
    $fromEmail = SMTP_USER;
    $eol = "\r\n";
    $headers  = 'Date: ' . date('r') . $eol;
    $headers .= 'From: ' . $enc(SMTP_FROM_NAME) . ' <' . $fromEmail . '>' . $eol;
    $headers .= 'To: <' . $toEmail . '>' . $eol;
    $headers .= 'Subject: ' . $enc($subject) . $eol;
    $headers .= 'Reply-To: ' . EMPRESA_EMAIL . $eol;
    $headers .= 'MIME-Version: 1.0' . $eol;
    if ($pdfB64) {
        $boundary = '----=_Part_' . md5(uniqid());
        $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $eol;
        $body  = '--' . $boundary . $eol;
        $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body .= 'Content-Transfer-Encoding: 7bit' . $eol . $eol;
        $body .= $htmlBody . $eol;
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Type: application/pdf; name="' . $pdfName . '"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-Disposition: attachment; filename="' . $pdfName . '"' . $eol . $eol;
        $body .= $pdfB64 . $eol;
        $body .= '--' . $boundary . '--';
    } else {
        $headers .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body = $htmlBody;
    }
    $message = $headers . $eol . $body;
    $message = str_replace(["\r\n", "\r", "\n"], "\n", $message);
    $message = str_replace("\n", $eol, $message);
    $message = str_replace($eol . '.', $eol . '..', $message);

    $transport = (SMTP_PORT == 465 ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
    $fp = @stream_socket_client($transport, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $err = "Conexión SMTP fallida: $errstr ($errno)"; return false; }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) { $data .= $line; if (strlen($line) < 4 || $line[3] === ' ') break; }
        return $data;
    };
    $cmd = function (string $c) use ($fp, $read): string { fwrite($fp, $c . "\r\n"); return $read(); };
    $ok  = function (string $resp, array $codes) use (&$err): bool {
        foreach ($codes as $code) { if (strncmp($resp, $code, strlen($code)) === 0) return true; }
        $err = trim($resp); return false;
    };
    $fail = function () use ($fp) { @fwrite($fp, "QUIT\r\n"); @fclose($fp); return false; };

    if (!$ok($read(), ['220']))                                   return $fail();
    if (!$ok($cmd('EHLO ' . SMTP_HOST), ['250']))                 return $fail();
    if (!$ok($cmd('AUTH LOGIN'), ['334']))                        return $fail();
    if (!$ok($cmd(base64_encode(SMTP_USER)), ['334']))            return $fail();
    if (!$ok($cmd(base64_encode(SMTP_PASS)), ['235']))            return $fail();
    if (!$ok($cmd('MAIL FROM:<' . $fromEmail . '>'), ['250']))    return $fail();
    if (!$ok($cmd('RCPT TO:<' . $toEmail . '>'), ['250', '251'])) return $fail();
    if (!$ok($cmd('DATA'), ['354']))                              return $fail();
    if (!$ok($cmd($message . "\r\n."), ['250']))                  return $fail();
    $cmd('QUIT'); fclose($fp);
    return true;
}

// ── Funciones de seguridad ────────────────────────────────────────────────────
function sanitizarCabecera(string $s): string {
    return trim(str_replace(["\r\n","\r","\n","%0d%0a","%0D%0A","%0d","%0D","%0a","%0A"], '', $s));
}
function esc(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function limpiarTexto(?string $s, int $maxLen = 500): string {
    if ($s === null || $s === '') return '';
    return mb_substr(trim(strip_tags($s)), 0, $maxLen, 'UTF-8');
}
function checkAndRegisterRateLimit(string $ip, string $rateFile): bool {
    $lockPath = $rateFile . '.lock';
    $fp = @fopen($lockPath, 'c');
    if (!$fp || !flock($fp, LOCK_EX)) return true;
    $now = time(); $window = RATE_LIMIT_WINDOW; $max = RATE_LIMIT_MAX; $data = [];
    if (file_exists($rateFile)) {
        $raw = file_get_contents($rateFile);
        $data = ($raw !== false && $raw !== '') ? (json_decode($raw, true) ?: []) : [];
    }
    foreach (array_keys($data) as $k) {
        $data[$k] = array_values(array_filter($data[$k], function($ts) use ($now, $window) { return ($now - $ts) < $window; }));
        if (empty($data[$k])) unset($data[$k]);
    }
    $ipEntries = $data[$ip] ?? [];
    if (count($ipEntries) >= $max) { flock($fp, LOCK_UN); fclose($fp); return false; }
    $ipEntries[] = $now; $data[$ip] = $ipEntries;
    file_put_contents($rateFile, json_encode($data));
    flock($fp, LOCK_UN); fclose($fp);
    return true;
}
function logSecurityEvent(string $logFile, string $event, array $context = []): void {
    foreach (['email','nombres','doc_nro','apoderado_nombres','apoderado_doc_nro'] as $k) unset($context[$k]);
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ' | ' . $event . ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}
function verificarHCaptcha(string $token, string $secretKey): bool {
    if ($token === '' || $secretKey === '') return false;
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query(['secret' => $secretKey, 'response' => $token]), 'timeout' => 10]]);
    $result = @file_get_contents('https://hcaptcha.com/siteverify', false, $ctx);
    if ($result === false) return false;
    $data = json_decode($result, true);
    return isset($data['success']) && $data['success'] === true;
}

$success       = false;
$errorMsg      = '';
$generatedCode = '';
$submittedData = [];
$debugLog      = [];
$clientIP = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')[0]);

set_error_handler(function($errno, $errstr) use (&$errorMsg) {
    if ($errno === E_ERROR || $errno === E_USER_ERROR) $errorMsg = 'Error interno del servidor. Por favor, inténtelo más tarde.';
    return true;
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. Honeypot ───────────────────────────────────────────────────────────
    if (!empty($_POST['hp_website'] ?? '')) {
        logSecurityEvent($logFile, 'HONEYPOT_TRIGGERED', ['ip' => $clientIP]);
        header('HTTP/1.1 200 OK'); exit;
    }

    // ── 2. CSRF ───────────────────────────────────────────────────────────────
    $csrfPost = (string)($_POST['csrf_token'] ?? '');
    $csrfSess = (string)($_SESSION['csrf_token'] ?? '');
    if (!$csrfPost || !$csrfSess || !hash_equals($csrfSess, $csrfPost)) {
        logSecurityEvent($logFile, 'CSRF_FAILED', ['ip' => $clientIP]);
        $errorMsg = 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    }

    // ── 3. hCaptcha ───────────────────────────────────────────────────────────
    if (empty($errorMsg)) {
        $captchaToken = (string)($_POST['h-captcha-response'] ?? '');
        if (!verificarHCaptcha($captchaToken, HCAPTCHA_SECRET_KEY)) {
            logSecurityEvent($logFile, 'CAPTCHA_FAILED', ['ip' => $clientIP]);
            $errorMsg = 'Por favor, completa el desafío de seguridad (captcha) antes de enviar.';
        }
    }

    // ── 4. Origin / Referer ───────────────────────────────────────────────────
    if (empty($errorMsg)) {
        $origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($origin && strpos($origin, DOMINIO_PERMITIDO) === false) {
            logSecurityEvent($logFile, 'ORIGIN_REJECTED', ['ip' => $clientIP, 'origin' => $origin]);
            $errorMsg = 'Solicitud rechazada por origen no permitido.';
        } elseif (!$origin && $referer && strpos($referer, DOMINIO_PERMITIDO) === false) {
            logSecurityEvent($logFile, 'REFERER_REJECTED', ['ip' => $clientIP]);
            $errorMsg = 'Solicitud rechazada por referencia no permitida.';
        }
    }

    // ── 5. Rate limiting ──────────────────────────────────────────────────────
    if (empty($errorMsg)) {
        if (!checkAndRegisterRateLimit($clientIP, $rateFile)) {
            logSecurityEvent($logFile, 'RATE_LIMIT_EXCEEDED', ['ip' => $clientIP]);
            $errorMsg = 'Has enviado un reclamo recientemente. Por favor, espera 30 minutos antes de volver a intentarlo.';
        }
    }

    // ── 6. Sanitizar y validar ────────────────────────────────────────────────
    if (empty($errorMsg)) {
        $nombres            = limpiarTexto($_POST['nombres']            ?? '', 200);
        $doc_tipo           = $_POST['doc_tipo']           ?? '';
        $doc_nro            = limpiarTexto($_POST['doc_nro']            ?? '', 20);
        $email              = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $telefono           = limpiarTexto($_POST['telefono']           ?? '', 20);
        $direccion          = limpiarTexto($_POST['direccion']          ?? '', 300);
        $departamento       = limpiarTexto($_POST['departamento']       ?? '', 100);
        $provincia          = limpiarTexto($_POST['provincia']          ?? '', 100);
        $distrito           = limpiarTexto($_POST['distrito']           ?? '', 100);
        $menor_edad         = isset($_POST['menor_edad']);
        $apoderado_nombres  = limpiarTexto($_POST['apoderado_nombres']  ?? '', 200);
        $apoderado_doc_tipo = $_POST['apoderado_doc_tipo'] ?? '';
        $apoderado_doc_nro  = limpiarTexto($_POST['apoderado_doc_nro']  ?? '', 20);
        $bien_tipo          = $_POST['bien_tipo']          ?? '';
        $bien_desc          = limpiarTexto($_POST['bien_desc']          ?? '', 500);
        $reclamo_tipo       = $_POST['reclamo_tipo']       ?? '';
        $detalle            = limpiarTexto($_POST['detalle']            ?? '', 2000);
        $pedido             = limpiarTexto($_POST['pedido']             ?? '', 2000);
        $monto              = number_format(max(0.0, (float)preg_replace('/[^\d.]/', '', $_POST['monto'] ?? '0')), 2, '.', '');

        if (!in_array($doc_tipo,           ['DNI','CE','PASAPORTE','RUC'], true)) $doc_tipo           = 'DNI';
        if (!in_array($apoderado_doc_tipo, ['DNI','CE','PASAPORTE'],        true)) $apoderado_doc_tipo = 'DNI';
        if (!in_array($bien_tipo,          ['producto','servicio'],          true)) $bien_tipo          = 'producto';
        if (!in_array($reclamo_tipo,       ['reclamo','queja'],              true)) $reclamo_tipo       = 'reclamo';

        if (!$nombres || !$doc_nro || !$email || !$direccion || !$detalle || !$pedido) {
            $errorMsg = 'Por favor, rellene todos los campos obligatorios del formulario.';
        }
    }

    // ── 7. Guardar registro ───────────────────────────────────────────────────
    if (empty($errorMsg)) {
        $fp = fopen($lockFile, 'w');
        if ($fp && flock($fp, LOCK_EX)) {
            $year    = date('Y');
            $records = [];
            if (file_exists($recordsFile)) $records = json_decode(file_get_contents($recordsFile), true) ?: [];
            $yearCount = 0;
            foreach ($records as $r) { if (isset($r['year']) && $r['year'] == $year) $yearCount++; }
            $generatedCode = sprintf('REC-%s-%04d', $year, $yearCount + 1);
            $submittedData = [
                'codigo' => $generatedCode, 'year' => $year, 'fecha' => date('d/m/Y h:i A'),
                'nombres' => $nombres, 'doc_tipo' => $doc_tipo, 'doc_nro' => $doc_nro,
                'email' => $email, 'telefono' => $telefono, 'direccion' => $direccion,
                'departamento' => $departamento, 'provincia' => $provincia, 'distrito' => $distrito,
                'menor_edad' => $menor_edad,
                'apoderado_nombres'  => $menor_edad ? $apoderado_nombres  : '',
                'apoderado_doc_tipo' => $menor_edad ? $apoderado_doc_tipo : '',
                'apoderado_doc_nro'  => $menor_edad ? $apoderado_doc_nro  : '',
                'bien_tipo' => $bien_tipo, 'monto' => $monto, 'bien_desc' => $bien_desc,
                'reclamo_tipo' => $reclamo_tipo, 'detalle' => $detalle, 'pedido' => $pedido,
                'estado' => 'Pendiente', 'respuesta' => '',
            ];
            $records[] = $submittedData;
            file_put_contents($recordsFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            flock($fp, LOCK_UN);
            $success = true;
        } else {
            $errorMsg = 'Error al registrar la reclamación en el servidor. Inténtelo nuevamente.';
        }
        if ($fp) fclose($fp);
    }

    // ── 8. Generar PDF + enviar correos + redirigir ───────────────────────────
    if ($success) {
        logSecurityEvent($logFile, 'RECLAMO_OK', ['ip' => $clientIP, 'codigo' => $generatedCode]);
        $pdfBytes = '';
        if ($fpdfAvailable) { try { $pdfBytes = generarPDFReclamo($submittedData); } catch (Exception $e) { } }
        $pdfB64  = $pdfBytes ? chunk_split(base64_encode($pdfBytes)) : '';
        $pdfName = 'Hoja_Reclamacion_' . $generatedCode . '.pdf';
        if ($pdfBytes) @file_put_contents($recordsDir . '/' . $pdfName, $pdfBytes);

        $emailBody = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;'>
            <div style='background:#0A1628;padding:25px;text-align:center;'>
                <h2 style='margin:0;font-size:22px;color:#FFD000;'>HOJA DE RECLAMACIÓN VIRTUAL</h2>
                <p style='margin:5px 0 0;color:#fff;font-size:14px;font-weight:bold;'>Código: " . esc($generatedCode) . "</p>
            </div>
            <div style='padding:25px;line-height:1.6;'>
                <p>Estimado(a) <strong>" . esc($nombres) . "</strong>,</p>
                <p>Confirmamos la recepción de tu reclamación registrada el <strong>" . date('d/m/Y') . "</strong>. Adjunto encontrarás el cargo de tu Hoja de Reclamación Virtual.</p>
                <p>De acuerdo con la legislación vigente en el Perú (Ley N° 29571), daremos respuesta a tu requerimiento en un plazo máximo de <strong>15 días hábiles</strong>.</p>
                <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
                <h3 style='color:#0A1628;'>Detalle de la Reclamación</h3>
                <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                    <tr><td style='padding:6px 0;font-weight:bold;width:150px;'>Consumidor:</td><td>" . esc($nombres) . " (" . esc($doc_tipo) . " " . esc($doc_nro) . ")</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;'>Bien contratado:</td><td style='text-transform:capitalize;'>" . esc($bien_tipo) . " — S/. " . esc($monto) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;'>Tipo incidencia:</td><td style='font-weight:bold;color:" . ($reclamo_tipo==='reclamo'?'#dc2626':'#d97706') . ";text-transform:capitalize;'>" . esc($reclamo_tipo) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;vertical-align:top;'>Detalle:</td><td style='background:#f9f9f9;padding:8px;border-radius:4px;'>" . esc($detalle) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;vertical-align:top;'>Pedido:</td><td style='background:#f9f9f9;padding:8px;border-radius:4px;'>" . esc($pedido) . "</td></tr>
                </table>
            </div>
            <div style='background:#f5f5f5;padding:15px;text-align:center;font-size:12px;color:#888;border-top:1px solid #e0e0e0;'>
                Cargo automático — no responder a este mensaje.
            </div>
        </div>";

        $errCliente = '';
        enviarCorreoSMTP($email, 'Cargo de Hoja de Reclamación N° ' . $generatedCode . ' — Supply360', $emailBody, $pdfB64, $pdfName, $errCliente);

        $emailBodyEmpresa = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;padding:25px;'>
            <h2 style='color:#dc2626;margin-top:0;'>Nuevo Reclamo/Queja Registrado</h2>
            <p>Código: <strong>" . esc($generatedCode) . "</strong> — Plazo máximo: <strong>15 días hábiles</strong>.</p>
            <h3 style='border-bottom:2px solid #eee;padding-bottom:5px;color:#0A1628;'>1. Reclamante</h3>
            <p><strong>Nombre:</strong> " . esc($nombres) . "<br><strong>Doc:</strong> " . esc($doc_tipo) . " " . esc($doc_nro) . "<br>
            <strong>Tel:</strong> " . esc($telefono) . "<br><strong>Email:</strong> " . esc($email) . "<br>
            <strong>Dirección:</strong> " . esc($direccion) . ", " . esc($distrito) . " - " . esc($provincia) . " (" . esc($departamento) . ")</p>
            <h3 style='border-bottom:2px solid #eee;padding-bottom:5px;color:#0A1628;'>2. Bien Contratado</h3>
            <p><strong>Tipo:</strong> " . esc(ucfirst($bien_tipo)) . " — S/. " . esc($monto) . "<br><strong>Descripción:</strong> " . esc($bien_desc) . "</p>
            <h3 style='border-bottom:2px solid #eee;padding-bottom:5px;color:#0A1628;'>3. Reclamo</h3>
            <p><strong>Tipo:</strong> " . esc(strtoupper($reclamo_tipo)) . "</p>
            <div style='background:#f7f7f7;padding:12px;border-left:4px solid #dc2626;margin-bottom:10px;'><strong>Detalle:</strong><br>" . nl2br(esc($detalle)) . "</div>
            <div style='background:#f7f7f7;padding:12px;border-left:4px solid #FFD000;'><strong>Pedido:</strong><br>" . nl2br(esc($pedido)) . "</div>
        </div>";

        $errEmpresa = '';
        enviarCorreoSMTP(EMPRESA_NOTIF_EMAIL, 'NUEVA HOJA DE RECLAMACIÓN N° ' . $generatedCode . ' — ' . $nombres, $emailBodyEmpresa, $pdfB64, $pdfName, $errEmpresa);

        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
        $_SESSION['reclamo_success'] = $submittedData;
        header('Location: libro-de-reclamaciones.php?ok=1');
        exit;
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_GET['ok']) && !empty($_SESSION['reclamo_success'])) {
    $success       = true;
    $submittedData = $_SESSION['reclamo_success'];
    $generatedCode = $submittedData['codigo'] ?? '';
    unset($_SESSION['reclamo_success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libro de Reclamaciones - Supply360</title>
    <meta name="description" content="Libro de Reclamaciones Virtual de Supply360. Presenta tus quejas o reclamos conforme al Código de Protección al Consumidor en Perú.">
    <link rel="icon" type="image/png" href="assets/favicon/favicon-32x32.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { deep: '#0A1628', mid: '#132040', light: '#1E3A6E' },
                        brand: { DEFAULT: '#FFD000', light: '#FFF3B0', pale: '#FFFAE8' },
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>

    <style>
        * { box-sizing: border-box; }
        body { background-color: #FFFFFF; color: #111827; font-family: 'Inter', sans-serif; overflow-x: hidden; }

        .glass {
            background: #FFFFFF;
            border: 1px solid rgba(255,208,0,0.22);
            box-shadow: 0 2px 12px rgba(10,22,40,0.07);
        }
        .grad-text {
            background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .form-input {
            width: 100%;
            background: rgba(10,22,40,0.03);
            border: 1px solid rgba(10,22,40,0.14);
            border-radius: 12px;
            padding: 10px 14px;
            color: #111827;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-input::placeholder { color: rgba(10,22,40,0.3); }
        .form-input:focus { border-color: #FFD000; box-shadow: 0 0 0 2px rgba(255,208,0,0.18); }
        select.form-input { background-color: #FFFFFF; color: #111827; }
        select.form-input option { background: #FFFFFF; color: #111827; }

        .btn-sec {
            border: 1px solid rgba(10,22,40,0.35);
            color: #0A1628;
            background: transparent;
            transition: all 0.3s;
        }
        .btn-sec:hover { background: rgba(10,22,40,0.06); transform: translateY(-1px); }

        .btn-primary {
            background: linear-gradient(135deg, #0A1628, #1E3A6E);
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(10,22,40,0.28); }

        @media print {
            body { background: white !important; color: black !important; font-size: 11px !important; }
            .no-print { display: none !important; }
            .print-card { background: white !important; border: 1px solid #ddd !important; box-shadow: none !important; color: black !important; padding: 15px !important; border-radius: 0 !important; }
            .print-field { border-bottom: 1px solid #ccc !important; padding: 4px 0 !important; background: transparent !important; color: black !important; }
            .print-title { color: black !important; font-size: 16px !important; }
            .print-label { color: #555 !important; font-weight: bold !important; font-size: 10px !important; }
        }
    </style>
<?php if (defined('HCAPTCHA_SITE_KEY') && HCAPTCHA_SITE_KEY !== ''): ?>
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php endif; ?>
</head>
<body class="min-h-screen flex flex-col">

    <!-- HEADER -->
    <header class="no-print w-full border-b border-navy-deep/10 bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.html" class="flex items-center gap-3">
                <img src="LOGO - SUPPLY 360.png" alt="Supply360" class="h-10 w-10 rounded-lg object-contain bg-white p-0.5 border border-navy-deep/10">
                <div>
                    <div class="text-navy-deep font-black text-sm tracking-wide">SUPPLY<span class="text-yellow-500">360</span></div>
                    <div class="text-navy-deep/40 text-[9px] tracking-widest">SUPPLY CHAIN MANAGEMENT</div>
                </div>
            </a>
            <a href="index.html" class="text-navy-deep/50 hover:text-navy-deep text-xs flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver a la web
            </a>
        </div>
    </header>

    <main class="flex-grow max-w-4xl w-full mx-auto px-4 py-10">

        <!-- SUCCESS -->
        <?php if ($success): ?>
        <div class="print-card glass rounded-3xl p-8 sm:p-12 shadow-2xl relative border border-emerald-200">
            <div class="no-print absolute -right-20 -top-20 w-80 h-80 bg-emerald-50 rounded-full blur-3xl pointer-events-none"></div>

            <div class="no-print text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 text-3xl mb-4">✓</div>
                <h1 class="text-3xl font-black text-navy-deep mb-2">¡Reclamación Registrada!</h1>
                <p class="text-navy-deep/60 text-sm max-w-lg mx-auto">
                    Tu reclamo/queja ha sido procesado de forma correcta. Se ha enviado un cargo de recepción al correo <strong class="text-navy-deep"><?= htmlspecialchars($submittedData['email']) ?></strong>.
                </p>
            </div>

            <div class="bg-navy-deep/5 p-6 rounded-2xl border border-navy-deep/5 print-card">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-navy-deep/10 pb-5 mb-6 print-field">
                    <div>
                        <h2 class="text-xl font-black text-navy-deep print-title">HOJA DE RECLAMACIÓN VIRTUAL</h2>
                        <p class="text-xs text-navy-deep/50 mt-1">Conforme a la Ley N° 29571 / D.S. N° 011-2011-PCM</p>
                    </div>
                    <div class="mt-4 sm:mt-0 text-left sm:text-right">
                        <div class="text-xs text-yellow-600 font-black uppercase tracking-wider print-label">CÓDIGO DE RECLAMACIÓN</div>
                        <div class="text-xl font-black text-emerald-600 print-title mt-0.5"><?= htmlspecialchars($submittedData['codigo']) ?></div>
                        <div class="text-[10px] text-navy-deep/40 mt-1">Fecha de registro: <?= htmlspecialchars($submittedData['fecha']) ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-xs text-navy-deep/60 border-b border-navy-deep/5 pb-4 print-field">
                    <div><span class="block font-bold text-navy-deep/80 print-label">Proveedor:</span><?= EMPRESA_RAZON_SOCIAL ?></div>
                    <div><span class="block font-bold text-navy-deep/80 print-label">RUC:</span><?= EMPRESA_RUC ?></div>
                    <div class="md:col-span-2"><span class="block font-bold text-navy-deep/80 print-label">Domicilio Fiscal:</span><?= EMPRESA_DIRECCION ?></div>
                </div>

                <h3 class="text-sm font-bold uppercase tracking-wider text-yellow-600 mb-3 print-label">1. Identificación del Consumidor Reclamante</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 text-xs text-navy-deep/70 border-b border-navy-deep/5 pb-4 print-field">
                    <div>
                        <span class="block text-navy-deep/40 print-label">Nombre del consumidor:</span>
                        <strong class="text-navy-deep print-title"><?= htmlspecialchars($submittedData['nombres']) ?></strong>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 print-label">Documento:</span>
                        <span class="text-navy-deep"><?= htmlspecialchars($submittedData['doc_tipo']) ?> - <?= htmlspecialchars($submittedData['doc_nro']) ?></span>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 print-label">Domicilio:</span>
                        <span class="text-navy-deep"><?= htmlspecialchars($submittedData['direccion']) ?>, <?= htmlspecialchars($submittedData['distrito']) ?> - <?= htmlspecialchars($submittedData['provincia']) ?> (<?= htmlspecialchars($submittedData['departamento']) ?>)</span>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 print-label">Contacto:</span>
                        <span class="text-navy-deep">Email: <?= htmlspecialchars($submittedData['email']) ?> | Tel: <?= htmlspecialchars($submittedData['telefono'] ?: '-') ?></span>
                    </div>
                    <?php if ($submittedData['menor_edad']): ?>
                    <div class="sm:col-span-2 bg-navy-deep/5 p-3 rounded-lg border border-navy-deep/5 print-field">
                        <span class="block font-bold text-navy-deep/70 print-label">Representante / Apoderado:</span>
                        <span class="text-navy-deep text-xs"><?= htmlspecialchars($submittedData['apoderado_nombres']) ?> (<?= htmlspecialchars($submittedData['apoderado_doc_tipo']) ?> <?= htmlspecialchars($submittedData['apoderado_doc_nro']) ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>

                <h3 class="text-sm font-bold uppercase tracking-wider text-yellow-600 mb-3 print-label">2. Identificación del Bien Contratado</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 text-xs text-navy-deep/70 border-b border-navy-deep/5 pb-4 print-field">
                    <div><span class="block text-navy-deep/40 print-label">Tipo de Bien:</span><span class="text-navy-deep text-sm capitalize"><?= htmlspecialchars($submittedData['bien_tipo']) ?></span></div>
                    <div><span class="block text-navy-deep/40 print-label">Monto Reclamado:</span><span class="text-navy-deep text-sm font-bold">S/. <?= htmlspecialchars($submittedData['monto']) ?></span></div>
                    <div class="sm:col-span-2"><span class="block text-navy-deep/40 print-label">Descripción:</span><span class="text-navy-deep"><?= htmlspecialchars($submittedData['bien_desc'] ?: 'No especificado') ?></span></div>
                </div>

                <h3 class="text-sm font-bold uppercase tracking-wider text-yellow-600 mb-3 print-label">3. Detalle de la Reclamación y Pedido del Consumidor</h3>
                <div class="space-y-4 text-xs text-navy-deep/70">
                    <div class="flex items-center gap-3">
                        <span class="text-navy-deep/40 print-label">Tipo de Incidencia:</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider <?= $submittedData['reclamo_tipo'] === 'reclamo' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-amber-50 text-amber-600 border border-amber-200' ?> print-title">
                            <?= htmlspecialchars($submittedData['reclamo_tipo']) ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 print-label">Detalle:</span>
                        <div class="bg-navy-deep/5 p-3 rounded-lg border border-navy-deep/5 whitespace-pre-wrap mt-1 text-navy-deep print-field"><?= htmlspecialchars($submittedData['detalle']) ?></div>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 print-label">Pedido concreto:</span>
                        <div class="bg-navy-deep/5 p-3 rounded-lg border border-navy-deep/5 whitespace-pre-wrap mt-1 text-navy-deep print-field"><?= htmlspecialchars($submittedData['pedido']) ?></div>
                    </div>
                </div>
            </div>

            <div class="no-print mt-8 flex flex-col sm:flex-row justify-center gap-4">
                <button onclick="window.print()" class="btn-primary text-white font-bold px-6 py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Descargar / Imprimir Reclamo
                </button>
                <a href="index.html" class="btn-sec text-center font-semibold px-6 py-3.5 rounded-xl text-sm flex items-center justify-center gap-2">
                    Volver a la Página de Inicio
                </a>
            </div>
        </div>

        <!-- FORMULARIO -->
        <?php else: ?>
        <div class="no-print text-center mb-10">
            <div class="inline-flex items-center gap-2 bg-brand-pale border border-brand/30 rounded-full px-4 py-1.5 mb-4">
                <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                <span class="text-yellow-700 text-[10px] font-semibold tracking-widest uppercase">Ley N° 29571</span>
            </div>
            <h1 class="text-4xl font-black text-navy-deep mb-2">Libro de Reclamaciones <span class="grad-text">Virtual</span></h1>
            <p class="text-navy-deep/60 text-sm max-w-lg mx-auto">
                Para registrar tu queja o reclamo, completa todos los campos del formulario. Nos comunicaremos contigo en un plazo máximo de 15 días hábiles.
            </p>
        </div>

        <?php if (!empty($errorMsg)): ?>
        <div class="no-print bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
            <span class="text-lg">⚠️</span> <?= $errorMsg ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="glass rounded-3xl p-6 sm:p-8 shadow-xl">
                    <form method="POST" action="libro-de-reclamaciones.php" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
                        <div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                            <label for="hp_website">Sitio web (no llenar)</label>
                            <input type="text" name="hp_website" id="hp_website" tabindex="-1" autocomplete="off" value="">
                        </div>

                        <!-- Sección 1 -->
                        <div class="border-b border-navy-deep/10 pb-5">
                            <h2 class="text-lg font-black text-navy-deep flex items-center gap-2 mb-4">
                                <span class="w-6 h-6 rounded-lg bg-navy-deep flex items-center justify-center text-xs text-white font-black">1</span>
                                Identificación del Consumidor
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Nombres y Apellidos completos *</label>
                                    <input type="text" name="nombres" required placeholder="Ingresa tus nombres completos" class="form-input" value="<?= isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : '' ?>">
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Tipo Documento *</label>
                                    <select name="doc_tipo" required class="form-input">
                                        <option value="DNI" <?= isset($_POST['doc_tipo']) && $_POST['doc_tipo']=='DNI' ? 'selected':'' ?>>DNI (Perú)</option>
                                        <option value="CE" <?= isset($_POST['doc_tipo']) && $_POST['doc_tipo']=='CE' ? 'selected':'' ?>>Carnet de Extranjería</option>
                                        <option value="PASAPORTE" <?= isset($_POST['doc_tipo']) && $_POST['doc_tipo']=='PASAPORTE' ? 'selected':'' ?>>Pasaporte</option>
                                        <option value="RUC" <?= isset($_POST['doc_tipo']) && $_POST['doc_tipo']=='RUC' ? 'selected':'' ?>>RUC</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Nro Documento *</label>
                                    <input type="text" name="doc_nro" required placeholder="Número de documento" class="form-input" value="<?= isset($_POST['doc_nro']) ? htmlspecialchars($_POST['doc_nro']) : '' ?>">
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Correo Electrónico *</label>
                                    <input type="email" name="email" required placeholder="nombre@correo.com" class="form-input" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Teléfono / Celular</label>
                                    <input type="tel" name="telefono" placeholder="Número de contacto" class="form-input" value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Dirección Completa *</label>
                                    <input type="text" name="direccion" required placeholder="Av., Calle, Nro., Dpto., Urb." class="form-input" value="<?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?>">
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Departamento *</label>
                                    <input type="text" name="departamento" required placeholder="Ej: Lima" class="form-input" value="<?= isset($_POST['departamento']) ? htmlspecialchars($_POST['departamento']) : '' ?>">
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Provincia *</label>
                                    <input type="text" name="provincia" required placeholder="Ej: Lima" class="form-input" value="<?= isset($_POST['provincia']) ? htmlspecialchars($_POST['provincia']) : '' ?>">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Distrito *</label>
                                    <input type="text" name="distrito" required placeholder="Ej: Miraflores" class="form-input" value="<?= isset($_POST['distrito']) ? htmlspecialchars($_POST['distrito']) : '' ?>">
                                </div>
                            </div>

                            <div class="mt-4 bg-navy-deep/5 p-4 rounded-xl border border-navy-deep/5">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" id="menor_edad" name="menor_edad" class="w-4 h-4 rounded accent-yellow-500 flex-shrink-0 cursor-pointer" onclick="toggleApoderado()" <?= isset($_POST['menor_edad']) ? 'checked' : '' ?>>
                                    <span class="text-navy-deep/70 text-xs">Soy menor de edad (se requiere ingresar los datos de un tutor/apoderado).</span>
                                </label>
                                <div id="apoderado_fields" class="mt-4 pt-4 border-t border-navy-deep/10 grid grid-cols-1 sm:grid-cols-2 gap-4 hidden">
                                    <div class="sm:col-span-2">
                                        <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Nombres del Apoderado *</label>
                                        <input type="text" id="apoderado_nombres" name="apoderado_nombres" placeholder="Nombres del padre, madre o apoderado" class="form-input" value="<?= isset($_POST['apoderado_nombres']) ? htmlspecialchars($_POST['apoderado_nombres']) : '' ?>">
                                    </div>
                                    <div>
                                        <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Tipo Doc. Apoderado *</label>
                                        <select id="apoderado_doc_tipo" name="apoderado_doc_tipo" class="form-input">
                                            <option value="DNI" <?= isset($_POST['apoderado_doc_tipo']) && $_POST['apoderado_doc_tipo']=='DNI'?'selected':'' ?>>DNI</option>
                                            <option value="CE" <?= isset($_POST['apoderado_doc_tipo']) && $_POST['apoderado_doc_tipo']=='CE'?'selected':'' ?>>Carnet de Extranjería</option>
                                            <option value="PASAPORTE" <?= isset($_POST['apoderado_doc_tipo']) && $_POST['apoderado_doc_tipo']=='PASAPORTE'?'selected':'' ?>>Pasaporte</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Nro Doc. Apoderado *</label>
                                        <input type="text" id="apoderado_doc_nro" name="apoderado_doc_nro" placeholder="Nro de documento" class="form-input" value="<?= isset($_POST['apoderado_doc_nro']) ? htmlspecialchars($_POST['apoderado_doc_nro']) : '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2 -->
                        <div class="border-b border-navy-deep/10 pb-5">
                            <h2 class="text-lg font-black text-navy-deep flex items-center gap-2 mb-4">
                                <span class="w-6 h-6 rounded-lg bg-navy-deep flex items-center justify-center text-xs text-white font-black">2</span>
                                Identificación del Bien Contratado
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Tipo de bien *</label>
                                    <div class="flex items-center gap-6 mt-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="bien_tipo" value="producto" required class="w-4 h-4 accent-yellow-500" <?= !isset($_POST['bien_tipo']) || $_POST['bien_tipo']=='producto' ? 'checked':'' ?>>
                                            <span class="text-navy-deep/80 text-sm">Producto</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="bien_tipo" value="servicio" class="w-4 h-4 accent-yellow-500" <?= isset($_POST['bien_tipo']) && $_POST['bien_tipo']=='servicio' ? 'checked':'' ?>>
                                            <span class="text-navy-deep/80 text-sm">Servicio</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Monto Reclamado (S/. opcional)</label>
                                    <input type="number" step="0.01" min="0" name="monto" placeholder="S/. 0.00" class="form-input" value="<?= isset($_POST['monto']) ? htmlspecialchars($_POST['monto']) : '' ?>">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Descripción del bien contratado</label>
                                    <textarea rows="2" name="bien_desc" placeholder="Describe brevemente el producto o servicio contratado" class="form-input resize-none"><?= isset($_POST['bien_desc']) ? htmlspecialchars($_POST['bien_desc']) : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 3 -->
                        <div>
                            <h2 class="text-lg font-black text-navy-deep flex items-center gap-2 mb-4">
                                <span class="w-6 h-6 rounded-lg bg-navy-deep flex items-center justify-center text-xs text-white font-black">3</span>
                                Detalle del Reclamo y Pedido
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Tipo de Reclamación *</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                        <label class="flex items-start gap-2.5 p-3 rounded-xl bg-navy-deep/5 border border-navy-deep/5 cursor-pointer hover:bg-brand-pale hover:border-brand/20 transition-colors">
                                            <input type="radio" name="reclamo_tipo" value="reclamo" required class="w-4 h-4 mt-0.5 accent-yellow-500" <?= !isset($_POST['reclamo_tipo']) || $_POST['reclamo_tipo']=='reclamo' ? 'checked':'' ?>>
                                            <div>
                                                <span class="block text-navy-deep text-xs font-bold uppercase tracking-wider">Reclamo</span>
                                                <span class="text-[10px] text-navy-deep/50 leading-tight block mt-0.5">Disconformidad relacionada a los productos o servicios contratados.</span>
                                            </div>
                                        </label>
                                        <label class="flex items-start gap-2.5 p-3 rounded-xl bg-navy-deep/5 border border-navy-deep/5 cursor-pointer hover:bg-brand-pale hover:border-brand/20 transition-colors">
                                            <input type="radio" name="reclamo_tipo" value="queja" class="w-4 h-4 mt-0.5 accent-yellow-500" <?= isset($_POST['reclamo_tipo']) && $_POST['reclamo_tipo']=='queja' ? 'checked':'' ?>>
                                            <div>
                                                <span class="block text-navy-deep text-xs font-bold uppercase tracking-wider">Queja</span>
                                                <span class="text-[10px] text-navy-deep/50 leading-tight block mt-0.5">Disconformidad no relacionada a los productos. Malestar respecto a la atención.</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Detalle de tu Queja o Reclamo *</label>
                                    <textarea rows="4" name="detalle" required placeholder="Describe de forma detallada y ordenada lo ocurrido..." class="form-input resize-none"><?= isset($_POST['detalle']) ? htmlspecialchars($_POST['detalle']) : '' ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-navy-deep/50 text-[10px] uppercase tracking-widest mb-1.5 font-bold">Pedido concreto (¿Qué solicitas?) *</label>
                                    <textarea rows="3" name="pedido" required placeholder="Indica detalladamente tu solicitud (cambio, devolución, compensación, etc.)" class="form-input resize-none"><?= isset($_POST['pedido']) ? htmlspecialchars($_POST['pedido']) : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Declaraciones -->
                        <div class="pt-4 border-t border-navy-deep/10 space-y-3">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" required class="w-4 h-4 mt-0.5 rounded accent-yellow-500 flex-shrink-0 cursor-pointer">
                                <span class="text-navy-deep/40 text-[10px] leading-relaxed">Declaro ser el usuario titular y que los datos consignados en esta Hoja de Reclamación son reales y verdaderos.</span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" required class="w-4 h-4 mt-0.5 rounded accent-yellow-500 flex-shrink-0 cursor-pointer">
                                <span class="text-navy-deep/40 text-[10px] leading-relaxed">Acepto el tratamiento de mis datos personales para los fines de responder este reclamo, de acuerdo con la <strong class="text-navy-deep/60">Ley N° 29733</strong> (Protección de Datos Personales en el Perú).</span>
                            </label>
                        </div>

                        <?php if (defined('HCAPTCHA_SITE_KEY') && HCAPTCHA_SITE_KEY !== ''): ?>
                        <div class="flex justify-center pt-2">
                            <div class="h-captcha" data-sitekey="<?= esc(HCAPTCHA_SITE_KEY) ?>" data-theme="dark"></div>
                        </div>
                        <?php else: ?>
                        <p class="text-amber-400/70 text-[10px] text-center">⚠️ hCaptcha no configurado — define <code>HCAPTCHA_SITE_KEY</code> en <code>config.smtp.php</code>.</p>
                        <?php endif; ?>
                        <button type="submit" id="btn-submit" class="btn-primary w-full py-4 rounded-2xl text-white font-black text-sm tracking-wider cursor-pointer" onclick="this.disabled=true;this.form.submit()">
                            PRESENTAR RECLAMACIÓN
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="glass rounded-3xl p-6 text-xs space-y-3 shadow-md">
                    <h3 class="text-sm font-black text-navy-deep uppercase tracking-wider mb-2">Datos del Proveedor</h3>
                    <div>
                        <span class="block text-navy-deep/40 uppercase tracking-widest text-[9px] mb-0.5">Razón Social</span>
                        <strong class="text-navy-deep text-sm"><?= EMPRESA_RAZON_SOCIAL ?></strong>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 uppercase tracking-widest text-[9px] mb-0.5">RUC</span>
                        <strong class="text-navy-deep text-sm"><?= EMPRESA_RUC ?></strong>
                    </div>
                    <div>
                        <span class="block text-navy-deep/40 uppercase tracking-widest text-[9px] mb-0.5">Dirección de Planta</span>
                        <span class="text-navy-deep/80"><?= EMPRESA_DIRECCION ?></span>
                    </div>
                </div>

                <div class="glass rounded-3xl p-6 shadow-md">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">📋</span>
                        <h3 class="text-sm font-black text-navy-deep uppercase tracking-wider">Aviso Virtual</h3>
                    </div>
                    <p class="text-navy-deep/60 text-xs leading-relaxed mb-4">
                        Conforme al Código de Protección y Defensa del Consumidor, contamos con un Libro de Reclamaciones Virtual. Puedes revisar el poster oficial de INDECOPI a continuación:
                    </p>
                    <div class="relative rounded-2xl overflow-hidden border border-navy-deep/10 bg-navy-deep/5 group">
                        <img src="Libro-reclamaciones/AvisoVirtual_page1.png" alt="Aviso Virtual de Libro de Reclamaciones INDECOPI" class="w-full h-auto object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                        <div class="absolute inset-0 bg-navy-deep/30 flex items-center justify-center opacity-100 group-hover:bg-navy-deep/10 transition-all">
                            <button onclick="openNoticeModal()" class="bg-yellow-400 text-navy-deep font-bold px-4 py-2 rounded-xl text-xs shadow-lg hover:scale-105 transition-transform cursor-pointer">
                                Ver a Pantalla Completa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <footer class="no-print border-t border-navy-deep/10 bg-white py-6 mt-12 text-center text-xs text-navy-deep/40">
        <div class="max-w-6xl mx-auto px-4">
            © <?= date('Y') ?> <?= EMPRESA_RAZON_SOCIAL ?> · RUC <?= EMPRESA_RUC ?> · Todos los derechos reservados.<br>
            <span class="text-[10px] text-navy-deep/20 mt-1 block">Regulado por INDECOPI y en conformidad con la Ley de Protección de Datos Personales N° 29733.</span>
        </div>
    </footer>

    <!-- Modal Aviso Virtual -->
    <div id="notice-modal" class="no-print fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/90 backdrop-blur-sm">
        <div class="absolute inset-0 cursor-pointer" onclick="closeNoticeModal()"></div>
        <div class="relative z-10 max-w-lg w-full bg-white rounded-3xl p-4 shadow-2xl flex flex-col items-center">
            <button onclick="closeNoticeModal()" class="absolute -top-10 right-0 sm:-right-8 text-white hover:text-yellow-400 text-3xl font-light cursor-pointer">✕</button>
            <div class="w-full overflow-y-auto max-h-[80vh] border border-gray-200 rounded-2xl">
                <img src="Libro-reclamaciones/AvisoVirtual_page1.png" alt="Aviso Virtual Oficial INDECOPI" class="w-full h-auto">
            </div>
            <p class="text-gray-500 text-[10px] mt-3 text-center">
                Aviso oficial de disponibilidad de Libro de Reclamaciones - INDECOPI.
            </p>
        </div>
    </div>

    <script>
        function toggleApoderado() {
            const checkbox    = document.getElementById('menor_edad');
            const fields      = document.getElementById('apoderado_fields');
            const nombresInput = document.getElementById('apoderado_nombres');
            const docNroInput  = document.getElementById('apoderado_doc_nro');
            if (checkbox.checked) {
                fields.classList.remove('hidden');
                nombresInput.required = true;
                docNroInput.required  = true;
            } else {
                fields.classList.add('hidden');
                nombresInput.required = false;
                docNroInput.required  = false;
                nombresInput.value    = '';
                docNroInput.value     = '';
            }
        }
        window.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('menor_edad')) toggleApoderado();
            <?php if (!empty($errorMsg)): ?>
            if (typeof hcaptcha !== 'undefined') { hcaptcha.reset(); }
            <?php endif; ?>
        });
        function openNoticeModal() {
            const modal = document.getElementById('notice-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeNoticeModal() {
            const modal = document.getElementById('notice-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNoticeModal(); });
    </script>
</body>
</html>
