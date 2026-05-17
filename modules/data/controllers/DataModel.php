<?php
declare(strict_types=1);

class DataModel extends Model
{
    protected static string $table = 'contacts';

    // Tables per module in dependency order (parents before children)
    public const MODULE_TABLES = [
        'crm'        => ['contacts', 'contact_tags', 'contact_notes', 'board_positions'],
        'membership' => ['membership_tiers', 'memberships', 'dues_payments', 'membership_history'],
        'donors'     => ['campaigns', 'donations'],
        'volunteers' => ['volunteer_shifts', 'volunteers', 'volunteer_hours'],
        'events'     => ['events', 'event_registrations'],
        'grants'     => ['funders', 'grants', 'grant_reports'],
    ];

    // Modules whose activity generates transaction rows
    private const FINANCIAL_MODULES = ['donors', 'membership', 'events', 'grants'];

    // FK column → referenced table; null = nullify on restore (user ref, etc.)
    private const FK_MAP = [
        'contact_tags'        => ['contact_id' => 'contacts'],
        'contact_notes'       => ['contact_id' => 'contacts', 'user_id' => null],
        'board_positions'     => ['contact_id' => 'contacts'],
        'memberships'         => ['contact_id' => 'contacts', 'tier_id' => 'membership_tiers'],
        'dues_payments'       => ['membership_id' => 'memberships', 'contact_id' => 'contacts'],
        'membership_history'  => ['membership_id' => 'memberships', 'contact_id' => 'contacts'],
        'donations'           => ['contact_id' => 'contacts', 'campaign_id' => 'campaigns'],
        'volunteers'          => ['contact_id' => 'contacts'],
        'volunteer_hours'     => ['volunteer_id' => 'volunteers', 'shift_id' => 'volunteer_shifts', 'approved_by' => null],
        'event_registrations' => ['event_id' => 'events', 'contact_id' => 'contacts'],
        'grants'              => ['funder_id' => 'funders'],
        'grant_reports'       => ['grant_id' => 'grants'],
        'transactions'        => ['contact_id' => 'contacts'],
    ];

    // Self-referential FK columns: inserted NULL first, then patched
    private const SELF_REF = [
        'contacts'         => 'merged_into_id',
        'membership_tiers' => 'parent_tier_id',
    ];

    public function backup(int $orgId, array $modules): array
    {
        $data = [];
        foreach ($modules as $mod) {
            if (!isset(self::MODULE_TABLES[$mod])) continue;
            $data[$mod] = [];
            foreach (self::MODULE_TABLES[$mod] as $table) {
                $data[$mod][$table] = $this->query("SELECT * FROM `$table` WHERE org_id = ?", [$orgId]);
            }
        }

        // Transactions: include when any financial module is in the backup
        if (array_intersect($modules, self::FINANCIAL_MODULES)) {
            $data['_transactions'] = $this->query(
                'SELECT * FROM transactions WHERE org_id = ? ORDER BY transaction_date',
                [$orgId]
            );
        }

        return $data;
    }

    public function wipe(int $orgId, array $modules): array
    {
        $db = Database::getInstance();
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $counts = [];

        // Delete in reverse dependency order
        $allTables = [];
        foreach (array_reverse($modules) as $mod) {
            if (!isset(self::MODULE_TABLES[$mod])) continue;
            foreach (array_reverse(self::MODULE_TABLES[$mod]) as $table) {
                $allTables[] = $table;
            }
        }

        // Include transactions when wiping any financial module
        if (array_intersect($modules, self::FINANCIAL_MODULES)) {
            array_unshift($allTables, 'transactions');
        }

        foreach (array_unique($allTables) as $table) {
            $stmt = $db->prepare("DELETE FROM `$table` WHERE org_id = ?");
            $stmt->execute([$orgId]);
            $counts[$table] = $stmt->rowCount();
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
        return $counts;
    }

    public function restore(int $orgId, array $backupData, array $modules): array
    {
        $db = Database::getInstance();
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');

        $idMap  = [];   // $idMap['contacts'][42] = 87
        $counts = [];
        $errors = [];

        foreach ($modules as $mod) {
            if (!isset(self::MODULE_TABLES[$mod], $backupData[$mod])) continue;

            foreach (self::MODULE_TABLES[$mod] as $table) {
                if (empty($backupData[$mod][$table])) {
                    $counts[$table] = 0;
                    continue;
                }
                [$inserted, $errs] = $this->restoreTable($db, $orgId, $table, $backupData[$mod][$table], $idMap);
                $counts[$table] = $inserted;
                $errors = array_merge($errors, $errs);
            }
        }

        // Restore transactions last (source_id remapping is cross-module)
        if (!empty($backupData['_transactions']) && array_intersect($modules, self::FINANCIAL_MODULES)) {
            $srcMap = [
                'donation'           => 'donations',
                'dues_payment'       => 'dues_payments',
                'event_registration' => 'event_registrations',
                'grant'              => 'grants',
            ];
            $inserted = 0;
            foreach ($backupData['_transactions'] as $row) {
                $oldId = (int)$row['id'];
                unset($row['id'], $row['created_at'], $row['updated_at']);
                $row['org_id'] = $orgId;

                $refTable = $srcMap[$row['source_type']] ?? null;
                if ($refTable && !empty($row['source_id'])) {
                    $row['source_id'] = $idMap[$refTable][(int)$row['source_id']] ?? null;
                }
                if (!empty($row['contact_id'])) {
                    $row['contact_id'] = $idMap['contacts'][(int)$row['contact_id']] ?? null;
                }

                if (empty($row['source_id'])) continue; // can't restore without valid source

                try {
                    $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($row)));
                    $phds = implode(', ', array_fill(0, count($row), '?'));
                    $db->prepare("INSERT INTO transactions ($cols) VALUES ($phds)")->execute(array_values($row));
                    $idMap['transactions'][$oldId] = (int)$db->lastInsertId();
                    $inserted++;
                } catch (Throwable $e) {
                    $errors[] = "transactions row $oldId: " . $e->getMessage();
                }
            }
            $counts['transactions'] = $inserted;
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
        return compact('counts', 'errors');
    }

    private function restoreTable(\PDO $db, int $orgId, string $table, array $rows, array &$idMap): array
    {
        $idMap[$table] = $idMap[$table] ?? [];
        $fks           = self::FK_MAP[$table]  ?? [];
        $selfRefCol    = self::SELF_REF[$table] ?? null;
        $selfRefPatches = [];
        $inserted = 0;
        $errors   = [];

        foreach ($rows as $row) {
            $oldId = (int)$row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['org_id'] = $orgId;

            // Defer self-referential FK
            if ($selfRefCol) {
                $selfRefPatches[$oldId] = $row[$selfRefCol] ?? null;
                $row[$selfRefCol] = null;
            }

            // Remap foreign keys
            foreach ($fks as $col => $refTable) {
                if ($refTable === null) {
                    $row[$col] = null;
                } elseif (!empty($row[$col])) {
                    $row[$col] = $idMap[$refTable][(int)$row[$col]] ?? null;
                }
            }

            $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($row)));
            $phds = implode(', ', array_fill(0, count($row), '?'));

            try {
                $db->prepare("INSERT INTO `$table` ($cols) VALUES ($phds)")->execute(array_values($row));
                $newId = (int)$db->lastInsertId();
                $idMap[$table][$oldId] = $newId;
                $inserted++;
            } catch (Throwable $e) {
                $errors[] = "$table (row old_id=$oldId): " . $e->getMessage();
            }
        }

        // Patch self-referential FKs now that all rows are inserted
        if ($selfRefCol) {
            foreach ($selfRefPatches as $oldId => $selfOldVal) {
                if ($selfOldVal === null) continue;
                $newSelfVal = $idMap[$table][(int)$selfOldVal] ?? null;
                $newId      = $idMap[$table][$oldId]           ?? null;
                if (!$newSelfVal || !$newId) continue;
                $db->prepare("UPDATE `$table` SET `$selfRefCol` = ? WHERE id = ?")->execute([$newSelfVal, $newId]);
            }
        }

        return [$inserted, $errors];
    }

    public function rowCounts(int $orgId, array $modules): array
    {
        $counts = [];
        $financialIncluded = false;
        foreach ($modules as $mod) {
            if (!isset(self::MODULE_TABLES[$mod])) continue;
            foreach (self::MODULE_TABLES[$mod] as $table) {
                $counts[$table] = (int)$this->scalar("SELECT COUNT(*) FROM `$table` WHERE org_id = ?", [$orgId]);
            }
            if (in_array($mod, self::FINANCIAL_MODULES)) $financialIncluded = true;
        }
        if ($financialIncluded) {
            $counts['transactions'] = (int)$this->scalar('SELECT COUNT(*) FROM transactions WHERE org_id = ?', [$orgId]);
        }
        return $counts;
    }
}
