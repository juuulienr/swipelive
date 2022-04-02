<?php

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
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $bankId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $currency;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $number;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="bankAccounts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $last4;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBankId(): ?string
    {
        return $this->bankId;
    }

    public function setBankId(string $bankId): self
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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

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
}
