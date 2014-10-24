<?php

namespace Topxia\Service\Taxonomy\Dao;

interface CategoryDao {

	public function addCategory($category);

	public function deleteCategory($id);

	public function getCategory($id);

	public function findCategoryByCode($sn);

	public function findCategoriesByGroupIdAndParentId($groupId, $pid);

	public function updateCategory($id, $category);

	public function findCategoriesByParentId($pid, $orderBy = null, $start, $limit);

	public function findCategoriesCountByParentId($pid);

	public function findCategoriesByGroupId($groupId);

	public function findCategoriesByIds(array $ids);

	public function findAllCategories();
	
	public function findAllCategoriesCountByPid();

}