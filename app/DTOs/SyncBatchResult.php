<?php

namespace App\DTOs;

final class SyncBatchResult
{
    /**
     * @param  list<string>  $successMessages
     * @param  array<string, int>  $errorCounts
     */
    public function __construct(
        public int $successCount = 0,
        public int $failedCount = 0,
        public array $successMessages = [],
        public array $errorCounts = [],
    ) {}

    /**
     * @return list<string>
     */
    public function errorSummary(): array
    {
        $lines = [];
        foreach ($this->errorCounts as $message => $count) {
            $lines[] = $count > 1 ? "{$message} (×{$count})" : $message;
        }

        return $lines;
    }

    public function flashSuccess(): ?string
    {
        if ($this->successCount === 0) {
            return null;
        }

        $preview = array_slice($this->successMessages, 0, 5);
        $suffix = count($this->successMessages) > 5 ? ' …' : '';

        return "{$this->successCount} berhasil. ".implode('; ', $preview).$suffix;
    }

    public function flashError(): ?string
    {
        if ($this->failedCount === 0) {
            return null;
        }

        return "{$this->failedCount} gagal. ".implode('; ', $this->errorSummary());
    }
}
