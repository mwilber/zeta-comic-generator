<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

interface DraftRepositoryInterface
{
    public function createPrepared(string $premise, string $workflow): string;

    /** @return array<string, mixed>|null */
    public function findActive(string $draftId): ?array;

    /** @return array<string, mixed> */
    public function beginGeneration(string $draftId): array;

    /** @param array<string, string> $savePayload */
    public function stage(string $draftId, array $savePayload): void;

    /** @return array{state: string, draft: array<string, mixed>} */
    public function reserveSave(string $draftId): array;

    public function completeSave(string $draftId, string $comicId, string $permalink): void;

    public function releaseSave(string $draftId, string $message): void;
}
