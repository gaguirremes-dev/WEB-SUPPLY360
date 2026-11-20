# assets/originals

Heavy source files kept in git for future re-exports. They are NOT deployed: `.cpanel.yml` never copies this folder, and nothing in the site references it.
Optimized derivatives live in `assets/img/{hero,servicios,general,legal}/` and `assets/video/`.

Remaining sources:

- `Puerto industrial nocturno bajo luces naranjas.png` - source of the home hero (`assets/img/hero/hero-puerto-*.webp`).
- `Puerto industrial bajo la lluvia.png` - source of the service-page hero background (`assets/img/servicios/hero-puerto-lluvia-*.webp`).
- `Oficina nocturna con luz cálida.png` - source of the legal-pages hero background (`assets/img/legal/oficina-nocturna-*.webp`).

The other originals (Card A/B/C and "Por que Supply360" JPEGs, hero/video1 MP4s) were deleted as unused; recover them from git history if needed.

Video sources (git-ignored via `assets/originals/*.mp4`, kept locally only):

- `quienes-somos-original.mp4` - 1080p source of `assets/video/quienes-somos.mp4` (home "Quiénes somos").
- `paso-04-inventario-original.mp4` - 1080p source of `assets/video/paso-04-inventario.mp4` (home flow, Paso 04).
- `caso-02-original.mp4` - 720p source (with audio) of `assets/video/caso-02-retail.mp4` (home use case 02, Retail).

Web encodes: H.264 main, 1280px wide, 24 fps, no audio (`-an`), faststart, CRF 28-30, 10 s loops.

- `operaciones-supply360-original.png`: source of `assets/img/servicios/operaciones-supply360-*.webp` (home, Paso 05).
