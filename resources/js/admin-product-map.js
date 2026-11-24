import { geocodeLocation } from './product-geocoding.js';

const instances = new WeakMap();

export function initAdminProductMap(element, { latitude, longitude, zoom = 13 } = {}) {
    if (!element) return Promise.resolve();
    if (instances.has(element)) return instances.get(element);
    const form = element.closest('form');
    const field = selector => form.querySelector(selector);
    const country = field('[name="country_id"]');
    const city = field('[name="city_id"]');
    const address = field('#address');
    const errorBox = field('#addressError');
    const showError = message => {
        errorBox.textContent = message;
        errorBox.classList.toggle('hidden', !message);
    };
    const context = () => ({
        country: country.value ? country.selectedOptions[0]?.text.trim() ?? '' : '',
        city: city.value ? city.selectedOptions[0]?.text.trim() ?? '' : '',
        countryId: country.value, cityId: city.value, address: address.value,
    });
    let revision = 0;
    let controller;
    const cancel = () => { revision++; controller?.abort(); };
    const updateCoords = point => {
        field('#latitude').value = point.lat.toFixed(6);
        field('#longitude').value = point.lng.toFixed(6);
    };
    const coordinate = (value, fallback) => Number.isFinite(Number.parseFloat(value)) ? Number.parseFloat(value) : fallback;
    const ready = window.loadLeaflet().then(L => {
        const point = [coordinate(latitude, 47.0105), coordinate(longitude, 28.8638)];
        const map = L.map(element).setView(point, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        const marker = L.marker(point, { draggable: true }).addTo(map);
        // Opening the form never writes rounded/default coordinates to inputs.
        const reverse = async point => {
            cancel();
            const request = revision;
            const snapshot = JSON.stringify(context());
            updateCoords(point);
            showError('');
            try {
                const url = new URL('https://nominatim.openstreetmap.org/reverse');
                url.search = new URLSearchParams({ format: 'json', lat: point.lat, lon: point.lng, zoom: 18, addressdetails: 1, 'accept-language': 'ru' });
                const response = await fetch(url);
                if (!response.ok) throw new Error();
                const data = await response.json();
                if (request !== revision || snapshot !== JSON.stringify(context())) return;
                if (data.address) {
                    const a = data.address;
                    address.value = [a.road, a.house_number, a.city || a.town || a.village, a.country].filter(Boolean).join(', ');
                }
            } catch {
                if (request === revision) showError('Координаты обновлены, но адрес определить не удалось.');
            }
        };
        marker.on('dragend', event => { void reverse(event.target.getLatLng()); });
        map.on('click', event => { marker.setLatLng(event.latlng); void reverse(event.latlng); });
        return { map, marker };
    }).catch(error => {
        showError('Не удалось загрузить карту. Перезагрузите страницу.');
        throw error;
    });
    instances.set(element, ready);
    const search = async cityOnly => {
        cancel();
        // User-selected city starts a new location; the previous street address
        // must not be reused, even if this lookup fails.
        if (cityOnly) address.value = '';
        const request = revision;
        const selected = context();
        const snapshot = JSON.stringify(selected);
        showError('');
        if (cityOnly && (!selected.city || !selected.country)) {
            showError('Выберите страну и город.');
            return;
        }
        try {
            const { map, marker } = await ready;
            if (request !== revision || snapshot !== JSON.stringify(context())) return;
            controller = new AbortController();
            const query = cityOnly
                ? `${selected.city}, ${selected.country}`
                : selected.address.trim();
            if (!query) throw new Error('Введите адрес для поиска');
            const point = await geocodeLocation(query, { signal: controller.signal });
            if (!point) throw new Error('Адрес не найден. Уточните запрос или установите маркер вручную.');
            if (request !== revision || snapshot !== JSON.stringify(context())) return;
            map.setView(point, cityOnly ? 13 : 14);
            marker.setLatLng(point);
            updateCoords(point);
            if (cityOnly) address.value = `${selected.city}, ${selected.country}`;
        } catch (error) {
            if (request === revision && snapshot === JSON.stringify(context()) && error.name !== 'AbortError') {
                showError(cityOnly
                    ? 'Не удалось определить центр выбранного города. Координаты остались прежними. Установите маркер вручную или повторите выбор города.'
                    : error.message || 'Не удалось определить местоположение.');
            }
        }
    };
    field('#searchAddress').addEventListener('click', () => { void search(false); });
    city.addEventListener('change', () => { void search(true); });
    country.addEventListener('change', cancel);
    address.addEventListener('input', cancel);
    return ready;
}
