"""Export the site's favicon files from source.png. Requires Pillow."""

import base64
from pathlib import Path

from PIL import Image


DIRECTORY = Path(__file__).resolve().parent
BACKGROUND = "#17102e"


def export(image, filename, size):
    image.resize(size, Image.Resampling.LANCZOS).save(DIRECTORY / filename, optimize=True)


with Image.open(DIRECTORY / "source.png") as original:
    source = original.convert("RGB")
    if source.size != (512, 512):
        raise ValueError("source.png must be a 512 × 512 square image")

    source.save(DIRECTORY / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)])

    for size in (16, 32, 48, 96):
        export(source, f"favicon-{size}x{size}.png", (size, size))

    for size in (120, 152, 167, 180):
        export(source, f"apple-touch-icon-{size}x{size}.png", (size, size))

    for size in (192, 512):
        export(source, f"android-chrome-{size}x{size}.png", (size, size))

    # Keep Alpha's face within the central 80%-diameter maskable safe zone.
    maskable = Image.new("RGB", (512, 512), BACKGROUND)
    inset = source.resize((358, 358), Image.Resampling.LANCZOS)
    maskable.paste(inset, (77, 77))
    for size in (192, 512):
        export(maskable, f"maskable-icon-{size}x{size}.png", (size, size))

    for size in (150, 310):
        export(source, f"mstile-{size}x{size}.png", (size, size))

    wide_tile = Image.new("RGB", (310, 150), BACKGROUND)
    wide_tile.paste(source.resize((150, 150), Image.Resampling.LANCZOS), (80, 0))
    wide_tile.save(DIRECTORY / "mstile-310x150.png", optimize=True)

# Embed the supplied raster artwork so the SVG remains self-contained.
encoded = base64.b64encode((DIRECTORY / "source.png").read_bytes()).decode("ascii")
(DIRECTORY / "favicon.svg").write_text(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">\n'
    '  <image width="512" height="512" '
    f'href="data:image/png;base64,{encoded}"/>\n'
    '</svg>\n',
    encoding="utf-8",
)
