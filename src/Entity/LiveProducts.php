<?php

namespace App\Entity;

use App\Repository\LiveProductsRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LiveProductsRepository::class)
 */
class LiveProducts
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("live:read")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="liveProducts")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("live:read")
     */
    private $product;

    /**
     * @ORM\Column(type="integer")
     * @Groups("live:read")
     */
    private $priority;

    /**
     * @ORM\ManyToOne(targetEntity=Live::class, inversedBy="liveProducts")
     */
    private $live;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

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
}
