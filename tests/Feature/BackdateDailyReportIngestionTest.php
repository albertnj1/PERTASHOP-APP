<?php

namespace Tests\Feature;

use App\Models\BackdateExcelFile;
use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\Price;
use App\Models\Shop;
use App\Models\User;
use App\Services\BackdateDailyReportIngestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackdateDailyReportIngestionTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $shop;
    protected $operator;
    protected $price;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('role', 'super-admin')->first() ?? User::factory()->create(['role' => 'super-admin']);
        $corp = \App\Models\Corporation::first() ?? \App\Models\Corporation::create(['nama' => 'PT Test', 'alamat' => 'Alamat Test']);
        $this->shop = Shop::first() ?? Shop::create([
            'corporation_id' => $corp->id,
            'kode' => '4P.53119',
            'nama' => 'Kalitapen',
            'alamat' => 'Jl. Raya Kalitapen',
            'skala' => 21.0,
            'stik_awal' => 100,
            'totalisator_awal' => 500000,
            'modal_awal' => 60000000,
        ]);

        $this->operator = Operator::first() ?? Operator::create([
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $this->price = Price::first() ?? Price::create([
            'shop_id' => $this->shop->id,
            'harga_jual' => 12200,
            'harga_beli' => 11276.08,
            'status' => true,
            'effective_at' => '2026-01-01',
        ]);
    }

    public function test_backdate_file_relationship_on_daily_report()
    {
        $bef = BackdateExcelFile::create([
            'shop_id' => $this->shop->id,
            'bulan_tahun' => '2026-06',
            'original_filename' => 'test_report.xlsx',
            'file_path' => 'backdate_excel/test.xlsx',
            'file_size' => 12345,
            'user_id' => $this->user->id,
        ]);

        $dr = DailyReport::create([
            'shop_id' => $this->shop->id,
            'operator_id' => $this->operator->id,
            'price_id' => $this->price->id,
            'totalisator_awal' => 1000,
            'totalisator_akhir' => 1500,
            'stik_awal' => 80,
            'stik_akhir' => 75,
            'stok_awal' => 1680,
            'disetorkan' => 5000000,
            'sumber_data' => 'import_excel_arsip',
            'sumber_file_excel' => $bef->original_filename,
            'backdate_file_id' => $bef->id,
            'created_at' => '2026-06-15 17:00:00',
        ]);

        $this->assertNotNull($dr->backdateFile);
        $this->assertEquals($bef->id, $dr->backdateFile->id);
        $this->assertTrue($bef->dailyReports->contains('id', $dr->id));
    }

    public function test_daily_report_index_filters_flexible_date_ranges()
    {
        $this->actingAs($this->user);

        // 1. Filter 1 Hari
        $res1 = $this->getJson(route('daily-reports.index', [
            'shop_id' => $this->shop->id,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res1->assertStatus(200);

        // 2. Filter 30 Hari
        $res30 = $this->getJson(route('daily-reports.index', [
            'shop_id' => $this->shop->id,
            'start_date' => Carbon::now()->subDays(29)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res30->assertStatus(200);

        // 3. Filter 60 Hari
        $res60 = $this->getJson(route('daily-reports.index', [
            'shop_id' => $this->shop->id,
            'start_date' => Carbon::now()->subDays(59)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res60->assertStatus(200);

        // 4. Filter 90 Hari (Maksimal)
        $res90 = $this->getJson(route('daily-reports.index', [
            'shop_id' => $this->shop->id,
            'start_date' => Carbon::now()->subDays(89)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res90->assertStatus(200);
    }

    public function test_daily_report_index_page_contains_all_quick_filter_buttons()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('daily-reports.index'));
        $response->assertStatus(200);

        // Pastikan seluruh tombol filter cepat yang diminta ada pada view
        $response->assertSee('1 Hari (Hari Ini)');
        $response->assertSee('7 Hari');
        $response->assertSee('30 Hari');
        $response->assertSee('60 Hari');
        $response->assertSee('90 Hari (Maks)');
        $response->assertSee('Bulan Ini');
        $response->assertSee('Bulan Lalu');

        // Pastikan atribut data-period tersedia untuk interaksi JS
        $response->assertSee('data-period="30days"', false);
        $response->assertSee('data-period="60days"', false);
        $response->assertSee('data-period="90days"', false);
    }

    public function test_daily_report_action_column_renders_backdate_badge()
    {
        $this->actingAs($this->user);

        $dr = DailyReport::create([
            'shop_id' => $this->shop->id,
            'operator_id' => $this->operator->id,
            'price_id' => $this->price->id,
            'totalisator_awal' => 1000,
            'totalisator_akhir' => 1500,
            'stik_awal' => 80,
            'stik_akhir' => 75,
            'stok_awal' => 1680,
            'disetorkan' => 5000000,
            'sumber_data' => 'import_excel_arsip',
            'created_at' => Carbon::now()->format('Y-m-d 17:00:00'),
        ]);

        $response = $this->getJson(route('daily-reports.index', [
            'shop_id' => $this->shop->id,
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $content = $response->json();

        $this->assertNotEmpty($content['data']);
        $firstRow = collect($content['data'])->firstWhere('id', $dr->id);
        $this->assertNotNull($firstRow);

        // Cek badge Arsip Historis
        $this->assertStringContainsString('badge-info', $firstRow['action']);
        $this->assertStringContainsString('Arsip', $firstRow['action']);
    }
}
