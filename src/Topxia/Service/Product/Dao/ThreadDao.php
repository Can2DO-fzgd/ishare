<?php

namespace Topxia\Service\Product\Dao;

interface ThreadDao
{
	public function getThread($id);

	public function findLatestThreadsByType($type, $start, $limit);

	public function findThreadsByUserIdAndType($userId, $type);

	public function findThreadsByProductId($productId, $orderBy, $start, $limit);

	public function findThreadsByProductIdAndType($productId, $type, $orderBy, $start, $limit);

	public function searchThreads($conditions, $orderBys, $start, $limit);

	public function searchThreadCount($conditions);

	public function searchThreadCountInProductIds($conditions);

	public function searchThreadInProductIds($conditions, $orderBys, $start, $limit);
	
	public function addThread($thread);

	public function deleteThread($id);

	public function updateThread($id, $fields);

	public function waveThread($id, $field, $diff);

}