# AI Shorts Mastery

Embudo completo (landing → ventas → checkout) para el curso **AI Shorts Mastery** (97/147 €).
Páginas estáticas HTML, sin dependencias. Se sirven tal cual desde cualquier hosting estático.

## Entrada
- `index.html` — hub con enlaces a todas las páginas.

## Funnel
- `clase-gratis.html` — landing de la **clase gratis** (pega aquí el embed de tu vídeo).
- `ai-shorts-mastery-clase.html` — **presentación (deck)** de la clase para grabar el vídeo. `←/→` mover, `F` pantalla completa, `N` notas de orador.
- `ai-shorts-mastery-ventas.html` — **página de ventas** con el stack de valor (826 € → 147 €) y botones a Stripe.
- `ai-shorts-mastery-logo.html` — kit de **logos** (descarga en PNG).

## Paneles de YouTube Studio (prueba)
- `yt-studio-monetizacion.html` — canal **propio (Duck)**: datos reales.
- `yt-studio-elcalacas.html`, `yt-studio-superviral.html` — **simulaciones** de canales públicos (úsalas solo como ejemplo/ilustración).

> El deck y la página de ventas **incrustan** estos paneles vía `iframe`, así que deben servirse desde la misma carpeta.

## Notas
- Checkout: enlace de Stripe ya integrado en los botones.
- Las páginas cargan avatares y miniaturas desde YouTube → requieren conexión.
