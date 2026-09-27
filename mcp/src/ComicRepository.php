<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use PDO;

interface ComicRepositoryInterface
{
    /** @return array{permalink: string, title: string, summary: ?string}|null Most recent public gallery comic. */
    public function latest(): ?array;

    /** @return array{permalink: string, title: string, summary: ?string}|null Random public gallery comic. */
    public function random(): ?array;
}

final class ComicRepository implements ComicRepositoryInterface
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function latest(): ?array
    {
        return $this->selectOne('`timestamp` DESC, `id` DESC');
    }

    public function random(): ?array
    {
        return $this->selectOne('RAND()');
    }

    /**
     * Uses the same eligibility rules as views/home.php and the public gallery.
     * The sort expression is supplied only by the two methods above, never by input.
     *
     * @return array{permalink: string, title: string, summary: ?string}|null
     */
    private function selectOne(string $order): ?array
    {
        $statement = $this->db->prepare(
            'SELECT `permalink`, `title`, `summary` FROM `comics`
             WHERE `gallery` = 1 AND (`seriesId` = 0 OR `seriesId` = 5)
             ORDER BY '.$order.' LIMIT 1'
        );
        $statement->execute();
        $comic = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($comic) ? $comic : null;
    }
}
