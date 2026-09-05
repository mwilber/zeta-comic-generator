<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use PDO;
use RuntimeException;

final class DraftRepository implements DraftRepositoryInterface
{
    public function __construct(private readonly PDO $db)
    {
    }

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

    /** @return array<string, mixed>|null */
    public function findActive(string $draftId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM mcp_drafts WHERE draft_id = :draft_id AND expires_at > UTC_TIMESTAMP() LIMIT 1'
        );
        $statement->execute([':draft_id' => $draftId]);
        $draft = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($draft) ? $draft : null;
    }

    /** @return array<string, mixed> */
    public function beginGeneration(string $draftId): array
    {
        $draft = $this->findActive($draftId);
        if (!$draft || !in_array($draft['status'], ['prepared', 'generating'], true)) {
            throw new RuntimeException('This generation request is invalid, expired, or already completed.');
        }

        if ('prepared' === $draft['status']) {
            $statement = $this->db->prepare(
                "UPDATE mcp_drafts SET status = 'generating', updated_at = UTC_TIMESTAMP()
                 WHERE draft_id = :draft_id AND status = 'prepared'"
            );
            $statement->execute([':draft_id' => $draftId]);
        }

        return $draft;
    }

    /** @param array<string, string> $savePayload */
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

    /** @return array{state: string, draft: array<string, mixed>} */
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

    public function completeSave(string $draftId, string $comicId, string $permalink): void
    {
        $statement = $this->db->prepare(
            "UPDATE mcp_drafts
             SET status = 'saved', comic_id = :comic_id, permalink = :permalink,
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

    private function deleteExpired(): void
    {
        $this->db->exec('DELETE FROM mcp_drafts WHERE expires_at <= UTC_TIMESTAMP()');
    }
}
