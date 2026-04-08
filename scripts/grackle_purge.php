<?php
/**
 * Marks files that are no longer linked on the website
 *
 * For webscan, we only care about Drupal files that are actually linked.
 * Drupal files stick around because they were linked in past revisions of pages.
 * Drupal does not delete these file in case someone wants to revert to a previous
 * revision.  Still, we do not want past files to be counted as problems.
 *
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
use Application\Content\ContentRepository;
use Application\Database;

include '../src/Web/bootstrap.php';

$webscan = Database::getConnection('default');
$content = new ContentRepository();
$update  = $webscan->prepare('update grackle_results set unlinked=1 where url=?');

/**
 * Grackle results for files that are not linked in the current revision.
 * These files are still linked in past revisions
 */
$sql     = "select f.fid, g.path, g.filename, g.url, g.score, g.scanned,
                   substring(g.url, 47) as internalFilename
            from      grackle_results     g
            left join drupal.file_managed f on f.uri=concat('public://', substring(g.url, 48))
            where f.fid is not null
              and left(g.url, 46)='https://bloomington.in.gov/sites/default/files'";
$query   = $webscan->query($sql);
foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $r) {
    $pages = $content->pages($r['internalFilename']);
    if (!count($pages)) {
        echo "$r[fid],$r[internalFilename]\n";
        $update->execute([$r['url']]);
    }
}
