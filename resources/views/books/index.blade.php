@extends('layouts.app')

@section('title', 'Pencarian Buku')

@section('content')
    <h1 class="h3 mb-3">Pencarian dan Filter Buku</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            {{-- Perbaikan: form memakai method GET, jadi isian form perlu
                 dipertahankan lewat request(), bukan old(). old() hanya
                 berisi nilai input yang gagal validasi pada request POST,
                 sehingga pada form GET nilainya selalu kosong. --}}
            <form action="{{ url('/books') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Cari judul atau penulis..."
                        value="{{ request('keyword') }}"
                    >
                </div>

                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            {{-- Perbaikan: perbandingan diubah ke string agar
                                 konsisten saat id dikirim lewat query string. --}}
                            <option value="{{ $category->id }}"
                                {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="{{ url('/books') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if (request()->filled('keyword') || request()->filled('category'))
        <div class="alert alert-info">
            Menampilkan {{ $books->count() }} buku
            @if (request()->filled('keyword'))
                untuk kata kunci &ldquo;{{ request('keyword') }}&rdquo;
            @endif
            @if (request()->filled('category'))
                pada kategori {{ $categories->firstWhere('id', (int) request('category'))?->name ?? 'tidak dikenal' }}
            @endif.
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr><th>Judul</th><th>Penulis</th><th>Kategori</th></tr>
                </thead>
                <tbody>
                    {{-- Perbaikan: Controller kini mengirim variabel "books"
                         (sebelumnya "hasilBuku"), jadi nama di sini harus $books. --}}
                    @forelse ($books as $book)
                        <tr>
                            <td>{{ $book->title }}</td>
                            <td>{{ $book->author }}</td>
                            <td><span class="badge bg-secondary">{{ $book->category?->name ?? 'Tanpa kategori' }}</span></td>
                        </tr>
                    @empty
                        {{-- Pesan ketika hasil pencarian kosong. --}}
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                @if (request()->filled('keyword') || request()->filled('category'))
                                    Buku tidak ditemukan untuk filter yang dipilih.
                                @else
                                    Belum ada data buku.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
