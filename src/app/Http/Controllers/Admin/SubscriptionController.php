<?php
// app/Http/Controllers/Admin/SubscriptionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'buyer')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

        return view('admin.subscriptions.index', compact('users'));
    }

    public function edit(User $subscription)
    {
        $user = $subscription;
        // Проверяем что пользователь является buyer
        if ($user->role !== 'buyer') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Здесь можно управлять только аккаунтами покупателей.');
        }

        return view('admin.subscriptions.edit', compact('user'));
    }

    public function update(Request $request, User $subscription)
    {
        $user = $subscription;
        // Проверяем что пользователь является buyer
        if ($user->role !== 'buyer') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Здесь можно управлять только аккаунтами покупателей.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'request_limit' => 'required|integer|min:1|max:1000000',
            'subscription_valid_until' => 'required|date|after:today',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'request_limit' => $validated['request_limit'],
            'subscription_valid_until' => $validated['subscription_valid_until'],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Подписка успешно обновлена');
    }

    public function generateApiKey(User $user)
    {
        if ($user->role !== 'buyer') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Только учетные записи покупателей могут генерировать ключи API');
        }

        $apiKey = Str::random(64);

        $user->update(['api_key' => $apiKey]);

        return redirect()->route('admin.subscriptions.edit', $user)
            ->with('success', 'Новый ключ API успешно сгенерирован')
            ->with('api_key', $apiKey); // Передаем ключ для показа
    }

    public function resetUsage(User $user)
    {
        if ($user->role !== 'buyer') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Только учетные записи покупателей могут сбросить использование');
        }

        $user->update(['requests_used' => 0]);

        return redirect()->route('admin.subscriptions.edit', $user)
            ->with('success', 'Счетчик использования сброшен на ноль');
    }

    public function destroy(User $subscription)
    {
        $user = $subscription;
        if ($user->role !== 'buyer') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Удалить можно только учетные записи покупателей.');
        }

        $user->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Подписка успешно удалена');
    }
}
