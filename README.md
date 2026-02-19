# PHP AI Bundle for Symfony

This package wraps the `1tomany/php-ai` library into an easy to use Symfony bundle.

## Installation

Install the bundle using Composer:

```
composer require 1tomany/php-ai-bundle
```

## Configuration

Create a file named `php_ai.yaml` in `config/packages/` and add the following lines:

```yaml
php_ai:
    claude:
        api_key: '%env(CLAUDE_API_KEY)%'
        # http_client: 'http_client'
        # serializer: 'serializer'
    gemini:
        api_key: '%env(GEMINI_API_KEY)%'
        # http_client: 'http_client'
        # serializer: 'serializer'
    openai:
        api_key: '%env(OPENAI_API_KEY)%'
        # http_client: 'http_client'
        # serializer: 'serializer'

when@dev:
    php_ai:
        mock:
            enabled: true
```

By default, the `http_client` and `serializer` properties in the `gemini` and `openai` blocks use the `@http_client` and `@serializer` services defined in a standard Symfony application. You're free to use your own scoped HTTP Client or Serializer services.

If you wish to disable a vendor, simply delete the configuration block from the file. For example, if your application only uses Gemini, you would delete the `claude` and `openai` blocks, leaving you with:

```yaml
php_ai:
    gemini:
        api_key: '%env(GEMINI_API_KEY)%'
```

You'll also have to define the API keys in your `.env` file or by using the [Symfony Secrets](https://symfony.com/doc/current/configuration/secrets.html) component.

## Usage

Any action interface can be injected into a service. Because you can have multiple clients loaded in at once, the model passed into the request dictates what client to use. This makes it very easy to allow your users to select amongst any client supported by the core `1tomany/php-ai` library.

```php
<?php

namespace App\File\Action\Handler;

use OneToMany\AI\Contract\Action\File\UploadFileActionInterface;
use OneToMany\AI\Contract\Action\Query\ExecuteQueryActionInterface;

use function mime_content_type;

final readonly class QueryFileHandler
{
    public function __construct(
        private UploadFileActionInterface $uploadFileAction,
        private ExecuteQueryActionInterface $executeQueryAction,
    ) {
    }

    public function __invoke(string $path, string $prompt): void
    {
        $model = 'gemini-2.5-flash';
        
        /** 
         * @var non-empty-lowercase-string $format
         */
        $format = mime_content_type($path);
        
        // Upload the file to cache it with the model
        $uploadRequest = new UploadRequest($model)
            ->atPath($path)
            ->withFormat($format);
        
        $response = $this->uploadFileAction->act(...[
            'request' => $uploadRequest,
        ]);
        
        // $response instanceof \OneToMany\AI\Response\File\UploadResponse
        $fileUri = $response->getUri();
        
        // Compile and execute a query using the file
        $compileRequest = new CompileRequest($model)
            ->withText($prompt)
            ->withFileUri($fileUri, $format);
            
        $response = $this->executeQueryAction->act(...[
            'request' => $compileRequest,
        ]);
        
        // $response instanceof \OneToMany\AI\Response\Query\ExecuteResponse
        printf("Model output: %s\n", $response->getOutput());
    }
}
```

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License
