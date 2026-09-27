<?php

declare(strict_types=1);

use ZetaComicGenerator\Mcp\ComicRepository;

require dirname(__DIR__).'/src/ComicRepository.php';

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// Exercise the production SQL locally, supplying deterministic MySQL RAND values.
$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$randomValues = [];
$db->sqliteCreateFunction('RAND', function () use (&$randomValues): float {
    if ([] === $randomValues) throw new RuntimeException('Unexpected random candidate.');
    return array_shift($randomValues);
});
$db->exec('CREATE TABLE comics (id INTEGER PRIMARY KEY, permalink TEXT, title TEXT, timestamp TEXT, gallery INTEGER, seriesId INTEGER, summary TEXT)');
$repository = new ComicRepository($db);
check(null === $repository->latest(), 'Empty gallery must have no latest comic.');
check(null === $repository->random(), 'Empty gallery must have no random comic.');
$insert = $db->prepare('INSERT INTO comics (id, permalink, title, timestamp, gallery, seriesId) VALUES (?, ?, ?, ?, ?, ?)');
foreach ([
    [1, md5('1'), 'Old standalone', '2026-09-01', 1, 0],
    [2, md5('2'), 'New series five', '2026-09-02', 1, 5],
    [3, md5('3'), 'Tied newest standalone', '2026-09-02', 1, 0],
    [4, md5('4'), 'Newer hidden comic', '2026-09-04', 0, 0],
    [5, md5('5'), 'Other series', '2026-09-05', 1, 9],
] as $row) $insert->execute($row);
check(md5('3') === $repository->latest()['permalink'], 'Latest must use timestamp, break ties by ID, and follow gallery eligibility.');
$summaries = [1 => null, 2 => '', 3 => "Alpha's discovery: \"space coffee\"."];
$update = $db->prepare('UPDATE comics SET summary = ? WHERE id = ?');
foreach ($summaries as $id => $summary) $update->execute([$summary, $id]);
check($summaries[3] === $repository->latest()['summary'], 'Latest must retrieve the stored summary unchanged.');

// Force each eligible row to win once: randomness covers older comics and series 5.
foreach ([1, 2, 3] as $winner) {
    $randomValues = [0.9, 0.9, 0.9];
    $randomValues[$winner - 1] = 0.1;
    $comic = $repository->random();
    check(md5((string) $winner) === $comic['permalink'], 'Random selection missed an eligible comic.');
    check($summaries[$winner] === $comic['summary'], 'Random must preserve summaries including null and empty values.');
    check([] === $randomValues, 'Random must consider all eligible comics exactly once.');
}
check(5 === (int) $db->query('SELECT COUNT(*) FROM comics')->fetchColumn(), 'Discovery must not modify comics.');
$db->exec('DELETE FROM comics WHERE id <= 3');
check(null === $repository->latest() && null === $repository->random(), 'Hidden comics and other series must not become discovery fallbacks.');
echo "ComicRepository discovery tests passed.\n";
