<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\FileToolkit;
use Topxia\Common\Paginator;
use Topxia\Service\Util\CloudClientFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ProductFileManageController extends BaseController
{

    public function indexAction(Request $request, $id)
    {
        $product = $this->getProductService()->tryManageProduct($id);

        $type = $request->query->get('type');
        $type = in_array($type, array('productlesson', 'productmaterial')) ? $type : 'productlesson';

        $conditions = array(
            'targetType'=> $type,
            'targetId'=>$product['id']
        );

        $paginator = new Paginator(
            $request,
            $this->getUploadFileService()->searchFileCount($conditions),
            20
        );

        $productLessons = $this->getUploadFileService()->searchFiles(
            $conditions,
            'latestCreated',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($productLessons, 'updatedUserId'));

        return $this->render('TopxiaWebBundle:ProductFileManage:index.html.twig', array(
            'type' => $type,
            'product' => $product,
            'productLessons' => $productLessons,
            'users' => ArrayToolkit::index($users, 'id'),
            'paginator' => $paginator
        ));
    }

    public function showAction(Request $request, $id, $fileId)
    {

        $product = $this->getProductService()->tryManageProduct($id);

        $file = $this->getUploadFileService()->getFile($fileId);

        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        if ($file['targetType'] == 'productlesson') {
            return $this->forward('TopxiaWebBundle:ProductLesson:file', array('fileId' => $file['id'], 'isDownload' => true));
        } else if ($file['targetType'] == 'productmaterial') {
            if ($file['storage'] == 'cloud') {
                $factory = new CloudClientFactory();
                $client = $factory->createClient();
                $client->download($client->getBucket(), $file['hashId'], 3600, $file['filename']);
            } else {
                return $this->createPrivateFileDownloadResponse($request, $file);
            }
        }

        throw $this->createNotFoundException();
    }

    public function convertAction(Request $request, $id, $fileId)
    {
        $product = $this->getProductService()->tryManageProduct($id);

        $file = $this->getUploadFileService()->getFile($fileId);
        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        if ($file['convertStatus'] != 'error') {
            return $this->createJsonResponse(array('status' => 'error', 'message' => '只有转换失败的文件，才能重新转换！'));
        }

        if ($file['type'] != 'video') {
            return $this->createJsonResponse(array('status' => 'error', 'message' => '只有视频文件，才能转换！'));
        }

        $factory = new CloudClientFactory();
        $client = $factory->createClient();

        $commands = array_keys($client->getVideoConvertCommands());
        $convertKey = substr(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36), 0, 12);
        $result = $client->convertVideo($client->getBucket(), $file['hashId'], implode(';', $commands), $this->generateUrl('uploadfile_cloud_convert_callback', array('key' => $convertKey), true));

        if (empty($result['persistentId'])) {
            return $this->createJsonResponse(array('status' => 'error', 'message' => '文件转换请求失败，请重试！'));
        }

        $convertHash = "{$result['persistentId']}:{$convertKey}";

        $this->getUploadFileService()->setFileConverting($file['id'], $convertHash);

        return $this->createJsonResponse(array('status' => 'ok'));
    }


    public function uploadProductFilesAction(Request $request, $id, $targetType)
    {
        $product = $this->getProductService()->tryManageProduct($id);
        $storageSetting = $this->getSettingService()->get('storage', array());
        return $this->render('TopxiaWebBundle:ProductFileManage:modal-upload-product-files.html.twig', array(
            'product' => $product,
            'storageSetting' => $storageSetting,
            'targetType' => $targetType,
            'targetId'=>$product['id'],
        ));
    }

    public function deleteProductFilesAction(Request $request, $id, $type)
    {
        $product = $this->getProductService()->tryManageProduct($id);

        $ids = $request->request->get('ids', array());

        $this->getUploadFileService()->deleteFiles($ids);


        return $this->createJsonResponse(true);
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    private function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
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