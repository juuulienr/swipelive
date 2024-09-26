<?php

namespace App\Entity;

use App\Repository\OrderStatusRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
* @ORM\Entity(repositoryClass=OrderStatusRepository::class)
*/
class OrderStatus
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("order:read")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("order:read")
   */
  private $code;

  /**
   * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="orderStatuses")
   */
  private $shipping;

  /**
   * @ORM\Column(type="datetime")
   * @Groups("order:read")
   */
  private $date;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("order:read")
   */
  private $location;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("order:read")
   */
  private $city;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("order:read")
   */
  private $postcode;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("order:read")
   */
  private $description;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getCode(): ?string
  {
    return $this->code;
  }

  public function setCode(string $code): self
  {
    $this->code = $code;

    return $this;
  }

  public function getShipping(): ?Order
  {
    return $this->shipping;
  }

  public function setShipping(?Order $shipping): self
  {
    $this->shipping = $shipping;

    return $this;
  }

  public function getDate(): ?\DateTimeInterface
  {
    return $this->date;
  }

  public function setDate(\DateTimeInterface $date): self
  {
    $this->date = $date;

    return $this;
  }

  public function getLocation(): ?string
  {
    return $this->location;
  }

  public function setLocation(string $location): self
  {
    $this->location = $location;

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

  public function getPostcode(): ?string
  {
    return $this->postcode;
  }

  public function setPostcode(string $postcode): self
  {
    $this->postcode = $postcode;

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
}
