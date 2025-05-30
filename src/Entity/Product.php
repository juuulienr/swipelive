<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
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
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $title;

  /**
   * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products", cascade={"persist"})
   *
   * @ORM\JoinColumn(nullable=false)
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   */
  private $category;

  /**
   * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="products")
   *
   * @ORM\JoinColumn(nullable=false)
   *
   * @Groups("product:read")
   * @Groups("category:read")
   * @Groups("clip:read")
   * @Groups("live:read")
   */
  private $vendor;

  /**
   * @ORM\Column(type="text")
   *
   * @Groups("product:read")
   * @Groups("live:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("favoris:read")
   */
  private $description;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("favoris:read")
   */
  private $price;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("favoris:read")
   */
  private $compareAtPrice;

  /**
   * @ORM\Column(type="integer")
   *
   * @Groups("product:read")
   * @Groups("live:read")
   * @Groups("clip:read")
   * @Groups("favoris:read")
   */
  private $quantity = 0;

  /**
   * @ORM\OneToMany(targetEntity=Upload::class, mappedBy="product", cascade={"persist"})
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $uploads;

  /**
   * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="product")
   */
  private $clips;

  /**
   * @ORM\OneToMany(targetEntity=LiveProducts::class, mappedBy="product")
   */
  private $liveProducts;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("favoris:read")
   */
  private $weight;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("favoris:read")
   */
  private $weightUnit = 'kg';

  /**
   * @ORM\OneToMany(targetEntity=Option::class, mappedBy="product", cascade={"persist"})
   *
   * @ORM\OrderBy({"position": "ASC"})
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $options;

  /**
   * @ORM\OneToMany(targetEntity=Variant::class, mappedBy="product", cascade={"persist"})
   *
   * @Groups("product:read")
   * @Groups("clip:read")
   * @Groups("category:read")
   * @Groups("live:read")
   * @Groups("order:read")
   * @Groups("favoris:read")
   */
  private $variants;

  /**
   * @ORM\OneToMany(targetEntity=LineItem::class, mappedBy="product")
   */
  private $lineItems;

  /**
   * @ORM\OneToMany(targetEntity=Favoris::class, mappedBy="product", orphanRemoval=true)
   */
  private $favoris;

  public function __construct()
  {
    $this->uploads      = new ArrayCollection();
    $this->clips        = new ArrayCollection();
    $this->liveProducts = new ArrayCollection();
    $this->options      = new ArrayCollection();
    $this->variants     = new ArrayCollection();
    $this->lineItems    = new ArrayCollection();
    $this->favoris      = new ArrayCollection();
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

  public function getCategory(): ?Category
  {
    return $this->category;
  }

  public function setCategory(?Category $category): self
  {
    $this->category = $category;

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

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(string $description): self
  {
    $this->description = $description;

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

  /**
   * @return Collection|Upload[]
   */
  public function getUploads(): Collection
  {
    return $this->uploads;
  }

  public function addUpload(Upload $upload): self
  {
    if (!$this->uploads->contains($upload)) {
      $this->uploads[] = $upload;
      $upload->setProduct($this);
    }

    return $this;
  }

  public function removeUpload(Upload $upload): self
  {if ($this->uploads->removeElement($upload) && $upload->getProduct() === $this) {
      $upload->setProduct(null);
    }

    return $this;
  }

  /**
   * @return Collection|Clip[]
   */
  public function getClips(): Collection
  {
    return $this->clips;
  }

  public function addClip(Clip $clip): self
  {
    if (!$this->clips->contains($clip)) {
      $this->clips[] = $clip;
      $clip->setProduct($this);
    }

    return $this;
  }

  public function removeClip(Clip $clip): self
  {if ($this->clips->removeElement($clip) && $clip->getProduct() === $this) {
      $clip->setProduct(null);
    }

    return $this;
  }

  /**
   * @return Collection|LiveProducts[]
   */
  public function getLiveProducts(): Collection
  {
    return $this->liveProducts;
  }

  public function addLiveProduct(LiveProducts $liveProduct): self
  {
    if (!$this->liveProducts->contains($liveProduct)) {
      $this->liveProducts[] = $liveProduct;
      $liveProduct->setProduct($this);
    }

    return $this;
  }

  public function removeLiveProduct(LiveProducts $liveProduct): self
  {if ($this->liveProducts->removeElement($liveProduct) && $liveProduct->getProduct() === $this) {
      $liveProduct->setProduct(null);
    }

    return $this;
  }

  /**
   * @return Collection|Option[]
   */
  public function getOptions(): Collection
  {
    return $this->options;
  }

  public function addOption(Option $option): self
  {
    if (!$this->options->contains($option)) {
      $this->options[] = $option;
      $option->setProduct($this);
    }

    return $this;
  }

  public function removeOption(Option $option): self
  {if ($this->options->removeElement($option) && $option->getProduct() === $this) {
      $option->setProduct(null);
    }

    return $this;
  }

  /**
   * @return Collection|Variant[]
   */
  public function getVariants(): Collection
  {
    return $this->variants;
  }

  public function addVariant(Variant $variant): self
  {
    if (!$this->variants->contains($variant)) {
      $this->variants[] = $variant;
      $variant->setProduct($this);
    }

    return $this;
  }

  public function removeVariant(Variant $variant): self
  {if ($this->variants->removeElement($variant) && $variant->getProduct() === $this) {
      $variant->setProduct(null);
    }

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
      $lineItem->setProduct($this);
    }

    return $this;
  }

  public function removeLineItem(LineItem $lineItem): self
  {if ($this->lineItems->removeElement($lineItem) && $lineItem->getProduct() === $this) {
      $lineItem->setProduct(null);
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

  /**
   * @return Collection|Favoris[]
   */
  public function getFavoris(): Collection
  {
    return $this->favoris;
  }

  public function addFavori(Favoris $favori): self
  {
    if (!$this->favoris->contains($favori)) {
      $this->favoris[] = $favori;
      $favori->setProduct($this);
    }

    return $this;
  }

  public function removeFavori(Favoris $favori): self
  {if ($this->favoris->removeElement($favori) && $favori->getProduct() === $this) {
      $favori->setProduct(null);
    }

    return $this;
  }
}
