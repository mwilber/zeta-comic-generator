# Site icons

All icons use the supplied Alpha Zeta close-up without redrawing it.

- `favicon.ico`: embedded 16, 32, and 48 pixel icons.
- `favicon-*.png`: 16, 32, 48, and 96 pixel browser/search icons.
- `favicon.svg`: self-contained SVG wrapper containing the original PNG; it is not vector artwork.
- `apple-touch-icon-*.png`: 120, 152, 167, and 180 pixel Apple home-screen icons.
- `android-chrome-*.png`: 192 and 512 pixel app icons.
- `maskable-icon-*.png`: 192 and 512 pixel icons with padding around Alpha's face for rounded/circular masks.
- `mstile-*.png` and `browserconfig.xml`: legacy Windows tiles.
- `site.webmanifest`: app identity, colors, launch URL, icon definitions, and Create/Gallery shortcuts.

The shared site head in `index.php` references these files. The root Apache rewrite maps `/favicon.ico` to this folder for clients that request the conventional URL. The folder's `.htaccess` supplies the manifest MIME type. `robots.txt` allows crawlers to fetch this folder despite the broader assets exclusion.

To rebuild after replacing `source.png` with another 512 × 512 PNG:

```sh
python3 assets/favicons/generate.py
```

The export script requires Pillow. The manifest provides app branding and launch metadata; it does not add offline support or a service worker.

Sizing and metadata references: [MDN app icons](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/How_to/Define_app_icons), [Apple web app configuration](https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html), and [Google Search favicon guidance](https://developers.google.com/search/docs/appearance/favicon-in-search).
