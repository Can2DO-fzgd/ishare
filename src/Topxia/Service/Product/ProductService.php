<?php

namespace Topxia\Service\Product;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProductService
{

	/**
	 * 每个产品可添加的最大的享客人数
	 */
	const MAX_TEACHER = 100;

	/**
	 * Product API
	 */

	public function getProduct($id);

	public function findProductsByIds(array $ids);

	public function findProductsByTagIdsAndStatus(array $tagIds, $state, $start, $limit);

	public function findProductsByAnyTagIdsAndStatus(array $tagIds, $state, $orderBy, $start, $limit);

	public function searchProducts($conditions, $sort = 'latest', $start, $limit);

	public function searchProductCount($conditions);

	public function findUserLearnProducts($userId, $start, $limit);

	public function findUserLearnProductCount($userId);
 
	public function findUserLeaningProducts($userId, $start, $limit);

	public function findUserLeaningProductCount($userId);

	public function findUserLeanedProductCount($userId);

	public function findUserLeanedProducts($userId, $start, $limit);

	public function findUserTeachProductCount($userId, $onlyPublished = true);
	
	public function findUserTeachProducts($userId, $start, $limit, $onlyPublished = true);

	public function findUserFavoritedProductCount($userId);

	public function findUserFavoritedProducts($userId, $start, $limit);

	public function createProduct($product);

	public function updateProduct($id, $fields);

	public function updateProductCounter($id, $counter);

	public function changeProductPicture ($productId, $filePath, array $options);

	public function recommendProduct($id, $number);

	public function cancelRecommendProduct($id);

	/**
	 * 删除产品
	 */
	public function deleteProduct($id);

	public function publishProduct($id);

	public function closeProduct($id);


	/**
	 * Lesson API
	 */
	public function findLessonsByIds(array $ids);

	public function getProductLesson($productId, $lessonId);
	
	public function getProductLessons($productId);

	public function searchLessons($condition, $orderBy, $start, $limit);

	public function createLesson($lesson);

	public function updateLesson($productId, $lessonId, $fields);

	public function deleteLesson($productId, $lessonId);

	public function publishLesson($productId, $lessonId);

	public function unpublishLesson($productId, $lessonId);

	public function getNextLessonNumber($productId);

	public function startLearnLesson($productId, $lessonId);

	public function finishLearnLesson($productId, $lessonId);

	public function cancelLearnLesson($productId, $lessonId);

	public function getUserLearnLessonStatus($userId, $productId, $lessonId);

	public function getUserLearnLessonStatuses($userId, $productId);

	public function getUserNextLearnLesson($userId, $productId);

	/**
	 * Chapter API
	 */
	
	public function getChapter($productId, $chapterId);

	public function getProductChapters($productId);

	public function createChapter($chapter);

	public function updateChapter($productId, $chapterId, $fields);

	public function deleteChapter($productId, $chapterId);

	public function getNextChapterNumber($productId);

	/**
	 * 获得产品的目录项
	 * 
	 * 目录项包含，产品介绍章节、产品介绍、问卷
	 * 
	 */
	public function getProductItems($productId);

	public function sortProductItems($productId, array $itemIds);

	/**
	 * Member API
	 */

	public function searchMembers($conditions, $orderBy, $start, $limit);

	public function searchMember($conditions, $start, $limit);

	public function searchMemberCount($conditions);

	public function getProductMember($productId, $userId);

	public function searchMemberIds($conditions, $sort = 'latest', $start, $limit);

	public function updateProductMember($id, $fields);

	public function isMemberNonExpired($product, $member);

	public function findProductStudents($productId, $start, $limit);

	public function getProductStudentCount($productId);

	public function findProductTeachers($productId);

	public function isProductTeacher($productId, $userId);
	
	public function isProductStudent($productId, $userId);

	public function setProductTeachers($productId, $teachers);

	public function cancelTeacherInAllProducts($userId);

	public function remarkStudent($productId, $userId, $remark);

	/**
	 * 成为会员，即加入产品的关注
	 */
	public function becomeStudent($productId, $userId);

	/**
	 * 退出
	 */
	public function removeStudent($productId, $userId);



	/**
	 * 封锁会员，封锁之后会员不能再查看该产品
	 */
	public function lockStudent($productId, $userId);

	/**
	 * 解封学员
	 */
	public function unlockStudent($productId, $userId);
	
	/**
	 * 尝试管理产品, 无权限则抛出异常
	 * 例如：编辑、上传资料...
	 * 
	 * @param  Integer $productId 产品ID
	 * @return array 产品信息
	 */
	public function tryManageProduct($productId);

	/**
	 * 是否可以管理产品
	 * 
	 * 注意： 如果产品不存在，且当前操作用户为管理员时，返回true。
	 * 
	 */
	public function canManageProduct($productId);

	/**
	 * 尝试使用产品
	 * 例如：查看产品、提问、下载产品资料...
	 * 
	 * @param  Integer $productId 产品ID
	 * @return array 产品信息
	 */
	public function tryTakeProduct($productId);

	/**
	 * 是否可以使用产品
	 */
	public function canTakeProduct($product);

	/**
	 * 尝试关注产品
	 * 
	 * 只有是产品的关注会员/享客，才可以查看。
	 * 
	 * @param  [type] $productId 产品ID
	 * @return array
	 */
	public function tryLearnProduct($productId);

	public function increaseLessonQuizCount($lessonId);
	public function resetLessonQuizCount($lessonId,$count);
	public function increaseLessonMaterialCount($lessonId);
	public function resetLessonMaterialCount($lessonId,$count);

	public function setMemberNoteNumber($productId, $userId, $number);

	public function favoriteProduct($productId);

	public function unFavoriteProduct($productId);

	public function hasFavoritedProduct($productId);

	/*announcement*/
	public function createAnnouncement($productId, $fields);

	public function getProductAnnouncement($productId, $id);

	public function deleteProductAnnouncement($productId, $id);

	public function findAnnouncements($productId, $start, $limit);

	public function findAnnouncementsByProductIds(array $ids, $start, $limit);

	public function updateAnnouncement($productId, $id, $fields);


}