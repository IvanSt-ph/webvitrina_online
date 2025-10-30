<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;

class HelpController extends Controller
{
    public function show(string $slug)
    {
        // ищем статью в конфиге
        $news = collect(config('seller_news'))
            ->firstWhere('url', "/seller/help/{$slug}");

        if (!$news) {
            abort(404);
        }

        // ищем Blade для конкретной статьи
        $viewPath = "seller.help.articles.{$slug}";

        // если он существует — показываем индивидуальную страницу
        if (view()->exists($viewPath)) {
            return view($viewPath, compact('news'));
        }

        // иначе — общий шаблон
        return view('seller.help.show', compact('news'));
    }

    public function index()
{
    $news = config('seller_news');
    return view('seller.help.index', compact('news'));
}

}
