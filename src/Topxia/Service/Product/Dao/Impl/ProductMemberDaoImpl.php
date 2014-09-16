<?php
namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\ProductMemberDao;
use Topxia\Service\Product\Dao\ProductDao;

class ProductMemberDaoImpl extends BaseDao implements ProductMemberDao
{
    protected $table = 'product_member';

    public function getMember($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function addMember($member)
    {
        $affected = $this->getConnection()->insert($this->table, $member);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert product member error.');
        }
        return $this->getMember($this->getConnection()->lastInsertId());
    }

    public function getMemberByProductIdAndUserId($productId, $userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE userId = ? AND productId = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($userId, $productId)) ? : null;
    }

    public function findMembersByUserIdAndRole($userId, $role, $start, $limit, $onlyPublished = true)
    {
        $this->filterStartLimit($start, $limit);

        $sql  = "SELECT m.* FROM {$this->table} m ";
        $sql.= ' JOIN  '. ProductDao::TABLENAME . ' AS c ON m.userId = ? ';
        $sql .= " AND m.role =  ? AND m.productId = c.id ";
        if($onlyPublished){
            $sql .= " AND c.status = 'published' ";
        }

        $sql .= " ORDER BY createdTime DESC LIMIT {$start}, {$limit}";

        return $this->getConnection()->fetchAll($sql, array($userId, $role));
    }

    public function findMemberCountByUserIdAndRole($userId, $role, $onlyPublished = true)
    {
        $sql = "SELECT COUNT( m.productId ) FROM {$this->table} m ";
        $sql.= " JOIN  ". ProductDao::TABLENAME ." AS c ON m.userId = ? ";
        $sql.= " AND m.role =  ? AND m.productId = c.id ";
        if($onlyPublished){
            $sql.= " AND c.status = 'published' ";
        }
        return $this->getConnection()->fetchColumn($sql,array($userId, $role));
    }

    public function findAllMemberByUserIdAndRole($userId, $role, $onlyPublished = true)
    {
        $this->filterStartLimit($start, $limit);

        $sql  = "SELECT m.* FROM {$this->table} m ";
        $sql.= ' JOIN  '. ProductDao::TABLENAME . ' AS c ON m.userId = ? ';
        $sql .= " AND m.role =  ? AND m.productId = c.id ";
        if($onlyPublished){
            $sql .= " AND c.status = 'published' ";
        }

        // $sql .= " ORDER BY createdTime DESC LIMIT {$start}, {$limit}";

        return $this->getConnection()->fetchAll($sql, array($userId, $role));
    }

    public function findMemberCountByUserIdAndRoleAndIsLearned($userId, $role, $isLearned)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE  userId = ? AND role = ? AND isLearned = ?";
        return $this->getConnection()->fetchColumn($sql, array($userId, $role, $isLearned));
    }

    public function findMembersByUserIdAndRoleAndIsLearned($userId, $role, $isLearned, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql = "SELECT * FROM {$this->table} WHERE userId = ? AND role = ? AND isLearned = ? 
            ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($userId, $role, $isLearned));
    }

    public function findMembersByProductIdAndRole($productId, $role, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $sql = "SELECT * FROM {$this->table} WHERE productId = ? AND role = ? ORDER BY createdTime DESC LIMIT {$start}, {$limit}";
        return $this->getConnection()->fetchAll($sql, array($productId, $role));
    }

    public function findMemberCountByProductIdAndRole($productId, $role)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE  productId = ? AND role = ?";
        return $this->getConnection()->fetchColumn($sql, array($productId, $role));
    }

    public function searchMemberCount($conditions)
    {
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('COUNT(id)');
        return $builder->execute()->fetchColumn(0);
    }

    public function searchMembers($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        return $builder->execute()->fetchAll() ? : array();         
    }

    public function searchMember($conditions, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->orderBy('createdTime', 'ASC');
        return $builder->execute()->fetchAll() ? : array(); 
    }

    public function searchMemberIds($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions);

        if(isset($conditions['unique'])){
            $builder->select('DISTINCT userId');
        }else {
            $builder->select('userId');
        }
        $builder->orderBy($orderBy[0], $orderBy[1]);
        $builder->setFirstResult($start);
        $builder->setMaxResults($limit);

        return $builder->execute()->fetchAll() ? : array();
    }

    public function updateMember($id, $member)
    {
        $this->getConnection()->update($this->table, $member, array('id' => $id));
        return $this->getMember($id);
    }

    public function deleteMember($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    }

    public function deleteMembersByProductId($productId)
    {
        $sql = "DELETE FROM {$this->table} WHERE productId = ?";
        return $this->getConnection()->executeUpdate($sql, array($productId));
    }

    public function deleteMemberByProductIdAndUserId($productId, $userId)
    {
        $sql = "DELETE FROM {$this->table} WHERE userId AND productId = ?";
        return $this->getConnection()->executeUpdate($sql, array($userId, $productId));
    }

    private function _createSearchQueryBuilder($conditions)
    {   
        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, 'product_member')
            ->andWhere('userId = :userId')
            ->andWhere('productId = :productId')
            ->andWhere('noteNum > :noteNumGreaterThan')
            ->andWhere('role = :role')
            ->andWhere('createdTime >= :startTimeGreaterThan')
            ->andWhere('createdTime < :startTimeLessThan');

        if (isset($conditions['productIds'])) {
            $productIds = array();
            foreach ($conditions['productIds'] as $productId) {
                if (ctype_digit($productId)) {
                    $productIds[] = $productId;
                }
            }
            if ($productIds) {
                $productIds = join(',', $productIds);
                $builder->andStaticWhere("productId IN ($productIds)");
            }
        }

        return $builder;
    }

}
