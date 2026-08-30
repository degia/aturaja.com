<?php

namespace App\Support;

/**
 * Daftar logo bawaan aplikasi berupa badge SVG sederhana (inisial + warna brand)
 * untuk bank dan e-wallet Indonesia. Bukan file logo asli ber-trademark, melainkan
 * representasi aman dengan inisial dan warna khas.
 */
class BankLogos
{
    public const MAP = [
        'cash' => ['name' => 'Tunai', 'label' => 'Tunai', 'color' => '#64748B'],
        'wallet' => ['name' => 'Dompet', 'label' => 'Dompet', 'color' => '#14B8A6'],
        'card' => ['name' => 'Kartu', 'label' => 'Kartu', 'color' => '#6366F1'],
        'bca' => ['name' => 'Bank Central Asia', 'label' => 'BCA', 'color' => '#0060AC'],
        'mandiri' => ['name' => 'Bank Mandiri', 'label' => 'Mandiri', 'color' => '#0070C0'],
        'bni' => ['name' => 'Bank Negara Indonesia', 'label' => 'BNI', 'color' => '#F5A300'],
        'bri' => ['name' => 'Bank Rakyat Indonesia', 'label' => 'BRI', 'color' => '#003C8F'],
        'bsi' => ['name' => 'Bank Syariah Indonesia', 'label' => 'BSI', 'color' => '#00A99D'],
        'permata' => ['name' => 'Bank Permata', 'label' => 'Permata', 'color' => '#003B5C'],
        'cimb' => ['name' => 'CIMB Niaga', 'label' => 'CIMB', 'color' => '#7A2E1D'],
        'jenius' => ['name' => 'Bank BTPN', 'label' => 'Jenius', 'color' => '#F401A7'],
        'digibank' => ['name' => 'DBS', 'label' => 'Digibank', 'color' => '#FDB516'],
        'gopay' => ['name' => 'GoPay', 'label' => 'GoPay', 'color' => '#00AED6'],
        'ovo' => ['name' => 'OVO', 'label' => 'OVO', 'color' => '#4C3494'],
        'dana' => ['name' => 'DANA', 'label' => 'DANA', 'color' => '#108EE9'],
        'shopeepay' => ['name' => 'ShopeePay', 'label' => 'ShopeePay', 'color' => '#EE4D2D'],
        'linkaja' => ['name' => 'LinkAja', 'label' => 'LinkAja', 'color' => '#FF4E13'],
        'sakuku' => ['name' => 'SakuKu', 'label' => 'SakuKu', 'color' => '#FF5A00'],
        'isaku' => ['name' => 'iSaku', 'label' => 'iSaku', 'color' => '#00B14F'],
    ];

    /** Prefix yang menandai logo hasil upload (nilai icon tersimpan di DB). */
    public const UPLOAD_PREFIX = 'upload:';

    public static function all(): array
    {
        return self::MAP;
    }

    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    public static function find(?string $code): ?array
    {
        if ($code === null) {
            return null;
        }

        return self::MAP[$code] ?? null;
    }

    public static function isUploaded(?string $icon): bool
    {
        return is_string($icon) && str_starts_with($icon, self::UPLOAD_PREFIX);
    }

    public static function uploadedPath(?string $icon): ?string
    {
        if (! self::isUploaded($icon)) {
            return null;
        }

        return substr($icon, strlen(self::UPLOAD_PREFIX));
    }
}
