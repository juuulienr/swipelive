<?php

namespace App\Entity;

use App\Entity\Role;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Un compte est associé à cette adresse mail"
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("user:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email(message="L'adresse mail est invalide !")
     * @Groups("user:read")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pushToken;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $picture;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeCustomer;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="user")
     * @Groups("user:read")
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="following", orphanRemoval=true)
     * @Groups("user:read")
     * @Groups("clip:read")
     * @Groups("live:read")
     */
    private $followers;

    /**
     * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="follower", orphanRemoval=true)
     * @Groups("user:read")
     */
    private $following;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user:read")
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="buyer")
     * @Groups("user:read")
     */
    private $purchases;

    /**
     * @ORM\OneToOne(targetEntity=Vendor::class, cascade={"persist", "remove"})
     */
    private $vendor;
    

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->purchases = new ArrayCollection();
    }

    public function getId(): ?int
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

        return ['ROLE_USER'];
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
            $message->setUser($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getUser() === $this) {
                $message->setUser(null);
            }
        }

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

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

    public function getStripeCustomer(): ?string
    {
        return $this->stripeCustomer;
    }

    public function setStripeCustomer(?string $stripeCustomer): self
    {
        $this->stripeCustomer = $stripeCustomer;

        return $this;
    }


    /**
     * @return Collection|Follow[]
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(Follow $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers[] = $follower;
            $follower->setFollowing($this);
        }

        return $this;
    }

    public function removeFollower(Follow $follower): self
    {
        if ($this->followers->removeElement($follower)) {
            // set the owning side to null (unless already changed)
            if ($follower->getFollowing() === $this) {
                $follower->setFollowing(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Follow[]
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(Follow $following): self
    {
        if (!$this->following->contains($following)) {
            $this->following[] = $following;
            $following->setFollower($this);
        }

        return $this;
    }

    public function removeFollowing(Follow $following): self
    {
        if ($this->following->removeElement($following)) {
            // set the owning side to null (unless already changed)
            if ($following->getFollower() === $this) {
                $following->setFollower(null);
            }
        }

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(Order $purchase): self
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases[] = $purchase;
            $purchase->setBuyer($this);
        }

        return $this;
    }

    public function removePurchase(Order $purchase): self
    {
        if ($this->purchases->removeElement($purchase)) {
            // set the owning side to null (unless already changed)
            if ($purchase->getBuyer() === $this) {
                $purchase->setBuyer(null);
            }
        }

        return $this;
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
}
