<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;
use Topxia\Common\ArrayToolkit;

class LatestProductMembers2DataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取一个分类下的产品会员列表
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

        $members = $this->getProductService()->searchMembers($conditions,array('createdTime', 'DESC'),0,$arguments['count']);
        $productIds = ArrayToolkit::column($members, 'productId');
        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $users = ArrayToolkit::index($users, 'id');
        $this->unsetUserPasswords($users);
        $products = $this->getProductService()->findProductsByIds($productIds);
        $products = ArrayToolkit::index($products, 'id');

        foreach ($members as &$member) {
            $member['product'] = $products[$member["productId"]];
            $member['user'] = $users[$member["userId"]];
        }

        return $members;
    }
}
