<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 */
class Category
{
  /**
   * @ORM\Id
   *
   * @ORM\GeneratedValue
   *
   * @ORM\Column(type="integer")
   *
   * @Groups("product:read")
   * @Groups("category:read")
   * @Groups("user:read")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("category:read")
   * @Groups("product:read")
   * @Groups("user:read")
   * @Groups("live:read")
   * @Groups("clip:read")
   */
  private $name;

  /**
   * @ORM\OneToMany(targetEntity=Product::class, mappedBy="category")
   */
  private $products;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("category:read")
   */
  private $picture;

  public function __construct()
  {
    $this->products = new ArrayCollection();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }

  /**
   * @return Collection|Product[]
   */
  public function getProducts(): Collection
  {
    return $this->products;
  }

  public function addProduct(Product $product): self
  {
    if (!$this->products->contains($product)) {
      $this->products[] = $product;
      $product->setCategory($this);
    }

    return $this;
  }

  public function removeProduct(Product $product): self
  {
    // set the owning side to null (unless already changed)
    if ($this->products->removeElement($product) && $product->getCategory() === $this) {
      $product->setCategory(null);
    }

    return $this;
  }

  public function getPicture(): ?string
  {
    return $this->picture;
  }

  public function setPicture(?string $picture): self
  {
    $this->picture = $picture;

    return $this;
  }
}
