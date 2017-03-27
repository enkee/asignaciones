<?php
namespace UserBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * Task
 *
 * @ORM\Table(name="task")
 * @ORM\Entity(repositoryClass="UserBundle\Entity\TaskRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Task
{
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="task")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */ 
    protected $user;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100)
     * @Assert\NotBlank()
     */
    private $title;
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank()
     */
    private $description;
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updateAt;
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set title
     *
     * @param string $title
     * @return Task
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }
    /**
     * Set description
     *
     * @param string $description
     * @return Task
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * Set status
     *
     * @param boolean $status
     * @return Task
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set createAt
     *
     * @param \DateTime $createAt
     * @return Task
     */
    public function setCreateAt($createAt)
    {
        $this->createAt = $createAt;
        return $this;
    }
    /**
     * Get createAt
     *
     * @return \DateTime 
     */
    public function getCreateAt()
    {
        return $this->createAt;
    }
    /**
     * Set updateAt
     *
     * @param \DateTime $updateAt
     * @return Task
     */
    public function setUpdateAt($updateAt)
    {
        $this->updateAt = $updateAt;
        return $this;
    }
    /**
     * Get updateAt
     *
     * @return \DateTime 
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }
    /**
     * @ORM\PrePersist
     */
    public function setCreateAtValue()
    {
        $this->createAt = new \DateTime();
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setUpdateAtValue()
    {
        $this->updateAt = new \DateTime();
    }
    /**
     * Set user
     *
     * @param \UserBundle\Entity\User $user
     * @return Task
     */
    public function setUser(\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
        return $this;
    }
    /**
     * Get user
     *
     * @return \UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}