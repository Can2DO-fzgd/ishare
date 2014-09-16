<?php
namespace Topxia\Service\Product;

interface MaterialService
{
	public function uploadMaterial($material);

	public function deleteMaterial($productId, $materialId);

	public function deleteMaterialsByLessonId($lessonId);

	public function deleteMaterialsByProductId($productId);

	public function getMaterial($productId, $materialId);

    public function findProductMaterials($productId, $start, $limit);

	public function findLessonMaterials($lessonId, $start, $limit);

	public function getMaterialCount($productId);
}