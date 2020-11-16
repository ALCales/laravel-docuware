## Installation

You can install the package via composer:

``` bash
composer require alcales/laravel-docuware
```

The package will automatically register itself.

Optionally you can publish the config-file:

```bash
php artisan vendor:publish --provider="ALCales\Docuware\DocuwareServiceProvider" --tag="config"
```

Here's what that looks like:

```php
return [

    'url_root' => env('DOCUWARE_URL'),

    'user' => env('DOCUWARE_USER'),

    'password' => env('DOCUWARE_PASSWORD'),

];
```

you can define the credentials in environment variables in the `.env` file
 ``` bash
DOCUWARE_URL=
DOCUWARE_USER=
DOCUWARE_PASSWORD=
 ```

## Usage

Initialize.

```php
$docuware = new Docuware($urlHost, $user, $password);

// If the credentials are not defined in the constructor, the ones established in the environment variables of the `.env` file will be obtained.
$docuware = new Docuware();
```

Principal funtions

```php
$documentListArray = $docuware->getDocumentsList('your_gabinet_id');

$downloadedSuccessfully = $docuware->downloadDocument('your_gabinet_id', 'your_document_id', 'your_storage_path');

$fields = [
    new DocuwareField('Name', 'Alejandro', 'String'),
    new DocuwareField('Ages', 28, 'Int'),
];

$updateSuccessfully = $docuware->updateIndexValues('your_gabinet_id', 'your_document_id', $fields);
```

