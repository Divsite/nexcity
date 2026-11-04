<?php

namespace Database\Seeders;

use App\Models\Announcements\AnnouncementCategory;
use Illuminate\Database\Seeder;

/**
 * The kinds of announcement an RT and a mosque actually make.
 *
 * Drawn from what these two bodies already announce over a loudspeaker and in
 * WhatsApp groups — not from a generic CMS taxonomy. This is a starting list,
 * not a closed one: the table exists so a takmir can add a category we never
 * thought of.
 */
class AnnouncementCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // ── RT ──────────────────────────────────────────────────────────
            [
                'name' => 'Berita Duka',
                'slug' => 'berita-duka',
                'icon' => 'moon',
                'applies_to' => 'both',
                // Never public. A death notice names a grieving family and
                // gives the address of the house they are sitting in. That
                // belongs to the neighbourhood, not to the internet.
                'default_audience' => 'members',
                'is_urgent' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Kerja Bakti',
                'slug' => 'kerja-bakti',
                'icon' => 'broom',
                'applies_to' => 'both',
                'default_audience' => 'members',
                'is_urgent' => false,
                'sort_order' => 20,
            ],
            [
                'name' => 'Keamanan & Siskamling',
                'slug' => 'keamanan',
                'icon' => 'shield',
                'applies_to' => 'rt',
                'default_audience' => 'members',
                // A break-in tonight is worth an interruption.
                'is_urgent' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Iuran & Keuangan',
                'slug' => 'iuran-keuangan',
                'icon' => 'wallet',
                'applies_to' => 'rt',
                'default_audience' => 'members',
                'is_urgent' => false,
                'sort_order' => 40,
            ],
            [
                'name' => 'Bantuan Sosial',
                'slug' => 'bantuan-sosial',
                'icon' => 'gift',
                'applies_to' => 'both',
                'default_audience' => 'members',
                'is_urgent' => false,
                'sort_order' => 50,
            ],
            [
                'name' => 'Acara Warga',
                'slug' => 'acara-warga',
                'icon' => 'calendar',
                'applies_to' => 'rt',
                'default_audience' => 'members',
                'is_urgent' => false,
                'sort_order' => 60,
            ],

            // ── Masjid ──────────────────────────────────────────────────────
            [
                'name' => 'Jadwal Kajian',
                'slug' => 'jadwal-kajian',
                'icon' => 'book-open',
                'applies_to' => 'mosque',
                // Open by design: a kajian welcomes anyone who walks in.
                'default_audience' => 'public',
                'is_urgent' => false,
                'sort_order' => 70,
            ],
            [
                'name' => 'Program & Qurban',
                'slug' => 'program-qurban',
                'icon' => 'megaphone',
                'applies_to' => 'mosque',
                'default_audience' => 'public',
                'is_urgent' => false,
                'sort_order' => 80,
            ],
            [
                'name' => 'Sholat Jenazah',
                'slug' => 'sholat-jenazah',
                'icon' => 'moon',
                'applies_to' => 'mosque',
                'default_audience' => 'members',
                'is_urgent' => true,
                'sort_order' => 90,
            ],
            [
                'name' => 'Laporan Keuangan',
                'slug' => 'laporan-keuangan',
                'icon' => 'chart',
                'applies_to' => 'mosque',
                // Transparency is the point: a donor who cannot see where the
                // money went has been asked to trust an app instead of a mosque.
                'default_audience' => 'public',
                'is_urgent' => false,
                'sort_order' => 100,
            ],
            [
                'name' => 'Pembangunan & Renovasi',
                'slug' => 'pembangunan',
                'icon' => 'hammer',
                'applies_to' => 'mosque',
                'default_audience' => 'public',
                'is_urgent' => false,
                'sort_order' => 110,
            ],

            // ── Keduanya ────────────────────────────────────────────────────
            [
                'name' => 'Pengumuman Umum',
                'slug' => 'umum',
                'icon' => 'info',
                'applies_to' => 'both',
                'default_audience' => 'members',
                'is_urgent' => false,
                'sort_order' => 999,
            ],
        ];

        foreach ($categories as $category) {
            AnnouncementCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
