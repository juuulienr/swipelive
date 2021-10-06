<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=MessageRepository::class)
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Live::class, inversedBy="messages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $live;

    /**
     * @ORM\Column(type="integer")
     * @Groups("clip:read")
     * @Groups("message:read")
     */
    private $type;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups("clip:read")
     * @Groups("message:read")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="messages")
     * @Groups("clip:read")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="messages")
     */
    private $vendor;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("clip:read")
     * @Groups("message:read")
     */
    private $content;

    
    public function __construct()
    {
        $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
        $this->type = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLive(): ?Live
    {
        return $this->live;
    }

    public function setLive(?Live $live): self
    {
        $this->live = $live;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setVendor(?Vendor $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
