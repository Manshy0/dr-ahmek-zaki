const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..");
const SKIP_DIRS = new Set(["node_modules", ".git", "_new_assets", ".next"]);

// IMPORTANT: longest paths first
const linkReplacements = [
  // Long blog slug
  [
    "exercises-to-strengthen-your-shoulder-after-surgery-a-complete-recovery-guide-by-dr-usama-saleh",
    "exercises-to-strengthen-your-shoulder-after-surgery",
  ],
  // Personal news article URLs (now deleted) -> redirect to news index
  [
    "/news/📰-dr-usama-saleh-appointed-as-associate-clinical-professor-at-the-university-of-sharjah/",
    "/news/",
  ],
  [
    "news/📰-dr-usama-saleh-appointed-as-associate-clinical-professor-at-the-university-of-sharjah/",
    "news/",
  ],
  [
    "/news/dr-usama-saleh-highlights-his-core-philosophy/",
    "/news/",
  ],
  [
    "news/dr-usama-saleh-highlights-his-core-philosophy/",
    "news/",
  ],
  // Folder rename
  ["/meet-dr-usama/", "/meet-dr-ahmed-zaki/"],
  ["meet-dr-usama/", "meet-dr-ahmed-zaki/"],
  ['"meet-dr-usama"', '"meet-dr-ahmed-zaki"'],
  // URL-encoded variant of the news folder name (📰 = %F0%9F%93%B0)
  [
    "/news/%f0%9f%93%b0-dr-usama-saleh-appointed-as-associate-clinical-professor-at-the-university-of-sharjah/",
    "/news/",
  ],
  [
    "/news/%F0%9F%93%B0-dr-usama-saleh-appointed-as-associate-clinical-professor-at-the-university-of-sharjah/",
    "/news/",
  ],
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
  for (const [from, to] of linkReplacements) {
    if (content.includes(from)) {
      const c = content.split(from).length - 1;
      content = content.split(from).join(to);
      stats[from] = (stats[from] || 0) + c;
    }
  }
  if (content !== orig) {
    fs.writeFileSync(file, content, "utf8");
    fileCount++;
  }
}

walk(ROOT);
console.log("\n=== Link update stats ===");
Object.entries(stats)
  .sort((a, b) => b[1] - a[1])
  .forEach(([k, v]) => console.log(String(v).padStart(6), k.length > 80 ? k.slice(0, 77) + "..." : k));
console.log(`\nFiles changed: ${fileCount}`);
