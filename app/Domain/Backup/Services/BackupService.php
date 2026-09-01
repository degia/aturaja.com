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

    /**
     * Pemetaan foreign key per tabel untuk remapping id saat restore.
     * Memetakan nama tabel -> [kolom FK => tabel induk].
     */
    private const FK_MAP = [
        'categories' => ['parent_id' => 'categories'],
        'recurring_rules' => [
            'account_id' => 'accounts',
            'category_id' => 'categories',
            'transfer_to_account_id' => 'accounts',
        ],
        'budgets' => ['category_id' => 'categories'],
        'transactions' => [
            'account_id' => 'accounts',
            'category_id' => 'categories',
            'transfer_to_account_id' => 'accounts',
            'recurring_rule_id' => 'recurring_rules',
        ],
        'transaction_tag' => [
            'transaction_id' => 'transactions',
            'tag_id' => 'tags',
        ],
        'assets' => ['linked_account_id' => 'accounts'],
        'liabilities' => ['linked_account_id' => 'accounts'],
        'debts' => ['default_account_id' => 'accounts'],
        'debt_payments' => [
            'debt_id' => 'debts',
            'account_id' => 'accounts',
            'transaction_id' => 'transactions',
        ],
        'debt_installments' => ['debt_id' => 'debts'],
    ];

    /**
     * Kolom pada tabel yang TIDAK direstor (mis. pada scope settings) yang tetap
     * perlu diremapping karena mereferensikan tabel master (accounts/categories/
     * tags/recurring_rules). Dipakai untuk menjaga data lama tetap konsisten.
     * Memetakan nama tabel -> [kolom FK => tabel induk].
     */
    private const RETAINED_FK_MAP = [
        'transactions' => [
            'account_id' => 'accounts',
            'transfer_to_account_id' => 'accounts',
            'category_id' => 'categories',
            'recurring_rule_id' => 'recurring_rules',
        ],
        'assets' => ['linked_account_id' => 'accounts'],
        'liabilities' => ['linked_account_id' => 'accounts'],
        'debts' => ['default_account_id' => 'accounts'],
        'debt_payments' => ['account_id' => 'accounts'],
        'transaction_tag' => ['tag_id' => 'tags'],
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
     * Setiap baris diberi id baru (auto increment) dan seluruh foreign key
     * di-remapping dari id lama ke id baru. Ini membuat restore aman dilakukan
     * ke workspace/akun mana pun (id tidak bentrok dengan data yang ada).
     *
     * Dipakai DB::table agar tidak memicu observer/event model sehingga saldo
     * akun, status cicilan, dan relasi tetap persis seperti saat backup.
     */
    public function restore(Workspace $workspace, array $payload): void
    {
        $this->validatePayload($payload);

        $scope = $this->normalizeScope($payload['scope']);
        $tables = $this->tablesFor($scope);

        DB::transaction(function () use ($workspace, $payload, $scope, $tables) {
            // Matikan pengecekan foreign key HANYA saat penghapusan. Penting
            // untuk scope "settings": menghapus akun/kategori tidak boleh
            // meng-cascade transaksi/debt yang tetap dipertahankan.
            $fk = $this->foreignKeyChecks();
            $this->setForeignKeyChecks(false);

            try {
                // 1. Hapus data lama workspace (urut terbalik agar FK aman).
                foreach (array_reverse($tables) as $table) {
                    $this->deleteForWorkspace($table, $workspace->id);
                }
            } finally {
                $this->setForeignKeyChecks($fk);
            }

            // 2. Pulihkan informasi workspace (nama & mata uang).
            $workspace->forceFill([
                'name' => $payload['workspace']['name'],
                'currency' => $payload['workspace']['currency'],
            ])->save();

            // 3. Sisipkan ulang data backup dengan id baru + remapping FK.
            //    Urutan tabel sudah dependency-correct (induk dulu, anak setelah).
            $idMap = [];
            foreach ($tables as $table) {
                foreach ($payload['data'][$table] ?? [] as $row) {
                    $this->insertRemapped($table, $row, $workspace->id, $idMap);
                }
            }

            // 4. Untuk scope settings, data lama (transaksi/debt) dipertahankan
            //    sehingga FK-nya yang mengarah ke tabel master perlu di-remapping
            //    ke id baru yang baru saja dibuat.
            if ($scope === self::SCOPE_SETTINGS) {
                $this->remapRetainedReferences($workspace->id, $idMap);
            }
        });
    }

    /**
     * Sisipkan satu baris sambil memberi id baru dan meremapping foreign key.
     * Id lama -> id baru dicatat di $idMap agar baris anak bisa mengikutinya.
     */
    private function insertRemapped(string $table, array $row, int $workspaceId, array &$idMap): void
    {
        $oldId = isset($row['id']) ? (int) $row['id'] : null;

        // Remap foreign key yang mengarah ke tabel induk.
        foreach (self::FK_MAP[$table] ?? [] as $column => $parentTable) {
            if (isset($row[$column]) && $row[$column] !== null && isset($idMap[$parentTable][(int) $row[$column]])) {
                $row[$column] = $idMap[$parentTable][(int) $row[$column]];
            }
        }

        unset($row['id']);

        // Tabel tanpa id (composite PK) mis. transaction_tag.
        if ($table === 'transaction_tag') {
            DB::table($table)->insert($row);

            return;
        }

        // Tabel lain: id di-generate database. Tabel tanpa workspace_id
        // (mis. debt_installments) tidak diberi kolom workspace_id.
        if (! $this->isParentless($table)) {
            $row['workspace_id'] = $workspaceId;
        }

        $newId = DB::table($table)->insertGetId($row);

        if ($oldId !== null) {
            $idMap[$table][$oldId] = $newId;
        }
    }

    /**
     * Remapping foreign key di tabel lama (data yang dipertahankan) setelah
     * tabel master diganti dengan id baru. Ini menjaga transaksi/debt lama
     * tetap mengarah ke akun/kategori/tag yang baru di-restore.
     */
    private function remapRetainedReferences(int $workspaceId, array $idMap): void
    {
        foreach (self::RETAINED_FK_MAP as $table => $columns) {
            foreach ($columns as $column => $parentTable) {
                if (! isset($idMap[$parentTable])) {
                    continue;
                }

                foreach ($idMap[$parentTable] as $old => $new) {
                    $query = DB::table($table)
                        ->where($column, $old);

                    // transaction_tag tidak punya workspace_id.
                    if ($table !== 'transaction_tag') {
                        $query->where('workspace_id', $workspaceId);
                    }

                    $query->update([$column => $new]);
                }
            }
        }
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
