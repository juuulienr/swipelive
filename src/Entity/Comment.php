<?php

namespace App\Entity;

use App\Repository\LiveRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass=CommentRepository::class)
*/
class Comment
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity=Live::class, inversedBy="comments")
   */
  private $live;

  /**
   * @ORM\ManyToOne(targetEntity=User::class, inversedBy="comments")
   * @ORM\JoinColumn(nullable=false)
   * @Groups("live:read")
   * @Groups("clip:read")
   */
  private $user;

  /**
   * @ORM\Column(type="string", length=255)
   * @Groups("live:read")
   * @Groups("clip:read")
   */
  private $content;

  /**
   * @ORM\ManyToOne(targetEntity=Clip::class, inversedBy="comments")
   */
  private $clip;

  /**
   * @ORM\Column(type="datetime")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   */
  private $isVendor;

  public function __construct()
  {
    $this->createdAt = new \DateTime('now', timezone_open('UTC'));
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getLive(): ?Live
  {
    return $this->live;
  }

  public function setLive(?Live $live): self
  {
    $this->live = $live;

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

  public function getContent(): ?string
  {
    return $this->content;
  }

  public function setContent(string $content): self
  {
    $this->content = $content;

    return $this;
  }

  public function getClip(): ?Clip
  {
    return $this->clip;
  }

  public function setClip(?Clip $clip): self
  {
    $this->clip = $clip;

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

  public function getIsVendor(): ?bool
  {
    return $this->isVendor;
  }

  public function setIsVendor(?bool $isVendor): self
  {
    $this->isVendor = $isVendor;
    return $this;
  }
}
