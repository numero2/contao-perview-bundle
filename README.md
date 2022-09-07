Contao Perview Bundle
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-perview-bundle.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-perview-bundle) [![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg?style=flat-square)](http://www.gnu.org/licenses/lgpl-3.0)

About
--

Import job advertisements from [perview®](https://perview.de/) as news into Contao.

System requirements
--

* [Contao 4.13](https://github.com/contao/contao) (or newer)

Installation
--

* Install via Contao Manager or Composer (`composer require numero2/contao-perview-bundle`)
* Run a database update via the Contao-Installtool or using the [contao:migrate](https://docs.contao.org/dev/reference/commands/) command.

Hooks
--

By default the bundle only imports certain information from the perview® job advertisements. If you need more meta data you can import them on your own using the `parsePerviewPosition` hook:

```php
// src/EventListener/ParsePerviewPositionListener.php
namespace App\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\NewsModel;

/**
 * @Hook("parsePerviewPosition")
 */
class ParsePerviewPositionListener
{
    public function __invoke(NewsModel $news, object $position, bool $isUpdate): void
    {
        $news->something = $position->something;
    }
}
```