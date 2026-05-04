<?php

declare(strict_types=1);

namespace App\Models;

final class Ticket extends BaseModel
{
    protected static string $table = 'tickets';

    public static function generateNumber(): string
    {
        return 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /** @return TicketReply[] */
    public function replies(): array
    {
        return TicketReply::where('ticket_id = :tid ORDER BY id ASC', ['tid' => $this->id]);
    }
}
