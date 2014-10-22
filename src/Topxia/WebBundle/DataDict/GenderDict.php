<?php
namespace Topxia\WebBundle\DataDict;

class GenderDict implements DataDictInterface
{
    public function getDict() {
        return array(
            '1' => '男',
            '2' => '女',
        );
    }

    public function getRenderedDict() {
        return $this->getDict();
    }

    public function getGroupedDict() {
        return $this->getDict();
    }
}