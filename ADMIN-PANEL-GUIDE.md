# Admin Panel — دليل الاستخدام

لوحة تحكم خاصة بـ PHP، تشتغل على Hostinger مباشرة بدون أي إعدادات.
بتديك إمكانية تعديل الموقع بالكامل أون لاين بدون لمس الملفات.

---

## كيف ترفعها على Hostinger

### الخطوة 1: ارفع مجلد `/admin`

1. ادخل **hPanel** على Hostinger
2. افتح **File Manager**
3. روح للمجلد `public_html` (اللي فيه `index.htm` بتاع موقعك)
4. ارفع كامل مجلد `admin/` جنب باقي ملفات الموقع
   - يا إما زيب المجلد محلياً وارفع الـ ZIP وافك ضغطه على السيرفر
   - يا إما ارفع الملفات واحدة واحدة (أسرع من File Manager → Upload)

### الخطوة 2: تأكد من صلاحيات الملفات

ملف `.htaccess` المرفق هيوفر الحماية الأساسية. تأكد إن:
- المجلد `/admin/data/` صلاحياته `755` (قابل للكتابة)
- المجلد `/admin/backups/` صلاحياته `755`
- المجلد `/wp-content/uploads/custom/` (هيتعمل تلقائياً) صلاحياته `755`

في File Manager: كليك يمين على المجلد → Permissions → 755

### الخطوة 3: أول دخول

1. افتح في المتصفح: `https://yourdomain.com/admin/`
2. هتلاقي رسالة إن في ملف اسمه `INITIAL-PASSWORD.txt`
3. افتح File Manager وروح لـ `/admin/data/INITIAL-PASSWORD.txt`
4. هتلاقي:
   ```
   Username: admin
   Password: [كلمة سر عشوائية]
   ```
5. ادخل بيها على `/admin/login.php`
6. **مهم جداً**: من Users، عدّل كلمة السر بتاعتك لكلمة جديدة آمنة
7. **بعد كده احذف ملف `INITIAL-PASSWORD.txt`** من File Manager

---

## ميزات اللوحة

### 1. Dashboard (الرئيسية)
- عدد الصفحات والمقالات
- آخر التعديلات
- نشاط المستخدمين
- اختصارات سريعة

### 2. Pages (الصفحات)
- ليستة بكل صفحات الموقع
- بحث وفلترة
- زرار **Edit** لكل صفحة → يفتح المحرر المرئي

### 3. Visual Editor (المحرر المرئي) — أهم حاجة
- بيفتح الصفحة في إطار داخلي
- **اضغط على أي نص** علشان تعدله مباشرة
- **اضغط على أي صورة** علشان تستبدلها أو ترفع جديدة
- **اضغط على أي خلفية** علشان تغير الـ background image
- زرار **Save changes** يحفظ كل التعديلات دفعة واحدة
- اختصار `Ctrl + S` للحفظ
- في backup تلقائي قبل أي حفظ (لو حصلت غلطة)

### 4. Blog & News (المقالات والأخبار)
- إنشاء مقال جديد بصفحة واحدة (عنوان + slug + صورة + محتوى HTML)
- بعد الإنشاء → بيفتحلك المحرر المرئي تلقائياً علشان تعدل
- حذف المقالات (مع backup)

### 5. Media Library (الصور)
- رفع صور بـ Drag & Drop
- نسخ رابط الصورة بضغطة
- حذف الصور القديمة
- بترتفع في `/wp-content/uploads/custom/YYYY/MM/`

### 6. Menu & Footer
- تعديل عناصر القائمة
- بيانات التواصل (تليفون، إيميل، عنوان)
- روابط السوشيال ميديا

### 7. SEO
- Title و Meta description لكل صفحة
- Open Graph (للمشاركة على فيسبوك وتويتر)
- Canonical URL
- Robots (index/noindex)

### 8. Users (إدارة المستخدمين) — Admin فقط
أربع صلاحيات:
- **Administrator**: تحكم كامل، يقدر يدير المستخدمين
- **Editor**: يعدل ويحذف أي محتوى، بدون إدارة مستخدمين
- **Author**: ينشئ مقالات بس
- **Contributor**: يعمل drafts فقط

---

## الأمان

- كل التعديلات بتعمل **backup تلقائي** في `/admin/backups/` (آخر 20 نسخة لكل ملف)
- محاولات الدخول الخاطئة بتقفل الحساب 15 دقيقة بعد 5 محاولات
- Sessions بتنتهي بعد 8 ساعات
- CSRF tokens على كل العمليات
- بيانات المستخدمين متشفرة بـ bcrypt
- ملف `.htaccess` بيمنع الدخول لمجلدات `data` و `backups` و `includes`

---

## استكشاف الأخطاء

### "File not writable"
صلاحيات الملف `644` أو المجلد `755`. روح File Manager → كليك يمين → Permissions.

### "Upload directory not writable"
`/wp-content/uploads/custom/` لازم يكون 755. لو مش موجود، اعمله من File Manager.

### نسيت كلمة السر؟
1. ادخل File Manager
2. احذف ملف `/admin/data/users.json`
3. ادخل على `/admin/login.php` وهيتعمل user جديد بكلمة سر تلقائية

### الموقع بطيء أو في خطأ؟
شوف ملف `/admin/data/error.log` من File Manager.

### عاوز تستعيد نسخة قديمة من صفحة؟
الـ backups بتتحفظ في `/admin/backups/` بصيغة:
`20251108_143000__blogs__post__index.htm`
انسخ المحتوى منها والصقه في الملف الأصلي عبر File Manager.

---

## نصائح للاستخدام

1. **ابدأ بحساب admin قوي**: غيّر كلمة السر لحاجة معقدة فوراً
2. **اعمل حسابات Editors لزملاءك**: من Users → Add new
3. **خليك في الـ Editor المرئي**: أسهل من تعديل HTML يدوي
4. **قبل تعديلات كبيرة**: اعمل backup كامل لـ public_html من Hostinger
5. **بعد كل تعديل**: شوف الصفحة على الموقع تأكد إنها ظاهرة صح

---

## Files structure

```
/admin/
├── index.php             ← Dashboard
├── login.php             ← Login page
├── logout.php
├── pages.php             ← Pages list
├── posts.php             ← Blog/News manager
├── media.php             ← Media library
├── menu.php              ← Menu & footer
├── seo.php               ← SEO settings
├── users.php             ← User management
├── edit.php              ← Visual editor wrapper
│
├── api/
│   ├── serve.php         ← Serves pages with editor injected
│   ├── save-content.php  ← Saves HTML edits
│   ├── upload-image.php  ← Image uploads
│   ├── delete-page.php
│   └── delete-image.php
│
├── includes/
│   ├── config.php
│   ├── auth.php
│   ├── helpers.php
│   ├── header.php
│   └── footer.php
│
├── assets/
│   ├── admin.css
│   ├── admin.js
│   ├── inline-editor.css
│   └── inline-editor.js  ← Magic happens here
│
├── data/                 ← Users, activity, configs (protected)
├── backups/              ← Auto-backups before each save (protected)
└── .htaccess             ← Security rules
```
