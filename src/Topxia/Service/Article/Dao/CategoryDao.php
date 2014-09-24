<?php

namespace Topxia\Service\Article\Dao;

interface CategoryDao 
{

	public function addCategory($category);

	public function deleteCategory($id);

	public function getCategory($id);

	public function findCategoryByCode($code);
	
	public function getCategoryByParentId($pid);

	public function findAllCategories();

	public function updateCategory($id, $category);

	public function findCategoriesByParentId($pid, $orderBy = null, $start, $limit);

	public function findCategoriesCountByParentId($pid);

	public function findCategoriesByIds(array $ids);

}
	