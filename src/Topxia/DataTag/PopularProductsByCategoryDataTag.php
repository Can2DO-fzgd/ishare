<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class PopularProductsByCategoryDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取某一分类下最热门产品列表
     *
     * 可传入的参数：
     *   categoryId 必需 分类ID
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        $conditions = array('state' => '1', 'categoryId' => $arguments['categoryId']);
        $products = $this->getProductService()->searchProducts($conditions,'studentNum', 0, $arguments['count']);

        return $this->getProductTeachersAndCategories($products);
    }

}
