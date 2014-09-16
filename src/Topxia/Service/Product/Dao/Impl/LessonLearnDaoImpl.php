<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\LessonLearnDao;

class LessonLearnDaoImpl extends BaseDao implements LessonLearnDao
{
    protected $table = 'product_lesson_learn';

	public function getLearn($id)
	{
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
	}

	public function getLearnByUserIdAndLessonId($userId, $lessonId)
	{
        $sql ="SELECT * FROM {$this->table} WHERE userId=? AND lessonId=?";
        return $this->getConnection()->fetchAssoc($sql, array($userId, $lessonId)) ? : null;
	}

	public function findLearnsByUserIdAndProductId($userId, $productId)
	{
        $sql ="SELECT * FROM {$this->table} WHERE userId=? AND productId=?";
        return $this->getConnection()->fetchAll($sql, array($userId, $productId)) ? : array();
	}

	public function findLearnsByUserIdAndProductIdAndStatus($userId, $productId, $status)
	{
        $sql ="SELECT * FROM {$this->table} WHERE userId=? AND productId=? AND status = ?";
        return $this->getConnection()->fetchAll($sql, array($userId, $productId, $status)) ? : array();
	}

	public function getLearnCountByUserIdAndProductIdAndStatus($userId, $productId, $status)
	{
        $sql ="SELECT COUNT(*) FROM {$this->table} WHERE userId = ? AND productId = ? AND status = ?";
        return $this->getConnection()->fetchColumn($sql, array($userId, $productId, $status));
	}

    public function findLearnsByLessonId($lessonId, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql = "SELECT * FROM {$this->table} WHERE lessonId = ? ORDER BY startTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($lessonId));
    }

    public function findLearnsCountByLessonId($lessonId)
    {
        $sql ="SELECT COUNT(*) FROM {$this->table} WHERE lessonId = ?";
        return $this->getConnection()->fetchColumn($sql, array($lessonId));
    }

	public function addLearn($learn)
	{
        $affected = $this->getConnection()->insert($this->table, $learn);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert learn error.');
        }
        return $this->getLearn($this->getConnection()->lastInsertId());
	}

	public function updateLearn($id, $fields)
	{
        $id = $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getLearn($id);
	}

    public function deleteLearnsByLessonId($lessonId)
    {
        $sql = "DELETE FROM {$this->table} WHERE lessonId = ?";
        return $this->getConnection()->executeUpdate($sql, array($lessonId));
    }
}
