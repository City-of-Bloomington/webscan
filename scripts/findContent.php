<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
use Application\Database;
use Application\Content\ContentRepository;

include '../src/Web/bootstrap.php';

$drupal  = Database::getConnection('drupal' );
$wave    = Database::getConnection('default');

$year    = 2017;
$linked  = fopen("./{$year}_linked.csv", 'w');
$unused  = fopen("./{$year}_unused.csv", 'w');

$sql     = "select fid,
                   filename,
                   from_unixtime(changed) as changed
            from file_managed
            where year(from_unixtime(changed))=?
              and filemime like ?";
$query   = $drupal->prepare($sql);
$query->execute([$year, 'application%']);
$files   = $query->fetchAll(\PDO::FETCH_ASSOC);

$content = new ContentRepository();

foreach ($files as $f) {
    $nodes = $content->pages($f['filename']);
    echo "$f[fid] $f[changed] $f[filename] ";

    if (!$nodes) {
        echo "Not Found\n";
        fputcsv($unused, $f);
    }
    else {
        echo "\n";
        foreach ($nodes as $n) {
            $d = array_merge($f, $n);
            fputcsv($linked, $d);
        }
    }
}
