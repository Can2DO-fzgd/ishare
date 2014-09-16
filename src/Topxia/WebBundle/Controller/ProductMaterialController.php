<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Topxia\Common\ArrayToolkit;
use Topxia\Common\FileToolkit;
use Topxia\Common\Paginator;
use Topxia\Service\Util\CloudClientFactory;

class ProductMaterialController extends BaseController
{
    public function indexAction(Request $request, $id)
    {

        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '亲！你好像忘了登录哦？', null, 3000, $this->generateUrl('login'));
        }

        $product = $this->getProductService()->getProduct($id);
        if (empty($product)) {
            throw $this->createNotFoundException("对不起！产品不存在，或已删除。");
        }

        if (!$this->getProductService()->canTakeProduct($product)) {
            return $this->createMessageResponse('info', "您还不是产品《{$product['name']}》的关注或购买会员，请先关注或购买。", null, 3000, $this->generateUrl('product_show', array('id' => $id)));
        }


        list($product, $member) = $this->getProductService()->tryTakeProduct($id);

        $paginator = new Paginator(
            $request,
            $this->getMaterialService()->getMaterialCount($id),
            20
        );

        $materials = $this->getMaterialService()->findProductMaterials(
            $id,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $lessons = $this->getProductService()->getProductLessons($product['id']);
        $lessons = ArrayToolkit::index($lessons, 'id');

        return $this->render("TopxiaWebBundle:ProductMaterial:index.html.twig", array(
            'product' => $product,
            'lessons'=>$lessons,
            'materials' => $materials,
            'paginator' => $paginator,
        ));
    }

    public function downloadAction(Request $request, $productId, $materialId)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($productId);

        if ($member && !$this->getProductService()->isMemberNonExpired($product, $member)) {
            return $this->redirect($this->generateUrl('product_materials',array('id' => $productId)));
        }

        if ($member && $member['levelId'] > 0) {
            if ($this->getVipService()->checkUserInMemberLevel($member['userId'], $product['vipLevelId']) != 'ok') {
                return $this->redirect($this->generateUrl('product_show',array('id' => $id)));
            }
        }

        $material = $this->getMaterialService()->getMaterial($productId, $materialId);
        if (empty($material)) {
            throw $this->createNotFoundException();
        }

        $file = $this->getUploadFileService()->getFile($material['fileId']);
        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        if ($file['storage'] == 'cloud') {
            $factory = new CloudClientFactory();
            $client = $factory->createClient();
            $client->download($client->getBucket(), $file['hashId'], 3600, $file['filename']);
        } else {
            return $this->createPrivateFileDownloadResponse($request, $file);
        }
    }

    public function deleteAction(Request $request, $id, $materialId)
    {
        $product = $this->getProductService()->tryManageProduct($id);

        $this->getProductService()->deleteProductMaterial($id, $materialId);
        return $this->createJsonResponse(true);
    }

	public function latestBlockAction($product)
	{
        $materials = $this->getProductService()->findMaterials($product['id'], 0, 10);
		return $this->render('TopxiaWebBundle:ProductMaterial:latest-block.html.twig', array(
			'product' => $product,
            'materials' => $materials,
		));
	}

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getMaterialService()
    {
        return $this->getServiceKernel()->createService('Product.MaterialService');
    }

    private function getFileService()
    {
        return $this->getServiceKernel()->createService('Content.FileService');
    }

    private function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    private function createPrivateFileDownloadResponse(Request $request, $file)
    {

        $response = BinaryFileResponse::create($file['fullpath'], 200, array(), false);
        $response->trustXSendfileTypeHeader();

        $file['filename'] = urlencode($file['filename']);
        if (preg_match("/MSIE/i", $request->headers->get('User-Agent'))) {
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$file['filename'].'"');
        } else {
            $response->headers->set('Content-Disposition', "attachment; filename*=UTF-8''".$file['filename']);
        }

        $mimeType = FileToolkit::getMimeTypeByExtension($file['ext']);
        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }
}