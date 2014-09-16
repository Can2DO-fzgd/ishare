<?php

namespace Topxia\Service\Product\Dao;

interface ProductMaterialDao
{

    public function getMaterial($id);

    public function findMaterialsByProductId($productId, $start, $limit);

    public function findMaterialsByLessonId($lessonId, $start, $limit);

    public function getMaterialCountByProductId($productId);

    public function addMaterial($material);

    public function deleteMaterial($id);

    public function deleteMaterialsByLessonId($lessonId);

    public function deleteMaterialsByProductId($productId);

    public function getLessonMaterialCount($productId,$lessonId);

}