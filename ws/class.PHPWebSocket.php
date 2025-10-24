<?php

/*
	Based on PHP WebSocket Server 0.2
	 - http://code.google.com/p/php-websocket-server/
	 - http://code.google.com/p/php-websocket-server/wiki/Scripting

	WebSocket Protocol 07
	 - http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-07
	 - Supported by Firefox 6 (30/08/2011)

	Whilst a big effort is made to follow the protocol documentation, the current script version may unknowingly differ.
	Please report any bugs you may find, all feedback and questions are welcome!
*/


class PHPWebSocket {
	// maximum amount of clients that can be connected at one time
	const WS_MAX_CLIENTS = 100;

	// maximum amount of clients that can be connected at one time on the same IP v4 address
	const WS_MAX_CLIENTS_PER_IP = 100;

	// amount of seconds a client has to send data to the server, before a ping request is sent to the client,
	// if the client has not completed the opening handshake, the ping request is skipped and the client connection is closed
	const WS_TIMEOUT_RECV = 60;

	// amount of seconds a client has to reply to a ping request, before the client connection is closed
	const WS_TIMEOUT_PONG = 60;

	// the maximum length, in bytes, of a frame's payload data (a message consists of 1 or more frames), this is also internally limited to 2,147,479,538
	const WS_MAX_FRAME_PAYLOAD_RECV = 100000;

	// the maximum length, in bytes, of a message's payload data, this is also internally limited to 2,147,483,647
	const WS_MAX_MESSAGE_PAYLOAD_RECV = 500000;




	// internal
	const WS_FIN =  128;
	const WS_MASK = 128;

	const WS_OPCODE_CONTINUATION = 0;
	const WS_OPCODE_TEXT =         1;
	const WS_OPCODE_BINARY =       2;
	const WS_OPCODE_CLOSE =        8;
	const WS_OPCODE_PING =         9;
	const WS_OPCODE_PONG =         10;

	const WS_PAYLOAD_LENGTH_16 = 126;
	const WS_PAYLOAD_LENGTH_63 = 127;

	const WS_READY_STATE_CONNECTING = 0;
	const WS_READY_STATE_OPEN =       1;
	const WS_READY_STATE_CLOSING =    2;
	const WS_READY_STATE_CLOSED =     3;

	const WS_STATUS_NORMAL_CLOSE =             1000;
	const WS_STATUS_GONE_AWAY =                1001;
	const WS_STATUS_PROTOCOL_ERROR =           1002;
	const WS_STATUS_UNSUPPORTED_MESSAGE_TYPE = 1003;
	const WS_STATUS_MESSAGE_TOO_BIG =          1004;

	const WS_STATUS_TIMEOUT = 3000;

	// global vars
	public $wsClients       = array();
	public $wsRead          = array();
	public $wsClientCount   = 0;
	public $wsClientIPCount = array();
	public $wsOnEvents      = array();
        
        
// ------------------------------------------------------ mh additional vars ---

  public $reconnectQueue = array();
  public $suspendedClients = array();
  public $adminClients = array();
  private $logFile;

// ------------------------------------------------- mh additional functions ---
        
  function getSuspendedPtr($clientId) {
    $scPtr=0;
    foreach ($this->suspendedClients as $sc) {
      if ($sc['clientId']==$clientId) {return $scPtr;}
      ++$scPtr;
    }
    return -1;
  }

  public function removeClientFromSuspendedList($oldClientId) {
    $scPtr=$this->getSuspendedPtr($oldClientId);
    if ($scPtr > -1) {
      unset ($this->suspendedClients[$scPtr]);
      $this->suspendedClients=array_values($this->suspendedClients);            
    }
  }

  public function addClientToSuspendedList($clientId, $jType, $jNo, $npClientId, $pClientId, $uid) {
    $scDef=array('clientId'=>$clientId,'jType'=>$jType,'jNo'=>$jNo,'npClientId'=>$npClientId,'pClientId'=>$pClientId,'uid'=>$uid);
    array_push($this->suspendedClients, $scDef);
  }

  function isClientSuspended($clientId) {
    foreach ($this->suspendedClients as $sC) {
      if ($sC['clientId']==$clientId) {return true;} 
    }
    return false;
  }

  function addMessageToReconnectQueue($clientId, $message) {
    $qmDef=array('clientId'=>$clientId,'message'=>$message);
    array_push($this->reconnectQueue,$qmDef);
  }

  function ProcessUnsentMessages($oldClientId, $clientId) {
    $tempArray=array();
    foreach($this->reconnectQueue as $qmDef) {
      if ($qmDef['clientId']==$oldClientId) {
        $qmDef['clientId']=$clientId;
        $this->wsSend($clientId, $qmDef['message']);
      }
      else {
        array_push($tempArray,$qmDef); // keep for later
      }
    }
    $this->reconnectQueue=array_values($tempArray);
  }
  
  function uniqueAdmin($clientId) {
    foreach ($this->adminClients as $ac) {
      if ($ac['clientId'] == $clientId) { return false; }
    }
    return true;
  }
  
  function isStep1Client($clientId) {
    foreach ($this->adminClients as $ac) {
      if ($ac['clientId'] == $clientId) { return true; }
    }
    return false;
  }
  
  public function addAdminClient($clientId) {
    if ($this->uniqueAdmin($clientId)) {
      array_push($this->adminClients, array('clientId' => $clientId));
    }
  } 
        
        

	/*
		$this->wsClients[ integer clientId ] = array(
			0 => resource  Socket,                            // client socket
			1 => string    MessageBuffer,                     // a blank string when there's no incoming frames
			2 => integer   ReadyState,                        // between 0 and 3
			3 => integer   LastRecvTime,                      // set to time() when the client is added
			4 => int/false PingSentTime,                      // false when the server is not waiting for a pong
			5 => int/false CloseStatus,                       // close status that wsOnClose() will be called with
			6 => integer   IPv4,                              // client's IP stored as a signed long, retrieved from ip2long()
			7 => int/false FramePayloadDataLength,            // length of a frame's payload data, reset to false when all frame data has been read (cannot reset to 0, to allow reading of mask key)
			8 => integer   FrameBytesRead,                    // amount of bytes read for a frame, reset to 0 when all frame data has been read
			9 => string    FrameBuffer,                       // joined onto end as a frame's data comes in, reset to blank string when all frame data has been read
			10 => integer  MessageOpcode,                     // stored by the first frame for fragmented messages, default value is 0
			11 => integer  MessageBufferLength                // the payload data length of MessageBuffer
		)

		$wsRead[ integer clientId ] = resource Socket         // this one-dimensional array is used for socket_select()
															  // $wsRead[ 0 ] is the socket listening for incoming client connections

		$wsClientCount = integer ClientCount                  // amount of clients currently connected

		$wsClientIPCount[ integer IP ] = integer ClientCount  // amount of clients connected per IP v4 address
	*/

	// server state functions
	function wsStartServer($host, $port, $logName = NULL) {
		if (isset($this->wsRead[0])) return false;

		if (!$this->wsRead[0] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
			return false;
		}
		if (!socket_set_option($this->wsRead[0], SOL_SOCKET, SO_REUSEADDR, 1)) {
			socket_close($this->wsRead[0]);
			return false;
		}
		if (!socket_bind($this->wsRead[0], $host, $port)) {
			socket_close($this->wsRead[0]);
			return false;
		}
		if (!socket_listen($this->wsRead[0], 10)) {
			socket_close($this->wsRead[0]);
			return false;
		}

		$write = array();
		$except = array();

		$nextPingCheck = time() + 1;
    echo "starting server: next check at $nextPingCheck";
    date_default_timezone_set('GMT');
    
    if ($logName !=NULL) {
      $this->logFile = fopen("logs/".$logName.".txt",'wt');      
    }
    else {
      $date = date('Ymd-h-i-s', time());    
      $this->logFile = fopen("logs/listenerLog".$date.".txt",'wt');
    }
    
		while (isset($this->wsRead[0])) {
			$changed = $this->wsRead;
			$result = socket_select($changed, $write, $except, 1);

			if ($result === false) {
				socket_close($this->wsRead[0]);
				return false;
			}
			elseif ($result > 0) {
				foreach ($changed as $clientId => $socket) {
					if ($clientId != 0) {
						// client socket changed
						$buffer = '';
						$bytes = @socket_recv($socket, $buffer, 4096, 0);

						if ($bytes === false) {
							// error on recv, remove client socket (will check to send close frame)
							$this->wsSendClientClose($clientId, self::WS_STATUS_PROTOCOL_ERROR);
						}
						elseif ($bytes > 0) {
							// process handshake or frame(s)
							if (!$this->wsProcessClient($clientId, $buffer, $bytes)) {
								$this->wsSendClientClose($clientId, self::WS_STATUS_PROTOCOL_ERROR);
							}
						}
						else {
							// 0 bytes received from client, meaning the client closed the TCP connection
							$this->wsRemoveClient($clientId);
						}
					}
					else {
						// listen socket changed
						$client = socket_accept($this->wsRead[0]);
						if ($client !== false) {
							// fetch client IP as integer
							$clientIP = '';
							$result = socket_getpeername($client, $clientIP);
							$clientIP = ip2long($clientIP);

							if ($result !== false && $this->wsClientCount < self::WS_MAX_CLIENTS && (!isset($this->wsClientIPCount[$clientIP]) || $this->wsClientIPCount[$clientIP] < self::WS_MAX_CLIENTS_PER_IP)) {
								$this->wsAddClient($client, $clientIP);
							}
							else {
								socket_close($client);
							}
						}
					}
				}
			}

			if (time() >= $nextPingCheck) {
				$this->wsCheckIdleClients();
				$nextPingCheck = time() + 1;
			}
		}

		return true; // returned when wsStopServer() is called
	}
	function wsStopServer() {
		// check if server is not running
		if (!isset($this->wsRead[0])) return false;

		// close all client connections
		foreach ($this->wsClients as $clientId => $client) {
			// if the client's opening handshake is complete, tell the client the server is 'going away'
			if ($client[2] != self::WS_READY_STATE_CONNECTING) {
				$this->wsSendClientClose($clientId, self::WS_STATUS_GONE_AWAY);
			}
			socket_close($client[0]);
		}

		// close the socket which listens for incoming clients
		socket_close($this->wsRead[0]);

		// reset variables
		$this->wsRead          = array();
		$this->wsClients       = array();
		$this->wsClientCount   = 0;
		$this->wsClientIPCount = array();

    
    fclose($this->logFile);
		return true;
	}

	// client timeout functions
	function wsCheckIdleClients() {
		$time = time();
		foreach ($this->wsClients as $clientId => $client) {
			if ($client[2] != self::WS_READY_STATE_CLOSED) {
				// client ready state is not closed
				if ($client[4] !== false) {
          $this->log('msg', "pong check<:>$clientId");
					// ping request has already been sent to client, pending a pong reply
					if ($time >= $client[4] + self::WS_TIMEOUT_PONG) {
						// client didn't respond to the server's ping request in self::WS_TIMEOUT_PONG seconds            
            $this->log('msg', "time out occurred<:>$clientId");
            // don't close an admin client - only step1 ppt
            if ($this->isStep1Client($clientId)) {
              $this->log('msg', "step1 ppt time out message<:>$clientId");
              $this->wsSendClientClose($clientId, self::WS_STATUS_TIMEOUT);
              $this->wsRemoveClient($clientId);
            }
					}
				}
        else {
          $target = $client[3] + self::WS_TIMEOUT_RECV;
          //$this->log('msg', "ping check<:>$clientId<:>$time<:>$client[3]<:>$target");
          if ($time >= $client[3] + self::WS_TIMEOUT_RECV) {
            //$this->log('msg', "send ping check?<:>$clientId");
            // last data was received >= self::WS_TIMEOUT_RECV seconds ago
            if ($client[2] != self::WS_READY_STATE_CONNECTING) {
              // client ready state is open or closing
              $this->wsClients[$clientId][4] = time();
              $this->wsSendClientMessage($clientId, self::WS_OPCODE_PING, '');
            }
            else {
              // client ready state is connecting
              $this->wsRemoveClient($clientId);
            }
          }
        }
			}
		}
	}

	// client existence functions
	function wsAddClient($socket, $clientIP) {
		// increase amount of clients connected
		$this->wsClientCount++;

		// increase amount of clients connected on this client's IP
		if (isset($this->wsClientIPCount[$clientIP])) {
			$this->wsClientIPCount[$clientIP]++;
		}
		else {
			$this->wsClientIPCount[$clientIP] = 1;
		}

		// fetch next client ID
		$clientId = $this->wsGetNextclientId();

		// store initial client data
		$this->wsClients[$clientId] = array($socket, '', self::WS_READY_STATE_CONNECTING, time(), false, 0, $clientIP, false, 0, '', 0, 0);

		// store socket - used for socket_select()
		$this->wsRead[$clientId] = $socket;
	}
	function wsRemoveClient($clientId) {
		// fetch close status (which could be false), and call wsOnClose
		$closeStatus = $this->wsClients[$clientId][5];
		if ( array_key_exists('close', $this->wsOnEvents) )
			foreach ( $this->wsOnEvents['close'] as $func )
				$func($clientId, $closeStatus);

		// close socket
		$socket = $this->wsClients[$clientId][0];
		socket_close($socket);

		// decrease amount of clients connected on this client's IP
		$clientIP = $this->wsClients[$clientId][6];
		if ($this->wsClientIPCount[$clientIP] > 1) {
			$this->wsClientIPCount[$clientIP]--;
		}
		else {
			unset($this->wsClientIPCount[$clientIP]);
		}

		// decrease amount of clients connected
		$this->wsClientCount--;

		// remove socket and client data from arrays
		unset($this->wsRead[$clientId], $this->wsClients[$clientId]);
	}

	// client data functions
	function wsGetNextclientId() {
		$i = 1; // starts at 1 because 0 is the listen socket
		while (isset($this->wsRead[$i])) $i++;
		return $i;
	}
	function wsGetClientSocket($clientId) {
		return $this->wsClients[$clientId][0];
	}

	// client read functions
	function wsProcessClient($clientId, &$buffer, $bufferLength) {
		if ($this->wsClients[$clientId][2] == self::WS_READY_STATE_OPEN) {
			// handshake completed
			$result = $this->wsBuildClientFrame($clientId, $buffer, $bufferLength);
		}
		elseif ($this->wsClients[$clientId][2] == self::WS_READY_STATE_CONNECTING) {
			// handshake not completed
			$result = $this->wsProcessClientHandshake($clientId, $buffer);
			if ($result) {
				$this->wsClients[$clientId][2] = self::WS_READY_STATE_OPEN;

				if ( array_key_exists('open', $this->wsOnEvents) )
					foreach ( $this->wsOnEvents['open'] as $func )
						$func($clientId);
			}
		}
		else {
			// ready state is set to closed
			$result = false;
		}

		return $result;
	}
	function wsBuildClientFrame($clientId, &$buffer, $bufferLength) {
		// increase number of bytes read for the frame, and join buffer onto end of the frame buffer
		$this->wsClients[$clientId][8] += $bufferLength;
		$this->wsClients[$clientId][9] .= $buffer;

		// check if the length of the frame's payload data has been fetched, if not then attempt to fetch it from the frame buffer
		if ($this->wsClients[$clientId][7] !== false || $this->wsCheckSizeClientFrame($clientId) == true) {
			// work out the header length of the frame
			$headerLength = ($this->wsClients[$clientId][7] <= 125 ? 0 : ($this->wsClients[$clientId][7] <= 65535 ? 2 : 8)) + 6;

			// check if all bytes have been received for the frame
			$frameLength = $this->wsClients[$clientId][7] + $headerLength;
			if ($this->wsClients[$clientId][8] >= $frameLength) {
				// check if too many bytes have been read for the frame (they are part of the next frame)
				$nextFrameBytesLength = $this->wsClients[$clientId][8] - $frameLength;
				if ($nextFrameBytesLength > 0) {
					$this->wsClients[$clientId][8] -= $nextFrameBytesLength;
					$nextFrameBytes = substr($this->wsClients[$clientId][9], $frameLength);
					$this->wsClients[$clientId][9] = substr($this->wsClients[$clientId][9], 0, $frameLength);
				}

				// process the frame
				$result = $this->wsProcessClientFrame($clientId);

				// check if the client wasn't removed, then reset frame data
				if (isset($this->wsClients[$clientId])) {
					$this->wsClients[$clientId][7] = false;
					$this->wsClients[$clientId][8] = 0;
					$this->wsClients[$clientId][9] = '';
				}

				// if there's no extra bytes for the next frame, or processing the frame failed, return the result of processing the frame
				if ($nextFrameBytesLength <= 0 || !$result) return $result;

				// build the next frame with the extra bytes
				return $this->wsBuildClientFrame($clientId, $nextFrameBytes, $nextFrameBytesLength);
			}
		}

		return true;
	}
	function wsCheckSizeClientFrame($clientId) {
		// check if at least 2 bytes have been stored in the frame buffer
		if ($this->wsClients[$clientId][8] > 1) {
			// fetch payload length in byte 2, max will be 127
			$payloadLength = ord(substr($this->wsClients[$clientId][9], 1, 1)) & 127;

			if ($payloadLength <= 125) {
				// actual payload length is <= 125
				$this->wsClients[$clientId][7] = $payloadLength;
			}
			elseif ($payloadLength == 126) {
				// actual payload length is <= 65,535
				if (substr($this->wsClients[$clientId][9], 3, 1) !== false) {
					// at least another 2 bytes are set
					$payloadLengthExtended = substr($this->wsClients[$clientId][9], 2, 2);
					$array = unpack('na', $payloadLengthExtended);
					$this->wsClients[$clientId][7] = $array['a'];
				}
			}
			else {
				// actual payload length is > 65,535
				if (substr($this->wsClients[$clientId][9], 9, 1) !== false) {
					// at least another 8 bytes are set
					$payloadLengthExtended = substr($this->wsClients[$clientId][9], 2, 8);

					// check if the frame's payload data length exceeds 2,147,483,647 (31 bits)
					// the maximum integer in PHP is "usually" this number. More info: http://php.net/manual/en/language.types.integer.php
					$payloadLengthExtended32_1 = substr($payloadLengthExtended, 0, 4);
					$array = unpack('Na', $payloadLengthExtended32_1);
					if ($array['a'] != 0 || ord(substr($payloadLengthExtended, 4, 1)) & 128) {
						$this->wsSendClientClose($clientId, self::WS_STATUS_MESSAGE_TOO_BIG);
						return false;
					}

					// fetch length as 32 bit unsigned integer, not as 64 bit
					$payloadLengthExtended32_2 = substr($payloadLengthExtended, 4, 4);
					$array = unpack('Na', $payloadLengthExtended32_2);

					// check if the payload data length exceeds 2,147,479,538 (2,147,483,647 - 14 - 4095)
					// 14 for header size, 4095 for last recv() next frame bytes
					if ($array['a'] > 2147479538) {
						$this->wsSendClientClose($clientId, self::WS_STATUS_MESSAGE_TOO_BIG);
						return false;
					}

					// store frame payload data length
					$this->wsClients[$clientId][7] = $array['a'];
				}
			}

			// check if the frame's payload data length has now been stored
			if ($this->wsClients[$clientId][7] !== false) {

				// check if the frame's payload data length exceeds self::WS_MAX_FRAME_PAYLOAD_RECV
				if ($this->wsClients[$clientId][7] > self::WS_MAX_FRAME_PAYLOAD_RECV) {
					$this->wsClients[$clientId][7] = false;
					$this->wsSendClientClose($clientId, self::WS_STATUS_MESSAGE_TOO_BIG);
					return false;
				}

				// check if the message's payload data length exceeds 2,147,483,647 or self::WS_MAX_MESSAGE_PAYLOAD_RECV
				// doesn't apply for control frames, where the payload data is not internally stored
				$controlFrame = (ord(substr($this->wsClients[$clientId][9], 0, 1)) & 8) == 8;
				if (!$controlFrame) {
					$newMessagePayloadLength = $this->wsClients[$clientId][11] + $this->wsClients[$clientId][7];
					if ($newMessagePayloadLength > self::WS_MAX_MESSAGE_PAYLOAD_RECV || $newMessagePayloadLength > 2147483647) {
						$this->wsSendClientClose($clientId, self::WS_STATUS_MESSAGE_TOO_BIG);
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}
	function wsProcessClientFrame($clientId) {
		// store the time that data was last received from the client
		$this->wsClients[$clientId][3] = time();

		// fetch frame buffer
		$buffer = &$this->wsClients[$clientId][9];

		// check at least 6 bytes are set (first 2 bytes and 4 bytes for the mask key)
		if (substr($buffer, 5, 1) === false) return false;

		// fetch first 2 bytes of header
		$octet0 = ord(substr($buffer, 0, 1));
		$octet1 = ord(substr($buffer, 1, 1));

		$fin = $octet0 & self::WS_FIN;
		$opcode = $octet0 & 15;

		$mask = $octet1 & self::WS_MASK;
		if (!$mask) return false; // close socket, as no mask bit was sent from the client

		// fetch byte position where the mask key starts
		$seek = $this->wsClients[$clientId][7] <= 125 ? 2 : ($this->wsClients[$clientId][7] <= 65535 ? 4 : 10);

		// read mask key
		$maskKey = substr($buffer, $seek, 4);

		$array = unpack('Na', $maskKey);
		$maskKey = $array['a'];
		$maskKey = array(
			$maskKey >> 24,
			($maskKey >> 16) & 255,
			($maskKey >> 8) & 255,
			$maskKey & 255
		);
		$seek += 4;

		// decode payload data
		if (substr($buffer, $seek, 1) !== false) {
			$data = str_split(substr($buffer, $seek));
			foreach ($data as $key => $byte) {
				$data[$key] = chr(ord($byte) ^ ($maskKey[$key % 4]));
			}
			$data = implode('', $data);
		}
		else {
			$data = '';
		}

		// check if this is not a continuation frame and if there is already data in the message buffer
		if ($opcode != self::WS_OPCODE_CONTINUATION && $this->wsClients[$clientId][11] > 0) {
			// clear the message buffer
			$this->wsClients[$clientId][11] = 0;
			$this->wsClients[$clientId][1] = '';
		}

		// check if the frame is marked as the final frame in the message
		if ($fin == self::WS_FIN) {
			// check if this is the first frame in the message
			if ($opcode != self::WS_OPCODE_CONTINUATION) {
				// process the message
				return $this->wsProcessClientMessage($clientId, $opcode, $data, $this->wsClients[$clientId][7]);
			}
			else {
				// increase message payload data length
				$this->wsClients[$clientId][11] += $this->wsClients[$clientId][7];

				// push frame payload data onto message buffer
				$this->wsClients[$clientId][1] .= $data;

				// process the message
				$result = $this->wsProcessClientMessage($clientId, $this->wsClients[$clientId][10], $this->wsClients[$clientId][1], $this->wsClients[$clientId][11]);

				// check if the client wasn't removed, then reset message buffer and message opcode
				if (isset($this->wsClients[$clientId])) {
					$this->wsClients[$clientId][1] = '';
					$this->wsClients[$clientId][10] = 0;
					$this->wsClients[$clientId][11] = 0;
				}

				return $result;
			}
		}
		else {
			// check if the frame is a control frame, control frames cannot be fragmented
			if ($opcode & 8) return false;

			// increase message payload data length
			$this->wsClients[$clientId][11] += $this->wsClients[$clientId][7];

			// push frame payload data onto message buffer
			$this->wsClients[$clientId][1] .= $data;

			// if this is the first frame in the message, store the opcode
			if ($opcode != self::WS_OPCODE_CONTINUATION) {
				$this->wsClients[$clientId][10] = $opcode;
			}
		}

		return true;
	}
	function wsProcessClientMessage($clientId, $opcode, &$data, $dataLength) {
		// check opcodes
		if ($opcode == self::WS_OPCODE_PING) {
			// received ping message
			return $this->wsSendClientMessage($clientId, self::WS_OPCODE_PONG, $data);
		}
		elseif ($opcode == self::WS_OPCODE_PONG) {
			// received pong message (it's valid if the server did not send a ping request for this pong message)
			if ($this->wsClients[$clientId][4] !== false) {
				$this->wsClients[$clientId][4] = false;
			}
		}
		elseif ($opcode == self::WS_OPCODE_CLOSE) {
			// received close message
			if (substr($data, 1, 1) !== false) {
				$array = unpack('na', substr($data, 0, 2));
				$status = $array['a'];
			}
			else {
				$status = false;
			}

			if ($this->wsClients[$clientId][2] == self::WS_READY_STATE_CLOSING) {
				// the server already sent a close frame to the client, this is the client's close frame reply
				// (no need to send another close frame to the client)
				$this->wsClients[$clientId][2] = self::WS_READY_STATE_CLOSED;
			}
			else {
				// the server has not already sent a close frame to the client, send one now
				$this->wsSendClientClose($clientId, self::WS_STATUS_NORMAL_CLOSE);
			}

			$this->wsRemoveClient($clientId);
		}
		elseif ($opcode == self::WS_OPCODE_TEXT || $opcode == self::WS_OPCODE_BINARY) {
			if ( array_key_exists('message', $this->wsOnEvents) )
				foreach ( $this->wsOnEvents['message'] as $func )
					$func($clientId, $data, $dataLength, $opcode == self::WS_OPCODE_BINARY);
		}
		else {
			// unknown opcode
			return false;
		}

		return true;
	}
	function wsProcessClientHandshake($clientId, &$buffer) {
		// fetch headers and request line
		$sep = strpos($buffer, "\r\n\r\n");
		if (!$sep) return false;

		$headers = explode("\r\n", substr($buffer, 0, $sep));
		$headersCount = sizeof($headers); // includes request line
		if ($headersCount < 1) return false;

		// fetch request and check it has at least 3 parts (space tokens)
		$request = &$headers[0];
		$requestParts = explode(' ', $request);
		$requestPartsSize = sizeof($requestParts);
		if ($requestPartsSize < 3) return false;

		// check request method is GET
		if (strtoupper($requestParts[0]) != 'GET') return false;

		// check request HTTP version is at least 1.1
		$httpPart = &$requestParts[$requestPartsSize - 1];
		$httpParts = explode('/', $httpPart);
		if (!isset($httpParts[1]) || (float) $httpParts[1] < 1.1) return false;

		// store headers into a keyed array: array[headerKey] = headerValue
		$headersKeyed = array();
		for ($i=1; $i<$headersCount; $i++) {
			$parts = explode(':', $headers[$i]);
			if (!isset($parts[1])) return false;

			$headersKeyed[trim($parts[0])] = trim($parts[1]);
		}

		// check Host header was received
		if (!isset($headersKeyed['Host'])) return false;

		// check Sec-WebSocket-Key header was received and decoded value length is 16
		if (!isset($headersKeyed['Sec-WebSocket-Key'])) return false;
		$key = $headersKeyed['Sec-WebSocket-Key'];
		if (strlen(base64_decode($key)) != 16) return false;

		// check Sec-WebSocket-Version header was received and value is 7
		if (!isset($headersKeyed['Sec-WebSocket-Version']) || (int) $headersKeyed['Sec-WebSocket-Version'] < 7) return false; // should really be != 7, but Firefox 7 beta users send 8

		// work out hash to use in Sec-WebSocket-Accept reply header
		$hash = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

		// build headers
		$headers = array(
			'HTTP/1.1 101 Switching Protocols',
			'Upgrade: websocket',
			'Connection: Upgrade',
			'Sec-WebSocket-Accept: '.$hash
		);
		$headers = implode("\r\n", $headers)."\r\n\r\n";

		// send headers back to client
		$socket = $this->wsClients[$clientId][0];

		$left = strlen($headers);
		do {
			$sent = @socket_send($socket, $headers, $left, 0);
			if ($sent === false) return false;

			$left -= $sent;
			if ($sent > 0) $headers = substr($headers, $sent);
		}
		while ($left > 0);

		return true;
	}

	// client write functions
	function wsSendClientMessage($clientId, $opcode, $message) {
    // trap for a recently disconnected clientId (i.e. NP or P marked as disconnected (-1)
    if ($clientId == -1) { return true;}
		// check if client ready state is already closing or closed
		if ($this->wsClients[$clientId][2] == self::WS_READY_STATE_CLOSING || $this->wsClients[$clientId][2] == self::WS_READY_STATE_CLOSED) return true;

		// fetch message length
		$messageLength = strlen($message);

		// set max payload length per frame
		$bufferSize = 4096;

		// work out amount of frames to send, based on $bufferSize
		$frameCount = ceil($messageLength / $bufferSize);
		if ($frameCount == 0) $frameCount = 1;

		// set last frame variables
		$maxFrame = $frameCount - 1;
		$lastFrameBufferLength = ($messageLength % $bufferSize) != 0 ? ($messageLength % $bufferSize) : ($messageLength != 0 ? $bufferSize : 0);

		// loop around all frames to send
		for ($i=0; $i<$frameCount; $i++) {
			// fetch fin, opcode and buffer length for frame
			$fin = $i != $maxFrame ? 0 : self::WS_FIN;
			$opcode = $i != 0 ? self::WS_OPCODE_CONTINUATION : $opcode;

			$bufferLength = $i != $maxFrame ? $bufferSize : $lastFrameBufferLength;

			// set payload length variables for frame
			if ($bufferLength <= 125) {
				$payloadLength = $bufferLength;
				$payloadLengthExtended = '';
				$payloadLengthExtendedLength = 0;
			}
			elseif ($bufferLength <= 65535) {
				$payloadLength = self::WS_PAYLOAD_LENGTH_16;
				$payloadLengthExtended = pack('n', $bufferLength);
				$payloadLengthExtendedLength = 2;
			}
			else {
				$payloadLength = self::WS_PAYLOAD_LENGTH_63;
				$payloadLengthExtended = pack('xxxxN', $bufferLength); // pack 32 bit int, should really be 64 bit int
				$payloadLengthExtendedLength = 8;
			}

			// set frame bytes
			$buffer = pack('n', (($fin | $opcode) << 8) | $payloadLength) . $payloadLengthExtended . substr($message, $i*$bufferSize, $bufferLength);

			// send frame
			$socket = $this->wsClients[$clientId][0];

			$left = 2 + $payloadLengthExtendedLength + $bufferLength;
			do {
				$sent = @socket_send($socket, $buffer, $left, 0);
				if ($sent === false) return false;

				$left -= $sent;
				if ($sent > 0) $buffer = substr($buffer, $sent);
			}
			while ($left > 0);
		}

		return true;
	}
	function wsSendClientClose($clientId, $status=false) {
		// check if client ready state is already closing or closed
		if ($this->wsClients[$clientId][2] == self::WS_READY_STATE_CLOSING || $this->wsClients[$clientId][2] == self::WS_READY_STATE_CLOSED) return true;
    
    // send timeout message to step 1 client so that user can re-login
    $this->wsSend($clientId, "<message><messageType>socketTimeout</messageType><content>null</content></message>");
//    try {
//    }
//    catch (Exception $e){
//      echo 'exception: '. $e->getMessage();
//    }
		// store close status
		$this->wsClients[$clientId][5] = $status;

		// send close frame to client
		$status = $status !== false ? pack('n', $status) : '';
		$this->wsSendClientMessage($clientId, self::WS_OPCODE_CLOSE, $status);

		// set client ready state to closing
		$this->wsClients[$clientId][2] = self::WS_READY_STATE_CLOSING;
	}

	// client non-internal functions
	function wsClose($clientId) {
		return $this->wsSendClientClose($clientId, self::WS_STATUS_NORMAL_CLOSE);
	}
	function wsSend($clientId, $message, $binary=false) {
    if ($this->isClientSuspended($clientId)) {
      $this->addMessageToReconnectQueue($clientId,$message);
    }
    else {
      return $this->wsSendClientMessage($clientId, $binary ? self::WS_OPCODE_BINARY : self::WS_OPCODE_TEXT, $message);
      echo "send $message";
    }
	}

	function log($messageType, $message ) {
//    global $igrtSqli;
//    $logCmd=sprintf("INSERT INTO sysdiag_socketsLog (chrono,messageType,message) VALUES(NOW(),\"%s\",\"%s\");",$messageType,$message);
//    $igrtSqli->query($logCmd);
    //echo $messageType.':'. $message . "\n";
    //fwrite($this->logFile, $messageType);
    //fwrite($this->logFile, ':::');
    fwrite($this->logFile, $message . "\n");            
	}

	function bind( $type, $func ) {
		if ( !isset($this->wsOnEvents[$type]) )
			$this->wsOnEvents[$type] = array();
		$this->wsOnEvents[$type][] = $func;
	}

	function unbind( $type='' ) {
		if ( $type ) unset($this->wsOnEvents[$type]);
		else $this->wsOnEvents = array();
	}
        
}
