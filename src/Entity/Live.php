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
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="lives")
     * @Groups("live:read")
     */
    private $vendor;

    /**
     * @ORM\Column(type="integer")
     * @Groups("live:read")
     * @Groups("clip:read")
     */
    private $views;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="live", orphanRemoval=true)
     * @Groups("live:read")
     * @Groups("clip:read")
     */
    private $messages;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("live:read")
     * @Groups("clip:read")
     */
    private $broadcastId;

    /**
     * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="live", orphanRemoval=true)
     * @Groups("live:read")
     */
    private $clips;

    /**
     * @ORM\OneToMany(targetEntity=LiveProducts::class, mappedBy="live", cascade={"persist"})
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

    
    public function __construct()
    {
        $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
        $this->messages = new ArrayCollection();
        $this->clips = new ArrayCollection();
        $this->views = 0;
        $this->status = 0;
        $this->display = 0;
        $this->liveProducts = new ArrayCollection();
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

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(int $views): self
    {
        $this->views = $views;

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
            $message->setLive($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getLive() === $this) {
                $message->setLive(null);
            }
        }

        return $this;
    }

    public function getBroadcastId(): ?string
    {
        return $this->broadcastId;
    }

    public function setBroadcastId(?string $broadcastId): self
    {
        $this->broadcastId = $broadcastId;

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
}
