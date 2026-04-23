<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application\Departments;

use Application\PdoRepository;

final class DepartmentsRepository extends PdoRepository
{
    public  const COLUMNS = ['id', 'name', 'title', 'dn', 'nid', 'alias'];
    private const BASE_SELECT = <<<END
    select d.*, p.alias
    from departments d
    left join drupal.path_alias p on p.path=concat('/node/', d.nid)
    END;

    public function __construct() { parent::__construct('departments'); }

    /**
     * @throws \PDOException
     */
    public function loadById(int $id): ?array
    {
        $q = $this->pdo->prepare(self::BASE_SELECT.' where d.id=?');
        $q->execute([$id]);
        $r = $q->fetchAll(\PDO::FETCH_ASSOC);
        if (count($r)) { return $r[0]; }

        return null;
    }

    public function find(array $fields=[], ?string $order=null, ?int $itemsPerPage=null, ?int $currentPage=null): array
    {
        $select = self::BASE_SELECT;
        $where  = [];
        $params = [];

		if ($fields) {
			foreach ($fields as $k=>$v) {
                if (in_array($k, self::COLUMNS)) {
                    $where[]    = "$k=:$k";
                    $params[$k] = $v;
                }
			}
		}
        $sql = self::buildSql($select, [], $where, null, $order);
		return $this->performSelect($sql, $params, $itemsPerPage, $currentPage);
    }
}
