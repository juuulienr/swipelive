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
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("order:read")
     */
    private $updateAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("order:read")
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("order:read")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("order:read")
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $statusId;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="orderStatuses")
     */
    private $shipping;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeInterface $updateAt): self
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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

    public function getStatusId(): ?string
    {
        return $this->statusId;
    }

    public function setStatusId(string $statusId): self
    {
        $this->statusId = $statusId;

        return $this;
    }
}
