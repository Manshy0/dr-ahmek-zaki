import zipfile, os, sys

ZIP = 'drahmedzaki-site.zip'
z = zipfile.ZipFile(ZIP)
names = z.namelist()
bad = z.testzip()

print(f"ZIP size: {os.path.getsize(ZIP)/1024/1024:.1f} MB")
print(f"Files in ZIP: {len(names)}")
print(f"Integrity: {'OK' if bad is None else f'CORRUPT: {bad}'}")
print()

print("=== Critical files ===")
critical = [
    'index.htm', '.htaccess', 'sitemap.xml', 'robots.txt',
    'favicon.ico', 'HOSTINGER-DEPLOY.md',
    'assets/drazaki/i18n-toggle.js', 'assets/drazaki/i18n-toggle.css',
    'meet-dr-ahmed-zaki/index.htm',
    'wp-content/uploads/2025/04/DSC02763-684x1024.jpg',
    'wp-content/uploads/2025/06/cropped-favicon-192x192.png',
]
for f in critical:
    print(f"  {'[OK]     ' if f in names else '[MISSING]'} {f}")

print()
print("=== Forbidden files ===")
forbidden_pre = ('node_modules/', '.next/', 'app/', 'components/', 'lib/', 'hooks/', '_new_assets/', 'v0_memories/', 'user_read_only_context/')
forbidden_eq = ('package.json','tsconfig.json','next.config.mjs','tailwind.config.ts','postcss.config.mjs','components.json','pnpm-lock.yaml')
forbidden = [n for n in names if n.startswith(forbidden_pre) or n in forbidden_eq]
print(f"Found: {len(forbidden)}")
for f in forbidden[:5]:
    print(' ', f)
