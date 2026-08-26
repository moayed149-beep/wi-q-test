# wi-Q - PHP Backend Developer Test

The purpose of this test is to demonstrate your understanding of REST APIs and how you would orchestrate their consumption within an application. Your solution must be written in PHP. Please create the basics of a library using whichever tools you feel would be the most suitable to get the job done. We would prefer no specific framework, but if you have a good reason to use something, then feel free. We would also encourage the use of other packages that you deem useful.

Your library should provide the functionality to interact with a REST API, allowing you to perform the operations listed below (Scenario 1 & Scenario 2). It is not necessary to create a functioning REST API for this task, we are simply trying to see how you would go about connecting and consuming information from one. It is important that your library would work in a real world scenario, if required.

Please only spend 1 to 2 hours at most. The next interview stage will contain a discussion of your code including how it works and the design decisions you made.

## How to provide your answer

Your repository should contain a library for interacting with a REST API. It is highly desired that you provide tests, to prove that your solution works and is robust. 

Alongside your library, you will need some additional logic to take the API responses and convert them into the outputs specified in the scenarios below.

## Scenario 1

wi-Q is integrating with a fictitious company called 'Great Food Ltd'. 'Great Food Ltd' provide menu and item data from a REST API. Your job is to write some code to consume their API and parse the response into a readable format.

Using the API endpoints described below, write code that would be able to make a request to this API and collect the product data for the menu named `Takeaway`. Once the product data has been obtained, print it out in a list, containing the product id and name.

### Expected (sample) output

| ID | Name    |
| -- | ------- |
| 4  | Burger  |
| 5  | Chips   |
| 99 | Lasagna |

### API Endpoints

The available endpoints for the 'Great Food Ltd' API are as follows:
> ### /auth_token
> #### Arguments
> | Name          | Value              |
> | ------------- | ------------------ |
> | client_secret | 4j3g4gj304gj3      |
> | client_id     | 1337               |
> | grant_type    | client_credentials |
> #### Request Type
> `POST`
> #### Headers
> | Name         | Value                             |
> | -------------| --------------------------------- |
> | Content-Type | application/x-www-form-urlencoded |
> #### Response
> This has been provided in `responses/token.json`

> ### /menus
> #### Request Type
> `GET`
> #### Headers
> Authorization:
> | Name          | Value          |
> | ------------- | -------------- |
> | Authorization | Bearer {token} |
> #### Response
> This has been provided in `responses/menus.json`

> ### /menu/{menu_id}/products
> #### Request Type
> `GET`
> #### Headers
> Authorization:
> | Name          | Value          |
> | ------------- | -------------- |
> | Authorization | Bearer {token} |
> #### Response
> The list of products for the `Takeaway` menu has been provided in `responses/menu-products.json`

## Scenario 2

A customer has been in touch and advised you that product with id 84 in menu 7 has the wrong name. The product is currently named 'Chpis' but it should be named 'Chips'.

Using any of the API endpoints from Scenario 1 and the new method detailed below, write code to demonstrate this item being updated in the 'Great Food Ltd' API.

### Expected output

Proof that the API request has been successful.

> ### /menu/{menu_id}/product/{product_id}
> #### Arguments
> Product model as described in the `GET /menu/{menu_id}/products` response
> #### Request Type
> `PUT`
> #### Headers
> | Name          | Value          |
> | ------------- | -------------- |
> | Authorization | Bearer {token} |

## When you're done

Please zip up your solution and email it to eng-tests@wi-q.com.
