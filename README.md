# api-cli-handler
 Metricalo Test Assignment

This project was developed as the assigned task for Metricalo. Below is an overview of the key elements and the process during development

Overview : 

This application processes transactions via both an API and CLI. It supports different payment processors (Shift4,ACI) "For Now" and has a clean design that allows easy expansion for other providers in the future

Technologies Used :

- PHP 8.3.11 
- Symfony 6.4.11
    - symfony/maker-bundle
    - symfony/http-client
    - symfony/validator
    - doctrine/annotations

Main Functions : 

- ProcessTransactionCommand : created to handle transaction through command-line
- TransactionController : created using maker bundle to handle transaction through HTTP requests
- TransactionRequest : created in order to validate data in a cleaner way , and it can be used anywhere else in the app
- TransactionService : which does the core logic used by both the API and CLI
- PaymentProcessor Interface : defines a common contract for all payment processors which is processPayment
- PaymentProcessor Factory : used to create instances of different payment processors
- PaymentResponseAdapter Interface : defines a common contract for all response adapters which is returnResponse
- PaymentResponseAdapter Factory : used to create instances of different response adapters
- ACIPaymentProcessor : Process the payment request for ACI
- Shift4PaymentProcessor : Process the payment request for Shift4
- ACIPaymentResponseAdapter : created to adapt ACI data into unified transaction response
- Shift4PaymentResponseAdapter : created to adapt Shift4 data into unified transaction response
- UnifiedTransactionResponse : created in order to hold unified transaction response

Side Thoughts - was in my head , liked to share it with you as my task reviewer :D :

- what is card bin ? .. found out it is the first 6 digits of the card
- why Transaction-Payment not Payment-Transaction ? as I took a moments and did a small search I agreed (withmyself :D) that it makes sense for a Payment to be part of the big picture which is the Transaction as we can add any logic we need in the transaction service before doing the payment
- I had a couple of thoughts while working on the data validation :
    - card number should be validated by length ? I searched and found that it is not same for all cards , While working on a production app will check which cards we are accepting and add validation for it 
    - same for the currency there will be currencies that some are not accepted but in this case of course I do not have
    - card expiry date validation in the past is implemented , but should I validate that expiration date isn't too far in the future
- I implemented logs in the code , in a production project I would additionally have a logs db table that holds the transactions details for future logs access 



References :

- https://symfony.com/doc/6.4/console.html
- used https://api.shift4.com/tokens
- https://dev.shift4.com/docs/api#card-create
- https://docs.oppwa.com/integrations/server-to-server#syncPayment
- As I do not have your email to share my postman collection where I got introduced to ACI & SHIFT4 Requests to test the request and resposne myself not only depending on documentatons , and also testing my own endpoints developed so I added "Metricalo Test Assignment.postman_collection.json" which is JSON exported collection from my local postman

How to run the application : 

Message : 

I would like to take this opportunity to thank you for reviewing my project. Your time and feedback are greatly appreciated, and I hope this project reflects my skills and approach to solving problems effectively

Even if this task does not result in joining Metricalo, I would appreciate any feedback you may have. Your insights will help me improve and grow

Once again, thank you for your time and consideration!