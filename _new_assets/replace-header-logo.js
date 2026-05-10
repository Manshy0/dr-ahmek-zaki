const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const SRC = '_new_assets/ref-site/logo-h-full.png';
const VARIANTS = [
  { p: 'wp-content/uploads/2025/05/P-1-300x59.png',   w: 300,  h: 59 },
  { p: 'wp-content/uploads/2025/05/P-1-768x151.png',  w: 768,  h: 151 },
  { p: 'wp-content/uploads/2025/05/P-1-1024x201.png', w: 1024, h: 201 },
  { p: 'wp-content/uploads/2025/05/P-1-1536x302.png', w: 1536, h: 302 },
  { p: 'wp-content/uploads/2025/05/P-1-2048x403.png', w: 2048, h: 403 },
];

(async () => {
  for (const v of VARIANTS) {
    if (!fs.existsSync(v.p)) { console.log('skip', v.p); continue; }
    // Fit logo with transparent background (PNG supports it)
    await sharp(SRC)
      .resize(v.w, v.h, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 0 } })
      .png({ quality: 90, compressionLevel: 9 })
      .toFile(v.p + '.tmp');
    fs.renameSync(v.p + '.tmp', v.p);
    console.log('replaced:', v.p, `${v.w}x${v.h}`);
  }

  // Also create an unsuffixed P-1.png for completeness
  const main = 'wp-content/uploads/2025/05/P-1.png';
  await sharp(SRC).png({ quality: 90, compressionLevel: 9 }).toFile(main + '.tmp');
  fs.renameSync(main + '.tmp', main);
  console.log('replaced (main):', main);

  console.log('Done.');
})();
