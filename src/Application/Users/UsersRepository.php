<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application\Users;

use Application\PdoRepository;

final class UsersRepository extends PdoRepository
{
    public const COLUMNS = ['id', 'username', 'role', 'department_id', 'department'];
    private const BASE_SELECT = <<<END
    select u.id, u.username, u.department_id,
           d.name as department,
           r.roles_target_id as role
    from users u
    left join departments d on d.id=u.department_id
    left join drupal.users_field_data du on u.username=du.name
    left join drupal.user__roles r on du.uid=r.entity_id
    END;

    public function __construct() { parent::__construct('users'); }

    /**
     * @throws \PDOException
     */
    public function loadByUsername(string $username): ?array
    {
        $q = $this->pdo->prepare(self::BASE_SELECT.' where username=?');
        $q->execute([$username]);
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
                if (in_array($k, static::COLUMNS)) {
                    $where[]    = "$k=:$k";
                    $params[$k] = $v;
                }
			}
		}
        $sql = self::buildSql($select, [], $where, null, $order);
		return $this->performSelect($sql, $params, $itemsPerPage, $currentPage);
    }
}
