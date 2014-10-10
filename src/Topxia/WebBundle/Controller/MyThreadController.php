<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class MyThreadController extends BaseController
{

    public function discussionsAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->getCurrentUser();

        $conditions = array(
            'userId'=>$user['id'],
            'type'=>'discussion'
        );

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->searchThreadCount($conditions),
            20
        );

        $threads = $this->getThreadService()->searchThreads(
            $conditions,
            'createdNotStick',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($threads, 'productId'));
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($threads, 'latestPostUserId'));

        return $this->render('TopxiaWebBundle:MyThread:discussions.html.twig',array(
            'products'=>$products,
            'users'=>$users,
            'threads'=>$threads,
			'categories' => $categories,
            'paginator' => $paginator));
    }

    public function questionsAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->getCurrentUser();

        $conditions = array(
            'userId' => $user['id'],
            'type' => 'question',
        );

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->searchThreadCount($conditions),
            20
        );

        $threads = $this->getThreadService()->searchThreads(
            $conditions,
            'createdNotStick',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($threads, 'productId'));
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($threads, 'latestPostUserId'));

        return $this->render('TopxiaWebBundle:MyThread:questions.html.twig',array(
            'products'=>$products,
            'users'=>$users,
            'threads'=>$threads,
			'categories' => $categories,
            'paginator' => $paginator));
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
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