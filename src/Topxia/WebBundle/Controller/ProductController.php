<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Topxia\Common\Paginator;
use Topxia\WebBundle\Form\ProductType;
use Topxia\Service\Product\ProductService;
use Topxia\Common\ArrayToolkit;

class ProductController extends BaseController
{
    public function exploreAction(Request $request, $category)
    {
        if (!empty($category)) {
            if (ctype_digit((string) $category)) {
                $category = $this->getCategoryService()->getCategory($category);
            } else {
                $category = $this->getCategoryService()->getCategoryByCode($category);
            }

            if (empty($category)) {
                throw $this->createNotFoundException();
            }
        } else {
            $category = array('id' => null);
        }


        $sort = $request->query->get('sort', 'latest');

        $conditions = array(
            'status' => 'published',
            'categoryId' => $category['id'],
            'recommended' => ($sort == 'recommendedSeq') ? 1 : null
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->searchProductCount($conditions)
            , 10
        );


        $products = $this->getProductService()->searchProducts(
            $conditions, $sort,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $group = $this->getCategoryService()->getGroupByCode('product');
        if (empty($group)) {
            $categories = array();
        } else {
            $categories = $this->getCategoryService()->getCategoryTree($group['id']);
        }
     
        return $this->render('TopxiaWebBundle:Product:explore.html.twig', array(
            'products' => $products,
            'category' => $category,
            'sort' => $sort,
            'paginator' => $paginator,
            'categories' => $categories,
        ));
    }

    public function infoAction(Request $request, $id)
    {
        $product = $this->getProductService()->getProduct($id);
        $category = $this->getCategoryService()->getCategory($product['categoryId']);
        $tags = $this->getTagService()->findTagsByIds($product['tags']);
        return $this->render('TopxiaWebBundle:Product:info-modal.html.twig', array(
            'product' => $product,
            'category' => $category,
            'tags' => $tags,
        ));
    }

    public function teacherInfoAction(Request $request, $productId, $id)
    {
        $currentUser = $this->getCurrentUser();

        $product = $this->getProductService()->getProduct($productId);
        $user = $this->getUserService()->getUser($id);
        $profile = $this->getUserService()->getUserProfile($id);

        $isFollowing = $this->getUserService()->isFollowed($currentUser->id, $user['id']);

        return $this->render('TopxiaWebBundle:Product:teacher-info-modal.html.twig', array(
            'user' => $user,
            'profile' => $profile,
            'isFollowing' => $isFollowing,
        ));
    }

    public function membersAction(Request $request, $id)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($id);

        $paginator = new Paginator(
            $request,
            $this->getProductService()->getProductStudentCount($product['id']),
            6
        );

        $students = $this->getProductService()->findProductStudents(
            $product['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $studentUserIds = ArrayToolkit::column($students, 'userId');
        $users = $this->getUserService()->findUsersByIds($studentUserIds);
        $followingIds = $this->getUserService()->filterFollowingIds($this->getCurrentUser()->id, $studentUserIds);

        $progresses = array();
        foreach ($students as $student) {
            $progresses[$student['userId']] = $this->calculateUserLearnProgress($product, $student);
        }

        return $this->render('TopxiaWebBundle:Product:members-modal.html.twig', array(
            'product' => $product,
            'students' => $students,
            'users'=>$users,
            'progresses' => $progresses,
            'followingIds' => $followingIds,
            'paginator' => $paginator,
            'canManage' => $this->getProductService()->canManageProduct($product['id']),
        ));
    }

    /**
     * 如果用户已购买了此产品，或者用户是该产品的享客，则显示产品的Dashboard界面。
     * 如果用户未购买该产品，那么显示产品的营销界面。
     */
    public function showAction(Request $request, $id)
    {
        $product = $this->getProductService()->getProduct($id);
        
        if (empty($product)) {
            throw $this->createNotFoundException();
        }

        $previewAs = $request->query->get('previewAs');

        $user = $this->getCurrentUser();

        $items = $this->getProductService()->getProductItems($product['id']);

        $member = $user ? $this->getProductService()->getProductMember($product['id'], $user['id']) : null;

        if(!$this->canShowProduct($product, $user)) {
            return $this->createMessageResponse('info', '抱歉，产品已下线或未上线，不能购买，如有疑问请联系管理员！');
        }
        
        $member = $this->previewAsMember($previewAs, $member, $product);
        
        if ($member && empty($member['locked'])) {
            $learnStatuses = $this->getProductService()->getUserLearnLessonStatuses($user['id'], $product['id']);

            return $this->render("TopxiaWebBundle:Product:dashboard.html.twig", array(
                'product' => $product,
                'member' => $member,
                'items' => $items,
                'learnStatuses' => $learnStatuses
            ));
        }

        $groupedItems = $this->groupProductItems($items);
        $hasFavorited = $this->getProductService()->hasFavoritedProduct($product['id']);

        $category = $this->getCategoryService()->getCategory($product['categoryId']);
        $tags = $this->getTagService()->findTagsByIds($product['tags']);

        $checkMemberLevelResult = $productMemberLevel = null;
        if ($this->setting('vip.enabled')) {
            $productMemberLevel = $product['vipLevelId'] > 0 ? $this->getLevelService()->getLevel($product['vipLevelId']) : null;
            if ($productMemberLevel) {
                $checkMemberLevelResult = $this->getVipService()->checkUserInMemberLevel($user['id'], $productMemberLevel['id']);
            }
        }


        return $this->render("TopxiaWebBundle:Product:show.html.twig", array(
            'product' => $product,
            'member' => $member,
            'productMemberLevel' => $productMemberLevel,
            'checkMemberLevelResult' => $checkMemberLevelResult,
            'groupedItems' => $groupedItems,
            'hasFavorited' => $hasFavorited,
            'category' => $category,
            'previewAs' => $previewAs,
            'tags' => $tags,
        ));

    }

    private function canShowProduct($product, $user)
    {
        return ($product['status'] == 'published') or 
            $user->isAdmin() or 
            $this->getProductService()->isProductTeacher($product['id'],$user['id']) or
            $this->getProductService()->isProductStudent($product['id'],$user['id']);
    }

    private function previewAsMember($as, $member, $product)
    {
        $user = $this->getCurrentUser();
        if (empty($user->id)) {
            return null;
        }


        if (in_array($as, array('member', 'guest'))) {
            if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
                $member = array(
                    'id' => 0,
                    'productId' => $product['id'],
                    'userId' => $user['id'],
                    'levelId' => 0,
                    'learnedNum' => 0,
                    'isLearned' => 0,
                    'seq' => 0,
                    'isVisible' => 0,
                    'role' => 'teacher',
                    'locked' => 0,
                    'createdTime' => time(),
                    'deadline' => 0
                );
            }

            if (empty($member) or $member['role'] != 'teacher') {
                return $member;
            }

            if ($as == 'member') {
                $member['role'] = 'student';
            } else {
                $member = null;
            }
        }

        return $member;
    }

    private function groupProductItems($items)
    {
        $grouped = array();

        $list = array();
        foreach ($items as $id => $item) {
            if ($item['itemType'] == 'chapter') {
                if (!empty($list)) {
                    $grouped[] = array('type' => 'list', 'data' => $list);
                    $list = array();
                }
                $grouped[] = array('type' => 'chapter', 'data' => $item);
            } else {
                $list[] = $item;
            }
        }

        if (!empty($list)) {
            $grouped[] = array('type' => 'list', 'data' => $list);
        }

        return $grouped;
    }

    private function calculateUserLearnProgress($product, $member)
    {
        if ($product['lessonNum'] == 0) {
            return array('percent' => '0%', 'number' => 0, 'total' => 0);
        }

        $percent = intval($member['learnedNum'] / $product['lessonNum'] * 100) . '%';

        return array (
            'percent' => $percent,
            'number' => $member['learnedNum'],
            'total' => $product['lessonNum']
        );
    }
    
    public function favoriteAction(Request $request, $id)
    {
        $this->getProductService()->favoriteProduct($id);
        return $this->createJsonResponse(true);
    }

    public function unfavoriteAction(Request $request, $id)
    {
        $this->getProductService()->unfavoriteProduct($id);
        return $this->createJsonResponse(true);
    }

	public function createAction(Request $request)
	{  
        $user = $this->getUserService()->getCurrentUser();
        $userProfile = $this->getUserService()->getUserProfile($user['id']);

        if (false === $this->get('security.context')->isGranted('ROLE_ISHARE')) {
            throw $this->createAccessDeniedException();
        }

		$form = $this->createProductForm();

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $product = $form->getData();
                $product = $this->getProductService()->createProduct($product);
                return $this->redirect($this->generateUrl('product_manage', array('id' => $product['id'])));
            }
        }

		return $this->render('TopxiaWebBundle:Product:create.html.twig', array(
			'form' => $form->createView(),
            'userProfile'=>$userProfile
		));
	}

    public function exitAction(Request $request, $id)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($id);
        $user = $this->getCurrentUser();

        if (empty($member)) {
            throw $this->createAccessDeniedException('您不是产品的买家。');
        }

        if (!empty($member['orderId'])) {
            throw $this->createAccessDeniedException('有关联的订单，不能直接退货。');
        }

        $this->getProductService()->removeStudent($product['id'], $user['id']);
        return $this->createJsonResponse(true);
    }

    public function becomeUseMemberAction(Request $request, $id)
    {
        if (!$this->setting('vip.enabled')) {
            $this->createAccessDeniedException();
        }

        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            $this->createAccessDeniedException();
        }
        $this->getProductService()->becomeStudent($id, $user['id'], array('becomeUseMember' => true));
        return $this->createJsonResponse(true);
    }

    public function learnAction(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '亲！你好像忘了登录哦？', null, 3000, $this->generateUrl('login'));
        }

        $product = $this->getProductService()->getProduct($id);
        if (empty($product)) {
            throw $this->createNotFoundException("对不起！产品不存在，或已删除。");
        }

        if (!$this->getProductService()->canTakeProduct($id)) {
            return $this->createMessageResponse('info', "您还不是产品《{$product['name']}》的关注或购买会员，请先关注或购买。", null, 3000, $this->generateUrl('product_show', array('id' => $id)));
        }

        try{
            list($product, $member) = $this->getProductService()->tryTakeProduct($id);
            if ($member && !$this->getProductService()->isMemberNonExpired($product, $member)) {
                return $this->redirect($this->generateUrl('product_show',array('id' => $id)));
            }

            if ($member && $member['levelId'] > 0) {
                if ($this->getVipService()->checkUserInMemberLevel($member['userId'], $product['vipLevelId']) != 'ok') {
                    return $this->redirect($this->generateUrl('product_show',array('id' => $id)));
                }
            }



        }catch(Exception $e){
            throw $this->createAccessDeniedException('抱歉，未上线产品不能购买！');
        }
        return $this->render('TopxiaWebBundle:Product:learn.html.twig', array(
            'product' => $product,
        ));
    }

    public function addMemberExpiryDaysAction(Request $request, $productId, $userId)
    {
        $user = $this->getUserService()->getUser($userId);
        $product = $this->getProductService()->getProduct($productId);

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            $this->getProductService()->addMemberExpiryDays($productId, $userId, $fields['expiryDay']);
            return $this->createJsonResponse(true);
        }

        return $this->render('TopxiaWebBundle:ProductStudentManage:set-expiryday-modal.html.twig', array(
            'product' => $product,
            'user' => $user
        ));
    }


    /**
     * Block Actions
     */

    public function headerAction($product, $manage = false)
    {
        $user = $this->getCurrentUser();

        $member = $this->getProductService()->getProductMember($product['id'], $user['id']);

        $users = empty($product['teacherIds']) ? array() : $this->getUserService()->findUsersByIds($product['teacherIds']);

        if (empty($member)) {
            $member['deadline'] = 0; 
            $member['levelId'] = 0;
        }

        $isNonExpired = $this->getProductService()->isMemberNonExpired($product, $member);

        if ($member['levelId'] > 0) {
            $vipChecked = $this->getVipService()->checkUserInMemberLevel($user['id'], $product['vipLevelId']);
        } else {
            $vipChecked = 'ok';
        }


        return $this->render('TopxiaWebBundle:Product:header.html.twig', array(
            'product' => $product,
            'canManage' => $this->getProductService()->canManageProduct($product['id']),
            'member' => $member,
            'users' => $users,
            'manage' => $manage,
            'isNonExpired' => $isNonExpired,
            'vipChecked' => $vipChecked,
            'isAdmin' => $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')
        ));
    }

    public function teachersBlockAction($product)
    {
        $users = $this->getUserService()->findUsersByIds($product['teacherIds']);
        $profiles = $this->getUserService()->findUserProfilesByIds($product['teacherIds']);

        return $this->render('TopxiaWebBundle:Product:teachers-block.html.twig', array(
            'product' => $product,
            'users' => $users,
            'profiles' => $profiles,
        ));
    }

    public function progressBlockAction($product)
    {
        $user = $this->getCurrentUser();

        $member = $this->getProductService()->getProductMember($product['id'], $user['id']);
        $nextLearnLesson = $this->getProductService()->getUserNextLearnLesson($user['id'], $product['id']);

        $progress = $this->calculateUserLearnProgress($product, $member);
        return $this->render('TopxiaWebBundle:Product:progress-block.html.twig', array(
            'product' => $product,
            'member' => $member,
            'nextLearnLesson' => $nextLearnLesson,
            'progress'  => $progress,
        ));
    }

    public function latestMembersBlockAction($product, $count = 10)
    {
        $students = $this->getProductService()->findProductStudents($product['id'], 0, 12);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($students, 'userId'));
        return $this->render('TopxiaWebBundle:Product:latest-members-block.html.twig', array(
            'students' => $students,
            'users' => $users,
        ));
    }

    public function productsBlockAction($products, $view = 'list', $mode = 'default')
    {
        $userIds = array();
        foreach ($products as $product) {
            $userIds = array_merge($userIds, $product['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Product:products-block-{$view}.html.twig", array(
            'products' => $products,
            'users' => $users,
            'mode' => $mode,
        ));
    }
	
	public function productsBlock1Action($products, $view = 'list1', $mode = 'default')
    {
        $userIds = array();
        foreach ($products as $product) {
            $userIds = array_merge($userIds, $product['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Product:products-block-{$view}.html.twig", array(
            'products' => $products,
            'users' => $users,
            'mode' => $mode,
        ));
    }
	
	public function productsBlock2Action($products, $view = 'list2', $mode = 'default')
    {
        $userIds = array();
        foreach ($products as $product) {
            $userIds = array_merge($userIds, $product['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Product:products-block-{$view}.html.twig", array(
            'products' => $products,
            'users' => $users,
            'mode' => $mode,
        ));
    }
	
	public function productsBlock3Action($products, $view = 'list3', $mode = 'default')
    {
        $userIds = array();
        foreach ($products as $product) {
            $userIds = array_merge($userIds, $product['teacherIds']);
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Product:products-block-{$view}.html.twig", array(
            'products' => $products,
            'users' => $users,
            'mode' => $mode,
        ));
    }
	
    public function relatedProductsBlockAction($product)
    {   

        $products = $this->getProductService()->findProductsByAnyTagIdsAndStatus($product['tags'], 'published', array('Rating' , 'DESC'), 0, 4);
        
        return $this->render("TopxiaWebBundle:Product:related-products-block.html.twig", array(
            'products' => $products,
            'currentProduct' => $product
        ));
    }

    private function createProductForm()
    {
        return $this->createNamedFormBuilder('product')
            ->add('name', 'text')
            ->getForm();
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getLevelService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.LevelService');
    }

    protected function getVipService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.VipService');
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getOrderService()
    {
        return $this->getServiceKernel()->createService('Product.ProductOrderService');
    }

    private function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

}