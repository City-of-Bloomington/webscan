<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Content\Search;

class View extends \Web\View
{
    public function __construct(?string $query=null, ?array $results=[])
    {
        parent::__construct();

        $this->vars = [
            'query'   => $query,
            'reports' => $results
        ];
    }

    public function render(): string
    {
        return $this->twig->render('html/content/search.twig', $this->vars);
    }
}
