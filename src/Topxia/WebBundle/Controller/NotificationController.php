<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class NotificationController extends BaseController
{


    public function indexAction (Request $request)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }
        
        $paginator = new Paginator(
            $request,
            $this->getNotificationService()->getUserNotificationCount($user->id),
            20
        );

        $notifications = $this->getNotificationService()->findUserNotifications(
            $user->id,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $this->getNotificationService()->clearUserNewNotificationCounter($user->id);

        return $this->render('TopxiaWebBundle:Notification:index.html.twig', array(
            'notifications' => $notifications,
			'categories' => $categories,
            'paginator' => $paginator
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
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }
	
}