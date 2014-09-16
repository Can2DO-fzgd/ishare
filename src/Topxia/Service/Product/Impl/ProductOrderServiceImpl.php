<?php
namespace Topxia\Service\Product\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Product\ProductOrderService;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\StringToolkit;

class ProductOrderServiceImpl extends BaseService implements ProductOrderService
{

    public function createOrder($info)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createServiceException('用户未登录，不能创建订单');
        }

        if (!ArrayToolkit::requireds($info, array('productId', 'payment'))) {
            throw $this->createServiceException('订单数据缺失，创建产品订单失败。');
        }

        $product = $this->getProductService()->getProduct($info['productId']);
        if (empty($product)) {
            throw $this->createServiceException('产品不存在，创建产品订单失败。');
        }

        $order = array();

        $order['userId'] = $user->id;
        $order['title'] = "购买产品《{$product['name']}》";
        $order['targetType'] = 'product';
        $order['targetId'] = $product['id'];
        $order['payment'] = $info['payment'];
        $order['amount'] = $product['price'];
        $order['snPrefix'] = 'C';

        if (!empty($info['coupon'])) {
            $order['couponCode'] = $info['coupon'];
        }

        if (!empty($info['note'])) {
            $order['data'] = array('note' => $info['note']);
        }

        $order = $this->getOrderService()->createOrder($order);
        if (empty($order)) {
            throw $this->createServiceException('创建产品订单失败！');
        }

        // 免费产品，就直接将订单置为已购买
        if (intval($order['amount']*100) == 0) {
            list($success, $order) = $this->getOrderService()->payOrder(array(
                'sn' => $order['sn'],
                'status' => 'success', 
                'amount' => $order['amount'], 
                'paidTime' => time()
            ));

            $info = array(
                'orderId' => $order['id'],
                'remark'  => empty($order['data']['note']) ? '' : $order['data']['note'],
            );
            $this->getProductService()->becomeStudent($order['targetId'], $order['userId'], $info);
        }

        return $order;
    }

    public function doSuccessPayOrder($id)
    {
        $order = $this->getOrderService()->getOrder($id);
        if (empty($order) or $order['targetType'] != 'product') {
            throw $this->createServiceException('非产品订单，加入产品失败。');
        }

        $info = array(
            'orderId' => $order['id'],
            'remark'  => empty($order['data']['note']) ? '' : $order['data']['note'],
        );

        if (!$this->getProductService()->isProductStudent($order['targetId'], $order['userId'])) {
            $this->getProductService()->becomeStudent($order['targetId'], $order['userId'], $info);
        }

        return ;
    }

    public function applyRefundOrder($id, $amount, $reason, $container)
    {
        $order = $this->getOrderService()->getOrder($id);
        if (empty($order)) {
            throw $this->createServiceException('订单不存在，不嫩申请退款。');
        }

        $refund = $this->getOrderService()->applyRefundOrder($id, $amount, $reason);
        if ($refund['status'] == 'created') {
            $this->getProductService()->lockStudent($order['targetId'], $order['userId']);

            $setting = $this->getSettingService()->get('refund');
            $message = empty($setting) or empty($setting['applyNotification']) ? '' : $setting['applyNotification'];
            if ($message) {
                $productUrl = $container->get('router')->generate('product_show', array('id' => $product['id']));
                $variables = array(
                    'product' => "<a href='{$productUrl}'>{$product['name']}</a>"
                );
                $message = StringToolkit::template($message, $variables);
                $this->getNotificationService()->notify($refund['userId'], 'default', $message);
            }

        } elseif ($refund['status'] == 'success') {
            $this->getProductService()->removeStudent($order['targetId'], $order['userId']);
        }

        return $refund;

    }

    public function cancelRefundOrder($id)
    {
        $order = $this->getOrderService()->getOrder($id);
        if (empty($order) or $order['targetType'] != 'product') {
            throw $this->createServiceException('订单不存在，取消退款申请失败。');
        }

        $this->getOrderService()->cancelRefundOrder($id);

        if ($this->getProductService()->isProductStudent($order['targetId'], $order['userId'])) {
            $this->getProductService()->unlockStudent($order['targetId'], $order['userId']);
        }
    }

    protected function getProductService()
    {
        return $this->createService('Product.ProductService');
    }

    protected function getOrderService()
    {
        return $this->createService('Order.OrderService');
    }

    protected function getSettingService()
    {
        return $this->createService('System.SettingService');
    }

    private function getNotificationService()
    {
        return $this->createService('User.NotificationService');
    }

}