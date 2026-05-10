"""Build Hostinger-ready ZIP package using stdlib zipfile."""
import os
import zipfile
import time

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
OUT = os.path.join(ROOT, 'drahmedzaki-site.zip')

# Top-level dirs to exclude
SKIP_DIRS = {
    'node_modules', '_new_assets', '_uploads_temp',
    'app', 'components', 'hooks', 'lib', 'styles', 'public',
    '.next', '.git', '.cache',
    'v0_memories', 'v0_' + 'plans',
    'user_read' + '_only_context',
}
# Top-level files to exclude
SKIP_FILES = {
    'package.json', 'package-lock.json', 'pnpm-lock.yaml', 'pnpm-workspace.yaml',
    'tsconfig.json', 'next.config.mjs', 'next.config.js', 'next-env.d.ts',
    'postcss.config.mjs', 'tailwind.config.ts', 'components.json',
    'drahmedzaki-site.zip', '.gitignore',
}

if os.path.exists(OUT):
    os.remove(OUT)
    print('Removed previous package')

print('Building package...')
start = time.time()
count = 0
total = 0
with zipfile.ZipFile(OUT, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
    for entry in os.listdir(ROOT):
        full = os.path.join(ROOT, entry)
        if os.path.isdir(full):
            if entry in SKIP_DIRS:
                continue
            for root, dirs, files in os.walk(full):
                # also skip nested matches just in case
                dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
                for f in files:
                    src = os.path.join(root, f)
                    rel = os.path.relpath(src, ROOT)
                    zf.write(src, rel)
                    count += 1
                    total += os.path.getsize(src)
                    if count % 1000 == 0:
                        print(f'  ... {count} files, {total/1024/1024:.1f} MB')
        else:
            if entry in SKIP_FILES:
                continue
            zf.write(full, entry)
            count += 1
            total += os.path.getsize(full)

elapsed = time.time() - start
size = os.path.getsize(OUT)
print(f'\nDone in {elapsed:.1f}s')
print(f'Files added: {count}')
print(f'Source bytes: {total/1024/1024:.1f} MB')
print(f'Package: {OUT}')
print(f'ZIP size: {size/1024/1024:.1f} MB')
