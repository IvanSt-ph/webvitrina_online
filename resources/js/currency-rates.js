// ===============================================================
// === 💱 Автоматическое обновление курсов ПМР (Агропромбанк) ====
// ===============================================================
export async function getCurrencyRates() {
  const CACHE_KEY = 'agroprombank_rates';
  const CACHE_TTL = 24 * 60 * 60 * 1000; // 24 часа

  const cached = localStorage.getItem(CACHE_KEY);
  if (cached) {
    const { rates, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp < CACHE_TTL) return rates;
  }

  try {
const res = await fetch('/internal/currency/agroprombank');

    const html = await res.text();

    // 🧠 Парсим из HTML курсы (примерно: "UAH 0.3650 / 0.4000")
    const uahMatch = html.match(/UAH[^0-9]+([\d.,]+)[^0-9]+([\d.,]+)/i);
    const mdlMatch = html.match(/MDL[^0-9]+([\d.,]+)[^0-9]+([\d.,]+)/i);

    const buyUAH = uahMatch ? parseFloat(uahMatch[1].replace(',', '.')) : 0.365;
    const sellUAH = uahMatch ? parseFloat(uahMatch[2].replace(',', '.')) : 0.4;
    const buyMDL = mdlMatch ? parseFloat(mdlMatch[1].replace(',', '.')) : 0.95;
    const sellMDL = mdlMatch ? parseFloat(mdlMatch[2].replace(',', '.')) : 1.06;

// Средние значения — заменяем на банковские
// Банк считает: 1 PRB = 1 / buyRate
const avgUAH = 1 / buyUAH;
const avgMDL = 1 / buyMDL;

// Переводим в "1 ПМР = X"
const rates = {
  PRB: { PRB: 1, MDL: avgMDL, UAH: avgUAH },
  MDL: { PRB: 1 / avgMDL, MDL: 1, UAH: avgUAH / avgMDL },
  UAH: { PRB: 1 / avgUAH, MDL: avgMDL / avgUAH, UAH: 1 },
};


    localStorage.setItem(CACHE_KEY, JSON.stringify({ rates, timestamp: Date.now() }));
    console.log('💱 Курсы обновлены:', rates);

    return rates;
  } catch (err) {
    console.warn('Ошибка загрузки курса Агропромбанка:', err);
    // fallback
    return {
      PRB: { PRB: 1, MDL: 1.06, UAH: 2.6 },
      MDL: { PRB: 0.94, MDL: 1, UAH: 2.45 },
      UAH: { PRB: 0.385, MDL: 0.41, UAH: 1 },
    };
  }
}
