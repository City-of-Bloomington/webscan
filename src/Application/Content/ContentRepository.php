<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace Application\Content;

use Application\Database;
use Application\PdoRepository;

class ContentRepository extends PdoRepository
{
    public function __construct()
    {
        $this->pdo    = Database::getConnection('drupal');
        $this->table = 'node_field_data';
    }

    /**
     * Returns nodes with content that matches search
     */
    public function pages(string $search): array
    {
        $sql = "select n.nid,
                       n.type,
                       n.title,
                       a.alias
                from node_field_data n
                join path_alias      a on a.path=concat('/node/', n.nid)
                join (    select   entity_id,  body_value               as content from node__body
                    union select   entity_id,  field_aside_value        as content from node__field_aside
                    union select   entity_id,  field_details_value      as content from node__field_details
                    union select   entity_id,  field_related_links_uri  as content from node__field_related_links
                    union select   entity_id,  field_call_to_action_uri as content from node__field_call_to_action
                    union select i.entity_id,l.field_info_link_uri      as content
                          from node_field_data                      n
                          join node__field_info_links               i on n.vid=i.revision_id
                          join paragraphs_item_revision_field_data pr on pr.revision_id=i.field_info_links_target_revision_id
                          join paragraph__field_info_card           c on c.revision_id=pr.revision_id
                          join paragraph__field_info_link           l on l.revision_id=c.field_info_card_target_revision_id
                    union select a.entity_id,
                                 concat('https://bloomington.in.gov/sites/default/files', substring(f.uri, 9)) as content
                          from node__field_attachments a
                          join file_managed            f on f.fid=a.field_attachments_target_id
                          where a.field_attachments_display=1 and a.deleted=0
                ) x on n.nid=x.entity_id
                where (x.content like ? or x.content like ?)";
        $query   = $this->pdo->prepare($sql);
        $encoded = str_replace('%', '\%', self::urlencode($search));
        $query->execute(["%$search%", "%$encoded%"]);
        $result  = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Returns fields in a single node with content that matches search
     */
    public function page_content(int $nid, string $search): array
    {
        $sql = "select x.field,
                       x.content
                from node_field_data n
                join path_alias      a on a.path=concat('/node/', n.nid)
                join (    select   entity_id,  body_value               as content, 'node__body'                 as field from node__body
                    union select   entity_id,  field_aside_value        as content, 'node__field_aside'          as field from node__field_aside
                    union select   entity_id,  field_details_value      as content, 'node__field_details'        as field from node__field_details
                    union select   entity_id,  field_related_links_uri  as content, 'node__field_related_links'  as field from node__field_related_links
                    union select   entity_id,  field_call_to_action_uri as content, 'node__field_call_to_action' as field from node__field_call_to_action
                    union select i.entity_id,l.field_info_link_uri      as content, 'paragraph__field_info_link' as field
                          from node_field_data                      n
                          join node__field_info_links               i on n.vid=i.revision_id
                          join paragraphs_item_revision_field_data pr on pr.revision_id=i.field_info_links_target_revision_id
                          join paragraph__field_info_card           c on c.revision_id=pr.revision_id
                          join paragraph__field_info_link           l on l.revision_id=c.field_info_card_target_revision_id
                    union select a.entity_id,f.filename                 as content, 'node__field_attachments'    as field
                          from node__field_attachments a
                          join file_managed            f on f.fid=a.field_attachments_target_id
                          where a.field_attachments_display=1 and a.deleted=0
                ) x on n.nid=x.entity_id
                where n.nid=?
                  and (x.content like ? or x.content like ?)";
        $query   = $this->pdo->prepare($sql);
        $encoded = str_replace('%', '\%', self::urlencode($search));
        $query->execute([$nid, "%$search%", "%$encoded%"]);
        $result  = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function grackle_results(string $path): array
    {
        $sql = "select g.*,
                    case when left(g.url, 46)='https://bloomington.in.gov/sites/default/files'
                        then if(f.fid, '', 'deleted') else ''
                    end as status
                from      webscan.grackle_results g
                left join drupal.file_managed  f on f.uri=replace(g.url, 'https://bloomington.in.gov/sites/default/files', 'public:/')
                where unlinked=false
                  and g.path=?";
        $qq  = $this->pdo->prepare($sql);
        $qq->execute([$path]);
        $res = $qq->fetchAll(\PDO::FETCH_ASSOC);
        return $res;
    }

    /**
     * Url encoding that matches Drupal's method
     *
     * Drupal does not encode forward slashes.  If we want to find content, we
     * need to urlencode strings the same way Drupal does.
     * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Component!Utility!UrlHelper.php/function/UrlHelper::encodePath/11.x
     */
    private static function urlencode($string): string
    {
        return str_replace('%2F', '/', rawurlencode($string));
    }
}
