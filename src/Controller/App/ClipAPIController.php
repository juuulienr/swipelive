<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\Clip;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\ClipRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ClipAPIController extends AbstractController
{
    public function getUser(): ?User
    {
        $user = parent::getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * Récupérer tous les clips.
     *
     * @Route("/user/api/clips", name="user_api_clips", methods={"GET"})
     */
    public function clips(Request $request, ObjectManager $manager, ClipRepository $clipRepo): JsonResponse
    {
        $clips = $clipRepo->findBy(
            ['vendor' => $this->getUser()->getVendor()],
            ['createdAt' => 'DESC']
        );

        return $this->json($clips, 200, [], [
            'groups' => 'clip:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }

    /**
     * Ajouter un comment sur un clip.
     *
     * @Route("/user/api/clip/{id}/comment/add", name="user_api_clip_comment_add", methods={"POST"})
     */
    public function addComment(Clip $clip, Request $request, ObjectManager $manager, SerializerInterface $serializer): ?JsonResponse
    {
        if ($json = $request->getContent()) {
            $param = \json_decode($json, true);
            $content = $param['content'];
            $user = $this->getUser();

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setUser($user);
            $comment->setClip($clip);

            if ($user->getVendor() && $user->getVendor()->getPseudo() === $clip->getVendor()->getPseudo()) {
                $comment->setIsVendor(true);
            }

            $manager->persist($comment);
            $manager->flush();

            return $this->json($clip, 200, [], [
                'groups' => 'clip:read',
                'circular_reference_limit' => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);
        }

        return null;
    }

    /**
     * Mettre à jour les likes.
     *
     * @Route("/user/api/clips/{id}/update/likes", name="user_api_clips_update_likes", methods={"PUT"})
     */
    public function updateLikes(Clip $clip, Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
    {
        $clip->setTotalLikes($clip->getTotalLikes() + 1);
        $manager->flush();

        return $this->json(true, 200);
    }

    /**
     * Supprimer un clip.
     *
     * @Route("/user/api/clips/{id}/delete", name="user_api_clips_delete", methods={"GET"})
     */
    public function delete(Clip $clip, Request $request, ObjectManager $manager, ClipRepository $clipRepo): JsonResponse
    {
        $live = $clip->getLive();
        $comments = $clip->getComments();

        if (!$comments->isEmpty()) {
            foreach ($comments as $comment) {
                $manager->remove($comment);
            }
            $manager->flush();
        }

        $manager->remove($clip);
        $manager->flush();

        if (0 === \count($live->getClips())) {
            $liveProducts = $live->getLiveProducts();
            $comments = $live->getComments();

            if (!$liveProducts->isEmpty()) {
                foreach ($liveProducts as $liveProduct) {
                    $manager->remove($liveProduct);
                }
                $manager->flush();
            }

            if (!$comments->isEmpty()) {
                foreach ($comments as $comment) {
                    $manager->remove($comment);
                }
                $manager->flush();
            }

            $manager->remove($live);
            $manager->flush();
        }

        return $this->json($this->getUser(), 200, [], [
            'groups' => 'user:read',
            'circular_reference_limit' => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
    }
}
