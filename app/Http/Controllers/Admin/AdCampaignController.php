<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdSlot;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdCampaignController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,hidden,scheduled,expired'],
            'slot' => ['nullable', 'exists:ad_slots,id'],
            'type' => ['nullable', Rule::in(array_keys(AdCampaign::targetTypes()))],
            'category_id' => ['nullable', 'exists:categories,id'],
            'section' => ['nullable', 'in:overview,slots,campaigns,stats,settings'],
        ]);

        $section = in_array($request->query('section'), ['campaigns', 'stats'], true)
            ? $request->query('section')
            : 'overview';
        $totalImpressions = AdImpression::count();
        $totalClicks = AdClick::count();
        $summary = [
            'total' => AdCampaign::count(),
            'active' => AdCampaign::live()->count(),
            'hidden' => AdCampaign::where('is_active', false)->count(),
            'scheduled' => AdCampaign::where('is_active', true)->where('starts_at', '>', now())->count(),
            'expired' => AdCampaign::whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'ctr' => $totalImpressions > 0 ? round($totalClicks * 100 / $totalImpressions, 2) : 0,
        ];

        $campaigns = AdCampaign::query()
            ->with(['slot:id,key,name,placement', 'product:id,title,slug,image,status,user_id', 'product.seller:id,name', 'product.seller.shop:id,user_id,name,slug', 'shop:id,user_id,name,slug,banner', 'shop.user:id,name,avatar', 'category:id,name,slug'])
            ->withCount(['impressions', 'clicks'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('destination_url', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('shop', fn ($shop) => $shop->where('name', 'like', "%{$search}%"));

                    if (ctype_digit($search)) {
                        $subQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->when($request->filled('slot'), fn ($query) => $query->where('ad_slot_id', $request->integer('slot')))
            ->when($request->filled('type'), fn ($query) => $query->where('target_type', $request->query('type')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->query('status') === 'active', fn ($query) => $query->live())
            ->when($request->query('status') === 'hidden', fn ($query) => $query->where('is_active', false))
            ->when($request->query('status') === 'scheduled', fn ($query) => $query->where('is_active', true)->where('starts_at', '>', now()))
            ->when($request->query('status') === 'expired', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now()))
            ->orderByDesc('sort_order')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $slots = AdSlot::orderBy('placement')->orderBy('name')->get(['id', 'key', 'name', 'placement']);
        $slotGuide = AdSlot::publicGuide();
        $categories = Category::orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.ads.index', compact('campaigns', 'slots', 'summary', 'slotGuide', 'categories', 'section'));
    }

    public function create()
    {
        return view('admin.ads.form', $this->formData(new AdCampaign([
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'label' => 'Продвигается',
            'sort_order' => 100,
        ])));
    }

    public function searchProducts(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($data['q'] ?? ''));

        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

        $products = Product::query()
            ->active()
            ->with(['seller:id,name', 'seller.shop:id,user_id,name,slug'])
            ->where(function ($query) use ($like, $search) {
                $query->where('title', 'like', $like);

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            })
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'slug', 'image', 'status', 'user_id']);

        return response()->json([
            'results' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => '#' . $product->id . ' · ' . $product->title,
                'subtitle' => $product->seller?->shop?->name ?: $product->seller?->name ?: 'Без магазина',
                'image' => $product->image_thumb_url,
            ])->values(),
        ]);
    }

    public function searchShops(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($data['q'] ?? ''));

        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

        $shops = Shop::query()
            ->with('user:id,name,avatar')
            ->whereNotNull('slug')
            ->where(function ($query) use ($like, $search) {
                $query->where('name', 'like', $like);

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'user_id', 'name', 'slug', 'banner', 'city']);

        return response()->json([
            'results' => $shops->map(fn (Shop $shop) => [
                'id' => $shop->id,
                'title' => '#' . $shop->id . ' · ' . $shop->name,
                'subtitle' => $shop->city ?: 'Магазин продавца',
                'image' => $shop->card_image_url,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        AdCampaign::create($data);
        $this->forgetAdCache();

        return redirect()
            ->route('admin.ads.index')
            ->with('success', 'Кампания продвижения добавлена.');
    }

    public function edit(AdCampaign $ad)
    {
        return view('admin.ads.form', $this->formData($ad));
    }

    public function update(Request $request, AdCampaign $ad)
    {
        $data = $this->validatedData($request);
        $data['updated_by'] = $request->user()->id;

        $ad->update($data);
        $this->forgetAdCache();

        return redirect()
            ->route('admin.ads.index')
            ->with('success', 'Кампания продвижения обновлена.');
    }

    public function destroy(AdCampaign $ad)
    {
        $ad->delete();
        $this->forgetAdCache();

        return back()->with('success', 'Кампания продвижения удалена.');
    }

    private function formData(AdCampaign $campaign): array
    {
        return [
            'campaign' => $campaign,
            'slots' => AdSlot::orderBy('placement')->orderBy('name')->get(['id', 'key', 'name', 'placement']),
            'selectedProduct' => $campaign->product_id
                ? Product::query()
                    ->with(['seller:id,name', 'seller.shop:id,user_id,name,slug'])
                    ->find($campaign->product_id)
                : null,
            'selectedShop' => $campaign->shop_id
                ? Shop::find($campaign->shop_id)
                : null,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'targetTypes' => AdCampaign::targetTypes(),
            'slotGuide' => AdSlot::publicGuide(),
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'ad_slot_id' => ['required', 'exists:ad_slots,id'],
            'target_type' => ['required', Rule::in(array_keys(AdCampaign::targetTypes()))],
            'product_id' => ['nullable', 'exists:products,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'destination_url' => $this->linkRules(),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'max_impressions' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['label'] = $data['label'] ?: 'Продвигается';
        $data['sort_order'] = $data['sort_order'] ?? 100;
        $data['max_impressions'] = $data['max_impressions'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        if ($data['target_type'] === AdCampaign::TYPE_PRODUCT) {
            $request->validate(['product_id' => ['required', 'exists:products,id']]);
            $data['shop_id'] = null;
            $data['destination_url'] = null;
        } elseif ($data['target_type'] === AdCampaign::TYPE_SHOP) {
            $request->validate(['shop_id' => ['required', 'exists:shops,id']]);
            $data['product_id'] = null;
            $data['destination_url'] = null;
        } else {
            $request->validate(['destination_url' => $this->linkRules(required: true)]);
            $data['product_id'] = null;
            $data['shop_id'] = null;
        }

        return $data;
    }

    private function linkRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:500',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
                    return;
                }

                $scheme = parse_url($value, PHP_URL_SCHEME);

                if (in_array($scheme, ['http', 'https'], true) && filter_var($value, FILTER_VALIDATE_URL)) {
                    return;
                }

                $fail('Ссылка должна быть внутренним путём /... или URL с http/https.');
            },
        ];
    }

    private function forgetAdCache(): void
    {
        cache()->forget('ads.home');
        cache()->forget('ads.category.featured');
    }
}
