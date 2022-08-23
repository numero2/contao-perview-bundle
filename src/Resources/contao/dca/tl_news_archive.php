<?php

/**
 * Perview Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\BackendUser;
use Contao\DataContainer;


$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['__selector__'][] = 'perview_enable';
$GLOBALS['TL_DCA']['tl_news_archive']['subpalettes']['perview_enable'] = 'personio_xml_uri,perview_user,perview_password,perview_channel,perview_language,perview_default_author';


$GLOBALS['TL_DCA']['tl_news_archive']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_news_archive']['fields']
,   [
        'perview_enable' => [
            'exclude'      => true
        ,   'filter'       => true
        ,   'inputType'    => 'checkbox'
        ,   'eval'         => ['submitOnChange'=>true]
        ,   'sql'          => "char(1) NOT NULL default ''"
        ]
    ,   'perview_user' => [
            'exclude'      => true
        ,   'inputType'    => 'text'
        ,   'eval'         => ['mandatory'=>true, 'tl_class'=>'w50', 'decodeEntities'=>true]
        ,   'sql'          => "varchar(255) NOT NULL default ''"
        ]
    ,   'perview_password' => [
            'exclude'      => true
        ,   'inputType'    => 'text'
        ,   'eval'         => ['mandatory'=>true, 'tl_class'=>'w50', 'decodeEntities'=>true, 'hideInput'=>true]
        ,   'sql'          => "varchar(255) NOT NULL default ''"
        ]
    ,   'perview_channel' => [
            'exclude'      => true
        ,   'inputType'    => 'select'
        ,   'eval'         => ['mandatory'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "varchar(13) NOT NULL default ''"
        ]
    ,   'perview_language' => [
            'exclude'      => true
        ,   'inputType'    => 'select'
        ,   'eval'         => ['mandatory'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "varchar(5) NOT NULL default ''"
        ]
    ,   'perview_default_author' => [
            'exclude'      => true
        ,   'default'      => BackendUser::getInstance()->id
        ,   'flag'         => DataContainer::SORT_ASC
        ,   'inputType'    => 'select'
        ,   'foreignKey'   => 'tl_user.name'
        ,   'eval'         => ['mandatory'=>true, 'doNotCopy'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "int(10) unsigned NOT NULL default 0"
        ]
    ]
);