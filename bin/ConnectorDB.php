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

namespace Modules\ModuleQualityAssessment\bin;

use MikoPBX\Common\Models\SoundFiles;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\Workers\WorkerBase;
use MikoPBX\Core\System\BeanstalkClient;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModuleAutoDialer\Lib\Logger;
use Modules\ModuleQualityAssessment\Lib\YandexSynthesize;
use Modules\ModuleQualityAssessment\Models\ModuleQualityAssessment;
use Modules\ModuleQualityAssessment\Models\QuestionResults;
use Modules\ModuleQualityAssessment\Models\QuestionsList;

require_once 'Globals.php';

class ConnectorDB extends WorkerBase
{
    private Logger $logger;

    /**
     * Старт работы листнера.
     *
     * @param $params
     */
    public function start($params):void
    {
        $this->logger   = new Logger('ConnectorDB', 'ModuleQualityAssessment');
        $this->logger->writeInfo('Starting...');
        $beanstalk      = new BeanstalkClient(self::class);
        $beanstalk->subscribe(self::class, [$this, 'onEvents']);
        $beanstalk->subscribe($this->makePingTubeName(self::class), [$this, 'pingCallBack']);
        while ($this->needRestart === false) {
            $beanstalk->wait();
            $this->logger->rotate();
        }
    }

    /**
     * Получение запросов на идентификацию номера телефона.
     * @param $tube
     * @return void
     */
    public function onEvents($tube): void
    {
        try {
            $data = json_decode($tube->getBody(), true, 512, JSON_THROW_ON_ERROR);
        }catch (\Throwable $e){
            return;
        }
        if($data['action'] === 'invoke'){
            $res_data = [];
            $funcName = $data['function']??'';
            if(method_exists($this, $funcName)){
                if(count($data['args']) === 0){
                    $res_data = $this->$funcName();
                }else{
                    $res_data = $this->$funcName(...$data['args']??[]);
                }
                $res_data = serialize($res_data);
            }
            if(isset($data['need-ret'])){
                $tube->reply($res_data);
            }
        }
    }

    /**
     * Сохранение резульата опроса.
     * @param $result
     * @return void
     */
    public function saveQuality($result):void
    {
        $qResult = new QuestionResults();
        foreach ($result as $key => $value){
            $qResult->writeAttribute($key, $value);
        }
        $qResult->save();
    }

    /**
     * @param string $moduleDir
     * @return array
     */
    public function getQuestionFiles(string $moduleDir): array
    {
        $files = [];
        $settings = ModuleQualityAssessment::findFirst();
        if ($settings) {

            $ys = new YandexSynthesize("{$moduleDir}/db/tts", $settings->yandexApiKey);
            /** @var QuestionsList $question */
            $questions = QuestionsList::find(['order' => 'priority']);
            foreach ($questions as $question) {
                $filename = '';
                if ($settings->useTts === '1') {
                    $filename = $ys->makeSpeechFromText($question->textQuestions);
                } else {
                    $soundFile = SoundFiles::findFirstById($question->soundFileId);
                    if ($soundFile) {
                        $filename = $soundFile->path;
                    }
                }
                if (file_exists($filename)) {
                    $files[''.$question->role][] = Util::trimExtensionForFile($filename);
                }
            }
        }
        return $files;
    }

    /**
     * Выполнение меодов worker, запущенного в другом процессе.
     * @param string $function
     * @param array $args
     * @param bool $retVal
     * @return array|bool|mixed
     */
    public static function invoke(string $function, array $args = [], bool $retVal = true){
        $req = [
            'action'   => 'invoke',
            'function' => $function,
            'args'     => $args
        ];
        $client = new BeanstalkClient(self::class);
        try {
            if($retVal){
                $req['need-ret'] = true;
                $result = $client->request(json_encode($req, JSON_THROW_ON_ERROR), 20);
            }else{
                $client->publish(json_encode($req, JSON_THROW_ON_ERROR));
                return true;
            }
            $object = unserialize($result, ['allowed_classes' => [PBXApiResult::class]]);
        } catch (\Throwable $e) {
            $object = [];
        }
        return $object;
    }
}


if(isset($argv) && count($argv) !== 1){
    ConnectorDB::startWorker($argv??[]);
}
