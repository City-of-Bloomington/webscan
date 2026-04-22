<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Web\Reports\Info;

class View extends \Web\View
{
    public function __construct(array $report, ?string $format='html')
    {
        parent::__construct($format);

        $this->vars = [
            'report' => $report
        ];
    }

    public function render(): string
    {
        return $this->twig->render($this->outputFormat.'/reports/info.twig', $this->vars);
    }
}
