<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Departments\List;

use Application\Departments\DepartmentsRepository;

class Controller extends \Web\Controller
{
    public function __invoke(array $params): \Web\View
    {
        $repo = new DepartmentsRepository();
        $list = $repo->find();

        return new View($list['rows']);
    }
}
