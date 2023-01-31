<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\SecurityUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SecurityUserRepository::class)
 */
class SecurityUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("user:read")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("user:read")
     * @Groups("discussion:read")
     */
    private $connectedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="securityUsers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;


    public function __construct()
    {
      $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
      $this->connectedAt = new \DateTime('now', timezone_open('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getConnectedAt(): ?\DateTimeInterface
    {
        return $this->connectedAt;
    }

    public function setConnectedAt(\DateTimeInterface $connectedAt): self
    {
        $this->connectedAt = $connectedAt;

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
