<?php

namespace App\DTOs;

final class SyncBatchResult
{
    /**
     * @param  list<string>  $successMessages
     * @param  array<string, int>  $errorCounts
     * @param  list<array{subject: string, message: string}>  $failedItems
     */
    public function __construct(
        public int $successCount = 0,
        public int $failedCount = 0,
        public array $successMessages = [],
        public array $errorCounts = [],
        public array $failedItems = [],
    ) {}

    public function recordFailure(string $subject, string $message): void
    {
        $this->failedCount++;
        $this->errorCounts[$message] = ($this->errorCounts[$message] ?? 0) + 1;
        $this->failedItems[] = [
            'subject' => $subject,
            'message' => $message,
        ];
    }

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
