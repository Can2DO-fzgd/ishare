<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;

class DefaultController extends BaseController
{

    public function popularProductsAction(Request $request)
    {
        $dateType = $request->query->get('dateType');

        $map = array();
        $students = $this->getProductService()->searchMember(array('date'=>$dateType, 'role'=>'student'), 0 , 10000);
        foreach ($students as $student) {
            if (empty($map[$student['productId']])) {
                $map[$student['productId']] = 1;
            } else {
                $map[$student['productId']] ++;
            }
        }
        asort($map, SORT_NUMERIC);
        $map = array_slice($map, 0, 5, true);

        $products = array();
        foreach ($map as $productId => $studentNum) {
            $product = $this->getProductService()->getProduct($productId);
            $product['addedStudentNum'] = $studentNum;
            $product['addedMoney'] = 0;

            $orders = $this->getOrderService()->searchOrders(array('targetType'=>'product', 'targetId'=>$productId, 'status' => 'paid', 'date'=>$dateType), 'latest', 0, 10000);

            foreach ($orders as $id => $order) {
                $product['addedMoney'] += $order['amount'];
            }

            $products[] = $product;
        }

        return $this->render('TopxiaAdminBundle:Default:popular-products-table.html.twig', array(
            'products' => $products
        ));
        
    }

    public function indexAction(Request $request)
    {
        return $this->render('TopxiaAdminBundle:Default:index.html.twig');
    }

    public function latestUsersBlockAction(Request $request)
    {
        $users = $this->getUserService()->searchUsers(array(), array('createdTime', 'DESC'), 0, 5);
        return $this->render('TopxiaAdminBundle:Default:latest-users-block.html.twig', array(
            'users'=>$users,
        ));
    }

    public function unsolvedQuestionsBlockAction(Request $request)
    {
        $questions = $this->getThreadService()->searchThreads(
            array('type' => 'question', 'postNum' => 0),
            'createdNotStick',
            0,5
        );

        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($questions, 'productId'));
        $askers = $this->getUserService()->findUsersByIds(ArrayToolkit::column($questions, 'userId'));

        $teacherIds = array();
        foreach (ArrayToolkit::column($products, 'teacherIds') as $teacherId) {
             $teacherIds = array_merge($teacherIds,$teacherId);
        }
        $teachers = $this->getUserService()->findUsersByIds($teacherIds);        

        return $this->render('TopxiaAdminBundle:Default:unsolved-questions-block.html.twig', array(
            'questions'=>$questions,
            'products'=>$products,
            'askers'=>$askers,
            'teachers'=>$teachers
        ));
    }

    public function latestPaidOrdersBlockAction(Request $request)
    {
        $orders = $this->getOrderService()->searchOrders(array('status'=>'paid'), 'latest', 0 , 5);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($orders, 'userId'));
        
        return $this->render('TopxiaAdminBundle:Default:latest-paid-orders-block.html.twig', array(
            'orders'=>$orders,
            'users'=>$users,
        ));
    }

    public function questionRemindTeachersAction(Request $request, $productId, $questionId)
    {
        $product = $this->getProductService()->getProduct($productId);
        $question = $this->getThreadService()->getThread($productId, $questionId);
        $questionUrl = $this->generateUrl('product_thread_show', array('productId'=>$product['id'], 'id'=> $question['id']), true);
        $questionTitle = strip_tags($question['title']);
        foreach ($product['teacherIds'] as $receiverId) {
            $result = $this->getNotificationService()->notify($receiverId, 'default',
                "产品《{$product['name']}》有新问题 <a href='{$questionUrl}' target='_blank'>{$questionTitle}</a>，请及时回答。");
        }

        return $this->createJsonResponse(array('success' => true, 'message' => 'ok'));
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getOrderService()
    {
        return $this->getServiceKernel()->createService('Order.OrderService');
    }

    protected function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }
}
