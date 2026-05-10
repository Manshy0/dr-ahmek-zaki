// Build Hostinger-ready ZIP package
// Excludes: node_modules, _new_assets, dev tooling, Next.js scaffolding, hidden dirs
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const OUT = path.join(ROOT, 'drahmedzaki-site.zip');

// Files / dirs to exclude (relative to project root)
const EXCLUDES = [
  'node_modules/*',
  'node_modules',
  '_new_assets/*',
  '_new_assets',
  'app/*',
  'app',
  'components/*',
  'components',
  'hooks/*',
  'hooks',
  'lib/*',
  'lib',
  '.next/*',
  '.next',
  '.git/*',
  '.git',
  'package.json',
  'package-lock.json',
  'pnpm-lock.yaml',
  'pnpm-workspace.yaml',
  'tsconfig.json',
  'next.config.mjs',
  'next.config.js',
  'next-env.d.ts',
  'postcss.config.mjs',
  'tailwind.config.ts',
  'components.json',
  'styles/*',
  'styles',
  'public/*',
  'public',
  'drahmedzaki-site.zip',
  'v0_memories/*',
  'v0_memories',
  'v0_plans/*',
  'v0_plans',
  '_uploads_temp/*',
  'user_read_only_context/*',
  'user_read_only_context',
];

if (fs.existsSync(OUT)) {
  fs.unlinkSync(OUT);
  console.log('Removed previous package');
}

const xargs = EXCLUDES.map(e => `-x "${e}"`).join(' ');
const cmd = `cd "${ROOT}" && zip -r -q "${OUT}" . ${xargs}`;

console.log('Building package...');
const start = Date.now();
execSync(cmd, { stdio: 'inherit' });
const elapsed = ((Date.now() - start) / 1000).toFixed(1);

const size = fs.statSync(OUT).size;
console.log(`\nDone in ${elapsed}s`);
console.log(`Package: ${OUT}`);
console.log(`Size: ${(size / 1024 / 1024).toFixed(1)} MB`);
