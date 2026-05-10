// Replace fake/old credentials with real Dr. Ahmed Zaki credentials
// Source: https://darkslategrey-mandrill-488404.hostingersite.com
const fs = require('fs');
const path = require('path');

// Order matters: longer/more specific phrases first
const REPLACEMENTS = [
  // Degrees
  ['MD &amp; PhD in Orthopedic Surgery', 'MBBCh &amp; MSc in Orthopedic and Trauma Surgery'],
  ['MD & PhD in Orthopedic Surgery',     'MBBCh & MSc in Orthopedic and Trauma Surgery'],
  ['MD &amp; PhD',                       'MBBCh &amp; MSc'],
  ['MD & PhD',                           'MBBCh & MSc'],

  // MRCS UK / Royal College
  ['MRCS (UK) – Royal College of Surgeons of England',
    'AO Trauma (Switzerland) – AO Foundation Member'],
  ['MRCS (UK) - Royal College of Surgeons of England',
    'AO Trauma (Switzerland) - AO Foundation Member'],
  ['MRCS (UK)',                          'AO Trauma (Switzerland)'],
  ['MRCS(UK)',                           'AO Trauma (Switzerland)'],
  ['MRCS',                               'AO Trauma'],
  ['Royal College of Surgeons of England',
    'AO Foundation, Switzerland'],
  ['Royal College of Surgeons',          'AO Foundation'],

  // Universities
  ['University of Edinburgh',            'Ain Shams University Hospital, Cairo'],
  ['University of Manchester',           'Ain Shams University, Cairo'],
  ['University Of Edinburgh',            'Ain Shams University Hospital, Cairo'],

  // Description of board / fellowship
  ['Fellowship in Upper Extremity Surgery – University of Edinburgh',
    'Trauma & Sports Injuries Training – Ain Shams University Hospital'],
  ['Fellowship in Upper Extremity Surgery - University of Edinburgh',
    'Trauma & Sports Injuries Training - Ain Shams University Hospital'],
  ['Fellowship in Upper Extremity Surgery',
    'Specialist in Trauma & Sports Injuries'],
  ['Fellowship-Trained in',              'Specialist in'],
  ['Upper Extremity Surgery',            'Trauma & Sports Injuries'],

  // Generic recognition lines
  ['Recognized as a Member of one of the world&#8217;s most prestigious surgical bodies.',
    'Active member of the AO Foundation Switzerland for advanced trauma and orthopedic surgery training.'],
  ['Recognized as a Member of one of the world\'s most prestigious surgical bodies.',
    'Active member of the AO Foundation Switzerland for advanced trauma and orthopedic surgery training.'],

  // "Dr. Saleh" leftovers
  ['Dr. Saleh', 'Dr. Ahmed Zaki'],
  ['Dr Saleh',  'Dr. Ahmed Zaki'],

  // GCC/regional generic
  ['Dr. Ahmed Zaki delivers cutting-edge orthopedic solutions tailored to the needs of patients in the GCC.',
    'Dr. Ahmed Zaki delivers cutting-edge orthopedic solutions tailored to the needs of patients in the UAE and the GCC region.'],

  // Update tagline / hero
  ['board-certified orthopedic surgeon, completed rigorous medical and surgical training',
    'Specialist Orthopaedic Surgeon with over 17 years of clinical experience'],

  // Stats / years
  ['25+ years', '17+ years'],
  ['25 + years', '17+ years'],
  ['25+ Years',  '17+ Years'],
  ['Over 25 years', 'Over 17 years'],
  ['over 25 years', 'over 17 years'],
];

const SKIP = new Set(['node_modules','.git','_new_assets','wp-includes','wp-admin','user_read_only_context','v0_memories','v0_plans','.next']);

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
    const before = data.length;
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
