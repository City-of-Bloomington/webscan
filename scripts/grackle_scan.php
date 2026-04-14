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


$query   = $webscan->prepare('select * from grackle_results where path=? and url=?');

$q = $webscan->query("select * from drupal.file_managed where filemime='application/pdf'");
foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $f) {
    $internalFilename = substr($f['uri'], 8);
    $pages = $content->pages($internalFilename);
    if (count($pages)) {
        $file = DRUPAL_HOME.'/files'.$internalFilename;
        $url  = DRUPAL_SITE.'/sites/default/files'.$internalFilename;

        echo "\t$file\n";
        $score = null;
        foreach ($pages as $p) {
            $path = $p['alias'];
            $query->execute([$path, $url]);
            // Does this page already have scores
            $s = $query->fetchAll(\PDO::FETCH_ASSOC);
            if (count($s)) { relink_pdf_results($path, $url); }
            else {
                if (!$score) {
                    $json = $grackle->scan($file);
                    if (isset($json['conformanceIndex'])) {
                        $score = (int)$json['conformanceIndex'];
                    }
                }
                $d = [
                    'path'     => $path,
                    'filename' => basename($internalFilename),
                    'url'      => $url,
                    'score'    => $score
                ];
                print_r($d);
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


function relink_pdf_results(string $path, string $url)
{
    $webscan = Database::getConnection('default');
    $update  = $webscan->prepare('update grackle_results set unlinked=0 where path=? and url=?');
    $s       = $update->execute([$path, $url]);
    if (!$s) {
        $e = $webscan->errorInfo();
        print_r($e);
        echo "$path\n$url\n";
        exit();
    }
}
