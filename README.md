# sj_jav
JAV Censored 전용 Plex Agent

# 사용법
일본 dmm 사이트에서 메타정보를 가져오는데, 이 사이트가 국내에서 접속은 차단되어 있다.
해외 호스팅업체 sj_jav_server.php 파일을 올리고 Agent 설정에서 서버 URL을 입력한다.

# 번역
sj_jav_server.php 에서는 구글 번역 API를 사용하는 코드로 되어 있다.
```function trans($str) ``` 에서 ```return $str;```을 삭제하고 ```구글 API KEY```부분에 구글 KEY를 입력해주면 된다.


```
function trans($str) {
	return $str;
	$handle = curl_init();
	if (FALSE === $handle) {
		return $str;
	}
	curl_setopt($handle, CURLOPT_URL,'https://www.googleapis.com/language/translate/v2');
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_POSTFIELDS, array('key'=> '구글API KEY', 'q' => $str, 'source' => 'ja', 'target' => 'ko'));
```    

> Avgle에서는 에이전트에서 파파고 API를 사용하여 번역한다.구글번역을 이용하지 않으려면 참고하여 Agent에서 번역하는 방식으로 코딩하면 된다. [링크](https://github.com/soju6jan/Avgle.bundle/blob/master/Avgle.bundle/Contents/Code/__init__.py)

