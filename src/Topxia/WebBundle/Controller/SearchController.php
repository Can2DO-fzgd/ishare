<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class SearchController extends BaseController
{
    public function indexAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $products = $paginator = null;

        $keywords = $request->query->get('q');
        if (!$keywords) {
            goto response;
        }

        $conditions = array(
            'status' => 'published',
            'name' => $keywords
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

        response:
        return $this->render('TopxiaWebBundle:Search:index.html.twig', array(
            'products' => $products,
            'paginator' => $paginator,
			'categories' => $categories,
            'keywords' => $keywords,
        ));
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

}