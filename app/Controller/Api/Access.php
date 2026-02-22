<?php
/**
 * Created by PhpStorm.
 * User: exodus4d
 * Date: 24.05.2015
 * Time: 17:42
 */

namespace Exodus4D\Pathfinder\Controller\Api;

use Exodus4D\Pathfinder\Controller;
use Exodus4D\Pathfinder\Model\Pathfinder;

class Access extends Controller\AccessController {


    /**
     * search character/corporation or alliance by name
     * @param \Base $f3
     * @param $params
     * @throws \Exception
     */
    public function search($f3, $params){
        $accessData = [];

        if(
            array_key_exists('arg1', $params) &&
            array_key_exists('arg2', $params)
        ){
            $searchType = strtolower($params['arg1']);
            $searchToken = trim((string)$params['arg2']);
            $searchTokenLower = strtolower($searchToken);

            $accessModel = null;
            switch($searchType){
                case 'character':
                    $accessModel = Pathfinder\AbstractPathfinderModel::getNew('CharacterModel');
                    break;
                case 'corporation':
                    $accessModel = Pathfinder\AbstractPathfinderModel::getNew('CorporationModel');
                    break;
                case 'alliance':
                    $accessModel = Pathfinder\AbstractPathfinderModel::getNew('AllianceModel');
                    break;
            }

            if( is_object($accessModel) ){

                // find "active" entries that have their "sharing" option activated
                $accessList = $accessModel->find( [
                    "LOWER(name) LIKE :token AND " .
                    "active = 1 AND " .
                    "shared = 1 ",
                    ':token' => '%' . $searchTokenLower . '%'
                ]);

                if($accessList){
                    foreach($accessList as $accessObject){
                        $accessData[] = $accessObject->getData();
                    }
                }

                // Fallback: load alliance data from ESI if no local shared alliance matched.
                // This keeps existing behavior for character/corporation search unchanged.
                if(
                    $searchType === 'alliance' &&
                    empty($accessData) &&
                    $searchToken !== ''
                ){
                    $activeCharacter = $this->getCharacter();

                    $universeIds = $f3->ccpClient()->send('search', ['alliance'], $searchToken, $activeCharacter->_id, $activeCharacter->getAccessToken());
                    if(
                        !isset($universeIds['error']) &&
                        !empty($universeIds['alliance'])
                    ){
                        $allianceData = $f3->ccpClient()->send('getUniverseNames', $universeIds['alliance']);
                        foreach((array)$allianceData as $item){
                            if(
                                isset($item['category']) &&
                                $item['category'] === 'alliance'
                            ){
                                $accessData[] = [
                                    'id' => (int)$item['id'],
                                    'name' => (string)$item['name']
                                ];
                            }
                        }
                    }
                }
            }
        }

        echo json_encode($accessData);
    }

}
