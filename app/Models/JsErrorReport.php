<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\JsErrorReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JsErrorReport extends Model
{
    /** @use HasFactory<JsErrorReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'error_count' => 'integer',
            'first_occurred_at' => 'datetime',
            'last_occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
