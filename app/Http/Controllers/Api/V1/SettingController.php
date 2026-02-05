<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = ShopSetting::all()->pluck('value', 'key');
        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable|string',
        ]);

        $shopId = $request->user()->shop_id ?? app('current_shop_id');

        foreach ($validated['settings'] as $setting) {
            ShopSetting::updateOrCreate(
                ['shop_id' => $shopId, 'key' => $setting['key']],
                ['value' => $setting['value']],
            );
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function show(string $key): JsonResponse
    {
        $setting = ShopSetting::where('key', $key)->first();
        return response()->json(['key' => $key, 'value' => $setting?->value]);
    }
}
