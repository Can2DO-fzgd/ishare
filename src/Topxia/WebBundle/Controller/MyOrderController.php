<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class MyOrderController extends BaseController
{

    public function indexAction (Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
    	$user = $this->getCurrentUser();

    	$conditions = array(
    		'userId' => $user['id'],
		);

        $paginator = new Paginator(
            $request,
            $this->getOrderService()->searchOrderCount($conditions),
            20
        );

        $orders = $this->getOrderService()->searchOrders(
        	$conditions,
        	'latest',
        	$paginator->getOffsetCount(),
        	$paginator->getPerPageCount()
    	);

        return $this->render('TopxiaWebBundle:MyOrder:index.html.twig',array(
        	'orders' => $orders,
			'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    public function refundsAction(Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
    	$user = $this->getCurrentUser();

        $paginator = new Paginator(
            $request,
            $this->getOrderService()->findUserRefundCount($user['id']),
            20
        );

        $refunds = $this->getOrderService()->findUserRefunds(
        	$user['id'],
        	$paginator->getOffsetCount(),
        	$paginator->getPerPageCount()
    	);

    	$orders = $this->getOrderService()->findOrdersByIds(ArrayToolkit::column($refunds, 'orderId'));

        return $this->render('TopxiaWebBundle:MyOrder:refunds.html.twig',array(
        	'refunds' => $refunds,
        	'orders' => $orders,
			'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    public function cancelRefundAction(Request $request, $id)
    {
        $this->getProductOrderService()->cancelRefundOrder($id);
        return $this->createJsonResponse(true);
    }

    private function getOrderService()
    {
        return $this->getServiceKernel()->createService('Order.OrderService');
    }

    private function getProductOrderService()
    {
        return $this->getServiceKernel()->createService('Product.ProductOrderService');
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

}