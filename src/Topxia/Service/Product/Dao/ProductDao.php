<?php

namespace Topxia\Service\Product\Dao;

interface ProductDao
{
    const TABLENAME = 'product';

    public function getProduct($id);

    public function findProductsByIds(array $ids);

    public function findProductsByTagIdsAndStatus(array $tagIds, $status, $start, $limit);

    public function findProductsByAnyTagIdsAndStatus(array $tagIds, $status, $orderBy, $start, $limit);

	public function searchProducts($conditions, $orderBy, $start, $limit);

	public function searchProductCount($conditions);

    public function addProduct($product);

    public function updateProduct($id, $fields);

    public function deleteProduct($id);

}
