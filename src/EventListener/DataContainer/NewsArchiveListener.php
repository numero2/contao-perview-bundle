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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\NewsArchiveModel;
use Symfony\Contracts\Translation\TranslatorInterface;


class NewsArchiveListener {


    /**
     * @var Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;


    public function __construct( TranslatorInterface $translator ) {
        $this->translator = $translator;
    }


    /**
     * Adds the Perview configuration fields to the default palette
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_news_archive", target="config.onload")
     */
    public function modifyPalettes( DataContainer $dc ): void {

        PaletteManipulator::create()
            ->addLegend('perview_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField(['perview_enable'], 'perview_legend', PaletteManipulator::POSITION_APPEND )
            ->applyToPalette('default', $dc->table);
    }


    /**
     * Generates an array of channel options
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_news_archive", target="fields.perview_channel.options")
     */
    public function getChannelOptions( DataContainer $dc ): array {

        return [
            'own_website' => $this->translator->trans('tl_news_archive.channels.own_website', [], 'contao_default')
        ,   'freeoffering'=> $this->translator->trans('tl_news_archive.channels.freeoffering', [], 'contao_default')
        ];
    }


    /**
     * Generates an array of language options
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_news_archive", target="fields.perview_language.options")
     */
    public function getLanguageOptions( DataContainer $dc ): array {

        return [
            'de-DE' => 'German (Germany)'
        ,   'en-GB' => 'English (Great-Britain)'
        ,   'en-US' => 'English (United States)'
        ,   'nl-BE' => 'Dutch (Belgium)'
        ,   'nl-NL' => 'Dutch (Netherlands)'
        ,   'fr-FR' => 'French (France)'
        ,   'es-ES' => 'Spanish (Spain)'
        ,   'pt-PT' => 'Portuguese (Portugal)'
        ,   'it-IT' => 'Italian (Italy)'
        ,   'ro-RO' => 'Romanian'
        ];
    }
}
