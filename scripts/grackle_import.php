<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
use Application\Database;

$SCAN_DATE = '2026-03-02';

include '../src/Web/bootstrap.php';
$csv = fopen('./grackle.csv', 'r');
define('FILENAME', 0);
define('SCORE',    1);
define('URL',      2);
define('PATH',     3);

$pdo = Database::getConnection();
$pdo->query('truncate table grackle_results');

$fields = [
    'path',
    'filename',
    'url',
    'score',
    'scanned'
];
$col = implode(',', $fields);
$par = implode(',', array_map(fn($f): string => ":$f", $fields));
$ins = $pdo->prepare("insert into grackle_results ($col) values($par)");

while ($d = fgetcsv($csv)) {
    $data = [
        'path'     => str_replace('https://bloomington.in.gov', '', urldecode($d[PATH])),
        'filename' => urldecode($d[FILENAME]),
        'url'      => urldecode($d[URL]),
        'score'    =>      (int)$d[SCORE],
        'scanned'  =>      $SCAN_DATE
    ];
    echo $data[1]."\n";
    $ins->execute($data);
}

$sql = "update grackle_results set url=replace(url, 'www.bloomington.in.gov', 'bloomington.in.gov')";
$pdo->query($sql);
