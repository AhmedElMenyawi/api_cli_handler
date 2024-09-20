# api-cli-handler
 Metricalo Test Assignment

This project is developed as a part of the assigned task. Below is an overview of the key elements and my thought process during the development.

Using :
- PHP 8.3.11 
- Symfony 6.4.11
- symfony/maker-bundle
- symfony/http-client

Used maker bundle to make TransactionController
Manually added DTO/TransactionRequest to transfer input data to object in order to validate data in a cleaner way , and it can be used anywhere else in the app
Manually added Service/TransactionService to do the logic for both API & CLI
Used maker bundle to make command ProcessTransaction
Manually added Service/Payment folder to cleanly handle all payment service needed code
Manually added Service/Payment/PaymentProcessorInterface to make sure whatever provider we implement they are gonna speak the same language and have processPayment()
Manually added Service/Payment/PaymentProcessorFactory for creating instances of different payment processors without complexity
Manually added Service/Payment/Processors/ACIPaymentProcessor for handling transactions using ACI
Manually added Service/Payment/Processors/Shift4PaymentProcessor for handling transactions using Shift4
Checking what is card bin ? 

References Used : 
- https://symfony.com/doc/6.4/console.html
- used https://api.shift4.com/tokens as part of getting introduced to shift4 to understand more about how it works
- https://dev.shift4.com/docs/api#card-create




#Remember to disable debuggers before last commit