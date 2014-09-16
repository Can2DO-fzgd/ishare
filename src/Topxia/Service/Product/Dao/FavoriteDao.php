<?php

namespace Topxia\Service\Product\Dao;

interface FavoriteDao
{

    public function getFavorite($id);

    public function getFavoriteByUserIdAndProductId($userId, $productId);

    public function findProductFavoritesByUserId($userId, $start, $limit);

    public function getFavoriteProductCountByUserId($userId);

    public function addFavorite($collect);

    public function deleteFavorite($id);

}