<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Reports\Info;

use Application\Content\ContentRepository;
use Application\Reports\ReportsRepository;

class Controller extends \Web\Controller
{
    public function __invoke(array $params): \Web\View
    {
        $content = new ContentRepository();
        $reports = new ReportsRepository();
        $r       = $reports->loadById((int)$params['id']);
        if ($r) {
            $grackle = $content->grackle_results($r['path']);
            foreach ($grackle as $g) {
                $pages           = $content->pages($g['filename']);
                $g['link_count'] = count($pages);
                $r['grackle'][]  = $g;
            }
            return new View($r);
        }

        return new \Web\Views\NotFoundView();
    }
}
