<?php

declare(strict_types=1);

namespace App\Models;

final class Invoice extends BaseModel
{
    protected static string $table = 'invoices';

    public static function generateNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /** @return InvoiceItem[] */
    public function items(): array
    {
        return InvoiceItem::where('invoice_id = :iid', ['iid' => $this->id]);
    }
}
