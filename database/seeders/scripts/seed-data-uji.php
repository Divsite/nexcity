<?php

/**
 * Test data, all of it tagged [UJI].
 *
 * Every row this script creates carries the marker so it can be told apart
 * from Al-amanah's real Ramadan records and deleted in one go. Untagged test
 * data is how a pending row for Baba Rudin ended up looking like a bug in the
 * app rather than something we put there ourselves.
 */

use App\Models\Charities\CharityTransaction;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use Illuminate\Support\Facades\DB;

const MARK = '[UJI]';

$alamanah = 2;   // Islamic Center Al-amanah
$darul = 3;      // Darul Muminin — the second mosque, for cross-organization checks

// ── 1. Charity for 2025, so the year picker has something to switch to ──────

$typeFor = function (int $org, string $name, int $year): int {
    $sourceId = DB::table('m_charity_type_sources')->where('name', $name)->value('id');

    if (! $sourceId) {
        $sourceId = DB::table('m_charity_type_sources')->insertGetId([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $existing = DB::table('charity_types')
        ->where('organization_id', $org)
        ->where('charity_type_source_id', $sourceId)
        ->where('year', $year)
        ->value('id');

    return $existing ?: DB::table('charity_types')->insertGetId([
        'organization_id' => $org,
        'charity_type_source_id' => $sourceId,
        'year' => $year,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
};

$record = function (int $org, string $type, int $year, string $when, float $money, float $rice, string $payer) use ($typeFor) {
    $at = \Illuminate\Support\Carbon::parse($when);

    $transaction = CharityTransaction::firstOrCreate(
        ['organization_id' => $org, 'payer_name' => MARK . ' ' . $payer, 'year' => $year],
        [
            'charity_type_id' => $typeFor($org, $type, $year),
            'payment_method' => $money > 0 ? 'transfer' : 'cash',
            'status' => 'paid',
            'created_at' => $at,
            'updated_at' => $at,
        ],
    );

    if ($transaction->wasRecentlyCreated) {
        DB::table('charity_fitrah_receipts')->insert([
            'charity_transaction_id' => $transaction->id,
            'amount_money' => $money,
            'amount_rice' => $rice,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
};

// 2025 — a whole Ramadan, so switching the year shows a real shape.
$record($alamanah, 'Zakat Fitrah', 2025, '2025-03-20 08:00', 250000, 0, 'Warga Satu');
$record($alamanah, 'Zakat Fitrah', 2025, '2025-03-21 09:00', 0, 4, 'Warga Dua');
$record($alamanah, 'Zakat Fitrah', 2025, '2025-03-22 10:00', 500000, 0, 'Warga Tiga');
$record($alamanah, 'Zakat Mal', 2025, '2025-03-23 11:00', 3500000, 0, 'Warga Empat');
$record($alamanah, 'Infaq', 2025, '2025-03-24 19:00', 1200000, 0, 'Warga Lima');
$record($alamanah, 'Infaq', 2025, '2025-06-13 19:30', 800000, 0, 'Warga Enam');
$record($alamanah, 'Sedekah', 2025, '2025-09-05 19:30', 450000, 0, 'Warga Tujuh');

// 2024, so the picker has three years and the trend has something to compare.
$record($alamanah, 'Zakat Fitrah', 2024, '2024-04-01 08:00', 1750000, 0, 'Warga Lama');
$record($alamanah, 'Infaq', 2024, '2024-04-05 19:00', 600000, 0, 'Warga Lama Dua');

// Today, so "Amal hari ini" on the home screen is not permanently zero.
$record($alamanah, 'Infaq', (int) now()->year, now()->setTime(7, 30)->toDateTimeString(), 150000, 0, 'Infaq Subuh');
$record($alamanah, 'Sedekah', (int) now()->year, now()->setTime(12, 15)->toDateTimeString(), 75000, 0, 'Sedekah Dzuhur');

// A second mosque, so cross-organization leaks show up as wrong figures.
$record($darul, 'Infaq', (int) now()->year, now()->setTime(8, 0)->toDateTimeString(), 999000, 0, 'Jangan Muncul di Alamanah');
$record($darul, 'Zakat Fitrah', 2025, '2025-03-20 08:00', 2000000, 0, 'Darul 2025');

// ── 2. A qurban programme, because every qurban screen hangs off one ────────

$program = QurbanProgram::firstOrCreate(
    ['organization_id' => $alamanah, 'slug' => 'uji-qurban-1447h'],
    [
        'title' => MARK . ' Qurban Idul Adha 1447 H',
        'year' => (int) now()->year,
        'period_start_at' => now()->subDays(10),
        'period_end_at' => now()->addDays(45),
        'description' => 'Program qurban uji coba. Boleh dihapus kapan saja.',
        'status' => 'open',
        'is_public' => true,
    ],
);

$packages = [
    [
        'title' => 'Patungan Sapi 1/7',
        'animal_type' => 'cow',
        'package_type' => 'share',
        'share_count' => 7,
        'price' => 3200000,
        'quota' => 49,
        'target_weight_min' => 250,
        'target_weight_max' => 300,
    ],
    [
        'title' => 'Sapi Utuh',
        'animal_type' => 'cow',
        'package_type' => 'full',
        'share_count' => null,
        'price' => 22000000,
        'quota' => 5,
        'target_weight_min' => 250,
        'target_weight_max' => 300,
    ],
    [
        'title' => 'Kambing Reguler',
        'animal_type' => 'goat',
        'package_type' => 'full',
        'share_count' => null,
        'price' => 2750000,
        'quota' => 30,
        'target_weight_min' => 20,
        'target_weight_max' => 25,
    ],
];

foreach ($packages as $package) {
    QurbanProgramPackage::firstOrCreate(
        ['qurban_program_id' => $program->id, 'title' => $package['title']],
        $package + [
            'remaining_quota' => $package['quota'],
            'is_active' => true,
        ],
    );
}

// Animals at different stages, so the tracking screen has a real spread rather
// than one row repeated.
$animals = [
    ['cow', 'SAPI-01', 'available', 285],
    ['cow', 'SAPI-02', 'allocated', 310],
    ['cow', 'SAPI-03', 'slaughtered', 295],
    ['goat', 'KMB-01', 'available', 23],
    ['goat', 'KMB-02', 'distributed', 21],
];

foreach ($animals as [$type, $code, $status, $weight]) {
    QurbanAnimal::firstOrCreate(
        ['organization_id' => $alamanah, 'animal_code' => MARK . ' ' . $code],
        [
            'animal_type' => $type,
            'ear_tag_code' => strtolower($code),
            'gender' => 'male',
            'weight' => $weight,
            'health_status' => 'healthy',
            'status' => $status,
            'notes' => 'Data uji.',
        ],
    );
}

echo '── Ringkasan ──' . PHP_EOL;
echo 'Transaksi bertanda ' . MARK . ': '
    . CharityTransaction::where('payer_name', 'like', MARK . '%')->count() . PHP_EOL;
echo 'Tahun tersedia (Alamanah): '
    . CharityTransaction::where('organization_id', $alamanah)->distinct()->pluck('year')->sort()->implode(', ') . PHP_EOL;
echo 'Program qurban: ' . QurbanProgram::count()
    . ' · paket: ' . QurbanProgramPackage::count()
    . ' · hewan: ' . QurbanAnimal::count() . PHP_EOL;
