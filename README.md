# PHP code obfuscator
A simple and easy to use PHP code obfuscator that ~~doesn't~~ shouldn't break your project's code. Deploy your projects without being afraid of unwanted copies or changes by 3rd party developers without your consent.

___

### How it works
* The class copies all the files from the `source_dir` to your `destination_dir` and obfuscates the code.

```php
require_once 'Obfuscator.php';
$obfuscator = new Obfuscator();
$obfuscator->run();
```

### What does it obfuscate
* variables incl. arrays
* session-variables
* interfaces
* constants
* whitespace
* comments

### What doesn't it obfuscate
* file names (no file rename because of external calls like form submit or ajax)
* class names (because of file names and autoload)
* $_GET, $_POST, $_REQUEST (because of webservice calls)
* $_COOKIES (because of external influences like js)
* magic methods (because the code would break)
* array index/key (might be used from json decode)

### Dependecies
* PHP 5.x
* filesystem permission (read/write)

### Todos
- [ ] replace $GLOBAL
- [ ] replace exceptions
- [ ] source and destination path settings from the outside
- [ ] setting if strip whitespaces (yes/no)
- [ ] add custom file heading text
- [ ] tests with different platforms (Windows, Mac, Linux)

### Changes
You are free to contribute to this project.

:star: if you like this project