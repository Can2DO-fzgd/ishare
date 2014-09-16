<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class ProductThreadDataTag extends ProductBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品话题
     *
     * 可传入的参数：
     *   productId 必需 产品ID
     *   threadId 必需 产品话题ID
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkProductId($arguments);
        $this->checkThreadId($arguments);

    	$thread = $this->getThreadService()->getThread($arguments['productId'], $arguments['threadId']);
        $thread['product'] = $this->getProductService()->getProduct($arguments['productId']);

        return $thread;
    }

}
