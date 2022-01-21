<?php

namespace App\Entity;

use App\Repository\VariantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=VariantRepository::class)
 */
class Variant
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $title;

    /**
     * @ORM\Column(type="float")
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $price;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $compareAtPrice;

    /**
     * @ORM\Column(type="integer")
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $quantity;

    /**
     * @ORM\Column(type="integer")
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $option1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $option2;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $weight;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="variants")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Upload::class, inversedBy="variants")
     */
    private $upload;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups("product:read")
     * @Groups("variant:read")
     */
    private $archived;

    public function __construct()
    {
        $this->quantity = 0;
        $this->archived = 0;
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCompareAtPrice(): ?float
    {
        return $this->compareAtPrice;
    }

    public function setCompareAtPrice(?float $compareAtPrice): self
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

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): self
    {
        $this->weight = $weight;

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

    public function getUpload(): ?Upload
    {
        return $this->upload;
    }

    public function setUpload(?Upload $upload): self
    {
        $this->upload = $upload;

        return $this;
    }

    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(?bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }
}
