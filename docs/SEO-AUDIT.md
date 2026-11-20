# SEO audit: supplylogistic360.com (2026-11-17)

Resumen: sitio estático en español (es-PE) para Perú. Rastreo e indexación sin bloqueos conocidos en el código; 7 páginas de servicio, un hub, páginas legales, 404 real y sitemap/robots consistentes. Lo que falta es medición (GA4) y presencia (Search Console, Google Business Profile), que requieren sesión. Nada se verificó contra producción: el estado se basa en el código del repositorio.

Estado: ✅ ok, ⚠️ mejorar, ❌ falla, 👤 manual. Prioridad: P1 bloquea, P2 alto impacto, P3 mejora.

| Fase | Elemento | Estado | Evidencia | Acción | Prio |
|---|---|---|---|---|---|
| F1 | Etiqueta GA4 | 👤 | No hay gtag/GTM en ningún HTML | Crear propiedad e instalar (ver SEO-PENDIENTES.md) | P2 |
| F1 | Eventos clave | 👤 | Existen CTAs wa.me, mailto, tel y formulario de reclamos | Definir eventos | P2 |
| F1 | Aviso de privacidad y analítica | ✅ | /politica-de-privacidad/ indica que hoy no hay analítica | Actualizarla al instalar GA4 | P2 |
| F2 | Search Console | 👤 | Meta google-site-verification en index.html; no verificable desde código | Enviar sitemap, pedir indexación | P1 |
| F2 | Google Business Profile | 👤 | No verificable | Crear ficha de área de servicio en Jesús María | P2 |
| F2 | NAP coherente | ✅ | Mismos datos en footer, schema y docs | Replicar en fichas y directorios | P2 |
| F2 | Bing Webmaster | 👤 | No verificable | Importar desde Search Console | P3 |
| F3 | robots.txt | ✅ | Allow /, Sitemap, Disallow solo de config.smtp*, /reclamaciones/, /lib/; CSS/JS/imágenes abiertos | Comprobar 200 en producción | P1 |
| F3 | noindex heredado | ✅ | Solo 404.html lleva noindex (intencional) | Ninguna | P1 |
| F3 | Sitemap | ✅ | 12 URLs, lastmod 2026-11-17, todas iguales a su canonical; sin changefreq/priority | Mantener lastmod real | P1 |
| F3 | Canonical y redirecciones | ✅ | Canonical en todas las páginas; reglas www a no-www y HTTP a HTTPS en .htaccess | Probar con curl -I tras el deploy | P1 |
| F3 | Archivos de servidor protegidos | ✅ | .htaccess.security niega config.smtp*.php, .cpanel.yml, archivos .git | Verificar 403 en producción | P1 |
| F4 | Títulos 50-60 | ✅ | Todas las páginas indexables entre 51 y 59 | Revisar con datos reales de Search Console | P3 |
| F4 | Descripciones 120-160 | ✅ | Todas entre 124 y 160 (la 404 es noindex) | Idem | P3 |
| F4 | Un H1, lang, viewport | ✅ | 1 H1 por página (el libro tiene un segundo H1 solo en el estado de éxito) | Ninguna | P3 |
| F4 | Enlazado interno | ✅ | Hub /servicios/, breadcrumbs, footer con servicios y legales | Añadir enlaces contextuales desde la home a los 7 servicios | P3 |
| F5 | Páginas por intención | ⚠️ | 1 página por servicio con FAQ | Investigar consultas reales en Search Console y ampliar contenido, casos y fotos propias | P2 |
| F5 | Contenido propio | ⚠️ | Texto genérico de servicio | Añadir casos reales, rangos de precio y fotos | P2 |
| F6 | HTTPS y versión única | ✅ | Reglas en .htaccess | Verificar en producción | P1 |
| F6 | Schema | ✅ | Organization+LocalBusiness y WebSite (home), Service/ItemList y BreadcrumbList (hub), Service+FAQ+Breadcrumb (servicios); JSON-LD parsea; sin horarios, coordenadas ni valoraciones | Validar con la Prueba de resultados enriquecidos; añadir postalCode si se conoce | P3 |
| F6 | 404 real | ✅ | ErrorDocument 404 /404.html | Comprobar código 404 | P2 |
| F6 | Core Web Vitals | ⚠️ | Mejoras aplicadas (ver "Rendimiento"); queda GSAP/Lenis desde CDN | Medir en PageSpeed Insights con datos de campo tras el deploy | P2 |
| F6 | Enlaces rotos | ✅ | Revisión de enlaces internos sin fallos | Repetir tras cambios | P2 |
| F7 | Búsqueda con IA | ✅ | Sin requisitos extra; páginas rastreables e indexables | Mantener ficha de Google al día | P3 |
| Legal | Privacidad, términos, libro | ⚠️ | Páginas publicadas y enlazadas en todos los footers | Revisión por abogado peruano | P2 |

## Cambios aplicados
- Teléfono real (+51 993 527 864) y WhatsApp en home, servicios y footers; schema con telephone.
- Schema de la home ampliado (Organization+LocalBusiness, WebSite).
- Nuevas páginas: /servicios/, /politica-de-privacidad/, /terminos-y-condiciones/.
- Breadcrumbs de servicios apuntan a /servicios/; footers con enlaces legales.
- Libro de reclamaciones: title, canonical, robots y enlaces legales.
- robots.txt, sitemap.xml, .htaccess.security y .cpanel.yml actualizados.

## Rendimiento y Core Web Vitals (2026-11-17)
Objetivo móvil: LCP <= 2.5 s, INP <= 200 ms, CLS <= 0.1. Los tamaños son medidos en disco; los tiempos reales deben medirse en producción (no hay navegador en el entorno de trabajo).

**Medido (peso de medios de la home, escritorio 1x, scroll completo)**
- Antes: 24.16 MB. Después: 2.31 MB.
- Medios del primer viewport: 5.93 MB antes (logo PNG 169 KB + sello ISO PNG 194 KB + video de 5.56 MB con autoplay) frente a 0.10 MB después (logo webp 13 KB + sello webp 72 KB + poster 12 KB); el video de 0.65 MB arranca tras el evento load.
- Video hero: 5.56 MB a 0.65 MB (960x540, sin audio, faststart). Video de casos: 7.16 MB a 1.13 MB (sin audio) y solo se pide al acercarse al viewport.
- Fotos Card A/B/C: 3.89 / 3.11 / 3.23 MB (JPEG 2752 px) a webp de 31-212 KB en tres anchos (480/800/1280) con srcset y sizes.
- Logo del menú: 169 KB (PNG 5431 px) a 13 KB (webp 360 px). Logo blanco del footer: 39 KB a 10 KB. Sello ISO del footer: 563 KB a 37 KB.
- Los originales no se borraron (siguen como respaldo y para el JSON-LD).

**Aplicado**
- LCP: poster del hero (webp 12 KB) precargado con fetchpriority=high; videos con poster, preload=none, muted, playsinline; el archivo se pide tras load y solo si no hay ahorro de datos, 2G ni prefers-reduced-motion (js/main.js, initVideos). En las páginas de servicio el LCP es texto (H1), sin imágenes sobre el pliegue.
- Recursos bloqueantes: CSS de Swiper (sin uso, CDN) eliminado; Google Fonts carga sin bloquear el render (media=print + onload, con noscript), preconnect ya existía, display=swap ya existía; Inter 300 eliminado (sin uso).
- Libro: Tailwind Play CDN (script de 407 KB, bloqueante, 3.4.17) reemplazado por css/libro.css (17 KB minificado, compilado con Tailwind 3.4.17 real, mismo config navy/brand/Inter, con Preflight), cargado con ?v=20261118 tras el <style> inline para conservar el orden de cascada. Verificado con Chromium a 390 y 1280 px: capturas idénticas (0 px de diferencia) y estilos computados iguales. Si se agregan clases nuevas al PHP hay que recompilar el CSS.
- Scripts: defer en GSAP, ScrollTrigger, Lenis y main.js; todos los CDN con versión exacta (gsap 3.12.7, lenis 1.1.18, tailwind 3.4.17). Se quitó SplitText: esa URL devuelve 404 en gsap 3.12.7 (js/main.js ya tenía el plan B, así que no cambia lo visible).
- CLS: width/height en todas las imágenes, picture con webp para sellos ISO, logo del menú con proporción correcta (125x40).
- Caché: ?v=20261117 en CSS/JS de todas las páginas; .htaccess.perf (nuevo, con su propio marcador SUPPLY360 PERF RULES y línea en .cpanel.yml) pone CSS/JS a 1 año con immutable, HTML con revalidación y cabeceras de seguridad (nosniff, Referrer-Policy, X-Frame-Options SAMEORIGIN, Permissions-Policy) solo para supplylogistic360.com y solo archivos estáticos (el PHP del libro conserva sus propias cabeceras, más estrictas). Todo dentro de IfModule; sin CSP a propósito (rompería CDN y hCaptcha).
- SEO: hreflang es-PE y x-default autorreferenciados en todas las páginas indexables, og:image 1200x630 (recorte de reunion-estrategica.webp, foto propia) con tipo, ancho, alto y alt, twitter:image y alt, theme-color y manifest también en el libro, site.webmanifest con colores de marca coherentes (theme #F65726) y lang es-PE.
- Accesibilidad: enlace "Saltar al contenido principal" funcional y primero en el body (en la home el anterior era invisible y estaba al final), <main> en la home, anillo de foco :focus-visible, etiquetas label/for asociadas en el formulario del libro, aria-label en el botón de cerrar el aviso, role=dialog en el modal.
- Contraste del footer: rgba(255,255,255,.35) sobre #0D0D0D daba 3.2:1 (falla). Ahora .55 = 6.2:1; la línea de copyright (.2) pasa a .5 = 5.3:1; la dirección (.28) pasa a .55. Texto legal del formulario (10 px) de 40% a 60% de opacidad.
- Validado con script propio en 13 páginas: JSON-LD parsea, un H1 por página, sin saltos de encabezado, canonicals = URLs del sitemap (11 de 11), sin recursos ni enlaces rotos, enlaces de carpeta con barra final, target=_blank con noopener, scripts con defer (salvo Tailwind a propósito).

**Recomendado, no aplicado**
- GSAP 3.13+ incluye SplitText gratis: si se quiere la animación de líneas del H1, subir a gsap@3.13.0 y volver a añadir SplitText (cambia lo visible).
- Fallback tipográfico con size-adjust/ascent-override para Inter e IBM Plex Sans (reduce el salto al cambiar la fuente); requiere medir con Lighthouse.
- INP: scroll-progress lee scrollHeight en cada scroll y Lenis recibe raf duplicado (propio + ticker de GSAP); ambos se pueden agrupar con requestAnimationFrame. Sin cambios por prudencia.
- Quitar del despliegue los originales pesados que ya no se referencian (video1.mp4, Background loop del Hero.mp4 y los JPEG Card A/B/C) para acelerar cada deploy; se dejaron las líneas cp existentes.
- og:image propia por servicio y avif (no hay encoder avif disponible aquí).
- Agrupar CSS+JS de GSAP en un solo bundle propio (ahorra 3 peticiones a CDN) y evaluar self-host de fuentes.
- Mover el cache-busting ?v a un paso de build o a un archivo de include cuando haya más de unas pocas páginas.

**Qué verificar en el navegador tras el deploy**
- PageSpeed Insights (móvil) para: https://supplylogistic360.com/ , https://supplylogistic360.com/servicios/inbound-logistics/ , https://supplylogistic360.com/libro-de-reclamaciones.php
- Home: el fondo del hero muestra el poster al instante y el video arranca solo después; con "Ahorro de datos" o "Reducir movimiento" se queda en la imagen fija. Las fotos de las secciones se ven nítidas y recortadas igual que antes. Sin saltos de diseño al cargar fuentes.
- Pulsar Tab en la primera pantalla: aparece "Saltar al contenido principal" y lleva al contenido; foco visible en enlaces y botones.
- Libro de reclamaciones: enviar un reclamo de prueba (hCaptcha, sesión, correo y PDF siguen igual); comprobar etiquetas y modal del aviso.
- Cabeceras (curl -I): CSS/JS con Cache-Control public, max-age=31536000, immutable; HTML con max-age=0, must-revalidate; X-Frame-Options SAMEORIGIN en estáticos; libro con X-Frame-Options DENY (el de PHP). Comprobar que rrhh. no cambió.
- Los archivos nuevos (assets/img, assets/og, videos, webp de logos y sellos, .htaccess.perf) deben estar versionados en git para que .cpanel.yml no falle al copiar; .htaccess.perf se añade una sola vez al .htaccess del servidor (si luego se edita, aplicar a mano).
- Compartir una URL en WhatsApp/LinkedIn y revisar la vista previa con la nueva og:image.
- Search Console: Core Web Vitals y cobertura 2 a 4 semanas después.

## Rediseño del hero y de Servicios (home, 2026-11-19)
- Hero en tarjeta redondeada con navbar "pill" flotante (CSS compartido: se aplica a todas las páginas con el mismo HTML). Se mantiene un único H1 con "operador logístico en Perú" y "cadena de suministro", `id="hero-title"`, poster con preload y video diferido (sin cambios de comportamiento), sello ISO 28000, teléfono y WhatsApp.
- Nueva sección "Quiénes somos" bajo el hero (texto tomado de /servicios/). `#servicios` pasa a 7 tarjetas con foto, cada una enlazada a su página /servicios/<slug>/ (antes 3 tarjetas con 12 enlaces internos); se reutilizan las imágenes existentes (sin descargas nuevas, `loading="lazy"`, srcset webp).
- JSON-LD, meta y canonical sin cambios. Cache-busting `?v=20261119` en CSS/JS de todas las páginas.
- Verificar tras publicar: contraste de botones (se usó naranja `#C8431A` para texto pequeño blanco), LCP/CLS en PageSpeed y que los enlaces internos a servicios sigan rastreándose.

## Qué medir y cuándo
- Cada 2 a 4 semanas al inicio: páginas indexadas, impresiones, clics, consultas y posición media (Search Console); Core Web Vitals.
- Una vez instalado GA4: clics en WhatsApp, correo y teléfono, y reclamos enviados por página de origen.
- Al mes: ficha de Google (vistas, llamadas, clics a la web).
- Revisar este informe cada trimestre o tras cambios grandes. No se prometen posiciones ni plazos de indexación.

## Imagen del hero de servicios y reorganización de assets (2026-11-26)
- Las 7 páginas de servicio muestran una foto compartida del puerto nocturno en el hero (solo escritorio >= 900 px; en móvil el `<picture>` resuelve a un GIF de 1 px embebido, por lo que no se descargan bytes). Variantes webp 480/800/1200/1448 en `assets/img/servicios/puerto-lluvia-*.webp`, con preload condicionado a `min-width:900px`. CSS `?v=20261126`.
- Nueva estructura: `assets/img/hero/`, `assets/img/servicios/` (tarjetas y puerto-lluvia), `assets/img/general/`, `assets/video/` y `assets/originals/` (fuentes pesadas, NO se despliega). `.cpanel.yml` crea y copia las carpetas nuevas; las reglas de caché de `.htaccess.perf` son por tipo MIME/extensión, no dependen de rutas.

## Cleanup and full-bleed service hero (2026-11)

- The service-page hero now uses the rainy-port photo as a full-bleed background (`assets/img/servicios/hero-puerto-lluvia-{800,1280,1672}.webp`, preloaded on every viewport). The earlier right-hand 4:3 card (`puerto-lluvia-*.webp`) was removed. CSS `?v=20261127`.
- Deleted unused files: the Card A/B/C, "Por que Supply360" JPEGs and `video1.mp4` / `Background loop del Hero.mp4` originals, `assets/video/hero-loop.mp4`, `assets/img/hero/hero-poster.webp`, the old logo JPEG/white PNG, the ISO certificate source PNG and the root favicon JPG. `assets/originals/` now only keeps the two port PNG sources.
- Home use case 02 (Retail) now plays `assets/video/caso-02-retail.mp4` (poster `assets/img/general/caso-02-poster.webp`); `video1-web.mp4` and `caso-poster.webp` were deleted.
