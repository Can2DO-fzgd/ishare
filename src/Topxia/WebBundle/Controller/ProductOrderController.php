<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\StringToolkit;
use Topxia\Component\Payment\Payment;
use Topxia\WebBundle\Util\AvatarAlert;
use Symfony\Component\HttpFoundation\Response;

class ProductOrderController extends OrderController
{
    public $productId = 0;

    public function buyAction(Request $request, $id)
    {   
        $product = $this->getProductService()->getProduct($id);

        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        $previewAs = $request->query->get('previewAs');

        $member = $user ? $this->getProductService()->getProductMember($product['id'], $user['id']) : null;

        $member = $this->previewAsMember($previewAs, $member, $product);

        $productSetting = $this->getSettingService()->get('product', array());

        $userInfo = $this->getUserService()->getUserProfile($user['id']);
        $userInfo['approvalStatus'] = $user['approvalStatus'];

        $product = $this->getProductService()->getProduct($id);

        return $this->render('TopxiaWebBundle:ProductOrder:buy-modal.html.twig', array(
            'product' => $product,
            'payments' => $this->getEnabledPayments(),
            'user' => $userInfo,
            'avatarAlert' => AvatarAlert::alertJoinProduct($user),
            'productSetting' => $productSetting,
            'member' => $member
        ));
    }

    public function payAction(Request $request)
    {
        $formData = $request->request->all();

        $user = $this->getCurrentUser();
        if (empty($user)) {
            return $this->createMessageResponse('error', '用户未登录，创建产品订单失败。');
        }

        $userInfo = ArrayToolkit::parts($formData, array(
            'realName',
            'mphone',
            'qq',
            'companyname',
            'job'
        ));
        $userInfo = $this->getUserService()->updateUserProfile($user['id'], $userInfo);


        $order = $this->getProductOrderService()->createOrder($formData);

        if ($order['status'] == 'paid') {
            return $this->redirect($this->generateUrl('product_show', array('id' => $order['targetId'])));
        } else {
            $payRequestParams = array(
                'returnUrl' => $this->generateUrl('product_order_pay_return', array('name' => $order['payment']), true),
                'notifyUrl' => $this->generateUrl('product_order_pay_notify', array('name' => $order['payment']), true),
                'showUrl' => $this->generateUrl('product_show', array('id' => $order['targetId']), true),
            );

            return $this->forward('TopxiaWebBundle:Order:submitPayRequest', array(
                'order' => $order,
                'requestParams' => $payRequestParams,
            ));
        }
    }

    public function payReturnAction(Request $request, $name)
    {
        $controller = $this;
        return $this->doPayReturn($request, $name, function($success, $order) use(&$controller) {
            if (!$success) {
                $controller->generateUrl('product_show', array('id' => $order['targetId']));
            }

            $controller->getProductOrderService()->doSuccessPayOrder($order['id']);

            return $controller->generateUrl('product_show', array('id' => $order['targetId']));
        });
    }

    public function payNotifyAction(Request $request, $name)
    {
        $controller = $this;
        return $this->doPayNotify($request, $name, function($success, $order) use(&$controller) {
            if (!$success) {
                return ;
            }

            $controller->getProductOrderService()->doSuccessPayOrder($order['id']);

            return ;
        });
    }

    public function refundAction(Request $request , $id)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($id);
        $user = $this->getCurrentUser();

        if (empty($member) or empty($member['orderId'])) {
            throw $this->createAccessDeniedException('您不是产品的买家或尚未购买该产品，不能退货。');
        }

        $order = $this->getOrderService()->getOrder($member['orderId']);
        if (empty($order)) {
            throw $this->createNotFoundException();
        }

        if ('POST' == $request->getMethod()) {
            $data = $request->request->all();
            $reason = empty($data['reason']) ? array() : $data['reason'];
            $amount = empty($data['applyRefund']) ? 0 : null;

            $refund = $this->getProductOrderService()->applyRefundOrder($member['orderId'], $amount, $reason, $this->container);

            return $this->createJsonResponse($refund);
        }

        $maxRefundDays = (int) $this->setting('refund.maxRefundDays', 0);
        $refundOverdue = (time() - $order['createdTime']) > ($maxRefundDays * 86400);

        return $this->render('TopxiaWebBundle:ProductOrder:refund-modal.html.twig', array(
            'product' => $product,
            'order' => $order,
            'maxRefundDays' => $maxRefundDays,
            'refundOverdue' => $refundOverdue,
        ));
    }

    public function cancelRefundAction(Request $request , $id)
    {
        $product = $this->getProductService()->getProduct($id);
        if (empty($product)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getCurrentUser();
        if (empty($user)) {
            throw $this->createAccessDeniedException();
        }

        $member = $this->getProductService()->getProductMember($product['id'], $user['id']);
        if (empty($member) or empty($member['orderId'])) {
            throw $this->createAccessDeniedException('您不是产品的买家或尚未购买该产品，不能取消退款。');
        }

        $this->getProductOrderService()->cancelRefundOrder($member['orderId']);

        return $this->createJsonResponse(true);

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
                    'state' => 1,
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

    public function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    public function getProductOrderService()
    {
        return $this->getServiceKernel()->createService('Product.ProductOrderService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

    private function getEnabledPayments()
    {
        $enableds = array();

        $setting = $this->setting('payment', array());

        if (empty($setting['enabled'])) {
            return $enableds;
        }

        $payNames = array('alipay');
        foreach ($payNames as $payName) {
            if (!empty($setting[$payName . '_enabled'])) {
                $enableds[$payName] = array(
                    'type' => empty($setting[$payName . '_type']) ? '' : $setting[$payName . '_type'],
                );
            }
        }

        return $enableds;
    }

}