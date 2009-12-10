<?php
require_once "server.php";

interface informDatasource
{
	// return a valie TFTP server or NULL to ignore the request
	public function TftpServerFromMacAddress(dhcpServer $server, $macAddress);
}

class chronosPacketProcessor implements dhcpPacketProcessor
{
	private $datasource;
	private $dryRun;
	
	public function __construct(informDatasource $datasource, $dryRun = true)
	{
		$this->datasource = $datasource;
		$this->dryRun = $dryRun;
	}
	
	function handleClientInform(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response)
	{
		$macAddress = substr($packet->getData('chaddr'),0,12);	// only look at the first 12 characters for the MAC address
		$bootServer = $this->datasource->TftpServerFromMacAddress($server,$macAddress);

		$server->verbosity && print("Mac Address: $macAddress\n");

		if ($bootServer)
		{
			$attributes = $storage->getAttributesForClient($packet->getMACAddress());

			$response->setData('op', dhcpPacket::BOOTREPLY);
			$response->setData('hops', $packet->getData('hops'));
			$response->setData('xid', $packet->getData('xid'));
			$response->setData('htype', $packet->getData('htype'));
			$response->setData('ciaddr', $packet->getData('ciaddr'));
			$response->setData('chaddr', $packet->getData('chaddr'));
			$response->setData('message_type', 'ack');
			$response->setData('server_id', $server->getSocketHost());
			$response->setData('tftp_server', $bootServer);

			$server->verbosity && print("Setting response option 66 to $bootServer\n");
			$server->verbosity  > 1 && print_r($response);
		}

		return !$this->dryRun && $bootServer !== NULL;
	}
}

class volitileIniFileTftpServerDatasource implements informDatasource
{
	private $iniFile;
	private $_lastCTime = NULL;
	private $_data = array();
	
	public function __construct($iniFile)
	{
		$this->iniFile = $iniFile;
	}

	public function TftpServerFromMacAddress(dhcpServer $server, $macAddress)
	{
		$data = $this->GetVolitileData();
		return array_key_exists(self::Normalize($macAddress),$data) ? $data[$macAddress] : NULL;
	}
	
	private function GetVolitileData()
	{
		// rebuild the data if the file has changed
		$ctime = filectime($this->iniFile);
		if ($this->_lastCTime === NULL || $this->_lastCTime !== $ctime)
		{
			$this->_lastCTime = $ctime;
			foreach(parse_ini_file($this->iniFile) as $macAddress => $bootServer)
			{
				$this->_data[self::Normalize($macAddress)] = $bootServer;
			}
		}
		
		return $this->_data;
	}

	private static function Normalize($macAddress)
	{
		return strtolower(str_replace(":","",$macAddress));
	}
}

class stringTftpServerDatasource implements informDatasource
{
	private $bootServer;
	
	public function __construct($bootServer)
	{
		$this->bootServer = $bootServer;
	}
	
	public function TftpServerFromMacAddress(dhcpServer $server, $macAddress)
	{
		return $this->bootServer;
	}
}

$options = getopt("v::u:n");
$debug = isset($options['v']) ? strlen($options['v'])+1 : 0;
$bootServerInfo = @$options['u'];		// url or ini file.  If url, it's used for ALL mac addresses passed in. If ini file, format is MAC_ADDRESS=BOOT_SERVER pairs - MAC_ADDRESSes will be normalized after they are read in
$dryRun = isset($options['n']);			// -n means we're not active (dry run)

if (!$bootServerInfo)
{
	print( "Usage: {$argv[0]} -u <url or ini file> [-v{v}] [-n]");
	exit;
}

$datasource = is_file($bootServerInfo) ? new volitileIniFileTftpServerDatasource($bootServerInfo) : new stringTftpServerDatasource($bootServerInfo);
$packetProcessor = new chronosPacketProcessor($datasource,$dryRun);

$debug && $dryRun && print("DRY RUN MODE\n");

$server = new dhcpServer($packetProcessor,$debug);
$server->listen();

?>