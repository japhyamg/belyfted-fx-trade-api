## Belyfted FX Trade API

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

## Set Up
- Run: git clone https://github.com/japhyamg/belyfted-fx-trade-api.git
- Run: composer install
- Run: php artisan migrate
- Run: php artisan db:seed

- To test: php artisan test

## Architecture
This solution uses a Client/Server architecture which follows a request-response pattern, where the client sends a request and the server processes it and sends back a response.
This architecture pattern allows for centralized maintenance and scalability.

## Design Pattern
Two different design patterns was used;
- Repository pattern: To wrap model actions, to abstract the data access layer,  the controller uses the repository instead of the model directly and in that repository you may declare your methods using the model
- Service Pattern: This encapsulates core business logic to manage complex operations, freeing the controller and allowing it to focus on request and response.


## Database Design
The database consists of 4 tables; users, accounts, audit_logs, market_rate and trade.


## Project Structure
belyfted-fx-trade-api
-app 
    -DTOs: for Data transfer objects
    -Http: for controllers and requests
    -Models: for Database models
    -Repositories: for Data access layer
    -Services: for Core Business logic
-bootstrap 
-config 
-database
-public 
-resources
-routes
-storage
-tests
-vendor
-.env
-artisan
-composer.json
-composer.lock
-package.json
-phpunit.xml
-README.md
-vite.config.js
-belyfted-fx-trade-api.postman_collection.json : Postman Collection
