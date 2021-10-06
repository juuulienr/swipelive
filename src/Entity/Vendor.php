<?php

namespace App\Entity;

use App\Entity\Role;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VendorRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Un compte est associé à cette adresse e-mail"
 * )
 */
class Vendor implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("vendor:read")
     * @Groups("clip:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email(message="L'adresse mail est invalide !")
     * @Groups("vendor:read")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("vendor:read")
     */
    private $pushToken;


    /**
     * @ORM\Column(type="datetime")
     * @Groups("vendor:read")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=Live::class, mappedBy="vendor")
     */
    private $lives;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="vendor")
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity=Clip::class, mappedBy="vendor")
     */
    private $clips;

    
    public function __construct()
    {
        $this->createdAt = new \DateTime('now', timezone_open('Europe/Paris'));
        $this->lives = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->clips = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getRoles() {

        return ['ROLE_VENDOR'];
    }

    public function getPassword() {
        return $this->hash;
    }

    public function getSalt() {}
    
    public function getUsername() {
        return $this->email;
    }

    public function eraseCredentials() {}


    public function getPushToken(): ?string
    {
        return $this->pushToken;
    }

    public function setPushToken(?string $pushToken): self
    {
        $this->pushToken = $pushToken;

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

    /**
     * @return Collection|Live[]
     */
    public function getLives(): Collection
    {
        return $this->lives;
    }

    public function addLife(Live $life): self
    {
        if (!$this->lives->contains($life)) {
            $this->lives[] = $life;
            $life->setVendor($this);
        }

        return $this;
    }

    public function removeLife(Live $life): self
    {
        if ($this->lives->removeElement($life)) {
            // set the owning side to null (unless already changed)
            if ($life->getVendor() === $this) {
                $life->setVendor(null);
            }
        }

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
            $message->setVendor($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getVendor() === $this) {
                $message->setVendor(null);
            }
        }

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
            $clip->setVendor($this);
        }

        return $this;
    }

    public function removeClip(Clip $clip): self
    {
        if ($this->clips->removeElement($clip)) {
            // set the owning side to null (unless already changed)
            if ($clip->getVendor() === $this) {
                $clip->setVendor(null);
            }
        }

        return $this;
    }
}
