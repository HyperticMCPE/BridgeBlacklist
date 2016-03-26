<?php

namespace EPICMC\BridgeBlacklist;

use EPICMC\BridgeBlacklist\task\BlacklistCheckTask;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class BridgeBlacklist extends PluginBase implements Listener{

    /** @var Player[] */
    	protected $pendingBlacklistCheck;
    	protected $api_url;
		
	 const NOT_BLACKLISTED = 0;
	 const PLAYER_BLACKLISTED = 1;
	 const IP_BLACKLISTED = 2;
    	 const BOTH_BLACKLISTED = 3;
	
  	public function onEnable(){
		@mkdir($this->getDataFolder());
        	$this->setting = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
		'api-url' => "yoururl/blacklist.php"
		]);
        	$this->getServer()->getPluginManager()->registerEvents($this, $this);
        	$this->pendingBlacklistCheck = [];
		$this->api_url = $this->setting->get('api-url');
	}
	
    	public function onPlayerPreLogin(PlayerPreLoginEvent $event){
	$player = $event->getPlayer();
        $this->pendingBlacklistCheck[$player->getName()] = $player;
        $task = new BlacklistCheckTask($player->getName(), $player->getAddress(), $this->api_url);
        $this->getServer()->getScheduler()->scheduleAsyncTask($task);
    	}

    	public function blacklistCheckComplete($name, $ip, $result){
        	if(isset($this->pendingBlacklistCheck[$name])){
            		$player = $this->pendingBlacklistCheck[$name];
            		$player_blacklist = $result['player'];
			$ip_blacklist = $result['ip'];
			
			unset($this->pendingBlacklistCheck[$player->getName()]);
			
			if($player_blacklist === true){
				if($ip_blacklist === false){
					$player->close('', "This username is blacklisted!");
				}else{
					$player->close('', "This username and IP address are blacklisted!");
				}
			}else {
				if($ip_blacklist === true){
					$player->close('', "This IP address is blacklisted!");
				}
			}
         	}else {
            		$this->getLogger()->warning(TextFormat::RED . "Extraneous request detected. Result ignored.");
         	}
     	} 	
	
	public function onMove(PlayerMoveEvent $event){
		$name = $event->getPlayer()->getName();	
		
		if(isset($this->pendingBlacklistCheck[$name])){
			$event->setCancelled();
		}
	}
	
	public function onPlayerChat(PlayerChatEvent $event){
		$pname = $event->getPlayer()->getName();
    	
    	if(isset($this->pendingBlacklistCheck[$pname])){
    		$event->setCancelled();
    	}
    	
    	$recipients = $event->getRecipients();
    	
    	foreach($recipients as $key => $recipient){
    		if($recipient instanceof Player){
    			if(isset($this->pendingBlacklistCheck[$recipient->getName()])){
    				unset($recipients[$key]);
    			}
    		}
    	}
    	
    	$event->setRecipients($recipients);
    	}
	
	public function onInteract(PlayerInteractEvent $event){
		$name = $event->getPlayer()->getName();
    		if(isset($this->pendingBlacklistCheck[$name])){
    			$event->setCancelled();
    		}
    	}
	
	public function onBreak(BlockBreakEvent $event){
		$name = $event->getPlayer()->getName();
		
    		if(isset($this->pendingBlacklistCheck[$name])){
    			$event->setCancelled();
    		}
	}
	
	public function onPlayerCommand(PlayerCommandPreprocessEvent $event){
        $msg = strtolower($event->getMessage());
		$p = $event->getPlayer();
		$cmd = explode(" ", $msg);
		if($cmd[0] === 'ban' || $cmd[0] === 'ban-ip'){
			$event->setCancelled();
			$p->sendMessage(TextFormat::RED . 'The ban commands can\' be used while BridgeBlacklist is enabled.');
		}
	}
	
	public function onServerCommand(ServerCommandEvent $event){
        	$msg = strtolower($event->getCommand());
		$console = $event->getSender();
		$cmd = explode(" ", $msg);
		
		if($cmd[0] === 'ban' || $cmd[0] === 'ban-ip'){
			$event->setCancelled();
			$console->sendMessage(TextFormat::RED . 'The ban commands can\' be used while BridgeBlacklist is enabled.');
		}
	}
}
