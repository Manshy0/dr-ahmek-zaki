// Pass 4: handle remaining edge cases — escaped slashes, JSON unicode escapes,
// span-split headlines, twitter handles, author slugs, image filename variants.
const fs = require("fs")
const path = require("path")

const ROOT = path.resolve(__dirname, "..")

const REPLACEMENTS = [
  // Image filenames with hyphen-period: Dr.-Usama-Saleh, Dr.-usama-listen, DR-USAMA-PATIENT-...
  ["Dr.-Usama-Saleh", "Dr.-Ahmed-Zaki"],
  ["Dr.-usama-listen", "Dr.-ahmed-listen"],
  ["DR-USAMA-PATIENT-TESTIMONY", "DR-AHMED-PATIENT-TESTIMONY"],
  ["dr-usama-saleh", "dr-ahmed-zaki"],
  ["DrUsamaSaleh", "DrAhmedZaki"],

  // JSON unicode escape for apostrophe (\u2019)
  ["Dr. Usama\\u2019s", "Dr. Ahmed Zaki\\u2019s"],
  ["Usama\\u2019s", "Ahmed Zaki\\u2019s"],
  ["Dr Usama\\u2019s", "Dr Ahmed Zaki\\u2019s"],
  ["Dr. Usama Saleh", "Dr. Ahmed Zaki"], // re-run for safety after \u replacements
  ["Dr.\\u00a0Usama\\u00a0Saleh", "Dr.\\u00a0Ahmed\\u00a0Zaki"],

  // Escaped path forms (\/) used inside JSON strings
  ["\\/Dr.-Usama-Saleh", "\\/Dr.-Ahmed-Zaki"],
  ["\\/Dr.-usama-listen", "\\/Dr.-ahmed-listen"],
  ["\\/DR-USAMA-PATIENT-TESTIMONY", "\\/DR-AHMED-PATIENT-TESTIMONY"],
  ["\\/2025\\/04\\/Dr.-Usama", "\\/2025\\/04\\/Dr.-Ahmed-Zaki"],

  // Author URL slug (in /author/ links)
  ["author/usamasaleh-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2-2", "author/drahmedzaki"],
  ["author/usamasaleh", "author/drahmedzaki"],
  ["/author/usamasaleh", "/author/drahmedzaki"],
  ["\"caption\":\"usamasaleh\"", "\"caption\":\"drahmedzaki\""],
  ["\"author\":\"usamasaleh\"", "\"author\":\"drahmedzaki\""],

  // Twitter meta data
  ["name=\"twitter:data1\" content=\"usamasaleh\"", "name=\"twitter:data1\" content=\"Dr. Ahmed Zaki\""],
  ["content=\"usamasaleh\"", "content=\"Dr. Ahmed Zaki\""],

  // Headline split into spans by qodef plugin: <span>Usama</span> <span>Saleh</span>
  // Replace ">Usama</span>" with ">Ahmed</span>" and ">Saleh</span>" with ">Zaki</span>"
  // Only when inside qodef word holders
  [">Usama</span>", ">Ahmed</span>"],
  [">USAMA</span>", ">AHMED</span>"],
  [">Saleh</span>", ">Zaki</span>"],
  [">SALEH</span>", ">ZAKI</span>"],
  [">usama</span>", ">ahmed</span>"],

  // Patient testimonial uses "dr Usama" — also "Dr. Usama" already covered, but lowercase variants:
  ["dr Usama and", "Dr Ahmed Zaki and"],
  ["dr Usama ", "Dr Ahmed Zaki "],
  ["dr usama ", "Dr Ahmed Zaki "],
  [" Usama and ", " Ahmed Zaki and "],

  // Final catch — short remaining "Dr. Usama" and "Dr Usama" (very last)
  ["Dr. Usama", "Dr. Ahmed Zaki"],
  ["Dr Usama", "Dr Ahmed Zaki"],
  ["Dr. Osama", "Dr. Ahmed Zaki"],
  ["Dr Osama", "Dr Ahmed Zaki"],

  // Fix possible double-replacement artifacts:
  ["Dr. Ahmed Zaki Zaki", "Dr. Ahmed Zaki"],
  ["Dr. Ahmed Zaki Saleh", "Dr. Ahmed Zaki"],
  ["Dr Ahmed Zaki Saleh", "Dr Ahmed Zaki"],
  ["Ahmed Zaki Saleh", "Ahmed Zaki"],
  ["Dr. Ahmed Zaki Hassan Saleh", "Dr. Ahmed Zaki"],
  ["Ahmed Zaki Hassan Saleh", "Ahmed Zaki"],
  ["Ahmed Zaki Hassan Zaki", "Ahmed Zaki"],
  ["Ahmed Zaki Hassan", "Ahmed Zaki"],
  ["Dr.%20Ahmed%20Zaki%20Saleh", "Dr.%20Ahmed%20Zaki"],
  ["Dr%20Ahmed%20Zaki%20Saleh", "Dr%20Ahmed%20Zaki"],
  ["Ahmed%20Zaki%20Saleh", "Ahmed%20Zaki"],
  ["Ahmed-Zaki-Saleh", "Ahmed-Zaki"],
  ["ahmed-zaki-saleh", "ahmed-zaki"],
]

const TEXT_EXTS = new Set([
  ".htm", ".html", ".css", ".js", ".php", ".txt", ".json", ".xml", ".rss", ".atom", ".webmanifest", ".svg",
])
const SKIP_DIRS = new Set([
  "node_modules", ".git", "_new_assets",
  "wp-content/plugins/elementor",
  "wp-content/plugins/jeg-elementor-kit",
  "wp-content/plugins/skyboot-custom-icons-for-elementor",
  "wp-content/plugins/elementskit-lite",
  "wp-includes", "wp-admin",
])

let filesChanged = 0
const replaceCounts = {}

function shouldSkip(rel) {
  for (const d of SKIP_DIRS) {
    if (rel === d || rel.startsWith(d + path.sep) || rel.startsWith(d + "/")) return true
  }
  return false
}
function processFile(filePath) {
  const ext = path.extname(filePath).toLowerCase()
  if (!TEXT_EXTS.has(ext)) return
  const rel = path.relative(ROOT, filePath)
  if (shouldSkip(rel)) return
  let content
  try { content = fs.readFileSync(filePath, "utf8") } catch { return }
  let changed = false
  for (const [oldStr, newStr] of REPLACEMENTS) {
    if (!content.includes(oldStr)) continue
    const occurrences = content.split(oldStr).length - 1
    content = content.split(oldStr).join(newStr)
    replaceCounts[oldStr] = (replaceCounts[oldStr] || 0) + occurrences
    changed = true
  }
  if (changed) {
    fs.writeFileSync(filePath, content, "utf8")
    filesChanged++
  }
}
function walk(dir) {
  let entries
  try { entries = fs.readdirSync(dir, { withFileTypes: true }) } catch { return }
  for (const ent of entries) {
    const full = path.join(dir, ent.name)
    const rel = path.relative(ROOT, full)
    if (shouldSkip(rel)) continue
    if (ent.isDirectory()) walk(full)
    else if (ent.isFile()) processFile(full)
  }
}
walk(ROOT)
console.log("\n=== Pass 4 replacement counts ===")
const sorted = Object.entries(replaceCounts).sort((a, b) => b[1] - a[1])
for (const [k, v] of sorted) console.log(`  ${v.toString().padStart(5)}  "${k}"`)
console.log(`\nFiles changed in pass 4: ${filesChanged}`)
