const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..");
const SKIP_DIRS = new Set(["node_modules", ".git", "_new_assets", ".next"]);

const replacements = [
  // Schema author handle
  ['"name":"usamasaleh"', '"name":"drahmedzaki"'],
  ['"name": "usamasaleh"', '"name": "drahmedzaki"'],
  // Twitter data
  ['content="usamasaleh"', 'content="drahmedzaki"'],
  // Author URL slug
  ["author/usamasaleh", "author/drahmedzaki"],
  // Any remaining standalone usamasaleh
  [/\busamasaleh\b/g, "drahmedzaki"],
  // Image filename remnants in alt attributes
  [/Dr-Usama-Saleh-orthopedic-surgeon[a-zA-Z0-9.-]*\.webp/g, "dr-ahmed-zaki.webp"],
  [/Dr\.\s*[-_]\s*Usama[a-zA-Z0-9.\-_]*\.webp/gi, "dr-ahmed-zaki.webp"],
  // Any "DR USAMA / OSAMA" left in image filenames or headings
  [/DR-USAMA[A-Z0-9.\-_]*/g, "DR-AHMED-ZAKI"],
  [/dr-usama[a-z0-9.\-_]*/g, "dr-ahmed-zaki"],
  [/dr\.-usama-listen/gi, "dr-ahmed-zaki-listen"],
  // Alt-attribute fragments
  [/\bUsama Saleh\b/gi, "Ahmed Zaki"],
  [/\bOsama Saleh\b/gi, "Ahmed Zaki"],
  [/\bUsama\b(?!\s*Bin)/g, "Ahmed"],
  [/\bOsama\b/g, "Ahmed"],
  [/\bUSAMA\b/g, "AHMED"],
  [/\bOSAMA\b/g, "AHMED"],
];

const exts = new Set([".htm", ".html", ".css", ".js", ".php", ".txt", ".json", ".xml"]);
let fileCount = 0;
const stats = {};

function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (SKIP_DIRS.has(entry.name)) continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(full);
    else if (exts.has(path.extname(entry.name))) processFile(full);
  }
}

function processFile(file) {
  let content;
  try {
    content = fs.readFileSync(file, "utf8");
  } catch {
    return;
  }
  const orig = content;
  for (const [from, to] of replacements) {
    if (typeof from === "string") {
      if (content.includes(from)) {
        const c = content.split(from).length - 1;
        content = content.split(from).join(to);
        stats[String(from)] = (stats[String(from)] || 0) + c;
      }
    } else {
      const m = content.match(from);
      if (m) {
        stats[String(from)] = (stats[String(from)] || 0) + m.length;
        content = content.replace(from, to);
      }
    }
  }
  if (content !== orig) {
    fs.writeFileSync(file, content, "utf8");
    fileCount++;
  }
}

walk(ROOT);
console.log("\n=== Pass 5 stats ===");
Object.entries(stats)
  .sort((a, b) => b[1] - a[1])
  .forEach(([k, v]) => console.log(String(v).padStart(6), k));
console.log(`\nFiles changed: ${fileCount}`);
