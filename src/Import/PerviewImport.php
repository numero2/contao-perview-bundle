<?php

/**
 * Perview Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PerviewBundle\Import;

use \Exception;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\Message;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class PerviewImport {


    /**
     * @var string
     */
    const ENDPOINT = "api-ats.perview.de";

    /**
     * @var int
     */
    public const STATUS_ERROR = 0;
    public const STATUS_NEW = 1;
    public const STATUS_UPDATE = 2;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var HttpClientInterface
     */
    private $client;


    public function __construct( Connection $connection, RequestStack $requestStack, ScopeMatcher $scopeMatcher, LoggerInterface $logger, TranslatorInterface $translator, HttpClientInterface $client ) {

        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->client = $client;
    }


    public function __invoke(): void {

        $id = null;
        $id = Input::get('id');

        // run import for current archive (while in the backend)
        if( $this->requestStack->getCurrentRequest() && $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest()) && $id ) {

            $archive = null;
            $archive = NewsArchiveModel::findOneById($id);

            if( $archive && $archive->perview_enable ) {

                $this->importAdvertisementsForArchive($archive, false);
                Controller::redirect(Controller::getReferer());

            } else {
                throw new Exception('News archive ID ' . $id . ' is not configured for use with Perview');
            }

        // import for all archives
        } else {

            $archive = null;
            $archive = NewsArchiveModel::findBy(["perview_enable!=''"],null);

            if( $archive ) {

                while( $archive->next() ) {

                    if( $archive->perview_enable ) {
                        $this->importAdvertisementsForArchive($archive->current());
                    }
                }
            }
        }
    }


    /**
     * Imports job advertisements for the given news archive
     *
     * @param Contao\NewsArchiveModel $archive
     * @param boolean $silent Indicates whether the import should trigger messages in backend
     */
    private function importAdvertisementsForArchive( NewsArchiveModel $archive, bool $silent=true ): void {

        $ads = null;
        $ads = $this->getAdvertisements( $archive->perview_channel, $archive->perview_user, $archive->perview_password, $archive->perview_language );

        $results = [
            self::STATUS_ERROR => 0
        ,   self::STATUS_NEW => 0
        ,   self::STATUS_UPDATE => 0
        ];

        if( $ads ) {

            // initially hide all job listings in current archive to make sure
            // deleted listings are not shown anymore
            $this->connection->prepare("UPDATE ".NewsModel::getTable()." SET published = '' WHERE perview_id != '0' AND published = '1' AND pid = :pid")
                ->execute(['pid'=> $archive->id]);

            foreach( $ads as $ad ) {

                $status = $this->importAdvertisement($ad, $archive);
                $results[(int)$status]++;
            }
        }

        // add message for backend
        if( !$silent ) {

            if( empty($ads) ) {

                Message::addError(
                    $this->translator->trans('ERR.general', [], 'contao_default')
                );

            } else {

                if( $results[self::STATUS_ERROR] !== 0 ) {

                    Message::addError(
                        $this->translator->trans('perview.msg.import_error', [], 'contao_default')
                    );
                }

                if( $results[self::STATUS_NEW] || $results[self::STATUS_UPDATE] ) {

                    Message::addInfo(sprintf(
                        $this->translator->trans('perview.msg.import_success', [], 'contao_default')
                    ,   $results[self::STATUS_NEW]
                    ,   $results[self::STATUS_UPDATE]
                    ));
                }
            }

        } else {

            if( empty($ads) ) {

                $this->logger->log(LogLevel::ERROR, 'Could not import job advertisements for news archive ID ' .$archive->id, ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

            } else {

                if( $results[self::STATUS_ERROR] !== 0 ) {

                    $this->logger->log(LogLevel::ERROR, 'Failed to import ' .$results[self::STATUS_ERROR]. ' job advertisements for news archive ID ' .$archive->id, ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
                }

                if( $results[self::STATUS_NEW] || $results[self::STATUS_UPDATE] ) {

                    $this->logger->log(LogLevel::INFO, 'Successfully imported job advertisements for news archive ID ' .$archive->id. ' (' .$results[self::STATUS_NEW]. ' new / ' .$results[self::STATUS_UPDATE]. ' updated)', ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
                }
            }
        }
    }


    /**
     * Imports the given job advertisement into the given news archive
     *
     * @param object $position
     * @param NewsArchiveModel $archive
     *
     * @return int|null
     */
    private function importAdvertisement( object $position, NewsArchiveModel $archive ): ?int {

        // find existing news...
        $news = null;
        $news = NewsModel::findOneBy(['pid=?','perview_id=?'],[$archive->id,$position->id]);

        //... or create a new one
        if( !$news ) {

            $news = new NewsModel();

            $news->pid = $archive->id;
            $news->perview_id = $position->id;
            $news->tstamp = time();
            $news->author = $archive->perview_default_author;
            $news->source = 'default';
            $news->published = false;
        }

        $isUpdate = (bool) $news->id;

        // set / update metadata
        $news->headline = $position->name->value;
        $news->alias = $news->perview_id.'-'.StringUtil::standardize($news->headline);
        $news->date = $news->time = strtotime($position->creationDate);
        $news->start = strtotime($position->publicationDate);
        $news->stop = strtotime($position->expirationDate);

        // set content
        if( !empty($position->description) ) {

            // make sure we have an id to work with
            if( !$news->id ) {
                $news->save();
            }

            $news->teaser = '<p>' . strip_tags(StringUtil::substrHtml($position->description->value,200)) . '…</p>';

            // find existing Content Element...
            $content = null;
            $content = ContentModel::findBy(['ptable=?','pid=?','type=?'], [NewsModel::getTable(), $news->id, 'text'], ['order' => 'sorting ASC']);

            // ... or create a new one
            if( !$content ) {

                $content = new ContentModel();
                $content->ptable = NewsModel::getTable();
                $content->pid = $news->id;
                $content->sorting = 128;
            }

            $content->tstamp = time();
            $content->type = 'text';

            $content->text = $position->description->value;

            $content->save();
        }

        $news->published = '1';

        // HOOK: add custom logic
        if( isset($GLOBALS['TL_HOOKS']['parsePerviewPosition']) && \is_array($GLOBALS['TL_HOOKS']['parsePerviewPosition']) ) {

            foreach( $GLOBALS['TL_HOOKS']['parsePerviewPosition'] as $callback ) {
                System::importStatic($callback[0])->{$callback[1]}($news,$position,$isUpdate);
            }
        }

        $news->save();

        return $isUpdate ? self::STATUS_UPDATE : self::STATUS_NEW;
    }


    /**
     * Returns a JSON containing the latest advertisements for the given configuration
     *
     * @param string $channel
     * @param string $user
     * @param string $password
     * @param string|null $language
     *
     * @return array|null
     */
    private function getAdvertisements( string $channel, string $user, string $password, ?string $language='en-GB' ): ?array {

        if( empty($channel) || empty($user) || empty($password) ) {
            return null;
        }

        $options = null;
        $options = new HttpOptions();

        $options->setHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'numero2/contao-perview-bundle',
        ]);

        $uri = 'https://' . self::ENDPOINT . '/' . urlencode($user) . '/' . urlencode($password) . '/job/' . $channel .'/JSON1_15' . '/' . $language;

        $response = null;
        $response = $this->client->request('GET', $uri, $options->toArray());

        try {

            if( $response->getStatusCode(false) !== 200 ) {
                $this->logger->log(LogLevel::ERROR, 'Could not request job advertisements from Perview ('.$uri.'), received status '.$response->getStatusCode(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
                return null;
            }

        } catch( Exception $e ) {

            $this->logger->log(LogLevel::ERROR, 'Could not request job advertisements from Perview ('.$uri.'). '.$e->getMessage(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
            return null;
        }

        $json = null;
        $json = json_decode($response->getContent(false));

        return $json??null;
    }
}
