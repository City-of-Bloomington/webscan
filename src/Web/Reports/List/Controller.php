<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Reports\List;

use Application\Reports\ReportsRepository;
use Web\Ldap;

class Controller extends \Web\Controller
{
    public function __invoke(array $params): \Web\View
    {
        $repo   = new ReportsRepository();
        $page   = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $params = self::cleanParameters();
        $sort   = self::prepareSort($params['sort'] ?? 'views desc');
        $search = self::prepareSearch($params);
        $list   = $repo->search(fields:$search,
                                 order:$sort,
                          itemsPerPage:parent::ITEMS_PER_PAGE,
                           currentPage:$page);

        return new View($list['rows'] ?? [],
                        $search,
                        $params,
                        $sort,
                        $list['total'] ?? 0,
                        parent::ITEMS_PER_PAGE,
                        $page,
                        $repo->creditsRemaining());
    }

    private static function cleanParameters(): array
    {
        $fields = ['path', 'username', 'department', 'errors', 'sort'];
        $params = [];
        $regex  = '/[^a-zA-Z0-9\.\/\s\\\-]/';
        foreach ($fields as $f) {
            if (!empty($_GET[$f])) {
                $params[$f] = preg_replace($regex, '', $_GET[$f]);
            }
        }
        return $params;
    }

    private static function prepareSearch(array $params): array
    {
        // defaults
        $s = [];
        if (empty($params['errors'])) { $params['errors'] = 'any'; }

        if (!empty($params['username'])) { $s['username'] =      $params['username']; }
        if (!empty($params['path'    ])) { $s['path'    ] =      $params['path'    ]; }

        if ( isset( $params['errors'])) {
            switch ($params['errors']) {
                case 'any':      $s['errors'  ] = true;  break;
                case 'none':     $s['errors'  ] = false; break;
                case 'error':    $s['error'   ] = true;  break;
                case 'contrast': $s['contrast'] = true;  break;
                case 'pdf':      $s['pdf'     ] = true;  break;
            }
        }

        if (     !empty($params['department'])
            && in_array($params['department'], array_keys(Ldap::$departments))) {

            $s['department'] = $params['department'];
        }

        return $s;
    }

    private static function prepareSort(string $sort): ?string
    {
        $s = explode(' ', $sort);
        if (in_array($s[0], ReportsRepository::$sortable_columns)) {
            return (isset($s[1]) && $s[1]=='desc')
                    ? "$s[0] desc"
                    :  $s[0];
        }
        return null;
    }
}
