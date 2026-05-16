<?php
declare(strict_types=1);

class TierModel extends Model
{
    protected static string $table = 'membership_tiers';

    public function allWithParent(int $orgId): array
    {
        return $this->query(
            'SELECT t.*, p.name AS parent_name FROM membership_tiers t
             LEFT JOIN membership_tiers p ON p.id = t.parent_tier_id
             WHERE t.org_id = ? ORDER BY t.sort_order, t.name',
            [$orgId]
        );
    }
}
