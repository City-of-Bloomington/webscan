<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Departments\Info;

class View extends \Web\View
{
    public function __construct(array $department)
    {
        parent::__construct();

        $this->vars = [
            'department' => $department
        ];
    }

    public function render(): string
    {
        return $this->twig->render('html/departments/info.twig', $this->vars);
    }
}
