# LPFile

Application du principe de moindre privilège aux fonctions natives PHP `file_get_contents`, `file_put_contents`, `file` et `fopen`.

## Objectif

L'objectif est de limiter l'accès aux fonctions `file_get_contents`, `file_put_content`, `file` et `fopen` pour réduire les risques d'anomalies et de failles (par exemple [SSRF](https://owasp.org/Top10/fr/A10_2021-Server-Side_Request_Forgery_%28SSRF%29/))
en privilégiant l'utilisation de listes blanches.

Règles générales :
 * L'utilisation des `include_path` est bloquée.
 * Les chemins, extensions et noms de fichiers sont sensibles à la casse.

## Utilisation

### `file_get_contents`

```php
use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\FilePolicy;
use Aubes\LPFile\Policy\HttpPolicy;
use Aubes\LPFile\Policy\PolicyInterface;

// [...]

$LPFile = new LPFile();

// Par défaut, le mode est en lecture seule
$LPFile->addPolicy(new FilePolicy($basePath, 'txt')/*, PolicyInterface::MODE_READ*/);

// Il est possible d'ajouter plusieurs règles sur le même protocole
$LPFile->addPolicy(new FilePolicy($basePath, 'csv'));

// Il est possible de cumuler les modes
$LPFile->addPolicy(new FilePolicy($basePath, ['txt', 'csv']), PolicyInterface::MODE_READ | PolicyInterface::MODE_WRITE);

// Il est possible d'ajouter plusieurs protocoles
$LPFile->addPolicy(new HttpPolicy($secured, $host, $basePath));

try {
    $content = $LPFile->fileGetContents($filePath);
} catch (\RuntimeException $e) {
    // [...]
}
```

### `file_put_contents`

```php
use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\FilePolicy;
use Aubes\LPFile\Policy\PolicyInterface;

// [...]

$LPFile = new LPFile();

// Il faut utiliser le mode écriture
$LPFile->addPolicy(new FilePolicy($basePath, $extensions, PolicyInterface::MODE_WRITE));

try {
    $length = $LPFile->filePutContents($filePath, $data);
} catch (\RuntimeException $e) {
    // [...]
}
```

###`file`

Se comporte comme `file_get_content`.

```php
use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\FilePolicy;

// [...]

$LPFile = new LPFile();

$LPFile->addPolicy(new FilePolicy($basePath, 'txt'));

try {
    $content = $LPFile->file($filePath);
} catch (\RuntimeException $e) {
    // [...]
}
```

### `fopen`

```php
use Aubes\LPFile\LPFile;
use Aubes\LPFile\Policy\FilePolicy;
use Aubes\LPFile\Policy\PolicyInterface;

// [...]

$LPFile = new LPFile();
$LPFile->addPolicy(new FilePolicy($basePathRead, $extensions)/*, PolicyInterface::MODE_READ*/);
$LPFile->addPolicy(new FilePolicy($BasePathWrite, $extensions), PolicyInterface::MODE_WRITE);
$LPFile->addPolicy(new FilePolicy($BasePathBoth, $extensions), PolicyInterface::MODE_READ & PolicyInterface::MODE_WRITE);

try {
    $resourceRead = $LPFile->fopen($filePathRead, 'r');
    $resourceWrite = $LPFile->fopen($filePathWrite, 'w');
    $resourceBoth = $LPFile->fopen($filePathBoth, 'w+');
} catch (\RuntimeException $e) {
    // [...]
}
```

## "Policy" et Protocoles

Les règles actuellement disponibles sont :
 * `Aubes\LPFileGetContents\FilePolicy`: Pour le protocole `file://`
 * `Aubes\LPFileGetContents\FtpPolicy`: Pour les protocoles `ftp://` et `ftps://`
 * `Aubes\LPFileGetContents\HttpPolicy`: Pour les protocoles `http://` et `https://`
 * `Aubes\LPFileGetContents\HttpWildcardPolicy`: Pour les protocoles `http://` et `https://`

### FilePolicy

Applique les règles suivantes :
 * Limitation à un répertoire
 * Liste blanche d'extensions
 * Interdiction/Permission de l'utilisation du répertoire parent `..`: interdiction par défaut
 * Autorise la lecture et l'écriture

```php
public function __construct(string $baseDirectory, $extensions, bool $allowParentDirectory = false)
```
#### Exemples

```php
new Aubes\LPFile\FilePolicy('/absolute-path', 'txt');
new Aubes\LPFile\FilePolicy('./relative-path', ['txt', 'csv']);
```

### FtpPolicy

Applique les règles suivantes :
 * Ftp ou ftps
 * Liste blanche de domaines
 * Liste branche de chemins (commence par)
 * Interdiction/Permission d'utilisation des "dot-segments": interdiction par défaut
 * Autorise la lecture et l'écriture

```php
public function __construct(bool $secured, $hosts, $basePaths, $allowDotSegment = false)
```

#### Exemples

```php
new Aubes\LPFile\FtpPolicy(false, 'example.com', '/');
new Aubes\LPFile\FtpPolicy(true, ['example.com', 'example.fr'], ['/fr', '/en']);
```

### HttpPolicy

Applique les règles suivantes :
 * Http ou https
 * Liste blanche de domaines
 * Liste branche de chemins (commence par)
 * Liste blanche de "QueryString"
 * Interdiction/Permission d'utilisation des "dot-segments": interdiction par défaut
 * Interdiction/Permission du contexte `follow-redirection`: interdiction par défaut
 * Autorise uniquement la lecture

```php
public function __construct(bool $secured, $hosts, $basePaths, $queryString = [], $allowDotSegment = false, $followRedirect = false)
```

#### Exemples

```php
new Aubes\LPFile\HttpPolicy(false, 'example.com', '/');
new Aubes\LPFile\HttpPolicy(true, ['example.com', 'example.fr'], ['/fr', '/en'], ['lang']);
```

### HttpWildcardPolicy

Identique à "HttpPolicy" mais permet d'utiliser un wildcard dans les domaines et les chemins.

#### Exemples

```php
new Aubes\LPFile\HttpWildcardPolicy(true, '*.example.com', '/path/*');
```
