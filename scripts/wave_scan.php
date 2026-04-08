<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

use Application\Content\ContentRepository;
use Application\Database;
use Application\WaveGateway;

include '../src/Web/bootstrap.php';

$content = new ContentRepository();
$api     = new WaveGateway(['api_key' => WAVE_API_KEY]);
$drupal  = Database::getConnection('drupal');
$wave    = Database::getConnection('default');
$del     = $wave->prepare('delete from reports where nid=?');
$ins     = $wave->prepare("insert reports (nid,path,error,contrast,alert,report) values(?,?,?,?,?,?)");

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
}
