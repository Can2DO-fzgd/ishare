<?php

namespace Topxia\Service\Product\Dao;

interface LessonLearnDao
{
	public function getLearn($id);

	public function getLearnByUserIdAndLessonId($userId, $lessonId);

	public function findLearnsByUserIdAndProductId($userId, $productId);

	public function findLearnsByUserIdAndProductIdAndStatus($userId, $productId, $status);

	public function getLearnCountByUserIdAndProductIdAndStatus($userId, $productId, $status);

    public function findLearnsByLessonId($lessonId, $start, $limit);

    public function findLearnsCountByLessonId($lessonId);

	public function addLearn($learn);

	public function updateLearn($id, $fields);

    public function deleteLearnsByLessonId($lessonId);
}