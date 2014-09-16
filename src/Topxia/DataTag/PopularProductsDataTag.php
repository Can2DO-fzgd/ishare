<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class PopularProductsDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取最热门产品列表
     *
     * 可传入的参数：
     *   categoryId 可选 分类ID
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        if (empty($arguments['categoryId'])){
            $conditions = array('status' => 'published');
        } else {
            $conditions = array('status' => 'published', 'categoryId' => $arguments['categoryId']);
        }
        $products = $this->getProductService()->searchProducts($conditions,'popular', 0, $arguments['count']);

        return $this->getProductTeachersAndCategories($products);
    }

}