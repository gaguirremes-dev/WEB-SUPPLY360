<?php
/**
 * Plantilla de configuración SMTP.
 * Copia este archivo a config.smtp.php y rellena tus credenciales reales.
 * config.smtp.php NO debe subirse al repositorio (.gitignore).
 */
define('SMTP_HOST', 'mail.supplylogistic360.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'reclamaciones@supplylogistic360.com');
define('SMTP_PASS', 'TU_CONTRASEÑA_AQUI');
define('SMTP_FROM_NAME', 'Supply Logistic 360');

define('EMPRESA_NOTIF_EMAIL', 'reclamaciones@supplylogistic360.com');

// hCaptcha (https://dashboard.hcaptcha.com)
define('HCAPTCHA_SITE_KEY',   'TU_SITE_KEY_AQUI');
define('HCAPTCHA_SECRET_KEY', 'TU_SECRET_KEY_AQUI');

// ── Seguridad del Libro de Reclamaciones (TODO OPCIONAL) ─────────────────────
// Si no defines estas constantes se usan los valores por defecto que se muestran aquí.

// Sal para anonimizar las IP: solo se guarda un hash SHA-256 salado, nunca la IP.
// Déjala vacía y el sistema crea una clave aleatoria propia (reclamaciones/install.key).
define('IP_HASH_SALT', '');

// Límites anti-spam
define('RATE_LIMIT_MAX',      1);     // envíos por IP dentro de la ventana
define('RATE_LIMIT_WINDOW',   1800);  // ventana en segundos (1800 = 30 minutos)
define('RATE_LIMIT_DAY_MAX',  5);     // envíos por IP cada 24 horas
define('SITE_LIMIT_HOUR_MAX', 20);    // envíos de todo el sitio por hora
define('SITE_LIMIT_DAY_MAX',  80);    // envíos de todo el sitio por día

// Verificación del certificado TLS del servidor SMTP. Mantenlo en true.
// Ponlo en false SOLO si los correos dejan de enviarse porque el certificado del
// servidor no coincide con SMTP_HOST (sigue cifrado, pero no valida la identidad del servidor).
define('SMTP_VERIFY_TLS', true);
