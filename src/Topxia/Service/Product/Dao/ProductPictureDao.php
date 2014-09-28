<?php

namespace Topxia\Service\Product\Dao;

interface ProductPictureDao
{
    //public function getReview($id);

    public function findProductPictureByProductcode($productId, $start, $limit);

    public function getProductPictureCountByProductcode($productId);

    //public function getReviewByUserIdAndProductId($userId, $productId);

    //public function getReviewRatingSumByProductId($productId);

    //public function searchReviewsCount($conditions);

    //public function searchReviews($conditions, $orderBy, $start, $limit);

    //public function addReview($review);

    //public function updateReview($id, $fields);

    //public function deleteReview($id);

}