/**
 * Replace all doctor photos in wp-content/uploads with the REAL Dr. Ahmed Zaki photos
 * sourced from the official reference site.
 */
const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const REF = path.join(__dirname, 'ref-site');
const HERO_PORTRAIT = path.join(REF, 'doctor-hero.webp');      // Medcare photo (preferred for hero)
const SECONDARY = path.join(REF, 'doctor-secondary.webp');     // Portrait

// Validate sources
if (!fs.existsSync(HERO_PORTRAIT) || !fs.existsSync(SECONDARY)) {
  console.error('Source photos missing'); process.exit(1);
}

const UPLOADS = path.join(process.cwd(), 'wp-content', 'uploads');

// Heuristic: identify files that are doctor photos based on filename patterns we set previously
const PORTRAIT_PATTERNS = [
  /^DSC[-_0-9]/i,      // DSC0, DSC02763, DSC_303, DSC-Photoroom, DSC2, DSC3, etc.
  /^DSC\./i,           // DSC.webp
  /Dr-?Ahmed-?Zaki/i,
  /doctor[-_ ]?ahmed/i,
  /img_338[01]/i,
  /Copilot_/i,
];

// Recursively find image files
function walk(dir, out=[]) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p, out);
    else if (/\.(jpg|jpeg|png|webp)$/i.test(e.name)) out.push(p);
  }
  return out;
}

async function replacePhoto(filePath, source) {
  try {
    // Read original metadata to preserve dimensions
    const meta = await sharp(filePath).metadata();
    const w = meta.width || 800;
    const h = meta.height || 1200;
    const ext = path.extname(filePath).toLowerCase();

    let pipeline = sharp(source).resize(w, h, {
      fit: 'cover',
      position: 'top',
    });

    if (ext === '.png') pipeline = pipeline.png({ quality: 88, compressionLevel: 9 });
    else if (ext === '.webp') pipeline = pipeline.webp({ quality: 82 });
    else pipeline = pipeline.jpeg({ quality: 85, progressive: true, mozjpeg: true });

    const buf = await pipeline.toBuffer();
    fs.writeFileSync(filePath, buf);
    return true;
  } catch (e) {
    console.error('  fail:', filePath, e.message);
    return false;
  }
}

(async () => {
  const allFiles = walk(UPLOADS);
  console.log(`Scanning ${allFiles.length} images in uploads...`);

  let portraitCount = 0;
  let processed = 0;

  for (const file of allFiles) {
    const base = path.basename(file);
    const isPortrait = PORTRAIT_PATTERNS.some(re => re.test(base));
    if (!isPortrait) continue;

    // Use HERO photo for the canonical 2025/04 directory (homepage hero), SECONDARY for others
    const source = file.includes('/2025/04/') ? HERO_PORTRAIT : SECONDARY;
    if (await replacePhoto(file, source)) {
      portraitCount++;
      if (portraitCount % 5 === 0) console.log(`  replaced ${portraitCount} portrait files...`);
    }
    processed++;
  }

  console.log(`\nDone. Replaced ${portraitCount} portrait images.`);
})();
