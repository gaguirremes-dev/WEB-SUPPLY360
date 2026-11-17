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
| F6 | Core Web Vitals | ⚠️ | Video de fondo, GSAP/Lenis desde CDN, Tailwind CDN en el libro | Medir en PageSpeed Insights con datos de campo | P2 |
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

## Qué medir y cuándo
- Cada 2 a 4 semanas al inicio: páginas indexadas, impresiones, clics, consultas y posición media (Search Console); Core Web Vitals.
- Una vez instalado GA4: clics en WhatsApp, correo y teléfono, y reclamos enviados por página de origen.
- Al mes: ficha de Google (vistas, llamadas, clics a la web).
- Revisar este informe cada trimestre o tras cambios grandes. No se prometen posiciones ni plazos de indexación.
