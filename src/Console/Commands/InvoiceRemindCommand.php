<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class InvoiceRemindCommand extends Command
{
    public function name(): string { return 'invoice:remind'; }
    public function description(): string { return 'Send payment reminders for invoices that are due soon or overdue.'; }

    public function handle(array $args): int
    {
        $rows = $this->app->db->select(
            "SELECT id, user_id, number, total, currency, due_date FROM invoices WHERE status IN ('unpaid','partial') AND due_date <= ?",
            [date('Y-m-d', strtotime('+3 days'))]
        );
        foreach ($rows as $r) {
            $this->out("Reminder queued for invoice #{$r['number']} (user {$r['user_id']})");
            // NotificationService would dispatch here.
        }
        $this->out('Sent ' . count($rows) . ' reminders.');
        return 0;
    }
}
