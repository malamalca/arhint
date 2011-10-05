<?php
	class LilTasksParseEmail {
		static function fetch($options) {
			if ($mbox = imap_open(sprintf('{%1$s:%2$s/%3$s}INBOX', $options['server'], $options['port'], implode('/', $options['settings'])), $options['user'], $options['pass'])) {
				$ret = array();
				if (($messages=imap_num_msg($mbox))>0) {
					for ($message=1; $message < $messages+1; $message++) {
						$eml = imap_fetchheader($mbox, $message) . imap_body($mbox, $message);
						
						$data = array('Task' => array());
						$email_data = LilTasksParseEmail::__parseEmailHeader($mbox, $message);
						
						$data['Task']['title'] = $email_data['subject'];
						$data['Task']['happened'] = strftime('%Y-%m-%d', strtotime($email_data['date']));
						
						list($sender, $domain) = explode('@', $email_data['from']);
						if ($sender == 'today') {
							$data['Task']['deadline'] = strftime('%Y-%m-%d');
						} else if ($sender == 'tomorrow') {
							$data['Task']['deadline'] = strftime('%Y-%m-%d', time()+24*60*60);
						} else if (in_array(strtolower($sender), array('monday', 'tuesday', 'wednesday', 'thursday', 'saturday', 'sunday'))) {
							$data['Task']['deadline'] = strftime('%Y-%m-%d', strtotime('next ' . ucfirst($sender)));
						}
						
						$hash = sha1($data['Task']['happened'] . '_' . $email_data['subject']);
						
						$parts = array();
						$data['Task']['descript'] = LilTasksParseEmail::__parseEmailBody($mbox, $message, $hash, $parts);
						
						file_put_contents(TMP . $hash . '.eml', $eml);
						$data['Attachment'][0] = array(
							'model'    => 'Task',
							'filename' => array(
								'name' => $hash . '.eml',
								'tmp_name' => TMP . $hash . '.eml'
							),
							'title'    => 'SOURCE: ' . $data['Task']['title']
						);
						
						App::uses('Sanitize', 'Utility');
						foreach ($parts as $part) {
							if (!empty($part['attachment'])) {
								$data['Attachment'][] = array(
									'model'    => 'Task',
									'filename' => array(
										'name' => Sanitize::paranoid($part['attachment']['filename']),
										'tmp_name' => $part['attachment']['tmp']
									),
									'title'    => $part['attachment']['filename']
								);
							}
						}
						
						$ret[$message] = $data;
						
						imap_delete($mbox, $message);
					}
				}
				return $ret;
				imap_close($mbox, CL_EXPUNGE);
			} else var_dump(imap_errors());
		}
		
		function __parseEmailBody(&$mbox, $message, $hash, &$parts) {
			$body = '';
			$s = imap_fetchstructure($mbox, $message);
			//see if there are any parts
			if (!isset($s->parts) || count($s->parts)==0){
				$body = imap_body($mbox, $message);
				if ($s->encoding==4) $body=quoted_printable_decode($body);
				if (strtoupper($s->subtype)=='HTML') $body=strip_tags($body);
				foreach ($s->parameters as $p) {
					if (strtoupper($p->attribute)=='CHARSET') {
						$body = mb_convert_encoding($body, 'UTF-8', $p->value);
					}
				}
				return $body;
			} else {
				foreach ($s->parts as $partno=>$partarr){
					LilTasksParseEmail::__parseEmailPart($partarr, $partno+1, $hash, $mbox, $message, $parts);
				}
				// try to find first plain body
				foreach ($parts as $part) {
					if (strtoupper($part['subtype'])=='PLAIN' && isset($part['text'])) {
						return $part['text'];
					}
				}
				// no plain body found. search for html body
				foreach ($parts as $part) {
					if (strtoupper($part['subtype'])=='HTML' && isset($part['text'])) {
						return strip_tags($part['text']);
					}
				}
				return false;
			}
		}
		
		function __parseEmailHeader(&$mbox, $message) {
			$header = imap_headerinfo($mbox, $message);
						
			$from = '';
			if (!empty($header->senderaddress) && $data = imap_mime_header_decode($header->senderaddress)) {
				$from = array();
				foreach ($data as $k=>$v) {
					if ($v->charset=='default') $from[] = $v->text; else $from[] = mb_convert_encoding($v->text, 'UTF-8', $v->charset);
				}
				$from = implode(',', $from);
			}
			
			$to = '';
			if (!empty($header->toaddress) && $data = imap_mime_header_decode($header->toaddress)) {
				$to = array();
				foreach ($data as $k=>$v) {
					if ($v->charset=='default') $to[] = $v->text; else $to[] = mb_convert_encoding($v->text, 'UTF-8', $v->charset);
				}
				$to = implode(',', $to);
			}
			
			$cc = '';
			if (!empty($header->ccaddress) && $data = imap_mime_header_decode($header->ccaddress)) {
				$cc = array();
				foreach ($data as $k=>$v) {
					if ($v->charset=='default') $cc[] = $v->text; else $cc[] = mb_convert_encoding($v->text, 'UTF-8', $v->charset);
				}
				$cc = implode(',', $cc);
			}
			
			$subject = '';
			if (!empty($header->subject) && $data = imap_mime_header_decode($header->subject)) {
				$subject = array();
				foreach ($data as $k=>$v) {
					if ($v->charset=='default') $subject[] = $v->text; else $subject[] = mb_convert_encoding($v->text, 'UTF-8', $v->charset);
				}
				$subject = implode(',', $subject);
			}
			$date = $header->date;
			
			return array('date'=>$header->date, 'from'=>$from, 'to'=>$to, 'cc'=>$cc, 'subject'=>$subject);
		}
		
		function __parseEmailPart($p, $i, $hash, &$link, $msgid, &$partsarray) {
			App::uses('Sanitize', 'Utils');
			$filestore = TMP.'emails'.DS;
			$part = imap_fetchbody($link, $msgid, $i);
			
			$partsarray[$i]['subtype'] = $p->subtype; 
			
			//******************************************************************
			// Get filename of attachment if present
			//******************************************************************
			$filename='';
			// if there are any dparameters present in this part
			if (isset($p->dparameters) && count($p->dparameters)>0) {
				foreach ($p->dparameters as $dparam){
					if ((strtoupper($dparam->attribute)=='NAME') ||(strtoupper($dparam->attribute)=='FILENAME')) $filename=$dparam->value;
				}
			}
			//if no filename found
			if ($filename=='') {
				// if there are any parameters present in this part
				if (isset($p->parameters) && count($p->parameters)>0){
					foreach ($p->parameters as $param) {
						if ((strtoupper($param->attribute)=='NAME') ||(strtoupper($param->attribute)=='FILENAME')) $filename=$param->value;
					}
				}
			}
			//******************************************************************
			//DECODE PART       
			//decode if base64 or quoted printable
			if ($p->encoding==3) $part=base64_decode($part);
			if ($p->encoding==4) $part=quoted_printable_decode($part);
			//no need to decode binary or 8bit!
			
			if ($p->type!=0) {
				//if type is not text
				//end if type!=0       
			} else if($p->type==0) {
				//decode text
				//OPTIONAL PROCESSING e.g. nl2br for plain text
				//if plain text
				if (strtoupper($p->subtype)=='PLAIN') 1;
				else if (strtoupper($p->subtype)=='HTML') 1;
				
				$charset = '';
				if (isset($p->parameters) && count($p->parameters)>0){
					foreach ($p->parameters as $param) {
						if (strtoupper($param->attribute)=='CHARSET') $charset = $param->value;
					}
				}
				if (!empty($charset)) $part = mb_convert_encoding($part, 'UTF-8', $charset);
				if (empty($filename)) $partsarray[$i]['text'] = $part;
			}
			
			//write to disk and set partsarray variable
			if (!empty($filename)) {
				$filehash = $filestore.$hash.'_'.Sanitize::paranoid($filename, array('.'));
				$partsarray[$i]['attachment'] = array('tmp'=>$filehash, 'filename'=>$filename);
				file_put_contents($filehash, $part);
			}
				
			//if subparts... recurse into function and parse them too!
			if (isset($p->parts) && count($p->parts)>0){
				foreach ($p->parts as $pno=>$parr) {
					LilTasksParseEmail::__parseEmailPart($parr, ($i.'.'.($pno+1)), $hash, $link, $msgid, $partsarray);   
				}
			}
			return;
		}
	}