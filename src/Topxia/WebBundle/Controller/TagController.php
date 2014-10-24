<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class TagController extends BaseController
{
    /**
     * 获取所有热词，以JSONM的方式返回数据
     * 
     * @return JSONM Response
     */
    public function indexAction()
    {   
		$group = $this->getCategoryService()->getGroupByCode('product');
        if (empty($group)) {
            $categories1 = array();
        } else {
            $categories1 = $this->getCategoryService()->getCategoryTree($group['id']);
        }
		
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $tags = $this->getTagService()->findAllTags(0, 100);
		
		$categoriescount = $this->getCategoryService()->getAllCategoriesCountByPid();

        return $this->render('TopxiaWebBundle:Tag:index.html.twig',array(
			'categories' => $categories,
			'categories1' => $categories1,
            'tags'=>$tags,
			'categoriescount'=>$categoriescount
        ));
    }
	
	public function index1Action()
    {   
		$group = $this->getCategoryService()->getGroupByCode('product');
        if (empty($group)) {
            $categories1 = array();
        } else {
            $categories1 = $this->getCategoryService()->getCategoryTree($group['id']);
        }
		
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $tags = $this->getTagService()->findAllTags(0, 100);

        return $this->render('TopxiaWebBundle:Tag:index1.html.twig',array(
			'categories' => $categories,
			'categories1' => $categories1,
            'tags'=>$tags
        ));
    }

    public function showAction(Request $request,$name)
    {   
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $products = $paginator = null;

        $tag = $this->getTagService()->getTagByName($name);

        if($tag) {  
            $conditions = array(
                'state' => '1',
                'tagId' => $tag['id']
            );

            $paginator = new Paginator(
                $this->get('request'),
                $this->getProductService()->searchProductCount($conditions)
                , 10
            );       

            $products = $this->getProductService()->searchProducts(
                $conditions,
                'latest',
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
        }

        return $this->render('TopxiaWebBundle:Tag:show.html.twig',array(
            'tag'=>$tag,
			'categories' => $categories,
            'products'=>$products,
            'paginator' => $paginator
        ));
    }

    public function allAction()
    {
        $data = array();

        $tags = $this->getTagService()->findAllTags(0, 100);
        foreach ($tags as $tag) {
            $data[] = array('id' => $tag['id'],  'name' => $tag['name'] );
        }
        return $this->createJsonmResponse($data);
    }

    public function matchAction(Request $request)
    {
        $data = array();
        $queryString = $request->query->get('q');
        $callback = $request->query->get('callback');
        $tags = $this->getTagService()->getTagByLikeName($queryString);
        foreach ($tags as $tag) {
            $data[] = array('id' => $tag['id'],  'name' => $tag['name'] );
        }
        return new JsonResponse($data);
    }

    protected function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

}