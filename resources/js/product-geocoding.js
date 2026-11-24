// Shared seller/admin search: use Nominatim's first result, as the seller form did.
export async function geocodeLocation(query, { signal } = {}) {
    const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(query)}`,
        { headers: { 'Accept-Language': 'ru' }, signal },
    );
    if (!response.ok) throw new Error('Сервис поиска недоступен. Повторите попытку позже.');
    const results = await response.json();
    if (!Array.isArray(results)) throw new Error('Некорректный ответ сервиса поиска.');
    if (!results.length) return null;
    const lat = Number.parseFloat(results[0].lat);
    const lng = Number.parseFloat(results[0].lon);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
        throw new Error('Некорректные координаты в ответе сервиса поиска.');
    }
    return { lat, lng };
}
