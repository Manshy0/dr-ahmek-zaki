// Compress all images in wp-content/uploads to reduce site size for Hostinger
const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', 'wp-content', 'uploads');

let processed = 0, savedBytes = 0, errors = 0;

async function compress(file) {
  try {
    const ext = path.extname(file).toLowerCase();
    const stat = fs.statSync(file);
    if (stat.size < 30 * 1024) return; // skip tiny files
    const before = stat.size;

    let buf;
    if (ext === '.jpg' || ext === '.jpeg') {
      buf = await sharp(file).jpeg({ quality: 78, mozjpeg: true, progressive: true }).toBuffer();
    } else if (ext === '.png') {
      buf = await sharp(file).png({ quality: 80, compressionLevel: 9, palette: true }).toBuffer();
    } else if (ext === '.webp') {
      buf = await sharp(file).webp({ quality: 78 }).toBuffer();
    } else {
      return;
    }

    if (buf.length < before * 0.95) {
      fs.writeFileSync(file, buf);
      savedBytes += (before - buf.length);
    }
    processed++;
    if (processed % 200 === 0) {
      console.log(`Processed ${processed}, saved ${(savedBytes/1024/1024).toFixed(1)} MB`);
    }
  } catch (e) {
    errors++;
  }
}

async function walk(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const e of entries) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) await walk(p);
    else if (/\.(jpg|jpeg|png|webp)$/i.test(e.name)) await compress(p);
  }
}

(async () => {
  console.log('Compressing images in', ROOT);
  await walk(ROOT);
  console.log(`\nDone. Processed: ${processed}, Errors: ${errors}, Saved: ${(savedBytes/1024/1024).toFixed(1)} MB`);
})();
