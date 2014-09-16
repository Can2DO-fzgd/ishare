<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class TeacherProductsDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取特定享客的产品列表
     *
     * 可传入的参数：
     *   userId   必需 享客ID
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */

    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        $this->checkUserId($arguments);
        
        $conditions = array(
            'status' => 'published', 
            'userId' => $arguments['userId']
        );
        $products = $this->getProductService()->searchProducts($conditions,'latest', 0, $arguments['count']);

    	return $this->getProductTeachersAndCategories($products);
    }

}
