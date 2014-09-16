<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\StringToolkit;

class ProductOrderController extends BaseController
{

    public function manageAction(Request $request)
    {
        return $this->forward('TopxiaAdminBundle:Order:manage', array(
            'request' => $request,
            'type' => 'product',
            'layout' => 'TopxiaAdminBundle:Product:layout.html.twig',
        ));
    }

    public function refundsAction(Request $request)
    {
        $conditions = $this->prepareRefundSearchConditions($request->query->all());

        $paginator = new Paginator(
            $this->get('request'),
            $this->getOrderService()->searchRefundCount($conditions),
            20
        );

        $refunds = $this->getOrderService()->searchRefunds(
            $conditions,
            'latest',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($refunds, 'userId'));
        $orders = $this->getOrderService()->findOrdersByIds(ArrayToolkit::column($refunds, 'orderId'));

        return $this->render('TopxiaAdminBundle:ProductOrder:refunds.html.twig', array(
            'refunds' => $refunds,
            'users' => $users,
            'orders' => $orders,
            'paginator' => $paginator
        ));
    }

    private function prepareRefundSearchConditions($conditions)
    {
        $conditions = array_filter($conditions);

        if (!empty($conditions['orderSn'])) {
            $order = $this->getOrderService()->getOrderBySn($conditions['orderSn']);
            $conditions['orderId'] = $order ? $order['id'] : -1;
            unset($conditions['orderSn']);
        }

        if (!empty($conditions['userName'])) {
            $user = $this->getUserService()->getUserByUserName($conditions['userName']);
            $conditions['userId'] = $user ? $user['id'] : -1;
            unset($conditions['userName']);
        }

        return $conditions;
    }

    public function cancelRefundAction(Request $request, $id)
    {
        $this->getProductOrderService()->cancelRefundOrder($id);
        return $this->createJsonResponse(true);
    }

    public function auditRefundAction(Request $request, $id)
    {
        $order = $this->getOrderService()->getOrder($id);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();

            $pass = $data['result'] == 'pass' ? true : false;
            $this->getOrderService()->auditRefundOrder($order['id'], $pass, $data['amount'], $data['note']);

            if ($pass) {
                if ($this->getProductService()->isProductStudent($order['targetId'], $order['userId'])) {
                    $this->getProductService()->removeStudent($order['targetId'], $order['userId']);
                }
            } else {
                if ($this->getProductService()->isProductStudent($order['targetId'], $order['userId'])) {
                    $this->getProductService()->unlockStudent($order['targetId'], $order['userId']);
                }
            }

            $this->sendAuditRefundNotification($order, $pass, $data['amount'], $data['note']);

            return $this->createJsonResponse(true);
        }

        return $this->render('TopxiaAdminBundle:ProductOrder:refund-confirm-modal.html.twig', array(
            'order' => $order,
        ));

    }

    private function sendAuditRefundNotification($order, $pass, $amount, $note)
    {
        $product = $this->getProductService()->getProduct($order['targetId']);
        if (empty($product)) {
            return false;
        }

        if ($pass) {
            $message = $this->setting('refund.successNotification', '');
        } else {
            $message = $this->setting('refund.failedNotification', '');
        }

        if (empty($message)) {
            return false;
        }

        $productUrl = $this->generateUrl('product_show', array('id' => $product['id']));
        $variables = array(
            'product' => "<a href='{$productUrl}'>{$product['name']}</a>",
            'amount' => $amount,
            'note' => $note,
        );
        
        $message = StringToolkit::template($message, $variables);
        $this->getNotificationService()->notify($order['userId'], 'default', $message);

        return true;
    }

    protected function getOrderService()
    {
        return $this->getServiceKernel()->createService('Order.OrderService');
    }

    protected function getProductOrderService()
    {
        return $this->getServiceKernel()->createService('Product.ProductOrderService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

}