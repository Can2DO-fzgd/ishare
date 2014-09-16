<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;

class ProductMaterialManageController extends BaseController
{

	public function indexAction(Request $request, $productId, $lessonId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);
		$lesson = $this->getProductService()->getProductLesson($productId, $lessonId);
		$materials = $this->getMaterialService()->findLessonMaterials($lesson['id'], 0, 100);
		return $this->render('TopxiaWebBundle:ProductMaterialManage:material-modal.html.twig', array(
			'product' => $product,
			'lesson' => $lesson,
			'materials' => $materials,
            'storageSetting' => $this->setting('storage'),
            'targetType' => 'productmaterial',
            'targetId' => $product['id'],
		));
	}

	public function uploadAction(Request $request, $productId, $lessonId)
	{

        $product = $this->getProductService()->tryManageProduct($productId);
        $lesson = $this->getProductService()->getProductLesson($productId, $lessonId);
        if (empty($lesson)) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            if (empty($fields['fileId']) && empty($fields['link'])) {
                throw $this->createNotFoundException();
            }

            $fields['productId'] = $product['id'];
            $fields['lessonId'] = $lesson['id'];

            $material = $this->getMaterialService()->uploadMaterial($fields);

			return $this->render('TopxiaWebBundle:ProductMaterialManage:list-item.html.twig', array(
				'material' => $material,
			));
        }

		return $this->render('TopxiaWebBundle:ProductMaterial:upload-modal.html.twig', array(
			'form' => $form->createView(),
			'product' => $product,
		));

	}

	public function deleteAction(Request $request, $productId, $lessonId, $materialId)
	{
        $product = $this->getProductService()->tryManageProduct($productId);
        $this->getMaterialService()->deleteMaterial($productId, $materialId);
        return $this->createJsonResponse(true);
	}

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getMaterialService()
    {
        return $this->getServiceKernel()->createService('Product.MaterialService');
    }

    private function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }
}