<?php
namespace Topxia\WebBundle\Util;

use Topxia\Service\Common\ServiceKernel;

class CategoryBuilder
{
	public function buildChoices($groupCode, $indent = '─')
	{
        $group = $this->getCategoryService()->getGroupByCode($groupCode);
        if (empty($group)) {
        	return array();
        }

        $choices = array();
        $categories = $this->getCategoryService()->getCategoryTree($group['id']);

        foreach ($categories as $category) {
            $choices[$category['id']] = str_repeat(is_null($indent) ? '└' : '└'.$indent, ($category['depth']-1)) . $category['name'];
        }

        return $choices;
	}
	
	public function build1Choices($groupCode, $indent = '─')
	{
        $group = $this->getCategoryService()->getGroupByCode($groupCode);
        if (empty($group)) {
        	return array();
        }

        $choices = array();
        //$categories = $this->getCategoryService()->getCategoryTree($group['id']);
		$categories = $this->getCategoryService()->findGroupRootCategories($groupCode);

        foreach ($categories as $category) {
            $choices[$category['id']] = str_repeat(is_null($indent) ? '└' : '└'.$indent, ($category['depth']-1)) . $category['name'];
        }

        return $choices;
	}
	
	public function build2Choices($groupCode, $pid, $indent = '─')
	{
        $group = $this->getCategoryService()->getGroupByCode($groupCode);
        if (empty($group)) {
        	return array();
        }

        $choices = array();
        //$categories = $this->getCategoryService()->getCategoryTree($group['id']);
		$categories = $this->getCategoryService()->findGroupRoot2Categories($groupCode ,$pid);

        foreach ($categories as $category) {
            $choices[$category['id']] = str_repeat(is_null($indent) ? '└' : '└'.$indent, ($category['depth']-1)) . $category['name'];
        }

        return $choices;
	}
	
	public function build3Choices($groupCode, $pid, $indent = '─')
	{
        $group = $this->getCategoryService()->getGroupByCode($groupCode);
        if (empty($group)) {
        	return array();
        }

        $choices = array();
        $categories = $this->getCategoryService()->getCategoryTree($group['id']);
		//$categories = $this->getCategoryService()->findGroupRoot2Categories($groupCode ,$pid);
		
        foreach ($categories as $category) {
		if ($category['depth'] < '4')
            $choices[$category['id']] = str_repeat(is_null($indent) ? '└' : '└'.$indent, ($category['depth']-1)) . $category['name'];
        }

        return $choices;
	}

    private function getCategoryService()
    {
        return ServiceKernel::instance()->createService('Taxonomy.CategoryService');
    }
}
    