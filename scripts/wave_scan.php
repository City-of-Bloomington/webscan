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

    $links = links((int)$node['nid'], $content);
    $query = $webscan->prepare('select * from grackle_results where url=?');
    foreach ($links as $url) {
        $query->execute([$url]);
        $score = $query->fetchAll(\PDO::FETCH_ASSOC);
        if (!count($score)) {
            // Send to grackle
            if (substr($url, 0, 46) == 'https://bloomington.in.gov/sites/default/files') {
                update_grackle_score($url, $grackle);
            }
        }
    }
}


function links(int $nid, ContentRepository $content): array
{
    $batch   = [];
    $regex   = 'href="([^"]+\\.pdf)"';
    $res     = $content->page_content($nid, '.pdf');
    foreach ($res as $r) {
        switch ($r['field']) {
            // HTML content
            case 'node__body':
            case 'node__field_aside':
            case 'node__field_details':
                // Find the URLs for PDF links in the HTML
                $matches = [];
                preg_match_all("|$regex|", $r['content'], $matches);
                if (  isset ($matches[1]) ) {
                    foreach ($matches[1] as $u) { $batch[] = urldecode($u); }
                }
            break;

            // URL fields
            default:
                $batch[] = urldecode($r['content']);
        }
    }
    return $batch;
}

function update_grackle_score(string $url, GrackleGateway $grackle)
{
    $file = DRUPAL_HOME.'/files'.substr($url, 40);
    echo "\t$url\n";
    echo "\t$file\n";
    if (is_file($file)) {
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
