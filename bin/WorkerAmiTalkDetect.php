#!/usr/bin/php
<?php

namespace Modules\ModuleQualityAssessment\Lib;
require_once 'Globals.php';

use MikoPBX\Core\System\{BeanstalkClient, Storage, Util};
use MikoPBX\Core\Asterisk\AsteriskManager;
use MikoPBX\Core\Workers\WorkerBase;
use Pheanstalk\Contract\PheanstalkInterface;
use Throwable;

class WorkerAmiTalkDetect extends WorkerBase
{
    public    array $mixMonitorChannels = [];
    protected BeanstalkClient $client;
    protected AsteriskManager $am;

    /**
     * Установка фильтра
     */
    private function setFilter($event): void
    {
        $params = ['Operation' => 'Add', 'Filter' => 'Event: '.$event];
        $this->am->sendRequestTimeout('Filter', $params);
        $this->am->addEventHandler($event, [$this, "callback"]);
    }

    private function connectAmi():void
    {
        $this->am = Util::getAstManager();
        $this->setFilter('ChannelTalkingStart');
        $this->setFilter('ChannelTalkingStop');
    }

    /**
     * Старт работы листнера.
     *
     * @param $argv
     */
    public function start($argv): void
    {
        $this->client = new BeanstalkClient(self::class);
        $this->connectAmi();
        while ($this->needRestart === false) {
            $result = $this->am->waitUserEvent(true);
            if ($result === []) {
                // Нужен реконнект.
                usleep(100000);
                $this->connectAmi();
            }
        }
    }

    /**
     * Функция обработки оповещений.
     *
     * @param $parameters
     */
    public function callback($parameters): void
    {
        print_r($parameters);

        $linkedId = $parameters['Linkedid']??'';
        $channel = $parameters['Channel']??'';
        if('ChannelTalkingStop' === $parameters['Event'] ||
            ('ChannelTalkingStart' === $parameters['Event'] && !isset($this->mixMonitorChannels[$channel])) ){
            $time     = time();
            $this->StopMixMonitor($channel);
            $this->MixMonitor($channel, $linkedId.'_'.$time);
        }
    }

    /**
     * Инициирует запись разговора на канале.
     *
     * @param string    $channel
     * @param string   $file_name
     *
     * @return string
     */
    public function MixMonitor(string $channel, string $file_name): string
    {
        $file_name = str_replace('/', '_', $file_name);
        [$filePath, $fIn, $fOut, $options] = $this->setMonitorFilenameOptions($file_name);
        $result = $this->am->MixMonitor($channel, $filePath, $options);
        if(!isset($result['MixMonitorID'])){
            return '';
        }
        $this->mixMonitorChannels[$channel] = [
            'MixMonitorID' => $result['MixMonitorID'],
            'fileIn' => $fIn,
            'fileOut' => $fOut,
        ];
        return $filePath;
    }

    /**
     * Останавливает запись разговора на канале.
     * @param string $channel
     */
    public function StopMixMonitor(string $channel): void
    {
        if(!isset($this->mixMonitorChannels[$channel])){
            return;
        }
        $params = [
            'Channel' => $channel,
            'MixMonitorID' => $this->mixMonitorChannels[$channel]['MixMonitorID']
        ];
        $this->actionSendToBeanstalk($this->mixMonitorChannels[$channel]['fileOut'], $channel);

        unset($this->mixMonitorChannels[$channel]);
        $this->am->sendRequestTimeout('StopMixMonitor', $params);
        unset($this->mixMonitorChannels[$channel]);
    }

    /**
     * @param string|null $file_name
     * @return array
     */
    public function setMonitorFilenameOptions(string $file_name): array{
        $monitor_dir = Storage::getMonitorDir();
        $sub_dir = "translate/". date('Y/m/d/');
        $f = "{$monitor_dir}/{$sub_dir}{$file_name}";
        $options = "abSi(translator_monitor_id)r({$f}_in.wav)t({$f}_out.wav)";
        return ["$f.wav","{$f}_in.wav", "{$f}_out.wav", $options];
    }

    /**
     * Отправка данных на сервер очередей.
     *
     * @param string $result - данные в ормате json для отправки.
     */
    private function actionSendToBeanstalk(string $result, string $tube): void
    {
        $message_is_sent = false;
        $error = '';
        for ($i = 1; $i <= 10; $i++) {
            try {
                $result_send = (bool) $this->client->publish($result, $tube, PheanstalkInterface::DEFAULT_PRIORITY, 0, 30);
                if ($result_send === false) {
                    $this->client->reconnect();
                }
                $message_is_sent = ($result_send !== false);
                if ($message_is_sent === true) {
                    // Проверка
                    break;
                }
            } catch (Throwable $e) {
                $this->client = new BeanstalkClient(self::class);
                $error = $e->getMessage();
            }
        }

        if ($message_is_sent === false) {
            Util::sysLogMsg(__METHOD__, "Error send data to queue. " . $error, LOG_ERR);
        }
    }

}

// Start worker process
WorkerAmiTalkDetect::startWorker($argv ?? null);