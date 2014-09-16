<?php
namespace Topxia\Service\Product\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Product\ThreadService;
use Topxia\Common\ArrayToolkit;

class ThreadServiceImpl extends BaseService implements ThreadService
{

	public function getThread($productId, $threadId)
	{
		$thread = $this->getThreadDao()->getThread($threadId);
		if (empty($thread)) {
			return null;
		}
		return $thread['productId'] == $productId ? $thread : null;
	}

	public function findThreadsByType($productId, $type, $sort = 'latestCreated', $start, $limit)
	{
		if ($sort == 'latestPosted') {
			$orderBy = array('latestPosted', 'DESC');
		} else {
			$orderBy = array('createdTime', 'DESC');
		}

		if (!in_array($type, array('question', 'discussion'))) {
			$type = 'all';
		}

		if ($type == 'all') {
			return $this->getThreadDao()->findThreadsByProductId($productId, $orderBy, $start, $limit);
		}

		return $this->getThreadDao()->findThreadsByProductIdAndType($productId, $type, $orderBy, $start, $limit);
	}

	public function findLatestThreadsByType($type, $start, $limit)
	{
		return $this->getThreadDao()->findLatestThreadsByType($type, $start, $limit);
	}

	public function searchThreads($conditions, $sort, $start, $limit)
	{
		
		$orderBys = $this->filterSort($sort);
		$conditions = $this->prepareThreadSearchConditions($conditions);
		return $this->getThreadDao()->searchThreads($conditions, $orderBys, $start, $limit);
	}


	public function searchThreadCount($conditions)
	{	
		$conditions = $this->prepareThreadSearchConditions($conditions);
		return $this->getThreadDao()->searchThreadCount($conditions);
	}

	public function searchThreadCountInProductIds($conditions)
	{
		$conditions = $this->prepareThreadSearchConditions($conditions);
		return $this->getThreadDao()->searchThreadCountInProductIds($conditions);
	}

	public function searchThreadInProductIds($conditions, $sort, $start, $limit)
	{
		$orderBys = $this->filterSort($sort);
		$conditions = $this->prepareThreadSearchConditions($conditions);
		return $this->getThreadDao()->searchThreadInProductIds($conditions, $orderBys, $start, $limit);
	}
	
	private function filterSort($sort)
	{
		switch ($sort) {
			case 'created':
				$orderBys = array(
					array('isStick', 'DESC'),
					array('createdTime', 'DESC'),
				);
				break;
			case 'posted':
				$orderBys = array(
					array('isStick', 'DESC'),
					array('latestPostTime', 'DESC'),
				);
				break;
			case 'createdNotStick':
				$orderBys = array(
					array('createdTime', 'DESC'),
				);
				break;
			case 'postedNotStick':
				$orderBys = array(
					array('latestPostTime', 'DESC'),
				);
				break;
			case 'popular':
				$orderBys = array(
					array('hitNum', 'DESC'),
				);
				break;

			default:
				throw $this->createServiceException('参数sort不正确。');
		}
		return $orderBys;
	}

	private function prepareThreadSearchConditions($conditions)
	{

		if(empty($conditions['type'])) {
			unset($conditions['type']);
		}

		if(empty($conditions['keyword'])) {
			unset($conditions['keyword']);
			unset($conditions['keywordType']);
		}

		if (isset($conditions['keywordType']) && isset($conditions['keyword'])) {
			if (!in_array($conditions['keywordType'], array('title', 'content', 'productId'))) {
				throw $this->createServiceException('keywordType参数不正确');
			}
			$conditions[$conditions['keywordType']] = $conditions['keyword'];
			unset($conditions['keywordType']);
			unset($conditions['keyword']);
		}

		if(empty($conditions['author'])) {
			unset($conditions['author']);
		}

		if (isset($conditions['author'])) {
			$author = $this->getUserService()->getUserByUserName($conditions['author']);
			$conditions['userId'] = $author ? $author['id'] : -1;
		}

		return $conditions;
	}

	public function createThread($thread)
	{
		if (empty($thread['productId'])) {
			throw $this->createServiceException('Product ID can not be empty.');
		}
		if (empty($thread['type']) or !in_array($thread['type'], array('discussion', 'question'))) {
			throw $this->createServiceException(sprintf('Thread type(%s) is error.', $thread['type']));
		}

		list($product, $member) = $this->getProductService()->tryTakeProduct($thread['productId']);

		$thread['userId'] = $this->getCurrentUser()->id;
		$thread['title'] = empty($thread['title']) ? '' : $thread['title'];

		//创建thread过滤html
		$thread['content'] = $this->purifyHtml($thread['content']);
		$thread['createdTime'] = time();
		$thread['latestPostUserId'] = $thread['userId'];
		$thread['latestPostTime'] = $thread['createdTime'];
		$thread = $this->getThreadDao()->addThread($thread);

		foreach ($product['teacherIds'] as $teacherId) {
			if ($teacherId == $thread['userId']) {
				continue;
			}

			if ($thread['type'] != 'question') {
				continue;
			}

			$this->getNotifiactionService()->notify($teacherId, 'thread', array(
				'threadId' => $thread['id'],
				'threadUserId' => $thread['userId'],
				'threadUserUserName' => $this->getCurrentUser()->userName,
				'threadTitle' => $thread['title'],
				'threadType' => $thread['type'],
				'productId' => $product['id'],
				'productName' => $product['name'],
			));
		}

		return $thread;
	}

	public function updateThread($productId, $threadId, $fields)
	{
		$thread = $this->getThread($productId, $threadId);
		if (empty($thread)) {
			throw $this->createServiceException('话题不存在，更新失败！');
		}

		$user = $this->getCurrentUser();
		($user->isLogin() and $user->id == $thread['userId']) or $this->getProductService()->tryManageProduct($productId);

		$fields = ArrayToolkit::parts($fields, array('title', 'content'));
		if (empty($fields)) {
			throw $this->createServiceException('参数缺失，更新失败。');
		}

		//更新thread过滤html
		$fields['content'] = $this->purifyHtml($fields['content']);
		return $this->getThreadDao()->updateThread($threadId, $fields);
	}

	public function deleteThread($threadId)
	{
		$thread = $this->getThreadDao()->getThread($threadId);
		if (empty($thread)) {
			throw $this->createServiceException(sprintf('话题(ID: %s)不存在。', $threadId));
		}

		if (!$this->getProductService()->canManageProduct($thread['productId'])) {
			throw $this->createServiceException('您无权限删除该话题');
		}

		$this->getThreadPostDao()->deletePostsByThreadId($threadId);
		$this->getThreadDao()->deleteThread($threadId);

		$this->getLogService()->info('thread', 'delete', "删除话题 {$thread['title']}({$thread['id']})");
	}

	public function stickThread($productId, $threadId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);

		$thread = $this->getThread($productId, $threadId);
		if (empty($thread)) {
			throw $this->createServiceException(sprintf('话题(ID: %s)不存在。', $thread['id']));
		}

		$this->getThreadDao()->updateThread($thread['id'], array('isStick' => 1));
	}

	public function unstickThread($productId, $threadId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);

		$thread = $this->getThread($productId, $threadId);
		if (empty($thread)) {
			throw $this->createServiceException(sprintf('话题(ID: %s)不存在。', $thread['id']));
		}

		$this->getThreadDao()->updateThread($thread['id'], array('isStick' => 0));
	}

	public function eliteThread($productId, $threadId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);

		$thread = $this->getThread($productId, $threadId);
		if (empty($thread)) {
			throw $this->createServiceException(sprintf('话题(ID: %s)不存在。', $thread['id']));
		}

		$this->getThreadDao()->updateThread($thread['id'], array('isElite' => 1));
	}

	public function uneliteThread($productId, $threadId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);

		$thread = $this->getThread($productId, $threadId);
		if (empty($thread)) {
			throw $this->createServiceException(sprintf('话题(ID: %s)不存在。', $thread['id']));
		}

		$this->getThreadDao()->updateThread($thread['id'], array('isElite' => 0));
	}

	public function hitThread($productId, $threadId)
	{
		$this->getThreadDao()->waveThread($threadId, 'hitNum', +1);
	}

	public function findThreadPosts($productId, $threadId, $sort = 'default', $start, $limit)
	{
		$thread = $this->getThread($productId, $threadId);
		if (empty($thread)) {
			return array();
		}
		if ($sort == 'best') {
			$orderBy = array('score', 'DESC');
		} else {
			$orderBy = array('createdTime', 'ASC');
		}

		return $this->getThreadPostDao()->findPostsByThreadId($threadId, $orderBy, $start, $limit);
	}

	public function getThreadPostCount($productId, $threadId)
	{
		return $this->getThreadPostDao()->getPostCountByThreadId($threadId);
	}

	public function findThreadElitePosts($productId, $threadId, $start, $limit)
	{
		return $this->getThreadPostDao()->findPostsByThreadIdAndIsElite($threadId, 1, $start, $limit);
	}

	public function getPost($productId, $id)
	{
		$post = $this->getThreadPostDao()->getPost($id);
		if (empty($post) or $post['productId'] != $productId) {
			return null;
		}
		return $post;
	}

	public function createPost($post)
	{
		$requiredKeys = array('productId', 'threadId', 'content');
		if (!ArrayToolkit::requireds($post, $requiredKeys)) {
			throw $this->createServiceException('参数缺失');
		}

		$thread = $this->getThread($post['productId'], $post['threadId']);
		if (empty($thread)) {
			throw $this->createServiceException(sprintf('产品(ID: %s)话题(ID: %s)不存在。', $post['productId'], $post['threadId']));
		}

		list($product, $member) = $this->getProductService()->tryTakeProduct($post['productId']);

		$post['userId'] = $this->getCurrentUser()->id;
		$post['isElite'] = $this->getProductService()->isProductTeacher($post['productId'], $post['userId']) ? 1 : 0;
		$post['createdTime'] = time();

		//创建post过滤html
		$post['content'] = $this->purifyHtml($post['content']);
		$post = $this->getThreadPostDao()->addPost($post);

		// 高并发的时候， 这样更新postNum是有问题的，这里暂时不考虑这个问题。
		$threadFields = array(
			'postNum' => $thread['postNum'] + 1,
			'latestPostUserId' => $post['userId'],
			'latestPostTime' => $post['createdTime'],
		);
		$this->getThreadDao()->updateThread($thread['id'], $threadFields);

		if ($thread['userId'] != $post['userId']) {
			$this->getNotifiactionService()->notify($thread['userId'], 'thread-post', array(
				'postId' => $post['id'],
				'postUserId' => $post['userId'],
				'postUserUserName' => $this->getCurrentUser()->userName,
				'threadId' => $thread['id'],
				'threadTitle' => $thread['title'],
				'threadType' => $thread['type'],
				'productId' => $thread['productId']
			));
		}

		return $post;
	}

	public function updatePost($productId, $id, $fields)
	{
		$post = $this->getPost($productId, $id);
		if (empty($post)) {
			throw $this->createServiceException("回帖#{$id}不存在。");
		}

		$user = $this->getCurrentUser();
		($user->isLogin() and $user->id == $post['userId']) or $this->getProductService()->tryManageProduct($productId);


		$fields  = ArrayToolkit::parts($fields, array('content'));
		if (empty($fields)) {
			throw $this->createServiceException('参数缺失。');
		}

		//更新post过滤html
		$fields['content'] = $this->purifyHtml($fields['content']);
		return $this->getThreadPostDao()->updatePost($id, $fields);
	}

	public function deletePost($productId, $id)
	{
		$product = $this->getProductService()->tryManageProduct($productId);

		$post = $this->getThreadPostDao()->getPost($id);
		if (empty($post)) {
			throw $this->createServiceException(sprintf('帖子(#%s)不存在，删除失败。', $id));
		}

		if ($post['productId'] != $productId) {
			throw $this->createServiceException(sprintf('帖子#%s不属于产品#%s，删除失败。', $id, $productId));
		}

		$this->getThreadPostDao()->deletePost($post['id']);
		$this->getThreadDao()->waveThread($post['threadId'], 'postNum', -1);
	}

	private function getThreadDao()
	{
		return $this->createDao('Product.ThreadDao');
	}

	private function getThreadPostDao()
	{
		return $this->createDao('Product.ThreadPostDao');
	}

	private function getProductService()
	{
		return $this->createService('Product.ProductService');
	}

	private function getUserService()
    {
      	return $this->createService('User.UserService');
    }

	private function getNotifiactionService()
    {
      	return $this->createService('User.NotificationService');
    }

    private function getLogService()
    {
    	return $this->createService('System.LogService');
    }

}