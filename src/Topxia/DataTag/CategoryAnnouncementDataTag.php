<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CategoryAnnouncementDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取公告列表
     *
     * 可传入的参数：
     *   categoryId 可选 分类ID
     *   count    必需 产品数量，取值不超过10
     * 
     * @param  array $arguments 参数
     * @return array 公告列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);

        $conditions = array();
        $conditions['status'] = 'published';

        if (!empty($arguments['categoryId'])) {
            $conditions['categoryId'] = $arguments['categoryId'];
        } 

        $productCount =  $this->getProductService()->searchProductCount($conditions);
        
        $products = $this->getProductService()->searchProducts($conditions,'latest', 0, $productCount);

        $ids = array();

        foreach ($products as $product) {
            array_push($ids, $product['id']);
        }

        $announcement = $this->getProductService()->findAnnouncementsByProductIds($ids, 0, $arguments['count']);

        return $announcement;
    }
}
