<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=VariantRepository::class)
 */
class Variant
{
  /**
   * @ORM\Id
   *
   * @ORM\GeneratedValue
   *
   * @ORM\Column(type="integer")
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $title;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $price;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $compareAtPrice;

  /**
   * @ORM\Column(type="integer")
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $quantity = 0;

  /**
   * @ORM\Column(type="integer")
   *
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
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $option1;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $option2;

  /**
   * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="variants")
   */
  private $product;

  /**
   * @ORM\OneToMany(targetEntity=LineItem::class, mappedBy="variant")
   */
  private $lineItems;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $weight;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $weightUnit = 'kg';

  public function __construct()
  {
    $this->lineItems = new ArrayCollection();
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

  public function getPrice(): string
  {
    return $this->price;
  }

  public function setPrice(string $price): self
  {
    $this->price = $price;

    return $this;
  }

  public function getCompareAtPrice(): ?string
  {
    return $this->compareAtPrice;
  }

  public function setCompareAtPrice(?string $compareAtPrice): self
  {
    $this->compareAtPrice = $compareAtPrice;

    return $this;
  }

  public function getQuantity(): int
  {
    return $this->quantity;
  }

  public function setQuantity(int $quantity): self
  {
    $this->quantity = $quantity;

    return $this;
  }

  public function getPosition(): ?int
  {
    return $this->position;
  }

  public function setPosition(int $position): self
  {
    $this->position = $position;

    return $this;
  }

  public function getOption1(): ?string
  {
    return $this->option1;
  }

  public function setOption1(?string $option1): self
  {
    $this->option1 = $option1;

    return $this;
  }

  public function getOption2(): ?string
  {
    return $this->option2;
  }

  public function setOption2(?string $option2): self
  {
    $this->option2 = $option2;

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

  /**
   * @return Collection|LineItem[]
   */
  public function getLineItems(): Collection
  {
    return $this->lineItems;
  }

  public function addLineItem(LineItem $lineItem): self
  {
    if (!$this->lineItems->contains($lineItem)) {
      $this->lineItems[] = $lineItem;
      $lineItem->setVariant($this);
    }

    return $this;
  }

  public function removeLineItem(LineItem $lineItem): self
  {
    // set the owning side to null (unless already changed)
    if ($this->lineItems->removeElement($lineItem) && $lineItem->getVariant() === $this) {
      $lineItem->setVariant(null);
    }

    return $this;
  }

  public function getWeight(): ?string
  {
    return $this->weight;
  }

  public function setWeight(?string $weight): self
  {
    $this->weight = $weight;

    return $this;
  }

  public function getWeightUnit(): ?string
  {
    return $this->weightUnit;
  }

  public function setWeightUnit(?string $weightUnit): self
  {
    $this->weightUnit = $weightUnit;

    return $this;
  }
}
