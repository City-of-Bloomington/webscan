<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application\Reports;

use Application\PdoRepository;

class ReportsRepository extends PdoRepository
{
    public const SORT_DEFAULT = 'created desc';
    public function __construct() { parent::__construct('reports'); }
    public static $sortable_columns = ['r.path', 'created', 'error', 'contrast', 'pdf', 'username', 'department', 'views'];

    public function loadById(int $id): ?array
    {
        $sql = "select r.*,
                       n.title, n.type,
                       u.username,
                       coalesce(dv.name, dept.name, dn.name, u.department) as department,
                       a.views
                from reports r
                     join drupal.node_field_data            n on    r.nid=n.nid
                     join drupal.node_revision              v on    n.nid=v.nid and n.vid=v.vid
                left join drupal.node__field_department    df on    n.nid=df.entity_id
                left join drupal.node__field_division      vf on    n.nid=vf.entity_id
                left join drupal.node__field_directory_dn dnf on n.nid=dnf.entity_id
                left join departments                    dept on dept.nid=df.field_department_target_id
                left join departments                      dv on   dv.nid=vf.field_division_target_id
                left join departments                      dn on   dn.dn=dnf.field_directory_dn_value
                left join users                             u on u.id=v.revision_uid
                left join analytics                         a on r.path=a.path
                where r.id=?";
        $q   = $this->pdo->prepare($sql);
        $q->execute([$id]);
        $r   = $q->fetchAll(\PDO::FETCH_ASSOC);
        return $r ? $r[0] : null;
    }

    public function search(array $fields=[], string $order=self::SORT_DEFAULT, ?int $itemsPerPage=null, ?int $currentPage=null): array
    {
        $select = "select r.*,
                          u.username,
                          coalesce(dv.name, dept.name, dn.name, u.department) as department,
                          a.views,
                          g.pdf
                   from reports r
                        join drupal.node_field_data            n on    r.nid=n.nid
                        join drupal.node_revision              v on    n.nid=v.nid and n.vid=v.vid
                   left join drupal.node__field_department    df on    n.nid=df.entity_id
                   left join drupal.node__field_division      vf on    n.nid=vf.entity_id
                   left join drupal.node__field_directory_dn dnf on n.nid=dnf.entity_id
                   left join departments                    dept on dept.nid=df.field_department_target_id
                   left join departments                      dv on   dv.nid=vf.field_division_target_id
                   left join departments                      dn on   dn.dn=dnf.field_directory_dn_value
                   left join users                             u on u.id=v.revision_uid
                   left join analytics                         a on r.path=a.path
                   left join (
                       select path, count(*) as pdf
                       from grackle_results
                       where score<90 and unlinked=false
                       group by path
                   ) g on r.path=g.path";

        $joins  = [];
        $where  = [];
        $params = [];

		if ($fields) {
			foreach ($fields as $k=>$v) {
                switch ($k) {
                    case 'errors':
                        $where[] = $v ? '(r.error>0 or  r.contrast>0 or   g.pdf>0)'
                                      : '(r.error<1 and r.contrast<1 and (g.pdf is null or g.pdf<1))';
                    break;
                    case 'error':
                        $where[] = $v ? 'r.error>0' : 'r.error<1';
                    break;
                    case 'contrast':
                        $where[] = $v ? 'r.contrast>0' : 'r.contrast<1';
                    break;
                    case 'pdf':
                        $where[] = $v ? 'g.pdf>0' : '(g.pdf is null or g.pdf<1)';
                    break;

                    case 'department':
                        if ($v == 'UNKNOWN') { $where[] = "$k is null"; }
                        else {
                            $where[]    = "coalesce(dv.name, dept.name, dn.name, u.department) like :$k";
                            $params[$k] = "$v%";
                        }
                    break;
                    case 'path':
                        $where[] = "r.$k like :$k";
                        $params[$k] = "$v%";
                    break;
                    default:
                        $where[]    = "$k like :$k";
                        $params[$k] = "$v%";
                }
			}
		}
        $sql = self::buildSql($select, $joins, $where, null, $order);
		return $this->performSelect($sql, $params, $itemsPerPage, $currentPage);
    }

    public function creditsRemaining(): int
    {
        $sql  = 'select report from reports order by created desc limit 1';
        $q    = $this->pdo->query($sql);
        $r    = $q->fetchAll(\PDO::FETCH_ASSOC);
        $json = json_decode($r[0]['report'], true);
        return (int)$json['statistics']['creditsremaining'];
    }

    public function departments(): array
    {
        $q = $this->pdo->query('select * from departments order by name');
        return $q->fetchAll(\PDO::FETCH_ASSOC);
    }
}
