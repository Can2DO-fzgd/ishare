<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ProductMaterialDao;

class ProductMaterialDaoImpl extends BaseDao implements ProductMaterialDao
{
    protected $table = 'product_material';

    public function getMaterial($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function findMaterialsByProductId($productId, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql ="SELECT * FROM {$this->table} WHERE productId=? ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($productId)) ? : array();
    }

    public function findMaterialsByLessonId($lessonId, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql ="SELECT * FROM {$this->table} WHERE lessonId=? ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($lessonId)) ? : array();
    }

    public function getMaterialCountByProductId($productId)
    {
        $sql ="SELECT COUNT(*) FROM {$this->table} WHERE productId = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId));
    }

    public function addMaterial($material)
    {
        $affected = $this->getConnection()->insert($this->table, $material);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert material error.');
        }
        return $this->getMaterial($this->getConnection()->lastInsertId());
    }

    public function deleteMaterial($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    }

    public function deleteMaterialsByLessonId($lessonId)
    {
        $sql = "DELETE FROM {$this->table} WHERE lessonId = ?";
        return $this->getConnection()->executeUpdate($sql, array($lessonId));
    }

    public function deleteMaterialsByProductId($productId)
    {
        $sql = "DELETE FROM {$this->table} WHERE productId = ?";
        return $this->getConnection()->executeUpdate($sql, array($productId));
    }


     public function getLessonMaterialCount($productId,$lessonId)
     {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE  productId = ? AND lessonId = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId, $lessonId)); 
     }  
}
