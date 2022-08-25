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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Doctrine\DBAL\Connection;
use numero2\PerviewBundle\JobApplicationLogModel;
use numero2\PerviewBundle\JobApplicationModel;
use numero2\PerviewBundle\JobOfferModel;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


/**
 * TODO
 *
 * @CronJob("daily")
 */
class ImportAdvertisementsCron {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;


    public function __construct( Connection $connection, LoggerInterface $logger ) {

        $this->connection = $connection;
        $this->logger = $logger;
    }


    public function __invoke(): void {

        // TODO
    }
}