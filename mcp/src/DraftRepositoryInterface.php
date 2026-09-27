<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

interface DraftRepositoryInterface
{
    /**
     * Creates a temporary draft for the supplied premise and workflow.
     *
     * @param string $premise User-provided comic premise.
     * @param string $workflow Selected generation provider.
     * @return string The draft identifier.
     */
    public function createPrepared(string $premise, string $workflow): string;

    /**
     * Finds an unexpired draft or a permanent saved-comic reference.
     *
     * @param string $draftId Draft identifier.
     * @return array<string, mixed>|null The draft row, or null when unavailable.
     */
    public function findActive(string $draftId): ?array;

    /**
     * Atomically claims a prepared draft; generation can start only once.
     *
     * @param string $draftId Draft identifier.
     * @return array<string, mixed> The draft as read before the status update.
     * @throws \RuntimeException If the draft cannot begin generation.
     */
    public function beginGeneration(string $draftId): array;

    /**
     * Stores a completed save payload, allowing identical staging retries.
     *
     * @param string $draftId Draft identifier.
     * @param array<string, string> $savePayload Validated website save fields.
     * @return void
     * @throws \RuntimeException If the draft is unavailable, changed, or cannot be staged.
     */
    public function stage(string $draftId, array $savePayload): void;

    /**
     * Reserves a ready draft for saving or returns an already saved result.
     *
     * @param string $draftId Draft identifier.
     * @return array{state: string, draft: array<string, mixed>} Reservation state and draft row.
     * @throws \RuntimeException If the draft cannot be reserved.
     */
    public function reserveSave(string $draftId): array;

    /**
     * Keeps a permanent saved-comic reference and discards the temporary payload.
     *
     * @param string $draftId Reserved draft identifier.
     * @param string $comicId Identifier returned by the website save API.
     * @param string $permalink Permanent comic link token.
     * @return void
     */
    public function completeSave(string $draftId, string $comicId, string $permalink): void;

    /**
     * Returns a failed save reservation to the ready state for a later retry.
     *
     * @param string $draftId Reserved draft identifier.
     * @param string $message Failure message to retain for diagnostics.
     * @return void
     */
    public function releaseSave(string $draftId, string $message): void;
}
