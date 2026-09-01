<?php

namespace App\Domain\Backup\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackupService
{
    /**
     * Scope backup yang tersedia.
     */
    public const SCOPE_FULL = 'full';

    public const SCOPE_SETTINGS = 'settings';

    public const SCOPES = [self::SCOPE_FULL, self::SCOPE_SETTINGS];

    /**
     * Tabel "master/setting" yang masuk ke kedua scope (full maupun settings).
     */
    private const MASTER_TABLES = [
        'accounts',
        'categories',
        'tags',
        'recurring_rules',
        'budgets',
    ];

    /**
     * Tabel data transaksi/keuangan yang hanya masuk ke scope full.
     */
    private const DATA_TABLES = [
        'transactions',
        'transaction_tag',
        'assets',
        'liabilities',
        'debts',
        'debt_payments',
        'debt_installments',
        'net_worth_snapshots',
    ];

    /**
     * Tabel yang tidak memiliki kolom workspace_id (relasinya lewat FK ke tabel
     * induk yang punya workspace_id). Memetakan nama tabel -> [kolom FK, tabel induk].
     */
    private const PARENTLESS_TABLES = [
        'transaction_tag' => ['transaction_id', 'transactions'],
        'debt_installments' => ['debt_id', 'debts'],
    ];

    public function scopes(): array
    {
        return [
            self::SCOPE_FULL => 'Full data (seluruh catatan transaksi & keuangan)',
            self::SCOPE_SETTINGS => 'Settings saja (akun, kategori, tag, aturan berulang, budget)',
        ];
    }

    public function scopeLabel(string $scope): string
    {
        return $this->scopes()[$scope] ?? $scope;
    }

    public function tablesFor(string $scope): array
    {
        if ($scope === self::SCOPE_FULL) {
            return array_merge(self::MASTER_TABLES, self::DATA_TABLES);
        }

        return self::MASTER_TABLES;
    }

    /**
     * Buat payload JSON dari data workspace sesuai scope.
     *
     * @return array{payload: array<string, mixed>, filename: string}
     */
    public function export(Workspace $workspace, string $scope): array
    {
        $scope = $this->normalizeScope($scope);

        $data = [];
        foreach ($this->tablesFor($scope) as $table) {
            [$column, $ids] = $this->workspaceFilter($table, $workspace->id);

            $query = DB::table($table)
                ->when($column === 'workspace_id', fn ($q) => $q->where('workspace_id', $ids))
                ->when($column !== 'workspace_id', fn ($q) => $q->whereIn($column, $ids));

            if ($column === 'workspace_id') {
                $query->orderBy('id');
            }

            $data[$table] = $query
                ->get()
                ->map(fn ($row) => (array) $row)
                ->values()
                ->toArray();
        }

        $payload = [
            'app' => 'aturaja',
            'version' => 1,
            'scope' => $scope,
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'currency' => $workspace->currency,
            ],
            'exported_at' => now()->toIso8601String(),
            'data' => $data,
        ];

        $slug = str()->slug($workspace->name) ?: 'workspace';
        $filename = sprintf(
            'aturaja-%s-%s-%s.json',
            $slug,
            $scope,
            now()->format('Ymd-His')
        );

        return ['payload' => $payload, 'filename' => $filename];
    }

    /**
     * Restore (ganti total) data workspace dengan isi payload backup.
     *
     * Dipakai DB::table agar tidak memicu observer/event model sehingga saldo
     * akun, status cicilan, dan relasi tetap persis seperti saat backup.
     */
    public function restore(Workspace $workspace, array $payload): void
    {
        $this->validatePayload($payload);

        if ((int) ($payload['workspace']['id'] ?? 0) !== (int) $workspace->id) {
            throw new RuntimeException('Backup ini bukan milik workspace yang sedang aktif.');
        }

        $scope = $this->normalizeScope($payload['scope']);
        $tables = $this->tablesFor($scope);

        DB::transaction(function () use ($workspace, $payload, $tables) {
            // Matikan pengecekan foreign key selama penghapusan + penyisipan
            // ulang. Ini penting untuk scope "settings": menghapus akun/kategori
            // tidak boleh meng-cascade transaksi/debt yang tetap dipertahankan.
            // Karena id asli dipertahankan, referensi tetap valid setelah FK
            // diaktifkan kembali. Bila ada referensi yang menggantung, transaksi
            // DB akan gagal dan seluruh restore di-rollback.
            $fk = $this->foreignKeyChecks();
            $this->setForeignKeyChecks(false);

            try {
                // 1. Hapus data lama workspace (urut terbalik agar FK aman).
                foreach (array_reverse($tables) as $table) {
                    $this->deleteForWorkspace($table, $workspace->id);
                }

                // 2. Pulihkan informasi workspace (nama & mata uang).
                $workspace->forceFill([
                    'name' => $payload['workspace']['name'],
                    'currency' => $payload['workspace']['currency'],
                ])->save();

                // 3. Sisipkan ulang data backup.
                foreach ($tables as $table) {
                    $rows = $payload['data'][$table] ?? [];

                    foreach ($rows as $row) {
                        $this->insertRow($table, $row, $workspace->id);
                    }
                }
            } finally {
                $this->setForeignKeyChecks($fk);
            }
        });
    }

    private function foreignKeyChecks(): bool
    {
        return (bool) DB::selectOne('SELECT @@SESSION.foreign_key_checks AS fk')->fk;
    }

    private function setForeignKeyChecks(bool $enabled): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS='.($enabled ? '1' : '0'));
    }

    /**
     * Hapus seluruh baris milik workspace. Tabel tanpa workspace_id dihapus
     * lewat id pada tabel induk yang mengarah ke workspace tersebut.
     */
    private function deleteForWorkspace(string $table, int $workspaceId): void
    {
        [$column, $ids] = $this->workspaceFilter($table, $workspaceId);

        DB::table($table)
            ->when($column === 'workspace_id', fn ($q) => $q->where('workspace_id', $ids))
            ->when($column !== 'workspace_id', fn ($q) => $q->whereIn($column, $ids))
            ->delete();
    }

    /**
     * Kembalikan [kolom, id] untuk memfilter baris milik workspace pada sebuah
     * tabel. Untuk tabel dengan workspace_id, id berupa angka workspace. Untuk
     * tabel tanpa workspace_id, id berupa daftar id pada tabel induk.
     *
     * @return array{0: string, 1: int|array}
     */
    private function workspaceFilter(string $table, int $workspaceId): array
    {
        if (isset(self::PARENTLESS_TABLES[$table])) {
            [$fk, $parent] = self::PARENTLESS_TABLES[$table];

            return [$fk, DB::table($parent)->where('workspace_id', $workspaceId)->pluck('id')->all()];
        }

        return ['workspace_id', $workspaceId];
    }

    /**
     * Sisipkan satu baris sambil mempertahankan id asli agar relasi antar-tabel
     * (FK seperti transaction.account_id, debt_payments.transaction_id, dst.)
     * tetap utuh. Karena baris lama workspace sudah dihapus lebih dulu di dalam
     * transaksi yang sama, id asli pasti bebas dan tidak bentrok.
     *
     * Tabel tanpa workspace_id (mis. transaction_tag, debt_installments) tidak
     * punya kolom workspace_id maupun id (composite primary key), sehingga
     * keduanya dilewati.
     */
    private function insertRow(string $table, array $row, int $workspaceId): void
    {
        if ($this->isParentless($table)) {
            DB::table($table)->insert($row);

            return;
        }

        $row['workspace_id'] = $workspaceId;
        $row['id'] = (int) $row['id'];

        DB::table($table)->insert($row);
    }

    private function isParentless(string $table): bool
    {
        return isset(self::PARENTLESS_TABLES[$table]);
    }

    private function normalizeScope(string $scope): string
    {
        if ($scope === self::SCOPE_FULL) {
            return self::SCOPE_FULL;
        }

        return self::SCOPE_SETTINGS;
    }

    private function validatePayload(array $payload): void
    {
        if (($payload['app'] ?? null) !== 'aturaja') {
            throw new RuntimeException('File bukan backup AturAja yang valid.');
        }

        if (! isset($payload['data']) || ! is_array($payload['data'])) {
            throw new RuntimeException('File backup tidak memiliki data.');
        }

        if (! isset($payload['workspace']) || ! is_array($payload['workspace'])) {
            throw new RuntimeException('File backup tidak memiliki informasi workspace.');
        }
    }
}
