<?php
declare(strict_types=1);

function fmt_date(?string $date, string $format = 'M j, Y'): string
{
    if (!$date || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
}

function fmt_datetime(?string $dt): string
{
    return fmt_date($dt, 'M j, Y g:i a');
}

function humanize_action(string $action): string
{
    static $map = [
        'membership.create'   => 'Created Membership',
        'membership.update'   => 'Updated Membership',
        'dues.payment'        => 'Recorded Dues Payment',
        'dues.update'         => 'Updated Dues Payment',
        'donation.create'     => 'Recorded Donation',
        'donation.update'     => 'Updated Donation',
        'campaign.create'     => 'Created Campaign',
        'campaign.update'     => 'Updated Campaign',
        'tier.create'         => 'Created Membership Tier',
        'tier.update'         => 'Updated Tier',
        'contact.create'      => 'Added Contact',
        'contact.update'      => 'Updated Contact',
        'contact.delete'      => 'Deleted Contact',
        'contact.merge'       => 'Merged Contacts',
        'note.create'         => 'Added Note',
        'volunteer.create'    => 'Added Volunteer',
        'volunteer.update'    => 'Updated Volunteer',
        'hours.log'           => 'Logged Hours',
        'hours.approve'       => 'Approved Hours',
        'hours.reject'        => 'Rejected Hours',
        'shift.create'        => 'Created Shift',
        'event.create'        => 'Created Event',
        'event.update'        => 'Updated Event',
        'event.register'      => 'Registered Attendee',
        'event.checkin'       => 'Checked In Attendee',
        'event.cancel'        => 'Cancelled Registration',
        'grant.create'        => 'Created Grant',
        'grant.update'        => 'Updated Grant',
        'grant.award'         => 'Awarded Grant',
        'grant.report'        => 'Added Grant Report',
        'grant.report_submit' => 'Submitted Grant Report',
        'funder.create'       => 'Added Funder',
        'funder.update'       => 'Updated Funder',
        'settings.org_update' => 'Updated Org Settings',
        'settings.import'     => 'Imported Settings',
        'user.create'         => 'Created User',
        'user.deactivate'     => 'Deactivated User',
        'user.role_change'    => 'Changed User Role',
    ];
    return $map[$action] ?? ucwords(str_replace(['.', '_'], ' ', $action));
}
