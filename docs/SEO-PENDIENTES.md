# SEO: pendientes manuales (requieren iniciar sesión)

Datos NAP (usar idénticos en todas partes):

| Campo | Valor |
|---|---|
| Nombre | Supply360 (razón social: Supply360 S.A.C.S., RUC 20615946207) |
| Dirección | Av. República de Chile 324 Int. 601, Edificio Polaris, Jesús María, Lima, Perú |
| Teléfono / WhatsApp | +51 993 527 864 (https://wa.me/51993527864) |
| Correo | comercial@supplylogistic360.com |
| Web | https://supplylogistic360.com/ |
| Redes | Facebook, Instagram, TikTok y YouTube (ver `sameAs` en index.html) |

## 1. Despliegue previo
1. Hacer commit/push (cPanel Git) y confirmar que el deploy copió `servicios/`, `politica-de-privacidad/`, `terminos-y-condiciones/`, `404.html`.
2. Comprobar en el servidor que `public_html/.htaccess` contiene los bloques `SUPPLY360 SEO RULES` y `SUPPLY360 SECURITY RULES`.
3. Comprobar que `https://supplylogistic360.com/config.smtp.php` responde 403/404 y que `/servicios/` responde 200.

## 2. Google Search Console
1. Verificar la propiedad tipo dominio por DNS (ya existe una meta de verificación en index.html; conservarla).
2. Sitemaps: enviar `https://supplylogistic360.com/sitemap.xml`.
3. Inspección de URL: solicitar indexación de `/`, `/servicios/` y los 7 servicios (hay límite diario; no garantiza nada).
4. Revisar a las 2 y 4 semanas: Páginas indexadas, Rendimiento y Core Web Vitals.

## 3. Google Analytics 4 (aún sin ID: no hay etiqueta en el sitio)
1. Crear propiedad GA4 y un flujo web para https://supplylogistic360.com.
2. Instalar la etiqueta `G-XXXXXXXXXX` una sola vez por página (gtag o GTM, no ambos).
3. Marcar como eventos clave: clic en WhatsApp (`wa.me`), clic en correo (`mailto:`), clic en teléfono (`tel:`) y envío del Libro de Reclamaciones.
4. Vincular GA4 con Search Console: Admin > Product links > Search Console links.
5. IMPORTANTE: al instalar GA4, actualizar la sección 9 de `/politica-de-privacidad/` (hoy dice que no hay analítica) y evaluar un aviso de cookies con consentimiento.

## 4. Google Business Profile
1. Crear/reclamar una sola ficha para Supply360.
2. Modelo: negocio de área de servicio (ocultar dirección si no se atiende público en la oficina) con base en Jesús María, Lima; definir hasta 20 zonas de servicio (distritos/ciudades), no el país entero.
3. Categoría principal (p. ej. servicio de logística), servicios, horarios reales, fotos reales y enlace a la web.
4. Verificación: preparar fotos/video de la oficina, letrero y operación si Google lo pide.
5. NAP idéntico al de la tabla superior.

## 5. Bing Webmaster Tools
1. Importar el sitio desde Search Console o verificar por DNS.
2. Enviar el sitemap.

## 6. Citaciones y backlinks (coherencia NAP)
1. Registrar el mismo NAP en directorios empresariales peruanos y de logística, y en perfiles de redes sociales (ya enlazados en el schema).
2. Pedir enlaces a clientes, proveedores, cámaras y asociaciones del sector (enlace a la home o a la página de servicio relevante).
3. Evitar compra masiva de enlaces.

## 7. Legal (revisión humana)
1. Pedir a un abogado peruano que revise la política de privacidad y los términos (son una plantilla sólida, no asesoría legal).
2. Confirmar si corresponde inscribir el banco de datos personales ante la ANPD; la política no afirma que esté inscrito.
3. Confirmar el número y vigencia del Reglamento de la Ley N° 29733 citado (D.S. 016-2024-JUS) y los plazos de conservación de reclamos.
