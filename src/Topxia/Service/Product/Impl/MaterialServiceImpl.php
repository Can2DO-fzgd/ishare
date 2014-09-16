<?php
namespace Topxia\Service\Product\Impl;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Topxia\Service\Common\BaseService;
use Topxia\Service\Product\MaterialService;
use Topxia\Common\ArrayToolkit;

class MaterialServiceImpl extends BaseService implements MaterialService
{

	public function uploadMaterial($material)
	{
		if (!ArrayToolkit::requireds($material, array('productId', 'fileId'))) {
			throw $this->createServiceException('参数缺失，上传失败！');
		}

		$product = $this->getProductService()->getProduct($material['productId']);
		if (empty($product)) {
			throw $this->createServiceException('产品不存在，上传资料失败！');
		}

        $fields = array(
            'productId' => $material['productId'],
            'lessonId' => empty($material['lessonId']) ? 0 : $material['lessonId'],
            'description'  => empty($material['description']) ? '' : $material['description'],
            'userId' => $this->getCurrentUser()->id,
            'createdTime' => time(),
        );

        if (empty($material['fileId'])) {
            if (empty($material['link'])) {
                throw $this->createServiceException('资料链接地址不能为空，添加资料失败！');
            }
            $fields['fileId'] = 0;
            $fields['link'] = $material['link'];
            $fields['title'] = empty($material['description']) ? $material['link'] : $material['description'];
        } else {
            $fields['fileId'] = (int) $material['fileId'];
    		$file = $this->getUploadFileService()->getFile($material['fileId']);
    		if (empty($file)) {
    			throw $this->createServiceException('文件不存在，上传资料失败！');
    		}
            $fields['link'] = '';
            $fields['title'] = $file['filename'];
            $fields['fileSize'] = $file['size'];
        }

		$material =  $this->getMaterialDao()->addMaterial($fields);

		$this->getProductService()->increaseLessonMaterialCount($fields['lessonId']);

		return $material;
	}

	public function deleteMaterial($productId, $materialId)
	{
		$material = $this->getMaterialDao()->getMaterial($materialId);
		if (empty($material) or $material['productId'] != $productId) {
			throw $this->createNotFoundException('产品资料不存在，删除失败。');
		}
		$this->getMaterialDao()->deleteMaterial($materialId);
		if($material['lessonId']){
		   $count = $this->getMaterialDao()->getLessonMaterialCount($productId,$material['lessonId']);
		   $this->getProductService()->resetLessonMaterialCount($material['lessonId'], $count);
		}
	}

	public function deleteMaterialsByLessonId($lessonId)
	{
		return $this->getMaterialDao()->deleteMaterialsByLessonId($lessonId);
	}

	public function deleteMaterialsByProductId($productId)
	{
		return $this->getMaterialDao()->deleteMaterialsByProductId($productId);
	}

	public function getMaterial($productId, $materialId)
	{
		$material = $this->getMaterialDao()->getMaterial($materialId);
		if (empty($material) or $material['productId'] != $productId) {
			return null;
		}
		return $material;
	}

	public function findProductMaterials($productId, $start, $limit)
	{
		return $this->getMaterialDao()->findMaterialsByProductId($productId, $start, $limit);
	}

    public function findLessonMaterials($lessonId, $start, $limit)
    {
        return $this->getMaterialDao()->findMaterialsByLessonId($lessonId, $start, $limit);
    }

	public function getMaterialCount($productId)
	{
		return $this->getMaterialDao()->getMaterialCountByProductId($productId);
	}

    private function getMaterialDao()
    {
    	return $this->createDao('Product.ProductMaterialDao');
    }

    private function getProductService()
    {
    	return $this->createService('Product.ProductService');
    }

    private function getFileService()
    {
    	return $this->createService('Content.FileService');
    }

    private function getUploadFileService()
    {
        return $this->createService('File.UploadFileService');
    }

}