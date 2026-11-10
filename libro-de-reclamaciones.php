<?php
/**
 * Libro de Reclamaciones Virtual - Supply360
 * Conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor)
 * y el D.S. N° 011-2011-PCM (modificado por D.S. 101-2021-PCM).
 */

// Session: started by reclamosIniciarSesion() (HttpOnly, SameSite=Lax, Secure on HTTPS).
date_default_timezone_set('America/Lima');

define('DOMINIO_PERMITIDO', 'supplylogistic360.com');
define('EMPRESA_RAZON_SOCIAL', 'SUPPLY360 S.A.C.S.');
define('EMPRESA_RUC', '20615946207');
define('EMPRESA_DIRECCION', 'Av. República de Chile Nro. 324 Int. 601 Urb. Santa Beatriz (Edificio Polaris), Lima - Lima - Jesús María');
define('EMPRESA_EMAIL', 'reclamaciones@supplylogistic360.com');

$smtpConfigFile = __DIR__ . '/config.smtp.php';
if (file_exists($smtpConfigFile)) {
    require $smtpConfigFile;
}

if (!defined('SMTP_PORT'))           define('SMTP_PORT', 465);
if (!defined('SMTP_FROM_NAME'))      define('SMTP_FROM_NAME', 'Supply360');
if (!defined('EMPRESA_NOTIF_EMAIL')) define('EMPRESA_NOTIF_EMAIL', 'reclamaciones@supplylogistic360.com');

if (!defined('HCAPTCHA_SITE_KEY'))   define('HCAPTCHA_SITE_KEY',   '');
if (!defined('HCAPTCHA_SECRET_KEY')) define('HCAPTCHA_SECRET_KEY', '');

$recordsDir  = __DIR__ . '/reclamaciones';
$recordsFile = $recordsDir . '/records.json';
$lockFile    = $recordsDir . '/records.lock';

$logFile      = $recordsDir . '/security.log'; // directory is prepared by reclamosPrepararDirectorio()

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
    $pdf->SetFillColor(17, 17, 17);   // dark #111111
    $pdf->Rect(0, 0, 210, 38, 'F');
    $pdf->SetTextColor(246, 87, 38);  // accent #F65726
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

// ══════════════════════════════════════════════════════════════════════════════
// HARDENING CORE - identical in every complaints book. Keep the six copies in sync.
// ══════════════════════════════════════════════════════════════════════════════

// Optional limits / switches (override them in config.smtp.php).
foreach ([
    'RATE_LIMIT_MAX'      => 1,     // submissions per IP inside RATE_LIMIT_WINDOW
    'RATE_LIMIT_WINDOW'   => 1800,  // seconds (30 min)
    'RATE_LIMIT_DAY_MAX'  => 5,     // submissions per IP per 24 h
    'SITE_LIMIT_HOUR_MAX' => 20,    // site-wide submissions per hour
    'SITE_LIMIT_DAY_MAX'  => 80,    // site-wide submissions per 24 h
    'SMTP_VERIFY_TLS'     => true,  // verify the SMTP server certificate
    'IP_HASH_SALT'        => '',    // optional; empty = random per-install key file
] as $reclamosK => $reclamosV) {
    if (!defined($reclamosK)) define($reclamosK, $reclamosV);
}

// Cloudflare published ranges (https://www.cloudflare.com/ips/). Review from time to time.
define('RECLAMOS_CF_RANGES', [
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
    '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
    '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
]);
const RECLAMOS_MAX_BODY  = 65536; // reject bigger POST bodies (bytes)
const RECLAMOS_MIN_AGE   = 5;     // seconds after render before a submit is accepted
const RECLAMOS_MAX_AGE   = 7200;  // seconds after render before the form expires

function reclamosCabecerasSeguridad(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header_remove('X-Powered-By');
}

function reclamosIniciarSesion(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
          || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
          || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https' && reclamosEsCloudflare((string)($_SERVER['REMOTE_ADDR'] ?? '')));
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    session_cache_limiter(''); // keep our own Cache-Control header
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ── Client IP: REMOTE_ADDR, or CF-Connecting-IP only when the peer is Cloudflare ──
function reclamosNormalizarIp(string $ip): string {
    $ip = trim($ip);
    if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)) return $m[1];
    return $ip;
}
function reclamosIpEnCidr(string $ip, string $cidr): bool {
    $parts  = explode('/', $cidr, 2);
    $ipBin  = @inet_pton($ip);
    $netBin = @inet_pton($parts[0]);
    if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) return false;
    $bits  = isset($parts[1]) ? (int)$parts[1] : strlen($ipBin) * 8;
    $bytes = intdiv($bits, 8);
    $rem   = $bits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) return false;
    if ($rem === 0) return true;
    $mask = (0xFF << (8 - $rem)) & 0xFF;
    return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
}
function reclamosEsCloudflare(string $ip): bool {
    $ip = reclamosNormalizarIp($ip);
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) return false;
    foreach (RECLAMOS_CF_RANGES as $cidr) {
        if (reclamosIpEnCidr($ip, $cidr)) return true;
    }
    return false;
}
function reclamosClientIp(): string {
    $remote = reclamosNormalizarIp((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (filter_var($remote, FILTER_VALIDATE_IP) === false) return '0.0.0.0';
    if (reclamosEsCloudflare($remote)) {
        $cf = reclamosNormalizarIp((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) !== false) return $cf;
    }
    return $remote;
}

// ── Records directory: protected, never listable, per-install secret key ─────
function reclamosPrepararDirectorio(string $dir): bool {
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) return false;
    $ht  = $dir . '/.htaccess';
    $cur = is_file($ht) ? (string)@file_get_contents($ht) : '';
    if (strpos($cur, 'Require all denied') === false || strpos($cur, 'Options -Indexes') === false) {
        @file_put_contents($ht,
            "# Protected records directory: never served over HTTP.\n" .
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n" .
            "Options -Indexes\n");
    }
    if (!is_file($dir . '/index.html')) @file_put_contents($dir . '/index.html', '');
    return is_writable($dir);
}
function reclamosClaveInstalacion(string $dir): string {
    static $cache = [];
    if (isset($cache[$dir])) return $cache[$dir];
    $path = $dir . '/install.key';
    $key  = is_file($path) ? trim((string)@file_get_contents($path)) : '';
    if (strlen($key) < 32) {
        $fp = @fopen($path, 'c+');
        $key = '';
        if ($fp && flock($fp, LOCK_EX)) {
            $existing = trim((string)stream_get_contents($fp));
            if (strlen($existing) >= 32) {
                $key = $existing;
            } else {
                $new = bin2hex(random_bytes(32));
                ftruncate($fp, 0); rewind($fp); fwrite($fp, $new); fflush($fp);
                @chmod($path, 0600);
                $key = $new;
            }
            flock($fp, LOCK_UN);
        }
        if ($fp) fclose($fp);
    }
    return $cache[$dir] = $key;
}
// Salted SHA-256 of the IP: the only form in which an IP is ever stored.
function reclamosHashIp(string $ip, string $dir): string {
    $salt = (string)IP_HASH_SALT;
    if ($salt === '') $salt = reclamosClaveInstalacion($dir);
    if ($salt === '') return '';
    return hash('sha256', $salt . '|' . $ip);
}
function reclamosHuellaContenido(string $dir, string $detalle, string $email): string {
    $norm = mb_strtolower(preg_replace('/\s+/u', ' ', trim($detalle)), 'UTF-8') . '|' . strtolower($email);
    return hash_hmac('sha256', $norm, reclamosClaveInstalacion($dir));
}

// ── Logging (never secrets, bodies or personal data) ─────────────────────────
function logSecurityEvent(string $logFile, string $event, array $context = []): void {
    foreach (['email','nombres','doc_nro','apoderado_nombres','apoderado_doc_nro','detalle','pedido'] as $k) unset($context[$k]);
    if (@is_file($logFile) && @filesize($logFile) > 2097152) @rename($logFile, $logFile . '.1'); // crude rotation
    $line = date('Y-m-d H:i:s') . ' | ' . $event . ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ── Time trap: HMAC-signed render timestamp, bound to the session CSRF token ──
function reclamosTokenTiempo(string $dir, string $csrf): string {
    $key = reclamosClaveInstalacion($dir);
    if ($key === '' || $csrf === '') return '';
    $ts = (string)time();
    return $ts . '.' . hash_hmac('sha256', 'ts|' . $ts . '|' . $csrf, $key);
}
// Returns '' when valid, otherwise a short reason code.
function reclamosVerificarTiempo(string $dir, string $csrf, string $token): string {
    $key = reclamosClaveInstalacion($dir);
    if ($key === '' || $csrf === '') return 'TT_NO_KEY';
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $token, $m)) return 'TT_MALFORMED';
    $expected = hash_hmac('sha256', 'ts|' . $m[1] . '|' . $csrf, $key);
    if (!hash_equals($expected, $m[2])) return 'TT_BAD_SIGNATURE';
    $age = time() - (int)$m[1];
    if ($age < RECLAMOS_MIN_AGE) return 'TT_TOO_FAST';
    if ($age > RECLAMOS_MAX_AGE) return 'TT_EXPIRED';
    return '';
}

// ── Origin / Referer: compare parsed hosts, never substrings ─────────────────
function reclamosHostPermitido(string $host): bool {
    $host = rtrim(strtolower(trim($host)), '.');
    $dom  = strtolower((string)DOMINIO_PERMITIDO);
    return $host !== '' && ($host === $dom || $host === 'www.' . $dom);
}
function reclamosOrigenValido(): bool {
    $origin  = trim((string)($_SERVER['HTTP_ORIGIN']  ?? ''));
    $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
    if ($origin !== '') return reclamosHostPermitido((string)parse_url($origin, PHP_URL_HOST)); // "null" -> rejected
    if ($referer !== '') return reclamosHostPermitido((string)parse_url($referer, PHP_URL_HOST));
    return true; // some privacy setups strip both; CSRF + captcha + time trap still apply
}

// ── hCaptcha: fails closed (no keys / no answer / bad hostname => false) ─────
function verificarHCaptcha(string $token, string $secretKey): bool {
    if ($token === '' || strlen($token) > 4096 || $secretKey === '') return false;
    $post = ['secret' => $secretKey, 'response' => $token];
    if (HCAPTCHA_SITE_KEY !== '') $post['sitekey'] = HCAPTCHA_SITE_KEY;
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($post),
        'timeout' => 10,
    ]]);
    $result = @file_get_contents('https://hcaptcha.com/siteverify', false, $ctx);
    if ($result === false) return false;
    $data = json_decode($result, true);
    if (!is_array($data) || ($data['success'] ?? false) !== true) return false;
    if (isset($data['hostname']) && is_string($data['hostname']) && $data['hostname'] !== '') {
        if (!reclamosHostPermitido($data['hostname'])) return false;
    }
    return true;
}

// ── Input helpers ─────────────────────────────────────────────────────────────
function sanitizarCabecera(string $s): string {
    $s = str_ireplace(['%0d', '%0a'], '', $s);
    return trim((string)preg_replace('/[\x00-\x1F\x7F]+/', '', $s));
}
function esc(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
// Strips tags, control chars and invisible bidi/format chars; enforces UTF-8 and max length.
function limpiarTexto(?string $s, int $maxLen = 500, bool $multiline = false): string {
    if ($s === null || $s === '') return '';
    if (!mb_check_encoding($s, 'UTF-8')) return '';
    $s = strip_tags($s);
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = $multiline
        ? (string)preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/', '', $s)
        : (string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', $s);
    $s = (string)preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $s);
    if ($multiline) $s = (string)preg_replace("/\n{3,}/", "\n\n", $s);
    return mb_substr(trim($s), 0, $maxLen, 'UTF-8');
}
// Valid address (<= 254 chars, no header-breaking chars) or '' when invalid.
function reclamosEmail($raw): string {
    $e = trim((string)$raw);
    if ($e === '' || strlen($e) > 254 || preg_match('/[\x00-\x20\x7F<>,;"\'\\\\]/', $e)) return '';
    $ok = filter_var($e, FILTER_VALIDATE_EMAIL);
    return $ok === false ? '' : (string)$ok;
}
// Spam heuristics: more than 2 URLs in the free text, <a ...> tags or [url=] BBCode.
function reclamosContenidoSospechoso(array $textos): bool {
    $all = implode("\n", array_map('strval', $textos));
    if (preg_match('/<\s*a[\s>]/i', $all) || preg_match('/\[\s*(?:url|link)\b/i', $all)) return true;
    return preg_match_all('~(?:https?://|ftp://|www\.)\S+~i', $all) > 2;
}

// ── Request guards. Returns '' when everything passes, else a Spanish message ─
function reclamosGuardarSolicitud(string $dir, string $logFile, string $ipHash): string {
    $log = function (string $ev, array $ctx = []) use ($logFile, $ipHash): void {
        logSecurityEvent($logFile, $ev, $ctx + ['ip_hash' => $ipHash]);
    };
    $contacto = defined('EMPRESA_EMAIL') ? ' o escríbenos a ' . EMPRESA_EMAIL : '';

    if ($ipHash === '') {
        $log('NO_KEY_AVAILABLE');
        return 'No pudimos procesar tu solicitud en este momento. Inténtalo más tarde' . $contacto . '.';
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > RECLAMOS_MAX_BODY) {
        $log('PAYLOAD_TOO_LARGE');
        http_response_code(413);
        return 'La solicitud es demasiado grande. Reduce el texto e inténtalo de nuevo.';
    }
    // Honeypot: off-screen field, humans never fill it. Answer 200 and do nothing.
    if (!empty($_POST['hp_website'] ?? '')) {
        $log('HONEYPOT_TRIGGERED');
        http_response_code(200);
        exit;
    }
    foreach ($_POST as $v) {
        if (!is_string($v) || !mb_check_encoding($v, 'UTF-8')) {
            $log('BAD_INPUT_ENCODING');
            return 'El formulario contiene datos no válidos. Recarga la página e inténtalo de nuevo.';
        }
    }
    $csrfPost = (string)($_POST['csrf_token'] ?? '');
    $csrfSess = (string)($_SESSION['csrf_token'] ?? '');
    if ($csrfPost === '' || $csrfSess === '' || !hash_equals($csrfSess, $csrfPost)) {
        $log('CSRF_FAILED');
        return 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    }
    $tt = reclamosVerificarTiempo($dir, $csrfSess, (string)($_POST['form_ts'] ?? ''));
    if ($tt !== '') {
        $log('TIME_TRAP', ['reason' => $tt]);
        return $tt === 'TT_TOO_FAST'
            ? 'Enviaste el formulario demasiado rápido. Espera unos segundos e inténtalo de nuevo.'
            : 'El formulario expiró o no es válido. Revisa tus datos e inténtalo de nuevo.';
    }
    if (!reclamosOrigenValido()) {
        $log('ORIGIN_REJECTED');
        return 'Solicitud rechazada. Recarga la página e inténtalo de nuevo.';
    }
    if (reclamosContenidoSospechoso([$_POST['detalle'] ?? '', $_POST['pedido'] ?? '', $_POST['bien_desc'] ?? '', $_POST['servicio'] ?? ''])) {
        $log('SPAM_CONTENT');
        return 'Tu mensaje contiene enlaces o formato no permitido. Quita los enlaces e inténtalo de nuevo.';
    }
    if (HCAPTCHA_SECRET_KEY === '' || HCAPTCHA_SITE_KEY === '') {
        $log('CAPTCHA_NOT_CONFIGURED');
        return 'El servicio no está disponible temporalmente. Inténtalo más tarde' . $contacto . '.';
    }
    if (!verificarHCaptcha((string)($_POST['h-captcha-response'] ?? ''), HCAPTCHA_SECRET_KEY)) {
        $log('CAPTCHA_FAILED');
        return 'Por favor, completa el desafío de seguridad (captcha) antes de enviar.';
    }
    return '';
}

// ── Atomic rate limit (per IP-hash + site-wide) and duplicate-content check ──
// Reserves the slot when everything passes. Returns '' on success, else a Spanish message.
function reclamosReservarEnvio(string $dir, string $ipHash, string $dupHash, string $logFile): string {
    $contacto = defined('EMPRESA_EMAIL') ? ' o escríbenos a ' . EMPRESA_EMAIL : '';
    $generic  = 'No pudimos registrar tu solicitud en este momento. Inténtalo más tarde' . $contacto . '.';
    $stateFile = $dir . '/rate_state.json';
    @unlink($dir . '/rate_limits.json');      // legacy file kept raw IPs
    @unlink($dir . '/rate_limits.json.lock');
    $fp = @fopen($stateFile . '.lock', 'c');
    if (!$fp || !flock($fp, LOCK_EX)) {
        if ($fp) fclose($fp);
        logSecurityEvent($logFile, 'RATE_LOCK_FAILED', ['ip_hash' => $ipHash]);
        return $generic; // fail closed
    }
    $now  = time();
    $data = [];
    if (is_file($stateFile)) {
        $raw  = (string)@file_get_contents($stateFile);
        $data = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    }
    $ips  = is_array($data['ip']   ?? null) ? $data['ip']   : [];
    $site = is_array($data['site'] ?? null) ? $data['site'] : [];
    $dups = is_array($data['dup']  ?? null) ? $data['dup']  : [];
    $fresh = function ($ts) use ($now): bool { return is_int($ts) && ($now - $ts) < 86400; };
    $site = array_values(array_filter($site, $fresh));
    $dups = array_filter($dups, $fresh);
    foreach ($ips as $h => $list) {
        $ips[$h] = is_array($list) ? array_values(array_filter($list, $fresh)) : [];
        if (!$ips[$h]) unset($ips[$h]);
    }
    $mine     = $ips[$ipHash] ?? [];
    $inWindow = count(array_filter($mine, function ($t) use ($now) { return ($now - $t) < RATE_LIMIT_WINDOW; }));
    $siteHour = count(array_filter($site, function ($t) use ($now) { return ($now - $t) < 3600; }));
    $err = '';
    if ($inWindow >= RATE_LIMIT_MAX) {
        $err = 'RATE_LIMIT_IP';
        $msg = 'Has enviado un reclamo recientemente. Por favor, espera ' . (int)ceil(RATE_LIMIT_WINDOW / 60) . ' minutos antes de volver a intentarlo.';
    } elseif (count($mine) >= RATE_LIMIT_DAY_MAX) {
        $err = 'RATE_LIMIT_IP_DAY';
        $msg = 'Has alcanzado el límite diario de envíos desde tu conexión. Inténtalo nuevamente mañana' . $contacto . '.';
    } elseif ($siteHour >= SITE_LIMIT_HOUR_MAX || count($site) >= SITE_LIMIT_DAY_MAX) {
        $err = 'RATE_LIMIT_SITE';
        $msg = 'Estamos recibiendo muchas solicitudes en este momento. Inténtalo nuevamente en unos minutos' . $contacto . '.';
    } elseif ($dupHash !== '' && isset($dups[$dupHash])) {
        $err = 'DUPLICATE_CONTENT';
        $msg = 'Ya recibimos un reclamo con el mismo contenido. Si necesitas ayuda, comunícate con nosotros' . $contacto . '.';
    }
    if ($err !== '') {
        flock($fp, LOCK_UN); fclose($fp);
        logSecurityEvent($logFile, $err, ['ip_hash' => $ipHash]);
        return $msg;
    }
    $mine[]         = $now;
    $ips[$ipHash]   = $mine;
    $site[]         = $now;
    if ($dupHash !== '') $dups[$dupHash] = $now;
    $written = @file_put_contents($stateFile, json_encode(['ip' => $ips, 'site' => $site, 'dup' => $dups]));
    flock($fp, LOCK_UN); fclose($fp);
    if ($written === false) {
        logSecurityEvent($logFile, 'RATE_WRITE_FAILED', ['ip_hash' => $ipHash]);
        return $generic;
    }
    return '';
}

// ── SMTP (implicit TLS on 465, STARTTLS otherwise; certificate verified by default) ──
function enviarCorreoSMTP(string $toEmail, string $subject, string $htmlBody, string $pdfB64 = '', string $pdfName = '', string &$err = ''): bool {
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        $err = 'SMTP configuration missing.';
        return false;
    }
    $toEmail = sanitizarCabecera($toEmail);
    if (strlen($toEmail) > 254 || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid recipient address.';
        return false;
    }
    $subject   = mb_substr(sanitizarCabecera($subject), 0, 200, 'UTF-8');
    $pdfName   = (string)preg_replace('/[^A-Za-z0-9._-]/', '_', $pdfName);
    $fromEmail = sanitizarCabecera(SMTP_USER);
    $replyTo   = sanitizarCabecera(EMPRESA_EMAIL);
    $enc = function (string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; };
    $eol = "\r\n";
    $headers  = 'Date: ' . date('r') . $eol;
    $headers .= 'From: ' . $enc(sanitizarCabecera(SMTP_FROM_NAME)) . ' <' . $fromEmail . '>' . $eol;
    $headers .= 'To: <' . $toEmail . '>' . $eol;
    $headers .= 'Subject: ' . $enc($subject) . $eol;
    $headers .= 'Reply-To: ' . $replyTo . $eol;
    $headers .= 'MIME-Version: 1.0' . $eol;
    if ($pdfB64) {
        $boundary = '----=_Part_' . md5(uniqid('', true));
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

    $verify = filter_var(SMTP_VERIFY_TLS, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $verify = ($verify === null) ? true : $verify;
    $sslOpts = ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify, 'SNI_enabled' => true];
    if ($verify) $sslOpts['peer_name'] = SMTP_HOST;
    $ctx = stream_context_create(['ssl' => $sslOpts]);
    $implicitTls = in_array((int)SMTP_PORT, [465, 4655], true);
    $fp = @stream_socket_client(($implicitTls ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $err = "SMTP connection failed: $errstr ($errno)"; return false; }
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
    if (!$implicitTls) { // never send credentials in clear text
        if (!$ok($cmd('STARTTLS'), ['220']))                      return $fail();
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $err = 'STARTTLS negotiation failed.'; return $fail(); }
        if (!$ok($cmd('EHLO ' . SMTP_HOST), ['250']))             return $fail();
    }
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

// ── Runtime bootstrap (hardened) ──────────────────────────────────────────────
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
reclamosCabecerasSeguridad();
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    exit;
}
reclamosIniciarSesion();

$success = false; $errorMsg = ''; $generatedCode = ''; $submittedData = []; $debugLog = [];
if (!reclamosPrepararDirectorio($recordsDir)) {
    $errorMsg = 'El servicio no está disponible temporalmente. Inténtalo más tarde o escríbenos a ' . EMPRESA_EMAIL . '.';
}
// The raw IP is never kept: $clientIP holds the salted hash (it is what the logs record).
$ipHash   = reclamosHashIp(reclamosClientIp(), $recordsDir);
$clientIP = $ipHash;

set_error_handler(function ($errno) use (&$errorMsg) {
    if ($errno === E_ERROR || $errno === E_USER_ERROR) $errorMsg = 'Error interno del servidor. Por favor, inténtelo más tarde.';
    return true;
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMsg === '') {

    // ── 1-5. Guards: size, honeypot, encoding, CSRF, time trap, origin, spam, captcha ──
    $errorMsg = reclamosGuardarSolicitud($recordsDir, $logFile, $ipHash);

    // ── 6. Sanitizar y validar ────────────────────────────────────────────────
    if (empty($errorMsg)) {
        $nombres            = limpiarTexto($_POST['nombres']            ?? '', 200);
        $doc_tipo           = $_POST['doc_tipo']           ?? '';
        $doc_nro            = limpiarTexto($_POST['doc_nro']            ?? '', 20);
        $email              = reclamosEmail($_POST['email'] ?? '');
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
        $bien_desc          = limpiarTexto($_POST['bien_desc']          ?? '', 500, true);
        $reclamo_tipo       = $_POST['reclamo_tipo']       ?? '';
        $detalle            = limpiarTexto($_POST['detalle']            ?? '', 2000, true);
        $pedido             = limpiarTexto($_POST['pedido']             ?? '', 2000, true);
        $monto              = number_format(max(0.0, (float)preg_replace('/[^\d.]/', '', $_POST['monto'] ?? '0')), 2, '.', '');

        if (!in_array($doc_tipo,           ['DNI','CE','PASAPORTE','RUC'], true)) $doc_tipo           = 'DNI';
        if (!in_array($apoderado_doc_tipo, ['DNI','CE','PASAPORTE'],        true)) $apoderado_doc_tipo = 'DNI';
        if (!in_array($bien_tipo,          ['producto','servicio'],          true)) $bien_tipo          = 'producto';
        if (!in_array($reclamo_tipo,       ['reclamo','queja'],              true)) $reclamo_tipo       = 'reclamo';

        if (!$nombres || !$doc_nro || !$email || !$direccion || !$detalle || !$pedido) {
            $errorMsg = 'Por favor, rellene todos los campos obligatorios del formulario.';
        }
    }

    // ── 6b. Atomic rate limit (IP + site-wide) and duplicate-content check ─────
    if (empty($errorMsg)) {
        $errorMsg = reclamosReservarEnvio($recordsDir, $ipHash, reclamosHuellaContenido($recordsDir, $detalle, $email), $logFile);
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
            <div style='background:#111111;padding:25px;text-align:center;'>
                <h2 style='margin:0;font-size:22px;color:#F65726;'>HOJA DE RECLAMACIÓN VIRTUAL</h2>
                <p style='margin:5px 0 0;color:#fff;font-size:14px;font-weight:bold;'>Código: " . esc($generatedCode) . "</p>
            </div>
            <div style='padding:25px;line-height:1.6;'>
                <p>Estimado(a) <strong>" . esc($nombres) . "</strong>,</p>
                <p>Confirmamos la recepción de tu reclamación registrada el <strong>" . date('d/m/Y') . "</strong>. Adjunto encontrarás el cargo de tu Hoja de Reclamación Virtual.</p>
                <p>De acuerdo con la legislación vigente en el Perú (Ley N° 29571), daremos respuesta a tu requerimiento en un plazo máximo de <strong>15 días hábiles</strong>.</p>
                <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
                <h3 style='color:#111111;'>Detalle de la Reclamación</h3>
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
        $mailClienteSubj = 'Cargo de Hoja de Reclamación N° ' . $generatedCode . ' — Supply360';
        $mailClienteBody = $emailBody;

        $emailBodyEmpresa = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;padding:25px;'>
            <h2 style='color:#dc2626;margin-top:0;'>Nuevo Reclamo/Queja Registrado</h2>
            <p>Código: <strong>" . esc($generatedCode) . "</strong> — Plazo máximo: <strong>15 días hábiles</strong>.</p>
            <h3 style='border-bottom:2px solid #eee;padding-bottom:5px;color:#111111;'>1. Reclamante</h3>
            <p><strong>Nombre:</strong> " . esc($nombres) . "<br><strong>Doc:</strong> " . esc($doc_tipo) . " " . esc($doc_nro) . "<br>
            <strong>Tel:</strong> " . esc($telefono) . "<br><strong>Email:</strong> " . esc($email) . "<br>
            <strong>Dirección:</strong> " . esc($direccion) . ", " . esc($distrito) . " - " . esc($provincia) . " (" . esc($departamento) . ")</p>
            <h3 style='border-bottom:2px solid #eee;padding-bottom:5px;color:#111111;'>2. Bien Contratado</h3>
            <p><strong>Tipo:</strong> " . esc(ucfirst($bien_tipo)) . " — S/. " . esc($monto) . "<br><strong>Descripción:</strong> " . esc($bien_desc) . "</p>
            <h3 style='border-bottom:2px solid #eee;padding-bottom:5px;color:#111111;'>3. Reclamo</h3>
            <p><strong>Tipo:</strong> " . esc(strtoupper($reclamo_tipo)) . "</p>
            <div style='background:#f7f7f7;padding:12px;border-left:4px solid #dc2626;margin-bottom:10px;'><strong>Detalle:</strong><br>" . nl2br(esc($detalle)) . "</div>
            <div style='background:#f7f7f7;padding:12px;border-left:4px solid #F65726;'><strong>Pedido:</strong><br>" . nl2br(esc($pedido)) . "</div>
        </div>";

        $errEmpresa = '';
        $okEmpresa = enviarCorreoSMTP(EMPRESA_NOTIF_EMAIL, 'NUEVA HOJA DE RECLAMACIÓN N° ' . $generatedCode . ' — ' . $nombres, $emailBodyEmpresa, $pdfB64, $pdfName, $errEmpresa);
        if ($okEmpresa) {
            // Consumer copy only after the internal mail was accepted (anti mail-bombing).
            if (!enviarCorreoSMTP($email, $mailClienteSubj, $mailClienteBody, $pdfB64, $pdfName, $errCliente)) {
                logSecurityEvent($logFile, 'SMTP_CLIENT_FAILED', ['detail' => substr($errCliente, 0, 160)]);
            }
        } else {
            logSecurityEvent($logFile, 'SMTP_INTERNAL_FAILED', ['detail' => substr($errEmpresa, 0, 160)]);
        }

        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
        session_regenerate_id(true);
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
                        navy: { deep: '#111111', mid: '#1C1C1C', light: '#495057' },
                        brand: { DEFAULT: '#F65726', light: '#FF9A80', pale: '#FFF0EC' },
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
            border: 1px solid rgba(246,87,38,0.22);
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .grad-text {
            background: linear-gradient(135deg, #F65726 0%, #FF7A52 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .form-input {
            width: 100%;
            background: rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.14);
            border-radius: 12px;
            padding: 10px 14px;
            color: #111827;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-input::placeholder { color: rgba(0,0,0,0.3); }
        .form-input:focus { border-color: #F65726; box-shadow: 0 0 0 2px rgba(246,87,38,0.18); }
        select.form-input { background-color: #FFFFFF; color: #111827; }
        select.form-input option { background: #FFFFFF; color: #111827; }

        .btn-sec {
            border: 1px solid rgba(0,0,0,0.25);
            color: #111111;
            background: transparent;
            transition: all 0.3s;
        }
        .btn-sec:hover { background: rgba(0,0,0,0.06); transform: translateY(-1px); }

        .btn-primary {
            background: linear-gradient(135deg, #F65726, #D44A1F);
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(246,87,38,0.32); }

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
                <img src="assets/logo/LOGO SUPPLY 360.png" alt="Supply360" class="h-10 w-10 rounded-lg object-contain bg-white p-0.5 border border-navy-deep/10">
                <div>
                    <div class="text-navy-deep font-black text-sm tracking-wide">SUPPLY<span class="text-brand">360</span></div>
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
                        <div class="text-xs text-brand font-black uppercase tracking-wider print-label">CÓDIGO DE RECLAMACIÓN</div>
                        <div class="text-xl font-black text-emerald-600 print-title mt-0.5"><?= htmlspecialchars($submittedData['codigo']) ?></div>
                        <div class="text-[10px] text-navy-deep/40 mt-1">Fecha de registro: <?= htmlspecialchars($submittedData['fecha']) ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-xs text-navy-deep/60 border-b border-navy-deep/5 pb-4 print-field">
                    <div><span class="block font-bold text-navy-deep/80 print-label">Proveedor:</span><?= EMPRESA_RAZON_SOCIAL ?></div>
                    <div><span class="block font-bold text-navy-deep/80 print-label">RUC:</span><?= EMPRESA_RUC ?></div>
                    <div class="md:col-span-2"><span class="block font-bold text-navy-deep/80 print-label">Domicilio Fiscal:</span><?= EMPRESA_DIRECCION ?></div>
                </div>

                <h3 class="text-sm font-bold uppercase tracking-wider text-brand mb-3 print-label">1. Identificación del Consumidor Reclamante</h3>
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

                <h3 class="text-sm font-bold uppercase tracking-wider text-brand mb-3 print-label">2. Identificación del Bien Contratado</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 text-xs text-navy-deep/70 border-b border-navy-deep/5 pb-4 print-field">
                    <div><span class="block text-navy-deep/40 print-label">Tipo de Bien:</span><span class="text-navy-deep text-sm capitalize"><?= htmlspecialchars($submittedData['bien_tipo']) ?></span></div>
                    <div><span class="block text-navy-deep/40 print-label">Monto Reclamado:</span><span class="text-navy-deep text-sm font-bold">S/. <?= htmlspecialchars($submittedData['monto']) ?></span></div>
                    <div class="sm:col-span-2"><span class="block text-navy-deep/40 print-label">Descripción:</span><span class="text-navy-deep"><?= htmlspecialchars($submittedData['bien_desc'] ?: 'No especificado') ?></span></div>
                </div>

                <h3 class="text-sm font-bold uppercase tracking-wider text-brand mb-3 print-label">3. Detalle de la Reclamación y Pedido del Consumidor</h3>
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
                <span class="w-2 h-2 rounded-full bg-brand"></span>
                <span class="text-brand text-[10px] font-semibold tracking-widest uppercase">Ley N° 29571</span>
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
                        <input type="hidden" name="form_ts" value="<?= esc(reclamosTokenTiempo($recordsDir, (string)($_SESSION['csrf_token'] ?? ''))) ?>">
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
                                    <input type="checkbox" id="menor_edad" name="menor_edad" class="w-4 h-4 rounded accent-brand flex-shrink-0 cursor-pointer" onclick="toggleApoderado()" <?= isset($_POST['menor_edad']) ? 'checked' : '' ?>>
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
                                            <input type="radio" name="bien_tipo" value="producto" required class="w-4 h-4 accent-brand" <?= !isset($_POST['bien_tipo']) || $_POST['bien_tipo']=='producto' ? 'checked':'' ?>>
                                            <span class="text-navy-deep/80 text-sm">Producto</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="bien_tipo" value="servicio" class="w-4 h-4 accent-brand" <?= isset($_POST['bien_tipo']) && $_POST['bien_tipo']=='servicio' ? 'checked':'' ?>>
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
                                            <input type="radio" name="reclamo_tipo" value="reclamo" required class="w-4 h-4 mt-0.5 accent-brand" <?= !isset($_POST['reclamo_tipo']) || $_POST['reclamo_tipo']=='reclamo' ? 'checked':'' ?>>
                                            <div>
                                                <span class="block text-navy-deep text-xs font-bold uppercase tracking-wider">Reclamo</span>
                                                <span class="text-[10px] text-navy-deep/50 leading-tight block mt-0.5">Disconformidad relacionada a los productos o servicios contratados.</span>
                                            </div>
                                        </label>
                                        <label class="flex items-start gap-2.5 p-3 rounded-xl bg-navy-deep/5 border border-navy-deep/5 cursor-pointer hover:bg-brand-pale hover:border-brand/20 transition-colors">
                                            <input type="radio" name="reclamo_tipo" value="queja" class="w-4 h-4 mt-0.5 accent-brand" <?= isset($_POST['reclamo_tipo']) && $_POST['reclamo_tipo']=='queja' ? 'checked':'' ?>>
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
                                <input type="checkbox" required class="w-4 h-4 mt-0.5 rounded accent-brand flex-shrink-0 cursor-pointer">
                                <span class="text-navy-deep/40 text-[10px] leading-relaxed">Declaro ser el usuario titular y que los datos consignados en esta Hoja de Reclamación son reales y verdaderos.</span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" required class="w-4 h-4 mt-0.5 rounded accent-brand flex-shrink-0 cursor-pointer">
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
                            <button onclick="openNoticeModal()" class="bg-brand text-white font-bold px-4 py-2 rounded-xl text-xs shadow-lg hover:scale-105 transition-transform cursor-pointer">
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
            <button onclick="closeNoticeModal()" class="absolute -top-10 right-0 sm:-right-8 text-white hover:text-brand text-3xl font-light cursor-pointer">✕</button>
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
