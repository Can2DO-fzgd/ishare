<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\LessonDao;

class LessonDaoImpl extends BaseDao implements LessonDao
{
    protected $table = 'product_lesson';

    public function getLesson($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function findLessonsByIds(array $ids)
    {
        if(empty($ids)){ return array(); }
        $marks = str_repeat('?,', count($ids) - 1) . '?';
        $sql ="SELECT * FROM {$this->table} WHERE id IN ({$marks});";
        return $this->getConnection()->fetchAll($sql, $ids);
    }

    public function findLessonsByProductId($productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE productId = ? ORDER BY seq ASC";
        return $this->getConnection()->fetchAll($sql, array($productId));
    }

    public function findLessonIdsByProductId($productId)
    {
        $sql = "SELECT id FROM {$this->table} WHERE  productId = ? ORDER BY number ASC";
        return $this->getConnection()->executeQuery($sql, array($productId))->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getLessonCountByProductId($productId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE productId = ? ";
        return $this->getConnection()->fetchColumn($sql, array($productId));
    }

    public function searchLessons($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        return $builder->execute()->fetchAll() ? : array(); 
    }

    public function getLessonMaxSeqByProductId($productId)
    {
        $sql = "SELECT MAX(seq) FROM {$this->table} WHERE  productId = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId));
    }

    public function findLessonsByChapterId($chapterId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE chapterId = ? ORDER BY seq ASC";
        return $this->getConnection()->fetchAll($sql, array($chapterId));
    }

    public function addLesson($lesson)
    {
        $affected = $this->getConnection()->insert($this->table, $lesson);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert product lesson error.');
        }
        return $this->getLesson($this->getConnection()->lastInsertId());
    }

    public function updateLesson($id, $fields)
    {
        $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getLesson($id);
    }

    public function deleteLessonsByProductId($productId)
    {
        $sql = "DELETE FROM {$this->table} WHERE productId = ?";
        return $this->getConnection()->executeUpdate($sql, array($productId));
    }

    public function deleteLesson($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    }

    private function _createSearchQueryBuilder($conditions)
    {

        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, $this->table)
            ->andWhere('productId = :productId')
            ->andWhere('status = :status')
            ->andWhere('type = :type')
            ->andWhere('free = :free')
            ->andWhere('userId = :userId')
            ->andWhere('title LIKE :titleLike');

        return $builder;
    }

}
