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
 *
 * @method static mixed findFirstByNumber(array|string|int $parameters = null)
 */
class QuestionsList extends ModulesModelsBase
{
    public const ROLE_START     = 'ROLE_START';
    public const ROLE_END       = 'ROLE_END';
    public const ROLE_QUESTION  = '';

    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * Number for search i.e. 79065554343
     *
     * @Column(type="string", nullable=true)
     */
    public $textQuestions;

    /**
     * @Column(type="integer", nullable=true)
     */
    public ?string $soundFileId = '0';

    /**
     * Number for search i.e. 79065554343
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $role = '';

    /**
     * @Column(type="integer", nullable=true)
     */
    public ?string $useTts = '0';

    /**
     * @Column(type="integer", nullable=true)
     */
    public ?string $priority = '0';

    public function initialize(): void
    {
        $this->setSource('m_QuestionsList');
        parent::initialize();
        $this->useDynamicUpdate(true);
    }

    public function validation(): bool
    {
        return true;
    }
}