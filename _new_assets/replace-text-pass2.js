// Second pass: handle remaining shorter / less obvious patterns.
const fs = require("fs")
const path = require("path")

const ROOT = path.resolve(__dirname, "..")

// IMPORTANT order: longest first. "Dr. Usama Hassan Saleh" must run BEFORE "Dr. Usama".
const REPLACEMENTS = [
  // Hassan Saleh full names (variations)
  ["Dr. Usama Hassan Saleh", "Dr. Ahmed Zaki"],
  ["Dr Usama Hassan Saleh", "Dr Ahmed Zaki"],
  ["Dr. Osama Hassan Saleh", "Dr. Ahmed Zaki"],
  ["Dr Osama Hassan Saleh", "Dr Ahmed Zaki"],
  ["Usama Hassan Saleh", "Ahmed Zaki"],
  ["Osama Hassan Saleh", "Ahmed Zaki"],
  ["Usama Hassan", "Ahmed Zaki"],
  ["Osama Hassan", "Ahmed Zaki"],

  // Short forms ("Dr. Usama" used in body copy)
  ["Meet Dr. Usama", "Meet Dr. Ahmed Zaki"],
  ["Meet Dr Usama", "Meet Dr Ahmed Zaki"],
  ["meet Dr. Usama", "meet Dr. Ahmed Zaki"],
  ["with Dr. Usama", "with Dr. Ahmed Zaki"],
  ["with Dr Usama", "with Dr Ahmed Zaki"],
  ["from Dr. Usama", "from Dr. Ahmed Zaki"],
  ["like Dr. Usama", "like Dr. Ahmed Zaki"],
  ["recommend Dr. Usama", "recommend Dr. Ahmed Zaki"],
  ["For Dr. Usama,", "For Dr. Ahmed Zaki,"],
  ["does Dr. Usama", "does Dr. Ahmed Zaki"],
  ["Dr. Usama,", "Dr. Ahmed Zaki,"],
  ["Dr Usama,", "Dr Ahmed Zaki,"],

  // Generic "Dr. Usama" catch-all (with trailing space, period or punctuation context)
  ["Dr. Usama ", "Dr. Ahmed Zaki "],
  ["Dr Usama ", "Dr Ahmed Zaki "],
  ["Dr. Osama ", "Dr. Ahmed Zaki "],
  ["Dr Osama ", "Dr Ahmed Zaki "],
  ["Dr. usama ", "Dr. Ahmed Zaki "],
  ["dr. Usama ", "Dr. Ahmed Zaki "],

  // EOL / end-of-sentence forms
  ["Dr. Usama.", "Dr. Ahmed Zaki."],
  ["Dr Usama.", "Dr Ahmed Zaki."],
  ["Dr. Usama!", "Dr. Ahmed Zaki!"],
  ["Dr. Usama?", "Dr. Ahmed Zaki?"],
  ["Dr. Usama:", "Dr. Ahmed Zaki:"],
  ["Dr. Usama;", "Dr. Ahmed Zaki;"],
  ["Dr. Usama)", "Dr. Ahmed Zaki)"],

  // HTML entity-flanked
  ["Dr. Usama<", "Dr. Ahmed Zaki<"],
  ["Dr Usama<", "Dr Ahmed Zaki<"],
  ['Dr. Usama"', 'Dr. Ahmed Zaki"'],
  ['Dr Usama"', 'Dr Ahmed Zaki"'],

  // Class/breadcrumb pages
  ["Meet Dr. Usama</", "Meet Dr. Ahmed Zaki</"],
  ["Meet Dr Usama</", "Meet Dr Ahmed Zaki</"],

  // Lone "Usama" inside JSON/breadcrumbs (only when very explicit context)
  ["\"Usama Saleh\"", "\"Ahmed Zaki\""],
  ["\"Dr Usama\"", "\"Dr Ahmed Zaki\""],
  ["\"Dr. Usama\"", "\"Dr. Ahmed Zaki\""],
  ["'Usama Saleh'", "'Ahmed Zaki'"],
  ["'Dr. Usama'", "'Dr. Ahmed Zaki'"],
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

console.log("\n=== Pass 2 replacement counts ===")
const sorted = Object.entries(replaceCounts).sort((a, b) => b[1] - a[1])
for (const [k, v] of sorted) console.log(`  ${v.toString().padStart(5)}  "${k}"`)
console.log(`\nFiles changed in pass 2: ${filesChanged}`)
