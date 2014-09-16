<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class ProductDataTag extends ProductBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品
     *
     * 可传入的参数：
     *   productId 必需 产品ID
     * 
     * @param  array $arguments 参数
     * @return array 产品
     */
    
    public function getData(array $arguments)
    {   
        $this->checkProductId($arguments);

    	$product = $this->getProductService()->getProduct($arguments['productId']);
        $product['teachers'] = empty($product['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($product['teacherIds']);

        if ($product['categoryId'] != '0') {
            $product['category'] = $this->getCategoryService()->getCategory($product['categoryId']);
        }

        return $product;
    }

    
}

