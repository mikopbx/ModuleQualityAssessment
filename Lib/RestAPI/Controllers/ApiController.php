<?php
/**
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 4 2020
 *
 */

namespace Modules\ModuleQualityAssessment\Lib\RestAPI\Controllers;

use MikoPBX\PBXCoreREST\Controllers\Modules\ModulesControllerBase;
use Modules\ModuleQualityAssessment\bin\ConnectorDB;

class ApiController extends ModulesControllerBase
{
    /**
     * curl -X GET http://127.0.0.1/pbxcore/api/module-quality-assessment/v1/results/{changeTime}
     * @param string $changeTime
     * @return void
     */
    public function getResultsAction(string $changeTime):void
    {
        $result = ConnectorDB::invoke('getResults', [$changeTime]);
        $this->echoResponse($result);
        $this->response->sendRaw();
    }

    /**
     * Вывод ответа сервера.
     * @param $result
     * @return void
     */
    private function echoResponse($result):void
    {
        try {
            echo json_encode($result, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
        }catch (\Exception $e){
            echo 'Error json encode: '. print_r($result, true);
        }
    }
}