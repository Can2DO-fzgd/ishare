<?php
namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ProductNoteDao;
use PDO;

class ProductNoteDaoImpl extends BaseDao implements ProductNoteDao
{
    protected $table = 'product_note';
	
	public function getNote($id)
	{
		$sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
	}

	public function findNotesByUserIdAndProductId($userId, $productId)
	{
		$sql = "SELECT * FROM {$this->table} WHERE userId = ? AND productId = ? ORDER BY createdTime DESC";
        return $this->getConnection()->fetchAll($sql, array($userId, $productId));
	}

	public function addNote($noteInfo)
	{
		$affected = $this->getConnection()->insert($this->table, $noteInfo);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert noteInfo error.');
        }
        return $this->getNote($this->getConnection()->lastInsertId());
	}

	public function updateNote($id,$noteInfo)
	{
		$this->getConnection()->update($this->table, $noteInfo, array('id' => $id));
        return $this->getNote($id);
	}

	public function deleteNote($id)
	{
		return $this->getConnection()->delete($this->table, array('id' => $id));
	}

	public function getNoteByUserIdAndLessonId($userId,$lessonId)
	{
		$sql = "SELECT * FROM {$this->table} WHERE userId = ? AND lessonId = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($userId, $lessonId));
	}

    public function findNotesByUserIdAndStatus($userId, $status)
    {
    	$sql = "SELECT * FROM {$this->table} WHERE userId = ? AND status = ?";
        return $this->getConnection()->fetchAll($sql, array($userId, $status));
    }
    	
	public function searchNotes($conditions, $orderBy, $start, $limit)
	{
		$this->filterStartLimit($start, $limit);
		$builder = $this->createSearchNoteQueryBuilder($conditions)
			->select('*')
			->addOrderBy($orderBy[0], $orderBy[1])
			->setFirstResult($start)
			->setMaxResults($limit);

		return $builder->execute()->fetchAll() ? : array();
	}
	
	public function searchNoteCount($conditions)
	{
		$builder = $this->createSearchNoteQueryBuilder($conditions)
			->select('count(id)');
		return $builder->execute()->fetchColumn(0);
	}

	public function getNoteCountByUserIdAndProductId($userId, $productId)
	{
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE userId = ? AND productId = ?";
        return $this->getConnection()->fetchColumn($sql, array($userId, $productId));
	}

	private function createSearchNoteQueryBuilder($conditions)
	{
		if (isset($conditions['content'])) {
			$conditions['content'] = "%{$conditions['content']}%";
		}

		$builder = $this->createDynamicQueryBuilder($conditions)
			->from($this->table, 'note')
			->andWhere('userId = :userId')
			->andWhere('productId = :productId')
			->andWhere('lessonId = :lessonId')
			->andWhere('status = :status')
			->andWhere('content LIKE :content');

		return $builder;
	}

}
