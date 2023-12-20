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

namespace Modules\ModuleQualityAssessment\App\Forms;

use Modules\ModuleQualityAssessment\Models\ModuleQualityAssessment;
use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Numeric;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\TextArea;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;


class ModuleQualityAssessmentForm extends Form
{
    public function initialize($entity = null, $options = null) :void
    {
        $this->add(new Hidden('id', ['value' => $entity->id]));
        $this->add(new Text('yandexApiKey'));
        $this->add(new Text('yandexFolderId'));
        $this->add(new Text('tinkoffApiKey'));
        $this->add(new Text('tinkoffSecretKey'));

        $this->add(new Text('pressed1'));
        $this->add(new Text('pressed2'));
        $this->add(new Text('pressed3'));
        $this->add(new Text('pressed4'));
        $this->add(new Text('pressed5'));

        $useTts = ['value' => null];
        if ($entity->useTts === '1') {
            $useTts = ['checked' => 'checked', 'value' => null];
        }
        $this->add(new Check('useTts', $useTts));

        $arrLibraryType = [
            ModuleQualityAssessment::TTS_NONE => ModuleQualityAssessment::TTS_NONE,
            ModuleQualityAssessment::TTS_TINKOFF => ModuleQualityAssessment::TTS_TINKOFF,
            ModuleQualityAssessment::TTS_YANDEX => ModuleQualityAssessment::TTS_YANDEX,
        ];
        $ttsEngine = new Select(
            'ttsEngine', $arrLibraryType, [
                            'using'    => [
                                'id',
                                'name',
                            ],
                            'useEmpty' => true,
                            'value'    => $entity->ttsEngine,
                            'class'    => 'ui selection dropdown library-type-select',
                        ]
        );
        $this->add($ttsEngine);
    }
}