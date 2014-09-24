<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ProductChapterDao;

class ProductChapterDaoImpl extends BaseDao implements ProductChapterDao
{
    protected $table = 'product_chapter';

    public function getChapter($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function addChapter(array $chapter)
    {
        $affected = $this->getConnection()->insert($this->table, $chapter);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert product chapter error.');
        }
        return $this->getChapter($this->getConnection()->lastInsertId());
    }

    public function findChaptersByProductId($productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE productId = ? ORDER BY createdTime ASC";
        return $this->getConnection()->fetchAll($sql, array($productId));
    }

    public function getChapterCountByProductIdAndType($productId, $type)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE  productId = ? AND type = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId, $type));
    }

    public function getChapterCountByProductIdAndTypeAndParentId($productId, $type, $pid)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE  productId = ? AND type = ? AND pid = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId, $type, $pid));
    }

    public function getLastChapterByProductIdAndType($productId, $type)
    {
        $sql = "SELECT * FROM {$this->table} WHERE  productId = ? AND type = ? ORDER BY seq DESC LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($productId, $type)) ? : null;
    }

    public function getLastChapterByProductId($productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE  productId = ? ORDER BY seq DESC LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($productId)) ? : null;
    }

    public function getChapterMaxSeqByProductId($productId)
    {
        $sql = "SELECT MAX(seq) FROM {$this->table} WHERE  productId = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId));
    }

    public function updateChapter($id, array $chapter)
    {
        $this->getConnection()->update($this->table, $chapter, array('id' => $id));
        return $this->getChapter($id);
    }

    public function deleteChapter($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    }

    public function deleteChaptersByProductId($productId)
    {
        $sql = "DELETE FROM {$this->table} WHERE productId = ?";
        return $this->getConnection()->executeUpdate($sql, array($productId));
    }

}
