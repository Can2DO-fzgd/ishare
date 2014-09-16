<?php

namespace Topxia\Service\Product\Dao;

interface OrderLogDao
{

	public function getLog($id);

	public function addLog($log);

	public function findLogsByOrderId($orderId);

}