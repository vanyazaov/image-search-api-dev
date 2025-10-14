<!-- resources/views/admin/subscriptions/edit.blade.php -->
<x-app-layout>

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Subscription</h1>
        <a href="{{ route('admin.subscriptions.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to List
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('api_key'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            <strong>New API Key Generated:</strong>
            <code class="block mt-2 p-2 bg-blue-50 rounded text-sm">{{ session('api_key') }}</code>
            <p class="text-sm mt-2 text-blue-600">
                ⚠️ Make sure to copy this key now. It won't be shown again!
            </p>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('admin.subscriptions.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- User Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">User Information</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Subscription Settings -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700">Subscription Settings</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Request Limit *</label>
                        <input type="number" name="request_limit" 
                               value="{{ old('request_limit', $user->request_limit) }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" 
                               min="1" max="1000000" required>
                        @error('request_limit')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Valid Until *</label>
                        <input type="datetime-local" name="subscription_valid_until" 
                               value="{{ old('subscription_valid_until', $user->subscription_valid_until ? $user->subscription_valid_until->format('Y-m-d\TH:i') : '') }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                        @error('subscription_valid_until')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Subscription is active
                        </label>
                    </div>
                </div>
            </div>

            <!-- API Key Management -->
            <div class="mt-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">API Key Management</h3>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">
                            @if($user->api_key)
                                Current API Key: 
                                <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                    {{ Str::limit($user->api_key, 30) }}
                                </code>
                            @else
                                No API key generated yet
                            @endif
                        </p>
                    </div>
                    
                    <div class="space-x-2">
                        <form action="{{ route('admin.subscriptions.generate-key', $user) }}" 
                              method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                    onclick="return confirm('Generate new API key? Old key will be invalidated!')">
                                Generate New Key
                            </button>
                        </form>

                        <form action="{{ route('admin.subscriptions.reset-usage', $user) }}" 
                              method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                                    onclick="return confirm('Reset usage counter to zero?')">
                                Reset Usage
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Usage -->
            <div class="mt-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Current Usage</h3>
                <p class="text-sm text-gray-600">
                    {{ $user->requests_used }} / {{ $user->request_limit }} requests used
                    ({{ number_format(($user->requests_used / max($user->request_limit, 1)) * 100, 1) }}%)
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    @php
                        $percentage = $user->request_limit > 0 ? ($user->requests_used / $user->request_limit) * 100 : 0;
                        $color = $percentage > 80 ? 'bg-red-500' : ($percentage > 60 ? 'bg-yellow-500' : 'bg-green-500');
                    @endphp
                    <div class="h-2 rounded-full {{ $color }}" style="width: {{ min($percentage, 100) }}%"></div>
                </div>
            </div>

            <!-- Subscription Status -->
            <div class="mt-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Subscription Status</h3>
                <p class="text-sm">
                    Status: 
                    <span class="font-semibold {{ $user->isValidSubscription() ? 'text-green-600' : 'text-red-600' }}">
                        {{ $user->isValidSubscription() ? 'VALID' : 'INVALID' }}
                    </span>
                </p>
                @if(!$user->isValidSubscription())
                    <p class="text-sm text-red-600 mt-1">
                        @if(!$user->is_active)
                            • Subscription is inactive<br>
                        @endif
                        @if($user->subscription_valid_until && $user->subscription_valid_until->isPast())
                            • Subscription expired on {{ $user->subscription_valid_until->format('M d, Y') }}<br>
                        @endif
                        @if($user->requests_used >= $user->request_limit)
                            • Request limit exceeded ({{ $user->requests_used }}/{{ $user->request_limit }})<br>
                        @endif
                    </p>
                @endif
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.subscriptions.index') }}" 
                   class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                    Update Subscription
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
