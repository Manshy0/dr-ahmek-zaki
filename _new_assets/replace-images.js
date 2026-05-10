// Replace all photographs of the old doctor with the new Dr. Ahmed Zaki portrait
// while keeping the same filenames so the existing HTML works without changes.

const sharp = require("sharp")
const fs = require("fs")
const path = require("path")

const ROOT = path.resolve(__dirname, "..")
const PORTRAIT = path.join(__dirname, "dr-ahmed-portrait-3.jpg") // primary portrait
const PORTRAIT_2 = path.join(__dirname, "dr-ahmed-portrait-2.jpg") // alt portrait
const LOGO = path.join(__dirname, "dr-ahmed-logo-clean.jpg") // generated logo
const HERO_BANNER = path.join(__dirname, "dr-ahmed-hero-banner.jpg") // hero banner
const CLINIC = path.join(__dirname, "dr-ahmed-clinic.jpg") // clinic photo

// Helper: parse the size suffix like "-684x1024" out of a filename and resize accordingly
function parseSize(filename) {
  const m = filename.match(/-(\d+)x(\d+)\.(jpe?g|png|webp)$/i)
  if (m) return { w: parseInt(m[1], 10), h: parseInt(m[2], 10) }
  return null
}

async function makeImage(srcImg, outPath, w, h, format) {
  const dir = path.dirname(outPath)
  fs.mkdirSync(dir, { recursive: true })
  let pipe = sharp(srcImg).resize(w, h, {
    fit: "cover",
    position: "top", // keep faces in frame for portraits
  })
  if (format === "webp") {
    pipe = pipe.webp({ quality: 82 })
  } else if (format === "png") {
    pipe = pipe.png({ quality: 90 })
  } else {
    pipe = pipe.jpeg({ quality: 86, mozjpeg: true })
  }
  await pipe.toFile(outPath)
  console.log(`  -> ${path.relative(ROOT, outPath)}  (${w}x${h})`)
}

async function makeFavicon(srcImg, outPath, size) {
  const dir = path.dirname(outPath)
  fs.mkdirSync(dir, { recursive: true })
  await sharp(srcImg).resize(size, size, { fit: "cover", position: "centre" }).png({ quality: 95 }).toFile(outPath)
  console.log(`  -> ${path.relative(ROOT, outPath)}  (${size}x${size})`)
}

// All the existing doctor photo files in the project (these were photos of Dr. Usama).
// Keys: source image to use   ->   list of [output path, w, h, format]
async function run() {
  console.log("\n=== 1. Doctor portrait images (real photos) ===")

  // The hero/main portrait used in WordPress: Copilot_20250810_102441-Photoroom (multiple sizes)
  const portraitTargets = [
    "wp-content/uploads/2026/05/Copilot_20250810_102441-Photoroom-200x300.webp",
    "wp-content/uploads/2026/05/Copilot_20250810_102441-Photoroom-682x1024.webp",
    "wp-content/uploads/2026/05/Copilot_20250810_102441-Photoroom-768x1152.webp",
    "wp-content/uploads/2026/05/Copilot_20250810_102441-Photoroom.webp",
  ]
  for (const rel of portraitTargets) {
    const full = path.join(ROOT, rel)
    const sz = parseSize(rel)
    const w = sz ? sz.w : 1024
    const h = sz ? sz.h : 1536
    await makeImage(PORTRAIT, full, w, h, "webp")
  }

  // OG image referenced from absolute URL (https://usamasaleh.com/wp-content/uploads/2025/04/DSC02763-684x1024.jpg)
  // Create a local copy so when domain is changed the image works
  await makeImage(PORTRAIT, path.join(ROOT, "wp-content/uploads/2025/04/DSC02763-684x1024.jpg"), 684, 1024, "jpg")
  await makeImage(PORTRAIT, path.join(ROOT, "wp-content/uploads/2025/04/DSC02763.jpg"), 1200, 1800, "jpg")

  console.log("\n=== 2. Elementor thumbnail portraits ===")
  const elementorPortraits = [
    "wp-content/uploads/elementor/thumbs/DSC-rkutaktqely1wjyzikipyf1lgk46q5wvgw69b0koyk.webp",
    "wp-content/uploads/elementor/thumbs/DSC08315-scaled-rkutann8z41wvduw23qlnwbz8pqad982ha4pqugifw.webp",
    "wp-content/uploads/elementor/thumbs/DSC08340-scaled-rkutalrklfzc85xmd2xciwt21xzjxv0lt0tqsajasc.webp",
    "wp-content/uploads/elementor/thumbs/DSC08374-scaled-rkutann8z41wvduw23qlnwbz8pqad982ha4pqugifw.webp",
  ]
  for (const rel of elementorPortraits) {
    const full = path.join(ROOT, rel)
    if (!fs.existsSync(full)) continue
    const meta = await sharp(full).metadata()
    await makeImage(PORTRAIT, full, meta.width, meta.height, "webp")
  }

  console.log("\n=== 3. Article author photos (Dr-Usama-Saleh-orthopedic-surgeon-*) ===")
  const articlePortraits = [
    "wp-content/uploads/2026/05/Dr-Usama-Saleh-orthopedic-surgeon-1024x576.webp",
    "wp-content/uploads/2026/05/Dr-Usama-Saleh-orthopedic-surgeon-1536x865.webp",
    "wp-content/uploads/2026/05/Dr-Usama-Saleh-orthopedic-surgeon-2048x1153.webp",
    "wp-content/uploads/2026/05/Dr-Usama-Saleh-orthopedic-surgeon-300x169.webp",
    "wp-content/uploads/2026/05/Dr-Usama-Saleh-orthopedic-surgeon-768x432.webp",
  ]
  for (const rel of articlePortraits) {
    const full = path.join(ROOT, rel)
    if (!fs.existsSync(full)) continue
    const sz = parseSize(rel)
    // For wider article banners use the banner image not portrait
    await makeImage(HERO_BANNER, full, sz.w, sz.h, "webp")
  }

  // Also create a "Dr-Ahmed-Zaki-orthopedic-surgeon-*" set (same physical files renamed)
  // We will keep the old filenames to avoid HTML changes — but also write copies under
  // the new name so future references work.
  const newArticlePortraits = articlePortraits.map((r) => r.replace("Dr-Usama-Saleh", "Dr-Ahmed-Zaki"))
  for (const rel of newArticlePortraits) {
    const full = path.join(ROOT, rel)
    const sz = parseSize(rel)
    await makeImage(HERO_BANNER, full, sz.w, sz.h, "webp")
  }

  console.log("\n=== 4. Favicon files ===")
  const faviconSizes = [16, 32, 64, 96, 150, 180, 192, 270, 300, 512]
  for (const s of faviconSizes) {
    await makeFavicon(LOGO, path.join(ROOT, `wp-content/uploads/2025/06/cropped-favicon-${s}x${s}.png`), s)
  }
  // The "main" favicon
  await makeFavicon(LOGO, path.join(ROOT, "wp-content/uploads/2025/06/cropped-favicon.png"), 512)
  await makeFavicon(LOGO, path.join(ROOT, "favicon.ico"), 32) // legacy root
  await makeFavicon(LOGO, path.join(ROOT, "favicon.png"), 192)

  console.log("\nDone.\n")
}

run().catch((e) => {
  console.error(e)
  process.exit(1)
})
