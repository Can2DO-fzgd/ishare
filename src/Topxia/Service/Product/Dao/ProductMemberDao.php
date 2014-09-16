<?php

namespace Topxia\Service\Product\Dao;

interface ProductMemberDao
{

    public function getMember($id);

    public function getMemberByProductIdAndUserId($productId, $userId);

    public function findMembersByUserIdAndRole($userId, $role, $start, $limit, $onlyPublished = true);

    public function findMemberCountByUserIdAndRole($userId, $role, $onlyPublished = true);

    public function findMemberCountByUserIdAndRoleAndIsLearned($userId, $role, $isLearned);
    
    public function findMembersByUserIdAndRoleAndIsLearned($userId, $role, $isLearned, $start, $limit);
    
    public function findMembersByProductIdAndRole($productId, $role, $start, $limit);

    public function findMemberCountByProductIdAndRole($productId, $role);

    public function searchMemberCount($conditions);

    public function searchMembers($conditions, $orderBy, $start, $limit);
    
    public function searchMember($conditions, $start, $limit);

    public function searchMemberIds($conditions, $orderBy, $start, $limit);

    public function addMember($member);

    public function updateMember($id, $member);

    public function deleteMember($id);

    public function deleteMembersByProductId($productId);

    public function deleteMemberByProductIdAndUserId($productId, $userId);
}