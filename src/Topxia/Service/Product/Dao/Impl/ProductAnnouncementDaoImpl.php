<?php
namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ProductAnnouncementDao;
use PDO;

class ProductAnnouncementDaoImpl extends BaseDao implements ProductAnnouncementDao
{
    protected $table = 'product_announcement';

    public function getAnnouncement($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function findAnnouncementsByProductId($productId, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql ="SELECT * FROM {$this->table} WHERE productId=? ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($productId)) ? : array();
    }

    public function findAnnouncementsByProductIds($ids, $start, $limit)
    {
       if(empty($ids)){
            return array();
        }
        $marks = str_repeat('?,', count($ids) - 1) . '?';
        $sql ="SELECT * FROM {$this->table} WHERE productId IN ({$marks}) ORDER BY createdTime DESC LIMIT {$start}, {$limit};";
        return $this->getConnection()->fetchAll($sql, $ids);
    }

	public function addAnnouncement($fields)
	{
        $affected = $this->getConnection()->insert($this->table, $fields);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert announcement error.');
        }
        return $this->getAnnouncement($this->getConnection()->lastInsertId());
	}

	public function deleteAnnouncement($id)
	{
        $sql = "DELETE FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->executeUpdate($sql, array($id));
	}

	public function updateAnnouncement($id, $fields)
	{
        $id = $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getAnnouncement($id);
	}
}
