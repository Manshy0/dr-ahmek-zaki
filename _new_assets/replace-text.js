// Comprehensive text replacement: replace every reference to the old doctor
// with Dr. Ahmed Zaki across all relevant text files.
//
// Replacements are applied in order. Earlier (longer / more specific) patterns
// run first so they don't get partially matched by later, broader ones.

const fs = require("fs")
const path = require("path")

const ROOT = path.resolve(__dirname, "..")

// [oldString, newString]   (literal substring replacement, case-sensitive)
const REPLACEMENTS = [
  // ---------- 1. Name variations (longest / most specific first) ----------
  ["Dr. Usama Saleh", "Dr. Ahmed Zaki"],
  ["Dr. Usama saleh", "Dr. Ahmed Zaki"],
  ["Dr Usama Saleh", "Dr Ahmed Zaki"],
  ["Dr. Osama Saleh", "Dr. Ahmed Zaki"],
  ["Dr. usama saleh", "Dr. Ahmed Zaki"],
  ["dr. Usama saleh", "Dr. Ahmed Zaki"],
  ["Usama H. Saleh", "Ahmed Zaki"],
  ["Usama Saleh", "Ahmed Zaki"],
  ["Osama Saleh", "Ahmed Zaki"],
  ["dr.usamasaleh", "drahmedzaki"],
  ["DrUsamaSaleh", "DrAhmedZaki"],
  ["dr_usama.saleh", "drahmedzaki"],
  ["dr_usamasaleh", "drahmedzaki"],
  ["usama-h-saleh-3b958941", "dr-ahmed-zaki"],
  ["Dr-Usama-Saleh", "Dr-Ahmed-Zaki"],
  ["dr-usama-saleh", "dr-ahmed-zaki"],

  // Cyrillic / Arabic if any
  ["أسامة صالح", "أحمد زكي"],
  ["د. أسامة صالح", "د. أحمد زكي"],
  ["د.أسامة صالح", "د. أحمد زكي"],
  ["د/ أسامة صالح", "د/ أحمد زكي"],
  ["دكتور أسامة صالح", "دكتور أحمد زكي"],

  // ---------- 2. Contact info ----------
  ["+971567853864", "+971585670984"],
  ["+971-567853864", "+971585670984"],
  ["971567853864", "971585670984"],
  ["00971567853864", "00971585670984"],
  ["dr@usamasaleh.com", "info@drahmedzaki.ae"],

  // ---------- 3. Domain ----------
  ["https://usamasaleh.com", "https://drahmedzaki.ae"],
  ["http://usamasaleh.com", "https://drahmedzaki.ae"],
  ["//usamasaleh.com", "//drahmedzaki.ae"],
  ["www.usamasaleh.com", "www.drahmedzaki.ae"],
  ["usamasaleh.com", "drahmedzaki.ae"],

  // ---------- 4. Folder paths in URLs ----------
  ["/meet-dr-usama/", "/meet-dr-ahmed-zaki/"],
  ["'meet-dr-usama/", "'meet-dr-ahmed-zaki/"],
  ['"meet-dr-usama/', '"meet-dr-ahmed-zaki/'],
  ["=meet-dr-usama/", "=meet-dr-ahmed-zaki/"],
  ["(meet-dr-usama/", "(meet-dr-ahmed-zaki/"],
  ["meet-dr-usama/index.htm", "meet-dr-ahmed-zaki/index.htm"],
  ["/exercises-to-strengthen-your-shoulder-after-surgery-a-complete-recovery-guide-by-dr-usama-saleh/", "/exercises-to-strengthen-your-shoulder-after-surgery/"],
  ["exercises-to-strengthen-your-shoulder-after-surgery-a-complete-recovery-guide-by-dr-usama-saleh", "exercises-to-strengthen-your-shoulder-after-surgery"],

  // ---------- 5. Site / SEO copy that uses old phrasing ----------
  ["Fellowship Trained Orthopedic Surgeon UAE", "Specialist Orthopedic Surgeon UAE"],
  ["Fellowship-Trained Orthopedic Surgeon UAE", "Specialist Orthopedic Surgeon UAE"],
  ["fellowship-trained orthopedic surgeon", "specialist orthopedic surgeon"],
  ["Fellowship trained", "Specialist"],

  // ---------- 6. Social handles ----------
  ["facebook.com/DrUsamaSaleh/", "facebook.com/drahmedzaki/"],
  ["facebook.com/DrUsamaSaleh", "facebook.com/drahmedzaki"],
  ["instagram.com/dr_usama.saleh/", "instagram.com/drahmedzaki/"],
  ["instagram.com/dr_usama.saleh", "instagram.com/drahmedzaki"],
  ["twitter.com/dr_usamasaleh", "twitter.com/drahmedzaki"],
  ["x.com/dr_usamasaleh", "x.com/drahmedzaki"],
  ["linkedin.com/in/usama-h-saleh-3b958941", "linkedin.com/in/dr-ahmed-zaki"],

  // ---------- 7. Address/clinic generic phrases ----------
  // The original site doesn't use a single fixed address string; specific
  // address lines are mostly inside HTML and replaced in a separate pass
  // (see replace-content.js). We only normalize obvious clinic refs here.
]

const TEXT_EXTS = new Set([
  ".htm",
  ".html",
  ".css",
  ".js",
  ".php",
  ".txt",
  ".json",
  ".xml",
  ".rss",
  ".atom",
  ".webmanifest",
  ".svg",
])

const SKIP_DIRS = new Set([
  "node_modules",
  ".git",
  "_new_assets",
  "wp-content/plugins/elementor",
  "wp-content/plugins/jeg-elementor-kit",
  "wp-content/plugins/skyboot-custom-icons-for-elementor",
  "wp-content/plugins/elementskit-lite",
  "wp-includes",
  "wp-admin",
])

let filesChanged = 0
let filesScanned = 0
const replaceCounts = {}

function shouldSkip(rel) {
  // Skip plugin internals — they are framework code, not content
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

  filesScanned++
  let content
  try {
    content = fs.readFileSync(filePath, "utf8")
  } catch (e) {
    return
  }
  let changed = false
  let perFileCounts = 0

  for (const [oldStr, newStr] of REPLACEMENTS) {
    if (!content.includes(oldStr)) continue
    const before = content.length
    const occurrences = content.split(oldStr).length - 1
    content = content.split(oldStr).join(newStr)
    perFileCounts += occurrences
    replaceCounts[oldStr] = (replaceCounts[oldStr] || 0) + occurrences
    changed = true
    void before
  }

  if (changed) {
    fs.writeFileSync(filePath, content, "utf8")
    filesChanged++
    if (perFileCounts > 5) {
      console.log(`  ${rel}  (${perFileCounts} replacements)`)
    }
  }
}

function walk(dir) {
  let entries
  try {
    entries = fs.readdirSync(dir, { withFileTypes: true })
  } catch {
    return
  }
  for (const ent of entries) {
    const full = path.join(dir, ent.name)
    const rel = path.relative(ROOT, full)
    if (shouldSkip(rel)) continue
    if (ent.isDirectory()) walk(full)
    else if (ent.isFile()) processFile(full)
  }
}

console.log(`Scanning ${ROOT}...\n`)
walk(ROOT)

console.log(`\n=== Per-pattern replacement counts ===`)
const sorted = Object.entries(replaceCounts).sort((a, b) => b[1] - a[1])
for (const [k, v] of sorted) console.log(`  ${v.toString().padStart(5)}  "${k}"`)

console.log(`\nFiles scanned: ${filesScanned}`)
console.log(`Files changed: ${filesChanged}`)
