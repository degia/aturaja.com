<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class BankLogos
{
    public const MAP = [
        'cash'     => ['name' => 'Tunai', 'label' => 'Tunai', 'color' => '#64748B', 'image' => 'images/banks/cash.png'],
        'wallet'   => ['name' => 'Dompet', 'label' => 'Dompet', 'color' => '#14B8A6', 'image' => 'images/banks/wallet.png'],
        'agi'     => ['name' => 'Bank Artha Graha International', 'label' => 'AGI', 'color' => '#14B8A6', 'image' => 'images/banks/agi.png'],
        'bca'      => ['name' => 'Bank Central Asia', 'label' => 'BCA', 'color' => '#0060AC', 'image' => 'images/banks/bca.png'],
        'mandiri'  => ['name' => 'Bank Mandiri', 'label' => 'Mandiri', 'color' => '#0070C0', 'image' => 'images/banks/mandiri.png'],
        'bni'      => ['name' => 'Bank Negara Indonesia', 'label' => 'BNI', 'color' => '#F5A300', 'image' => 'images/banks/bni.png'],
        'bri'      => ['name' => 'Bank Rakyat Indonesia', 'label' => 'BRI', 'color' => '#003C8F', 'image' => 'images/banks/bri.png'],
        'bsi'      => ['name' => 'Bank Syariah Indonesia', 'label' => 'BSI', 'color' => '#00A99D', 'image' => 'images/banks/bsi.png'],
        'permata'  => ['name' => 'Bank Permata', 'label' => 'Permata', 'color' => '#003B5C', 'image' => 'images/banks/permata.png'],
        'cimb'     => ['name' => 'CIMB Niaga', 'label' => 'CIMB', 'color' => '#7A2E1D', 'image' => 'images/banks/cimb.png'],
        'jenius'   => ['name' => 'Bank BTPN', 'label' => 'Jenius', 'color' => '#F401A7', 'image' => 'images/banks/jenius.png'],
        'digibank' => ['name' => 'DBS', 'label' => 'Digibank', 'color' => '#FDB516', 'image' => 'images/banks/digibank.png'],
        'gopay'    => ['name' => 'GoPay', 'label' => 'GoPay', 'color' => '#00AED6', 'image' => 'images/banks/gopay.png'],
        'ovo'      => ['name' => 'OVO', 'label' => 'OVO', 'color' => '#4C3494', 'image' => 'images/banks/ovo.png'],
        'dana'     => ['name' => 'DANA', 'label' => 'DANA', 'color' => '#108EE9', 'image' => 'images/banks/dana.png'],
        'shopeepay' => ['name' => 'ShopeePay', 'label' => 'ShopeePay', 'color' => '#EE4D2D', 'image' => 'images/banks/shopeepay.png'],
        'linkaja'  => ['name' => 'LinkAja', 'label' => 'LinkAja', 'color' => '#FF4E13', 'image' => 'images/banks/linkaja.png'],
        'sakuku'   => ['name' => 'SakuKu', 'label' => 'SakuKu', 'color' => '#FF5A00', 'image' => 'images/banks/sakuku.png'],
        'isaku'    => ['name' => 'iSaku', 'label' => 'iSaku', 'color' => '#00B14F', 'image' => 'images/banks/isaku.png'],
    ];

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

    /**
     * Mendapatkan URL gambar PNG yang valid (baik preset maupun upload kustom).
     */
    public static function getUrl(?string $codeOrUpload): ?string
    {
        if (empty($codeOrUpload)) {
            return null;
        }

        // 1. Jika logo berasal dari upload kustom user
        if (self::isUploaded($codeOrUpload)) {
            $path = self::uploadedPath($codeOrUpload);
            return Storage::url($path);
        }

        // 2. Jika logo berasal dari preset MAP bawaan
        $bank = self::find($codeOrUpload);
        if ($bank && isset($bank['image'])) {
            return asset($bank['image']);
        }

        return null;
    }
}
