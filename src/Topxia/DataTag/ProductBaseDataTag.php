<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;
use Topxia\Service\Common\ServiceKernel;

abstract class ProductBaseDataTag extends BaseDataTag implements DataTag  
{

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    protected function getReviewService()
    {
        return $this->getServiceKernel()->createService('Product.ReviewService');
    }

    protected function checkUserId(array $arguments)
    {
        if (empty($arguments['userId'])) {
            throw new \InvalidArgumentException("userId参数缺失");            
        }
    }

    protected function checkCount(array $arguments)
    {
        if (empty($arguments['count'])) {
            throw new \InvalidArgumentException("count参数缺失");
        }
        if ($arguments['count'] > 100) {
            throw new \InvalidArgumentException("count参数超出最大取值范围");
        }
    }

    protected function checkProductId(array $arguments)
    {
        if (empty($arguments['productId'])) {
            throw new \InvalidArgumentException("productId参数缺失");
        }
    }

    protected function checkProductArguments(array $arguments)
    {
        if (empty($arguments['productId'])){
            $conditions = array();
        } else {
            $conditions = array('productId' => $arguments['productId']);
        }
        return $conditions;
    }

    protected function checkThreadId(array $arguments)
    {
        if (empty($arguments['threadId'])) {
            throw new \InvalidArgumentException("threadId参数缺失");
        }
    }

    protected function checkReviewId(array $arguments)
    {
        if (empty($arguments['reviewId'])) {
            throw new \InvalidArgumentException("reviewId参数缺失");
        }
    }

    protected function checkCategoryId(array $arguments)
    {
        if (empty($arguments['categoryId'])) {
            throw new \InvalidArgumentException("categoryId参数缺失");
        }
    }

    protected function checkGroupId(array $arguments)
    {
        if (empty($arguments['group'])) {
            throw new \InvalidArgumentException("group参数缺失");
        }
    }
    
 	protected function getProductTeachersAndCategories($products)
    {
        $userIds = array();
        $categoryIds = array();
	    foreach ($products as $product) {
            $userIds = array_merge($userIds, $product['teacherIds']);
            $categoryIds[] = $product['categoryId'];
        }

        $users = $this->getUserService()->findUsersByIds($userIds);
        $categories = $this->getCategoryService()->findCategoriesByIds($categoryIds);

        foreach ($products as &$product) {
            $teachers = array();
            foreach ($product['teacherIds'] as $teacherId) {
                $user = $users[$teacherId];
                unset($user['password']);
                unset($user['salt']);
                $teachers[] = $user;
            }
            $product['teachers'] = $teachers;

            $categoryId = $product['categoryId'];
            if($categoryId!=0) {
                $product['category'] = $categories[$categoryId];
            }
        }
        
		return $products;
	}

    protected function getProductsAndUsers($productRelations)
    {
        $userIds = array();
        $productIds = array();
        foreach ($productRelations as &$productRelation) {
            $userIds[] = $productRelation['userId'];
            $productIds[] = $productRelation['productId'];
        }

        $users = $this->getUserService()->findUsersByIds($userIds);
        $products = $this->getProductService()->findProductsByIds($productIds);

        foreach ($productRelations as &$productRelation) {
            $userId = $productRelation['userId'];
            $user = $users[$userId];
            unset($user['password']);
            unset($user['salt']);
            $productRelation['User'] = $user;

            $productId = $productRelation['productId'];
            $product = $products[$productId];
            $productRelation['product'] = $product;
        }

        return $productRelations;
    }

    protected function unsetUserPasswords($users)
    {
        foreach ($users as &$user) {
            unset($user['password']);
            unset($user['salt']);
        }
        return $users;
    }

}