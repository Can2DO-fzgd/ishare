<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class RecommendProductsDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取推荐产品列表
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
            $conditions = array('state' => '1', 'tuijian' => 1 );
        } else {
            $conditions = array('state' => '1', 'tuijian' => 1 ,'categoryId' => $arguments['categoryId']);
        }
        
        if (!empty($arguments['categoryCode'])) {
            $category = $this->getCategoryService()->getCategoryByCode($arguments['categoryCode']);
            $conditions['categoryId'] = empty($category) ? -1 : $category['id'];
        }
        
        $products = $this->getProductService()->searchProducts($conditions,'recommendedSeq', 0, $arguments['count']);

        return $this->getProductTeachersAndCategories($products);
    }
}
