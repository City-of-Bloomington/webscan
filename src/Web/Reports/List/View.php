<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Reports\List;

use Application\Reports\ReportsRepository;
use Web\Ldap;

class View extends \Web\View
{
    public function __construct(array  $reports,
                                array  $search, // Reports search parameters
                                array  $params, // RAW query parameters
                                string $sort,
                                int    $total,
                                int    $itemsPerPage,
                                int    $currentPage,
                                int    $credits)
    {
        parent::__construct();

        $this->vars = [
            'reports'      => $reports,
            'search'       => $search,
            'params'       => $params,
            'sort'         => $sort,
            'total'        => $total,
            'itemsPerPage' => $itemsPerPage,
            'currentPage'  => $currentPage,
            'errors'       => $_GET['errors'] ?? null,
            'departments'  => self::departments(),
            'errorOptions' => self::errorOptions(),
            'sorts'        => self::sorts(),
            'credits'      => $credits
        ];
    }

    public function render(): string
    {
        return $this->twig->render('html/reports/list.twig', $this->vars);
    }

    private static function departments(): array
    {
        $t    = new ReportsRepository();
        $opts = [['value'=>'']];
        foreach ($t->departments() as $d) { $opts[] = ['value'=>$d['name']]; }
        return $opts;
    }

    private static function errorOptions(): array
    {
        return [
            ['value'=>'all',      'label'=>'All Pages'          ],
            ['value'=>'none',     'label'=>'Has no errors'      ],
            ['value'=>'error',    'label'=>'Has webpage errors' ],
            ['value'=>'contrast', 'label'=>'Has contrast errors'],
            ['value'=>'pdf',      'label'=>'Has PDF problems'   ],
            ['value'=>'any',      'label'=>'Has any errors'     ],
        ];
    }

    private static function sorts(): array
    {
        $o    = [];
        $cols = [
            'r.path'     => 'Page',
            'error'      => 'Errors',
            'contrast'   => 'Contrast',
            'pdf'        => 'PDF Problems',
            'username'   => 'User',
            'department' => 'Department',
            'created'    => 'Scanned',
            'views'      => 'Views'
        ];

        foreach ($cols as $k=>$v) {
            $o[] = ['value' => "$k asc",  'label'=>"$v Ascending" ];
            $o[] = ['value' => "$k desc", 'label'=>"$v Descending"];
        }
        return $o;
    }
}
