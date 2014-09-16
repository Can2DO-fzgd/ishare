<?php

namespace Topxia\Service\Product\Listener;

use Topxia\Service\Common\ServiceEventListener;
use Topxia\Service\Favorite\Event\FavoriteEvent;
use Topxia\Service\Favorite\Event\FavoriteEventListener;

class ProductFavoriteListener extends ServiceEventListener implements FavoriteEventListener {

    public function onFavoriteAdd(FavoriteEvent $event) {
        $productId = $event->favorite['typeId'];
        if (empty($productId)) {
            return ;
        }
        $this->getProductService()->waveProductFavoriteNum($productId, 1);
    }

    public function onFavoriteRemove (FavoriteEvent $event) {
        $productId = $event->favorite['typeId'];
        if (empty($productId)) {
            return ;
        }
        $this->getProductService()->waveProductFavoriteNum($productId, -1);
    }

    protected function getProductService() {
        return $this->container->get('topxia.product_service');
    }

}