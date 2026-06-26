# AI Shorts Mastery

Embudo completo (landing → ventas → checkout) para el curso **AI Shorts Mastery** (97/147 €).
Páginas estáticas HTML, sin dependencias. Se sirven tal cual desde cualquier hosting estático.

## Funnel
- `index.html` — **entrada = clase gratis** (la raíz del dominio). Pega aquí el embed de tu vídeo. CTA → página de ventas.
- `ai-shorts-mastery-clase.html` — **presentación (deck)** de la clase para grabar el vídeo (uso interno, no enlazada). `←/→` mover, `F` pantalla completa, `N` notas de orador.
- `ai-shorts-mastery-ventas.html` — **página de ventas** con el stack de valor (826 € → 147 €) y botones a Stripe.
- `ai-shorts-mastery-logo.html` — kit de **logos** (descarga en PNG).

## Prueba
- La **página de ventas** usa **canales reales con sus vídeos públicos embebidos** (visitas verificables en YouTube). No se inventa ni se muestra ningún panel de YouTube Studio.
- `yt-studio-monetizacion.html` — panel del canal **propio (Duck)**, datos reales. Solo lo usa el **deck** (uso interno), no la web pública.

## Notas
- Checkout: enlace de Stripe ya integrado en los botones.
- Las páginas cargan avatares y miniaturas desde YouTube → requieren conexión.
