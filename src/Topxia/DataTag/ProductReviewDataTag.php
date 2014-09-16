<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class ProductReviewDataTag extends ProductBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品评论
     *
     * 可传入的参数：
     *   reviewId 必需 产品评论ID
     * 
     * @param  array $arguments 参数
     * @return array 产品评论
     */
    
    public function getData(array $arguments)
    {
        $this->checkReviewId($arguments);

    	$productReview = $this->getReviewService()->getReview($arguments['reviewId']);
        $productReview['reviewer'] = $this->getUserService()->getUser($productReview['userId']);
        $Reviewer = &$productReview['reviewer'];
        unset($Reviewer['password']);
        unset($Reviewer['salt']);
        $productReview['product'] = $this->getProductService()->getProduct($productReview['productId']);

        return $productReview;
		
    }   
}