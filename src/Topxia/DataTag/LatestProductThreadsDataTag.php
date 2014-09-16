<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class LatestProductThreadsDataTag extends ProductBaseDataTag implements DataTag  
{
    
    /**
     * 获取最新发表的产品话题列表
     *
     * 可传入的参数：
     *   productId 必需 产品ID
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkProductId($arguments);
        $this->checkCount($arguments);

        $conditions = array( 'productId' => $arguments['productId']);
    	$threads = $this->getThreadService()->searchThreads($conditions, 'created', 0, $arguments['count']);
        $threads['product'] = $this->getProductService()->getProduct($arguments['productId']);

        return $threads;
    }


}
