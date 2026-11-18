<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function seedData(): void
    {
        $technology = Category::create(['name' => 'Teknologi']);
        $accounting = Category::create(['name' => 'Akuntansi']);
        $management = Category::create(['name' => 'Manajemen']);

        Book::create(['title' => 'Laravel untuk Pemula', 'author' => 'Andi', 'category_id' => $technology->id]);
        Book::create(['title' => 'Belajar Python', 'author' => 'Budi', 'category_id' => $technology->id]);
        Book::create(['title' => 'Pemrograman Web Modern', 'author' => 'Citra', 'category_id' => $technology->id]);
        Book::create(['title' => 'Pengantar Akuntansi', 'author' => 'Dewi', 'category_id' => $accounting->id]);
        Book::create(['title' => 'Akuntansi Biaya', 'author' => 'Eko', 'category_id' => $accounting->id]);
        Book::create(['title' => 'Manajemen Keuangan', 'author' => 'Fitri', 'category_id' => $management->id]);
    }

    /**
     * Gejala 1: "Kata kunci dari form tidak terbaca oleh Controller."
     * Penyebab: Controller membaca $request->search, form mengirim "keyword".
     */
    public function test_kata_kunci_dari_form_terbaca_controller()
    {
        $this->seedData();

        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();
        $response->assertViewHas('keyword', 'Laravel');
        $response->assertSee('Laravel untuk Pemula');
        $response->assertDontSee('Belajar Python');
    }

    /**
     * Gejala 2: "Pencarian hanya menemukan kecocokan persis."
     * Penyebab: memakai where() tanpa wildcard, kini memakai LIKE '%...%'.
     */
    public function test_pencarian_menggunakan_like_parsial_pada_judul()
    {
        $this->seedData();

        // "Belajar" ada di tengah judul, bukan di awal/akhir.
        $response = $this->get('/books?keyword=Belajar');

        $response->assertSee('Belajar Python');
    }

    public function test_pencarian_menggunakan_like_parsial_pada_penulis()
    {
        $this->seedData();

        $response = $this->get('/books?keyword=ud');

        $response->assertSee('Budi');
        $response->assertDontSee('Andi');
    }

    /**
     * Tugas: "Gabungkan kondisi judul dan penulis secara benar."
     * Perhatikan orWhere harus berada di dalam grup where() agar tidak
     * membuat kondisi OR bocor ke filter kategori.
     */
    public function test_kondisi_judul_dan_penulis_tergabung_dengan_benar()
    {
        $this->seedData();

        // "Andi" adalah penulis, "Akuntansi" adalah judul.
        $response = $this->get('/books?keyword=Akuntansi');

        $response->assertSee('Pengantar Akuntansi');
        $response->assertSee('Akuntansi Biaya');
        $response->assertDontSee('Laravel untuk Pemula');
    }

    /**
     * Gejala 3: "Filter kategori tidak bekerja."
     * Penyebab: Controller membaca $request->kategori, form mengirim "category".
     */
    public function test_filter_kategori_bekerja()
    {
        $this->seedData();

        $technology = Category::where('name', 'Teknologi')->first();
        $accounting = Category::where('name', 'Akuntansi')->first();

        $response = $this->get('/books?category='.$accounting->id);

        $response->assertOk();
        $response->assertViewHas('categoryId', (string) $accounting->id);
        $response->assertSee('Pengantar Akuntansi');
        $response->assertSee('Akuntansi Biaya');
        $response->assertDontSee('Laravel untuk Pemula');
    }

    /**
     * Kombinasi keyword + kategori harus saling membatasi (AND),
     * bukan salah satu saja (OR).
     */
    public function test_keyword_dan_kategori_tergabung_dengan_and()
    {
        $this->seedData();

        $technology = Category::where('name', 'Teknologi')->first();
        $accounting = Category::where('name', 'Akuntansi')->first();

        // "Akuntansi" ada di kategori Akuntansi, tapi juga kata "Akuntansi"
        // muncul di judul buku kategori Teknologi? Tidak - jadi hasil kosong.
        $response = $this->get('/books?keyword=Akuntansi&category='.$technology->id);

        $response->assertSee('Buku tidak ditemukan');
    }

    public function test_tanpa_filter_semua_buku_tampil()
    {
        $this->seedData();

        $response = $this->get('/books');

        $response->assertOk();
        $response->assertViewHas('books');
        $this->assertCount(6, $response->viewData('books'));
    }

    /**
     * Gejala 4: "Variabel hasil pencarian tidak tersedia di Blade."
     * Penyebab: Controller mengirim "hasilBuku", Blade meminta "$books".
     */
    public function test_variabel_hasil_pencarian_tersedia_di_view()
    {
        $this->seedData();

        $response = $this->get('/books');

        $response->assertViewHas('books');
        $response->assertViewHas('categories');
        $this->assertArrayNotHasKey(
            'hasilBuku',
            $response->viewData(),
            'View masih menerima variabel lama hasilBuku.'
        );
    }

    /**
     * Gejala 5: "Input pencarian tidak dipertahankan setelah submit."
     * Penyebab: Blade memakai old('keyword') pada form GET, yang selalu kosong.
     */
    public function test_input_pencarian_pertahankan_setelah_submit()
    {
        $this->seedData();

        $response = $this->get('/books?keyword=Laravel');

        $response->assertSee('value="Laravel"', false);
    }

    public function test_pilihan_kategori_terpilih_terpertahankan_setelah_submit()
    {
        $this->seedData();

        $accounting = Category::where('name', 'Akuntansi')->first();

        $response = $this->get('/books?category='.$accounting->id);

        $response->assertSee('selected', false);
    }

    /**
     * Tugas: "Tampilkan pesan ketika hasil kosong."
     */
    public function test_menampilkan_pesan_saat_hasil_kosong()
    {
        $this->seedData();

        $response = $this->get('/books?keyword=TidakAdaBuku');

        $response->assertOk();
        $response->assertSee('Buku tidak ditemukan');
        $this->assertCount(0, $response->viewData('books'));
    }

    public function test_pesan_kosong_tidak_muncul_saat_data_ada()
    {
        $this->seedData();

        $response = $this->get('/books?keyword=Laravel');

        $response->assertDontSee('Buku tidak ditemukan');
    }

    public function test_keyword_dengan_spasi_ekstra_ditangani()
    {
        $this->seedData();

        $response = $this->get('/books?keyword=%20%20Laravel%20%20');

        $response->assertViewHas('keyword', 'Laravel');
        $response->assertSee('Laravel untuk Pemula');
    }
}
