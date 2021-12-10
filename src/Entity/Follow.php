<?php

namespace App\Entity;

use App\Repository\FollowRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=FollowRepository::class)
 */
class Follow
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="followers")
     * @Groups("vendor:read")
     */
    private $following;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="following")
     * @Groups("vendor:read")
     */
    private $vendor;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="following")
     * @Groups("vendor:read")
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollowing(): ?Vendor
    {
        return $this->following;
    }

    public function setFollowing(?Vendor $following): self
    {
        $this->following = $following;

        return $this;
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setVendor(?Vendor $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
