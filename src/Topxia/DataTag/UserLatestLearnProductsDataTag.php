<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class UserLatestLearnProductsDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取用户当前关注的产品
     *
     * 可传入的参数：
     *   userId   必需 用户ID
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */

    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        $this->checkUserId($arguments);

    	$products =  $this->getProductService()->findUserLeaningProducts($arguments['userId'], 0, $arguments['count']);
        
        return $this->getProductTeachersAndCategories($products);
    }

}