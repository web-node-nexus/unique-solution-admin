<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    public function edit(): View
    {
        abort_unless(auth()->user()?->can('settings.view'), 403);

        $keys = [
            'shop_name',
            'shop_tagline',
            'shop_address',
            'shop_logo',
            'contact_number',
            'contact_email',
            'tax_percentage',
            'default_shipping_charge',
            'currency_symbol',
            'serviceable_pincodes',
            'delivery_eta_days',
            'whatsapp_number',
            'razorpay_key',
            'razorpay_secret',
            'payu_key',
            'payu_salt',
            'notification_order_confirmed',
            'notification_order_shipped',
            'notification_order_delivered',
            'fcm_project_id',
            'fcm_server_key',
            'fcm_credentials_path',
        ];

        $settings = Setting::getMany($keys);

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('shop_logo')) {
            $existing = Setting::get('shop_logo');
            if ($existing) {
                $this->imageService->delete($existing);
            }
            $data['shop_logo'] = $this->imageService->upload($request->file('shop_logo'), 'settings');
        }

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            Setting::set($key, $value);
        }

        activity_log('updated', 'settings', 'Updated shop settings');

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Settings saved successfully.');
    }
}
