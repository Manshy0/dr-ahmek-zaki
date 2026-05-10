/**
 * Replace all favicon and logo files with the official Dr. Ahmed Zaki logo.
 */
const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const REF = path.join(__dirname, 'ref-site');
const SQUARE_LOGO = path.join(REF, 'logo-square.webp');   // for favicons (square crop)
const HORIZONTAL_LOGO = path.join(REF, 'logo-h-full.png'); // for header/horizontal usage

if (!fs.existsSync(SQUARE_LOGO) || !fs.existsSync(HORIZONTAL_LOGO)) {
  console.error('Source logos missing'); process.exit(1);
}

// All favicon files to update with their target sizes
const FAVICON_FILES = {
  'wp-content/uploads/2025/06/cropped-favicon-16x16.png':   16,
  'wp-content/uploads/2025/06/cropped-favicon-32x32.png':   32,
  'wp-content/uploads/2025/06/cropped-favicon-64x64.png':   64,
  'wp-content/uploads/2025/06/cropped-favicon-96x96.png':   96,
  'wp-content/uploads/2025/06/cropped-favicon-150x150.png': 150,
  'wp-content/uploads/2025/06/cropped-favicon-180x180.png': 180,
  'wp-content/uploads/2025/06/cropped-favicon-192x192.png': 192,
  'wp-content/uploads/2025/06/cropped-favicon-270x270.png': 270,
  'wp-content/uploads/2025/06/cropped-favicon-300x300.png': 300,
  'wp-content/uploads/2025/06/cropped-favicon-512x512.png': 512,
  'wp-content/uploads/2025/06/cropped-favicon.png':         512,
  'favicon.png': 512,
};

async function regenerateFavicons() {
  for (const [rel, size] of Object.entries(FAVICON_FILES)) {
    const out = path.join(process.cwd(), rel);
    if (!fs.existsSync(out)) {
      console.log('  skip (missing):', rel);
      continue;
    }
    try {
      const buf = await sharp(SQUARE_LOGO)
        .resize(size, size, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 1 } })
        .png({ compressionLevel: 9, quality: 90 })
        .toBuffer();
      fs.writeFileSync(out, buf);
      console.log(`  ${size}x${size} -> ${rel}`);
    } catch (e) {
      console.error('  fail:', rel, e.message);
    }
  }

  // favicon.ico (32x32 PNG renamed to .ico - browsers accept this)
  const ico = await sharp(SQUARE_LOGO)
    .resize(32, 32, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 1 } })
    .png({ compressionLevel: 9 })
    .toBuffer();
  fs.writeFileSync(path.join(process.cwd(), 'favicon.ico'), ico);
  console.log('  32x32 -> favicon.ico');
}

regenerateFavicons().then(() => console.log('\nFavicons regenerated.')).catch(console.error);
