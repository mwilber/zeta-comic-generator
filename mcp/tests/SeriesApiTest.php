<?php

declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// Exercise the real controller and SQL without website credentials or a MySQL service.
final class Database
{
    public static PDO $db;
    public static bool $fail = false;
    public static int $calls = 0;

    public function getConnection(): ?PDO
    {
        ++self::$calls;
        if (self::$fail) {
            echo 'Private database connection details';
            return null;
        }
        return self::$db;
    }
}

function request(array $query): object
{
    $_GET = $query;
    http_response_code(200);
    $output = (object) ['error' => ''];
    ob_start();
    require dirname(__DIR__, 2).'/api/controllers/series.php';
    $unexpected = ob_get_clean();
    check($unexpected === '', 'Controller must not emit non-JSON output or private diagnostics.');
    return $output;
}

$db = Database::$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE series (id INTEGER PRIMARY KEY, permalink TEXT, title TEXT, premise TEXT, active INTEGER, timestamp TEXT)');
$db->exec('CREATE TABLE comics (id INTEGER PRIMARY KEY, permalink TEXT, title TEXT, summary TEXT, seriesId INTEGER, gallery INTEGER, timestamp TEXT)');
check([] === request([])->json['series'], 'Empty series must return an empty list.');
$insert = $db->prepare('INSERT INTO series VALUES (?, ?, ?, ?, ?, ?)');
foreach ([
    [9, 'adventures', 'Adventures', 'Alpha explores space & time.', 1, '2026-09-01'],
    [10, 'hidden', 'Hidden', 'Private premise', 0, '2026-09-03'],
    [11, 'empty', 'Empty', null, 1, '2026-09-04'],
    [12, 'unpublished', 'Unpublished', '', 1, '2026-09-05'],
    [13, 'newer', 'Newer', '', 1, '2026-09-02'],
] as $row) $insert->execute($row);
$insert = $db->prepare('INSERT INTO comics VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ([
    [20, md5('20'), 'Newest', 'Newest summary', 9, 1, '2026-09-03'],
    [4, md5('4'), 'Tied second', '', 9, 1, '2026-09-01'],
    [3, md5('3'), 'Oldest', null, 9, 1, '2026-09-01'],
    [1, md5('1'), 'Unpublished oldest', 'Secret', 9, 0, '2026-08-01'],
    [2, md5('2'), 'Inactive series comic', 'Secret', 10, 1, '2026-09-01'],
    [5, md5('5'), 'Unpublished series comic', 'Secret', 12, 0, '2026-09-01'],
    [6, md5('6'), 'Other series', 'Another', 13, 1, '2026-09-01'],
] as $row) $insert->execute($row);
$result = request([]);
check(['newer', 'adventures'] === array_column($result->json['series'], 'permalink'), 'List must match public site visibility and series ordering, without duplicates.');
check(3 === $result->json['series'][1]['comic_count'], 'Count must exclude unpublished comics.');
check('Alpha explores space & time.' === $result->json['series'][1]['description'], 'Description must be the exact website premise.');
check('' === $result->json['series'][0]['description'], 'Empty descriptions must be preserved.');
foreach ([3, 4, 20] as $index => $id) {
    $result = request(['series' => 'adventures', 'index' => (string) $index]);
    check($result->json['found'] && md5((string) $id) === $result->json['permalink'], 'Index must use oldest-first timestamp ordering with ID tie-breaker.');
    check($index === $result->json['index'], 'Index must be returned as an integer.');
    if ($index === 0) check(null === $result->json['summary'], 'Null comic summary must be preserved.');
}
foreach ([['adventures', '3'], ['missing', '0'], ['hidden', '0'], ['empty', '0'], ['unpublished', '0'], ["adventures' OR 1=1 --", '0']] as [$series, $index]) {
    $result = request(compact('series', 'index'));
    check($result->error === '' && !$result->json['found'] && !isset($result->json['permalink']), 'Unavailable series/index must return found=false and no comic.');
}
$calls = Database::$calls;
foreach ([['index' => '0'], ['series' => 'adventures'], ['series' => '', 'index' => '0'], ['series' => ['adventures'], 'index' => '0']] as $query) {
    check(request($query)->error !== '' && http_response_code() === 400, 'Invalid series query must return HTTP 400.');
}
foreach (['-1', '1.5', 'abc', '01', "0\n", str_repeat('9', 30), ['0']] as $index) {
    check(request(['series' => 'adventures', 'index' => $index])->error !== '' && http_response_code() === 400, 'Invalid index must return HTTP 400.');
}
check($calls === Database::$calls, 'Invalid requests must not access the database.');
check(7 === (int) $db->query('SELECT COUNT(*) FROM comics')->fetchColumn(), 'Discovery must not modify comics.');
Database::$fail = true;
$result = request([]);
check(http_response_code() === 503 && $result->json === null && !str_contains($result->error, 'Private'), 'Database failure must return sanitized HTTP 503.');
echo "Series API tests passed.\n";
