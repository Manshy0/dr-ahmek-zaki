# دليل رفع الموقع على Hostinger
## موقع: الدكتور أحمد زكي (Dr. Ahmed Zaki)

---

## نظرة عامة

هذا الموقع موقع ثابت (Static HTML) — لا يحتاج PHP/MySQL/WordPress.
يمكن رفعه على **أي استضافة**، وأبسطها Hostinger Shared Hosting.

---

## خطوات الرفع على Hostinger

### الخطوة 1 — تجهيز ملف الـ ZIP
1. افتح مجلد المشروع.
2. حدد **كل** الملفات والمجلدات **ما عدا** المجلدات التالية (لا تحتاجها على الاستضافة):
   - `node_modules/`
   - `_new_assets/`
   - `package.json`, `package-lock.json`, `pnpm-lock.yaml`
   - `tsconfig.json`, `next.config.mjs`, `next-env.d.ts`
   - `app/`, `components/`, `hooks/`, `lib/` (مجلدات Next.js — غير مستخدمة)
   - مجلدات `.git/`, `.next/`, `v0_*`, `user_read_only_context/`
3. اضغط الباقي في ملف `drahmedzaki-site.zip`.

أو استخدم السكربت الجاهز:
```bash
node _new_assets/build-package.js
```

### الخطوة 2 — تسجيل الدخول لـ Hostinger
1. افتح https://hpanel.hostinger.com
2. اختر **Hosting → Manage** للدومين الذي تريد الرفع عليه.

### الخطوة 3 — الرفع عبر File Manager
1. من القائمة الجانبية، افتح **Files → File Manager**.
2. ادخل لمجلد `public_html` (أو `domains/your-domain.com/public_html`).
3. **احذف** أي ملفات افتراضية موجودة (مثل `default.php`, `index.html`).
4. اضغط زر **Upload Files** ثم ارفع `drahmedzaki-site.zip`.
5. كليك يمين على الـ ZIP المرفوع → **Extract**.
6. احذف ملف الـ ZIP بعد فك الضغط.

### الخطوة 4 — التأكد من ملف .htaccess
- ملف `.htaccess` موجود في الحزمة (مخفي).
- يحتوي على إعدادات: HTTPS، GZIP، Cache headers، أمان.
- إذا لم يظهر، فعّل **Show Hidden Files** في File Manager.

### الخطوة 5 — التحقق من الفهرس الافتراضي
ملفات الموقع تستخدم `index.htm` (وليس `index.html` أو `index.php`).
الـ `.htaccess` يضبط ذلك تلقائياً عبر السطر:
```
DirectoryIndex index.htm index.html index.php
```

### الخطوة 6 — ربط الدومين وتفعيل SSL
1. من **Domains → DNS / Nameservers** تأكد أن الدومين مربوط بالاستضافة.
2. من **Security → SSL** فعّل **Let's Encrypt SSL** (مجاني).
3. انتظر 5-15 دقيقة لانتشار الـ DNS.

### الخطوة 7 — اختبار الموقع
افتح المتصفح وادخل:
- `https://your-domain.com/` → يجب أن تفتح الصفحة الرئيسية.
- `https://your-domain.com/meet-dr-ahmed-zaki/` → صفحة التعريف.
- `https://your-domain.com/news/` → الأخبار.
- `https://your-domain.com/?lang=ar` ثم اضغط زر "العربية" → التبديل للعربية.

---

## بنية المشروع

```
/
├── index.htm                     # الصفحة الرئيسية
├── .htaccess                     # إعدادات Apache (HTTPS, gzip, cache)
├── robots.txt                    # توجيهات محركات البحث
├── sitemap.xml                   # خريطة الموقع
├── favicon.ico
├── assets/drazaki/               # ملفات تبديل اللغة (JS + CSS)
│   ├── i18n-toggle.js
│   └── i18n-toggle.css
├── meet-dr-ahmed-zaki/           # صفحة "تعرّف على الطبيب"
├── treatments/                   # العلاجات
├── news/                         # الأخبار
├── blogs/                        # المدوّنة
├── appointments/                 # الحجز
├── patient-connection-hub/       # مركز التواصل
├── results/                      # النتائج
├── gallery/                      # المعرض
├── shoulder-treatment-dubai/     # علاج الكتف
├── patient_journey/              # رحلة المريض
└── wp-content/uploads/           # كل الصور
```

---

## بيانات التواصل المحدّثة

| البيان | القيمة |
|---|---|
| الاسم | Dr. Ahmed Zaki |
| التخصص | Specialist Orthopedic Surgeon |
| الهاتف / واتساب | +971 58 567 0984 |
| البريد | info@drahmedzaki.ae |
| العنوان | Medcare Royal Speciality Hospital, 16 18th St — Al Qusais 2, Dubai, UAE |
| Twitter / X | @drahmedzaki |

> **مهم:** كل هذه القيم في ملفات HTML. لتعديلها، استخدم بحث-واستبدال على المجلد كاملاً.

---

## التحسينات المطبّقة

- ضغط 1498 صورة (وفّر 26.7 MB).
- تفعيل GZIP عبر `.htaccess`.
- ضبط Cache-Control للصور (1 سنة) والـ HTML (1 ساعة).
- تفعيل HTTPS وredirect إجباري.
- Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy).
- إنشاء `sitemap.xml` بـ 49 URL.
- إضافة دعم العربية (RTL) عبر زر تبديل في كل الصفحات.

---

## استكشاف الأخطاء

### الموقع يفتح صفحة فارغة أو 404
- تأكد أن الملفات في `public_html` مباشرة (وليس داخل مجلد فرعي مثل `public_html/site/`).
- تأكد أن `index.htm` موجود في الجذر.

### الصور لا تظهر
- تأكد أن مجلد `wp-content/uploads/` مرفوع كاملاً.
- تأكد من صلاحيات الملفات (644 للملفات، 755 للمجلدات).

### زر اللغة لا يظهر
- تأكد أن مجلد `assets/drazaki/` مرفوع.
- افتح Console في المتصفح وتأكد أن `i18n-toggle.js` يحمل بـ status 200.

### الـ .htaccess لا يعمل
- بعض إعدادات الـ Hostinger تتطلب تفعيل `mod_rewrite`. تحقق من Hostinger Support إذا واجهت مشاكل.

---

## نصائح إضافية

### إعدادات Hostinger الموصى بها
1. **PHP Version**: غير مهم (الموقع ثابت).
2. **Caching**: فعّل **LiteSpeed Cache** من **Advanced → Cache Manager**.
3. **CDN**: فعّل **Cloudflare** المجاني المرفق مع Hostinger Premium.
4. **Backups**: فعّل النسخ الاحتياطي اليومي.

### تحديث المحتوى لاحقاً
- لتعديل نص: افتح ملف `.htm` في المجلد المعني واستبدل النص.
- لإضافة صفحة جديدة: انسخ مجلد قائم وعدّله.
- لتغيير صورة: استبدل الملف في `wp-content/uploads/` بنفس الاسم.

### إضافة Google Analytics
أضف هذا قبل `</head>` في كل ملف HTML (أو استخدم سكربت بحث-واستبدال):
```html
<script async src="https://www.googletagmanager.com/gtag/js?id=YOUR_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'YOUR_ID');
</script>
```
