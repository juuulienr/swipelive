<?php

namespace App\Entity;

use App\Repository\ClipRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClipRepository::class)
 */
class Clip
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("clip:read")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="clips")
     * @Groups("clip:read")
     */
    private $vendor;

    /**
     * @ORM\ManyToOne(targetEntity=Live::class, inversedBy="clips")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("clip:read")
     */
    private $live;

    /**
     * @ORM\Column(type="integer")
     * @Groups("clip:read")
     */
    private $start;

    /**
     * @ORM\Column(type="integer")
     * @Groups("clip:read")
     */
    private $end;

    /**
     * @ORM\Column(type="integer")
     * @Groups("clip:read")
     */
    private $duration;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("clip:read")
     * @Groups("vendor:read")
     */
    private $resourceUri;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="clips")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("clip:read")
     */
    private $product;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("clip:read")
     * @Groups("vendor:read")
     */
    private $preview;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $broadcastId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("clip:read")
     * @Groups("vendor:read")
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLive(): ?Live
    {
        return $this->live;
    }

    public function setLive(?Live $live): self
    {
        $this->live = $live;

        return $this;
    }

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function setStart(int $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?int
    {
        return $this->end;
    }

    public function setEnd(int $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getResourceUri(): ?string
    {
        return $this->resourceUri;
    }

    public function setResourceUri(?string $resourceUri): self
    {
        $this->resourceUri = $resourceUri;

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

    public function getPreview(): ?string
    {
        return $this->preview;
    }

    public function setPreview(?string $preview): self
    {
        $this->preview = $preview;

        return $this;
    }

    public function getBroadcastId(): ?string
    {
        return $this->broadcastId;
    }

    public function setBroadcastId(?string $broadcastId): self
    {
        $this->broadcastId = $broadcastId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
