<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Content\Search;

use Application\Content\ContentRepository;
use Application\Reports\ReportsRepository;

class Controller extends \Web\Controller
{
    public function __invoke(array $params): \Web\View
    {
        if (!empty($_GET['query'])) {
            $content = new ContentRepository();
            $reports = new ReportsRepository();
            $nodes   = $content->pages($_GET['query']);
            $results = [];
            foreach ($nodes as $n) {
                $res     = $reports->find([ 'path'=>$n['alias'] ]);
                $results = array_merge($results, $res['rows']);
            }
            return new View($_GET['query'], $results);
        }

        return new View();
    }
}
