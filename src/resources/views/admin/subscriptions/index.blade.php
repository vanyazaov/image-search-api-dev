<!-- resources/views/admin/subscriptions/index.blade.php -->
<x-app-layout>  

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Управление подпиской</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">API Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            @if($user->api_key)
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ Str::limit($user->api_key, 20) }}
                                </code>
                            @else
                                <span class="text-red-500 text-sm">Not generated</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            {{ $user->requests_used }} / {{ $user->request_limit }}
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $percentage = $user->request_limit > 0 ? ($user->requests_used / $user->request_limit) * 100 : 0;
                                $color = $percentage > 80 ? 'bg-red-500' : ($percentage > 60 ? 'bg-yellow-500' : 'bg-green-500');
                            @endphp
                            <div class="h-2 rounded-full {{ $color }}" style="width: {{ min($percentage, 100) }}%"></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $user->is_active && $user->isValidSubscription() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $user->is_active && $user->isValidSubscription() ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $user->subscription_valid_until ? $user->subscription_valid_until->format('M d, Y') : 'Never' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.subscriptions.edit', $user) }}" 
                           class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        
                        <form action="{{ route('admin.subscriptions.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Are you sure you want to delete this subscription?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
</x-app-layout>
