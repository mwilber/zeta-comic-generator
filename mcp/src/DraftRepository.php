<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use PDO;
use RuntimeException;

final class DraftRepository implements DraftRepositoryInterface
{
    /**
     * Initializes MySQL-backed temporary draft storage.
     *
     * @param PDO $db Database connection configured to throw exceptions.
     */
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Creates a temporary draft for the supplied premise and workflow.
     *
     * @param string $premise User-provided comic premise.
     * @param string $workflow Selected generation provider.
     * @return string The draft identifier.
     */
    public function createPrepared(string $premise, string $workflow): string
    {
        $this->deleteExpired();
        $draftId = bin2hex(random_bytes(16));
        $statement = $this->db->prepare(
            "INSERT INTO mcp_drafts
                (draft_id, premise, workflow, status, created_at, updated_at, expires_at)
             VALUES
                (:draft_id, :premise, :workflow, 'prepared', UTC_TIMESTAMP(), UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 HOUR))"
        );
        $statement->execute([
            ':draft_id' => $draftId,
            ':premise' => $premise,
            ':workflow' => $workflow,
        ]);

        return $draftId;
    }

    /**
     * Finds an unexpired draft or a permanent saved-comic reference.
     *
     * @param string $draftId Draft identifier.
     * @return array<string, mixed>|null The draft row, or null when unavailable.
     */
    public function findActive(string $draftId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM mcp_drafts WHERE draft_id = :draft_id AND (expires_at > UTC_TIMESTAMP() OR status = 'saved') LIMIT 1"
        );
        $statement->execute([':draft_id' => $draftId]);
        $draft = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($draft) ? $draft : null;
    }

    /**
     * Atomically claims a prepared draft; generation can start only once.
     *
     * @param string $draftId Draft identifier.
     * @return array<string, mixed> The draft before the status update.
     * @throws RuntimeException If this request cannot claim generation.
     */
    public function beginGeneration(string $draftId): array
    {
        $draft = $this->findActive($draftId);
        if (!$draft || 'prepared' !== $draft['status']) {
            throw new RuntimeException('This generation request is invalid, expired, or already started.');
        }

        $statement = $this->db->prepare(
            "UPDATE mcp_drafts SET status = 'generating', updated_at = UTC_TIMESTAMP()
             WHERE draft_id = :draft_id AND status = 'prepared' AND expires_at > UTC_TIMESTAMP()"
        );
        $statement->execute([':draft_id' => $draftId]);
        if (1 !== $statement->rowCount()) {
            throw new RuntimeException('This generation request has already started or expired.');
        }

        return $draft;
    }

    /**
     * Stores a completed save payload, allowing identical staging retries.
     *
     * @param string $draftId Draft identifier.
     * @param array<string, string> $savePayload Validated website save fields.
     * @return void
     * @throws RuntimeException If the draft is unavailable, changed, or cannot be staged.
     */
    public function stage(string $draftId, array $savePayload): void
    {
        $draft = $this->findActive($draftId);
        if (!$draft || !in_array($draft['status'], ['generating', 'ready'], true)) {
            throw new RuntimeException('This draft is invalid, expired, or cannot be staged.');
        }

        $encoded = json_encode($savePayload, JSON_THROW_ON_ERROR);
        if ('ready' === $draft['status'] && hash_equals((string) $draft['save_payload'], $encoded)) {
            return;
        }
        if ('ready' === $draft['status']) {
            throw new RuntimeException('This completed draft cannot be replaced.');
        }

        $statement = $this->db->prepare(
            "UPDATE mcp_drafts
             SET save_payload = :save_payload, status = 'ready', updated_at = UTC_TIMESTAMP()
             WHERE draft_id = :draft_id AND status = 'generating'"
        );
        $statement->execute([':save_payload' => $encoded, ':draft_id' => $draftId]);
        if (1 !== $statement->rowCount()) {
            throw new RuntimeException('The draft could not be staged.');
        }
    }

    /**
     * Reserves a ready draft, reclaims a stale save, or returns an existing save.
     *
     * @param string $draftId Draft identifier.
     * @return array{state: string, draft: array<string, mixed>} Reservation state and draft row.
     * @throws RuntimeException If the draft is unavailable or another save is still active.
     */
    public function reserveSave(string $draftId): array
    {
        $draft = $this->findActive($draftId);
        if (!$draft) {
            throw new RuntimeException('This comic draft is invalid or expired.');
        }
        if ('saved' === $draft['status'] && $draft['permalink']) {
            return ['state' => 'saved', 'draft' => $draft];
        }
        if ('saving' === $draft['status']) {
            $statement = $this->db->prepare(
                "UPDATE mcp_drafts SET updated_at = UTC_TIMESTAMP()
                 WHERE draft_id = :draft_id AND status = 'saving'
                   AND updated_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE)"
            );
            $statement->execute([':draft_id' => $draftId]);
            if (1 === $statement->rowCount()) {
                return ['state' => 'reserved', 'draft' => $draft];
            }
        }
        if ('ready' !== $draft['status']) {
            throw new RuntimeException(
                'saving' === $draft['status']
                    ? 'This comic is already being saved. Please retry shortly.'
                    : 'This comic has not finished generating yet.'
            );
        }

        $statement = $this->db->prepare(
            "UPDATE mcp_drafts SET status = 'saving', updated_at = UTC_TIMESTAMP()
             WHERE draft_id = :draft_id AND status = 'ready'"
        );
        $statement->execute([':draft_id' => $draftId]);
        if (1 !== $statement->rowCount()) {
            throw new RuntimeException('This comic is already being saved. Please retry shortly.');
        }

        return ['state' => 'reserved', 'draft' => $draft];
    }

    /**
     * Keeps a permanent saved-comic reference and discards the temporary payload.
     *
     * @param string $draftId Reserved draft identifier.
     * @param string $comicId Identifier returned by the website save API.
     * @param string $permalink Permanent comic link token.
     * @return void
     */
    public function completeSave(string $draftId, string $comicId, string $permalink): void
    {
        $statement = $this->db->prepare(
            "UPDATE mcp_drafts
             SET status = 'saved', comic_id = :comic_id, permalink = :permalink, save_payload = NULL,
                 saved_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP(),
                 expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY)
             WHERE draft_id = :draft_id AND status = 'saving'"
        );
        $statement->execute([
            ':comic_id' => $comicId,
            ':permalink' => $permalink,
            ':draft_id' => $draftId,
        ]);
    }

    /**
     * Returns a failed save reservation to the ready state for a later retry.
     *
     * @param string $draftId Reserved draft identifier.
     * @param string $message Failure message to retain for diagnostics.
     * @return void
     */
    public function releaseSave(string $draftId, string $message): void
    {
        $statement = $this->db->prepare(
            "UPDATE mcp_drafts
             SET status = 'ready', error_message = :message, updated_at = UTC_TIMESTAMP()
             WHERE draft_id = :draft_id AND status = 'saving'"
        );
        $statement->execute([
            ':message' => mb_substr($message, 0, 255),
            ':draft_id' => $draftId,
        ]);
    }

    /**
     * Deletes expired unsaved drafts, preserving saved permalink references.
     *
     * @return void
     */
    private function deleteExpired(): void
    {
        $this->db->exec("DELETE FROM mcp_drafts WHERE expires_at <= UTC_TIMESTAMP() AND status <> 'saved'");
    }
}
