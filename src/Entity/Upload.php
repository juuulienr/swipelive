<?php

namespace App\Entity;

use App\Repository\UploadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UploadRepository::class)
 */
class Upload
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("upload:read")
     * @Groups("product:read")
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("upload:read")
     * @Groups("product:read")
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("category:read")
     * @Groups("live:read")
     */
    private $filename;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("upload:read")
     * @Groups("product:read")
     * @Groups("vendor:read")
     * @Groups("clip:read")
     * @Groups("category:read")
     * @Groups("live:read")
     */
    private $position;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="uploads")
     */
    private $product;

    /**
     * @ORM\OneToMany(targetEntity=Variant::class, mappedBy="upload")
     */
    private $variants;

    
    public function __construct()
    {
        $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
        $this->variants = new ArrayCollection();
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
            $variant->setUpload($this);
        }

        return $this;
    }

    public function removeVariant(Variant $variant): self
    {
        if ($this->variants->removeElement($variant)) {
            // set the owning side to null (unless already changed)
            if ($variant->getUpload() === $this) {
                $variant->setUpload(null);
            }
        }

        return $this;
    }
}
