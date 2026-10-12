<?php
// BookAFlight Language + Currency Switcher
// Include once just before </body>. No database changes.
?>
<style>
#baf-locale-widget, #baf-locale-widget * { box-sizing: border-box; }
#baf-locale-widget {
  position: fixed; top: 78px; right: 12px; z-index: 9980;
  direction: ltr; font-family: Arial, sans-serif; font-size: 14px;
}
#baf-locale-open {
  border: 1px solid #c8d8eb; background: #fff; color: #092c58;
  border-radius: 24px; padding: 10px 13px; cursor: pointer;
  min-height: 44px; font-weight: 700; box-shadow: 0 4px 16px #0002;
}
#baf-locale-panel {
  position: absolute; top: 50px; right: 0; width: 280px;
  max-width: calc(100vw - 24px); padding: 15px;
  border-radius: 14px; border: 1px solid #d8e2ee;
  background: #fff; color: #172b4d; box-shadow: 0 12px 36px #0003;
}
#baf-locale-panel[hidden] { display: none !important; }
#baf-locale-panel label { display: block; margin-bottom: 12px; font-weight: 700; color: inherit; }
#baf-locale-panel select {
  display: block; width: 100%; margin-top: 5px; padding: 10px;
  min-height: 44px; border: 1px solid #bacce0;
  background: #fff; color: #172b4d; border-radius: 8px; font: inherit;
}
#baf-fx-status { margin: 8px 0 0; font-size: 12px; line-height: 1.55; color: #496077; }
#baf-locale-panel button:focus-visible, #baf-locale-panel select:focus-visible,
#baf-locale-open:focus-visible { outline: 3px solid #4ca7fa; outline-offset: 2px; }
html[data-baf-theme="dark"] #baf-locale-open,
html[data-baf-theme="dark"] #baf-locale-panel {
  background: #162235; color: #f3f6fc; border-color: #3e536b;
}
html[data-baf-theme="dark"] #baf-locale-panel select {
  background: #101b2d; color: #f3f6fc; border-color: #526981;
}
html[data-baf-theme="dark"] #baf-fx-status { color: #c6d1e1; }
html[dir="rtl"] #baf-locale-panel { direction: rtl; }
html[dir="rtl"] [data-baf-bdt] { direction: ltr; unicode-bidi: isolate; }
@media (max-width: 600px) {
  #baf-locale-widget { top: 66px; right: 8px; }
  #baf-locale-open { padding: 10px; font-size: 12px; }
}
</style>

<div id="baf-locale-widget">
  <button id="baf-locale-open" type="button" aria-expanded="false"
          aria-controls="baf-locale-panel" aria-label="Language and currency settings">🌐 EN · BDT</button>
  <div id="baf-locale-panel" hidden>
    <label for="baf-language"><span data-baf-i18n="language">Language</span>
      <select id="baf-language">
        <option value="en">English</option>
        <option value="bn">বাংলা</option>
        <option value="ar">العربية</option>
        <option value="ur">اردو</option>
        <option value="hi">हिन्दी</option>
        <option value="ms">Bahasa Melayu</option>
      </select>
    </label>
    <label for="baf-currency"><span data-baf-i18n="currency">Currency</span>
      <select id="baf-currency">
        <option value="BDT">BDT — Bangladeshi Taka</option>
        <option value="USD">USD — US Dollar</option>
        <option value="EUR">EUR — Euro</option>
        <option value="GBP">GBP — British Pound</option>
        <option value="AED">AED — UAE Dirham</option>
        <option value="SAR">SAR — Saudi Riyal</option>
        <option value="INR">INR — Indian Rupee</option>
        <option value="MYR">MYR — Malaysian Ringgit</option>
        <option value="SGD">SGD — Singapore Dollar</option>
        <option value="THB">THB — Thai Baht</option>
        <option value="QAR">QAR — Qatari Riyal</option>
        <option value="TRY">TRY — Turkish Lira</option>
      </select>
    </label>
    <p id="baf-fx-status" role="status" aria-live="polite"></p>
  </div>
</div>

<script>
(() => {
  'use strict';
  if (window.__bafLocaleLoaded) return;
  window.__bafLocaleLoaded = true;

  const translations = {
    en: {language:'Language',currency:'Currency',search_flights:'Search Flights',group_booking:'Group Booking',travel_guide:'Travel Guide',favourite_routes:'Favourite Routes',price_comparison:'Price Comparison',my_bookings:'My Bookings',dashboard:'Dashboard',login:'Log in',logout:'Log out',from:'From',to:'To',departure:'Departure Date',return:'Return Date',passengers:'Passengers',search:'Search',select_flight:'Select Flight',book_now:'Book Now',support:'Customer Support',destination:'Destination',fx_bdt:'Prices shown in BDT.',fx_loading:'Loading reference exchange rates…',fx_ready:'Approximate conversion · rates dated {date}. Payment stays in BDT.',fx_failed:'Exchange rate unavailable — showing BDT prices. Payment stays in BDT.'},
    bn: {language:'ভাষা',currency:'মুদ্রা',search_flights:'ফ্লাইট খুঁজুন',group_booking:'গ্রুপ বুকিং',travel_guide:'ভ্রমণ গাইড',favourite_routes:'প্রিয় রুট',price_comparison:'ভাড়া তুলনা',my_bookings:'আমার বুকিং',dashboard:'ড্যাশবোর্ড',login:'লগইন',logout:'লগআউট',from:'যেখান থেকে',to:'যেখানে',departure:'যাত্রার তারিখ',return:'ফেরার তারিখ',passengers:'যাত্রী',search:'খুঁজুন',select_flight:'ফ্লাইট নির্বাচন',book_now:'এখনই বুক করুন',support:'কাস্টমার সাপোর্ট',destination:'গন্তব্য',fx_bdt:'দাম BDT-তে দেখানো হচ্ছে।',fx_loading:'বিনিময় হার লোড হচ্ছে…',fx_ready:'আনুমানিক রূপান্তর · {date} তারিখের হার। পেমেন্ট BDT-তেই থাকবে।',fx_failed:'বিনিময় হার পাওয়া যায়নি—দাম BDT-তেই দেখাচ্ছে। পেমেন্ট BDT-তেই থাকবে।'},
    ar: {language:'اللغة',currency:'العملة',search_flights:'ابحث عن رحلات',group_booking:'حجز المجموعات',travel_guide:'دليل السفر',favourite_routes:'المسارات المفضلة',price_comparison:'مقارنة الأسعار',my_bookings:'حجوزاتي',dashboard:'لوحة التحكم',login:'تسجيل الدخول',logout:'تسجيل الخروج',from:'من',to:'إلى',departure:'تاريخ المغادرة',return:'تاريخ العودة',passengers:'المسافرون',search:'بحث',select_flight:'اختر الرحلة',book_now:'احجز الآن',support:'دعم العملاء',destination:'الوجهة',fx_bdt:'الأسعار معروضة بالتكا البنغلاديشية.',fx_loading:'جارٍ تحميل أسعار الصرف المرجعية…',fx_ready:'تحويل تقريبي · أسعار بتاريخ {date}. الدفع يبقى بعملة BDT.',fx_failed:'أسعار الصرف غير متاحة؛ الأسعار معروضة بعملة BDT.'},
    ur: {language:'زبان',currency:'کرنسی',search_flights:'پروازیں تلاش کریں',group_booking:'گروپ بکنگ',travel_guide:'سفری رہنما',favourite_routes:'پسندیدہ راستے',price_comparison:'قیمتوں کا موازنہ',my_bookings:'میری بکنگ',dashboard:'ڈیش بورڈ',login:'لاگ اِن',logout:'لاگ آؤٹ',from:'کہاں سے',to:'کہاں تک',departure:'روانگی کی تاریخ',return:'واپسی کی تاریخ',passengers:'مسافر',search:'تلاش کریں',select_flight:'پرواز منتخب کریں',book_now:'ابھی بک کریں',support:'کسٹمر سپورٹ',destination:'منزل',fx_bdt:'قیمتیں BDT میں دکھائی جا رہی ہیں۔',fx_loading:'زرِ مبادلہ کی شرح لوڈ ہو رہی ہے…',fx_ready:'تخمینی تبدیلی · شرح کی تاریخ {date}۔ ادائیگی BDT میں ہوگی۔',fx_failed:'شرح دستیاب نہیں؛ قیمتیں BDT میں دکھائی جا رہی ہیں۔'},
    hi: {language:'भाषा',currency:'मुद्रा',search_flights:'उड़ानें खोजें',group_booking:'समूह बुकिंग',travel_guide:'यात्रा गाइड',favourite_routes:'पसंदीदा रूट',price_comparison:'कीमत तुलना',my_bookings:'मेरी बुकिंग',dashboard:'डैशबोर्ड',login:'लॉग इन',logout:'लॉग आउट',from:'कहाँ से',to:'कहाँ तक',departure:'प्रस्थान तिथि',return:'वापसी तिथि',passengers:'यात्री',search:'खोजें',select_flight:'उड़ान चुनें',book_now:'अभी बुक करें',support:'ग्राहक सहायता',destination:'गंतव्य',fx_bdt:'कीमतें BDT में दिखाई जा रही हैं।',fx_loading:'विनिमय दर लोड हो रही है…',fx_ready:'अनुमानित रूपांतरण · {date} की दरें। भुगतान BDT में रहेगा।',fx_failed:'विनिमय दर उपलब्ध नहीं; कीमतें BDT में दिखाई जा रही हैं।'},
    ms: {language:'Bahasa',currency:'Mata wang',search_flights:'Cari Penerbangan',group_booking:'Tempahan Berkumpulan',travel_guide:'Panduan Perjalanan',favourite_routes:'Laluan Kegemaran',price_comparison:'Perbandingan Harga',my_bookings:'Tempahan Saya',dashboard:'Papan Pemuka',login:'Log masuk',logout:'Log keluar',from:'Dari',to:'Ke',departure:'Tarikh Berlepas',return:'Tarikh Pulang',passengers:'Penumpang',search:'Cari',select_flight:'Pilih Penerbangan',book_now:'Tempah Sekarang',support:'Sokongan Pelanggan',destination:'Destinasi',fx_bdt:'Harga dipaparkan dalam BDT.',fx_loading:'Memuatkan kadar pertukaran rujukan…',fx_ready:'Penukaran anggaran · kadar bertarikh {date}. Bayaran kekal dalam BDT.',fx_failed:'Kadar pertukaran tidak tersedia — harga dipaparkan dalam BDT.'}
  };

  const localeTags = {en:'en',bn:'bn-BD',ar:'ar',ur:'ur',hi:'hi-IN',ms:'ms-MY'};
  const allowedCurrencies = new Set(['BDT','USD','EUR','GBP','AED','SAR','INR','MYR','SGD','THB','QAR','TRY']);
  const langSelect = document.getElementById('baf-language');
  const currencySelect = document.getElementById('baf-currency');
  const toggle = document.getElementById('baf-locale-open');
  const panel = document.getElementById('baf-locale-panel');
  const status = document.getElementById('baf-fx-status');
  const cacheKey = 'baf_bdt_rates_v1';

  const store = (key, value) => {try {localStorage.setItem(key, value);} catch (_) {}};
  const read = key => {try {return localStorage.getItem(key);} catch (_) {return null;}};
  let lang = Object.hasOwn(translations, read('baf_language')) ? read('baf_language') : 'en';
  let currency = allowedCurrencies.has(read('baf_currency')) ? read('baf_currency') : 'BDT';
  let rates = null;
  let rateDate = '';
  let fxLoading = false;

  langSelect.value = lang;
  currencySelect.value = currency;

  function tr(key) {return translations[lang]?.[key] ?? translations.en[key] ?? key;}
  function emit(name) {window.dispatchEvent(new CustomEvent(name, {detail:{language:lang, currency}}));}

  function translatePage() {
    document.documentElement.lang = localeTags[lang];
    document.documentElement.dir = (lang === 'ar' || lang === 'ur') ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-baf-i18n]').forEach(el => {
      const value = tr(el.dataset.bafI18n);
      if (el.textContent !== value) el.textContent = value;
    });
    document.querySelectorAll('[data-baf-i18n-placeholder]').forEach(el => {
      el.placeholder = tr(el.dataset.bafI18nPlaceholder);
    });
  }

  function formatMoney(bdtAmount) {
    const amount = Number(bdtAmount);
    if (!Number.isFinite(amount) || amount < 0) return '—';
    const convert = currency !== 'BDT' && rates && Number.isFinite(rates[currency]);
    const code = convert ? currency : 'BDT';
    const value = convert ? amount * rates[currency] : amount;
    return new Intl.NumberFormat(localeTags[lang], {
      style: 'currency', currency: code,
      maximumFractionDigits: code === 'BDT' ? 0 : 2
    }).format(value);
  }
  window.bafFormatCurrency = formatMoney;
  window.bafRefreshLocale = () => {translatePage(); renderPrices();};

  function renderPrices() {
    document.querySelectorAll('[data-baf-bdt]').forEach(el => {
      const base = Number(el.dataset.bafBdt);
      if (Number.isFinite(base) && base >= 0) el.textContent = formatMoney(base);
    });
  }

  function updateStatus() {
    let message = 'fx_bdt';
    if (currency !== 'BDT') {
      const hasSelectedRate = rates && Number.isFinite(rates[currency]) && rates[currency] > 0;
      message = hasSelectedRate ? 'fx_ready' : (fxLoading ? 'fx_loading' : 'fx_failed');
    }
    status.textContent = tr(message).replace('{date}', rateDate);
    toggle.textContent = `🌐 ${lang.toUpperCase()} · ${currency}`;
  }

  function refresh() {
    translatePage(); renderPrices(); updateStatus();
    emit('baf:locale-change');
    emit('baf:currency-change');
  }

  function isRecentPayload(data) {
    if (!data || typeof data !== 'object' || !data.bdt || typeof data.bdt !== 'object') return false;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(data.date || '')) return false;
    const age = Date.now() - Date.parse(`${data.date}T00:00:00Z`);
    return Number.isFinite(age) && age > -86400000 && age < 7 * 86400000 &&
      ['usd','eur','gbp'].every(code => Number.isFinite(Number(data.bdt[code])) && Number(data.bdt[code]) > 0);
  }

  function saveRates(data, persist = true) {
    if (!isRecentPayload(data)) return false;
    const next = {};
    for (const code of allowedCurrencies) {
      const rate = Number(data.bdt[code.toLowerCase()]);
      if (code !== 'BDT' && Number.isFinite(rate) && rate > 0) next[code] = rate;
    }
    rates = next;
    rateDate = data.date;
    if (persist) {
      try { localStorage.setItem(cacheKey, JSON.stringify({at:Date.now(), data})); } catch (_) {}
    }
    return true;
  }

  function cachedRates() {
    try {
      const item = JSON.parse(read(cacheKey) || 'null');
      if (item && Number.isFinite(item.at) && Date.now() - item.at < 6 * 3600000) {
        return saveRates(item.data, false);
      }
    } catch (_) {}
    return false;
  }

  async function requestRates(url) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 6000);
    try {
      const response = await fetch(url, {signal:controller.signal, cache:'no-cache'});
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return await response.json();
    } finally {clearTimeout(timer);}
  }

  async function loadRates() {
    if (cachedRates()) { refresh(); return; }
    fxLoading = true; updateStatus();
    const urls = [
      'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/bdt.min.json',
      'https://latest.currency-api.pages.dev/v1/currencies/bdt.min.json'
    ];
    try {
      let ok = false;
      for (const url of urls) {
        try {if (saveRates(await requestRates(url))) {ok = true; break;}} catch (_) {}
      }
      if (!ok) throw new Error('No valid recent currency data');
    } catch (_) {rates = null; rateDate = '';}
    finally {fxLoading = false; refresh();}
  }

  toggle.addEventListener('click', () => {
    panel.hidden = !panel.hidden;
    toggle.setAttribute('aria-expanded', String(!panel.hidden));
  });
  document.addEventListener('click', e => {
    if (!document.getElementById('baf-locale-widget').contains(e.target)) {
      panel.hidden = true; toggle.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {panel.hidden = true; toggle.setAttribute('aria-expanded', 'false');}
  });
  langSelect.addEventListener('change', () => {
    lang = langSelect.value; store('baf_language', lang); refresh();
  });
  currencySelect.addEventListener('change', () => {
    currency = currencySelect.value; store('baf_currency', currency); refresh();
  });
  refresh();
  loadRates();
})();
</script>