<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboards.settings.company');
    }

    public function company()
    {
        return view('dashboards.settings.company', [
            'company' => CompanySetting::current(),
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'legal_name' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        CompanySetting::current()->update($data);

        return back()->with('status', 'company-updated');
    }

    public function preferences()
    {
        return view('dashboards.settings.preferences', [
            'user' => $this->currentUser(),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dark_mode' => 'nullable|boolean',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string|max:60',
            'notify_critical_stock' => 'nullable|boolean',
            'notify_low_stock' => 'nullable|boolean',
            'notify_daily_financial_report' => 'nullable|boolean',
            'notify_report_generated' => 'nullable|boolean',
        ]);

        foreach (['dark_mode', 'notify_critical_stock', 'notify_low_stock', 'notify_daily_financial_report', 'notify_report_generated'] as $toggle) {
            $data[$toggle] = $request->boolean($toggle);
        }

        $this->currentUser()->update($data);

        return back()->with('status', 'preferences-updated');
    }

    public function integrations()
    {
        return view('dashboards.settings.integrations');
    }

    private function currentUser()
    {
        return auth()->user();
    }
}
