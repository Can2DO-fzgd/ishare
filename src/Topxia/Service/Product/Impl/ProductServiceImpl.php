<?php
namespace Topxia\Service\Product\Impl;

use Symfony\Component\HttpFoundation\File\File;
use Topxia\Service\Common\BaseService;
use Topxia\Service\Product\ProductService;
use Topxia\Common\ArrayToolkit;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;

class ProductServiceImpl extends BaseService implements ProductService
{

	/**
	 * Product API
	 */

	public function findProductsByIds(array $ids)
	{
		$products = ProductSerialize::unserializes(
            $this->getProductDao()->findProductsByIds($ids)
        );
        return ArrayToolkit::index($products, 'id');
	}

	public function findProductsByTagIdsAndStatus(array $tagIds, $status, $start, $limit)
	{
		$products = ProductSerialize::unserializes(
            $this->getProductDao()->findProductsByTagIdsAndStatus($tagIds, $status, $start, $limit)
        );
        return ArrayToolkit::index($products, 'id');
	}

	public function findProductsByAnyTagIdsAndStatus(array $tagIds, $status, $orderBy, $start, $limit)
	{
		$products = ProductSerialize::unserializes(
            $this->getProductDao()->findProductsByAnyTagIdsAndStatus($tagIds, $status, $orderBy, $start, $limit)
        );
        return ArrayToolkit::index($products, 'id');
	}

	public function findLessonsByIds(array $ids)
	{
		$lessons = $this->getLessonDao()->findLessonsByIds($ids);
		$lessons = LessonSerialize::unserializes($lessons);
        return ArrayToolkit::index($lessons, 'id');
	}

	public function getProduct($id, $inChanging = false)
	{
		return ProductSerialize::unserialize($this->getProductDao()->getProduct($id));
	}

	public function searchProducts($conditions, $sort = 'latest', $start, $limit)
	{
		$conditions = $this->_prepareProductConditions($conditions);
		if ($sort == 'popular') {
			$orderBy =  array('hitNum', 'DESC');
		} else if ($sort == 'recommended') {
			$orderBy = array('recommendedTime', 'DESC');
		} else if ($sort == 'Rating') {
			$orderBy = array('Rating' , 'DESC');
		} else if ($sort == 'hitNum') {
			$orderBy = array('hitNum' , 'DESC');
		} else if ($sort == 'studentNum') {
			$orderBy = array('studentNum' , 'DESC');
		} elseif ($sort == 'recommendedSeq') {
			$orderBy = array('recommendedSeq', 'ASC');
		} else {
			$orderBy = array('createdTime', 'DESC');
		}
		
		return ProductSerialize::unserializes($this->getProductDao()->searchProducts($conditions, $orderBy, $start, $limit));
	}

	public function searchProductCount($conditions)
	{
		$conditions = $this->_prepareProductConditions($conditions);
		return $this->getProductDao()->searchProductCount($conditions);
	}

	private function _prepareProductConditions($conditions)
	{
		$conditions = array_filter($conditions);
		if (isset($conditions['date'])) {
			$dates = array(
				'yesterday'=>array(
					strtotime('yesterday'),
					strtotime('today'),
				),
				'today'=>array(
					strtotime('today'),
					strtotime('tomorrow'),
				),
				'this_week' => array(
					strtotime('Monday this week'),
					strtotime('Monday next week'),
				),
				'last_week' => array(
					strtotime('Monday last week'),
					strtotime('Monday this week'),
				),
				'next_week' => array(
					strtotime('Monday next week'),
					strtotime('Monday next week', strtotime('Monday next week')),
				),
				'this_month' => array(
					strtotime('first day of this month midnight'), 
					strtotime('first day of next month midnight'),
				),
				'last_month' => array(
					strtotime('first day of last month midnight'),
					strtotime('first day of this month midnight'),
				),
				'next_month' => array(
					strtotime('first day of next month midnight'),
					strtotime('first day of next month midnight', strtotime('first day of next month midnight')),
				),
			);

			if (array_key_exists($conditions['date'], $dates)) {
				$conditions['startTimeGreaterThan'] = $dates[$conditions['date']][0];
				$conditions['startTimeLessThan'] = $dates[$conditions['date']][1];
				unset($conditions['date']);
			}
		}

		if (isset($conditions['creator'])) {
			$user = $this->getUserService()->getUserByUserName($conditions['creator']);
			$conditions['userId'] = $user ? $user['id'] : -1;
			unset($conditions['creator']);
		}

		if (isset($conditions['categoryId'])) {
			$childrenIds = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
			$conditions['categoryIds'] = array_merge(array($conditions['categoryId']), $childrenIds);
			unset($conditions['categoryId']);
		}

		return $conditions;
	}

	public function findUserLearnProducts($userId, $start, $limit)
	{
		$members = $this->getMemberDao()->findMembersByUserIdAndRole($userId, 'student', $start, $limit);

		$products = $this->findProductsByIds(ArrayToolkit::column($members, 'productId'));
		foreach ($members as $member) {
			if (empty($products[$member['productId']])) {
				continue;
			}
			$products[$member['productId']]['memberIsLearned'] = $member['isLearned'];
			$products[$member['productId']]['memberLearnedNum'] = $member['learnedNum'];
		}
		return $products;
	}

	public function findUserLearnProductCount($userId)
	{
		return $this->getMemberDao()->findMemberCountByUserIdAndRole($userId, 'student', 0);
	}

	public function findUserLeaningProductCount($userId)
	{
		return $this->getMemberDao()->findMemberCountByUserIdAndRoleAndIsLearned($userId, 'student', 0);
	}

	public function findUserLeaningProducts($userId, $start, $limit)
	{
		$members = $this->getMemberDao()->findMembersByUserIdAndRoleAndIsLearned($userId, 'student', '0', $start, $limit);

		$products = $this->findProductsByIds(ArrayToolkit::column($members, 'productId'));

		$sortedProducts = array();
		foreach ($members as $member) {
			if (empty($products[$member['productId']])) {
				continue;
			}
			$product = $products[$member['productId']];
			$product['memberIsLearned'] = 0;
			$product['memberLearnedNum'] = $member['learnedNum'];
			$sortedProducts[] = $product;
		}
		return $sortedProducts;
	}

	public function findUserLeanedProductCount($userId)
	{
		return $this->getMemberDao()->findMemberCountByUserIdAndRoleAndIsLearned($userId, 'student', 1);
	}

	public function findUserLeanedProducts($userId, $start, $limit)
	{
		$members = $this->getMemberDao()->findMembersByUserIdAndRoleAndIsLearned($userId, 'student', '1', $start, $limit);
		$products = $this->findProductsByIds(ArrayToolkit::column($members, 'productId'));

		$sortedProducts = array();
		foreach ($members as $member) {
			if (empty($products[$member['productId']])) {
				continue;
			}
			$product = $products[$member['productId']];
			$product['memberIsLearned'] = 1;
			$product['memberLearnedNum'] = $member['learnedNum'];
			$sortedProducts[] = $product;
		}
		return $sortedProducts;
	}

	public function findUserTeachProductCount($userId, $onlyPublished = true)
	{
		return $this->getMemberDao()->findMemberCountByUserIdAndRole($userId, 'teacher', $onlyPublished);
	}

	public function findUserTeachProducts($userId, $start, $limit, $onlyPublished = true)
	{
		$members = $this->getMemberDao()->findMembersByUserIdAndRole($userId, 'teacher', $start, $limit, $onlyPublished);

		$products = $this->findProductsByIds(ArrayToolkit::column($members, 'productId'));

		/**
		 * @todo 以下排序代码有共性，需要重构成一函数。
		 */
		$sortedProducts = array();
		foreach ($members as $member) {
			if (empty($products[$member['productId']])) {
				continue;
			}
			$sortedProducts[] = $products[$member['productId']];
		}
		return $sortedProducts;
	}

	public function findUserFavoritedProductCount($userId)
	{
		return $this->getFavoriteDao()->getFavoriteProductCountByUserId($userId);
	}

	public function findUserFavoritedProducts($userId, $start, $limit)
	{
		$productFavorites = $this->getFavoriteDao()->findProductFavoritesByUserId($userId, $start, $limit);
		$favoriteProducts = $this->getProductDao()->findProductsByIds(ArrayToolkit::column($productFavorites, 'productId'));
		return ProductSerialize::unserializes($favoriteProducts);
	}	

	public function createProduct($product)
	{
		if (!ArrayToolkit::requireds($product, array('name'))) {
			throw $this->createServiceException('缺少必要字段，创建产品失败！');
		}

		$product = ArrayToolkit::parts($product, array('name', 'specinfo', 'categoryId', 'tags', 'price', 'startTime', 'endTime', 'locationId', 'address'));

		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = //chr(123)// "{"
                substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
                //.chr(125);// "}"
				
		$product['code'] = $uuid;
		$product['state'] = '1';
		$product['sync'] = '0';
		$product['sort'] = '1';
		$product['createDate'] = date("Ymd");
		//$d=strtotime("tomorrow");
		//$product['syncdatetime'] = date("Y-m-d h:i:sa", $d);
		$product['syncdatetime'] = date("Y-m-d h-i-s");
		$product['tuijian'] = '0';
		
		$product['status'] = 'draft';
        $product['specinfo'] = !empty($product['specinfo']) ? $this->getHtmlPurifier()->purify($product['specinfo']) : '';
        $product['tags'] = !empty($product['tags']) ? $product['tags'] : '';
		$product['userId'] = $this->getCurrentUser()->id;
		$product['coopid'] = $this->getCurrentUser()->id;
		$product['createdTime'] = time();
		$product['teacherIds'] = array($product['userId']);
		$product = $this->getProductDao()->addProduct(ProductSerialize::serialize($product));
		
		$member = array(
			'productId' => $product['id'],
			'userId' => $product['userId'],
			'role' => 'teacher',
			'createdTime' => time(),
		);

		$this->getMemberDao()->addMember($member);

		$product = $this->getProduct($product['id']);

		$this->getLogService()->info('product', 'create', "创建产品《{$product['name']}》(#{$product['id']})");

		return $product;
	}
	
	//更新产品
	public function updateProduct($id, $fields)
	{
		$product = $this->getProductDao()->getProduct($id);
		if (empty($product)) {
			throw $this->createServiceException('产品不存在，更新失败！');
		}

		$fields = $this->_filterProductFields($fields);

		$this->getLogService()->info('product', 'update', "更新产品《{$product['name']}》(#{$product['id']})的信息", $fields);

		$fields = ProductSerialize::serialize($fields);

		return ProductSerialize::unserialize(
			$this->getProductDao()->updateProduct($id, $fields)
		);
	}

	public function updateProductCounter($id, $counter)
	{
		$fields = ArrayToolkit::parts($counter, array('rating', 'ratingNum', 'lessonNum'));
		if (empty($fields)) {
			throw $this->createServiceException('参数不正确，更新计数器失败！');
		}
		$this->getProductDao()->updateProduct($id, $fields);
	}

	private function _filterProductFields($fields)
	{
		$fields = ArrayToolkit::filter($fields, array(
			'name' => '',
			'jianjie' => '',
			'specinfo' => '',
			'expiryDay' => 0,
			'showStudentNumType' => 'opened',
			'serializeMode' => 'none',
			'categoryId' => 0,
			'vipLevelId' => 0,
			'efficacyinfo' => array(),
			'yaodian' => array(),
			'tags' => '',
			'price' => 0.00,
			'startTime' => 0,
			'endTime'  => 0,
			'locationId' => 0,
			'address' => '',
		));

		if (!empty($fields['specinfo'])) {
			$fields['specinfo'] = $this->purifyHtml($fields['specinfo']);
		}

		if (!empty($fields['tags'])) {
			$fields['tags'] = explode(',', $fields['tags']);
			$fields['tags'] = $this->getTagService()->findTagsByNames($fields['tags']);
			array_walk($fields['tags'], function(&$item, $key) {
				$item = (int) $item['id'];
			});
		}
		return $fields;
	}

    public function changeProductPicture ($productId, $filePath, array $options)
    {
        $product = $this->getProductDao()->getProduct($productId);
        if (empty($product)) {
            throw $this->createServiceException('产品不存在，图标更新失败！');
        }

        $pathinfo = pathinfo($filePath);
        $imagine = new Imagine();
        $rawImage = $imagine->open($filePath);

        $largeImage = $rawImage->copy();
        $largeImage->crop(new Point($options['x'], $options['y']), new Box($options['width'], $options['height']));
        $largeImage->resize(new Box(480, 270));//270
        $largeFilePath = "{$pathinfo['dirname']}/{$pathinfo['filename']}_large.{$pathinfo['extension']}";
        $largeImage->save($largeFilePath, array('quality' => 90));
        $largeFileRecord = $this->getFileService()->uploadFile1('productpic', new File($largeFilePath));

        $largeImage->resize(new Box(304, 171));//171
        $middleFilePath = "{$pathinfo['dirname']}/{$pathinfo['filename']}_middle.{$pathinfo['extension']}";
        $largeImage->save($middleFilePath, array('quality' => 90));
        $middleFileRecord = $this->getFileService()->uploadFile1('productpic', new File($middleFilePath));

        $largeImage->resize(new Box(96, 54));//54
        $smallFilePath = "{$pathinfo['dirname']}/{$pathinfo['filename']}_small.{$pathinfo['extension']}";
        $largeImage->save($smallFilePath, array('quality' => 90));
        $smallFileRecord = $this->getFileService()->uploadFile1('productpic', new File($smallFilePath));

        $fields = array(
        	'smallPicture' => $smallFileRecord['uri'],
        	'middlePicture' => $middleFileRecord['uri'],
        	'largePicture' => $largeFileRecord['uri'],
			'typeimg' => str_replace('public://productpic/', '', $middleFileRecord['uri']),//替换typeimg中的public://productpic/为空格
    	);

    	@unlink($filePath);

    	$oldPictures = array(
            'smallPicture' => $product['smallPicture'] ? $this->getKernel()->getParameter('topxia.upload.public_directory') . '/' . str_replace('public://', '', $product['smallPicture']) : null,
            'middlePicture' => $product['middlePicture'] ? $this->getKernel()->getParameter('topxia.upload.public_directory') . '/' . str_replace('public://', '', $product['middlePicture']) : null,
            //'largePicture' => $product['largePicture'] ? $this->getKernel()->getParameter('topxia.upload.public_directory') . '/' . str_replace('public://', '', $product['largePicture']) : null,//直接读取typeimg的值
			'largePicture' => $product['typeimg'] ? $this->getKernel()->getParameter('topxia.upload.public_directory') . '/' .'productpic/' . $product['typeimg'] : null,
			'typeimg' => $product['typeimg'] ? $this->getKernel()->getParameter('topxia.upload.public_directory') . '/' .'productpic/' . $product['typeimg'] : null
        );


        array_map(function($oldPicture){
        	if (!empty($oldPicture)){
	            @unlink($oldPicture);
        	}
        }, $oldPictures);

		$this->getLogService()->info('product', 'update_picture', "更新产品《{$product['name']}》(#{$product['id']})图片", $fields);
        
        return $this->getProductDao()->updateProduct($productId, $fields);
    }

	public function recommendProduct($id, $number)
	{
		$product = $this->tryAdminProduct($id);

		if (!is_numeric($number)) {
			throw $this->createAccessDeniedException('推荐产品序号只能为数字！');
		}

		$product = $this->getProductDao()->updateProduct($id, array(
			'recommended' => 1,
			'recommendedSeq' => (int)$number,
			'recommendedTime' => time(),
		));

		$this->getLogService()->info('product', 'recommend', "推荐产品《{$product['name']}》(#{$product['id']}),序号为{$number}");

		return $product;
	}

	public function cancelRecommendProduct($id)
	{
		$product = $this->tryAdminProduct($id);

		$this->getProductDao()->updateProduct($id, array(
			'recommended' => 0,
			'recommendedTime' => 0,
		));

		$this->getLogService()->info('product', 'cancel_recommend', "取消推荐产品《{$product['name']}》(#{$product['id']})");
	}

	public function deleteProduct($id)
	{
		$product = $this->tryAdminProduct($id);

		$this->getMemberDao()->deleteMembersByProductId($id);
		$this->getLessonDao()->deleteLessonsByProductId($id);
		$this->getChapterDao()->deleteChaptersByProductId($id);

		$this->getProductDao()->deleteProduct($id);

		$this->getLogService()->info('product', 'delete', "删除产品《{$product['name']}》(#{$product['id']})");

		return true;
	}

	public function publishProduct($id)
	{
		$product = $this->tryManageProduct($id);
		$this->getProductDao()->updateProduct($id, array('status' => 'published'));
		$this->getLogService()->info('product', 'publish', "发布产品《{$product['name']}》(#{$product['id']})");
	}

	public function closeProduct($id)
	{
		$product = $this->tryManageProduct($id);
		$this->getProductDao()->updateProduct($id, array('status' => 'closed'));
		$this->getLogService()->info('product', 'close', "关闭产品《{$product['name']}》(#{$product['id']})");
	}

	public function favoriteProduct($productId)
	{
		$user = $this->getCurrentUser();
		if (empty($user['id'])) {
			throw $this->createAccessDeniedException();
		}

		$product = $this->getProduct($productId);
		if($product['status']!='published'){
			throw $this->createServiceException('不能收藏未发布产品');
		}

		if (empty($product)) {
			throw $this->createServiceException("该产品不存在,收藏失败!");
		}

		$favorite = $this->getFavoriteDao()->getFavoriteByUserIdAndProductId($user['id'], $product['id']);
		if($favorite){
			throw $this->createServiceException("该收藏已经存在，请不要重复收藏!");
		}

		$this->getFavoriteDao()->addFavorite(array(
			'productId'=>$product['id'],
			'userId'=>$user['id'], 
			'createdTime'=>time()
		));

		return true;
	}

	public function unFavoriteProduct($productId)
	{
		$user = $this->getCurrentUser();
		if (empty($user['id'])) {
			throw $this->createAccessDeniedException();
		}

		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createServiceException("该产品不存在,收藏失败!");
		}

		$favorite = $this->getFavoriteDao()->getFavoriteByUserIdAndProductId($user['id'], $product['id']);
		if(empty($favorite)){
			throw $this->createServiceException("你未收藏本产品，取消收藏失败!");
		}

		$this->getFavoriteDao()->deleteFavorite($favorite['id']);

		return true;
	}

	public function hasFavoritedProduct($productId)
	{
		$user = $this->getCurrentUser();
		if (empty($user['id'])) {
			return false;
		}

		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createServiceException("产品{$productId}不存在");
		}

		$favorite = $this->getFavoriteDao()->getFavoriteByUserIdAndProductId($user['id'], $product['id']);

		return $favorite ? true : false;
	}

	private function autosetProductFields($productId)
	{
		$fields = array('type' => 'text', 'lessonNum' => 0);
		$lessons = $this->getProductLessons($productId);
		if (empty($lessons)) {
			$this->getProductDao()->updateProduct($productId, $fields);
			return ;
		}

        $counter = array('text' => 0, 'video' => 0);

        foreach ($lessons as $lesson) {
            $counter[$lesson['type']] ++;
            $fields['lessonNum'] ++;
        }

        $percents = array_map(function($value) use ($fields) {
        	return $value / $fields['lessonNum'] * 100;
        }, $counter);

        if ($percents['video'] > 50) {
            $fields['type'] = 'video';
        } else {
            $fields['type'] = 'text';
        }

		$this->getProductDao()->updateProduct($productId, $fields);

	}

	/**
	 * Lesslon API
	 */

	public function getProductLesson($productId, $lessonId)
	{
		$lesson = $this->getLessonDao()->getLesson($lessonId);
		if (empty($lesson) or ($lesson['productId'] != $productId)) {
			return null;
		}
		return LessonSerialize::unserialize($lesson);
	}

	public function getProductLessons($productId)
	{
		$lessons = $this->getLessonDao()->findLessonsByProductId($productId);
		return LessonSerialize::unserializes($lessons);
	}

	public function searchLessons($conditions, $orderBy, $start, $limit)
	{
		return $this->getLessonDao()->searchLessons($conditions, $orderBy, $start, $limit);
	}

	public function createLesson($lesson)
	{
		$lesson = ArrayToolkit::filter($lesson, array(
			'productId' => 0,
			'chapterId' => 0,
			'free' => 0,
			'title' => '',
			'summary' => '',
			'tags' => array(),
			'type' => 'text',
			'content' => '',
			'media' => array(),
			'mediaId' => 0,
			'length' => 0,
		));

		if (!ArrayToolkit::requireds($lesson, array('productId', 'title', 'type'))) {
			throw $this->createServiceException('参数缺失，创建产品介绍失败！');
		}

		if (empty($lesson['productId'])) {
			throw $this->createServiceException('添加产品介绍失败，产品ID为空。');
		}

		$product = $this->getProduct($lesson['productId'], true);
		if (empty($product)) {
			throw $this->createServiceException('添加产品介绍失败，产品不存在。');
		}

		if (!in_array($lesson['type'], array('text', 'audio', 'video', 'testpaper'))) {
			throw $this->createServiceException('产品介绍类型不正确，添加失败！');
		}

		$this->fillLessonMediaFields($lesson);



		//产品内容的过滤 @todo
		// if(isset($lesson['content'])){
		// 	$lesson['content'] = $this->purifyHtml($lesson['content']);
		// }
		if (isset($fields['title'])) {
			$fields['title'] = $this->purifyHtml($fields['title']);
		}

		// 产品处于发布状态时，新增产品介绍，产品介绍默认的状态为“未发布"
		$lesson['status'] = $product['status'] == 'published' ? 'unpublished' : 'published';
		$lesson['free'] = empty($lesson['free']) ? 0 : 1;
		$lesson['number'] = $this->getNextLessonNumber($lesson['productId']);
		$lesson['seq'] = $this->getNextProductItemSeq($lesson['productId']);
		$lesson['userId'] = $this->getCurrentUser()->id;
		$lesson['createdTime'] = time();

		$lastChapter = $this->getChapterDao()->getLastChapterByProductId($lesson['productId']);
		$lesson['chapterId'] = empty($lastChapter) ? 0 : $lastChapter['id'];

		$lesson = $this->getLessonDao()->addLesson(
			LessonSerialize::serialize($lesson)
		);

		$this->updateProductCounter($product['id'], array(
			'lessonNum' => $this->getLessonDao()->getLessonCountByProductId($product['id'])
		));

		$this->getLogService()->info('product', 'add_lesson', "添加产品介绍《{$lesson['title']}》({$lesson['id']})", $lesson);

		return $lesson;
	}

	private function fillLessonMediaFields(&$lesson)
	{

		if (in_array($lesson['type'], array('video', 'audio'))) {
			$media = empty($lesson['media']) ? null : $lesson['media'];
			if (empty($media) or empty($media['source']) or empty($media['name'])) {
				throw $this->createServiceException("media参数不正确，添加产品介绍失败！");
			}

			if ($media['source'] == 'self') {
				$media['id'] = intval($media['id']);
				if (empty($media['id'])) {
					throw $this->createServiceException("media id参数不正确，添加/编辑产品介绍失败！");
				}
				$file = $this->getUploadFileService()->getFile($media['id']);
				if (empty($file)) {
					throw $this->createServiceException('文件不存在，添加/编辑产品介绍失败！');
				}

				$lesson['mediaId'] = $file['id'];
				$lesson['mediaName'] = $file['filename'];
				$lesson['mediaSource'] = 'self';
				$lesson['mediaUri'] = '';
			} else {
				if (empty($media['uri'])) {
					throw $this->createServiceException("media uri参数不正确，添加/编辑产品介绍失败！");
				}
				$lesson['mediaId'] = 0;
				$lesson['mediaName'] = $media['name'];
				$lesson['mediaSource'] = $media['source'];
				$lesson['mediaUri'] = $media['uri'];
			}
		} elseif ($lesson['type'] == 'testpaper') {
			$lesson['mediaId'] = $lesson['mediaId'];
		} else {
			$lesson['mediaId'] = 0;
			$lesson['mediaName'] = '';
			$lesson['mediaSource'] = '';
			$lesson['mediaUri'] = '';
		}

		unset($lesson['media']);

		return $lesson;
	}

	public function updateLesson($productId, $lessonId, $fields)
	{
		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createServiceException("产品(#{$productId})不存在！");
		}

		$lesson = $this->getProductLesson($productId, $lessonId);
		if (empty($lesson)) {
			throw $this->createServiceException("产品介绍(#{$lessonId})不存在！");
		}

		$fields = ArrayToolkit::filter($fields, array(
			'title' => '',
			'summary' => '',
			'content' => '',
			'media' => array(),
			'mediaId' => 0,
			'free' => 0,
			'length' => 0,
		));

		// if (isset($fields['content'])) {
		// 	$fields['content'] = $this->purifyHtml($fields['content']);
		// }

		if (isset($fields['title'])) {
			$fields['title'] = $this->purifyHtml($fields['title']);
		}

		$fields['type'] = $lesson['type'];
		$this->fillLessonMediaFields($fields);

		$lesson = LessonSerialize::unserialize(
			$this->getLessonDao()->updateLesson($lessonId, LessonSerialize::serialize($fields))
		);

		$this->getLogService()->info('product', 'update_lesson', "更新产品介绍《{$lesson['title']}》({$lesson['id']})", $lesson);

		return $lesson;
	}

	public function deleteLesson($productId, $lessonId)
	{
		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createServiceException("产品(#{$productId})不存在！");
		}

		$lesson = $this->getProductLesson($productId, $lessonId, true);
		if (empty($lesson)) {
			throw $this->createServiceException("产品介绍(#{$lessonId})不存在！");
		}

		// 更新看该产品介绍会员的计数器
		$learnCount = $this->getLessonLearnDao()->findLearnsCountByLessonId($lessonId);
		if ($learnCount > 0) {
			$learns = $this->getLessonLearnDao()->findLearnsByLessonId($lessonId, 0, $learnCount);
			foreach ($learns as $learn) {
				if ($learn['status'] == 'finished') {
					$member = $this->getProductMember($learn['productId'], $learn['userId']);
					if ($member) {
						$memberFields = array();
						$memberFields['learnedNum'] = $this->getLessonLearnDao()->getLearnCountByUserIdAndProductIdAndStatus($learn['userId'], $learn['productId'], 'finished') - 1;
						$memberFields['isLearned'] = $memberFields['learnedNum'] >= $product['lessonNum'] ? 1 : 0;
						// var_dump($member);exit();
						$this->getMemberDao()->updateMember($member['id'], $memberFields);
					}
				}
			}
		}
		$this->getLessonLearnDao()->deleteLearnsByLessonId($lessonId);

		$this->getLessonDao()->deleteLesson($lessonId);

		// 更新产品介绍序号
		$this->updateProductCounter($product['id'], array(
			'lessonNum' => $this->getLessonDao()->getLessonCountByProductId($product['id'])
		));
		// [END] 更新产品介绍序号
		
		$this->getLogService()->info('lesson', 'delete', "删除产品《{$product['name']}》(#{$product['id']})的产品介绍 {$lesson['title']}");

		// $this->autosetProductFields($productId);
	}

	public function publishLesson($productId, $lessonId)
	{
		$product = $this->tryManageProduct($productId);

		$lesson = $this->getProductLesson($productId, $lessonId);
		if (empty($lesson)) {
			throw $this->createServiceException("产品介绍#{$lessonId}不存在");
		}

		$this->getLessonDao()->updateLesson($lesson['id'], array('status' => 'published'));
	}

	public function unpublishLesson($productId, $lessonId)
	{
		$product = $this->tryManageProduct($productId);

		$lesson = $this->getProductLesson($productId, $lessonId);
		if (empty($lesson)) {
			throw $this->createServiceException("课时#{$lessonId}不存在");
		}

		$this->getLessonDao()->updateLesson($lesson['id'], array('status' => 'unpublished'));
	}

	public function getNextLessonNumber($productId)
	{
		return $this->getLessonDao()->getLessonCountByProductId($productId) + 1;
	}

	public function startLearnLesson($productId, $lessonId)
	{
		list($product, $member) = $this->tryTakeProduct($productId);
		$user = $this->getCurrentUser();

		$learn = $this->getLessonLearnDao()->getLearnByUserIdAndLessonId($user['id'], $lessonId);
		if ($learn) {
			return false;
		}

		$this->getLessonLearnDao()->addLearn(array(
			'userId' => $user['id'],
			'productId' => $productId,
			'lessonId' => $lessonId,
			'status' => 'learning',
			'startTime' => time(),
			'finishedTime' => 0,
		));

		return true;
	}

	public function finishLearnLesson($productId, $lessonId)
	{
		list($product, $member) = $this->tryLearnProduct($productId);

		$learn = $this->getLessonLearnDao()->getLearnByUserIdAndLessonId($member['userId'], $lessonId);
		if ($learn) {
			$this->getLessonLearnDao()->updateLearn($learn['id'], array(
				'status' => 'finished',
				'finishedTime' => time(),
			));
		} else {
			$this->getLessonLearnDao()->addLearn(array(
				'userId' => $member['userId'],
				'productId' => $productId,
				'lessonId' => $lessonId,
				'status' => 'finished',
				'startTime' => time(),
				'finishedTime' => time(),
			));
		}

		$memberFields = array();
		$memberFields['learnedNum'] = $this->getLessonLearnDao()->getLearnCountByUserIdAndProductIdAndStatus($member['userId'], $product['id'], 'finished');
		$memberFields['isLearned'] = $memberFields['learnedNum'] >= $product['lessonNum'] ? 1 : 0;
		$this->getMemberDao()->updateMember($member['id'], $memberFields);
	}

	public function cancelLearnLesson($productId, $lessonId)
	{
		list($product, $member) = $this->tryLearnProduct($productId);

		$learn = $this->getLessonLearnDao()->getLearnByUserIdAndLessonId($member['userId'], $lessonId);
		if (empty($learn)) {
			throw $this->createServiceException("产品介绍#{$lessonId}尚未查看，取消查看失败。");
		}

		if ($learn['status'] != 'finished') {
			throw $this->createServiceException("产品介绍#{$lessonId}尚未查看，取消查看失败。");
		}

		$this->getLessonLearnDao()->updateLearn($learn['id'], array(
			'status' => 'learning',
			'finishedTime' => 0,
		));

		$memberFields = array();
		$memberFields['learnedNum'] = $this->getLessonLearnDao()->getLearnCountByUserIdAndProductIdAndStatus($member['userId'], $product['id'], 'finished');
		$memberFields['isLearned'] = $memberFields['learnedNum'] >= $product['lessonNum'] ? 1 : 0;
		$this->getMemberDao()->updateMember($member['id'], $memberFields);
	}

	public function getUserLearnLessonStatus($userId, $productId, $lessonId)
	{
		$learn = $this->getLessonLearnDao()->getLearnByUserIdAndLessonId($userId, $lessonId);
		if (empty($learn)) {
			return null;
		}

		return $learn['status'];
	}

	public function getUserLearnLessonStatuses($userId, $productId)
	{
		$learns = $this->getLessonLearnDao()->findLearnsByUserIdAndProductId($userId, $productId) ? : array();

		$statuses = array();
		foreach ($learns as $learn) {
			$statuses[$learn['lessonId']] = $learn['status'];
		}

		return $statuses;
	}

	public function getUserNextLearnLesson($userId, $productId)
	{
		$lessonIds = $this->getLessonDao()->findLessonIdsByProductId($productId);

		$learns = $this->getLessonLearnDao()->findLearnsByUserIdAndProductIdAndStatus($userId, $productId, 'finished');

		$learnedLessonIds = ArrayToolkit::column($learns, 'lessonId');

		$unlearnedLessonIds = array_diff($lessonIds, $learnedLessonIds);
		$nextLearnLessonId = array_shift($unlearnedLessonIds);
		if (empty($nextLearnLessonId)) {
			return null;
		}
		return $this->getLessonDao()->getLesson($nextLearnLessonId);
	}

	public function getChapter($productId, $chapterId)
	{
		$chapter = $this->getChapterDao()->getChapter($chapterId);
		if (empty($chapter) or $chapter['productId'] != $productId) {
			return null;
		}
		return $chapter;
	}

	public function getProductChapters($productId)
	{
		return $this->getChapterDao()->findChaptersByProductId($productId);
	}

	public function createChapter($chapter)
	{
		if (!in_array($chapter['type'], array('chapter', 'unit'))) {
			throw $this->createServiceException("产品介绍章节类型不正确，添加失败！");
		}

		if ($chapter['type'] == 'unit') {
			list($chapter['number'], $chapter['parentId']) = $this->getNextUnitNumberAndParentId($chapter['productId']);
		} else {
			$chapter['number'] = $this->getNextChapterNumber($chapter['productId']);
			$chapter['parentId'] = 0;
		}

		$chapter['seq'] = $this->getNextProductItemSeq($chapter['productId']);
		$chapter['createdTime'] = time();
		return $this->getChapterDao()->addChapter($chapter);
	}

	public function updateChapter($productId, $chapterId, $fields)
	{
		$chapter = $this->getChapter($productId, $chapterId);
		if (empty($chapter)) {
			throw $this->createServiceException("产品介绍章节#{$chapterId}不存在！");
		}
		$fields = ArrayToolkit::parts($fields, array('title'));
		return $this->getChapterDao()->updateChapter($chapterId, $fields);
	}

	public function deleteChapter($productId, $chapterId)
	{
		$product = $this->tryManageProduct($productId);

		$deletedChapter = $this->getChapter($product['id'], $chapterId);
		if (empty($deletedChapter)) {
			throw $this->createServiceException(sprintf('产品介绍章节(ID:%s)不存在，删除失败！', $chapterId));
		}

		$this->getChapterDao()->deleteChapter($deletedChapter['id']);

		$prevChapter = array('id' => 0);
		foreach ($this->getProductChapters($product['id']) as $chapter) {
			if ($chapter['number'] < $deletedChapter['number']) {
				$prevChapter = $chapter;
			}
		}

		$lessons = $this->getLessonDao()->findLessonsByChapterId($deletedChapter['id']);
		foreach ($lessons as $lesson) {
			$this->getLessonDao()->updateLesson($lesson['id'], array('chapterId' => $prevChapter['id']));
		}
	}
	

	public function getNextChapterNumber($productId)
	{
		$counter = $this->getChapterDao()->getChapterCountByProductIdAndType($productId, 'chapter');
		return $counter + 1;
	}

	public function getNextUnitNumberAndParentId($productId)
	{
		$lastChapter = $this->getChapterDao()->getLastChapterByProductIdAndType($productId, 'chapter');

		$parentId = empty($lastChapter) ? 0 : $lastChapter['id'];

		$unitNum = 1 + $this->getChapterDao()->getChapterCountByProductIdAndTypeAndParentId($productId, 'unit', $parentId);

		return array($unitNum, $parentId);
	}

	public function getProductItems($productId)
	{
		$lessons = LessonSerialize::unserializes(
			$this->getLessonDao()->findLessonsByProductId($productId)
		);

		$chapters = $this->getChapterDao()->findChaptersByProductId($productId);

		$items = array();
		foreach ($lessons as $lesson) {
			$lesson['itemType'] = 'lesson';
			$items["lesson-{$lesson['id']}"] = $lesson;
		}

		foreach ($chapters as $chapter) {
			$chapter['itemType'] = 'chapter';
			$items["chapter-{$chapter['id']}"] = $chapter;
		}

		uasort($items, function($item1, $item2){
			return $item1['seq'] > $item2['seq'];
		});
		return $items;
	}

	public function sortProductItems($productId, array $itemIds)
	{
		$items = $this->getProductItems($productId);
		$existedItemIds = array_keys($items);

		if (count($itemIds) != count($existedItemIds)) {
			throw $this->createServiceException('itemdIds参数不正确');
		}

		$diffItemIds = array_diff($itemIds, array_keys($items));
		if (!empty($diffItemIds)) {
			throw $this->createServiceException('itemdIds参数不正确');
		}

		$lessonNum = $chapterNum = $unitNum = $seq = 0;
		$currentChapter = $rootChapter = array('id' => 0);

		foreach ($itemIds as $itemId) {
			$seq ++;
			list($type, ) = explode('-', $itemId);
			switch ($type) {
				case 'lesson':
					$lessonNum ++;
					$item = $items[$itemId];
					$fields = array('number' => $lessonNum, 'seq' => $seq, 'chapterId' => $currentChapter['id']);
					if ($fields['number'] != $item['number'] or $fields['seq'] != $item['seq'] or $fields['chapterId'] != $item['chapterId']) {
						$this->getLessonDao()->updateLesson($item['id'], $fields);
					}
					break;
				case 'chapter':
					$item = $currentChapter = $items[$itemId];
				    if ($item['type'] == 'unit') {
				    	$unitNum ++;
						$fields = array('number' => $unitNum, 'seq' => $seq, 'parentId' => $rootChapter['id']);
				    } else {
				    	$chapterNum ++;
				    	$unitNum = 0;
						$rootChapter = $item;
						$fields = array('number' => $chapterNum, 'seq' => $seq, 'parentId' => 0);
				    }
					if ($fields['parentId'] != $item['parentId'] or $fields['number'] != $item['number'] or $fields['seq'] != $item['seq']) {
						$this->getChapterDao()->updateChapter($item['id'], $fields);
					}
					break;
			}
		}
	}

	private function getNextProductItemSeq($productId)
	{
		$chapterMaxSeq = $this->getChapterDao()->getChapterMaxSeqByProductId($productId);
		$lessonMaxSeq = $this->getLessonDao()->getLessonMaxSeqByProductId($productId);
		return ($chapterMaxSeq > $lessonMaxSeq ? $chapterMaxSeq : $lessonMaxSeq) + 1;
	}

	public function addMemberExpiryDays($productId, $userId, $day)
	{
		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);

		if ($member['deadline'] > 0){
			$deadline = $day*24*60*60+$member['deadline'];
		} else {
			$deadline = $day*24*60*60+time();
		}

		return $this->getMemberDao()->updateMember($member['id'], array(
			'deadline' => $deadline
		));
	}

	/**
	 * Member API
	 */
	public function searchMemberCount($conditions)
	{
		return $this->getMemberDao()->searchMemberCount($conditions);
	}

	public function searchMembers($conditions, $orderBy, $start, $limit)
	{
		$conditions = $this->_prepareProductConditions($conditions);
		return $this->getMemberDao()->searchMembers($conditions, $orderBy, $start, $limit);
	}
	
	public function searchMember($conditions, $start, $limit)
	{
		$conditions = $this->_prepareProductConditions($conditions);
		return $this->getMemberDao()->searchMember($conditions, $start, $limit);
	}
	public function searchMemberIds($conditions, $sort = 'latest', $start, $limit)
	{	
		$conditions = $this->_prepareProductConditions($conditions);
		if ($sort = 'latest') {
			$orderBy = array('createdTime', 'DESC');
		} 
		return $this->getMemberDao()->searchMemberIds($conditions, $orderBy, $start, $limit);
	}

	public function updateProductMember($id, $fields)
	{
		return $this->getMemberDao()->updateMember($id, $fields);
	}

	public function getProductMember($productId, $userId)
	{
		return $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
	}

	public function findProductStudents($productId, $start, $limit)
	{
		return $this->getMemberDao()->findMembersByProductIdAndRole($productId, 'student', $start, $limit);
	}

	public function getProductStudentCount($productId)
	{
		return $this->getMemberDao()->findMemberCountByProductIdAndRole($productId, 'student');
	}

	public function findProductTeachers($productId)
	{
		return $this->getMemberDao()->findMembersByProductIdAndRole($productId, 'teacher', 0, self::MAX_TEACHER);
	}

	public function isProductTeacher($productId, $userId)
	{
		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if(!$member){
			return false;
		} else {
			return empty($member) or $member['role'] != 'teacher' ? false : true;
		}
	}

	public function isProductStudent($productId, $userId)
	{
		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if(!$member){
			return false;
		} else {
			return empty($member) or $member['role'] != 'student' ? false : true;
		}
	}

	public function setProductTeachers($productId, $teachers)
	{
		// 过滤数据
		$teacherMembers = array();
		foreach (array_values($teachers) as $index => $teacher) {
			if (empty($teacher['id'])) {
				throw $this->createServiceException("享客ID不能为空，设置产品(#{$productId})享客失败");
			}
			$user = $this->getUserService()->getUser($teacher['id']);
			if (empty($user)) {
				throw $this->createServiceException("用户不存在或没有享客角色，设置产品(#{$productId})享客失败");
			}

			$teacherMembers[] = array(
				'productId' => $productId,
				'userId' => $user['id'],
				'role' => 'teacher',
				'seq' => $index,
				'isVisible' => empty($teacher['isVisible']) ? 0 : 1,
				'createdTime' => time(),
			);
		}

		// 先清除所有的已存在的享客会员
		$existTeacherMembers = $this->findProductTeachers($productId);
		foreach ($existTeacherMembers as $member) {
			$this->getMemberDao()->deleteMember($member['id']);
		}

		// 逐个插入新享客的的会员数据
		$visibleTeacherIds = array();
		foreach ($teacherMembers as $member) {
			// 存在会员信息，说明该用户先前是会员，则删除该会员信息。
			$existMember = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $member['userId']);
			if ($existMember) {
				$this->getMemberDao()->deleteMember($existMember['id']);
			}
			$this->getMemberDao()->addMember($member);
			if ($member['isVisible']) {
				$visibleTeacherIds[] = $member['userId'];
			}
		}

		$this->getLogService()->info('product', 'update_teacher', "更新产品#{$productId}的教师", $teacherMembers);

		// 更新产品的teacherIds，该字段为产品可见享客的ID列表
		$fields = array('teacherIds' => $visibleTeacherIds);
		$this->getProductDao()->updateProduct($productId, ProductSerialize::serialize($fields));
	}

	/**
	 * @todo 当用户拥有大量的产品享客角色时，这个方法效率是有问题咯！鉴于短期内用户不会拥有大量的产品享客角色，先这么做着。
	 */
	public function cancelTeacherInAllProducts($userId)
	{
		$count = $this->getMemberDao()->findMemberCountByUserIdAndRole($userId, 'teacher', false);
		$members = $this->getMemberDao()->findMembersByUserIdAndRole($userId, 'teacher', 0, $count, false);
		foreach ($members as $member) {
			$product = $this->getProduct($member['productId']);

			$this->getMemberDao()->deleteMember($member['id']);

			$fields = array(
				'teacherIds' => array_diff($product['teacherIds'], array($member['userId']))
			);
			$this->getProductDao()->updateProduct($member['productId'], ProductSerialize::serialize($fields));
		}

		$this->getLogService()->info('product', 'cancel_teachers_all', "取消用户#{$userId}所有的产品享客角色");
	}

	public function remarkStudent($productId, $userId, $remark)
	{
		$member = $this->getProductMember($productId, $userId);
		if (empty($member)) {
			throw $this->createServiceException('产品会员不存在，备注失败!');
		}
		$fields = array('remark' => empty($remark) ? '' : (string) $remark);
		return $this->getMemberDao()->updateMember($member['id'], $fields);
	}

	public function becomeStudent($productId, $userId, $info = array())
	{
		$product = $this->getProduct($productId);

		if (empty($product)) {
			throw $this->createNotFoundException();
		}

		if($product['status'] != 'published') {
			throw $this->createServiceException('不能关注未发布产品');
		}

		$user = $this->getUserService()->getUser($userId);
		if (empty($user)) {
			throw $this->createServiceException("用户(#{$userId})不存在，加入产品失败！");
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if ($member) {
			throw $this->createServiceException("用户(#{$userId})已关注该产品！");
		}

		if (!empty($info['becomeUseMember'])) {
			$levelChecked = $this->getVipService()->checkUserInMemberLevel($user['id'], $product['vipLevelId']);
			if ($levelChecked != 'ok') {
				throw $this->createServiceException("用户(#{$userId})不能以会员身份关注产品！");
			}
			$userMember = $this->getVipService()->getMemberByUserId($user['id']);
		}

		if ($product['expiryDay'] > 0) {
			$deadline = $product['expiryDay']*24*60*60 + time();
		} else {
			$deadline = 0;
		}

		if (!empty($info['orderId'])) {
			$order = $this->getOrderService()->getOrder($info['orderId']);
			if (empty($order)) {
				throw $this->createServiceException("订单(#{$info['orderId']})不存在，关注产品失败！");
			}
		} else {
			$order = null;
		}

		$fields = array(
			'productId' => $productId,
			'userId' => $userId,
			'orderId' => empty($order) ? 0 : $order['id'],
			'deadline' => $deadline,
			'levelId' => empty($info['becomeUseMember']) ? 0 : $userMember['levelId'],
			'role' => 'student',
			'remark' => empty($order['note']) ? '' : $order['note'],
			'createdTime' => time()
		);

		if (empty($fields['remark'])) {
			$fields['remark'] = empty($info['note']) ? '' : $info['note'];
		}

		$member = $this->getMemberDao()->addMember($fields);

		$setting = $this->getSettingService()->get('product', array());
		if (!empty($setting['welcome_message_enabled']) && !empty($product['teacherIds'])) {
			$message = $this->getWelcomeMessageBody($user, $product);
	        $this->getMessageService()->sendMessage($product['teacherIds'][0], $user['id'], $message);
	    }

		$fields = array(
			'studentNum'=> $this->getProductStudentCount($productId),
		);
	    if ($order) {
	    	$fields['income'] = $this->getOrderService()->sumOrderPriceByTarget('product', $productId);
	    }
		$this->getProductDao()->updateProduct($productId, $fields);

		return $member;
	}

	private function getWelcomeMessageBody($user, $product)
    {
        $setting = $this->getSettingService()->get('product', array());
        $valuesToBeReplace = array('{{userName}}', '{{product}}');
        $valuesToReplace = array($user['userName'], $product['name']);
        $welcomeMessageBody = str_replace($valuesToBeReplace, $valuesToReplace, $setting['welcome_message_body']);
        return $welcomeMessageBody;
    }

	public function removeStudent($productId, $userId)
	{
		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException("产品(#${$productId})不存在，退出产品失败。");
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if (empty($member) or ($member['role'] != 'student')) {
			throw $this->createServiceException("用户(#{$userId})不是产品(#{$productId})的会员，退出关注产品失败。");
		}

		$this->getMemberDao()->deleteMember($member['id']);

		$this->getProductDao()->updateProduct($productId, array(
			'studentNum' => $this->getProductStudentCount($productId),
		));

		$this->getLogService()->info('product', 'remove_student', "产品《{$product['name']}》(#{$product['id']})，移除会员#{$member['id']}");
	}

	public function lockStudent($productId, $userId)
	{
		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException("产品(#${$productId})不存在，封锁会员失败。");
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if (empty($member) or ($member['role'] != 'student')) {
			throw $this->createServiceException("用户(#{$userId})不是产品(#{$productId})的会员，封锁会员失败。");
		}

		if ($member['locked']) {
			return ;
		}

		$this->getMemberDao()->updateMember($member['id'], array('locked' => 1));
	}

	public function unlockStudent($productId, $userId)
	{
		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException("产品(#${$productId})不存在，封锁会员失败。");
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if (empty($member) or ($member['role'] != 'student')) {
			throw $this->createServiceException("用户(#{$userId})不是产品(#{$productId})的会员，解封会员失败。");
		}

		if (empty($member['locked'])) {
			return ;
		}

		$this->getMemberDao()->updateMember($member['id'], array('locked' => 0));
	}

	public function increaseLessonQuizCount($lessonId){
	    $lesson = $this->getLessonDao()->getLesson($lessonId);
	    $lesson['quizNum'] += 1;
	    $this->getLessonDao()->updateLesson($lesson['id'],$lesson);

	}
	public function resetLessonQuizCount($lessonId,$count){
	    $lesson = $this->getLessonDao()->getLesson($lessonId);
	    $lesson['quizNum'] = $count;
	    $this->getLessonDao()->updateLesson($lesson['id'],$lesson);
	}
	
	public function increaseLessonMaterialCount($lessonId){
	    $lesson = $this->getLessonDao()->getLesson($lessonId);
	    $lesson['materialNum'] += 1;
	    $this->getLessonDao()->updateLesson($lesson['id'],$lesson);

	}
	public function resetLessonMaterialCount($lessonId,$count){
	    $lesson = $this->getLessonDao()->getLesson($lessonId);
	    $lesson['materialNum'] = $count;
	    $this->getLessonDao()->updateLesson($lesson['id'],$lesson);
	}

	public function setMemberNoteNumber($productId, $userId, $number)
	{
		$member = $this->getProductMember($productId, $userId);
		if (empty($member)) {
			return false;
		}

		$this->getMemberDao()->updateMember($member['id'], array(
			'noteNum' => (int) $number,
			'noteLastUpdateTime' => time(),
		));

		return true;
	}


	/**
	 * @todo refactor it.
	 */
	public function tryManageProduct($productId)
	{
		$user = $this->getCurrentUser();
		if (!$user->isLogin()) {
			throw $this->createAccessDeniedException('未登录用户，无权操作！');
		}

		$product = $this->getProductDao()->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException();
		}

		if (!$this->hasProductManagerRole($productId, $user['id'])) {
			throw $this->createAccessDeniedException('您不是产品的享客或管理员，无权操作！');
		}

		return ProductSerialize::unserialize($product);
	}

	public function tryAdminProduct($productId)
	{
		$product = $this->getProductDao()->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException();
		}

		$user = $this->getCurrentUser();
		if (empty($user->id)) {
			throw $this->createAccessDeniedException('未登录用户，无权操作！');
		}

		if (count(array_intersect($user['roles'], array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'))) == 0) {
			throw $this->createAccessDeniedException('您不是管理员，无权操作！');
		}

		return ProductSerialize::unserialize($product);
	}

	public function canManageProduct($productId)
	{
		$user = $this->getCurrentUser();
		if (!$user->isLogin()) {
			return false;
		}
		if ($user->isAdmin()) {
			return true;
		}

		$product = $this->getProduct($productId);
		if (empty($product)) {
			return $user->isAdmin();
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $user->id);
		if ($member and ($member['role'] == 'teacher')) {
			return true;
		}

		return false;
	}


	public function tryTakeProduct($productId)
	{
		$product = $this->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException();
		}
		$user = $this->getCurrentUser();
		if (!$user->isLogin()) {
			throw $this->createAccessDeniedException('您尚未登录用户，请登录后再查看！');
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $user['id']);
		if (count(array_intersect($user['roles'], array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'))) > 0) {
			return array($product, $member);
		}

		if (empty($member) or !in_array($member['role'], array('teacher', 'student'))) {
			throw $this->createAccessDeniedException('您不是产品会员，不能查看产品介绍内容，请先购买产品！');
		}

		return array($product, $member);
	}

	public function isMemberNonExpired($product, $member)
	{
		if (empty($product) or empty($member)) {
			throw $this->createServiceException("product, member参数不能为空");
		}

		if ($product['expiryDay'] == 0) {
			return true;
		}

		if ($member['deadline'] == 0) {
			return true;
		}

		if ($member['deadline'] > time()) {
			return true;
		}

		return false;
	}

	public function canTakeProduct($product)
	{
		$product = !is_array($product) ? $this->getProduct(intval($product)) : $product;
		if (empty($product)) {
			return false;
		}

		$user = $this->getCurrentUser();
		if (!$user->isLogin()) {
			return false;
		}

		if (count(array_intersect($user['roles'], array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'))) > 0) {
			return true;
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($product['id'], $user['id']);
		if ($member and in_array($member['role'], array('teacher', 'student'))) {
			return true;
		}

		return false;
	}

	public function tryLearnProduct($productId)
	{
		$product = $this->getProductDao()->getProduct($productId);
		if (empty($product)) {
			throw $this->createNotFoundException();
		}

		$user = $this->getCurrentUser();
		if (empty($user)) {
			throw $this->createAccessDeniedException('未登录用户，无权操作！');
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $user['id']);
		if (empty($member) or !in_array($member['role'], array('admin', 'teacher', 'student'))) {
			throw $this->createAccessDeniedException('您不是产品会员，不能查看！');
		}

		return array($product, $member);
	}

	public function getProductAnnouncement($productId, $id)
	{
		$announcement = $this->getAnnouncementDao()->getAnnouncement($id);
		if (empty($announcement) or $announcement['productId'] != $productId) {
			return null;
		}
		return $announcement;
	}

	public function findAnnouncements($productId, $start, $limit)
	{
		return $this->getAnnouncementDao()->findAnnouncementsByProductId($productId, $start, $limit);
	}

	public function findAnnouncementsByProductIds(array $ids, $start, $limit)
	{
		return $this->getAnnouncementDao()->findAnnouncementsByProductIds($ids,$start, $limit);
	}
	
	public function createAnnouncement($productId, $fields)
	{
		$product = $this->tryManageProduct($productId);
        if (!ArrayToolkit::requireds($fields, array('content'))) {
        	$this->createNotFoundException("产品公告数据不正确，创建失败。");
        }

        if(isset($fields['content'])){
        	$fields['content'] = $this->purifyHtml($fields['content']);
        }

		$announcement = array();
		$announcement['productId'] = $product['id'];
		$announcement['content'] = $fields['content'];
		$announcement['userId'] = $this->getCurrentUser()->id;
		$announcement['createdTime'] = time();
		return $this->getAnnouncementDao()->addAnnouncement($announcement);
	}



	public function updateAnnouncement($productId, $id, $fields)
	{
		$product = $this->tryManageProduct($productId);

        $announcement = $this->getProductAnnouncement($productId, $id);
        if(empty($announcement)) {
        	$this->createNotFoundException("产品公告{$id}不存在。");
        }

        if (!ArrayToolkit::requireds($fields, array('content'))) {
        	$this->createNotFoundException("产品公告数据不正确，更新失败。");
        }
        
        if(isset($fields['content'])){
        	$fields['content'] = $this->purifyHtml($fields['content']);
        }

        return $this->getAnnouncementDao()->updateAnnouncement($id, array(
        	'content' => $fields['content']
    	));
	}

	public function deleteProductAnnouncement($productId, $id)
	{
		$product = $this->tryManageProduct($productId);
		$announcement = $this->getProductAnnouncement($productId, $id);
		if(empty($announcement)) {
			$this->createNotFoundException("产品公告{$id}不存在。");
		}

		$this->getAnnouncementDao()->deleteAnnouncement($id);
	}

    private function getAnnouncementDao()
    {
    	return $this->createDao('Product.ProductAnnouncementDao');
    }

	private function hasProductManagerRole($productId, $userId) 
	{
		if($this->getUserService()->hasAdminRoles($userId)){
			return true;
		}

		$member = $this->getMemberDao()->getMemberByProductIdAndUserId($productId, $userId);
		if ($member and ($member['role'] == 'teacher')) {
			return true;
		}

		return false;
	}

	private function isCurrentUser($userId){
		$user = $this->getCurrentUser();
		if($userId==$user->id){
			return true;
		}
		return false;
	}



    private function getProductDao ()
    {
        return $this->createDao('Product.ProductDao');
    }

    private function getOrderDao ()
    {
        return $this->createDao('Product.OrderDao');
    }

    private function getFavoriteDao ()
    {
        return $this->createDao('Product.FavoriteDao');
    }

    private function getMemberDao ()
    {
        return $this->createDao('Product.ProductMemberDao');
    }

    private function getLessonDao ()
    {
        return $this->createDao('Product.LessonDao');
    }

    private function getLessonLearnDao ()
    {
        return $this->createDao('Product.LessonLearnDao');
    }

    private function getLessonViewedDao ()
    {
        return $this->createDao('Product.LessonViewedDao');
    }

    private function getChapterDao()
    {
        return $this->createDao('Product.ProductChapterDao');
    }

    private function getCategoryService()
    {
    	return $this->createService('Taxonomy.CategoryService');
    }

    private function getFileService()
    {
    	return $this->createService('Content.FileService');
    }

    private function getUserService()
    {
    	return $this->createService('User.UserService');
    }

    private function getOrderService()
    {
    	return $this->createService('Order.OrderService');
    }

    private function getVipService()
    {
    	return $this->createService('Vip:Vip.VipService');
    }

    private function getReviewService()
    {
    	return $this->createService('Product.ReviewService');
    }

    protected function getLogService()
    {
        return $this->createService('System.LogService');        
    }

    private function getDiskService()
    {
        return $this->createService('User.DiskService');
    }


    private function getUploadFileService()
    {
        return $this->createService('File.UploadFileService');
    }

    private function getMessageService(){
        return $this->createService('User.MessageService');
    }

    private function getSettingService()
    {
        return $this->createService('System.SettingService');
    }

    private function getLevelService()
    {
    	return $this->createService('User.LevelService');
    }
    
    private function getTagService()
    {
        return $this->createService('Taxonomy.TagService');
    }

}

class ProductSerialize
{
    public static function serialize(array &$product)
    {
    	if (isset($product['tags'])) {
    		if (is_array($product['tags']) and !empty($product['tags'])) {
    			$product['tags'] = '|' . implode('|', $product['tags']) . '|';
    		} else {
    			$product['tags'] = '';
    		}
    	}
    	
    	if (isset($product['efficacyinfo'])) {
    		if (is_array($product['efficacyinfo']) and !empty($product['efficacyinfo'])) {
    			$product['efficacyinfo'] = '|' . implode('|', $product['efficacyinfo']) . '|';
    		} else {
    			$product['efficacyinfo'] = '';
    		}
    	}

    	if (isset($product['yaodian'])) {
    		if (is_array($product['yaodian']) and !empty($product['yaodian'])) {
    			$product['yaodian'] = '|' . implode('|', $product['yaodian']) . '|';
    		} else {
    			$product['yaodian'] = '';
    		}
    	}

    	if (isset($product['teacherIds'])) {
    		if (is_array($product['teacherIds']) and !empty($product['teacherIds'])) {
    			$product['teacherIds'] = '|' . implode('|', $product['teacherIds']) . '|';
    		} else {
    			$product['teacherIds'] = null;
    		}
    	}

        return $product;
    }

    public static function unserialize(array $product = null)
    {
    	if (empty($product)) {
    		return $product;
    	}

		$product['tags'] = empty($product['tags']) ? array() : explode('|', trim($product['tags'], '|'));

		if(empty($product['efficacyinfo'] )) {
			$product['efficacyinfo'] = array();
		} else {
			$product['efficacyinfo'] = explode('|', trim($product['efficacyinfo'], '|'));
		}

		if(empty($product['yaodian'] )) {
			$product['yaodian'] = array();
		} else {
			$product['yaodian'] = explode('|', trim($product['yaodian'], '|'));
		}

		if(empty($product['teacherIds'] )) {
			$product['teacherIds'] = array();
		} else {
			$product['teacherIds'] = explode('|', trim($product['teacherIds'], '|'));
		}

		return $product;
    }

    public static function unserializes(array $products)
    {
    	return array_map(function($product) {
    		return ProductSerialize::unserialize($product);
    	}, $products);
    }
}



class LessonSerialize
{
    public static function serialize(array $lesson)
    {
        return $lesson;
    }

    public static function unserialize(array $lesson = null)
    {
        return $lesson;
    }

    public static function unserializes(array $lessons)
    {
    	return array_map(function($lesson) {
    		return LessonSerialize::unserialize($lesson);
    	}, $lessons);
    }
}