<?php

namespace App\Domain\Categories\Actions;

use App\Models\Category;
use App\Models\Workspace;

class SeedDefaultCategories
{
    /**
     * Daftar kategori bawaan, dijadikan referensi juga oleh DemoDataSeeder.
     *
     * @return array<int, array{name: string, type: string, icon: string, color: string}>
     */
    public static function list(): array
    {
        return [
            ['name' => 'Gaji', 'type' => 'income', 'icon' => '💰', 'color' => '#16A34A'],
            ['name' => 'Bonus', 'type' => 'income', 'icon' => '🎉', 'color' => '#0EA5E9'],
            ['name' => 'Pendapatan Bisnis', 'type' => 'income', 'icon' => '🏪', 'color' => '#8B5CF6'],
            ['name' => 'Bunga & Dividen', 'type' => 'income', 'icon' => '📈', 'color' => '#059669'],
            ['name' => 'Lainnya (Masuk)', 'type' => 'income', 'icon' => '📥', 'color' => '#64748B'],
            ['name' => 'Makanan', 'type' => 'expense', 'icon' => '🍜', 'color' => '#F97316'],
            ['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🚗', 'color' => '#14B8A6'],
            ['name' => 'Tagihan & Utilitas', 'type' => 'expense', 'icon' => '🧾', 'color' => '#EAB308'],
            ['name' => 'Belanja', 'type' => 'expense', 'icon' => '🛍️', 'color' => '#EC4899'],
            ['name' => 'Kesehatan', 'type' => 'expense', 'icon' => '🩺', 'color' => '#EF4444'],
            ['name' => 'Pendidikan', 'type' => 'expense', 'icon' => '🎓', 'color' => '#6366F1'],
            ['name' => 'Hiburan', 'type' => 'expense', 'icon' => '🎬', 'color' => '#A855F7'],
            ['name' => 'Gaya Hidup', 'type' => 'expense', 'icon' => '✨', 'color' => '#D946EF'],
            ['name' => 'Rumah', 'type' => 'expense', 'icon' => '🏠', 'color' => '#F59E0B'],
            ['name' => 'Lainnya (Keluar)', 'type' => 'expense', 'icon' => '📤', 'color' => '#94A3B8'],
        ];
    }

    public static function run(Workspace $workspace): int
    {
        $count = 0;

        foreach (self::list() as $category) {
            Category::create([
                'workspace_id' => $workspace->id,
                'name' => $category['name'],
                'type' => $category['type'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_default' => true,
            ]);

            $count++;
        }

        return $count;
    }
}