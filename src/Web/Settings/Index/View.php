<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Settings\Index;

final class View extends \Web\View
{
    public function __construct()
    {
        parent::__construct();

        $this->vars = [
            'links' => $this->links()
        ];
    }

    public function render(): string
    {
        return $this->twig->render('html/settings/index.twig', $this->vars);
    }

    private function links(): array
    {
        $links  = [];
        $routes = [
            'departments' => 'Departments',
        ];
        foreach ($routes as $route=>$label) {
            if (parent::isAllowed($route, 'index')) {
                $links[] = [
                    'url'   => parent::generateUri("$route.index"),
                    'label' => $label
                ];
            }
        }
        return $links;
    }
}
