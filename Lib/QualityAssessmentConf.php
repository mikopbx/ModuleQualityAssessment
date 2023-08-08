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

namespace Modules\ModuleQualityAssessment\Lib;

use MikoPBX\Common\Models\SoundFiles;
use MikoPBX\Core\System\PBX;
use MikoPBX\Core\System\Processes;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\Workers\Cron\WorkerSafeScriptsCore;
use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModuleQualityAssessment\bin\ConnectorDB;
use Modules\ModuleQualityAssessment\Models\ModuleQualityAssessment;
use Modules\ModuleQualityAssessment\Models\QuestionsList;
use Modules\ModuleQualityAssessment\Lib\RestAPI\Controllers\ApiController;
use Modules\ModuleTemplate\Lib\TemplateConf;

class QualityAssessmentConf extends ConfigClass
{

    /**
     * Receive information about mikopbx main database changes
     *
     * @param $data
     */
    public function modelsEventChangeData($data): void
    {
        if (in_array($data['model'], [ModuleQualityAssessment::class, QuestionsList::class], true ) ){
            PBX::dialplanReload();
        }
    }

    /**
     * Returns module workers to start it at WorkerSafeScriptCore
     *
     * @return array
     */
    public function getModuleWorkers(): array
    {
        return [
            [
                'type'           => WorkerSafeScriptsCore::CHECK_BY_BEANSTALK,
                'worker'         => ConnectorDB::class,
            ],
        ];
    }

    /**
     * REST API модуля.
     * @return array[]
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            [ApiController::class, 'getResultsAction', '/pbxcore/api/module-quality-assessment/v1/results/{changeTime}', 'get', '/', false],
        ];
    }

    /**
     *  Process CoreAPI requests under root rights
     *
     * @param array $request
     *
     * @return PBXApiResult An object containing the result of the API call.
     */
    public function moduleRestAPICallback(array $request): PBXApiResult
    {
        $res    = new PBXApiResult();
        $res->processor = __METHOD__;
        $action = strtoupper($request['action']);
        switch ($action) {
            case 'CHECK':
                $templateMain = new QualityAssessmentMain();
                $res          = $templateMain->checkModuleWorkProperly();
                break;
            case 'RELOAD':
                $templateMain = new QualityAssessmentMain();
                $templateMain->startAllServices(true);
                $res->success = true;
                break;
            default:
                $res->success    = false;
                $res->messages[] = 'API action not found in moduleRestAPICallback ModuleQualityAssessment';
        }

        return $res;
    }

    /**
     * Prepares additional parameters for each incoming context
     * and incoming route after dial command in an extensions.conf file
     * @see https://docs.mikopbx.com/mikopbx-development/module-developement/module-class#generateincomingroutafterdialcontext
     *
     *
     * @param string $uniqId
     *
     * @return string
     */
    public function generateIncomingRoutAfterDialContext(string $uniqId): string
    {
        return 'same => n,Goto(quality-start,s,1)';
    }

    /**
     * Prepares additional parameters for each outgoing route context
     * after dial call in the extensions.conf file
     * @see https://docs.mikopbx.com/mikopbx-development/module-developement/module-class#generateoutroutafterdialcontext
     *
     * @param array $rout
     *
     * @return string
     */
    public function generateOutRoutContext(array $rout): string
    {
        return 'same => n,Set(DOPTIONS=${DOPTIONS}F(quality-start,s,1))'."\n\t";
    }

    /**
     * Generates additional contexts for the queue.
     *
     * @return string The generated extension contexts.
     */
    public function extensionGenContexts(): string
    {
        $files = $this->getQuestionFiles();

        $conf = PHP_EOL."[quality-start]".PHP_EOL;
        $conf .= 'exten => _.!,1,NoOp(--- Quality assessment ---)' . PHP_EOL."\t";
        $conf .= 'same => n,ExecIf($["${FROM_DID}x" != "x" && "${M_DIALSTATUS}" != "ANSWER"]?return)'.PHP_EOL."\t";
        foreach ($files[QuestionsList::ROLE_START]??[] as $file){
            $conf .= "same => n,Playback($file)" . PHP_EOL."\t";
        }
        $ch = 1;
        foreach ($files[QuestionsList::ROLE_QUESTION]??[] as $file){
            $conf .= "same => n,Set(filename_{$ch}={$file})".PHP_EOL."\t";
            $ch++;
        }
        $conf .= 'same => n,Set(f_num=0);'.PHP_EOL."\t";
        $conf .= 'same => n,Goto(ivr-quality,s,1)'.PHP_EOL.PHP_EOL;

        $conf .= "[ivr-quality]".PHP_EOL;
        $conf .= 'exten => s,1,NoOP( start ivr quality )' . PHP_EOL."\t";
        $conf .= 'same => n,Set(f_num=$[${f_num} + 1])' . PHP_EOL."\t";
        $conf .= 'same => n,Set(filename=${filename_${f_num}})' . PHP_EOL."\t";
        $conf .= 'same => n,GotoIf($["x${filename}" == "x"]?ivr-quality,bye,1);' . PHP_EOL."\t";
        $conf .= 'same => n,Background(${filename})' . PHP_EOL."\t";
        $conf .= 'same => n,WaitExten(10)' . PHP_EOL;
        $conf .= 'exten => _[1-5],1,NoOP( quality is ${EXTEN})' . PHP_EOL."\t";
        $conf .= "same => n,AGI({$this->moduleDir}/agi-bin/quality_agi.php)" . PHP_EOL."\t";
        $conf .= 'same => n,Goto(ivr-quality,s,1)' . PHP_EOL;
        $conf .= 'exten => _[06-9t],1,Goto(ivr-quality,s,1)' . PHP_EOL;
        $conf .= 'exten => bye,1,NoOp' . PHP_EOL."\t";
        foreach ($files[QuestionsList::ROLE_END]??[] as $file){
            $conf .= "same => n,Playback($file)" . PHP_EOL."\t";
        }
        $conf .= 'same => n,Hangup()' . PHP_EOL.PHP_EOL;
        return $conf;
    }

    /**
     * Process after enable action in web interface
     *
     * @return void
     */
    public function onAfterModuleEnable(): void
    {
        $this->startAllServices(true);
    }

    /**
     * Process after enable action in web interface
     *
     * @return void
     */
    public function onAfterModuleDisable(): void
    {
        $this->startAllServices(false);
    }

    /**
     * Возвращает список файлов вопросов.
     * @return array
     */
    private function getQuestionFiles(): array
    {
        $files = [];
        $questions = [];
        $settings = ModuleQualityAssessment::findFirst();
        if ($settings) {
            $questions = $settings->toArray();
            $questions['files'] = QuestionsList::find(['order' => 'priority'])->toArray();
        }
        unset($settings);
        if (!empty($questions)) {
            $ys = new YandexSynthesize("{$this->moduleDir}/db/tts", $questions['yandexApiKey']);
            /** @var QuestionsList $question */
            $questionsData = QuestionsList::find(['columns' => 'textQuestions,soundFileId,role','order' => 'priority']);
            foreach ($questionsData as $questionsData) {
                $filename = '';
                if ($questions['useTts'] === '1') {
                    $filename = $ys->makeSpeechFromText($questionsData->textQuestions);
                } else {
                    $soundFile = SoundFiles::findFirstById($questionsData->soundFileId);
                    if ($soundFile) {
                        $filename = $soundFile->path;
                    }
                }
                if (file_exists($filename)) {
                    $files['' . $questionsData->role][] = Util::trimExtensionForFile($filename);
                }
            }
        }
        return $files;
    }

    /**
     * Start or restart module workers
     *
     * @param bool $moduleEnabled
     */
    public function startAllServices(bool $moduleEnabled): void
    {
        $workersToRestart = $this->getModuleWorkers();
        if ( !$moduleEnabled) {
            foreach ($workersToRestart as $moduleWorker) {
                Processes::processPHPWorker($moduleWorker['worker'], 'stop', 'stop');
            }
        }else{
            foreach ($workersToRestart as $moduleWorker) {
                Processes::processPHPWorker($moduleWorker['worker'], 'start', 'start');
            }
        }
        PBX::dialplanReload();
    }
}