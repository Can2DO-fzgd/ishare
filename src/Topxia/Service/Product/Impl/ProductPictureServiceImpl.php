<?php
namespace Topxia\Service\Product\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Product\ProductPictureService;
use Topxia\Common\ArrayToolkit;

class ProductPictureServiceImpl extends BaseService implements ProductPictureService
{

/*	public function getReview($id)
	{
		return $this->getReviewDao()->getReview($id);
	}
*/
	public function findProductPicture($productcode, $start, $limit)
	{
		return $this->getProductPictureDao()->findProductPictureByProductcode($productcode, $start, $limit);
	}

	public function getProductPictureCount($productcode)
	{
		return $this->getProductPictureDao()->getProductPictureCountByProductcode($productcode);
	}

/*	public function getUserProductReview($userId, $productId)
	{
		$user = $this->getUserService()->getUser($userId);
		if(empty($user)){
			throw $this->createServiceException("User is not Exist!");
		}
		$product = $this->getProductService()->getProduct($productId);
		if(empty($product)){
			throw $this->createServiceException("Product is not Exist!");
		}
		return $this->getReviewDao()->getReviewByUserIdAndProductId($userId, $productId);
	}*/

/*	public function searchReviews($conditions, $sort= 'latest', $start, $limit)
	{	
		if($sort=='latest'){
			$orderBy = array('createdTime', 'DESC');
		} else {
			$orderBy = array('rating','DESC');
		} 
		$conditions = $this->prepareReviewSearchConditions($conditions);
		return $this->getReviewDao()->searchReviews($conditions, $orderBy, $start, $limit);
	}*/

/*	public function searchReviewsCount($conditions)
	{		
		$conditions = $this->prepareReviewSearchConditions($conditions);
		return $this->getReviewDao()->searchReviewsCount($conditions);
	}*/

/*	private function prepareReviewSearchConditions($conditions)
	{
		$conditions = array_filter($conditions);

        if (isset($conditions['author'])) {
        	$author = $this->getUserService()->getUserByUserName($conditions['author']);
        	$conditions['userId'] = $author ? $author['id'] : -1;
        }

        return $conditions;
	}*/
	
/*	public function saveReview($fields)
	{
		if (!ArrayToolkit::requireds($fields, array('productId', 'userId', 'rating', 'username', 'contentid'))) {
			throw $this->createServiceException('��������ȷ������ʧ�ܣ�');
		}

		list($product, $member) = $this->getProductService()->tryTakeProduct($fields['productId']);

		$userId = $this->getCurrentUser()->id;

		if (empty($product)) {
			throw $this->createServiceException("��Ʒ(#{$fields['productId']})�����ڣ�����ʧ�ܣ�");
		}
		$user = $this->getUserService()->getUser($fields['userId']);
		if (empty($user)) {
			return $this->createServiceException("�û�(#{$fields['userId']})������,����ʧ��!");
		}

		$review = $this->getReviewDao()->getReviewByUserIdAndProductId($user['id'], $product['id']);
		if (empty($review)) {
			$review = $this->getReviewDao()->addReview(array(
				'userId' => $fields['userId'],
				'productId' => $fields['productId'],
				'rating' => $fields['rating'],
				'content' => empty($fields['content']) ? '' : $fields['content'],
				'createdTime' => time(),
				'createdate' => date("y-m-d h:i:s"),
				'username' => $fields['username'],
				'contentid' => $fields['contentid'],
				'contenttype' => $fields['contenttype'],
			));
		} else {
			$review = $this->getReviewDao()->updateReview($review['id'], array(
				'rating' => $fields['rating'],
				'content' => empty($fields['content']) ? '' : $fields['content'],
			));
		}

		$this->calculateProductRating($product['id']);

		return $review;
	}*/

/*	public function deleteReview($id)
	{
		$review = $this->getReview($id);
		if (empty($review)) {
			throw $this->createServiceException("����(#{$id})�����ڣ�ɾ��ʧ�ܣ�");
		}

		$this->getReviewDao()->deleteReview($id);

		$this->calculateProductRating($review['productId']);

		$this->getLogService()->info('review', 'delete', "ɾ������#{$id}");
	}*/

/*	private function calculateProductRating($productId)
	{
		$ratingSum = $this->getReviewDao()->getReviewRatingSumByProductId($productId);
		$ratingNum = $this->getReviewDao()->getReviewCountByProductId($productId);

		$this->getProductService()->updateProductCounter($productId, array(
			'rating' => $ratingNum ? $ratingSum / $ratingNum : 0,
			'ratingNum' => $ratingNum,
		));
	}*/


/*	private function isCurrentUser($userId){
		$user = $this->getCurrentUser();
		if($userId==$user->id){
			return true;
		}
		return false;
	}*/


	private function getProductPictureDao()
    {
    	return $this->createDao('Product.ProductPictureDao');
    }

/*    private function getUserService()
    {
    	return $this->createService('User.UserService');
    }*/

/*    private function getProductService()
    {
    	return $this->createService('Product.ProductService');
    }*/

/*    private function getLogService()
    {
    	return $this->createService('System.LogService');
    }*/

}