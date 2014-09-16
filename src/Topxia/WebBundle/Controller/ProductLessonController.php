<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\FileToolkit;
use Topxia\Service\Util\CloudClientFactory;

class ProductLessonController extends BaseController
{

    public function previewAction(Request $request, $productId, $lessonId)
    {
        $product = $this->getProductService()->getProduct($productId);
        $lesson = $this->getProductService()->getProductLesson($productId, $lessonId);
        if (empty($lesson)) {
            throw $this->createNotFoundException();
        }

        if (empty($lesson['free'])) {
            return $this->forward('TopxiaWebBundle:ProductOrder:buy', array('id' => $productId), array('preview' => true));
        }

        if ($lesson['type'] == 'video' and $lesson['mediaSource'] == 'self') {
            $file = $this->getUploadFileService()->getFile($lesson['mediaId']);
            if (!empty($file['metas2']) && !empty($file['metas2']['hd']['key'])) {
                $factory = new CloudClientFactory();
                $client = $factory->createClient();
                $hls = $client->generateHLSQualitiyListUrl($file['metas2'], 3600);
            }
        }

        return $this->render('TopxiaWebBundle:ProductLesson:preview-modal.html.twig', array(
            'product' => $product,
            'lesson' => $lesson,
            'hlsUrl' => (isset($hls) and is_array($hls) and !empty($hls['url'])) ? $hls['url'] : '',
        ));
    }

    public function showAction(Request $request, $productId, $lessonId)
    {
    	list($product, $member) = $this->getProductService()->tryTakeProduct($productId);

    	$lesson = $this->getProductService()->getProductLesson($productId, $lessonId);

        $json = array();
        $json['number'] = $lesson['number'];

        $chapter = empty($lesson['chapterId']) ? null : $this->getProductService()->getChapter($product['id'], $lesson['chapterId']);
        if ($chapter['type'] == 'unit') {
            $unit = $chapter;
            $json['unitNumber'] = $unit['number'];

            $chapter = $this->getProductService()->getChapter($product['id'], $unit['parentId']);
            $json['chapterNumber'] = empty($chapter) ? 0 : $chapter['number'];

        } else {
            $json['chapterNumber'] = empty($chapter) ? 0 : $chapter['number'];
            $json['unitNumber'] = 0;
        }

        $json['title'] = $lesson['title'];
        $json['summary'] = $lesson['summary'];
        $json['type'] = $lesson['type'];
        $json['content'] = $lesson['content'];
        $json['status'] = $lesson['status'];
        $json['quizNum'] = $lesson['quizNum'];
        $json['materialNum'] = $lesson['materialNum'];
        $json['mediaId'] = $lesson['mediaId'];
        $json['mediaSource'] = $lesson['mediaSource'];

        if ($json['mediaSource'] == 'self') {
            $file = $this->getUploadFileService()->getFile($lesson['mediaId']);

            if (!empty($file)) {
                if ($file['storage'] == 'cloud') {
                    $factory = new CloudClientFactory();
                    $client = $factory->createClient();

                    $json['mediaConvertStatus'] = $file['convertStatus'];

                    if (!empty($file['metas2']) && !empty($file['metas2']['hd']['key'])) {
                        $url = $client->generateHLSQualitiyListUrl($file['metas2'], 3600);
                        $json['mediaHLSUri'] = $url['url'];
                    } else {
                        if (!empty($file['metas']) && !empty($file['metas']['hd']['key'])) {
                            $key = $file['metas']['hd']['key'];
                        } else {
                            if ($file['type'] == 'video') {
                                $key = null;
                            } else {
                                $key = $file['hashId'];
                            }
                        }

                        if ($key) {
                            $url = $client->generateFileUrl($client->getBucket(), $key, 3600);
                            $json['mediaUri'] = $url['url'];
                        } else {
                            $json['mediaUri'] = '';
                        }

                    }
                } else {
                    $json['mediaUri'] = $this->generateUrl('product_lesson_media', array('productId'=>$product['id'], 'lessonId' => $lesson['id']));
                }
            } else {
                $json['mediaUri'] = '';
            }
        } else {
            $json['mediaUri'] = $lesson['mediaUri'];
        }

    	return $this->createJsonResponse($json);
    }

    public function mediaAction(Request $request, $productId, $lessonId)
    {
        $lesson = $this->getProductService()->getProductLesson($productId, $lessonId);  
        if (empty($lesson) || empty($lesson['mediaId']) || ($lesson['mediaSource'] != 'self') ) {
            throw $this->createNotFoundException();
        }

        if (!$lesson['free']) {
            $this->getProductService()->tryTakeProduct($productId);
        }

        return $this->fileAction($request, $lesson['mediaId']);
    }

    public function mediaDownloadAction(Request $request, $productId, $lessonId)
    {
        if (!$this->setting('product.student_download_media')) {
            return $this->createMessageResponse('未开启产品介绍音视频下载。');
        }
        $lesson = $this->getProductService()->getProductLesson($productId, $lessonId);  
        if (empty($lesson) || empty($lesson['mediaId']) || ($lesson['mediaSource'] != 'self') ) {
            throw $this->createNotFoundException();
        }

        $this->getProductService()->tryTakeProduct($productId);

        return $this->fileAction($request, $lesson['mediaId'], true);
    }

    public function fileAction(Request $request, $fileId, $isDownload = false)
    {
        $file = $this->getUploadFileService()->getFile($fileId);
        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        if ($file['storage'] == 'cloud') {
            if ($isDownload) {
                $key = $file['hashId'];
            } else {
                if (!empty($file['metas']) && !empty($file['metas']['hd']['key'])) {
                    $key = $file['metas']['hd']['key'];
                } else {
                    $key = $file['hashId'];
                }
            }
            if (empty($key)){
                throw $this->createNotFoundException();
            }

            $factory = new CloudClientFactory();
            $client = $factory->createClient();

            if ($isDownload) {
                $client->download($client->getBucket(), $key, 3600, $file['filename']);
            } else {
                $client->download($client->getBucket(), $key);
            }
        }

        return $this->createLocalMediaResponse($request, $file, $isDownload);
    }

    public function learnStatusAction(Request $request, $productId, $lessonId)
    {
        $user = $this->getCurrentUser();
        $status = $this->getProductService()->getUserLearnLessonStatus($user['id'], $productId, $lessonId);
        return $this->createJsonResponse(array('status' => $status ? : 'unstart'));
    }

    public function learnStartAction(Request $request, $productId, $lessonId)
    {
        $result = $this->getProductService()->startLearnLesson($productId, $lessonId);
        return $this->createJsonResponse($result);
    }

    public function learnFinishAction(Request $request, $productId, $lessonId)
    {
        $this->getProductService()->finishLearnLesson($productId, $lessonId);

        $user = $this->getCurrentUser();
        $member = $this->getProductService()->getProductMember($productId, $user['id']);

        $response = array(
            'learnedNum' => empty($member['learnedNum']) ? 0 : $member['learnedNum'],
            'isLearned' => empty($member['isLearned']) ? 0 : $member['isLearned'],
        );

        return $this->createJsonResponse($response);
    }

    public function learnCancelAction(Request $request, $productId, $lessonId)
    {
        $this->getProductService()->cancelLearnLesson($productId, $lessonId);
        return $this->createJsonResponse(true);
    }

    private function createLocalMediaResponse(Request $request, $file, $isDownload = false)
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

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getDiskService()
    {
        return $this->getServiceKernel()->createService('User.DiskService');
    }

    private function getFileService()
    {
        return $this->getServiceKernel()->createService('Content.FileService');
    }

    private function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

}