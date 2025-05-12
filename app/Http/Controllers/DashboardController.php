<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Categories;


class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function products(Request $request)
    {
        $q = $request->get('q');  // Mendapatkan nilai pencarian 'q' dari query string

        // Menarik data produk berdasarkan pencarian
        $products = Product::when($q, function($query) use ($q) {
            return $query->where('name', 'like', "%$q%")
                         ->orWhere('slug', 'like', "%$q%");
        })->paginate(10);  // Sesuaikan jumlah produk yang ingin ditampilkan

        // Mengembalikan view dengan data produk dan nilai pencarian
        return view('dashboard.products.index', compact('products', 'q'));
    }

    public function createProduct()
    {
        // Ambil semua kategori untuk dropdown
        $categories = Categories::all();
        return view('dashboard.products.create', compact('categories'));
    }

    public function storeProduct(Request $request)
    {
        // Validasi data yang diterima
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'sku' => 'required|string|max:50|unique:products,sku',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'product_category_id' => 'required|exists:product_categories,id', // Pastikan kategori ada di database
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Proses penyimpanan data produk
        $product = new Product();
        $product->name = $validated['name'];
        $product->slug = $validated['slug'];
        $product->sku = $validated['sku'];
        $product->description = $validated['description'];
        $product->price = $validated['price'];
        $product->stock = $validated['stock'];
        $product->product_category_id = $validated['product_category_id'];

        // Jika ada gambar yang diunggah, simpan gambar tersebut
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images/products', 'public');
            $product->image_url = $imagePath;
        }

        $product->save(); // Simpan produk ke database

        // Berikan feedback sukses dan kembali ke halaman produk
        return redirect()->route('dashboard.products.index')->with('successMessage', 'Product created successfully!');
    }


    public function editProduct(string $id)
    {
        $product = Product::findOrFail($id);
        $categories = Categories::all();
        return view('dashboard.products.edit', compact('product', 'categories'));
    }

    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug,' . $product->id . '|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'product_category_id' => 'required|exists:product_categories,id',
        ]);

        $product->update($validated);

        return redirect()->route('dashboard.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    // Method untuk menghapus produk
    public function destroyProduct(Product $product)
    {
        // Menghapus produk dari database
        $product->delete();

        // Redirect ke halaman produk dengan pesan sukses
        return redirect()->route('dashboard.products.index')->with('success', 'Product deleted successfully!');
    }

}