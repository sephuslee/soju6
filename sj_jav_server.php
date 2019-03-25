<?php
function search($arg) {
	try {
		$tmps = get_search_str($arg);
		if (count($tmps) == 2) {
			$strlen = strlen($tmps[1]);
			if ($strlen < 5) {
				$title = $tmps[0].str_pad($tmps[1],'5','0',STR_PAD_LEFT);
			} else if ($strlen == 5) {
				$title = $tmps[0].str_pad($tmps[1],'6','0',STR_PAD_LEFT);
			}
		}
		$dom = new DomDocument;
		$dom->loadHTMLFile('https://www.dmm.co.jp/search/=/searchstr='.$title.'/n1=FgRCTw9VBA4GAVhfWkIHWw__/');
		$xpath = new DomXPath($dom);
		$ret = array();
		$nodes = $xpath->query('//*[@id="list"]/li');
		$score = 95;
		foreach ($nodes as $i => $node) {
			try {
				$entity = array();
				$tag = $xpath->query('.//div/p[2]/a', $node)[0];
				$href = strtolower($tag->getAttribute('href'));
				preg_match('/\/cid=(?P<code>.*?)\//', $href, $matches, PREG_OFFSET_CAPTURE);
				$entity['id'] = $matches['code'][0];
				$already_exist = false;
				foreach($ret as $e) {
					if ($e['id'] == $entity['id']) {
						$already_exist = true;
						break;
					}
				}
				if ($already_exist) continue;
				$tag = $xpath->query('.//span[1]/img', $node)[0];
				$entity['title'] = $tag->getAttribute('alt');
				$entity['title_ko'] = trans($entity['title']);
				preg_match('/\d*(?P<real>[a-zA-Z]+)(?P<no>\d+)([a-zA-Z]+)?$/', $entity['id'], $matches, PREG_OFFSET_CAPTURE);
				if ($matches) {
					$entity['id_show'] = $matches['real'][0].$matches['no'][0];
				} else {
					$entity['id_show'] = $entity['id'];
				}
				if ( count($tmps) == 2) {
					if ( strpos($entity['id'], $title) !== false and strcmp($entity['id_show'], $title) == 0) {
						$entity['score'] = 100;
					} else if ( strpos($entity['id'], $tmps[0]) !== false && strpos($entity['id'], $tmps[1])!== false ) {
						$entity['score'] = $score;
						$score -= 5;
					} else if ( strpos($entity['id'], $tmps[0]) !== false || strpos($entity['id'], $tmps[1]) !== false ) {
						$entity['score'] = 60;
					} else {
						$entity['score'] = 20;
					}
				} else {
					if (strpos($entity['id'], $tmps[0]) !== false ) {
						$entity['score'] = $score;
						$score -= 5;
					} else {
						$entity['score'] = 20;
					}
				}
				array_push($ret, $entity);
			} catch(Exception $e) {

			}
		}
		echo json_encode( $ret );
	}
	catch(Exception $ee)
	{
		echo json_encode( array() );
	}
}

function get_search_str($arg) {
	$str = strtolower($arg);
	$tmps = explode(' ', $str);
	if ( count($tmps) == 2) {
		preg_match('/^[a-zA-Z]+$/', $tmps[0], $matches1, PREG_OFFSET_CAPTURE);
		preg_match('/^[0-9]+$/', $tmps[1], $matches2, PREG_OFFSET_CAPTURE);
		if ($matches1 && $matches2) {
			return $tmps;
		} else if ( ($tmps[0] == 't28' || $tmps[0] == '140c') && $matches2) {
			return $tmps;
		}
	}
	$regex = '^.*\.com\d*';
	preg_match('/'.$regex.'/', $str, $matches, PREG_OFFSET_CAPTURE);
	if ($matches) {
		$str = str_replace($matches[0], '', $str);
	}
	$regex = '^(?P<site>\[.*?\])?\s?(?P<name>[a-zA-Z]+)[\-|\s]?(?P<no>\d+).*?$';
	preg_match('/'.$regex.'/', $str, $matches, PREG_OFFSET_CAPTURE);
	if ( $matches ) {
		return [$matches['name'][0], $matches['no'][0]];
	}
	return [$arg];
}


function update($arg) {
	$ret = array();
	try {
		$dom = new DomDocument;
		$dom->loadHTMLFile('http://www.dmm.co.jp/digital/videoa/-/detail/=/cid='.$arg.'/');
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/div[1]/div');
		if (count($nodes) == 0) {
			throw new Exception("fail"); 
		}
		$a_nodes = $xpath->query('.//a', $nodes[0]);
		if (count($a_nodes) > 0) {
			$ret['poster_full'] = $a_nodes[0]->getAttribute('href');
			$nodes = $a_nodes;
		} else {
			$ret['poster_full'] = '';
		}
		$tag = $xpath->query('.//img', $nodes[0])[0];
		$ret['poster'] = $tag->getAttribute('src');
		$ret['title'] = $tag->getAttribute('alt');
		$ret['title_ko'] = trans($ret['title']);
		try {
			$tag = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/table//tr[4]/td[2]');
			$ret['date'] = trim(str_replace('/', '', $tag[0]->nodeValue));
		}catch (Exception $e){
			$ret['date'] = '';
		}
		if (strlen($ret['date']) != 8) {
			try {
				$tag = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/table//tr[3]/td[2]');
				$ret['date'] = trim(str_replace('/', '', $tag[0]->nodeValue));
			}catch (Exception $e){
				$ret['date'] = '';
			}
		}
		$tag = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/table//tr[5]/td[2]');
		preg_match('/^(?P<time>\d+)/', trim($tag[0]->nodeValue), $matches, PREG_OFFSET_CAPTURE);
		if ($matches) {
			$ret['running_time'] = $matches['time'][0];
		} else {
			$ret['running_time'] = '';
		}
		$nodes = $xpath->query('//*[@id="performer"]/a');
		$ret['performer'] = array();
		foreach ($nodes as $node) {
			$entity = array();
			preg_match('/\/id=(?P<id>.*?)\//', trim($node->getAttribute('href')), $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
				$entity['id'] = $matches['id'][0];
				$entity['name'] = trim($node->nodeValue);
				array_push($ret['performer'], get_actor_info($entity) );
			}
		}
		$ret = set_info($xpath, $ret, '//*[@id="mu"]/div/table//tr/td[1]/table//tr[7]/td[2]/a', 'director');
		$ret = set_info($xpath, $ret, '//*[@id="mu"]/div/table//tr/td[1]/table//tr[8]/td[2]/a', 'series');
		$ret = set_info($xpath, $ret, '//*[@id="mu"]/div/table//tr/td[1]/table//tr[9]/td[2]/a', 'studio');
		$ret = set_info($xpath, $ret, '//*[@id="mu"]/div/table//tr/td[1]/table//tr[10]/td[2]/a', 'label');
		$nodes = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/table//tr[11]/td[2]/a');
		$ret['genre'] = array();
		foreach ($nodes as $node) {
			$tmp = str_replace(' ', '', trans(trim($node->nodeValue)));
			if (strcmp($tmp, '고화질') && strcmp($tmp, '독점전달') && strcmp($tmp, '세트상품'))
				array_push($ret['genre'], $tmp);
		}
		$tag = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/table//tr[12]/td[2]');
		$ret['code'] = trim($tag[0]->nodeValue);
		preg_match('/\d*(?P<real>[a-zA-Z]+)(?P<no>\d+)([a-zA-Z]+)?$/', $ret['code'], $matches, PREG_OFFSET_CAPTURE);
		if ($matches) {
			$ret['code_show'] = $matches['real'][0].$matches['no'][0];
			$ret['release'] = $matches['real'][0];
		} else {
			$ret['code_show'] = $ret['code'];
			$ret['release'] = '';
		}
		try{
			$tag = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/table//tr[13]/td[2]/img');
			if (count($tag) == 0) throw new Exception('');
			preg_match('/\/(?P<rating>.*?)\.gif/', trim($tag[0]->getAttribute('src')), $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
				$tmps = explode('/', $matches['rating'][0]);
				$ret['rating'] = str_replace('_', '.', $tmps[count($tmps)-1]);
			} else {
				$ret['rating'] = '0';
			}
		} catch (Exception $e) {
			$ret['rating'] = '0';
		}
		$tag = $xpath->query('//*[@id="mu"]/div/table//tr/td[1]/div[4]');
		$ret['summary'] = explode('※', trim($tag[0]->nodeValue))[0];
		$ret['summary_ko'] = trans($ret['summary']);

		$nodes = $xpath->query('//*[@id="sample-image-block"]/a');
		$ret['sample_image'] = array();
		foreach ($nodes as $node) {
			$entity = array();
			$tag = $xpath->query('.//img', $nodes[0])[0];	
			$entity['thumb'] = $tag->getAttribute('src');
			$entity['full'] = str_replace($ret['code'].'-', $ret['code'].'jp-', $entity['thumb']);
			array_push($ret['sample_image'], $entity);
		}
		$ret['result'] = 'success';
		echo json_encode( $ret );
	} catch(Exception $e)
	{
		$ret['result'] = $e->getMessage();
		echo json_encode( $ret );
	}
}

function set_info($xpath, $ret, $path_str, $info) {
	try {
		$tag = $xpath->query($path_str);
		$ret[$info] = trim($tag[0]->nodeValue);
		$ret[$info.'_ko'] = trans($ret[$info]);
	} catch(Exception $s) {
		$ret[$info] = '';
		$ret[$info.'_ko'] = '';
	}
	return $ret;
}

function get_actor_info($entity) {
	$handle = curl_init();
	if (FALSE === $handle) {
		return $entity;
	}
	curl_setopt($handle, CURLOPT_URL,'https://hentaku.net/starsearch.php');
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_POSTFIELDS, array('name'=> $entity['name']));
	//curl_setopt($handle,CURLOPT_HTTPHEADER,array('X-HTTP-Method-Override: GET'));
	$response = curl_exec($handle);
	$dom = new DomDocument;
	$data = '<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">'.$response;
	$dom->loadHTML($data);
	$xpath = new DomXPath($dom);
	$nodes = $xpath->query('//img');
	if ( count($nodes) == 0) {
		$entity['img'] = 'xxxx';
		$entity['name_kor'] = '';
		$entity['name_eng'] = '';
	} else {
		$entity['img'] = trim($nodes[0]->getAttribute('src'));
		$nodes = $xpath->query('//div[@class="avstar_info_b"]');
		$tmps = explode('/', trim($nodes[0]->nodeValue));
		$entity['name_kor'] = trim($tmps[0]);
		$entity['name_eng'] = trim($tmps[1]);
	}
	return $entity;
}
	
function trans($str) {
	$handle = curl_init();
	if (FALSE === $handle) {
		return $str;
	}
	curl_setopt($handle, CURLOPT_URL,'https://www.googleapis.com/language/translate/v2');
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_POSTFIELDS, array('key'=> 'AIzaSyDmRnJerB5nzawxYuBHT5L7dTSrlSux12A', 'q' => $str, 'source' => 'ja', 'target' => 'ko'));
	curl_setopt($handle,CURLOPT_HTTPHEADER,array('X-HTTP-Method-Override: GET'));
	$response = curl_exec($handle);
	$data_array = json_decode($response, true);
	return $data_array['data']['translations'][0]['translatedText'];
}

libxml_use_internal_errors(true);
header('Content-type: application/json');
$mode = $_GET['mode'];
$arg = $_GET['arg'];
if ($mode == 'search') {
	return search($arg);
} else if ($mode == 'update') {
	return update($arg);
}
?>
