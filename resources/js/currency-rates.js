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
    const res = await fetch('/internal/currency/agroprombank', {
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) throw new Error('Currency rates request failed');

    const data = await res.json();
    const rates = data.rates;

    if (!rates?.PRB?.MDL || !rates?.PRB?.UAH) {
      throw new Error('Currency rates response is invalid');
    }


    localStorage.setItem(CACHE_KEY, JSON.stringify({ rates, timestamp: Date.now() }));

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
