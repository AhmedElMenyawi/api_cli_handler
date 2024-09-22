<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransactionRequest
{

    /**
     * @Assert\NotBlank(message="Provider is required")
     * @Assert\Type(type="string", message="Provider must be a string")
     * @Assert\Choice(choices={"aci", "shift4"}, message="Provider must be one of the following: aci, shift")
     */
    public ?string $provider;

    /**
     * @Assert\NotBlank(message="Amount is required")
     * @Assert\Type(type="numeric", message="Amount must be numeric")
     * @Assert\GreaterThan(value=0, message="Amount must be greater than 0")
     */
    public ?float $amount;

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
     * Validates the card expiration date
     *
     * This function is used as an extra layer of validation to ensure that:
     * - The expiration year (`cardExpYear`) is not in the past
     * - If the expiration year is the current year, the expiration month (`cardExpMonth`) must be in the future
     * 
     * This method is invoked by Symfony’s Validator component through the `@Assert\Callback` annotation and adds violations
     * to the `ExecutionContextInterface` if the validation fails
     *
     * @param ExecutionContextInterface $context The validation context used to build violations
     * 
     * @return void Adds a violation to the context if the expiration date is invalid:
     *              - If `cardExpYear` is in the past, it adds a violation for `cardExpYear`
     *              - If `cardExpYear` is the current year and `cardExpMonth` is in the past, it adds a violation for `cardExpMonth`
     */

    /**
     * @Assert\Callback
     */
    public function validateExpiryDate(ExecutionContextInterface $context)
    {
        if ($this->cardExpYear && $this->cardExpMonth) {
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('m');

            $cardExpYear = (int) $this->cardExpYear;
            $cardExpMonth = (int) $this->cardExpMonth;

            if ($cardExpYear < $currentYear) {
                $context->buildViolation('Expiration year cannot be in the past.')
                    ->atPath('cardExpYear')
                    ->addViolation();
            } else {
                if ($cardExpYear === $currentYear && $cardExpMonth < $currentMonth) {
                    $context->buildViolation('Expiration month must be in the future if the expiration year is the current year.')
                        ->atPath('cardExpMonth')
                        ->addViolation();
                }
            }
        }
    }

    // Getters and Setters
    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount(float $amount)
    {
        $this->amount = $amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    public function setCardNumber(string $cardNumber)
    {
        $this->cardNumber = $cardNumber;
    }

    public function getCardExpYear()
    {
        return $this->cardExpYear;
    }

    public function setCardExpYear(string $cardExpYear)
    {
        $this->cardExpYear = $cardExpYear;
    }

    public function getCardExpMonth()
    {
        return $this->cardExpMonth;
    }

    public function setCardExpMonth(string $cardExpMonth)
    {
        $this->cardExpMonth = $cardExpMonth;
    }

    public function getCardCvv()
    {
        return $this->cardCvv;
    }

    public function setCardCvv(string $cardCvv)
    {
        $this->cardCvv = $cardCvv;
    }
}
