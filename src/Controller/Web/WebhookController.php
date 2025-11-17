<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Repository\ClipRepository;
use App\Repository\LiveRepository;
use App\Repository\OrderRepository;
use App\Repository\OrderStatusRepository;
use App\Repository\UserRepository;
use App\Service\FirebaseMessagingService;
use App\Service\VideoProcessor;
use Bugsnag\Client;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly FirebaseMessagingService $firebaseMessagingService,
        private readonly Client $bugsnag,
        private readonly VideoProcessor $videoProcessor,
        private readonly LockFactory $lockFactory,
    ) {
    }

    /**
     * Webhooks Amazon Media Convert.
     *
     * @Route("/api/mediaconvert/webhooks", name="api_mediaconvert_webhooks", methods={"POST"})
     */
    public function mediaconvert(Request $request, ObjectManager $manager, ClipRepository $clipRepo): JsonResponse
    {
        try {
            $result = json_decode($request->getContent(), true);

            if (isset($result['Type']) && 'Notification' === $result['Type']) {
                $message = json_decode((string) $result['Message'], true);
                $status = $message['detail']['status'];
                $clipId = $message['detail']['userMetadata']['clipId'];
                $clip = $clipRepo->find($clipId);

                if ($clip) {
                    switch ($status) {
                        case 'COMPLETE':
                            $clip->setStatus('available');
                            break;

                        case 'ERROR':
                            $clip->setStatus('error');
                            break;

                        case 'CANCELED':
                            $clip->setStatus('canceled');
                            break;
                    }

                    $manager->flush();
                }
            }
        } catch (\Exception $error) {
            $this->bugsnag->notifyException($error, function ($report) use ($request): void {
                $report->setMetaData([
                    'mediaconvert' => [
                        'body' => $request->getContent(),
                        'headers' => $request->headers->all(),
                    ],
                ]);
            });
        }

        return $this->json(true, 200);
    }

    /**
     * Webhooks Agora RTC Channel Event.
     *
     * @Route("/api/agora/rtc/channel/event/webhooks", name="api_agora_rtc_channel_event", methods={"POST"})
     */
    public function agoraRTCChannelEvent(Request $request, ObjectManager $manager, LiveRepository $liveRepo, UserRepository $userRepo): JsonResponse
    {
        try {
            $result = json_decode($request->getContent(), true);

            if (isset($result['payload']['channelName']) && 'test_webhook' === $result['payload']['channelName']) {
                return $this->json(true, 200);
            }

            $noticeId = $result['noticeId'] ?? null;

            if (!$noticeId) {
                return $this->json(['message' => 'Notice ID manquant.'], 400);
            }

            $lock = $this->lockFactory->createLock('agora_notice_'.$noticeId);

            try {
                if (!$lock->acquire()) {
                    return $this->json(['message' => 'Traitement déjà en cours pour ce noticeId.'], 423);
                }

                $live = $liveRepo->findOneBy(['noticeId' => $noticeId]);

                if ($live) {
                    return $this->json(['message' => 'Enregistrement déjà effectué pour ce noticeId.'], 200);
                }

                if (isset($result['eventType'])) {
                    switch ($result['eventType']) {
                        case 103:
                            $cname = $result['payload']['channelName'];
                            $live = $liveRepo->findOneBy(['cname' => $cname]);

                            if ($live) {
                                if (2 != $live->getStatus() && !$live->getResourceId() && !$live->getSid()) {
                                    $live->setStatus(1);
                                    $manager->flush();

                                    $live->setNoticeId($noticeId);
                                    $manager->flush();

                                    $client = new GuzzleClient();
                                    $vname = 'vendor'.$live->getVendor()->getId();
                                    $appId = $this->getParameter('agora_app_id');

                                    $tokenUrl = $this->generateUrl('generate_agora_token_record', ['id' => $live->getId()], 0);
                                    $tokenResponse = $client->request('GET', $tokenUrl);
                                    $tokenData = json_decode($tokenResponse->getBody()->getContents(), true);

                                    if (!isset($tokenData['token'])) {
                                        throw new \Exception('Impossible de récupérer le token Agora.');
                                    }

                                    $tokenAgora = $tokenData['token'];

                                    $urlAcquire = sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/acquire', $appId);
                                    $headers = ['Content-Type' => 'application/json'];
                                    $bodyAcquire = json_encode([
                                        'cname' => $cname,
                                        'uid' => '123456789',
                                        'clientRequest' => new \stdClass(),
                                    ]);

                                    $resAcquire = $client->request('POST', $urlAcquire, [
                                        'headers' => $headers,
                                        'auth' => [$this->getParameter('agora_customer_id'), $this->getParameter('agora_customer_secret')],
                                        'body' => $bodyAcquire,
                                    ]);

                                    $acquireData = json_decode($resAcquire->getBody()->getContents(), true);
                                    if (!isset($acquireData['resourceId'])) {
                                        throw new \Exception('resourceId manquant lors de l\'acquisition.');
                                    }

                                    $resourceId = $acquireData['resourceId'];
                                    $live->setResourceId($resourceId);
                                    $manager->flush();

                                    $urlStart = sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/resourceid/%s/mode/mix/start', $appId, $resourceId);
                                    $bodyStart = json_encode([
                                        'cname' => $cname,
                                        'uid' => '123456789',
                                        'clientRequest' => [
                                            'token' => $tokenAgora,
                                            'recordingConfig' => [
                                                'maxIdleTime' => 120,
                                                'streamTypes' => 2,
                                                'channelType' => 0,
                                                'videoStreamType' => 0,
                                                'transcodingConfig' => [
                                                    'width' => 1080,
                                                    'height' => 1920,
                                                    'bitrate' => 3150,
                                                    'fps' => 30,
                                                ],
                                            ],
                                            'snapshotConfig' => [
                                                'captureInterval' => 3600,
                                                'fileType' => [
                                                    'jpg',
                                                ],
                                            ],
                                            'recordingFileConfig' => [
                                                'avFileType' => [
                                                    'hls',
                                                ],
                                            ],
                                            'storageConfig' => [
                                                'vendor' => $this->getParameter('s3_vendor'),
                                                'region' => $this->getParameter('s3_region'),
                                                'bucket' => $this->getParameter('s3_bucket'),
                                                'accessKey' => $this->getParameter('s3_access_key'),
                                                'secretKey' => $this->getParameter('s3_secret_key'),
                                                'fileNamePrefix' => [$vname, $cname],
                                            ],
                                        ],
                                    ]);

                                    $resStart = $client->request('POST', $urlStart, [
                                        'headers' => $headers,
                                        'auth' => [$this->getParameter('agora_customer_id'), $this->getParameter('agora_customer_secret')],
                                        'body' => $bodyStart,
                                    ]);

                                    $responseStart = json_decode($resStart->getBody()->getContents(), true);

                                    if (isset($responseStart['sid'])) {
                                        $sid = $responseStart['sid'];
                                        $live->setSid($sid);
                                        $manager->flush();
                                    } else {
                                        throw new \Exception('Impossible de démarrer l\'enregistrement.');
                                    }

                                    $followers = $userRepo->findUserFollowers($live->getVendor()->getUser());
                                    if ($followers) {
                                        foreach ($followers as $follower) {
                                            if ($follower->getPushToken()) {
                                                try {
                                                    $this->firebaseMessagingService->sendNotification(
                                                        'SWIPE LIVE',
                                                        '🔴 '.$live->getVendor()->getPseudo().' est actuellement en direct',
                                                        $follower->getPushToken()
                                                    );
                                                } catch (\Exception $error) {
                                                    $this->bugsnag->notifyException($error);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    return $this->json(['message' => 'Enregistrement déjà lancé.'], 200);
                                }
                            }
                            break;

                        case 104:
                            $cname = $result['payload']['channelName'];
                            $reason = $result['payload']['reason'];
                            $live = $liveRepo->findOneBy(['cname' => $cname]);

                            if ($live) {
                                $live->setStatus(2);
                                $live->setReason($reason);
                                $manager->flush();
                            }

                            break;
                    }
                }
            } catch (LockConflictedException $e) {
                return $this->json(['message' => 'Conflit de verrouillage. Traitement déjà en cours pour ce noticeId.'], 423);
            } finally {
                $lock->release();
            }
        } catch (\Exception $e) {
            $this->bugsnag->notifyException($e);
        }

        return $this->json(true, 200);
    }

    /**
     * Webhooks Agora Cloud Recording.
     *
     * @Route("/api/agora/cloud/recording/webhooks", name="api_agora_webhooks", methods={"POST"})
     */
    public function agoraCloudRecording(Request $request, ObjectManager $manager, LiveRepository $liveRepo): JsonResponse
    {
        try {
            $result = json_decode($request->getContent(), true);

            if (isset($result['eventType'])) {
                $cname = $result['payload']['cname'] ?? null;

                if (!$cname) {
                    return $this->json(['message' => 'Cname manquant.'], 400);
                }

                $lock = $this->lockFactory->createLock('agora_cloud_recording_'.$cname);

                try {
                    if (!$lock->acquire()) {
                        return $this->json(['message' => 'Traitement déjà en cours pour ce cname.'], 423);
                    }

                    switch ($result['eventType']) {
                        case 1:
                            throw new \Exception('An error occurs during the recording (cloud recording service)');
                        case 2:
                            throw new \Exception('A warning occurs during the recording (cloud recording service)');
                        case 31:
                            $fileList = $result['payload']['details']['fileList'] ?? [];
                            $live = $liveRepo->findOneBy(['cname' => $cname]);

                            if ($live && !empty($fileList)) {
                                $live->setFileList($fileList[0]['fileName']);
                                $manager->flush();

                                $clips = $live->getClips();
                                foreach ($clips as $clip) {
                                    $this->videoProcessor->processClip($clip);
                                }
                            }
                            break;

                        case 45:
                            $fileName = $result['payload']['details']['fileName'] ?? null;
                            $live = $liveRepo->findOneBy(['cname' => $cname]);

                            if ($live && $fileName) {
                                $live->setPreview($fileName);
                                $manager->flush();
                            }
                            break;
                    }
                } catch (LockConflictedException) {
                    return $this->json(['message' => 'Conflit de verrouillage. Traitement déjà en cours pour ce cname.'], 423);
                } finally {
                    $lock->release();
                }
            }
        } catch (\Exception $e) {
            $this->bugsnag->notifyException($e);
        }

        return $this->json(true, 200);
    }

    /**
     * Webhooks Stripe.
     *
     * @Route("/api/stripe/webhooks", name="api_stripe_webhooks", methods={"POST"})
     */
    public function stripe(Request $request, ObjectManager $manager, OrderRepository $orderRepo, LiveRepository $liveRepo): JsonResponse
    {
        $result = json_decode($request->getContent(), true);

        if (isset($result['object']) && 'event' === $result['object'] && isset($result['data']['object']['object']) && 'payment_intent' === $result['data']['object']['object']) {
            $order = $orderRepo->findOneBy(['paymentId' => $result['data']['object']['id']]);

            if ($order) {
                switch ($result['type']) {
                    case 'payment_intent.succeeded':
                        $order->setStatus('succeeded');
                        $vendor = $order->getVendor();
                        $pending = $order->getTotal() - $order->getFees() - $order->getShippingPrice();
                        $vendor->setPending((string) $pending);

                        foreach ($order->getLineItems() as $lineItem) {
                            if ($lineItem->getVariant()) {
                                $variant = $lineItem->getVariant();
                                $variant->setQuantity($variant->getQuantity() - $lineItem->getQuantity());
                            } else {
                                $product = $lineItem->getProduct();
                                $product->setQuantity($product->getQuantity() - $lineItem->getQuantity());
                            }
                        }

                        $order->setEventId($result['id']);
                        $order->setUpdatedAt(new \DateTime('now', timezone_open('Europe/Paris')));
                        $manager->flush();

                        $live = $liveRepo->vendorIsLive($vendor);

                        if (!$live && $vendor->getUser()->getPushToken()) {
                            try {
                                $data = [
                                    'route' => 'ListOrders',
                                    'type' => 'vente',
                                    'isOrder' => true,
                                    'orderId' => $order->getId(),
                                ];

                                $this->firebaseMessagingService->sendNotification(
                                    'SWIPE LIVE',
                                    'CLING 💰! Nouvelle commande pour un montant de '.str_replace('.', ',', (string) $order->getTotal()).'€',
                                    $vendor->getUser()->getPushToken(),
                                    $data
                                );
                            } catch (\Exception $error) {
                                $this->bugsnag->notifyException($error);
                            }
                        }

                        break;

                    default:
                        break;
                }
            }
        }

        return $this->json(true, 200);
    }

    /**
     * Webhooks Stripe Connect.
     *
     * @Route("/api/stripe/webhooks/connect", name="api_stripe_webhooks_connect", methods={"POST"})
     */
    public function stripeConnect(Request $request, ObjectManager $manager, OrderRepository $orderRepo): JsonResponse
    {
        $result = json_decode($request->getContent(), true);

        if (isset($result['object']) && 'event' === $result['object']) {
            switch ($result['type']) {
                case 'account.updated':
                case 'person.updated':
                case 'payout.failed':
                default:
                    break;
            }
        }

        return $this->json(true, 200);
    }

    /**
     * Webhooks Upelgo.
     *
     * @Route("/api/upelgo/webhooks", name="api_upelgo_webhooks", methods={"POST"})
     */
    public function upelgo(Request $request, ObjectManager $manager, OrderRepository $orderRepo, OrderStatusRepository $statusRepo): JsonResponse
    {
        $result = json_decode($request->getContent(), true);

        if (isset($result['action'])) {
            switch ($result['action']) {
                case 'ship':
                case 'delivery':
                case 'track':
                case 'multirate':
                case 'cancel':
                default:
                    break;
            }
        }

        return $this->json(true, 200);
    }
}
