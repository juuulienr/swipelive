<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass=MessageRepository::class)
*/
class Message
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
   * @ORM\Column(type="integer")
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $fromUser;

  /**
   * @ORM\Column(type="datetime")
   * @ORM\OrderBy({"createdAt" = "ASC"})
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $createdAt;

  /**
   * @ORM\ManyToOne(targetEntity=Discussion::class, inversedBy="messages")
   * @ORM\JoinColumn(nullable=false)
   */
  private $discussion;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $picture;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $text;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $pictureType;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   * @Groups("discussion:read")
   * @Groups("user:read")
   */
  private $loading = false;

  
  public function __construct()
  {
    $this->createdAt = new \DateTime('now', timezone_open('UTC'));
  }
  

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getFromUser(): ?int
  {
    return $this->fromUser;
  }

  public function setFromUser(int $fromUser): self
  {
    $this->fromUser = $fromUser;

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

  public function getDiscussion(): ?Discussion
  {
    return $this->discussion;
  }

  public function setDiscussion(?Discussion $discussion): self
  {
    $this->discussion = $discussion;

    return $this;
  }

  public function getPicture(): ?string
  {
    return $this->picture;
  }

  public function setPicture(?string $picture): self
  {
    $this->picture = $picture;

    return $this;
  }

  public function getText(): ?string
  {
    return $this->text;
  }

  public function setText(?string $text): self
  {
    $this->text = $text;

    return $this;
  }

  public function getPictureType(): ?string
  {
    return $this->pictureType;
  }

  public function setPictureType(?string $pictureType): self
  {
    $this->pictureType = $pictureType;

    return $this;
  }

  public function getLoading(): ?bool
  {
    return $this->loading;
  }

  public function setLoading(?bool $loading): self
  {
    $this->loading = $loading;

    return $this;
  }
}
