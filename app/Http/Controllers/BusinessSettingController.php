<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessSettingController extends Controller
{
    public function edit()
    {
        return view('business-settings.edit', [
            'businessSetting' => $this->getBusinessSetting(),
        ]);
    }

    public function update(Request $request)
    {
        $businessSetting = $this->getBusinessSetting();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'timezone'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && $businessSetting->logo_path) {
            Storage::disk('public')->delete($businessSetting->logo_path);
            $validated['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($businessSetting->logo_path) {
                Storage::disk('public')->delete($businessSetting->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('business-logos', 'public');
        }

        unset($validated['logo'], $validated['remove_logo']);

        $businessSetting->update($validated);

        return redirect()
            ->route('business-settings.edit')
            ->with('status', 'Business information updated successfully.');
    }
}
