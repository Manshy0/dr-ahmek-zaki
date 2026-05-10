const sharp = require("sharp");
const fs = require("fs");
const path = require("path");

const SOURCE_LOGO = path.join(__dirname, "dr-ahmed-favicon-192.png");
const ROOT = path.resolve(__dirname, "..");

const sizes = [16, 32, 64, 96, 150, 180, 192, 270, 300, 512];

(async () => {
  const src = fs.readFileSync(SOURCE_LOGO);

  // Update WordPress favicon set in /wp-content/uploads/2025/06/
  const wpFaviconDir = path.join(ROOT, "wp-content/uploads/2025/06");

  for (const size of sizes) {
    const file = path.join(wpFaviconDir, `cropped-favicon-${size}x${size}.png`);
    if (fs.existsSync(file)) {
      await sharp(src)
        .resize(size, size, { fit: "contain", background: { r: 255, g: 255, b: 255, alpha: 0 } })
        .png()
        .toFile(file + ".tmp");
      fs.renameSync(file + ".tmp", file);
      console.log(`updated ${file}`);
    }
  }

  // Master cropped-favicon.png
  const master = path.join(wpFaviconDir, "cropped-favicon.png");
  if (fs.existsSync(master)) {
    await sharp(src).resize(512, 512, { fit: "contain" }).png().toFile(master + ".tmp");
    fs.renameSync(master + ".tmp", master);
    console.log(`updated ${master}`);
  }

  // Root favicon.ico (sharp doesn't write .ico, so use 32x32 PNG content as fallback)
  const rootIco = path.join(ROOT, "favicon.ico");
  if (fs.existsSync(rootIco)) {
    await sharp(src).resize(32, 32).png().toFile(rootIco + ".tmp");
    fs.renameSync(rootIco + ".tmp", rootIco);
    console.log(`updated ${rootIco}`);
  }

  // Root favicon.png
  const rootPng = path.join(ROOT, "favicon.png");
  if (fs.existsSync(rootPng)) {
    await sharp(src).resize(192, 192).png().toFile(rootPng + ".tmp");
    fs.renameSync(rootPng + ".tmp", rootPng);
    console.log(`updated ${rootPng}`);
  }

  console.log("\nFavicon update complete.");
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
