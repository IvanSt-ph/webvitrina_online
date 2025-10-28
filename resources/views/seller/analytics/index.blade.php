<x-seller-layout title="Аналитика продавца">
  <div x-data="sellerAnalytics()" class="bg-gray-50">
    <main class="w-full pt-4 pb-10 space-y-10 px-4 sm:px-6 lg:px-10 xl:px-16 bg-gray-50">

      <!-- Заголовок + фильтры -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ri-bar-chart-box-line text-indigo-600"></i>
            Аналитика продавца
          </h1>
          <p class="text-gray-500">Эффективность и вовлечённость ваших товаров</p>
        </div>

        <div class="flex items-center gap-3">
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
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <template x-for="m in metrics" :key="m.key">
          <button type="button"
                  @click="setActive(m.key)"
                  class="metric-card group text-left w-full bg-white rounded-2xl border border-gray-100 hover:border-indigo-200 hover:shadow-md transition p-5"
                  :class="active === m.key ? 'ring-2 ring-indigo-400' : ''">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                     :class="m.bg + ' ' + m.text">
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
    </main>
  </div>

  @include('layouts.mobile-bottom-seller-nav')

  <!-- libs -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    @keyframes fadeIn {
  from {opacity:0; transform:scale(0.95);}
  to {opacity:1; transform:scale(1);}
}


    .metric-card{min-height:124px}
    @media (min-width:1024px){.metric-card{min-height:132px}}
  </style>

  <script>
  function sellerAnalytics(){
    const stats = @json($stats);
    const topProducts = @json($topProducts);

    return {
      period: 7,
      productId: 'all',
      active: 'views',
      chartTitle: 'Активность товаров',
      chart: null,
      sparkCharts: {},

      metrics: [
        {key:'views', label:'Просмотры', value: {{ (int)($summary['views'] ?? 0) }}, growth:12, icon:'ri-eye-line', bg:'bg-indigo-100', text:'text-indigo-600'},
        {key:'favorites', label:'Избранное', value: {{ (int)($summary['favorites'] ?? 0) }}, growth:3, icon:'ri-heart-line', bg:'bg-pink-100', text:'text-pink-600'},
        {key:'carts', label:'Корзина', value: {{ (int)($summary['cart_adds'] ?? 0) }}, growth:7, icon:'ri-shopping-cart-line', bg:'bg-emerald-100', text:'text-emerald-600'},
        {key:'total', label:'Товаров', value: {{ (int)($summary['total'] ?? 0) }}, growth:0, icon:'ri-store-2-line', bg:'bg-amber-100', text:'text-amber-600'},
      ],

      format(n){ try{ return new Intl.NumberFormat('ru-RU').format(n) }catch{ return n } },

      setActive(k){
        this.active = k;
        const m = this.metrics.find(x => x.key===k);
        this.chartTitle = m ? `Динамика: ${m.label.toLowerCase()}` : 'Активность товаров';
        this.renderMain();
      },

      reloadData(){},
      exportCSV(){ window.location.href = `/seller/analytics/export?days=${this.period}` },

      initSparks(){
        const series = {
          views: stats.map(s=>s.views ?? 0),
          favorites: stats.map(s=>s.favs ?? 0),
          carts: stats.map(s=>s.carts ?? 0),
          total: stats.map(s=>(s.views||0)+(s.favs||0)+(s.carts||0)),
        };
        ['views','favorites','carts','total'].forEach(key=>{
          const el = document.getElementById('spark-'+key);
          if(!el) return;
          this.sparkCharts[key]?.destroy?.();
          this.sparkCharts[key] = new Chart(el, {
            type: 'line',
            data: { labels: stats.map(s=>s.date), datasets: [{ data: series[key], borderWidth: 2, pointRadius: 0, tension: .35 }] },
            options: {
              responsive:true, maintainAspectRatio:false,
              plugins:{legend:{display:false}, tooltip:{enabled:false}},
              elements:{line:{borderColor:'#6366F1'}, point:{radius:0}},
              scales:{x:{display:false}, y:{display:false}}
            }
          });
        });
      },

      renderMain(){
        const map = {views:'views', favorites:'favs', carts:'carts', total:null};
        const key = map[this.active];
        const data = key ? stats.map(s=>s[key]||0) : stats.map(s=>(s.views||0)+(s.favs||0)+(s.carts||0));
        const labels = stats.map(s=>s.date);

        const ctx = document.getElementById('mainChart').getContext('2d');
        this.chart?.destroy?.();
        this.chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Активность',
              data,
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
            responsive:true,
            maintainAspectRatio:false,
            plugins:{
              legend:{display:false},
              tooltip:{
                backgroundColor:'rgba(99,102,241,0.9)',
                displayColors:false,
                padding:10,
                titleFont:{weight:'bold'},
                callbacks:{
                  title:(ctx)=> `📅 ${ctx[0].label}`,
                  label:(ctx)=> {
                    const v = ctx.formattedValue;
                    return this.active==='views'
                      ? `👁 Просмотров: ${v}`
                      : this.active==='favorites'
                      ? `💗 В избранное: ${v}`
                      : this.active==='carts'
                      ? `🛒 В корзину: ${v}`
                      : `Активность: ${v}`;
                  }
                }
              }
            },
            interaction:{mode:'nearest',intersect:true},
            scales:{
              x:{grid:{color:'#F3F4F6'}},
              y:{grid:{color:'#F3F4F6'},beginAtZero:true,ticks:{precision:0}}
            },
            // 👇 Клик по точке — модальное окно
onClick:(e,els)=>{
  if(!els.length) return;
  const index = els[0].index;
  const date = labels[index];

  fetch(`/seller/analytics/day/${date}`)
    .then(r=>r.json())
    .then(data=>{
      if(!data.length){ alert(`Нет данных за ${date}`); return; }

      // HTML содержимое
      const rows = data.map(d=>`
        <div class='flex justify-between items-center py-2'>
          <div class='flex flex-col'>
            <span class='font-medium text-gray-800'>${d.title}</span>
            <span class='text-xs text-gray-400'>ID: ${d.id ?? '-'}</span>
          </div>
          <div class='text-sm text-gray-600 flex gap-3'>
            <span title='Просмотры'>👁 ${d.views}</span>
            <span title='Избранное'>💗 ${d.favorites}</span>
            <span title='Корзина'>🛒 ${d.carts}</span>
          </div>
        </div>
      `).join('');

      const modal = document.createElement('div');
      modal.className = 'fixed inset-0 bg-black/40 flex items-center justify-center z-50 backdrop-blur-sm animate-[fadeIn_0.25s_ease-out]';
      modal.innerHTML = `
        <div class='bg-white rounded-2xl shadow-2xl w-[92%] max-w-lg p-6 relative'>
          <button class='absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl'>&times;</button>
          <h3 class='text-xl font-semibold mb-4 text-indigo-600 flex items-center gap-2'>
            <i class="ri-calendar-line"></i>
            ${date}
          </h3>
          <div class='divide-y divide-gray-100 max-h-80 overflow-y-auto'>
            ${rows}
          </div>
          <div class='mt-4 text-right'>
            <button class='bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700'>Закрыть</button>
          </div>
        </div>
      `;
      modal.querySelectorAll('button').forEach(btn => btn.onclick = ()=> modal.remove());
      document.body.appendChild(modal);
    });
}

          }
        });
      },

      initTop(){
        const ctx = document.getElementById('topProductsChart').getContext('2d');
        const render = (metric='score')=>{
          if(!Array.isArray(topProducts) || topProducts.length===0){ return; }

          const sorted = [...topProducts]
            .sort((a,b)=> (b[metric]||0)-(a[metric]||0))
            .slice(0,5);

          const labels = sorted.map(p => p.title.length > 48 ? p.title.slice(0,48)+'…' : p.title);
          const values = sorted.map(p => p[metric] || 0);

          // ✅ Без ошибок
          window.topProductsChart?.destroy?.();

          window.topProductsChart = new Chart(ctx, {
            type:'bar',
            data:{
              labels,
              datasets:[{
                data: values,
                borderRadius:10,
                backgroundColor:['#6366F1','#EC4899','#10B981','#FBBF24','#9333EA']
              }]
            },
            options:{
              indexAxis:'y',
              responsive:true,
              maintainAspectRatio:false,
              plugins:{
                legend:{display:false},
                tooltip:{
                  callbacks:{
                    title:(tt)=> labels[tt[0].dataIndex],
                    label:(tt)=> {
                      return ` ${
                        metric==='score' ? 'Индекс' :
                        metric==='views' ? 'Просмотры' :
                        metric==='favs' ? 'Избранное' : 'Корзина'
                      }: ${tt.raw}`;
                    }
                  }
                }
              },
              scales:{
                x:{beginAtZero:true, grid:{color:'#F3F4F6'}},
                y:{grid:{display:false}}
              },
onClick(_, els){
  if(!els.length) return;
  const idx = els[0].index;
  const product = sorted[idx];

  // Формируем HTML модалки
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/40 flex items-center justify-center z-50';
  modal.innerHTML = `
    <div class='bg-white rounded-2xl shadow-xl w-[90%] max-w-md p-6 relative'>
      <button class='absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-xl'>&times;</button>
      <h3 class='text-lg font-bold mb-3 text-indigo-600'>${product.title}</h3>
      <div class='space-y-2 mb-4'>
        <div class='flex justify-between text-gray-700'><span>👁 Просмотры:</span><span>${product.views ?? 0}</span></div>
        <div class='flex justify-between text-gray-700'><span>💗 В избранное:</span><span>${product.favs ?? 0}</span></div>
        <div class='flex justify-between text-gray-700'><span>🛒 В корзину:</span><span>${product.carts ?? 0}</span></div>
        <div class='flex justify-between text-gray-700 border-t pt-2 mt-2'><span>📊 Индекс:</span><span>${product.score ?? 0}</span></div>
      </div>
      <div class='flex gap-3'>
        <a href='/p/${product.id}' class='flex-1 text-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700'>Перейти к товару</a>
        <button class='flex-1 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-100'>Закрыть</button>
      </div>
    </div>
  `;

  // Закрытие окна
  modal.querySelectorAll('button').forEach(btn => btn.onclick = () => modal.remove());
  document.body.appendChild(modal);
}

            }
          });
        };

        render();
        document.getElementById('topMetric').addEventListener('change', e=> render(e.target.value));
      },

      // 👇 Закрываем return и функцию
      init(){
        this.initSparks();
        this.renderMain();
        this.initTop();
      }
    } // ← конец return
  } // ← конец функции sellerAnalytics

  document.addEventListener('alpine:init', ()=>{});
  </script>
</x-seller-layout>
