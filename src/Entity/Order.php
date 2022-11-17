<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paymentId;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="sales")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("order:read")
     */
    private $vendor;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $subTotal;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $total;

    /**
     * @ORM\OneToMany(targetEntity=LineItem::class, mappedBy="orderId")
     * @Groups("order:read")
     */
    private $lineItems;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $fees;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $profit;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("order:read")
     */
    private $buyer;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $shippingName;

    /**
     * @ORM\Column(type="integer")
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $shippingMethodId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $shippingCarrier;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $shippingPrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $servicePointId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("order:read")
     * @Groups("user:read")
     */
    private $trackingNumber;

    /**
     * @ORM\ManyToOne(targetEntity=ShippingAddress::class)
     */
    private $shippingAddress;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     */
    private $weight;


    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
        $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): self
    {
        $this->paymentId = $paymentId;

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

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSubTotal(): ?string
    {
        return $this->subTotal;
    }

    public function setSubTotal(string $subTotal): self
    {
        $this->subTotal = $subTotal;

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
            $lineItem->setOrderId($this);
        }

        return $this;
    }

    public function removeLineItem(LineItem $lineItem): self
    {
        if ($this->lineItems->removeElement($lineItem)) {
            // set the owning side to null (unless already changed)
            if ($lineItem->getOrderId() === $this) {
                $lineItem->setOrderId(null);
            }
        }

        return $this;
    }

    public function getFees(): ?string
    {
        return $this->fees;
    }

    public function setFees(string $fees): self
    {
        $this->fees = $fees;

        return $this;
    }

    public function getProfit(): ?string
    {
        return $this->profit;
    }

    public function setProfit(string $profit): self
    {
        $this->profit = $profit;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getShippingName(): ?string
    {
        return $this->shippingName;
    }

    public function setShippingName(string $shippingName): self
    {
        $this->shippingName = $shippingName;

        return $this;
    }

    public function getShippingMethodId(): ?int
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId(int $shippingMethodId): self
    {
        $this->shippingMethodId = $shippingMethodId;

        return $this;
    }

    public function getShippingCarrier(): ?string
    {
        return $this->shippingCarrier;
    }

    public function setShippingCarrier(string $shippingCarrier): self
    {
        $this->shippingCarrier = $shippingCarrier;

        return $this;
    }

    public function getShippingPrice(): ?string
    {
        return $this->shippingPrice;
    }

    public function setShippingPrice(string $shippingPrice): self
    {
        $this->shippingPrice = $shippingPrice;

        return $this;
    }

    public function getServicePointId(): ?int
    {
        return $this->servicePointId;
    }

    public function setServicePointId(?int $servicePointId): self
    {
        $this->servicePointId = $servicePointId;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?ShippingAddress $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

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
}
