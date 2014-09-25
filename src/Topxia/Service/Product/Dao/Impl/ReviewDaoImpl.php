<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ReviewDao;

class ReviewDaoImpl extends BaseDao implements ReviewDao
{
    protected $table = 't_review';

    public function getReview($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function findReviewsByProductId($productId, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql = "SELECT * FROM {$this->table} WHERE contentid = ? ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($productId)) ? : array();
    }

    public function getReviewCountByProductId($productId)
    {
        $sql = "SELECT COUNT(id) FROM {$this->table} WHERE contentid = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId));
    }

    public function addReview($review)
    {
        $affected = $this->getConnection()->insert($this->table, $review);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert review error.');
        }
        return $this->getReview($this->getConnection()->lastInsertId());
    }

    public function updateReview($id, $fields)
    {
        $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getReview($id);
    }

    public function getReviewByUserIdAndProductId($userId, $productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE contentid = ? AND userId = ? LIMIT 1;";
        return $this->getConnection()->fetchAssoc($sql, array($productId, $userId)) ? : null;
    }

    public function getReviewRatingSumByProductId($productId)
    {
        $sql = "SELECT sum(rating) FROM {$this->table} WHERE contentid = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId));
    }

    public function searchReviewsCount($conditions)
    {
         $builder = $this->createReviewSearchBuilder($conditions)
            ->select('COUNT(id)');
        return $builder->execute()->fetchColumn(0);
    }

    public function searchReviews($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->createReviewSearchBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        return $builder->execute()->fetchAll() ? : array();
    }

    public function deleteReview($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->executeUpdate($sql, array($id));
    }

    private function createReviewSearchBuilder($conditions)
    {
        if (isset($conditions['content'])) {
            $conditions['content'] = "%{$conditions['content']}%";
        }

        return $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, $this->table)
                ->andWhere('userId = :userId')
                ->andWhere('productId = :productId')
                ->andWhere('rating = :rating')
                ->andWhere('content LIKE :content');
    }

}
