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

/**
 * Class QuestionsList
 *
 * @package Modules\ModulePhoneBook\Models
 * @Indexes(
 *     [name='changeTime', columns=['changeTime'], type='']
 * )
 * * @method static mixed findFirstByNumber(array|string|int $parameters = null)
 */
class QuestionResults extends ModulesModelsBase
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @Column(type="string", nullable=true)
     */
    public $quality;

    /**
     * @Column(type="string", nullable=true)
     */
    public $f_num;

    /**
     * @Column(type="string", nullable=true)
     */
    public $filename;

    /**
     * @Column(type="string", nullable=true)
     */
    public $linkedid;

    /**
     * @Column(type="string", nullable=true)
     */
    public $callerid;

    /**
     * @Column(type="integer", nullable=true)
     */
    public $changeTime;

    public function initialize(): void
    {
        $this->setSource('m_QuestionResults');
        parent::initialize();
        $this->useDynamicUpdate(true);
    }

    public function validation(): bool
    {
        return true;
    }
}