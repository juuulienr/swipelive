<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\Clip;
use App\Entity\Comment;
use App\Entity\Live;
use App\Entity\LiveProducts;
use App\Entity\User;
use App\Entity\Vendor;
use App\Repository\CommentRepository;
use App\Repository\FollowRepository;
use App\Repository\LiveProductsRepository;
use App\Repository\OrderRepository;
use BoogieFromZk\AgoraToken\RtcTokenBuilder2;
use Doctrine\Persistence\ObjectManager;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use GuzzleHttp\Client;
use Pusher\Pusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class LiveAPIController extends AbstractController
{
    public function __construct()
    {
    }

    public function getUser(): ?User
    {
        $user = parent::getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * @Route("/user/api/agora/token/host/{id}", name="generate_agora_token_host")
     */
    public function generateHostToken(Live $live, ObjectManager $manager): JsonResponse
    {
        $appID = $this->getParameter('agora_app_id');
        $appCertificate = $this->getParameter('agora_app_certificate');
        $expiresInSeconds = 86400;
        $cname = 'Live'.$live->getId();
        $uid = (int) $this->getUser()->getId();
        $role = RtcTokenBuilder2::ROLE_PUBLISHER;

        $live->setCname($cname);
        $manager->flush();

        try {
            $token = RtcTokenBuilder2::buildTokenWithUid($appID, $appCertificate, $cname, $uid, $role, $expiresInSeconds);

            return $this->json(['token' => $token], 200);
        } catch (\Exception) {
            return $this->json('Failed to generate token', 500);
        }
    }

    /**
     * @Route("/user/api/agora/token/audience/{id}", name="generate_agora_token_audience")
     */
    public function generateAudienceToken(Live $live): JsonResponse
    {
        $appID = $this->getParameter('agora_app_id');
        $appCertificate = $this->getParameter('agora_app_certificate');
        $expiresInSeconds = 86400;
        $cname = 'Live'.$live->getId();
        $role = RtcTokenBuilder2::ROLE_SUBSCRIBER;
        $uid = (int) $this->getUser()->getId();

        try {
            $token = RtcTokenBuilder2::buildTokenWithUid($appID, $appCertificate, $cname, $uid, $role, $expiresInSeconds);

            return $this->json(['token' => $token], 200);
        } catch (\Exception) {
            return $this->json('Failed to generate token', 500);
        }
    }

    /**
     * @Route("/agora/token/record/{id}", name="generate_agora_token_record")
     */
    public function generateRecordToken(Live $live): JsonResponse
    {
        $appID = $this->getParameter('agora_app_id');
        $appCertificate = $this->getParameter('agora_app_certificate');
        $expiresInSeconds = 86400; // Expire dans 24 heures
        $role = RtcTokenBuilder2::ROLE_SUBSCRIBER;

        try {
            $token = RtcTokenBuilder2::buildTokenWithUid($appID, $appCertificate, $live->getCname(), 123456789, $role, $expiresInSeconds);

            return $this->json(['token' => $token], 200);
        } catch (\Exception) {
            return $this->json('Failed to generate token', 500);
        }
    }

    /**
     * Préparer un live.
     *
     * @Route("/user/api/prelive", name="user_api_prelive_step1", methods={"POST"})
     */
    public function prelive(Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        if ($json = $request->getContent()) {
            $live = $serializer->deserialize($json, Live::class, 'json');
            $live->setVendor($this->getUser()->getVendor());

            $manager->persist($live);
            $manager->flush();

            $liveProducts = $live->getLiveProducts()->toArray();

            if (1 === \count($liveProducts)) {
                $liveProducts[0]->setPriority(1);
                $manager->flush();
            }

            return $this->json($live, 200, [], [
                'groups' => 'live:read',
                'circular_reference_limit' => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);
        }

        return $this->json('Une erreur est survenue', 404);
    }

    /**
     * Modifier l'ordre des produits.
     *
     * @Route("/user/api/liveproducts/edit/{id}", name="user_api_liveproducts_edit", methods={"PUT"})
     */
    public function prelive2(LiveProducts $liveProduct, Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        if ($json = $request->getContent()) {
            $serializer->deserialize($json, LiveProducts::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $liveProduct]);
            $manager->flush();

            return $this->json(true, 200);
        }

        return $this->json(false, 404);
    }

    /**
     * Récupérer un live.
     *
     * @Route("/user/api/live/{id}", name="user_api_live", methods={"GET"})
     */
    public function live(Live $live, Request $request, ObjectManager $manager): JsonResponse
    {
        return $this->json($live, 200, [], [
            'groups' => 'live:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }

    /**
     * Mettre à jour un live.
     *
     * @Route("/user/api/live/update/{id}", name="user_api_live_update", methods={"PUT"})
     */
    public function updateLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        $channel = 'channel'.$live->getId();
        $event = 'event'.$live->getId();

        $live->setChannel($channel);
        $live->setEvent($event);
        $manager->flush();

        return $this->json($live, 200, [], [
            'groups' => 'live:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }

    /**
     * Stream sur facebook.
     *
     * @Route("/user/api/live/update/stream/{id}", name="user_api_live_update_stream", methods={"PUT"})
     */
    public function updateStream(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        if ($json = $request->getContent()) {
            $param = \json_decode($json, true);
            $fbIdentifier = $param['fbIdentifier'];
            $showGroupsPage = $param['showGroupsPage'];
            $fbPageIdentifier = $param['fbPageIdentifier'];
            $fbToken = $param['fbToken'];
            $fbTokenPage = $param['fbTokenPage'];
            $pages = $param['pages'];
            $groups = $param['groups'];

            $client = new Client();

            $data = [
                'title' => 'Live sur Swipe Live',
                'description' => 'Live sur Swipe Live',
                'status' => 'LIVE_NOW',
            ];

            $url = \sprintf('https://graph.facebook.com/v21.0/%s/live_videos?fields=id,permalink_url,secure_stream_url', $fbIdentifier);

            $body = \json_encode($data);
            $headers = [
                'Authorization' => 'Bearer '.$fbToken,
                'Content-Type' => 'application/json',
            ];

            try {
                $response = $client->post($url, [
                    'headers' => $headers,
                    'body' => $body,
                ]);

                $result = \json_decode((string) $response->getBody(), true);

                if ($result) {
                    $fbStreamId = $result['id'];
                    $fbStreamUrl = $result['secure_stream_url'];
                    $fbPermalinkUrl = $result['permalink_url'];
                    $postUrl = 'https://www.facebook.com'.$fbPermalinkUrl;

                    if ($groups && \count($groups) > 0) {
                        foreach ($groups as $group) {
                            if ('Test Live' === $group['name']) {
                                $urlGroup = \sprintf('https://graph.facebook.com/v21.0/%s/feed', $group['id']);

                                $bodyGroup = \json_encode([
                                    'link' => $postUrl,
                                    'message' => 'Partage du live',
                                ]);

                                $client->post($urlGroup, [
                                    'headers' => $headers,
                                    'body' => $bodyGroup,
                                ]);
                            }
                        }
                    }

                    $live->setFbStreamId($fbStreamId);
                    $live->setFbStreamUrl($fbStreamUrl);
                    $live->setPostUrl($postUrl);
                    $manager->flush();

                    return $this->json(['fbStreamId' => $fbStreamId], 200, [], [
                        'groups' => 'live:read',
                        'circular_reference_limit' => 1,
                        'circular_reference_handler' => fn ($object) => $object->getId(),
                    ]);
                }
            } catch (\Exception $e) {
                return $this->json([
                    'error' => 'Une erreur est survenue lors de la création du live sur facebook',
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        return $this->json(false, 404);
    }

    /**
     * Mettre à jour le produit pendant le live.
     *
     * @Route("/user/api/live/{id}/update/display", name="user_api_live_update_display", methods={"PUT"})
     */
    public function updateDisplay(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer, LiveProductsRepository $liveProductRepo, CommentRepository $commentRepo): ?JsonResponse
    {
        if ($json = $request->getContent()) {
            $param = \json_decode($json, true);
            $display = $param['display'];
            $user = $this->getUser();

            $live->setDisplay($display);
            $manager->flush();

            $pusher = new Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), ['cluster' => 'eu', 'useTLS' => true]);
            $pusher->trigger($live->getChannel(), $live->getEvent(), ['display' => $display]);

            // créer le clip pour le produit précédent
            --$display;
            $liveProduct = $liveProductRepo->findOneBy(['live' => $live, 'priority' => $display]);

            if ($liveProduct) {
                $start = 1 === $display ? 5 : $live->getDuration() + 1;

                $created = $live->getCreatedAt();
                $now = new \DateTime('now', \timezone_open('UTC'));
                $diff = $now->diff($created);
                $end = $this->dateIntervalToSeconds($diff);
                $duration = $end - $start;

                if ($duration > 15) {
                    $clip = new Clip();
                    $clip->setVendor($user->getVendor());
                    $clip->setLive($live);
                    $clip->setProduct($liveProduct->getProduct());
                    $clip->setFileList($live->getFileList());
                    $clip->setPreview($live->getPreview());
                    $clip->setStart($start);
                    $clip->setEnd($end);
                    $clip->setDuration($duration);

                    $manager->persist($clip);
                    $manager->flush();

                    $live->setDuration($end);
                    $comments = $commentRepo->findByLiveAndClipNull($live);

                    foreach ($comments as $comment) {
                        $comment->setClip($clip);
                    }

                    $manager->flush();
                }
            }

            return $this->json($live, 200, [], [
                'groups' => 'live:read',
                'circular_reference_limit' => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);
        }

        return null;
    }

    /**
     * Arreter un live.
     *
     * @Route("/user/api/live/stop/{id}", name="user_api_live_stop", methods={"PUT"})
     */
    public function stopLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer, LiveProductsRepository $liveProductRepo, CommentRepository $commentRepo): JsonResponse
    {
        $json = $request->getContent();
        $param = \json_decode($json, true);
        $live->setStatus(2);
        $manager->flush();
        $fbStreamId = $param['fbStreamId'];
        $fbToken = $param['fbToken'];

        try {
            // créer le dernier clip
            $liveProduct = $liveProductRepo->findOneBy(['live' => $live, 'priority' => $live->getDisplay()]);

            if ($liveProduct) {
                $start = 1 === $live->getDisplay() ? 5 : $live->getDuration() + 1;

                $created = $live->getCreatedAt();
                $now = new \DateTime('now', \timezone_open('UTC'));
                $diff = $now->diff($created);
                $end = $this->dateIntervalToSeconds($diff);
                $duration = $end - $start;

                if ($duration > 15) {
                    $clip = new Clip();
                    $clip->setVendor($this->getUser()->getVendor());
                    $clip->setLive($live);
                    $clip->setProduct($liveProduct->getProduct());
                    $clip->setPreview($live->getPreview());
                    $clip->setStart($start);
                    $clip->setEnd($end);
                    $clip->setDuration($duration);

                    $manager->persist($clip);
                    $manager->flush();

                    $live->setDuration($end);
                    $comments = $commentRepo->findByLiveAndClipNull($live);

                    foreach ($comments as $comment) {
                        $comment->setClip($clip);
                    }

                    $manager->flush();
                }
            }

            if ($live->getSid()) {
                $client = new Client();
                $appId = $this->getParameter('agora_app_id');
                $urlStop = \sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/resourceid/%s/sid/%s/mode/mix/stop', $appId, $live->getResourceId(), $live->getSid());
                $headers = ['Content-Type' => 'application/json'];
                $bodyStop = \json_encode([
                    'cname' => $live->getCname(),
                    'uid' => '123456789',
                    'clientRequest' => new \stdClass(),
                ]);

                $resStop = $client->request('POST', $urlStop, [
                    'headers' => $headers,
                    'auth' => [$this->getParameter('agora_customer_id'), $this->getParameter('agora_customer_secret')],
                    'body' => $bodyStop,
                ]);

                $stopData = \json_decode((string) $resStop->getBody(), true);

                if (isset($stopData['serverResponse']['fileList'])) {
                    $fileList = $stopData['serverResponse']['fileList'];
                    $live->setFileList($fileList);
                    $manager->flush();
                }
            }

            // stop stream sur facebook
            if ($fbStreamId) {
                $url = '/'.$fbStreamId.'/?end_live_video=true';
                $fb = new Facebook([
                    'app_id' => $this->getParameter('facebook_app_id'),
                    'app_secret' => $this->getParameter('facebook_app_secret'),
                    'default_graph_version' => 'v2.10',
                ]);

                try {
                    $response = $fb->post($url, [], $fbToken);
                } catch (FacebookResponseException $e) {
                    return $this->json('Graph returned an error: '.$e->getMessage(), 404);
                } catch (FacebookSDKException $e) {
                    return $this->json('Facebook SDK returned an error: '.$e->getMessage(), 404);
                }
            }

            return $this->json($this->getUser(), 200, [], [
                'groups' => 'user:read',
                'circular_reference_limit' => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Exception: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ajouter un comment pendant le live.
     *
     * @Route("/user/api/live/{id}/comment/add", name="user_api_live_comment_add", methods={"POST"})
     */
    public function addCommentLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer): ?JsonResponse
    {
        if ($json = $request->getContent()) {
            $param = \json_decode($json, true);
            $content = $param['content'];
            $user = $this->getUser();

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setUser($user);
            $comment->setLive($live);
            $manager->persist($comment);
            $manager->flush();

            $vendor = $user->getVendor() instanceof Vendor ? [
                'pseudo' => $user->getVendor()->getPseudo(),
            ] : null;

            $data = [
                'comment' => [
                    'content' => $content,
                    'user' => [
                        'vendor' => $vendor,
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'picture' => $user->getPicture(),
                    ],
                ],
            ];

            $pusher = new Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), ['cluster' => 'eu', 'useTLS' => true]);
            $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

            return $this->json($live, 200, [], [
                'groups' => 'live:read',
                'circular_reference_limit' => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);
        }

        return null;
    }

    /**
     * Mettre à jour les vues sur un live.
     *
     * @Route("/user/api/live/{id}/update/viewers", name="user_api_live_update_viewers", methods={"PUT"})
     */
    public function updateViewers(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        $pusher = new Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), ['cluster' => 'eu', 'useTLS' => true]);
        $info = $pusher->getChannelInfo($live->getChannel(), ['info' => 'subscription_count']);
        $count = $info->subscription_count;
        $user = $this->getUser();

        if ($count > 0) {
            --$count;
        }

        $vendor = $user->getVendor() instanceof Vendor ? [
            'pseudo' => $user->getVendor()->getPseudo(),
        ] : null;

        $type = $live->getViewers() > $count ? 'remove' : 'add';

        $live->setViewers($count);
        $live->setTotalViewers($live->getTotalViewers() + 1);
        $manager->flush();

        $data = [
            'viewers' => [
                'count' => $count,
                'type' => $type,
                'user' => [
                    'id' => $user->getId(),
                    'vendor' => $vendor,
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'picture' => $user->getPicture(),
                ],
            ],
        ];

        $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

        return $this->json($live, 200, [], [
            'groups' => 'live:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }

    /**
     * Mettre à jour les likes.
     *
     * @Route("/user/api/live/{id}/update/likes", name="user_api_live_update_likes", methods={"PUT"})
     */
    public function updateLikes(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        $pusher = new Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), ['cluster' => 'eu', 'useTLS' => true]);
        $live->setTotalLikes($live->getTotalLikes() + 1);
        $manager->flush();

        if ($live->getChannel() && $live->getEvent()) {
            $pusher->trigger($live->getChannel(), $live->getEvent(), [
                'likes' => $this->getUser()->getId(),
            ]);
        }

        return $this->json($live, 200, [], [
            'groups' => 'live:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }

    /**
     * Muter un viewer.
     *
     * @Route("/user/api/live/{id}/update/banned/{userId}", name="user_api_live_update_banned", methods={"GET"})
     */
    public function bannedViewer(Live $live, $userId, Request $request, ObjectManager $manager, FollowRepository $followRepo, SerializerInterface $serializer): JsonResponse
    {
        $pusher = new Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), ['cluster' => 'eu', 'useTLS' => true]);
        $follow = $followRepo->findOneBy(['following' => $live->getVendor()->getUser(), 'follower' => $userId]);

        if ($follow) {
            $manager->remove($follow);
            $manager->flush();
        }

        if ($live->getChannel() && $live->getEvent()) {
            $pusher->trigger($live->getChannel(), $live->getEvent(), [
                'banned' => $userId,
            ]);
        }

        return $this->json($live, 200, [], [
            'groups' => 'live:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }

    /**
     * Mettre à jour les commandes.
     *
     * @Route("/user/api/live/{id}/update/orders/{orderId}", name="user_api_live_update_orders", methods={"GET"})
     */
    public function updateOrders(Live $live, $orderId, Request $request, ObjectManager $manager, OrderRepository $orderRepo, LiveProductsRepository $liveProductRepo, SerializerInterface $serializer): JsonResponse
    {
        $order = $orderRepo->findOneById($orderId);
        $upload = null;
        $vendor = null;
        $nbProducts = 0;
        $available = null;
        $display = $live->getDisplay();
        $liveProduct = $liveProductRepo->findOneBy(['live' => $live, 'priority' => $display]);

        if ($liveProduct) {
            $product = $liveProduct->getProduct();

            if ($product && \count($product->getVariants()->toArray()) > 0) {
                foreach ($product->getVariants() as $variant) {
                    $available += $variant->getQuantity();
                }
            } else {
                $available = $product->getQuantity();
            }
        }

        if ($order) {
            $pusher = new Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), ['cluster' => 'eu', 'useTLS' => true]);

            if ($order->getBuyer()->getVendor()) {
                $vendor = ['pseudo' => $order->getBuyer()->getVendor()->getPseudo()];
            }

            if (\count($order->getLineItems()->toArray()[0]->getProduct()->getUploads()) > 0) {
                $upload = $order->getLineItems()->toArray()[0]->getProduct()->getUploads()[0]->getFilename();
            }

            foreach ($order->getLineItems()->toArray() as $lineItem) {
                $nbProducts += $lineItem->getQuantity();
            }

            $data = [
                'order' => [
                    'available' => $available,
                    'number' => $order->getNumber(),
                    'createdAt' => $order->getCreatedAt(),
                    'nbProducts' => $nbProducts,
                    'amount' => $order->getTotal(),
                    'upload' => $upload,
                    'buyer' => [
                        'vendor' => $vendor,
                        'firstname' => $order->getBuyer()->getFirstname(),
                        'lastname' => $order->getBuyer()->getLastname(),
                        'picture' => $order->getBuyer()->getPicture(),
                    ],
                ],
            ];

            $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

            return $this->json($live, 200, [], [
                'groups' => 'live:read',
                'circular_reference_limit' => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);
        }

        return $this->json('Impossible de trouver la commande', 404);
    }

    public function dateIntervalToSeconds(\DateInterval $dateInterval): int|float
    {
        $reference = new \DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        return $reference->getTimestamp() - $endTime->getTimestamp();
    }
}
