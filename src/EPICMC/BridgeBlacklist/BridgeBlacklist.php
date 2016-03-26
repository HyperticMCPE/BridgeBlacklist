<?php

namespace EPICMC\BridgeBlacklist;

use EPICMC\BridgeBlacklist\task\BanCheckTask;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class BridgeBlacklist extends PluginBase implements Listener{

    /** @var Player[] */
    protected $pendingBanCheck;
    protected $api_url;
		
	const NOT_BANNED = 0;
    const NAME_BANNED = 1;
    const IP_BANNED = 2;
    const BOTH_BANNED = 3;

    public function onEnable(){
		@mkdir($this->getDataFolder());
        $this->setting = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			'api-url' => "https://bridge.epicmc.me/blacklist"
		]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->pendingBanCheck = [];
		$this->api_url = $this->getConfig()->get('api-url');
	}
	
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
        $this->pendingBanCheck[$player->getName()] = $player;
        $task = new BanCheckTask($player->getName(), $player->getAddress(), $this->api_url);
        $this->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function banCheckComplete($name, $ip, $result){
        if(isset($this->pendingBanCheck[$name])){
            $player = $this->pendingBanCheck[$name];
            $name_ban = $result['player'];
			$ip_ban = $result['ip'];
			unset($this->pendingBanCheck[$player->getName()]);
			if($name_ban === true){
				if($ip_ban === false){
					$player->close('', "This username is banned!");
				}else{
					$player->close('', "This username and IP address are banned!");
				}
			}else{
				if($ip_ban === true){
					$player->close('', "This IP address is banned!");
				}
			}
        }else{
            $this->getLogger()->warning(TextFormat::RED . "Extraneous request detected. Result ignored.");
        }
    }
	
	public function onMove(PlayerMoveEvent $event){
		$name = $event->getPlayer()->getName();
		if(isset($this->pendingBanCheck[$name])){
			$event->setCancelled();
		}
	}
	
	public function onPlayerChat(PlayerChatEvent $event){
		$pname = $event->getPlayer()->getName();
    	if(isset($this->pendingBanCheck[$pname])){
    		$event->setCancelled();
    	}
    	$recipients = $event->getRecipients();
    	foreach($recipients as $key => $recipient){
    		if($recipient instanceof Player){
    			if(isset($this->pendingBanCheck[$recipient->getName()])){
    				unset($recipients[$key]);
    			}
    		}
    	}
    	$event->setRecipients($recipients);
    }
	
	public function onInteract(PlayerInteractEvent $event){
		$name = $event->getPlayer()->getName();
    	if(isset($this->pendingBanCheck[$name])){
    		$event->setCancelled();
    	}
    }
	
	public function onBreak(BlockBreakEvent $event){
		$name = $event->getPlayer()->getName();
    	if(isset($this->pendingBanCheck[$name])){
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
