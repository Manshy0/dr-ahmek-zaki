// Generates placeholder images for any image referenced in HTML/CSS/JS that doesn't exist on disk.
// For doctor-related images we use the new doctor portrait. For everything else, generic medical placeholder.
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const ROOT = path.resolve(__dirname, '..');
const NEW = path.join(ROOT, '_new_assets');

// Source images
const DOCTOR_SRC = fs.readFileSync(path.join(NEW, 'dr-ahmed-portrait-3.jpg'));
const HERO_SRC   = fs.readFileSync(path.join(NEW, 'dr-ahmed-hero-banner.jpg'));

const SKIP_DIRS = new Set(['node_modules', '.git', '_new_assets']);

// Walk all text files and collect image references
const refs = new Set();
function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (SKIP_DIRS.has(entry.name)) continue;
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) { walk(p); continue; }
    if (!/\.(htm|html|css|js|json|xml)$/i.test(entry.name)) continue;
    let data;
    try { data = fs.readFileSync(p, 'utf8'); } catch { continue; }
    const re = /wp-content\/uploads\/[^\s"'<>(){},]+?\.(?:jpg|jpeg|png|webp|svg|gif|ico)/gi;
    let m;
    while ((m = re.exec(data)) !== null) {
      refs.add(m[0]);
    }
  }
}
walk(ROOT);

console.log('Total unique referenced images:', refs.size);

// Identify which exist
const missing = [];
for (const r of refs) {
  const abs = path.join(ROOT, r);
  if (!fs.existsSync(abs)) missing.push(r);
}
console.log('Missing:', missing.length);

// Group by base (without size suffix and extension)
function isDoctorRelated(p) {
  return /DSC\d|Dr[._-]?(?:Usama|Ahmed|ahmed)|Copilot_2025|listen|portrait|surgeon|surgery|orthopedic/i.test(p);
}

function parseSize(filename) {
  // Match -WIDTHxHEIGHT before extension
  const m = filename.match(/-(\d+)x(\d+)\.[a-z]+(?:\.webp)?$/i);
  if (m) return { w: parseInt(m[1]), h: parseInt(m[2]) };
  // Match scaled
  if (/-scaled\./i.test(filename)) return { w: 1500, h: 1500 };
  return null;
}

function getOutFormat(filename) {
  // For SVG files we generate PNG content (rare); for .jpg.webp output webp
  if (/\.webp$/i.test(filename)) return 'webp';
  if (/\.png$/i.test(filename)) return 'png';
  if (/\.svg$/i.test(filename)) return 'svg';
  if (/\.gif$/i.test(filename)) return 'png';
  if (/\.ico$/i.test(filename)) return 'png';
  return 'jpeg';
}

let success = 0, failed = 0, skipped = 0;
async function process() {
  // Pre-cache resized variants to avoid re-processing for each size
  for (const ref of missing) {
    const abs = path.join(ROOT, ref);
    const dir = path.dirname(abs);
    const fn = path.basename(abs);
    fs.mkdirSync(dir, { recursive: true });

    const outFmt = getOutFormat(fn);
    if (outFmt === 'svg') {
      // Skip svg generation
      skipped++;
      continue;
    }

    const size = parseSize(fn);
    let w = size ? size.w : 1024;
    let h = size ? size.h : 768;
    // Cap large sizes
    if (w > 2000) w = 2000;
    if (h > 2000) h = 2000;

    // Source: doctor portrait if doctor-related, otherwise hero/clinic
    const src = isDoctorRelated(fn) ? DOCTOR_SRC : HERO_SRC;
    try {
      let pipeline = sharp(src).resize({
        width: w, height: h,
        fit: w > h * 1.3 || (h && w / h > 1.3) ? 'cover' : 'cover',
        position: 'top',
      });
      if (outFmt === 'jpeg') pipeline = pipeline.jpeg({ quality: 80 });
      else if (outFmt === 'png') pipeline = pipeline.png({ compressionLevel: 9 });
      else if (outFmt === 'webp') pipeline = pipeline.webp({ quality: 80 });

      const buf = await pipeline.toBuffer();
      fs.writeFileSync(abs, buf);
      success++;
    } catch (e) {
      failed++;
      if (failed < 5) console.log('  fail:', ref, e.message);
    }
  }
  console.log('Generated:', success, 'Failed:', failed, 'Skipped(svg):', skipped);
}
process();
