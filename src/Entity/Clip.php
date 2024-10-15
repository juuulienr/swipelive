<?php

namespace App\Entity;

use App\Repository\ClipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass=ClipRepository::class)
*/
class Clip
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("clip:read")
   * @Groups("user:read")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="clips")
   * @Groups("clip:read")
   */
  private $vendor;

  /**
   * @ORM\ManyToOne(targetEntity=Live::class, inversedBy="clips")
   * @ORM\JoinColumn(nullable=false)
   */
  private $live;

  /**
   * @ORM\Column(type="integer")
   * @Groups("clip:read")
   */
  private $start;

  /**
   * @ORM\Column(type="integer")
   * @Groups("clip:read")
   */
  private $end;

  /**
   * @ORM\Column(type="integer")
   * @Groups("clip:read")
   */
  private $duration;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("clip:read")
   */
  private $resourceId;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("clip:read")
   */
  private $fileList;

  /**
   * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="clips")
   * @ORM\JoinColumn(nullable=false)
   * @Groups("clip:read")
   */
  private $product;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("clip:read")
   */
  private $preview;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("clip:read")
   */
  private $status;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   * @Groups("clip:read")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $eventId;

  /**
   * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="clip")
   * @ORM\OrderBy({"createdAt" = "ASC"})
   * @Groups("clip:read")
   */
  private $comments;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @Groups("clip:read")
   */
  private $totalLikes;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $jobId;

  
  public function __construct()
  {
    $this->status = "waiting";
    $this->createdAt = new \DateTime('now', timezone_open('UTC'));
    $this->comments = new ArrayCollection();
    $this->totalLikes = rand(10, 200);
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

  public function getLive(): ?Live
  {
    return $this->live;
  }

  public function setLive(?Live $live): self
  {
    $this->live = $live;

    return $this;
  }

  public function getStart(): ?int
  {
    return $this->start;
  }

  public function setStart(int $start): self
  {
    $this->start = $start;

    return $this;
  }

  public function getEnd(): ?int
  {
    return $this->end;
  }

  public function setEnd(int $end): self
  {
    $this->end = $end;

    return $this;
  }

  public function getDuration(): ?int
  {
    return $this->duration;
  }

  public function setDuration(int $duration): self
  {
    $this->duration = $duration;

    return $this;
  }

  public function getResourceId(): ?string
  {
    return $this->resourceId;
  }

  public function setResourceId(?string $resourceId): self
  {
    $this->resourceId = $resourceId;

    return $this;
  }

  public function getFileList(): ?string
  {
    return $this->fileList;
  }

  public function setFileList(?string $fileList): self
  {
    $this->fileList = $fileList;

    return $this;
  }

  public function getProduct(): ?Product
  {
    return $this->product;
  }

  public function setProduct(?Product $product): self
  {
    $this->product = $product;

    return $this;
  }

  public function getPreview(): ?string
  {
    return $this->preview;
  }

  public function setPreview(?string $preview): self
  {
    $this->preview = $preview;

    return $this;
  }

  public function getStatus(): ?string
  {
    return $this->status;
  }

  public function setStatus(?string $status): self
  {
    $this->status = $status;

    return $this;
  }

  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->createdAt;
  }

  public function setCreatedAt(?\DateTimeInterface $createdAt): self
  {
    $this->createdAt = $createdAt;

    return $this;
  }

  public function getEventId(): ?string
  {
    return $this->eventId;
  }

  public function setEventId(?string $eventId): self
  {
    $this->eventId = $eventId;

    return $this;
  }

  /**
   * @return Collection|Comment[]
   */
  public function getComments(): Collection
  {
    return $this->comments;
  }

  public function addComment(Comment $comment): self
  {
    if (!$this->comments->contains($comment)) {
      $this->comments[] = $comment;
      $comment->setClip($this);
    }

    return $this;
  }

  public function removeComment(Comment $comment): self
  {
    if ($this->comments->removeElement($comment)) {
          // set the owning side to null (unless already changed)
      if ($comment->getClip() === $this) {
        $comment->setClip(null);
      }
    }

    return $this;
  }

  public function getTotalLikes(): ?int
  {
    return $this->totalLikes;
  }

  public function setTotalLikes(?int $totalLikes): self
  {
    $this->totalLikes = $totalLikes;

    return $this;
  }

  public function setJobId(string $jobId): self
  {
      $this->jobId = $jobId;
      return $this;
  }

  public function getJobId(): ?string
  {
      return $this->jobId;
  }

}
