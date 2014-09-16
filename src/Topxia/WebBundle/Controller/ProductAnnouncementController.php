<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;


class ProductAnnouncementController extends BaseController
{

	public function showAction(Request $request, $productId, $id)
	{
		list($product, $member) = $this->getProductService()->tryTakeProduct($productId);
        $announcement = $this->getProductService()->getProductAnnouncement($productId, $id);
		return $this->render('TopxiaWebBundle:Product:announcement-show-modal.html.twig',array(
			'announcement' => $announcement,
			'product' => $product,
			'canManage' => $this->getProductService()->canManageProduct($product['id']),
		));
	}

	public function showAllAction(Request $request, $productId)
	{

		$announcements = $this->getProductService()->findAnnouncements($productId, 0, 10000);
		$users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($announcements, 'userId'));
		return $this->render('TopxiaWebBundle:Product:announcement-show-all-modal.html.twig',array(
			'announcements'=>$announcements,
			'users'=>$users
		));
	}

	public function createAction(Request $request, $productId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);

	    if($request->getMethod() == 'POST'){
        	$announcement = $this->getProductService()->createAnnouncement($productId, $request->request->all());

        	if ($request->request->get('notify') == 'notify'){
	        	$count = $this->getProductService()->getProductStudentCount($productId);

	        	$members = $this->getProductService()->findProductStudents($productId, 0, $count);

	        	$productUrl = $this->generateUrl('product_show', array('id'=>$productId), true);
	        	foreach ($members as $member) {
		        	$result = $this->getNotificationService()->notify($member['userId'], 'default', "【产品公告】你正在关注的<a href='{$productUrl}' target='_blank'>{$product['name']}</a>发布了一个新的公告，<a href='{$productUrl}' target='_blank'>快去看看吧</a>");
	        	}
	        }

        	return $this->createJsonResponse(true);
		}

		return $this->render('TopxiaWebBundle:Product:announcement-write-modal.html.twig',array(
			'announcement' => array('id' => '', 'content' => ''),
			'product'=>$product,
		));
	}
	
	public function updateAction(Request $request, $productId, $id)
	{	
		$product = $this->getProductService()->tryManageProduct($productId);

        $announcement = $this->getProductService()->getProductAnnouncement($productId, $id);
        if (empty($announcement)) {
        	return $this->createNotFoundException("产品公告(#{$id})不存在。");
        }

	    if($request->getMethod() == 'POST') {
        	$this->getProductService()->updateAnnouncement($productId, $id, $request->request->all());
	        return $this->createJsonResponse(true);
		}

		return $this->render('TopxiaWebBundle:Product:announcement-write-modal.html.twig',array(
			'product' => $product,
			'announcement'=>$announcement,
		));
	}

	public function deleteAction(Request $request, $productId, $id)
	{
		$product = $this->getProductService()->tryManageProduct($productId);
		$this->getProductService()->deleteProductAnnouncement($productId, $id);
		return $this->createJsonResponse(true);
	}

	public function blockAction(Request $request, $product)
	{
		$announcements = $this->getProductService()->findAnnouncements($product['id'], 0, 10);
		return $this->render('TopxiaWebBundle:Product:announcement-block.html.twig',array(
			'product' => $product,
			'announcements' => $announcements,
			'canManage' => $this->getProductService()->canManageProduct($product['id']),
			'canTake' => $this->getProductService()->canTakeProduct($product)
		));
	}

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

}