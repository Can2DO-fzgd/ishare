<?php
namespace Topxia\Service\Product;

/**
 * @todo refactor: 去除第一个参数$productId
 */
interface ThreadService
{
	public function getThread($productId, $threadId);

	public function findThreadsByType($productId, $type, $sort = 'latestCreated', $start, $limit);

	public function findLatestThreadsByType($type, $start, $limit);

	public function searchThreads($conditions, $sort, $start, $limit);

	public function searchThreadCount($conditions);

	public function searchThreadCountInProductIds($conditions);

	public function searchThreadInProductIds($conditions, $sort, $start, $limit);

	/**
	 * 创建话题
	 */
	public function createThread($thread);

	public function updateThread($productId, $threadId, $thread);

	public function deleteThread($threadId);

	public function stickThread($productId, $threadId);

	public function unstickThread($productId, $threadId);

	public function eliteThread($productId, $threadId);

	public function uneliteThread($productId, $threadId);

	/**
	 * 点击查看话题
	 *
	 * 此方法，用来增加话题的查看数。
	 * 
	 * @param integer $productId 产品ID
	 * @param integer $threadId 话题ID
	 * 
	 */
	public function hitThread($productId, $threadId);

	/**
	 * 获得话题的回帖
	 * 
	 * @param integer  $productId 话题的产品ID
	 * @param integer  $threadId 话题ID
	 * @param string  	$sort     排序方式： defalut按帖子的发表时间顺序；best按顶的次序排序。
	 * @param integer 	$start    开始行数
	 * @param integer 	$limit    获取数据的限制行数
	 * 
	 * @return array 获得的话题回帖列表。
	 */
	public function findThreadPosts($productId, $threadId, $sort = 'default', $start, $limit);

	/**
	 * 获得话题回帖的数量
	 * @param  integer  $productId 话题的产品ID
	 * @param  integer  $threadId 话题ID
	 * @return integer  话题回帖的数量
	 */
	public function getThreadPostCount($productId, $threadId);

	public function findThreadElitePosts($productId, $threadId, $start, $limit);

	/**
	 * 回复话题
	 */
	public function getPost($productId, $id);

	public function createPost($post);

	public function updatePost($productId, $id, $fields);

	public function deletePost($productId, $id);

}