<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TelegramSetting;
use App\Models\TenantSetting;
use App\Models\TenantUserEmail;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function clinic()
    {
        $tenantId = tenant('id');

        $centralSettings = TenantSetting::where('tenant_id', $tenantId)->pluck('value', 'key');

        return response()->json([
            'name' => tenant('name'),
            'phone' => tenant('phone'),
            'address' => tenant('address'),
            'logo' => tenant('logo'),
            'work_start' => $centralSettings['work_start'] ?? '08:00',
            'work_end' => $centralSettings['work_end'] ?? '18:00',
            'receipt_tagline' => $centralSettings['receipt_tagline'] ?? 'Sog\'lom bo\'ling!',
            'show_logo_on_receipt' => $centralSettings['show_logo_on_receipt'] ?? true,
            'printer_type' => $centralSettings['printer_type'] ?? 'network',
            'printer_ip' => $centralSettings['printer_ip'] ?? '',
            'printer_port' => $centralSettings['printer_port'] ?? 9100,
        ]);
    }

    public function updateClinic(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'work_start' => 'nullable|string',
            'work_end' => 'nullable|string',
            'receipt_tagline' => 'nullable|string',
            'show_logo_on_receipt' => 'nullable|boolean',
        ]);

        $tenantId = tenant('id');
        $tenant = Tenant::find($tenantId);

        if (isset($validated['name'])) $tenant->update(['name' => $validated['name']]);
        if (isset($validated['phone'])) $tenant->update(['phone' => $validated['phone']]);
        if (isset($validated['address'])) $tenant->update(['address' => $validated['address']]);

        foreach (['work_start', 'work_end', 'receipt_tagline', 'show_logo_on_receipt'] as $key) {
            if (isset($validated[$key])) {
                TenantSetting::updateOrCreate(
                    ['tenant_id' => $tenantId, 'key' => $key],
                    ['value' => $validated[$key]]
                );
            }
        }

        return response()->json(['message' => 'Updated']);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $tenantId = tenant('id');
        $tenant   = Tenant::find($tenantId);

        // Remove old logo
        if ($tenant->logo) {
            $oldPath = str_replace('/storage/', 'public/', $tenant->logo);
            Storage::delete($oldPath);
        }

        $path = $request->file('logo')->store("public/tenants/{$tenantId}");
        $url  = Storage::url($path);

        $tenant->update(['logo' => $url]);

        return response()->json(['logo' => $url]);
    }

    public function telegram()
    {
        $settings = TelegramSetting::first() ?? new TelegramSetting();
        return response()->json($settings);
    }

    public function updateTelegram(Request $request)
    {
        $validated = $request->validate([
            'bot_token' => 'nullable|string',
            'group_chat_id' => 'nullable|string',
            'notifications' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = TelegramSetting::firstOrNew([]);
        $settings->fill($validated)->save();

        return response()->json($settings);
    }

    public function testTelegram()
    {
        $settings = TelegramSetting::first();
        if (!$settings || !$settings->bot_token || !$settings->group_chat_id) {
            return response()->json(['success' => false, 'message' => 'Sozlamalar to\'liq emas'], 422);
        }

        $telegram = new TelegramService();
        $result = $telegram->testConnection($settings->bot_token, $settings->group_chat_id);

        return response()->json($result);
    }

    public function setWebhook()
    {
        $settings = TelegramSetting::first();
        if (!$settings || !$settings->bot_token) {
            return response()->json(['success' => false, 'message' => 'Bot token kiritilmagan'], 422);
        }

        $tenantId    = tenant('id');
        $webhookUrl  = rtrim(config('app.url'), '/') . "/api/webhook/telegram/{$tenantId}";

        $telegram = new TelegramService();
        $result   = $telegram->setWebhook($settings->bot_token, $webhookUrl);

        return response()->json(array_merge($result, ['webhook_url' => $webhookUrl]));
    }

    public function webhookUrl()
    {
        $tenantId = tenant('id');
        $url = rtrim(config('app.url'), '/') . "/api/webhook/telegram/{$tenantId}";
        return response()->json(['webhook_url' => $url]);
    }

    public function printer()
    {
        $tenantId = tenant('id');
        $settings = TenantSetting::where('tenant_id', $tenantId)
            ->whereIn('key', ['printer_type', 'printer_ip', 'printer_port', 'printer_path'])
            ->pluck('value', 'key');

        return response()->json($settings);
    }

    public function updatePrinter(Request $request)
    {
        $validated = $request->validate([
            'printer_type' => 'required|in:usb,network,disabled',
            'printer_ip'   => 'nullable|ip',
            'printer_port' => 'nullable|integer',
            'printer_path' => 'nullable|string',
        ]);

        $tenantId = tenant('id');
        foreach ($validated as $key => $value) {
            if ($value === null) continue;
            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Updated']);
    }

    public function testPrint(Request $request)
    {
        $request->validate([
            'printer_type' => 'required|in:usb,network',
            'printer_host' => 'nullable|ip',
            'printer_port' => 'nullable|integer',
            'printer_path' => 'nullable|string',
        ]);

        try {
            $connector = match ($request->printer_type) {
                'usb'   => new \Mike42\Escpos\PrintConnectors\FilePrintConnector($request->printer_path ?? '/dev/usb/lp0'),
                default => new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($request->printer_host, $request->printer_port ?? 9100),
            };
            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->text("EverMED CRM\n");
            $printer->text("================================\n");
            $printer->text("Test chek muvaffaqiyatli!\n");
            $printer->text("XP-80TS 80mm\n");
            $printer->text("================================\n");
            $printer->feed(3);
            $printer->cut();
            $printer->close();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Test print error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function users(Request $request)
    {
        $users = User::when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->latest()
            ->paginate(15);

        return response()->json($users);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,receptionist',
            'phone'    => 'nullable|string',
        ]);

        $user = User::create($validated);

        // Central email → tenant mapping for slug-less login
        TenantUserEmail::updateOrCreate([
            'tenant_id' => tenant('id'),
            'email'     => $validated['email'],
        ]);

        return response()->json($user, 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'role' => 'sometimes|in:admin,receptionist',
            'phone' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update(array_filter($validated, fn($v) => !is_null($v)));
        return response()->json($user);
    }

    public function destroyUser(User $user)
    {
        TenantUserEmail::where('tenant_id', tenant('id'))
            ->where('email', $user->email)
            ->delete();

        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
