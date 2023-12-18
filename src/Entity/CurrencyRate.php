<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "currency_rates")]
class CurrencyRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "integer")]
    private $time;

    #[ORM\Column(type: "decimal", precision: 16, scale: 8)]
    private $high;

    #[ORM\Column(type: "decimal", precision: 16, scale: 8)]
    private $low;

    #[ORM\Column(type: "decimal", precision: 16, scale: 8)]
    private $open;

    #[ORM\Column(type: "decimal", precision: 16, scale: 8)]
    private $close;

    #[ORM\Column(type: "decimal", precision: 16, scale: 8)]
    private $volumeFrom;

    #[ORM\Column(type: "string", length: 7)]
    private $currencyPair;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function getFormattedTime(): ?\DateTime
    {
        return ($this->time !== null) ? \DateTime::createFromFormat('U', $this->time) : null;
    }
    public function getVolumeFrom(): string
    {
        return $this->volumeFrom;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function setFormattedTime(\DateTime $dateTime): self
    {
        $this->time = $dateTime->getTimestamp();
        return $this;
    }

    public function getHigh(): string
    {
        return $this->high;
    }

    public function setHigh(string $high): self
    {
        $this->high = $high;
        return $this;
    }

    public function getLow(): string
    {
        return $this->low;
    }

    public function setLow(string $low): self
    {
        $this->low = $low;
        return $this;
    }

    public function getOpen(): string
    {
        return $this->open;
    }

    public function setOpen(string $open): self
    {
        $this->open = $open;
        return $this;
    }

    public function getClose(): string
    {
        return $this->close;
    }

    public function setClose(string $close): self
    {
        $this->close = $close;
        return $this;
    }
    public function setVolumeFrom(string $volumeFrom): self
    {
        $this->volumeFrom = $volumeFrom;
        return $this;
    }
    public function getCurrencyPair(): string
    {
        return $this->currencyPair;
    }

    public function setCurrencyPair(string $currencyPair): self
    {
        $this->currencyPair = $currencyPair;
        return $this;
    }
}