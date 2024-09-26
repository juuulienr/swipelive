<?php

namespace App\Entity;

use App\Repository\ShippingAddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
* @ORM\Entity(repositoryClass=ShippingAddressRepository::class)
*/
class ShippingAddress
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("user:read")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $address;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   */
  private $houseNumber;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $city;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $zip;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $country;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $countryCode;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("user:read")
   */
  private $phone;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $name;

  /**
   * @ORM\ManyToOne(targetEntity=User::class, inversedBy="shippingAddresses")
   * @ORM\JoinColumn(nullable=false)
   */
  private $user;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $latitude;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("user:read")
   */
  private $longitude;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getAddress(): ?string
  {
    return $this->address;
  }

  public function setAddress(string $address): self
  {
    $this->address = $address;

    return $this;
  }

  public function getHouseNumber(): ?string
  {
    return $this->houseNumber;
  }

  public function setHouseNumber(?string $houseNumber): self
  {
    $this->houseNumber = $houseNumber;

    return $this;
  }

  public function getCity(): ?string
  {
    return $this->city;
  }

  public function setCity(string $city): self
  {
    $this->city = $city;

    return $this;
  }

  public function getZip(): ?string
  {
    return $this->zip;
  }

  public function setZip(string $zip): self
  {
    $this->zip = $zip;

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

  public function getCountryCode(): ?string
  {
    return $this->countryCode;
  }

  public function setCountryCode(string $countryCode): self
  {
    $this->countryCode = $countryCode;

    return $this;
  }

  public function getPhone(): ?string
  {
    return $this->phone;
  }

  public function setPhone(?string $phone): self
  {
    $this->phone = $phone;

    return $this;
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

  public function getUser(): ?User
  {
    return $this->user;
  }

  public function setUser(?User $user): self
  {
    $this->user = $user;

    return $this;
  }

  public function getLatitude(): ?string
  {
    return $this->latitude;
  }

  public function setLatitude(string $latitude): self
  {
    $this->latitude = $latitude;

    return $this;
  }

  public function getLongitude(): ?string
  {
    return $this->longitude;
  }

  public function setLongitude(string $longitude): self
  {
    $this->longitude = $longitude;

    return $this;
  }
}
