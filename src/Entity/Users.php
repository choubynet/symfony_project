<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UsersRepository")
 * @UniqueEntity(
 *  fields = {"email"},
 *  message = "Ce mail est déja utilisé !"
 * )
 * @UniqueEntity(
 *  fields = {"username"},
 *  message = "Ce pseudo est déja utilisé !"
 * )
 */
class Users implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    
    /**
     * @ORM\Column(type="string", length=255)
     * * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Le prénom doit comporter au minimum {{ limit }} caractères",
     *      maxMessage = "Le prénom doit comporter au maximum {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Le nom doit comporter au minimum {{ limit }} caractères",
     *      maxMessage = "Le nom doit comporter au maximum {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $last_name;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Le pseudo doit comporter au minimum {{ limit }} caractères",
     *      maxMessage = "Le pseudo doit comporter au maximum {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $username;

    /**
     * @ORM\Column(type="date")
     * @Assert\LessThan("today")
     */
    private $birthdate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email(
     *     message = "L'email '{{ value }}' n'est pas un email valide")
     * 
     */
    private $email;

    /**
     * @ORM\Column(type="date")
     */
    private $inscription_date;

    /**
     * @ORM\Column(type="boolean")
     * 
     */
    private $is_admin = false;

    /**
     * @ORM\Column(type="string", length=255)     
     */
    private $password;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_banned = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Lists", mappedBy="user", orphanRemoval=true)
     */
    private $lists;    
    
    public function __construct()
    {
        $this->inscription_date = new \DateTime();
        $this->lists = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }    

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
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

    public function getInscriptionDate(): ?\DateTimeInterface
    {
        return $this->inscription_date;
    }

    public function setInscriptionDate(\DateTimeInterface $inscription_date): self
    {
        $this->inscription_date;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->is_admin;
    }

    public function setIsAdmin(bool $is_admin): self
    {
        $this->is_admin = $is_admin;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);        

        return $this;
    }
    public function getIsBanned(): ?bool
    {
        return $this->is_banned;
    }

    public function setIsBanned(bool $is_banned): self
    {
        $this->is_banned = $is_banned;

        return $this;
    }

    public function eraseCredentials()
    {
        
    }
    public function getSalt()
    {
        
    }
    public function getRoles()
    {
        if ($this->getIsAdmin() == 0)
        {
            return ['ROLE_USER'];
        }
        else
        {
            return ['ROLE_ADMIN'];
        }        
    }

    /**
     * @return Collection|Lists[]
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }

    public function addList(Lists $list): self
    {
        if (!$this->lists->contains($list)) {
            $this->lists[] = $list;
            $list->setUser($this);
        }

        return $this;
    }

    public function removeList(Lists $list): self
    {
        if ($this->lists->contains($list)) {
            $this->lists->removeElement($list);
            // set the owning side to null (unless already changed)
            if ($list->getUser() === $this) {
                $list->setUser(null);
            }
        }

        return $this;
    }
}
