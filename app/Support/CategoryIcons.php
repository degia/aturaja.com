<?php

namespace App\Support;

class CategoryIcons
{
    /**
     * CDN untuk ikon Lucide (gaya line).
     */
    public const CDN = 'https://unpkg.com/lucide@latest';

    /**
     * Daftar ikon Lucide yang tersedia untuk kategori.
     * Key = nama ikon Lucide, Value = label tampilan.
     */
    public const ICONS = [
        // Keuangan umum
        'wallet' => 'Dompet',
        'banknote' => 'Uang',
        'coins' => 'Koin',
        'hand-coins' => 'Pendapatan',
        'piggy-bank' => 'Tabungan',
        'percent' => 'Persen',
        'trending-up' => 'Naik',
        'trending-down' => 'Turun',
        'gift' => 'Hadiah',
        'inbox' => 'Masuk',
        'send' => 'Keluar',
        'briefcase' => 'Bisnis',
        'briefcase-business' => 'Kerja',
        'building-2' => 'Bank',
        'landmark' => 'Bunga',
        'store' => 'Toko',
        'handshake' => 'Hutang',
        'scale' => 'Piutang',
        'arrow-left-right' => 'Transfer',
        // Makanan & minuman
        'utensils' => 'Makan',
        'utensils-crossed' => 'Makan & Minum',
        'coffee' => 'Kopi',
        'cup-soda' => 'Minuman',
        'glass-water' => 'Air',
        'beef' => 'Daging',
        // Transportasi
        'car' => 'Mobil',
        'car-front' => 'Kendaraan',
        'fuel' => 'Bensin',
        'bus' => 'Bus',
        'ticket' => 'Parkir',
        'wrench' => 'Servis',
        'settings-2' => 'Sparepart',
        // Tagihan & utilitas
        'receipt' => 'Tagihan',
        'file-text' => 'Dokumen',
        'scroll-text' => 'Utilitas',
        'zap' => 'Listrik',
        'droplets' => 'Air & Gas',
        'wifi' => 'Internet',
        'smartphone' => 'Pulsa',
        'tv' => 'TV',
        // Belanja
        'shopping-bag' => 'Belanja',
        'shopping-cart' => 'Keranjang',
        'shirt' => 'Pakaian',
        'brush' => 'Kosmetik',
        'cigarette' => 'Vape',
        // Kesehatan
        'heart-pulse' => 'Kesehatan',
        'stethoscope' => 'Medis',
        'dumbbell' => 'Olahraga',
        // Pendidikan
        'graduation-cap' => 'Pendidikan',
        'book-open' => 'Buku',
        // Hiburan
        'clapperboard' => 'Hiburan',
        'gamepad-2' => 'Game',
        'music' => 'Musik',
        'film' => 'Film',
        // Gaya hidup
        'sparkles' => 'Gaya Hidup',
        'flower-2' => 'Kebutuhan',
        'baby' => 'Keluarga',
        // Rumah
        'home' => 'Rumah',
        'house' => 'Tempat Tinggal',
        'sofa' => 'Perabot',
        // Lainnya
        'shapes' => 'Lainnya',
        'circle-help' => 'Bantuan',
        'paw-print' => 'Hewan',
    ];

    /**
     * Pemetaan emoji lama -> nama ikon Lucide untuk kategori yang sudah ada.
     */
    public const EMOJI_MAP = [
        '💰' => 'wallet',
        '🎉' => 'gift',
        '🏪' => 'store',
        '📈' => 'trending-up',
        '📥' => 'inbox',
        '🍜' => 'utensils',
        '🚗' => 'car',
        '🧾' => 'receipt',
        '🛍️' => 'shopping-bag',
        '🩺' => 'stethoscope',
        '🎓' => 'graduation-cap',
        '🎬' => 'clapperboard',
        '✨' => 'sparkles',
        '🏠' => 'home',
        '📤' => 'send',
    ];

    /**
     * Resolve nilai ikon (nama Lucide atau emoji lama) menjadi nama ikon Lucide.
     */
    public static function resolve(?string $icon): ?string
    {
        if (! $icon) {
            return null;
        }

        if (isset(self::ICONS[$icon])) {
            return $icon;
        }

        return self::EMOJI_MAP[$icon] ?? null;
    }
}
