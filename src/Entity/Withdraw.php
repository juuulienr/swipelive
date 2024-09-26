<?php

namespace App\Entity;

use App\Repository\WithdrawRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
* @ORM\Entity(repositoryClass=WithdrawRepository::class)
*/
class Withdraw
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="withdraws")
   */
  private $vendor;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $payoutId;

  /**
   * @ORM\Column(type="decimal", precision=8, scale=2)
   * @Groups("user:read")
   */
  private $amount;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $status;

  /**
   * @ORM\Column(type="datetime")
   * @Groups("user:read")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $last4;

  
  public function __construct()
  {
    $this->createdAt = new \DateTime('now', timezone_open('UTC'));
  }

  public function getId(): ?int
  {
    return $this->id;
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

  public function getPayoutId(): ?string
  {
    return $this->payoutId;
  }

  public function setPayoutId(?string $payoutId): self
  {
    $this->payoutId = $payoutId;

    return $this;
  }

  public function getAmount(): ?string
  {
    return $this->amount;
  }

  public function setAmount(string $amount): self
  {
    $this->amount = $amount;

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

  public function getLast4(): ?string
  {
    return $this->last4;
  }

  public function setLast4(string $last4): self
  {
    $this->last4 = $last4;

    return $this;
  }
}
