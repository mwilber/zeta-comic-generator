<?php

require_once __DIR__.'/../includes/series_repository.php';

// GET /api/series/ lists public series. Supply ?series={permalink}&index=0 for a comic.
$output->model = null;
$output->json = null;
$series = $_GET['series'] ?? null;
$index = $_GET['index'] ?? null;
if (($series !== null || $index !== null) && (
    !is_string($series) || trim($series) === '' || strlen($series) > 255 ||
    !is_string($index) || !preg_match('/^(0|[1-9][0-9]*)$/D', $index) ||
    filter_var($index, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false
)) {
    http_response_code(400);
    $output->error = 'Provide a series permalink and a nonnegative integer index.';
    return;
}

try {
    // Database::getConnection() echoes connection errors; keep them out of the JSON response.
    ob_start();
    try {
        $db = (new Database())->getConnection();
    } finally {
        ob_end_clean();
    }
    if (!$db instanceof PDO) {
        throw new RuntimeException('Database unavailable.');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $repository = new \ZetaComicGenerator\SeriesRepository($db);
    if ($series === null) {
        $output->json = ['series' => $repository->all()];
    } else {
        $comic = $repository->comic($series, (int) $index);
        $output->json = ['found' => $comic !== null, 'series' => $series, 'index' => (int) $index];
        if ($comic !== null) {
            $output->json += $comic;
        }
    }
} catch (Throwable $error) {
    http_response_code(503);
    $output->error = 'Series discovery is temporarily unavailable. Please try again later.';
}
