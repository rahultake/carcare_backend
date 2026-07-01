<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::latest()->paginate(10);

        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable'
        ]);

        $data = $request->all();

        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('image')) {

            $uploadPath = public_path('uploads/brands');

            $image = $request->file('image');
            $imageName = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();

            $image->move($uploadPath, $imageName);

            $data['image'] = 'uploads/brands/'.$imageName;
        }

        Brand::create($data);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable'
        ]);

        $data = $request->all();

        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('image')) {

            if ($brand->image && file_exists(public_path($brand->image))) {
                unlink(public_path($brand->image));
            }

            $uploadPath = public_path('uploads/brands');

            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $image = $request->file('image');
            $imageName = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();

            $image->move($uploadPath, $imageName);

            $data['image'] = 'uploads/brands/'.$imageName;
        }

        $brand->update($data);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->image && file_exists(public_path($brand->image))) {
            unlink(public_path($brand->image));
        }

        $brand->delete();

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}