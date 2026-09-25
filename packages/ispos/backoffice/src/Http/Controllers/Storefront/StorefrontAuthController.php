<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Models\Customer;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontAuthController extends Controller
{
    public function __construct(protected OnlineStoreSettingService $settings) {}

    public function showLogin(string $slug): Response|RedirectResponse
    {
        $setting = $this->publishedSetting($slug);
        if (Auth::guard('customer')->check()) {
            return redirect()->route('storefront.show', $slug);
        }

        return Inertia::render('Storefront/Auth/Login', $this->authPageProps($setting));
    }

    public function showRegister(string $slug): Response|RedirectResponse
    {
        $setting = $this->publishedSetting($slug);
        if (Auth::guard('customer')->check()) {
            return redirect()->route('storefront.show', $slug);
        }

        return Inertia::render('Storefront/Auth/Register', $this->authPageProps($setting));
    }

    public function login(Request $request, string $slug): RedirectResponse
    {
        $setting = $this->publishedSetting($slug);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $customer = Customer::query()
            ->where('company_id', $setting->store->company_id)
            ->where('email', $credentials['email'])
            ->where('status', 'active')
            ->whereNotNull('password')
            ->first();

        if (! $customer || ! Hash::check($credentials['password'], $customer->getAuthPassword())) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        Auth::guard('customer')->login($customer, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && str_contains($intended, '/store/'.$slug)) {
            return redirect()->to($intended);
        }

        return redirect()->route('storefront.show', $slug);
    }

    public function register(Request $request, string $slug): RedirectResponse
    {
        $setting = $this->publishedSetting($slug);
        $store = $setting->store;

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $exists = Customer::query()
            ->where('company_id', $store->company_id)
            ->where('email', $data['email'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['email' => 'An account with this email already exists. Please sign in.'])->onlyInput('email');
        }

        $customer = Customer::create([
            'company_id' => $store->company_id,
            'customer_code' => 'WEB-'.Str::upper(Str::random(8)),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'mobile' => $data['phone'],
            'password' => $data['password'],
            'status' => 'active',
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && str_contains($intended, '/store/'.$slug)) {
            return redirect()->to($intended)->with('success', 'Welcome! Your account is ready.');
        }

        return redirect()->route('storefront.show', $slug)
            ->with('success', 'Welcome! Your account is ready.');
    }

    public function logout(Request $request, string $slug): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.show', $slug);
    }

    protected function publishedSetting(string $slug)
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        return $setting;
    }

    /** @return array<string, mixed> */
    protected function authPageProps($setting): array
    {
        $store = $setting->store;

        return [
            'setting' => [
                'slug' => $setting->slug,
                'storefront_name' => $setting->storefront_name,
                'primary_color' => $setting->primary_color ?: '#0f766e',
                'accent_color' => $setting->accent_color ?: '#f59e0b',
                'logo_url' => $store->logo_url ?: $setting->logo_url,
            ],
            'store' => [
                'store_name' => $store->store_name,
            ],
        ];
    }
}
