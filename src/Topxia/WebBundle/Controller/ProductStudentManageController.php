<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;

class ProductStudentManageController extends BaseController
{

    public function indexAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $product = $this->getProductService()->tryManageProduct($id);

        $paginator = new Paginator(
            $request,
            $this->getProductService()->getProductStudentCount($product['id']),
            20
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
      
        return $this->render('TopxiaWebBundle:ProductStudentManage:index.html.twig', array(
            'product' => $product,
            'students' => $students,
            'users'=>$users,
            'progresses' => $progresses,
			'categories' => $categories,
            'followingIds' => $followingIds,
            'paginator' => $paginator,
            'canManage' => $this->getProductService()->canManageProduct($product['id']),
        ));

    }

    public function createAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $product = $this->getProductService()->tryAdminProduct($id);

        $currentUser = $this->getCurrentUser();

        if ('POST' == $request->getMethod()) {
            $data = $request->request->all();
            $user = $this->getUserService()->getUserByUserName($data['userName']);
            if (empty($user)) {
                throw $this->createNotFoundException("用户{$data['userName']}不存在");
            }

            if ($this->getProductService()->isProductStudent($product['id'], $user['id'])) {
                throw $this->createNotFoundException("用户已经是购买会员，不能添加！");
            }

            $order = $this->getOrderService()->createOrder(array(
                'userId' => $user['id'],
                'title' => "关注产品《{$product['name']}》(管理员添加)",
                'targetType' => 'product',
                'targetId' => $product['id'],
                'amount' => $data['price'],
                'payment' => 'none',
                'snPrefix' => 'C',
            ));

            $this->getOrderService()->payOrder(array(
                'sn' => $order['sn'],
                'status' => 'success', 
                'amount' => $order['amount'], 
                'paidTime' => time(),
            ));

            $info = array(
                'orderId' => $order['id'],
                'note'  => $data['remark'],
            );

            $this->getProductService()->becomeStudent($order['targetId'], $order['userId'], $info);

            $member = $this->getProductService()->getProductMember($product['id'], $user['id']);

            $this->getNotificationService()->notify($member['userId'], 'student-create', array(
                'productId' => $product['id'], 
                'productName' => $product['name'],
            ));



            $this->getLogService()->info('product', 'add_student', "产品《{$product['name']}》(#{$product['id']})，添加关注会员{$user['userName']}(#{$user['id']})，备注：{$data['remark']}");

            return $this->createStudentTrResponse($product, $member);
        }

        return $this->render('TopxiaWebBundle:ProductStudentManage:create-modal.html.twig',array(
            'categories' => $categories,
			'product'=>$product
        ));
    }

    public function removeAction(Request $request, $productId, $userId)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $product = $this->getProductService()->tryAdminProduct($productId);

        $this->getProductService()->removeStudent($productId, $userId);

        $this->getNotificationService()->notify($userId, 'student-remove', array(
            'productId' => $product['id'], 
			'categories' => $categories,
            'productName' => $product['name'],
        ));

        return $this->createJsonResponse(true);
    }

    public function exportCsvAction (Request $request, $id)
    {   
        $product = $this->getProductService()->tryAdminProduct($id);

        $productMembers = $this->getProductService()->searchMembers( array('productId' => $product['id'],'role' => 'student'),array('createdTime', 'DESC'), 0, 1000);

        $studentUserIds = ArrayToolkit::column($productMembers, 'userId');

        $users = $this->getUserService()->findUsersByIds($studentUserIds);
        $users = ArrayToolkit::index($users, 'id');

        $profiles = $this->getUserService()->findUserProfilesByIds($studentUserIds);
        $profiles = ArrayToolkit::index($profiles, 'id');
        
        $progresses = array();
        foreach ($productMembers as $student) {
            $progresses[$student['userId']] = $this->calculateUserLearnProgress($product, $student);
        }

        $str = "用户名,关注时间,关注进度,姓名,Email,公司,头衔,电话,微信号,QQ号"."\r\n";

        $students = array();

        foreach ($productMembers as $productMember) {
            $member = "";
            $member .= $users[$productMember['userId']]['userName'].",";
            $member .= date('Y-n-d H:i:s', $productMember['createdTime']).",";
            $member .= $progresses[$productMember['userId']]['percent'].",";
            $member .= $profiles[$productMember['userId']]['truename'] ? $profiles[$productMember['userId']]['truename']."," : "-".",";
            $member .= $users[$productMember['userId']]['email'].",";
            $member .= $profiles[$productMember['userId']]['company'] ? $profiles[$productMember['userId']]['company']."," : "-".",";
            $member .= $users[$productMember['userId']]['title'] ? $users[$productMember['userId']]['title']."," : "-".",";
            $member .= $profiles[$productMember['userId']]['mobile'] ? $profiles[$productMember['userId']]['mobile']."," : "-".",";
            $member .= $profiles[$productMember['userId']]['weixin'] ? $profiles[$productMember['userId']]['weixin']."," : "-".",";
            $member .= $profiles[$productMember['userId']]['qq'] ? $profiles[$productMember['userId']]['qq']."," : "-";
            $students[] = $member;   
        };

        $str .= implode("\r\n",$students);
        $str = chr(239) . chr(187) . chr(191) . $str;
        
        $filename = sprintf("product-%s-students-(%s).csv", $product['id'], date('Y-n-d'));

        $userId = $this->getCurrentUser()->id;

        $response = new Response();
        $response->headers->set('Content-type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Content-length', strlen($str));
        $response->setContent($str);

        return $response;
    }

    public function remarkAction(Request $request, $productId, $userId)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $product = $this->getProductService()->tryManageProduct($productId);
        $user = $this->getUserService()->getUser($userId);
        $member = $this->getProductService()->getProductMember($productId, $userId);

        if ('POST' == $request->getMethod()) {
            $data = $request->request->all();
            $member = $this->getProductService()->remarkStudent($product['id'], $user['id'], $data['remark']);
            return $this->createStudentTrResponse($product, $member);
        }

        return $this->render('TopxiaWebBundle:ProductStudentManage:remark-modal.html.twig',array(
            'member'=>$member,
            'user'=>$user,
			'categories' => $categories,
            'product'=>$product
        ));
    }

    public function checkUserNameAction(Request $request, $id)
    {
        $userName = $request->query->get('value');
        $result = $this->getUserService()->isUserNameAvaliable($userName);
        if ($result) {
            $response = array('success' => false, 'message' => '该用户不存在');
        } else {
            $user = $this->getUserService()->getUserByUserName($userName);
            $isProductStudent = $this->getProductService()->isProductStudent($id, $user['id']);
            if($isProductStudent){
                $response = array('success' => false, 'message' => '该用户已是本产品的关注用户了');
            } else {
                $response = array('success' => true, 'message' => '');
            }
            
            $isProductTeacher = $this->getProductService()->isProductTeacher($id, $user['id']);
            if($isProductTeacher){
                $response = array('success' => false, 'message' => '该用户是本产品的享客，不能添加');
            }
        }
        return $this->createJsonResponse($response);
    }

    public function showAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->getUserService()->getUser($id);
        $profile = $this->getUserService()->getUserProfile($id);
        $profile['title'] = $user['title'];
        return $this->render('TopxiaWebBundle:ProductStudentManage:show-modal.html.twig', array(
            'user' => $user,
			'categories' => $categories,
            'profile' => $profile,
        ));
    }

    private function calculateUserLearnProgress($product, $member)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        if ($product['lessonNum'] == 0) {
            return array('percent' => '0%', 'number' => 0, 'total' => 0);
        }

        $percent = intval($member['learnedNum'] / $product['lessonNum'] * 100) . '%';

        return array (
            'percent' => $percent,
			'categories' => $categories,
            'number' => $member['learnedNum'],
            'total' => $product['lessonNum']
        );
    }

    private function createStudentTrResponse($product, $student)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $user = $this->getUserService()->getUser($student['userId']);
        $isFollowing = $this->getUserService()->isFollowed($this->getCurrentUser()->id, $student['userId']);
        $progress = $this->calculateUserLearnProgress($product, $student);

        return $this->render('TopxiaWebBundle:ProductStudentManage:tr.html.twig', array(
            'product' => $product,
            'student' => $student,
			'categories' => $categories,
            'user'=>$user,
            'progress' => $progress,
            'isFollowing' => $isFollowing,
        ));
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

    private function getOrderService()
    {
        return $this->getServiceKernel()->createService('Order.OrderService');
    }
	
	protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }
}