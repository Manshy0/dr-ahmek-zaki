const fs = require('fs');
const path = require('path');

const REPLACEMENTS = [
  // Toronto leftover
  ['University of Toronto', 'Ain Shams University Hospital, Cairo'],

  // Both unicode and HTML-entity apostrophes
  ['Recognized as a Member of one of the world\u2019s most prestigious surgical bodies.',
    'Active member of the AO Foundation Switzerland for advanced trauma and orthopedic surgery training.'],
  ['Recognized as a Member of one of the world&#8217;s most prestigious surgical bodies.',
    'Active member of the AO Foundation Switzerland for advanced trauma and orthopedic surgery training.'],

  // McMaster, Oxford, Cambridge if any
  ['McMaster University', 'Ain Shams University, Cairo'],
  ['University of Oxford', 'Ain Shams University, Cairo'],
  ['University of Cambridge', 'Ain Shams University, Cairo'],
  ['Harvard Medical School', 'Ain Shams University Hospital, Cairo'],

  // Refine the "completed rigorous medical and surgical training" full sentence (HTML entity version)
  ['board-certified orthopedic surgeon, completed rigorous medical and surgical training',
    'Specialist Orthopaedic Surgeon with over 17 years of clinical experience'],
];

const SKIP = new Set(['node_modules','.git','_new_assets','wp-includes','wp-admin','user_read_only_context','v0_memories','v0_plans']);

function walk(dir, files = []) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    if (e.isDirectory()) {
      if (!SKIP.has(e.name)) walk(path.join(dir, e.name), files);
    } else if (/\.(htm|html|js|json|xml|txt|php|css)$/i.test(e.name)) {
      files.push(path.join(dir, e.name));
    }
  }
  return files;
}

let totalFiles = 0, totalReplacements = 0;
for (const f of walk('.')) {
  let data;
  try { data = fs.readFileSync(f, 'utf-8'); } catch { continue; }
  let modified = false;
  let count = 0;
  for (const [from, to] of REPLACEMENTS) {
    if (data.includes(from)) {
      data = data.split(from).join(to);
      modified = true;
      count++;
    }
  }
  if (modified) {
    fs.writeFileSync(f, data, 'utf-8');
    totalFiles++;
    totalReplacements += count;
    console.log(`  [+${count}] ${f}`);
  }
}
console.log(`\nUpdated ${totalFiles} files with ${totalReplacements} replacements`);
