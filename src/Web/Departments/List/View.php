<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Departments\List;

class View extends \Web\View
{
    public function __construct(array $departments)
    {
        parent::__construct();

        $this->vars = [
            'departments' => $departments
        ];
    }

    public function render(): string
    {
        return $this->twig->render('html/departments/list.twig', $this->vars);
    }
}
