<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransactionRequest
{
    /**
     * @Assert\NotBlank(message="Amount is required")
     * @Assert\Type(type="numeric", message="Amount must be numeric")
     * @Assert\GreaterThan(value=0, message="Amount must be greater than 0")
     */
    public ?int $amount;

    /**
     * @Assert\NotBlank(message="Currency is required")
     * @Assert\Length(min=3, max=3, exactMessage="Currency must be a 3 letter code")
     */
    public ?string $currency;

    /**
     * @Assert\NotBlank(message="Card number is required")
     */
    public ?string $cardNumber;

    /**
     * @Assert\NotBlank(message="Card expiration year is required")
     * @Assert\GreaterThanOrEqual(value=2024, message="Expiration year must be 2024 or later")
     */
    public ?string $cardExpYear;

     /**
     * @Assert\NotBlank(message="Card expiration month is required")
     * @Assert\Range(min=1, max=12, notInRangeMessage="Expiration month must be between 1 and 12")
     */
    public ?string $cardExpMonth;

    /**
     * @Assert\NotBlank(message="CVV is required")
     * @Assert\Length(min=3, max=4, exactMessage="CVV must be between 3 and 4 digits")
     */
    public ?string $cardCvv;

    /**
     * @Assert\Callback
     */
    //Extra layer of validation to make sure that expiration month is in the future if the expiration year is the current year
    public function validateExpiryDate(ExecutionContextInterface $context)
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        
        $cardExpYear = (int) $this->cardExpYear;
        $cardExpMonth = (int) $this->cardExpMonth;
        
        if ($cardExpYear === $currentYear && $cardExpMonth < $currentMonth) {
            $context->buildViolation('Expiration month must be in the future if the expiration year is the current year')
                ->atPath('cardExpMonth')
                ->addViolation();
        }
    }

    // Getters and Setters

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(string $cardNumber): void
    {
        $this->cardNumber = $cardNumber;
    }

    public function getCardExpYear(): string
    {
        return $this->cardExpYear;
    }

    public function setCardExpYear(string $cardExpYear): void
    {
        $this->cardExpYear = $cardExpYear;
    }

    public function getCardExpMonth(): string
    {
        return $this->cardExpMonth;
    }

    public function setCardExpMonth(string $cardExpMonth): void
    {
        $this->cardExpMonth = $cardExpMonth;
    }

    public function getCardCvv(): string
    {
        return $this->cardCvv;
    }

    public function setCardCvv(string $cardCvv): void
    {
        $this->cardCvv = $cardCvv;
    }
}