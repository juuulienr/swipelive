<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BankAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=BankAccountRepository::class)
 */
class BankAccount
{
  /**
   * @ORM\Id
   *
   * @ORM\GeneratedValue
   *
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $bankId;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   */
  private $currency;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   */
  private $number;

  /**
   * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="bankAccounts")
   *
   * @ORM\JoinColumn(nullable=false)
   */
  private $vendor;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   */
  private $last4;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $businessName;

  /**
   * @ORM\Column(type="string", length=255)
   *
   * @Groups("user:read")
   */
  private $countryCode;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   *
   * @Groups("user:read")
   */
  private $holderName;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getBankId(): ?string
  {
    return $this->bankId;
  }

  public function setBankId(?string $bankId): self
  {
    $this->bankId = $bankId;

    return $this;
  }

  public function getCurrency(): ?string
  {
    return $this->currency;
  }

  public function setCurrency(string $currency): self
  {
    $this->currency = $currency;

    return $this;
  }

  public function getNumber(): ?string
  {
    return $this->number;
  }

  public function setNumber(string $number): self
  {
    $this->number = $number;

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

  public function getLast4(): ?string
  {
    return $this->last4;
  }

  public function setLast4(string $last4): self
  {
    $this->last4 = $last4;

    return $this;
  }

  public function getBusinessName(): ?string
  {
    return $this->businessName;
  }

  public function setBusinessName(?string $businessName): self
  {
    $this->businessName = $businessName;

    return $this;
  }

  public function getCountryCode(): ?string
  {
    return $this->countryCode;
  }

  public function setCountryCode(string $countryCode): self
  {
    $this->countryCode = $countryCode;

    return $this;
  }

  public function getHolderName(): ?string
  {
    return $this->holderName;
  }

  public function setHolderName(?string $holderName): self
  {
    $this->holderName = $holderName;

    return $this;
  }
}
