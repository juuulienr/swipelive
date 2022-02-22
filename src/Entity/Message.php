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
     */
    private $live;

    /**
     * @ORM\Column(type="integer")
     * @Groups("clip:read")
     * @Groups("message:read")
     * @Groups("live:read")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="messages")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $vendor;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("clip:read")
     * @Groups("message:read")
     * @Groups("live:read")
     */
    private $content;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("clip:read")
     * @Groups("message:read")
     * @Groups("live:read")
     */
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity=Clip::class, inversedBy="messages")
     */
    private $clip;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups("message:read")
     *     
     */
    private $createdAt;

    
    public function __construct()
    {
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

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(?int $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getClip(): ?Clip
    {
        return $this->clip;
    }

    public function setClip(?Clip $clip): self
    {
        $this->clip = $clip;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
