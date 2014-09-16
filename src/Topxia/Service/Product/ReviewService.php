<?php
namespace Topxia\Service\Product;

interface ReviewService
{
	public function getReview($id);

	public function findProductReviews($productId, $start, $limit);

	public function getProductReviewCount($productId);
	
	public function getUserProductReview($userId, $productId);

	public function searchReviews($conditions, $sort = 'latest', $start, $limit);

	public function searchReviewsCount($conditions);

	public function saveReview($fields);

	public function deleteReview($id);

}