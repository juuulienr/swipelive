<?php

namespace App\Entity;

use App\Repository\LiveRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass=LiveRepository::class)
*/
class Live
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("live:read")
   * @Groups("user:read")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="lives")
   * @Groups("live:read")
   */
  private $vendor;
  
  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $eventId;

  /**
   * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="live", orphanRemoval=true)
   * @Groups("live:read")
   */
  private $clips;

  /**
   * @ORM\OneToMany(targetEntity=LiveProducts::class, mappedBy="live", cascade={"persist"})
   * @ORM\OrderBy({"priority" = "ASC"})
   * @Groups("live:read")
   */
  private $liveProducts;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @Groups("live:read")
   */
  private $status;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("live:read")
   */
  private $channel;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("live:read")
   */
  private $event;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @Groups("live:read")
   */
  private $display;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("live:read")
   */
  private $resourceId;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("live:read")
   */
  private $fileList;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("live:read")
   */
  private $sid;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("live:read")
   */
  private $cname;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("live:read")
   */
  private $preview;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   * @Groups("live:read")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="integer", nullable=true)
   */
  private $viewers;

  /**
   * @ORM\Column(type="integer", nullable=true)
   */
  private $totalViewers;

  /**
   * @ORM\Column(type="integer", nullable=true)
   */
  private $duration;

  /**
   * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="live")
   * @ORM\OrderBy({"createdAt" = "ASC"})
   * @Groups("live:read")
   * @Groups("clip:read")
   */
  private $comments;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @Groups("live:read")
   */
  private $totalLikes;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $fbStreamId;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $fbStreamUrl;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   */
  private $postUrl;

  
  public function __construct()
  {
    $this->createdAt = new \DateTime('now', timezone_open('UTC'));
    $this->clips = new ArrayCollection();
    $this->liveProducts = new ArrayCollection();
    $this->comments = new ArrayCollection();
    $this->duration = 0;
    $this->viewers = 0;
    $this->totalViewers = 0;
    $this->totalLikes = 0;
    $this->status = 0;
    $this->display = 1;
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

  /**
   * @return Collection|Clip[]
   */
  public function getClips(): Collection
  {
    return $this->clips;
  }

  public function addClip(Clip $clip): self
  {
    if (!$this->clips->contains($clip)) {
      $this->clips[] = $clip;
      $clip->setLive($this);
    }

    return $this;
  }

  public function removeClip(Clip $clip): self
  {
    if ($this->clips->removeElement($clip)) {
          // set the owning side to null (unless already changed)
      if ($clip->getLive() === $this) {
        $clip->setLive(null);
      }
    }

    return $this;
  }

  /**
   * @return Collection|LiveProducts[]
   */
  public function getLiveProducts(): Collection
  {
    return $this->liveProducts;
  }

  public function addLiveProduct(LiveProducts $liveProduct): self
  {
    if (!$this->liveProducts->contains($liveProduct)) {
      $this->liveProducts[] = $liveProduct;
      $liveProduct->setLive($this);
    }

    return $this;
  }

  public function removeLiveProduct(LiveProducts $liveProduct): self
  {
    if ($this->liveProducts->removeElement($liveProduct)) {
          // set the owning side to null (unless already changed)
      if ($liveProduct->getLive() === $this) {
        $liveProduct->setLive(null);
      }
    }

    return $this;
  }

  public function getStatus(): ?int
  {
    return $this->status;
  }

  public function setStatus(?int $status): self
  {
    $this->status = $status;

    return $this;
  }

  public function getChannel(): ?string
  {
    return $this->channel;
  }

  public function setChannel(?string $channel): self
  {
    $this->channel = $channel;

    return $this;
  }

  public function getEvent(): ?string
  {
    return $this->event;
  }

  public function setEvent(?string $event): self
  {
    $this->event = $event;

    return $this;
  }

  public function getDisplay(): ?int
  {
    return $this->display;
  }

  public function setDisplay(?int $display): self
  {
    $this->display = $display;

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

  public function getSid(): ?string
  {
    return $this->sid;
  }

  public function setSid(?string $sid): self
  {
    $this->sid = $sid;

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

  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->createdAt;
  }

  public function setCreatedAt(?\DateTimeInterface $createdAt): self
  {
    $this->createdAt = $createdAt;

    return $this;
  }

  public function getViewers(): ?int
  {
    return $this->viewers;
  }

  public function setViewers(?int $viewers): self
  {
    $this->viewers = $viewers;

    return $this;
  }

  public function getTotalViewers(): ?int
  {
    return $this->totalViewers;
  }

  public function setTotalViewers(?int $totalViewers): self
  {
    $this->totalViewers = $totalViewers;

    return $this;
  }

  public function getDuration(): ?int
  {
    return $this->duration;
  }

  public function setDuration(?int $duration): self
  {
    $this->duration = $duration;

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
      $comment->setLive($this);
    }

    return $this;
  }

  public function removeComment(Comment $comment): self
  {
    if ($this->comments->removeElement($comment)) {
          // set the owning side to null (unless already changed)
      if ($comment->getLive() === $this) {
        $comment->setLive(null);
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

  public function getFbStreamId(): ?string
  {
    return $this->fbStreamId;
  }

  public function setFbStreamId(?string $fbStreamId): self
  {
    $this->fbStreamId = $fbStreamId;

    return $this;
  }

  public function getFbStreamUrl(): ?string
  {
    return $this->fbStreamUrl;
  }

  public function setFbStreamUrl(?string $fbStreamUrl): self
  {
    $this->fbStreamUrl = $fbStreamUrl;

    return $this;
  }

  public function getPostUrl(): ?string
  {
    return $this->postUrl;
  }

  public function setPostUrl(?string $postUrl): self
  {
    $this->postUrl = $postUrl;

    return $this;
  }

  public function getCname(): ?string
  {
    return $this->cname;
  }

  public function setCname(?string $cname): self
  {
    $this->cname = $cname;

    return $this;
  }

}
