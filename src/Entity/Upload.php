<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UploadRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UploadRepository::class)
 */
class Upload
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     *
     * @Groups("upload:read")
     * @Groups("product:read")
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     * @Groups("order:read")
     * @Groups("favoris:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups("upload:read")
     * @Groups("product:read")
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("category:read")
     * @Groups("live:read")
     * @Groups("order:read")
     * @Groups("favoris:read")
     */
    private $filename;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups("upload:read")
     * @Groups("product:read")
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("category:read")
     * @Groups("live:read")
     * @Groups("order:read")
     * @Groups("favoris:read")
     */
    private $position;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="uploads")
     */
    private $product;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now', \timezone_open('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
