<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

use Application\Content\ContentRepository;
use Application\Database;
use Application\GrackleGateway;
use Application\WaveGateway;

include '../src/Web/bootstrap.php';

$content = new ContentRepository();
$api     = new WaveGateway(['api_key' => WAVE_API_KEY]);
$grackle = new GrackleGateway($GRACKLE);
$drupal  = Database::getConnection('drupal');
$webscan = Database::getConnection('default');
$del     = $webscan->prepare('delete from reports where nid=?');
$ins     = $webscan->prepare("insert reports (nid,path,error,contrast,alert,report) values(?,?,?,?,?,?)");

$sql     = "select n.nid,
                   n.type,
                   d.title,
                   from_unixtime(d.changed) as changed,
                   d.uid,
                   p.alias,
                   r.error, r.created,
                   c.field_coordinates_lat
            from      node                    n
                 join node_field_data         d on n.nid=d.nid and n.vid=d.vid
                 join path_alias              p on p.path=concat('/node/', n.nid)
            left join webscan.reports         r on r.nid=n.nid
            left join node__field_coordinates c on n.nid=c.entity_id
            where (r.nid is null or from_unixtime(d.changed) > r.created)";
$query   = $drupal->query($sql);
foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $node) {
    echo "$node[nid] $node[alias] ";
    $webpage = "https://bloomington.in.gov$node[alias]";
    $json    = $api->scan($webpage);
    echo "Credits remaining: {$json['statistics']['creditsremaining']}\n";

    // Google Maps always trigger an error in WAVE
    // Do not count the Google Map error against the page
    if ($node['field_coordinates_lat'] &&
        $json['categories']['error']['count'] > 0) {
        $json['categories']['error']['count']--;
    }

    $del->execute([$node['nid']]);
    $ins->execute([
        $node['nid'  ],
        $node['alias'],
        $json['categories']['error'   ]['count'],
        $json['categories']['contrast']['count'],
        $json['categories']['alert'   ]['count'],
        json_encode($json)
    ]);

    $links = $content->pdf_links((int)$node['nid']);
    $query = $webscan->prepare('select * from grackle_results where path=? and url=? and unlinked=0');
    foreach ($links as $pdf_url) {
        $query->execute([$node['alias'], $pdf_url]);
        $score = $query->fetchAll(\PDO::FETCH_ASSOC);
        if (!count($score)) {
            // Send to grackle
            if (substr($pdf_url, 0, 46) == 'https://bloomington.in.gov/sites/default/files') {
                update_grackle_score($node['alias'], $pdf_url, $grackle);
            }
        }
    }
    unlink_obsolete_grackle_results($node['alias'], $links);
}

function update_grackle_score(string $webpage_path, string $pdf_url, GrackleGateway $grackle)
{
    $webscan = Database::getConnection('default');
    $sql     = "insert into grackle_results set path=:path,filename=:filename,url=:url,score=:score,scanned=now()";
    $insert  = $webscan->prepare($sql);

    $file = DRUPAL_HOME.'/files'.substr($pdf_url, 40);
    echo "\t$pdf_url\n";
    echo "\t$file\n";
    if (is_file($file)) {
        $json = $grackle->scan($file);
        if (isset($json['conformanceIndex'])) {
            $d = [
                'path'     => $webpage_path,
                'filename' => basename($file),
                'url'      => $pdf_url,
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

function unlink_obsolete_grackle_results(string $webpage_path, array $current_links)
{
    $webscan = Database::getConnection('default');
    $update  = $webscan->prepare('update grackle_results set unlinked=1 where path=? and url=?');
    $query   = $webscan->prepare('select url from grackle_results where path=?');
    $query->execute([$webpage_path]);
    $scores  = $query->fetchAll(\PDO::FETCH_COLUMN);
    foreach (array_diff($scores, $current_links) as $url) {
        echo "\tunlinking $url\n";
        $s = $update->execute([$webpage_path, $url]);
        if (!$s) {
            $e = $webscan->errorInfo();
            print_r($e);
            echo "$webpage_path\n$url\n";
            exit();
        }
    }
}
