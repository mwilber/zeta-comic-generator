<?php

declare(strict_types=1);

namespace ZetaComicGenerator;

use PDO;

/** Public series use the same visibility rules as the production series page. */
final class SeriesRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(): array
    {
        $statement = $this->db->prepare(
            'SELECT s.permalink, s.title, s.premise AS description,
                    (SELECT COUNT(*) FROM comics c WHERE c.seriesId = s.id AND c.gallery = 1) AS comic_count
             FROM series s
             WHERE s.active = 1 AND EXISTS (SELECT 1 FROM comics c WHERE c.seriesId = s.id AND c.gallery = 1)
             ORDER BY s.timestamp DESC, s.id DESC'
        );
        $statement->execute();
        $series = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($series as &$row) {
            $row['comic_count'] = (int) $row['comic_count'];
        }
        return $series;
    }

    /** Index zero is the oldest published comic; IDs make timestamp ties deterministic. */
    public function comic(string $series, int $index): ?array
    {
        if ($index < 0) {
            throw new \InvalidArgumentException('Comic index must be nonnegative.');
        }
        $statement = $this->db->prepare(
            'SELECT c.permalink, c.title, c.summary
             FROM comics c INNER JOIN series s ON s.id = c.seriesId
             WHERE s.active = 1 AND s.permalink = :series AND c.gallery = 1
             ORDER BY c.timestamp ASC, c.id ASC LIMIT 1 OFFSET :index'
        );
        $statement->bindValue(':series', $series, PDO::PARAM_STR);
        $statement->bindValue(':index', $index, PDO::PARAM_INT);
        $statement->execute();
        $comic = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($comic) ? $comic : null;
    }
}
