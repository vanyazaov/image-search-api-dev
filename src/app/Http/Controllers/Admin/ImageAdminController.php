<?php
// app/Http/Controllers/Admin/ImageAdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageAdminController extends Controller
{
    public function index()
    {
        $images = Image::latest()->paginate(20);
        return view('admin.images.index', compact('images'));
    }

    public function create()
    {
        return view('admin.images.create');
    }

    public function store(Request $request)
    {
         
        $validated = $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'meta' => 'nullable|array'
        ]);
        
        try {
            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();

            // Уникальное имя
            $tempFilename = uniqid() . '_' . $originalName;

            // Сохраняем во временное хранилище
            $path = Storage::putFileAs('temp', $file, $tempFilename);

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error uploading image: ' . $e->getMessage());
        }
        
        return redirect()->route('admin.images.index')
            ->with('success', 'Image uploaded and processing in background');
    }

    public function destroy(Image $image, ElasticSearchService $elasticSearch)
    {
        // Удаляем файлы
        Storage::delete([$image->file_path, "thumbnails/{$image->id}.jpg"]);
        
        // Удаляем из базы
        $image->delete();

        return redirect()->route('admin.images.index')
            ->with('success', 'Image deleted successfully');
    }
}
