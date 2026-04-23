<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application\Users;

use Application\PdoRepository;

class UsersRepository extends PdoRepository
{
    public function __construct() { parent::__construct('users'); }

    /**
     * @throws \PDOException
     */
    public function loadByUsername(string $username): ?array
    {
        $q = $this->pdo->prepare('select * from users where username=?');
        $q->execute([$username]);
        $r = $q->fetchAll(\PDO::FETCH_ASSOC);
        if (count($r)) { return $r[0]; }

        return null;
    }
}
