<?php
namespace Topxia\WebBundle\DataDict;

use Topxia\WebBundle\DataDict\DataDictInterface;

class UserRoleDict implements DataDictInterface
{
    public function getDict()
    {
        return array(
            'ROLE_USER' => '会员',
            'ROLE_TEACHER' => '享客',
			'ROLE_MANUFACTURER' => '厂家',
			'ROLE_MERCHANTS' => '商家',
			'ROLE_AGENT' => '代理',
            'ROLE_ADMIN' => '管理员',
            'ROLE_SUPER_ADMIN' => '超级管理员',
        );
    }

    public function getGroupedDict()
    {
        return $this->getDict();
    }

    public function getRenderedDict()
    {
        return $this->getDict();
    }

}