<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        // Perbaikan: nama parameter form adalah "keyword" dan "category".
        // Versi awal membaca $request->search dan $request->kategori,
        // padahal Blade mengirim name="keyword" dan name="category",
        // sehingga kata kunci dan filter tidak pernah terbaca.
        $keyword = trim((string) $request->query('keyword', ''));
        $categoryId = $request->query('category');

        $books = Book::with('category')
            ->when($keyword !== '', function ($query) use ($keyword) {
                // Perbaikan: gunakan LIKE dengan wildcard '%' agar pencarian
                // bersifat parsial. Versi awal memakai where() biasa yang
                // hanya cocok pada nilai yang persis sama.
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                          ->orWhere('author', 'LIKE', "%{$keyword}%");
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->get();

        $categories = Category::orderBy('name')->get();

        // Perbaikan: variabel yang dikirim ke view diubah dari "hasilBuku"
        // menjadi "books" karena Blade index.blade.php memakai
        // @forelse ($books as $book). Nilai filter juga dikirim agar
        // input form bisa dipertahankan setelah submit.
        return view('books.index', [
            'books' => $books,
            'categories' => $categories,
            'keyword' => $keyword,
            'categoryId' => $categoryId,
        ]);
    }
}
