# Trust Token
Custom Laravel Authentication Driver to Authenticate against Backend Identity API

The custom authorization driver for Laravel provides secure and flexible access control via API. 

Based on the Laravel framework, the driver provides easy customization and integration of various API authentication methods. The package allows developers to create and manage unique tokens, for secure authentication and authorization requests.

With the use of this driver, developers get a tool that provides a high level of security and control when working with external APIs. 

## Create a new token

```php
/** @var TokenRepositoryInterface $tokenRepository */
$tokenRepository = app(TokenRepositoryInterface::class);
$userId = 1;

$token = $tokenRepository->createToken('myApp', $userId);
$accessToken = $token->plainTextToken;
```
