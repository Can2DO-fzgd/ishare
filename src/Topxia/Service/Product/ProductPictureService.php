<?php
namespace Topxia\Service\Product;

interface ProductPictureService
{
	//public function getProductPicture($id);

	public function findProductPicture($productcode, $start, $limit);

	public function getProductPictureCount($productcode);
	
	//public function getUserProductPicture($userId, $productcode);

	//public function searchProductPicture($conditions, $sort = 'latest', $start, $limit);

	//public function searchProductPictureCount($conditions);

	//public function saveProductPicture($fields);

	//public function deleteProductPicture($id);

}