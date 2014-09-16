<?php

namespace Topxia\Service\Product\Dao;

interface LessonDao
{

    public function getLesson($id);

    public function findLessonsByProductId($productId);

    public function findLessonIdsByProductId($productId);

    public function searchLessons($condition, $orderBy, $start, $limit);

    public function getLessonCountByProductId($productId);

    public function getLessonMaxSeqByProductId($productId);

    public function findLessonsByChapterId($chapterId);

    public function addLesson($product);

    public function updateLesson($id, $fields);

    public function deleteLesson($id);

    public function deleteLessonsByProductId($productId);

    public function findLessonsByIds(array $ids);
}