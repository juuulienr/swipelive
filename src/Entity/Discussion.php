<?php

namespace App\Entity;

use App\Repository\DiscussionRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DiscussionRepository::class)
 */
class Discussion
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $id;

  /**
   * @ORM\Column(type="text")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $preview;

  /**
   * @ORM\Column(type="datetime")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $updatedAt;

  /**
   * @ORM\OneToMany(targetEntity=Message::class, mappedBy="discussion", cascade={"persist"}), orphanRemoval=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $messages;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $unseen;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $unseenVendor;

  /**
   * @ORM\ManyToOne(targetEntity=User::class, inversedBy="discussions")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $user;

  /**
   * @ORM\ManyToOne(targetEntity=User::class, inversedBy="discussions")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $vendor;

  /**
   * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="discussions")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $purchase;


  public function __construct()
  {
    $this->messages = new ArrayCollection();
    $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
    $this->updatedAt = new \DateTime('now', timezone_open('Europe/Paris'));
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getPreview(): ?string
  {
    return $this->preview;
  }

  public function setPreview(string $preview): self
  {
    $this->preview = $preview;

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

  public function getUpdatedAt(): ?\DateTimeInterface
  {
    return $this->updatedAt;
  }

  public function setUpdatedAt(\DateTimeInterface $updatedAt): self
  {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * @return Collection|Message[]
   */
  public function getMessages(): Collection
  {
    return $this->messages;
  }

  public function addMessage(Message $message): self
  {
    if (!$this->messages->contains($message)) {
      $this->messages[] = $message;
      $message->setDiscussion($this);
    }

    return $this;
  }

  public function removeMessage(Message $message): self
  {
    if ($this->messages->removeElement($message)) {
          // set the owning side to null (unless already changed)
      if ($message->getDiscussion() === $this) {
        $message->setDiscussion(null);
      }
    }

    return $this;
  }

  public function getUnseen(): ?bool
  {
    return $this->unseen;
  }

  public function setUnseen(?bool $unseen): self
  {
    $this->unseen = $unseen;

    return $this;
  }

  public function getUnseenVendor(): ?bool
  {
    return $this->unseenVendor;
  }

  public function setUnseenVendor(?bool $unseenVendor): self
  {
    $this->unseenVendor = $unseenVendor;

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

  public function getVendor(): ?User
  {
    return $this->vendor;
  }

  public function setVendor(?User $vendor): self
  {
    $this->vendor = $vendor;

    return $this;
  }

  public function getPurchase(): ?Order
  {
    return $this->purchase;
  }

  public function setPurchase(?Order $purchase): self
  {
    $this->purchase = $purchase;

    return $this;
  }
}
