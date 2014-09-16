<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class LessonMaterialPluginController extends BaseController
{

    public function initAction (Request $request)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($request->query->get('productId'));
        $lesson = $this->getProductService()->getProductLesson($product['id'], $request->query->get('lessonId'));

        if ($lesson['mediaId'] > 0) {
            $file = $this->getUploadFileService()->getFile($lesson['mediaId']);
        } else {
            $file = null;
        }

        $lessonMaterials = $this->getMaterialService()->findLessonMaterials($lesson['id'], 0, 100);
        return $this->render('TopxiaWebBundle:LessonMaterialPlugin:index.html.twig',array(
            'materials' => $lessonMaterials,
            'product' => $product,
            'lesson' => $lesson,
            'file' => $file,
        ));
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getMaterialService()
    {
        return $this->getServiceKernel()->createService('Product.MaterialService');
    }
}