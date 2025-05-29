<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 *
 * @ORM\Table(name="`order`")
 */
class Order
{
  /**
   * @ORM\Id
   *
   * @ORM\GeneratedValue
   *
   * @ORM\Column(type="integer")
   *
   * @Groups("discussion:read")
   * @Groups("order:read")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $paymentId;

  /**
   * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="sales")
   *
   * @ORM\JoinColumn(nullable=false)
   *
   * @Groups("order:read")
   */
  private $vendor;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $status;

  /**
   * @ORM\Column(type="datetime")
   *
   * @Groups("order:read")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   *
   * @Groups("order:read")
   */
  private $subTotal;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   *
   * @Groups("order:read")
   */
  private $total;

  /**
   * @ORM\OneToMany(targetEntity=LineItem::class, mappedBy="orderId")
   *
   * @Groups("order:read")
   */
  private $lineItems;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   *
   * @Groups("order:read")
   */
  private $fees;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   *
   * @Groups("order:read")
   */
  private $updatedAt;

  /**
   * @ORM\ManyToOne(targetEntity=User::class, inversedBy="purchases")
   *
   * @ORM\JoinColumn(nullable=false)
   *
   * @Groups("order:read")
   */
  private $buyer;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   *
   * @Groups("order:read")
   */
  private $shippingPrice;

  /**
   * @ORM\Column(type="integer", nullable=true)
   *
   * @Groups("order:read")
   */
  private $number;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $trackingNumber;

  /**
   * @ORM\ManyToOne(targetEntity=ShippingAddress::class)
   */
  private $shippingAddress;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
   *
   * @Groups("order:read")
   */
  private $weight;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $pdf;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   *
   * @Groups("order:read")
   */
  private $expectedDelivery;

  /**
   * @ORM\OneToMany(targetEntity=OrderStatus::class, mappedBy="shipping")
   *
   * @ORM\OrderBy({"date": "ASC"})
   *
   * @Groups("order:read")
   */
  private $orderStatuses;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $shippingStatus;

  /**
   * @ORM\OneToMany(targetEntity=Discussion::class, mappedBy="purchase")
   */
  private $discussions;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $identifier;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $shippingCarrierId;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $shippingCarrierName;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $shippingServiceId;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $shippingServiceName;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("order:read")
   */
  private $shippingServiceCode;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $dropoffLocationId;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $dropoffCountryCode;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $dropoffPostcode;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("order:read")
   */
  private $dropoffName;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   *
   * @Groups("order:read")
   */
  private $delivered;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   *
   * @Groups("order:read")
   */
  private $incidentDate;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   *
   * @Groups("order:read")
   */
  private $deliveryDate;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $eventId;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
   *
   * @Groups("order:read")
   */
  private $promotionAmount;

  /**
   * @ORM\ManyToOne(targetEntity=Promotion::class, inversedBy="orders")
   */
  private $promotion;

  public function __construct()
  {
    $this->lineItems     = new ArrayCollection();
    $this->createdAt     = new DateTime('now', \timezone_open('UTC'));
    $this->orderStatuses = new ArrayCollection();
    $this->discussions   = new ArrayCollection();
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

  public function getCreatedAt(): ?DateTimeInterface
  {
    return $this->createdAt;
  }

  public function setCreatedAt(DateTimeInterface $createdAt): self
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
  {if ($this->lineItems->removeElement($lineItem) && $lineItem->getOrderId() === $this) {
      $lineItem->setOrderId(null);
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

  public function getUpdatedAt(): ?DateTimeInterface
  {
    return $this->updatedAt;
  }

  public function setUpdatedAt(?DateTimeInterface $updatedAt): self
  {
    $this->updatedAt = $updatedAt;

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

  public function getPdf(): ?string
  {
    return $this->pdf;
  }

  public function setPdf(?string $pdf): self
  {
    $this->pdf = $pdf;

    return $this;
  }

  public function getExpectedDelivery(): ?DateTimeInterface
  {
    return $this->expectedDelivery;
  }

  public function setExpectedDelivery(?DateTimeInterface $expectedDelivery): self
  {
    $this->expectedDelivery = $expectedDelivery;

    return $this;
  }

  /**
   * @return Collection|OrderStatus[]
   */
  public function getOrderStatuses(): Collection
  {
    return $this->orderStatuses;
  }

  public function addOrderStatus(OrderStatus $orderStatus): self
  {
    if (!$this->orderStatuses->contains($orderStatus)) {
      $this->orderStatuses[] = $orderStatus;
      $orderStatus->setShipping($this);
    }

    return $this;
  }

  public function removeOrderStatus(OrderStatus $orderStatus): self
  {if ($this->orderStatuses->removeElement($orderStatus) && $orderStatus->getShipping() === $this) {
      $orderStatus->setShipping(null);
    }

    return $this;
  }

  /**
   * @return Collection|Discussion[]
   */
  public function getDiscussions(): Collection
  {
    return $this->discussions;
  }

  public function addDiscussion(Discussion $discussion): self
  {
    if (!$this->discussions->contains($discussion)) {
      $this->discussions[] = $discussion;
      $discussion->setPurchase($this);
    }

    return $this;
  }

  public function removeDiscussion(Discussion $discussion): self
  {if ($this->discussions->removeElement($discussion) && $discussion->getPurchase() === $this) {
      $discussion->setPurchase(null);
    }

    return $this;
  }

  public function getIdentifier(): ?string
  {
    return $this->identifier;
  }

  public function setIdentifier(string $identifier): self
  {
    $this->identifier = $identifier;

    return $this;
  }

  public function getShippingStatus(): ?string
  {
    return $this->shippingStatus;
  }

  public function setShippingStatus(string $shippingStatus): self
  {
    $this->shippingStatus = $shippingStatus;

    return $this;
  }

  public function getShippingCarrierId(): ?string
  {
    return $this->shippingCarrierId;
  }

  public function setShippingCarrierId(string $shippingCarrierId): self
  {
    $this->shippingCarrierId = $shippingCarrierId;

    return $this;
  }

  public function getShippingCarrierName(): ?string
  {
    return $this->shippingCarrierName;
  }

  public function setShippingCarrierName(string $shippingCarrierName): self
  {
    $this->shippingCarrierName = $shippingCarrierName;

    return $this;
  }

  public function getShippingServiceId(): ?string
  {
    return $this->shippingServiceId;
  }

  public function setShippingServiceId(string $shippingServiceId): self
  {
    $this->shippingServiceId = $shippingServiceId;

    return $this;
  }

  public function getShippingServiceName(): ?string
  {
    return $this->shippingServiceName;
  }

  public function setShippingServiceName(string $shippingServiceName): self
  {
    $this->shippingServiceName = $shippingServiceName;

    return $this;
  }

  public function getShippingServiceCode(): ?string
  {
    return $this->shippingServiceCode;
  }

  public function setShippingServiceCode(string $shippingServiceCode): self
  {
    $this->shippingServiceCode = $shippingServiceCode;

    return $this;
  }

  public function getDropoffLocationId(): ?string
  {
    return $this->dropoffLocationId;
  }

  public function setDropoffLocationId(?string $dropoffLocationId): self
  {
    $this->dropoffLocationId = $dropoffLocationId;

    return $this;
  }

  public function getDropoffCountryCode(): ?string
  {
    return $this->dropoffCountryCode;
  }

  public function setDropoffCountryCode(?string $dropoffCountryCode): self
  {
    $this->dropoffCountryCode = $dropoffCountryCode;

    return $this;
  }

  public function getDropoffPostcode(): ?string
  {
    return $this->dropoffPostcode;
  }

  public function setDropoffPostcode(?string $dropoffPostcode): self
  {
    $this->dropoffPostcode = $dropoffPostcode;

    return $this;
  }

  public function getDropoffName(): ?string
  {
    return $this->dropoffName;
  }

  public function setDropoffName(?string $dropoffName): self
  {
    $this->dropoffName = $dropoffName;

    return $this;
  }

  public function getDelivered(): ?bool
  {
    return $this->delivered;
  }

  public function setDelivered(bool $delivered): self
  {
    $this->delivered = $delivered;

    return $this;
  }

  public function getIncidentDate(): ?DateTimeInterface
  {
    return $this->incidentDate;
  }

  public function setIncidentDate(DateTimeInterface $incidentDate): self
  {
    $this->incidentDate = $incidentDate;

    return $this;
  }

  public function getDeliveryDate(): ?DateTimeInterface
  {
    return $this->deliveryDate;
  }

  public function setDeliveryDate(DateTimeInterface $deliveryDate): self
  {
    $this->deliveryDate = $deliveryDate;

    return $this;
  }

  public function getEventId(): ?string
  {
    return $this->eventId;
  }

  public function setEventId(?string $eventId): self
  {
    $this->eventId = $eventId;

    return $this;
  }

  public function getPromotionAmount(): ?string
  {
    return $this->promotionAmount;
  }

  public function setPromotionAmount(?string $promotionAmount): self
  {
    $this->promotionAmount = $promotionAmount;

    return $this;
  }

  public function getPromotion(): ?Promotion
  {
    return $this->promotion;
  }

  public function setPromotion(?Promotion $promotion): self
  {
    $this->promotion = $promotion;

    return $this;
  }
}
