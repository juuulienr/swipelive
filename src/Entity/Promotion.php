<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\PromotionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PromotionRepository::class)
 */
class Promotion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("promotion:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("user:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("promotion:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("user:read")
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("promotion:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("user:read")
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     * @Groups("promotion:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("user:read")
     */
    private $value;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("promotion:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("user:read")
     */
    private $isActive;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="promotions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;


    public function __construct()
    {
      $this->isActive = true;
      $this->createdAt = new \DateTime('now', timezone_open('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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
}
