#!/usr/bin/php
<?php
require_once('Globals.php');
use MikoPBX\Core\Asterisk\AGI;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\System\Processes;
use Modules\ModuleQualityAssessment\bin\ConnectorDB;
use Modules\ModuleQualityAssessment\Models\ModuleQualityAssessment;
use Modules\ModuleQualityAssessment\Lib\YandexSynthesize;use Phalcon\Di;

include_once dirname(__DIR__).'/vendor/keinos/mb_levenshtein/mb_levenshtein.php';

$settings = ConnectorDB::invoke('getSettings', [],true);
if(empty($settings) || $settings['ttsEngine'] === ModuleQualityAssessment::TTS_NONE){
    exit(1);
}

$agi = new AGI();
$agi->answer();
$agi->set_variable('AGIEXITONHANGUP', 'yes');
$agi->set_variable('AGISIGHUP', 'yes');
$agi->set_variable('__ENDCALLONANSWER', 'yes');

$tmpDir = '/tmp';
$di = Di::getDefault();
if ($di) {
    $dirsConfig = $di->getShared('config');
    $tmpDir     = $dirsConfig->path('core.tempDir') . '/stt';
    Util::mwMkdir($tmpDir, true);
}

$fName = $tmpDir.'/data-'.microtime(true).'.wav';
$channel        = $agi->request['agi_callerid'];
$linkedId       = $agi->get_variable('IMPORT('.$channel.',CHANNEL(linkedid))', true);
$backFilename   = $agi->get_variable('IMPORT('.$channel.',f_num)', true);

$agi->verbose('src.channel: '.$agi->request['agi_callerid'] . ", f_num: ".$backFilename. ', moduleDir: '.dirname(__DIR__), 0);
$agi->exec('MixMonitor', $fName);
try {
    while ($agi->exec('WaitForSilence', '1000')) {
        $newFilename = $agi->get_variable('IMPORT('.$channel.',f_num)', true);
        if($newFilename !== $backFilename){
            // Идет обработка другого вопроса или канал был завершен.
            $agi->verbose('END... src.channel: '.$agi->request['agi_callerid'] . ", new_f_num: ".$newFilename, 0);
            break;
        }
        $agi->exec('StopMixMonitor', '');
        $oldFilename = $fName;
        $fName = $tmpDir."/data-$linkedId-".microtime(true).'.wav';
        $agi->exec('MixMonitor', $fName);

        $soxPath = Util::which('soxi');
        if( Processes::mwExec("$soxPath $fName | grep '00:00:01.00'") ===0 ){
            unlink($fName);
        }else{
            $agi->verbose('src.channel: '.$agi->request['agi_callerid'] . ", new_f_num: ".$newFilename, 0);
            $text = '';
            if($settings['ttsEngine'] === ModuleQualityAssessment::TTS_TINKOFF){
                $commands = [
                        'export VOICEKIT_API_KEY="'.$settings['tinkoffApiKey'].'"',
                        'export VOICEKIT_SECRET_KEY="'.$settings['tinkoffSecretKey'].'"',
                        dirname(__DIR__)."/bin/recognize -e LINEAR16 -r 8000 -c 1 -i $oldFilename",
                ];

                $srcText  = trim(shell_exec(implode(';', $commands)));
                $textData = json_decode($srcText, true);
                foreach ($textData['results'] as $data){
                    $text.= trim($data['alternatives'][0]['transcript'??'']). " ";
                }
            }elseif ($settings['ttsEngine'] === ModuleQualityAssessment::TTS_YANDEX){
                $folderId       = $settings['yandexFolderId'];
                $ya = new YandexSynthesize($tmpDir, $settings['yandexApiKey'], $folderId);
                $text = mb_strtolower($ya->getTextFromSpeech($oldFilename));
            }
            $agi->verbose('Result TTS: '.$text, 0);
            // Чистим знаки пунктуации
            $text = preg_replace('/[[:punct:]]/u', ' ', $text);
            unlink($oldFilename);
            $costs = [
                $settings['pressed5'] => 5,
                $settings['pressed4'] => 4,
                $settings['pressed3'] => 3,
                $settings['pressed2'] => 2,
                $settings['pressed1'] => 1,
            ];
            foreach (explode(' ', $text) as $word){
                foreach ($costs as $costArr => $value){
                    foreach (explode(' ', $costArr) as $cost) {
                        $sim = mb_levenshtein($word, $cost);
                        if($sim <= 1){
                            $agi->verbose("Get cost: $cost, val:$value".PHP_EOL, 0);
                            $am = Util::getAstManager('off');
                            $am->Redirect($channel, '', $value, 'ivr-quality', '1');
                            $agi->hangup();
                            exit(0);
                        }
                    }
                }
            }
        }
    }
} catch (Exception $err) {
    $agi->verbose(implode(' ', $err->getTrace()), 0);
}