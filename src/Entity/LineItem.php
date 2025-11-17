<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LineItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=LineItemRepository::class)
 */
class LineItem
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="lineItems")
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Variant::class, inversedBy="lineItems")
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $variant;

    /**
     * @ORM\Column(type="integer")
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $price;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $total;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="lineItems")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private $orderId;

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }

    public function setVariant(?Variant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getOrderId(): ?Order
    {
        return $this->orderId;
    }

    public function setOrderId(?Order $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }
}
