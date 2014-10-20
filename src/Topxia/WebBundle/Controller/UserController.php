<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;
use Topxia\Common\StringToolkit;

class UserController extends BaseController
{

    public function headerBlockAction($user)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $userProfile = $this->getUserService()->getUserProfile($user['id']);
        $user = array_merge($user, $userProfile);

        if ($this->getCurrentUser()->isLogin()) {
            $isFollowed = $this->getUserService()->isFollowed($this->getCurrentUser()->id, $user['id']);
        } else {
            $isFollowed = false;
        }

        return $this->render('TopxiaWebBundle:User:header-block.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'isFollowed' => $isFollowed,
        ));
    }

    public function showAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);

        if(in_array('ROLE_ISHARE', $user['roles'])) {
            return $this->_teachAction($user);
        }

        return $this->_learnAction($user);
    }

    public function learnAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        return $this->_learnAction($user);
    }

    public function teachAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        return $this->_teachAction($user);
    }

    public function favoritedAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->tryGetUser($id);
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserFavoritedProductCount($user['id']),
            10
        );

        $products = $this->getProductService()->findUserFavoritedProducts(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('TopxiaWebBundle:User:products.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'products' => $products,
            'paginator' => $paginator,
            'type' => 'favorited',
        ));
    }

    public function followingAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->tryGetUser($id);
        $this->getUserService()->findUserFollowingCount($user['id']);

        $paginator = new Paginator(
            $this->get('request'),
            $this->getUserService()->findUserFollowingCount($user['id']),
            10
        );

        $followings = $this->getUserService()->findUserFollowing(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('TopxiaWebBundle:User:friend.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'friends' => $followings,
            'friendNav' => 'following',
        ));

    }

    public function followerAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->tryGetUser($id);
        $this->getUserService()->findUserFollowerCount($user['id']);

        $paginator = new Paginator(
            $this->get('request'),
            $this->getUserService()->findUserFollowerCount($user['id']),
            10
        );

        $followers = $this->getUserService()->findUserFollowers(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('TopxiaWebBundle:User:friend.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'friends' => $followers,
            'friendNav' => 'follower',
        ));
    }

    public function remindCounterAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $counter = array('newMessageNum' => 0, 'newNotificationNum' => 0);
        if ($user->isLogin()) {
            $counter['newMessageNum'] = $user['newMessageNum'];
            $counter['newNotificationNum'] = $user['newNotificationNum'];
        }
        return $this->createJsonResponse($counter);
    }

    public function unfollowAction(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        $this->getUserService()->unFollow($user['id'], $id);

        $userShowUrl = $this->generateUrl('user_show', array('id' => $user['id']), true);
        $message = "用户<a href='{$userShowUrl}' target='_blank'>{$user['userName']}</a>对你已经取消了关注！";
        $this->getNotificationService()->notify($id, 'default', $message);

        return $this->createJsonResponse(true);
    }

    public function followAction(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }
        $this->getUserService()->follow($user['id'], $id);

        $userShowUrl = $this->generateUrl('user_show', array('id' => $user['id']), true);
        $message = "用户<a href='{$userShowUrl}' target='_blank'>{$user['userName']}</a>已经关注了你！";
        $this->getNotificationService()->notify($id, 'default', $message);

        return $this->createJsonResponse(true);
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    protected function getNoteService()
    {
        return $this->getServiceKernel()->createService('Product.NoteService');
    }

    protected function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function tryGetUser($id)
    {
        $user = $this->getUserService()->getUser($id);
        if (empty($user)) {
            throw $this->createNotFoundException();
        }
        return $user;
    }

    private function _learnAction($user)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserLearnProductCount($user['id']),
            10
        );

        $products = $this->getProductService()->findUserLearnProducts(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('TopxiaWebBundle:User:products.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'products' => $products,
            'paginator' => $paginator,
            'type' => 'learn',
        ));
    }

    private function _teachAction($user)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->findUserTeachProductCount($user['id']),
            10
        );

        $products = $this->getProductService()->findUserTeachProducts(
            $user['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('TopxiaWebBundle:User:products.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'products' => $products,
            'paginator' => $paginator,
            'type' => 'teach',
        ));
    }

}