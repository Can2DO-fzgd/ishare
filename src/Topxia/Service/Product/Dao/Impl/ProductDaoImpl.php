<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ProductDao;

class ProductDaoImpl extends BaseDao implements ProductDao
{

    public function getProduct($id)
    {
        $sql = "SELECT * FROM {$this->getTablename()} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }
    
    public function findProductsByIds(array $ids)
    {
        if(empty($ids)){
            return array();
        }
        $marks = str_repeat('?,', count($ids) - 1) . '?';
        $sql ="SELECT * FROM {$this->getTablename()} WHERE id IN ({$marks});";
        return $this->getConnection()->fetchAll($sql, $ids);
    }

    public function findProductsByTagIdsAndStatus(array $tagIds, $status, $start, $limit)
    {
        if(empty($tagIds)){
            return array();
        }

        $sql ="SELECT * FROM {$this->getTablename()} WHERE status = ?";

        foreach ($tagIds as $tagId) {
            $sql .= " AND tags LIKE '%$tagId%'";
        }

        $sql .= " ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
      
        return $this->getConnection()->fetchAll($sql, array($status));
    }

    public function findProductsByAnyTagIdsAndStatus(array $tagIds, $status, $orderBy, $start, $limit)
    {
        if(empty($tagIds)){
            return array();
        }

        $sql ="SELECT * FROM {$this->getTablename()} WHERE status = ? ";

        foreach ($tagIds as $tagId) {
            $sql .= " OR tags LIKE '%|$tagId|%' ";
        }

        $sql .= " ORDER BY {$orderBy[0]} {$orderBy[1]} LIMIT {$start}, {$limit}";
        
        return $this->getConnection()->fetchAll($sql, array($status));

    }

    public function searchProducts($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        if ($orderBy[0] == 'recommendedSeq') {
            $builder->addOrderBy('recommendedTime', 'DESC');
        }
        return $builder->execute()->fetchAll() ? : array(); 
    }

    public function searchProductCount($conditions)
    {
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('COUNT(id)');
        return $builder->execute()->fetchColumn(0);
    }

    public function addProduct($product)
    {
        $affected = $this->getConnection()->insert(self::TABLENAME, $product);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert product error.');
        }
        return $this->getProduct($this->getConnection()->lastInsertId());
    }

    public function updateProduct($id, $fields)
    {
        $this->getConnection()->update(self::TABLENAME, $fields, array('id' => $id));
        return $this->getProduct($id);
    }

    public function deleteProduct($id)
    {
        return $this->getConnection()->delete(self::TABLENAME, array('id' => $id));
    }

    private function _createSearchQueryBuilder($conditions)
    {

        if (isset($conditions['name'])) {
            $conditions['nameLike'] = "%{$conditions['name']}%";
            unset($conditions['name']);
        }

        if (isset($conditions['tagId'])) {
            $tagId = (int) $conditions['tagId'];
            if (!empty($tagId)) {
                $conditions['tagsLike'] = "%|{$conditions['tagId']}|%";
            }
            unset($conditions['tagId']);
        }

        if (isset($conditions['tagIds'])) {
            $tagIds = $conditions['tagIds'];
            $conditions['tagsLike'] = '%|';
            if (!empty($tagIds)) {
                foreach ($tagIds as $tagId) {
                    $conditions['tagsLike'] .= "{$tagId}|";
                }
            }
            $conditions['tagsLike'] .= '%';
            unset($conditions['tagIds']);
        }
        
        if (isset($conditions['notFree'])) {
            $conditions['notFree'] = 0;
        }

        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from(self::TABLENAME, 't_product')
            ->andWhere('status = :status')
            ->andWhere('price = :price')
            ->andWhere('price > :notFree')
            ->andWhere('name LIKE :nameLike')
            ->andWhere('userId = :userId')
            ->andWhere('tuijian = :tuijian')
            ->andWhere('tags LIKE :tagsLike')
            ->andWhere('startTime >= :startTimeGreaterThan')
            ->andWhere('startTime < :startTimeLessThan')
            ->andWhere('vipLevelId >= :vipLevelIdGreaterThan');


        if (isset($conditions['categoryIds'])) {
            $categoryIds = array();
            foreach ($conditions['categoryIds'] as $categoryId) {
                if (ctype_digit((string)abs($categoryId))) {
                    $categoryIds[] = $categoryId;
                }
            }
            if ($categoryIds) {
                $categoryIds = join(',', $categoryIds);
                $builder->andStaticWhere("categoryId IN ($categoryIds)");
            }
        }

        if (isset($conditions['vipLevelIds'])) {
            $vipLevelIds = array();
            foreach ($conditions['vipLevelIds'] as $vipLevelId) {
                if (ctype_digit((string)abs($vipLevelId))) {
                    $vipLevelIds[] = $vipLevelId;
                }
            }
            if ($vipLevelIds) {
                $vipLevelIds = join(',', $vipLevelIds);
                $builder->andStaticWhere("vipLevelId IN ($vipLevelIds)");
            }

        }

        return $builder;
    }

    private function getTablename()
    {
        return self::TABLENAME;
    }
}