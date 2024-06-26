<?php

namespace App\Http\Controllers;

use Ramsey\Uuid\Uuid;
use App\Models\ShortUrl;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreUrlRequest;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    public function dashboard()
    {
        return view('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function index()
    {
        return view('home');
    }

    public function storeApi(StoreUrlRequest $request)
    {
        $shortUrl = ShortUrl::where(ShortUrl::URL, $request->url)->first();

        if (!is_null($shortUrl)) {
            return ['success' => sprintf("%s/%s", config('app.url'),  $shortUrl->uuid)];
        }

        $guid = Str::random(8);

        $shortUrl = ShortUrl::create([
            ShortUrl::URL => $request->url,
            ShortUrl::UUID => $guid
        ]);

        return ['success' => sprintf("%s/%s", config('app.url'),  $shortUrl->uuid)];
    }

    public function store(StoreUrlRequest $request)
    {
        $isValidCaptcha = Http::asForm()->post(config('app.captchaURl'), [
            'secret' => config('app.captchaSecret'),
            'response' => $request['g-recaptcha-response']
        ])->json();

        if (!$isValidCaptcha['success']) {
            abort(403);
        }

        $shortUrl = ShortUrl::where(ShortUrl::URL, $request->url)->first();

        if (!is_null($shortUrl)) {
            return redirect()->back()->withInput()->with('success', sprintf("%s/%s", config('app.url'),  $shortUrl->uuid));
        }

        $guid = Str::random(8);

        $shortUrl = ShortUrl::create([
            ShortUrl::URL => $request->url,
            ShortUrl::UUID => $guid
        ]);

        return redirect()->back()->withInput()->with('success', sprintf("%s/%s", config('app.url'),  $shortUrl->uuid));
    }

    public function serveUrl(string $uuid)
    {
        $shortUrl = ShortUrl::where('uuid', $uuid)->first();

        if (is_null($shortUrl)) {
            abort(404);
        }

        return redirect($shortUrl->url);
    }

    public function optimize()
    {
        Artisan::call('optimize:clear');

        return redirect()->back();
    }

    public function cacheClear()
    {
        Artisan::call('cache:clear');

        return "Cache cleared!";
    }
}
