<?php

/**
 * Perview Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PerviewBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\NewsArchiveModel;


class NewsListener {


    /**
     * Adds an operation to manually start the import
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_news", target="config.onload")
     */
    public function addImportOperation( DataContainer $dc ): void {

        if( !$dc->id ) {
            return;
        }

        $archive = null;
        $archive = NewsArchiveModel::findOneById($dc->id);

        if( $archive && $archive->perview_enable ) {

            array_insert($GLOBALS['TL_DCA']['tl_news']['list']['global_operations'], 1, [
                'perview_import' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_news']['perview_import']
                ,   'href'      => 'key=perview_import'
                ,   'icon'      => 'bundles/perview/backend/img/import.svg'
                ]
            ]);
        }
    }
}
