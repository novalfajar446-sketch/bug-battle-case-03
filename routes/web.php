<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;

Route::get('/', fn () => redirect('/books'));

// Perbaikan: route diberi nama agar bisa dipanggil dengan route('books.index')
// pada view, dan agar konsisten dengan kasus sebelumnya.
Route::get('/books', [BookController::class, 'index'])->name('books.index');
