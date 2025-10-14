<!-- resources/views/admin/images/index.blade.php -->
<x-app-layout>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Управление изображениями</h1>
    </div>

    <!-- Сообщения об успехе/ошибке -->
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
    
    <!-- Кнопка добавления с Alpine -->
    <div x-data="{ showUpload: {{ $errors->any() ? 'true' : 'false' }} }" class="mb-6">
        <button @click="showUpload = true" class="bg-blue-500 text-white px-4 py-2 rounded">
            + Добавить изображение
        </button>
        
        <!-- Форма загрузки -->
        <div x-show="showUpload" class="mt-4 p-4 border rounded" style="display: none;">
            <form action="{{ route('admin.images.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Поле изображения -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Изображение *</label>
                    <input type="file" name="image" required 
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('image')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Поле названия -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Название *</label>
                    <input type="text" name="title" placeholder="Название изображения" required 
                           value="{{ old('title') }}"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('title')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Поле категории -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Категория *</label>
                    <input type="text" name="category" placeholder="Категория" required 
                           value="{{ old('category') }}"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('category')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Поле бренда -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Бренд *</label>
                    <input type="text" name="brand" placeholder="Бренд" required 
                           value="{{ old('brand') }}"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('brand')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Загрузить</button>
                    <button type="button" @click="showUpload = false" class="bg-gray-500 text-white px-4 py-2 rounded">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Список изображений -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($images as $image)
        <div class="border rounded-lg p-4">
            <img src="{{ route('api.images.show', [$image->id, 'w' => 200, 'h' => 200, 'api_key' =>  auth()->user()->api_key]) }}" 
                 alt="{{ $image->title }}" class="w-full h-32 object-cover mb-2">
            <h3 class="font-semibold">{{ $image->title }}</h3>
            <p class="text-sm text-gray-600">{{ $image->category }} • {{ $image->brand }}</p>
            
            <!-- Кнопка удаления -->
            <form action="{{ route('admin.images.destroy', $image) }}" method="POST" class="mt-2">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-500 text-sm">Удалить</button>
            </form>
        </div>
        @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-6">
        {{ $images->links() }}
    </div>
</div>
</x-app-layout>
