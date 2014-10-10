<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class MyTeachingController extends BaseController
{
    //我的产品
    public function productsAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserTeachProductCount($user['id'], false),
            12
        );
        
        $products = $this->getProductService()->findUserTeachProducts(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount(),
            false
        );

        return $this->render('TopxiaWebBundle:MyTeaching:teaching.html.twig', array(
            'products'=>$products,
			'categories' => $categories,
            'paginator' => $paginator
        ));
    }

	public function threadsAction(Request $request, $type)
	{
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
		$user = $this->getCurrentUser();
		$myTeachingProductCount = $this->getProductService()->findUserTeachProductCount($user['id'], true);

        if (empty($myTeachingProductCount)) {
            return $this->render('TopxiaWebBundle:MyTeaching:threads.html.twig', array(
                'type'=>$type,
                'threads' => array()
            ));
        }

		$myTeachingProducts = $this->getProductService()->findUserTeachProducts($user['id'], 0, $myTeachingProductCount, true);

		$conditions = array(
			'productIds' => ArrayToolkit::column($myTeachingProducts, 'id'),
			'type' => $type);

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->searchThreadCountInProductIds($conditions),
            20
        );

        $threads = $this->getThreadService()->searchThreadInProductIds(
            $conditions,
            'createdNotStick',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($threads, 'latestPostUserId'));
        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($threads, 'productId'));
        $lessons = $this->getProductService()->findLessonsByIds(ArrayToolkit::column($threads, 'lessonId'));

    	return $this->render('TopxiaWebBundle:MyTeaching:threads.html.twig', array(
    		'paginator' => $paginator,
            'threads' => $threads,
            'users'=> $users,'categories' => $categories,
            'products' => $products,
            'lessons' => $lessons,
            'type'=>$type
    	));
	}

	protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
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