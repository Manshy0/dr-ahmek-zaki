import os
from datetime import date

today = date.today().isoformat()
domain = "https://drahmedzaki.com"
# directories to skip
skip_dirs = {"node_modules", ".git", "_new_assets", "wp-includes", "wp-admin",
             "_uploads_temp", "v0_memories", "user_read" + "_only_context",
             "v0_" + "plans", ".next", ".cache"}

urls = []
root_dir = os.path.join(os.path.dirname(__file__), '..')
for root, dirs, files in os.walk(root_dir):
    dirs[:] = [d for d in dirs if d not in skip_dirs]
    for f in files:
        if f in ('index.htm', 'index.html'):
            rel = os.path.relpath(os.path.join(root, f), root_dir).replace('\\', '/')
            if rel in ('index.htm', 'index.html'):
                url = '/'
            else:
                url = '/' + rel.rsplit('/index.', 1)[0] + '/'
            urls.append(url)

urls = sorted(set(urls))

out = ['<?xml version="1.0" encoding="UTF-8"?>',
       '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
for u in urls:
    priority = "1.0" if u == "/" else ("0.8" if u.count("/") <= 2 else "0.6")
    out.append("  <url>\n    <loc>" + domain + u + "</loc>\n    <lastmod>" + today + "</lastmod>\n    <changefreq>monthly</changefreq>\n    <priority>" + priority + "</priority>\n  </url>")
out.append("</urlset>")

open(os.path.join(root_dir, 'sitemap.xml'), 'w', encoding='utf-8').write('\n'.join(out))
print("OK", len(urls), "URLs in sitemap.xml")
