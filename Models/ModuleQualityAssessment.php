<?php
/*
 * MikoPBX - free phone system for small business
 * Copyright © 2017-2023 Alexey Portnov and Nikolay Beketov
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\ModuleQualityAssessment\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

class ModuleQualityAssessment extends ModulesModelsBase
{
    public const TTS_YANDEX  = 'Yandex';
    public const TTS_TINKOFF = 'Tinkoff';
    public const TTS_NONE = '-';

    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     *
     * @Column(type="integer", default="0", nullable=true)
     */
    public $useTts;

    /**
     * TTS Engine
     * @Column(type="string", default="0", nullable=true)
     */
    public $ttsEngine;

    /**
     * Yandex API Key
     *
     * @Column(type="string", nullable=true)
     */
    public $yandexApiKey;

    /**
     * Yandex folder id
     *
     * @Column(type="string", nullable=true)
     */
    public $yandexFolderId;

    /**
     * Tinkoff api key
     *
     * @Column(type="string", nullable=true)
     */
    public $tinkoffApiKey;

    /**
     * Tinkoff secret key
     *
     * @Column(type="string", nullable=true)
     */
    public $tinkoffSecretKey;

    /**
     * @Column(type="string", nullable=true)
     */
    public $pressed1;
    /**
     * @Column(type="string", nullable=true)
     */
    public $pressed2;
    /**
     * @Column(type="string", nullable=true)
     */
    public $pressed3;
    /**
     * @Column(type="string", nullable=true)
     */
    public $pressed4;
    /**
     * @Column(type="string", nullable=true)
     */
    public $pressed5;

    /**
     * Returns dynamic relations between module models and common models
     * MikoPBX check it in ModelsBase after every call to keep data consistent
     *
     * There is example to describe the relation between Providers and ModuleQualityAssessment models
     *
     * It is important to duplicate the relation alias on message field after Models\ word
     *
     * @param $calledModelObject
     *
     * @return void
     */
    public static function getDynamicRelations(&$calledModelObject): void
    {
//        if (is_a($calledModelObject, Providers::class)) {
//            $calledModelObject->belongsTo(
//                'id',
//                ModuleQualityAssessment::class,
//                'dropdown_field',
//                [
//                    'alias'      => 'ModuleQualityAssessmentProvider',
//                    'foreignKey' => [
//                        'allowNulls' => 0,
//                        'message'    => 'Models\ModuleQualityAssessmentProvider',
//                        'action'     => Relation::ACTION_RESTRICT
//
//                    ],
//                ]
//            );
//        }
    }

    public function initialize(): void
    {
        $this->setSource('m_ModuleQualityAssessment');
        parent::initialize();
    }
}