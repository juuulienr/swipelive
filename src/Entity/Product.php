<?php

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
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("vendor:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("product:read")
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="products")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @ORM\Column(type="text")
     * @Groups("product:read")
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $online;

    /**
     * @ORM\Column(type="float")
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $price;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $compareAtPrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $quantity;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $tracking;

    /**
     * @ORM\OneToMany(targetEntity=Upload::class, mappedBy="product")
     * @Groups("product:read")
     * @Groups("vendor:read")
     */
    private $uploads;

    /**
     * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="product")
     */
    private $clips;

    /**
     * @ORM\ManyToMany(targetEntity=Live::class, mappedBy="products")
     */
    private $lives;

    public function __construct()
    {
        $this->uploads = new ArrayCollection();
        $this->clips = new ArrayCollection();
        $this->lives = new ArrayCollection();
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

    public function getOnline(): ?bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): self
    {
        $this->online = $online;

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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTracking(): ?bool
    {
        return $this->tracking;
    }

    public function setTracking(bool $tracking): self
    {
        $this->tracking = $tracking;

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
    {
        if ($this->uploads->removeElement($upload)) {
            // set the owning side to null (unless already changed)
            if ($upload->getProduct() === $this) {
                $upload->setProduct(null);
            }
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
    {
        if ($this->clips->removeElement($clip)) {
            // set the owning side to null (unless already changed)
            if ($clip->getProduct() === $this) {
                $clip->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Live[]
     */
    public function getLives(): Collection
    {
        return $this->lives;
    }

    public function addLife(Live $life): self
    {
        if (!$this->lives->contains($life)) {
            $this->lives[] = $life;
            $life->addProduct($this);
        }

        return $this;
    }

    public function removeLife(Live $life): self
    {
        if ($this->lives->removeElement($life)) {
            $life->removeProduct($this);
        }

        return $this;
    }
}
