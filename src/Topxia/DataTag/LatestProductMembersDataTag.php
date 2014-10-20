<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;
use Topxia\Common\ArrayToolkit;

class LatestProductMembersDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取一个分类下的产品成员列表
     *
     * 可传入的参数：
     *   categoryId 选填 分类ID
     *   count    必需 会员数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品会员列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        if (empty($arguments['categoryId'])) {
            $conditions = array('state' => '1');
        }else {
            $conditions = array('state' => '1', 'categoryId' => $arguments['categoryId']);        
        }
        $products = $this->getProductService()->searchProducts($conditions,'latest', 0, 1000);
        $productIds = ArrayToolkit::column($products, 'id');

        $conditions = array('productIds' => $productIds, 'unique' => true , 'role' => 'student');
        $memberIds = $this->getProductService()->searchMemberIds($conditions, 'latest', 0, $arguments['count']);
        $memberIds = ArrayToolkit::column($memberIds, 'userId');
        $users = $this->getUserService()->findUsersByIds($memberIds);
        $users = ArrayToolkit::index($users, 'id');

        $productMembers= array();
        foreach ($memberIds as $memberId) {
            $productMembers[$memberId] = $users[$memberId];
        }
        return $this->unsetUserPasswords($productMembers);
    }
}
