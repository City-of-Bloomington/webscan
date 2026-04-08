<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
use Application\Content\ContentRepository;
use Application\Database;
use Application\GrackleGateway;

include '../src/Web/bootstrap.php';

$webscan = Database::getConnection('default');
$content = new ContentRepository();
$grackle = new GrackleGateway($GRACKLE);

$sql     = "insert into grackle_results set path=:path,filename=:filename,url=:url,score=:score,scanned=now()";
$insert  = $webscan->prepare($sql);

/**
 * Drupal PDF files that do not have a grackle score
 */
$sql     = "select f.*
            from drupal.file_managed f
            left join grackle_results g on f.uri=concat('public://', substring(g.url, 48))
            where f.filemime='application/pdf'
              and g.url is null";
$query   = $webscan->query($sql);
foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $r) {
    $internalFilename = substr($r['uri'], 8);
    $pages = $content->pages($internalFilename);
    if (count($pages)) {
        $file = DRUPAL_HOME.'/files'.$internalFilename;
        echo "\t$file\n";
        $json = $grackle->scan($file);
        if (isset($json['conformanceIndex'])) {
            foreach ($pages as $p) {
                $d = [
                    'path'     => $p['alias'],
                    'filename' => basename($internalFilename),
                    'url'      => DRUPAL_SITE.'/sites/default/files'.$internalFilename,
                    'score'    => (int)$json['conformanceIndex']
                ];
                $s = $insert->execute($d);
                if (!$s) {
                    $e = $webscan->errorInfo();
                    print_r($e);
                    print_r($d);
                    exit();
                }
            }
        }
    }
}
