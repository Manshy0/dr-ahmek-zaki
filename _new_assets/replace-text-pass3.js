// Pass 3: URL-encoded variants, apostrophe variants, JSON-LD names,
// external profile links, and other edge cases.
const fs = require("fs")
const path = require("path")

const ROOT = path.resolve(__dirname, "..")

const REPLACEMENTS = [
  // ---- URL-encoded longer variants (longest first) ----
  ["Dr.%20Usama%20Saleh", "Dr.%20Ahmed%20Zaki"],
  ["Dr%20Usama%20Saleh", "Dr%20Ahmed%20Zaki"],
  ["Dr.%20Osama%20Saleh", "Dr.%20Ahmed%20Zaki"],
  ["Updates%20From%20Dr.%20Usama%20Saleh", "Updates%20From%20Dr.%20Ahmed%20Zaki"],
  ["Updates%20From%20Dr.%20Usama", "Updates%20From%20Dr.%20Ahmed%20Zaki"],
  ["Dr.%20Usama%20Saleh%27s", "Dr.%20Ahmed%20Zaki%27s"],
  ["Dr.%20Usama%20Saleh%E2%80%99s", "Dr.%20Ahmed%20Zaki%E2%80%99s"],
  ["Dr.%20Usama%E2%80%99s", "Dr.%20Ahmed%20Zaki%E2%80%99s"],
  ["Dr.%20Usama%27s", "Dr.%20Ahmed%20Zaki%27s"],
  ["Dr.%20Usama", "Dr.%20Ahmed%20Zaki"],
  ["Dr%20Usama", "Dr%20Ahmed%20Zaki"],
  ["%F0%9F%93%B0%20Dr.%20Usama%20Saleh", "%F0%9F%93%B0%20Dr.%20Ahmed%20Zaki"],
  ["Updates%20From%20Dr.%20Ahmed%20Zaki%20Saleh", "Updates%20From%20Dr.%20Ahmed%20Zaki"], // self-fix if previous run created
  ["Dr.%20Ahmed%20Zaki%20Saleh", "Dr.%20Ahmed%20Zaki"], // self-fix
  ["Dr%20Ahmed%20Zaki%20Saleh", "Dr%20Ahmed%20Zaki"], // self-fix

  // ---- URL-encoded folder paths ----
  ["%2Fmeet-dr-usama%2F", "%2Fmeet-dr-ahmed-zaki%2F"],
  ["meet-dr-usama%2F", "meet-dr-ahmed-zaki%2F"],
  ["%2Fmeet-dr-usama", "%2Fmeet-dr-ahmed-zaki"],

  // ---- Apostrophe variants (curly, entity, ASCII) ----
  ["Dr. Usama’s", "Dr. Ahmed Zaki’s"],
  ["Dr Usama’s", "Dr Ahmed Zaki’s"],
  ["Dr. Usama&#8217;s", "Dr. Ahmed Zaki&#8217;s"],
  ["Dr. Usama&rsquo;s", "Dr. Ahmed Zaki&rsquo;s"],
  ["Dr. Usama's", "Dr. Ahmed Zaki's"],
  ["Dr Usama's", "Dr Ahmed Zaki's"],
  ["Usama’s", "Ahmed Zaki’s"],
  ["Usama&#8217;s", "Ahmed Zaki&#8217;s"],
  ["Usama's", "Ahmed Zaki's"],

  // ---- JSON-LD givenName/familyName ----
  ['"givenName":"Usama","familyName":"Saleh"', '"givenName":"Ahmed","familyName":"Zaki"'],
  ['"givenName":"Osama","familyName":"Saleh"', '"givenName":"Ahmed","familyName":"Zaki"'],
  ['"givenName":"Usama"', '"givenName":"Ahmed"'],
  ['"givenName":"Osama"', '"givenName":"Ahmed"'],
  ['"familyName":"Saleh"', '"familyName":"Zaki"'],
  ['"caption":"usamasaleh"', '"caption":"drahmedzaki"'],

  // ---- External professional profile links (old doctor's profiles) ----
  // Replace with the new doctor's website since we don't have his Doctify/Medcare URLs.
  ["doctify.com/en-ae/specialist/usama-hassan-saleh", "drahmedzaki.ae"],
  ["medcare.ae/en/physician/view/usama-hassan-saleh.html", "drahmedzaki.ae"],
  ["medcare.ae/en/physician/view/usama-hassan-saleh", "drahmedzaki.ae"],
  ["usama-hassan-saleh", "ahmed-zaki"],

  // Google maps query
  ["Dr%20Usama%20Saleh%20dubai", "Dr%20Ahmed%20Zaki%20dubai"],
  ["Dr.%20Usama%20Saleh%20dubai", "Dr.%20Ahmed%20Zaki%20dubai"],

  // Specific phrase
  ["Dr. Usama’s website", "Dr. Ahmed Zaki’s website"],
  ["Dr. Usama's website", "Dr. Ahmed Zaki's website"],

  // SVG/title attribute
  ["Meet Dr. Usama", "Meet Dr. Ahmed Zaki"],

  // Catch standalone "Usama Saleh" or "Osama Saleh" left in JSON arrays
  ["Usama Saleh", "Ahmed Zaki"],
  ["Osama Saleh", "Ahmed Zaki"],
  ["Usama%20Saleh", "Ahmed%20Zaki"],
  ["Osama%20Saleh", "Ahmed%20Zaki"],
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

console.log("\n=== Pass 3 replacement counts ===")
const sorted = Object.entries(replaceCounts).sort((a, b) => b[1] - a[1])
for (const [k, v] of sorted) console.log(`  ${v.toString().padStart(5)}  "${k}"`)
console.log(`\nFiles changed in pass 3: ${filesChanged}`)
