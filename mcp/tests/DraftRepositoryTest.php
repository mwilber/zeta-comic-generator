<?php

declare(strict_types=1);

use ZetaComicGenerator\Mcp\DraftRepository;

require dirname(__DIR__).'/src/DraftRepositoryInterface.php';
require dirname(__DIR__).'/src/DraftRepository.php';

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// SQLite exercises the production claim and retention queries without a live MySQL server.
final class RaceDatabase extends PDO
{
    public bool $competingClaim = false;

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if ($this->competingClaim && str_contains($query, "UPDATE mcp_drafts SET status = 'generating'")) {
            $this->competingClaim = false;
            $this->exec("UPDATE mcp_drafts SET status = 'generating' WHERE draft_id = 'race'");
        }
        return parent::prepare($query, $options);
    }
}

$db = new RaceDatabase('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->sqliteCreateFunction('UTC_TIMESTAMP', fn () => '2026-09-12 12:00:00');
$db->exec('CREATE TABLE mcp_drafts (draft_id TEXT, status TEXT, expires_at TEXT, updated_at TEXT, permalink TEXT)');
$insert = $db->prepare('INSERT INTO mcp_drafts (draft_id, status, expires_at, permalink) VALUES (?, ?, ?, ?)');
foreach ([
    ['new', 'prepared', '2026-09-13', null],
    ['race', 'prepared', '2026-09-13', null],
    ['expired', 'prepared', '2026-09-01', null],
    ['old-save', 'saved', '2026-09-01', str_repeat('a', 32)],
    ['ready', 'ready', '2026-09-13', null],
    ['saving', 'saving', '2026-09-13', null],
] as $row) $insert->execute($row);
$repository = new DraftRepository($db);
check('prepared' === $repository->beginGeneration('new')['status'], 'First claim must succeed.');
check('generating' === $repository->findActive('new')['status'], 'Claim must persist before returning.');
foreach (['new', 'expired', 'old-save', 'missing', 'ready', 'saving'] as $id) {
    $rejected = false;
    try { $repository->beginGeneration($id); } catch (RuntimeException) { $rejected = true; }
    check($rejected, 'Only prepared active drafts can start: '.$id);
}
$db->competingClaim = true;
$rejected = false;
try { $repository->beginGeneration('race'); } catch (RuntimeException) { $rejected = true; }
check($rejected, 'A competing claim between lookup and update must prevent a second generation.');
check(null === $repository->findActive('expired'), 'Expired unsaved drafts must be unavailable.');
check(str_repeat('a', 32) === $repository->findActive('old-save')['permalink'], 'Saved references must survive expiry.');
$cleanup = new ReflectionMethod($repository, 'deleteExpired');
$cleanup->invoke($repository);
check(0 === (int) $db->query("SELECT COUNT(*) FROM mcp_drafts WHERE draft_id = 'expired'")->fetchColumn(), 'Cleanup must remove expired unsaved drafts.');
check(null !== $repository->findActive('old-save'), 'Cleanup must retain the saved permalink.');
echo "DraftRepository claim and retention tests passed.\n";
