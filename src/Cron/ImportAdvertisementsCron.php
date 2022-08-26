<?php

/**
 * Perview Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PerviewBundle\Cron;

use Contao\CoreBundle\ServiceAnnotation\CronJob;
use numero2\PerviewBundle\Import\PerviewImport;


/**
 * @CronJob("daily")
 */
class ImportAdvertisementsCron {


    /**
     * @var PerviewImport
     */
    private $importer;


    public function __construct( PerviewImport $importer ) {

        $this->importer = $importer;
    }

    public function __invoke(): void {

        $this->importer->__invoke();
    }
}