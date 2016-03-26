<?php
namespace EPICMC\BridgeBlacklist\task;

use EPICMC\BridgeBlacklist\BridgeBlacklist;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Utils;

class BlacklistCheckTask extends AsyncTask{
	
    protected $name;
    protected $ip;

    public function __construct($name, $ip, $api_url){
        $this->name = $name;
        $this->ip = $ip;
	$this->api_url = $api_url;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun(){
	$data = json_decode(Utils::getURL($this->api_url . '?player=' . $this->name . '&ip=' . $this->ip), true);
	$this->setResult($data);
    }

    public function onCompletion(Server $server){
        $plugin = $server->getPluginManager()->getPlugin("BridgeBlacklist");
        
        if($plugin instanceof BridgeBlacklist && $plugin->isEnabled()){
            $plugin->blacklistCheckComplete($this->name, $this->ip, $this->getResult());
        }
    }
}
