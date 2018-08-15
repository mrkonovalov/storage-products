<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    
    /**
     * TODO:: конечно лучше вынести в список
     *
     * @ORM\Column(type="string", length=255)
     */
    private $type;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $price;
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }
    
    public function setName(string $name): self
    {
        $this->name = $name;
        
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
    
    public function getPrice(): ?string
    {
        return $this->price;
    }
    
    /**
     * Для примера, пусть будет выводится цена с указанием валюты
     *
     * @return null|string
     */
    public function getPriceF(): ?string
    {
        if ($price = (float)$this->getPrice()) {
            return number_format($price, 2, ',', '&nbsp;').'&nbsp;₽';
        }
        
        return $this->getPrice();
    }
    
    public function setPrice(string $price): self
    {
        $this->price = $price;
        
        return $this;
    }
    
    /**
     * Плохие ли данные?
     *
     * @return bool
     */
    public function isBadData(): bool
    {
        return $this->isBadPrice() || $this->isBadName();
    }
    
    /**
     * Плохая цена?
     *
     * @return bool
     */
    public function isBadPrice(): bool
    {
        return (int)$this->getPrice() < 0 || !(int)$this->getPrice();
    }
    
    /**
     * Плохое название?
     *
     * @return bool
     */
    public function isBadName(): bool
    {
        return mb_strlen($this->getName()) <= 3;
    }
}
