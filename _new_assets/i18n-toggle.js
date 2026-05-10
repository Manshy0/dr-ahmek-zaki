/* Dr. Ahmed Zaki - Arabic Language Toggle
 * Lightweight client-side translator for the most important visible strings.
 * Stores preference in localStorage. Adds an Arabic/English toggle button to the header.
 */
(function () {
  'use strict';

  // Translation dictionary: most common English strings that appear across the site.
  // The script does whole-word and phrase-level replacement on visible text nodes only.
  // Keys are matched case-sensitively first, then case-insensitively as fallback.
  var DICT = {
    // ====== Doctor name & titles ======
    'Dr. Ahmed Zaki': 'الدكتور أحمد زكي',
    'Specialist Orthopedic Surgeon': 'استشاري جراحة العظام',
    'Orthopedic Surgeon': 'جراح العظام',
    'Leading Orthopedic, Sports Injury & Trauma Surgeon': 'جراح عظام رائد وإصابات الملاعب والكسور',
    'Dedicated to Your Recovery': 'مكرّس لتعافيك',
    'Specialized in': 'متخصص في',
    'Orthopedics': 'جراحة العظام',

    // ====== Navigation ======
    'Home': 'الرئيسية',
    'Meet Dr. Ahmed Zaki': 'تعرّف على د. أحمد زكي',
    'Patient Resources': 'موارد المرضى',
    'Make an Appointment': 'احجز موعداً',
    'Appointment': 'موعد',
    'Medical Center': 'المركز الطبي',
    'Treatments': 'العلاجات',
    'News': 'الأخبار',
    'Blogs': 'المدونة',
    'Blog': 'المدونة',
    'Contact': 'تواصل',
    'About': 'نبذة',
    'About Us': 'من نحن',
    'Services': 'الخدمات',

    // ====== CTAs ======
    'Book Consultation': 'احجز استشارة',
    'Book a Consultation': 'احجز استشارة',
    'Book Appointment': 'احجز موعداً',
    'Patient Connection Hub': 'مركز التواصل',
    'Explore Treatments': 'استكشف العلاجات',
    'Explore All Treatments': 'كل العلاجات',
    'Get Started': 'ابدأ الآن',
    'Read More': 'اقرأ المزيد',
    'Learn More': 'اعرف المزيد',
    'View More': 'عرض المزيد',
    'Submit': 'إرسال',
    'Send': 'إرسال',
    'Send Message': 'إرسال الرسالة',
    'Find My Treatment Options': 'اعرف خياراتك العلاجية',
    'Book Appointment': 'احجز موعداً',

    // ====== Stats / labels ======
    'Years of Experience': 'سنوات من الخبرة',
    'Surgeries': 'العمليات',
    'Five-Star Reviews': 'تقييمات خماسية',
    '5-Star Reviews': 'تقييمات خماسية',
    'Consultations': 'استشارات',
    'Specialties': 'التخصصات',

    // ====== Specialties ======
    'Joint Preservation & Replacement': 'الحفاظ على المفاصل والاستبدال',
    'Sports Injury Recovery': 'علاج إصابات الملاعب',
    'Complex Trauma': 'الكسور المعقدة',
    'Shoulder Surgery': 'جراحة الكتف',
    'Sports Injury': 'إصابات الملاعب',
    'Trauma': 'الكسور والإصابات',
    'Comprehensive Orthopedic Care': 'رعاية عظام شاملة',
    'Tailored to Every Life Stage': 'مصمّمة لكل مرحلة عمرية',

    // ====== Trust badges ======
    'Fellowship-Trained Expertise': 'خبرة بزمالات تخصصية',
    'Innovation Meets Compassion': 'ابتكار ورحمة',
    'Patient-First Philosophy': 'فلسفة المريض أولاً',
    'Recognized Leader in Orthopedic Surgery': 'قائد معترف به في جراحة العظام',
    'Trusted by thousands. Driven by one purpose: your healing.': 'موثوق به من الآلاف. هدف واحد: شفاؤك.',

    // ====== Hero descriptive ======
    'Every injury tells a story': 'كل إصابة لها قصة',
    'Spaces Designed for Comfort.': 'فضاءات مصمّمة للراحة.',
    'Technology Built for Precision.': 'تقنية مبنية على الدقة.',
    'Pinpoint your pain. Find your path to recovery.': 'حدّد ألمك. اكتشف طريق تعافيك.',
    'How I Can Help You Heal': 'كيف أساعدك على الشفاء',

    // ====== Form labels ======
    'First Name': 'الاسم الأول',
    'Last Name': 'الاسم الأخير',
    'Full Name': 'الاسم الكامل',
    'Phone Number': 'رقم الهاتف',
    'Phone': 'الهاتف',
    'Email': 'البريد الإلكتروني',
    'Email Address': 'البريد الإلكتروني',
    'Message': 'الرسالة',
    'Subject': 'الموضوع',
    'Your Name': 'اسمك',
    'Your Email': 'بريدك الإلكتروني',
    'Your Message': 'رسالتك',
    'Select a Treatment': 'اختر علاجاً',
    'Select Type of the Consultation': 'اختر نوع الاستشارة',
    'Your First Step Toward a Stronger Tomorrow Starts Now:': 'خطوتك الأولى نحو غد أقوى تبدأ الآن:',

    // ====== Footer ======
    'Quick Links': 'روابط سريعة',
    'Contact Info': 'معلومات التواصل',
    'Follow Us': 'تابعنا',
    'All rights reserved.': 'جميع الحقوق محفوظة.',
    'Privacy Policy': 'سياسة الخصوصية',
    'Terms of Service': 'شروط الخدمة',
    'Patient Journey': 'رحلة المريض',
    'Case Studies': 'دراسات حالة',
    'Surgical Techniques': 'تقنيات جراحية',
    'Patient Education Hub': 'مركز توعية المرضى',
    'News & Testimonials': 'أخبار وآراء',

    // ====== Address / location ======
    'Medcare Royal Speciality Hospital': 'مستشفى ميدكير رويال التخصصي',
    'Al Qusais 2, Dubai': 'القصيص 2، دبي',
    'Dubai, UAE': 'دبي، الإمارات',
    'UAE, Dubai': 'الإمارات، دبي',

    // ====== Common UI ======
    'Loading...': 'جارٍ التحميل...',
    'Search': 'بحث',
    'Menu': 'القائمة',
    'Close': 'إغلاق',
    'Back': 'رجوع',
    'Next': 'التالي',
    'Previous': 'السابق'
  };

  var STORAGE_KEY = 'drazaki_lang';
  var lang = (function(){
    try { return localStorage.getItem(STORAGE_KEY) || 'en'; } catch(e){ return 'en'; }
  })();

  // ----------- Build sorted dict (longer phrases first) -----------
  var entries = Object.keys(DICT).sort(function(a,b){ return b.length - a.length; })
    .map(function(k){ return [k, DICT[k]]; });

  // Walk text nodes
  function walk(node, fn) {
    if (!node) return;
    if (node.nodeType === 3) { fn(node); return; }
    if (node.nodeType !== 1) return;
    var tag = node.nodeName;
    if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' || tag === 'IFRAME') return;
    if (node.classList && node.classList.contains('drazaki-lang-toggle')) return;
    var c = node.firstChild;
    while (c) { var n = c.nextSibling; walk(c, fn); c = n; }
  }

  function translateText(text) {
    if (!text || !text.trim()) return text;
    var out = text;
    for (var i = 0; i < entries.length; i++) {
      var en = entries[i][0]; var ar = entries[i][1];
      // Whole-string match (most common)
      if (out.trim() === en) { return out.replace(en, ar); }
    }
    // Phrase-level replace (substring)
    for (var j = 0; j < entries.length; j++) {
      var ee = entries[j][0]; var aa = entries[j][1];
      if (out.indexOf(ee) !== -1) {
        out = out.split(ee).join(aa);
      }
    }
    return out;
  }

  function applyArabic() {
    document.documentElement.setAttribute('lang', 'ar');
    document.documentElement.setAttribute('dir', 'rtl');
    document.body.classList.add('drazaki-rtl');
    walk(document.body, function (n) {
      if (!n.nodeValue || !n.nodeValue.trim()) return;
      if (!n.__drz_orig) n.__drz_orig = n.nodeValue;
      var t = translateText(n.__drz_orig);
      if (t !== n.nodeValue) n.nodeValue = t;
    });
    // Also translate placeholders, titles, alt
    document.querySelectorAll('[placeholder]').forEach(function(el){
      if (!el.__drz_ph) el.__drz_ph = el.getAttribute('placeholder');
      el.setAttribute('placeholder', translateText(el.__drz_ph));
    });
    document.querySelectorAll('[title]').forEach(function(el){
      if (!el.__drz_ti) el.__drz_ti = el.getAttribute('title');
      el.setAttribute('title', translateText(el.__drz_ti));
    });
    document.querySelectorAll('img[alt]').forEach(function(el){
      if (!el.__drz_al) el.__drz_al = el.getAttribute('alt');
      el.setAttribute('alt', translateText(el.__drz_al));
    });
  }

  function applyEnglish() {
    document.documentElement.setAttribute('lang', 'en-US');
    document.documentElement.setAttribute('dir', 'ltr');
    document.body.classList.remove('drazaki-rtl');
    walk(document.body, function (n) {
      if (n.__drz_orig) n.nodeValue = n.__drz_orig;
    });
    document.querySelectorAll('[placeholder]').forEach(function(el){
      if (el.__drz_ph) el.setAttribute('placeholder', el.__drz_ph);
    });
    document.querySelectorAll('[title]').forEach(function(el){
      if (el.__drz_ti) el.setAttribute('title', el.__drz_ti);
    });
    document.querySelectorAll('img[alt]').forEach(function(el){
      if (el.__drz_al) el.setAttribute('alt', el.__drz_al);
    });
  }

  function setLang(l) {
    lang = l;
    try { localStorage.setItem(STORAGE_KEY, l); } catch(e){}
    if (l === 'ar') applyArabic(); else applyEnglish();
    var btn = document.querySelector('.drazaki-lang-toggle');
    if (btn) btn.textContent = l === 'ar' ? 'English' : 'العربية';
  }

  function injectButton() {
    if (document.querySelector('.drazaki-lang-toggle')) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'drazaki-lang-toggle';
    btn.textContent = lang === 'ar' ? 'English' : 'العربية';
    btn.setAttribute('aria-label', 'Toggle language');
    btn.addEventListener('click', function () {
      setLang(lang === 'ar' ? 'en' : 'ar');
    });
    document.body.appendChild(btn);
  }

  function init() {
    injectButton();
    if (lang === 'ar') applyArabic();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
