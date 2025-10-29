<x-seller-layout title="Аналитика продавца">
<div x-data="sellerAnalytics()" 
     x-on:analytics:go.window="initialized || (initialized = true, $nextTick(() => init()))"
     class="bg-gray-50">

    <main class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8">

<!-- Заголовок + фильтры -->
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
  <div>
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
      <i class="ri-bar-chart-box-line text-indigo-600"></i>
      Аналитика продавца
    </h1>
    <p class="text-gray-500">Эффективность и вовлечённость ваших товаров</p>
  </div>

 <!-- ✅ фильтры -->
  <div class="flex items-center gap-3 filters-mobile">
    <select x-model="period" @change="reloadData"
            class="h-10 border-gray-300 rounded-lg text-sm px-3">
      <option value="7">7 дней</option>
      <option value="30">30 дней</option>
      <option value="90">90 дней</option>
    </select>

    <select x-model="productId" @change="reloadData"
            class="h-10 border-gray-300 rounded-lg text-sm px-3 min-w-[220px]">
      <option value="all">Все товары</option>
      @foreach($products as $product)
        <option value="{{ $product->id }}">{{ Str::limit($product->title, 60) }}</option>
      @endforeach
    </select>

    <button @click="exportCSV"
            class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
      <i class="ri-download-2-line"></i> Экспорт
    </button>
  </div>
</div>

<!-- Метрики -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8 metrics-grid">
  <template x-for="m in metrics" :key="m.key">
    <button type="button"
            @click="setActive(m.key)"
            class="metric-card group text-left w-full bg-white rounded-2xl border border-gray-100 hover:border-indigo-200 hover:shadow-md transition p-5"
            :class="active === m.key ? 'ring-2 ring-indigo-400' : ''"
            @mouseenter="showTooltip(m.key)" 
            @mouseleave="hideTooltip()"
            >
            
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="m.bg + ' ' + m.text">
            <i :class="m.icon + ' text-xl'"></i>
          </div>
          <div>
            <div class="text-sm text-gray-500" x-text="m.label"></div>
            <div class="text-[28px] leading-7 font-bold text-gray-900 mt-1" x-text="format(m.value)"></div>
          </div>
        </div>
        <span class="text-xs font-semibold flex items-center gap-1 mt-1"
              :class="m.growth >= 0 ? 'text-emerald-600' : 'text-rose-600'">
          <i :class="m.growth >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
          <span x-text="Math.abs(m.growth)+'%'"></span>
        </span>
      </div>
      <div class="mt-4">
        <canvas :id="'spark-'+m.key" height="40"></canvas>
      </div>
    </button>
  </template>
</div>


      <!-- График + Топ-5 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- График -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <i class="ri-line-chart-line text-indigo-500"></i>
              <span x-text="chartTitle"></span>
            </h2>
            <div class="text-sm text-gray-500">
              Период: <span class="font-medium" x-text="period + ' д.'"></span>
            </div>
          </div>
          <div class="h-[340px]">
            <canvas id="mainChart"></canvas>
          </div>
          <!-- Динамика интереса -->
          <div class="mt-3 text-sm text-gray-600 text-center italic" id="interest-trend">Анализируем данные...</div>
        </div>

        <!-- Топ-5 -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <i class="ri-trophy-line text-yellow-500"></i>
              Топ-5 товаров
            </h2>
            <select id="topMetric" class="h-9 border-gray-300 rounded-lg text-sm px-2">
              <option value="score">Общий индекс</option>
              <option value="views">Просмотры</option>
              <option value="favs">Избранное</option>
              <option value="carts">Корзина</option>
            </select>
          </div>
          <div class="h-[340px]">
            <canvas id="topProductsChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Круговая диаграмма по категориям -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
          <i class="ri-pie-chart-2-line text-indigo-500"></i>
          Распределение интереса по категориям
        </h2>
        <div class="h-[320px]">
          <canvas id="categoryChart"></canvas>
        </div>
      </div>

      

      <!-- Советы системы -->
      <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl border border-indigo-100 p-5 flex gap-4 items-start">
        <div class="text-indigo-600 text-2xl">💡</div>
        <div>
          <div class="font-semibold text-gray-800 mb-1">Совет дня</div>
          <div class="text-gray-600 text-sm" id="advice-text"></div>
        </div>
      </div>
<!-- Tooltip -->
<div x-show="tooltip.visible"
     x-transition.opacity
     class="fixed z-50 bg-gray-900 text-white text-sm rounded-lg px-3 py-2 shadow-lg pointer-events-none"
     style="transform: translate(-50%, -120%); white-space: nowrap;"
     x-text="tooltip.text"
     x-cloak></div>

    </main>
  </div>

  @include('layouts.mobile-bottom-seller-nav')

  <!-- libs -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>


    [x-show="tooltip.visible"] {
  transition: opacity 0.15s ease, transform 0.15s ease;
  opacity: 0;
  transform: translate(-50%, -130%) scale(0.98);
}
[x-show="tooltip.visible"][x-cloak="false"],
[x-show="tooltip.visible"]:not([x-cloak]) {
  opacity: 1 !important;
  transform: translate(-50%, -130%) scale(1) !important;
}


    /* анимация появления */
    @keyframes fadeIn { from {opacity:0; transform:scale(.98)} to {opacity:1; transform:scale(1)} }
    .metric-card{min-height:124px}
    @media (min-width:1024px){.metric-card{min-height:132px}}

    /* простая анимация модалок */
    .modal-fade { animation: fadeIn .2s ease-out }
    .scroll-shadow { mask-image: linear-gradient(to bottom, black 85%, transparent) }



@media (max-width: 480px) {
  canvas {
    max-width: 100% !important;
  }

    /* Перестроим фильтры в столбец */
    .filters-mobile {
      flex-direction: column !important;
      align-items: stretch !important;
      gap: 0.5rem !important;
    }

    .filters-mobile select,
    .filters-mobile button {
      width: 100% !important;
    }

    /* Карточки метрик – по 2 в ряд */
    .metrics-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    /* Последняя одиночная карточка растягивается */
    .metrics-grid > :last-child {
      grid-column: span 2 / span 2;
    }

    /* Текст заголовков меньше */
    h1.text-3xl {
      font-size: 1.5rem !important;
    }

    .metric-card {
      padding: 1rem !important;
      min-height: 110px !important;
    }
  }

  canvas {
  opacity: 0;
  transition: opacity 0.4s ease;
}
canvas.loaded {
  opacity: 1;
}

  </style>

  <script>
  function sellerAnalytics(){
    const stats       = @json($stats);        // [{date, views, favs, carts, total_products?}]
    const topProducts = @json($topProducts);  // [{id,title,image,views,favs,carts,score}]
    const categories  = @json($categories ?? []); // [{name, count}]

    return {
      tooltip: { visible: false, text: '', x: 0, y: 0 },
      initialized: false,


showTooltip(key) {
  const tips = {
    views: 'Здесь указано, сколько раз ваши товары просматривали 👁',
    favorites: 'Показывает, сколько раз ваши товары добавляли в избранное 💗',
    carts: 'Сколько раз ваши товары добавляли в корзину 🛒',
    total: 'Всего активных товаров в вашем магазине 🏬',
    engagement: 'Процент вовлечённости — отношение лайков и корзин к просмотрам 📊',
  };
  this.tooltip.text = tips[key] || '';
  this.tooltip.visible = true;
},
hideTooltip() {
  this.tooltip.visible = false;
},





      // state
      period: 7,
      productId: 'all',
      active: 'views',
      chartTitle: 'Активность товаров',

      // charts
      chart: null,
      sparkCharts: {},

      // cards
      metrics: [
        { key:'views',       label:'Просмотры',       value: {{ (int)($summary['views'] ?? 0) }},     growth:12, icon:'ri-eye-line',            bg:'bg-indigo-100',  text:'text-indigo-600' },
        { key:'favorites',   label:'Избранное',       value: {{ (int)($summary['favorites'] ?? 0) }}, growth:3,  icon:'ri-heart-line',          bg:'bg-pink-100',    text:'text-pink-600' },
        { key:'carts',       label:'Корзина',         value: {{ (int)($summary['cart_adds'] ?? 0) }}, growth:7,  icon:'ri-shopping-cart-line',  bg:'bg-emerald-100', text:'text-emerald-600' },
        { key:'total',       label:'Всего товаров',   value: {{ (int)($summary['total'] ?? 0) }},     growth:0,  icon:'ri-store-2-line',        bg:'bg-amber-100',   text:'text-amber-600' },
        { key:'engagement',  label:'Вовлечённость %', value: {{ (float)($summary['engagement'] ?? 0) }}, growth:12, icon:'ri-pulse-line',  bg:'bg-purple-100',   text:'text-purple-600' },
      ],

      // helpers
      format(n){ try { return new Intl.NumberFormat('ru-RU').format(+n || 0) } catch { return n } },

      setActive(k){
        this.active = k;
        const m = this.metrics.find(x => x.key === k);
        this.chartTitle = m ? `Динамика: ${m.label.toLowerCase()}` : 'Активность товаров';
        this.renderMain();
      },

      reloadData(){
        // Заглушка под будущее API-фильтрацию по period/productId
        this.renderMain();
        this.initSparks();
      },

      exportCSV(){
        window.location.href = `/seller/analytics/export?days=${this.period}`;
      },

      // === Спарклайны (мини-линии) ===
      initSparks(){
        const byKey = {
          views:       stats.map(s => +s.views   || 0),
          favorites:   stats.map(s => +s.favs    || 0),
          carts:       stats.map(s => +s.carts   || 0),
          total:       stats.map(s => +s.total_products || 0),
          engagement:  stats.map(s => ( ((+s.favs || 0) + (+s.carts || 0)) / Math.max(+s.views || 0, 1) * 100 ).toFixed(1)),
        };

        Object.keys(byKey).forEach(key => {
          const el = document.getElementById('spark-'+key);
          if (!el) return;

          // разрушение и пересоздание (если ресайз/переключение)
          this.sparkCharts[key]?.destroy?.();

          this.sparkCharts[key] = new Chart(el, {
            type: 'line',
            data: {
              labels: stats.map(s => s.date),
              datasets: [{
                data: byKey[key],
                borderWidth: 2,
                pointRadius: 0,
                tension: .35,
                borderColor: '#6366F1',
                fill: false
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display:false }, tooltip: { enabled:false } },
              scales: { x: { display:false }, y: { display:false } },
              elements: { point: { radius:0 } }
            }
          });
        });
      },

      // === Основной график с кликабельными точками ===
      renderMain(){
        const labels = stats.map(s => s.date);

        // Данные по активной метрике
        const dataSeries = (() => {
          if (this.active === 'engagement') {
            return stats.map(s => ( ((+s.favs || 0) + (+s.carts || 0)) / Math.max(+s.views || 0, 1) * 100 ).toFixed(1));
          }
          if (this.active === 'total') {
            return stats.map(s => +s.total_products || 0);
          }
          const map = { views:'views', favorites:'favs', carts:'carts' };
          const key = map[this.active] || 'views';
          return stats.map(s => +s[key] || 0);
        })();

        // Рисуем
        const ctx = document.getElementById('mainChart').getContext('2d');
        this.chart?.destroy?.();
        this.chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Активность',
              data: dataSeries,
              fill: true,
              borderWidth: 2,
              tension: .35,
              backgroundColor: 'rgba(99,102,241,0.15)',
              borderColor: '#6366F1',
              pointRadius: 4,
              pointHoverRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            events: ['click', 'mousemove'],
            plugins: {
              legend: { display:false },
              tooltip: {
                backgroundColor: 'rgba(99,102,241,0.9)',
                displayColors: false,
                padding: 10,
                titleFont: { weight: 'bold' },
                callbacks: {
                  title: (ctx) => `📅 ${ctx[0].label}`,
                  label: (ctx) => {
                    const v = ctx.formattedValue;
                    if (this.active === 'views')      return `👁 Просмотров: ${v}`;
                    if (this.active === 'favorites')  return `💗 В избранное: ${v}`;
                    if (this.active === 'carts')      return `🛒 В корзину: ${v}`;
                    if (this.active === 'total')      return `Кол-во ваших товаров: ${v}`;
                    return `Вовлечённость: ${v}%`;
                  },
                },
              },
            },
            interaction: { mode:'nearest', intersect:false },
            scales: {
              x: { grid: { color:'#F3F4F6' } },
              y: { grid: { color:'#F3F4F6' }, beginAtZero:true, ticks:{ precision:0 } }
            },
            onClick: (evt, activeEls, chart) => {
              const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect:false }, true);
              if (!points.length) return;
              const index = points[0].index;
              const date  = chart.data.labels[index];

              if (this.active === 'total') {
                // Показать товары, добавленные в этот день
                this.openProductsOnDateModal(date);
              } else {
                // Показать разбивку по товарам за день по текущей метрике
                this.openMetricBreakdownModal(date);
              }
            }
          }
        });

        // Текст «Динамика интереса»
        const num = dataSeries.map(v => parseFloat(v) || 0);
        const take = Math.min(3, num.length);
        const currentAvg = num.slice(-take).reduce((a,b)=>a+b,0) / Math.max(take,1);
        const prevAvg    = num.slice(0, take).reduce((a,b)=>a+b,0) / Math.max(take,1);
        const diff       = ((currentAvg - prevAvg) / Math.max(prevAvg, 1) * 100).toFixed(1);
        const el         = document.getElementById('interest-trend');

        if (this.active === 'engagement' || this.active === 'views' || this.active === 'favorites' || this.active === 'carts') {
          el.textContent = diff > 0
            ? `Интерес к вашим товарам вырос на +${diff}% за последние дни 🚀`
            : `Интерес немного снизился (${diff}%) — обновите фото/описания для роста 📸`;
        } else {
          el.textContent = 'Показано количество активных товаров по датам.';
        }
      },

      // === Модалка: товары, добавленные на дату (для метрики total) ===
      openProductsOnDateModal(date){
        fetch(`/seller/analytics/products-on/${date}`)
          .then(r => r.json())
          .then(products => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 modal-fade';

            if (!products.length) {
              modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-[92%] max-w-md p-6 relative text-center">
                  <button class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                  <h3 class="text-lg font-bold mb-4 text-indigo-600 flex items-center justify-center gap-2">
                    <i class="ri-store-2-line"></i> ${date}
                  </h3>
                  <p class="text-gray-600 mb-4">В этот день не было добавлено новых товаров.</p>
                  <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Закрыть</button>
                </div>`;
              modal.querySelectorAll('button').forEach(b => b.onclick = () => modal.remove());
              modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
              document.body.appendChild(modal);
              return;
            }

            const rows = products.map(p => `
              <tr class="hover:bg-gray-50">
                <td class="p-2 w-12">
                  <img src="${p.image}" class="w-10 h-10 rounded object-cover shadow-sm" alt="">
                </td>
                <td class="p-2">
                  <div class="font-medium text-gray-800">${p.title}</div>
                  <div class="text-xs text-gray-400">${p.id ? 'ID: '+p.id : ''}</div>
                </td>
                <td class="p-2 text-sm text-gray-500">${new Date(p.created_at).toLocaleDateString('ru-RU')}</td>
                <td class="p-2 text-right">
                  <a href="/p/${p.id}" class="text-indigo-600 text-sm hover:underline">Открыть</a>
                </td>
              </tr>
            `).join('');

            modal.innerHTML = `
              <div class="bg-white rounded-2xl shadow-2xl w-[95%] max-w-2xl p-6 relative">
                <button class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <h3 class="text-lg font-bold mb-4 text-indigo-600 flex items-center gap-2">
                  <i class="ri-store-2-line"></i> ${date} • Новые товары (${products.length})
                </h3>
                <div class="max-h-96 overflow-y-auto rounded-lg border border-gray-100 scroll-shadow">
                  <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                      <tr>
                        <th class="p-2 w-12"></th>
                        <th class="p-2">Название</th>
                        <th class="p-2">Дата добавления</th>
                        <th class="p-2"></th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                      ${rows}
                    </tbody>
                  </table>
                </div>
                <div class="mt-5 text-right">
                  <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Закрыть</button>
                </div>
              </div>`;
            modal.querySelectorAll('button').forEach(b => b.onclick = () => modal.remove());
            modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
            document.body.appendChild(modal);
          });
      },

      // === Модалка: разбивка по товарам за день (views/favorites/carts) ===
      openMetricBreakdownModal(date){
        fetch(`/seller/analytics/day/${date}`)
          .then(r => r.json())
          .then(rows => {
            if (!rows.length) { alert(`Нет данных за ${date}`); return; }

            const metricKey  = this.active === 'favorites' ? 'favorites'
                                : this.active === 'carts' ? 'carts'
                                : 'views';
            const metricName = this.active === 'favorites' ? 'В избранное'
                                : this.active === 'carts' ? 'В корзину'
                                : 'Просмотры';
            const metricIcon = this.active === 'favorites' ? '💗'
                                : this.active === 'carts' ? '🛒'
                                : '👁';

            const filtered = rows
              .filter(r => (+r[metricKey] || 0) > 0)
              .sort((a,b) => (+b[metricKey] || 0) - (+a[metricKey] || 0));

            const list = (filtered.length ? filtered : rows).map(d => `
              <div class="flex justify-between items-center py-2">
                <div class="flex flex-col">
                  <span class="font-medium text-gray-800">${d.title}</span>
                  <span class="text-xs text-gray-400">${metricIcon} ${d[metricKey] ?? 0}</span>
                </div>
                ${d.id ? `<a href="/p/${d.id}" class="text-indigo-600 text-sm hover:underline">Открыть</a>` : ''}
              </div>
            `).join('');

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 modal-fade';
            modal.innerHTML = `
              <div class="bg-white rounded-2xl shadow-2xl w-[92%] max-w-lg p-6 relative">
                <button class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <h3 class="text-lg font-bold mb-4 text-indigo-600 flex items-center gap-2">
                  <i class="ri-calendar-line"></i> ${date} • ${metricIcon} ${metricName}
                </h3>
                <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                  ${list}
                </div>
                <div class="mt-5 text-right">
                  <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Закрыть</button>
                </div>
              </div>`;
            modal.querySelectorAll('button').forEach(b => b.onclick = () => modal.remove());
            modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
            document.body.appendChild(modal);
          });
      },

      // === Топ-5 товаров (горизонтальные бары) ===
      initTop(){
        const el = document.getElementById('topProductsChart');
        if (!el) return;
        const ctx = el.getContext('2d');

        const render = (metric='score') => {
          if (!Array.isArray(topProducts) || !topProducts.length) return;

          const sorted = [...topProducts].sort((a,b)=>(+b[metric]||0) - (+a[metric]||0)).slice(0,5);
          const labels = sorted.map(p => p.title.length > 48 ? p.title.slice(0,48)+'…' : p.title);
          const values = sorted.map(p => +p[metric] || 0);

          window.topProductsChart?.destroy?.();
          window.topProductsChart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels,
              datasets: [{
                data: values,
                borderRadius: 10,
                backgroundColor: ['#6366F1','#EC4899','#10B981','#FBBF24','#9333EA']
              }]
            },
            options: {
              indexAxis: 'y',
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display:false },
                tooltip: {
                  callbacks: {
                    title: (tt) => labels[tt[0].dataIndex],
                    label: (tt) => {
                      const keyMap = { score:'Индекс', views:'Просмотры', favs:'Избранное', carts:'Корзина' };
                      return ` ${keyMap[metric] || metric}: ${tt.raw}`;
                    }
                  }
                }
              },
              scales: {
                x: { beginAtZero:true, grid:{ color:'#F3F4F6' } },
                y: { grid:{ display:false } }
              },
              onClick: (_, els) => {
                if (!els.length) return;
                const idx = els[0].index;
                const p   = sorted[idx];

                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black/40 flex items-center justify-center z-50 modal-fade';
                modal.innerHTML = `
                  <div class="bg-white rounded-2xl shadow-xl w-[90%] max-w-md p-6 relative">
                    <button class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                    <h3 class="text-lg font-bold mb-3 text-indigo-600">${p.title}</h3>
                    <div class="space-y-2 mb-4">
                      <div class="flex justify-between text-gray-700"><span>👁 Просмотры:</span><span>${p.views ?? 0}</span></div>
                      <div class="flex justify-between text-gray-700"><span>💗 В избранное:</span><span>${p.favs ?? 0}</span></div>
                      <div class="flex justify-between text-gray-700"><span>🛒 В корзину:</span><span>${p.carts ?? 0}</span></div>
                      <div class="flex justify-between text-gray-700 border-t pt-2 mt-2">
                        <span>📊 Индекс:</span><span>${p.score ?? 0}</span>
                      </div>
                    </div>
                    <div class="flex gap-3">
                      <a href="/p/${p.id}" class="flex-1 text-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Перейти к товару</a>
                      <button class="flex-1 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100">Закрыть</button>
                    </div>
                  </div>`;
                modal.querySelectorAll('button').forEach(b => b.onclick = () => modal.remove());
                modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
                document.body.appendChild(modal);
              }
            }
          });
        };

        render();
        document.getElementById('topMetric').addEventListener('change', e => render(e.target.value));
      },

      // === Диаграмма по категориям (doughnut) ===
      initCategories(){
        const el = document.getElementById('categoryChart');
        if (!el || !categories || !categories.length) return;

        const ctx = el.getContext('2d');
        const labels = categories.map(c => c.name);
        const data   = categories.map(c => +c.count || 0);

        new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels,
            datasets: [{
              data,
              backgroundColor: ['#6366F1','#10B981','#F59E0B','#EC4899','#8B5CF6','#0EA5E9','#F97316','#84CC16','#14B8A6']
            }]
          },
          options: {
            cutout: '65%',
            plugins: {
              legend: { position:'bottom' },
              tooltip: {
                callbacks: { label: (ctx) => `${ctx.label}: ${ctx.formattedValue} товаров` }
              }
            }
          }
        });
      },

      
      // === Совет дня (рандомная подсказка) ===
      initAdvice(){
        const advices = [
          "Товары с 3+ фото получают на 45% больше кликов 📸",
          "Постите утром (09:00–11:00) — пик активности пользователей ☀️",
          "Добавьте ключевые слова в заголовок и описание — лучшее место для SEO 🔍",
          "Актуализируйте цены раз в неделю — влияет на конверсию в корзину 💹",
          "Короткое и точное название повышает CTR карточки 🧠",
          "Добавьте характеристики (размер/материал) — фильтры начнут работать на вас 🧩",
        ];
        document.getElementById('advice-text').textContent =
          advices[Math.floor(Math.random()*advices.length)];
      },

      // === Инициализация ===
      init(){
        this.initSparks();
        this.renderMain();
        this.initTop();
        this.initCategories();
        this.initAdvice();
        document.querySelectorAll('canvas').forEach(c => c.classList.add('loaded'));

      }
      
    }
  }

  // Alpine init hook (оставляем для совместимости)
document.addEventListener('mousemove', e => {
  const el = document.querySelector('[x-show="tooltip.visible"]');
  if (!el) return;
   el.style.left = e.pageX + 'px';
  el.style.top = (e.pageY - -40) + 'px'; // ✨ добавлен сдвиг вверх
});


// === Ленивая загрузка графиков ===
document.addEventListener("DOMContentLoaded", () => {
  const analyticsSection = document.querySelector("main");
  if (!analyticsSection) return;

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        window.dispatchEvent(new CustomEvent('analytics:go'));
        obs.disconnect();
      }
    });
  }, { threshold: 0.1 });

  observer.observe(analyticsSection);
});


  </script>
</x-seller-layout>
