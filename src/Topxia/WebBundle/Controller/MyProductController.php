<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Topxia\WebBundle\Util\AvatarAlert;

class MyProductController extends BaseController
{

    public function indexAction (Request $request)
    {
        if ($this->getCurrentUser()->isTeacher()) {
            return $this->redirect($this->generateUrl('my_teaching_products')); 
        } else {
            return $this->redirect($this->generateUrl('my_products_learning'));
        }
    }

    public function learningAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserLeaningProductCount($currentUser['id']),
            12
        );

        $products = $this->getProductService()->findUserLeaningProducts(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('TopxiaWebBundle:MyProduct:learning.html.twig', array(
            'products'=>$products,
			'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    public function learnedAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserLeanedProductCount($currentUser['id']),
            12
        );

        $products = $this->getProductService()->findUserLeanedProducts(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = array();
        foreach ($products as $product) {
            $userIds = array_merge($userIds, $product['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('TopxiaWebBundle:MyProduct:learned.html.twig', array(
            'products'=>$products,
            'users'=>$users,
			'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    public function favoritedAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserFavoritedProductCount($currentUser['id']),
            12
        );
        
        $products = $this->getProductService()->findUserFavoritedProducts(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = array();
        foreach ($products as $favoriteProduct) {
            $userIds = array_merge($userIds, $favoriteProduct['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('TopxiaWebBundle:MyProduct:favorited.html.twig', array(
            'products'=>$products,
            'users'=>$users,
			'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

}