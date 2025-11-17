<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SecurityUserRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=SecurityUserRepository::class)
 */
class SecurityUser
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
     * @ORM\Column(type="datetime")
     *
     * @Groups("user:read")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups("user:read")
     * @Groups("discussion:read")
     */
    private $connectedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="securityUsers")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wifiIPAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $carrierIPAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $connection;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups("user:read")
     */
    private $platform;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $version;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $manufacturer;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isVirtual;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups("user:read")
     */
    private $timezone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups("user:read")
     */
    private $locale;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now', \timezone_open('UTC'));
        $this->connectedAt = new \DateTime('now', \timezone_open('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getConnectedAt(): ?\DateTimeInterface
    {
        return $this->connectedAt;
    }

    public function setConnectedAt(\DateTimeInterface $connectedAt): self
    {
        $this->connectedAt = $connectedAt;

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

    public function getWifiIPAddress(): ?string
    {
        return $this->wifiIPAddress;
    }

    public function setWifiIPAddress(?string $wifiIPAddress): self
    {
        $this->wifiIPAddress = $wifiIPAddress;

        return $this;
    }

    public function getCarrierIPAddress(): ?string
    {
        return $this->carrierIPAddress;
    }

    public function setCarrierIPAddress(?string $carrierIPAddress): self
    {
        $this->carrierIPAddress = $carrierIPAddress;

        return $this;
    }

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    public function setConnection(?string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): self
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getIsVirtual(): ?bool
    {
        return $this->isVirtual;
    }

    public function setIsVirtual(?bool $isVirtual): self
    {
        $this->isVirtual = $isVirtual;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}
