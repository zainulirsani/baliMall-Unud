<?php


namespace App\Service;


use App\Exception\HttpClientException;
use App\Helper\StaticHelper;
use Psr\Log\LoggerInterface;

class BniService
{

    protected $headers;
    protected $endpoint;
    protected $logger;

    protected $baseUrl;
    protected $bniPrefix;
    protected $bniClientId;
    protected $bniSecretKey;

    const TIME_DIFF_LIMIT = 480;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->baseUrl = getenv('VA_BNI_URL_API');
        $this->bniPrefix = getenv('VA_BNI_PREFIX');
        $this->bniClientId = getenv('VA_BNI_CLIENT_ID');
        $this->bniSecretKey = getenv('VA_BNI_SECRET_KEY');
        date_default_timezone_set('Asia/Jakarta');
    }

    public function inquiryVa($trxId)
    {
        $request = [
            'type' => 'inquirybilling',
            'client_id' => $this->bniClientId,
            'trx_id' => $trxId,
        ];

        $hashed_string = $this->encrypt(
            $request,
            $this->bniClientId,
            $this->bniSecretKey
        );

        $dataSend = [
            'client_id' => $this->bniClientId,
            'data' => $hashed_string,
        ];

        $this->logger->error(sprintf('BNI VA API Payload: %s', json_encode([$request,$dataSend])));
        $response_json = $this->get_content($this->baseUrl, json_encode($dataSend));
        $response = json_decode($response_json, true);
        if ($response['status'] !== '000') {
            $result['status'] = false;

            $this->logger->error(sprintf('BNI VA API inquiry error: %s', json_encode($response)));
        } else {
            $result['status'] = true;
            $result['data'] = $this->decrypt($response['data'], $this->bniClientId, $this->bniSecretKey);

            $this->logger->error(sprintf('BNI VA API inquiry success: %s', json_encode([$response, $result['data']])));
        }

        date_default_timezone_set('Asia/Makassar');
        return $result;
    }

    public function updateVa($amount, $trxId, $customerName)
    {
        $request = [
            'type' => 'updatebilling',
            'client_id' => $this->bniClientId,
            'trx_id' => $trxId, // fill with Billing ID
            'trx_amount' => strval($amount),
            'customer_name' => $customerName,
        ];

        $hashed_string = $this->encrypt(
            $request,
            $this->bniClientId,
            $this->bniSecretKey
        );

        $dataSend = [
            'client_id' => $this->bniClientId,
            'data' => $hashed_string,
        ];

        $this->logger->error(sprintf('BNI VA API Payload: %s', json_encode([$request,$dataSend])));
        $response_json = $this->get_content($this->baseUrl, json_encode($dataSend));
        $response = json_decode($response_json, true);
        if ($response['status'] !== '000') {
            $result['status'] = false;

            $this->logger->error(sprintf('BNI VA API updated error: %s', json_encode($response)));
        } else {
            $result['status'] = true;
            $result['data'] = $this->decrypt($response['data'], $this->bniClientId, $this->bniSecretKey);

            $this->logger->error(sprintf('BNI VA API updated success: %s', json_encode([$response, $result['data']])));
        }

        date_default_timezone_set('Asia/Makassar');
        return $result;
    }

    public function createVa($amount, $trxId, $digitSatker, $user)
    {
        $request = [
            'type' => 'createbilling',
            'client_id' => $this->bniClientId,
            'trx_id' => $trxId, // fill with Billing ID
            'trx_amount' => strval($amount),
            'billing_type' => 'c',
            'datetime_expired' => date('c', time() + ((24 * 3600) * 3)), // billing will be expired in 2 hours
            'virtual_account' => $this->bniPrefix.$this->bniClientId.$digitSatker,
            'customer_name' => $user->getUsername(),
            'customer_email' => $user->getEmail(),
            'customer_phone' => $this->changeFormatPhoneNumber($user->getPhoneNumber()),
        ];

        
        $hashed_string = $this->encrypt(
            $request,
            $this->bniClientId,
            $this->bniSecretKey
        );

        $dataSend = [
            'client_id' => $this->bniClientId,
            'data' => $hashed_string,
        ];

        $this->logger->error(sprintf('BNI VA API Payload: %s', json_encode([$request,$dataSend])));
        $response_json = $this->get_content($this->baseUrl, json_encode($dataSend));
        $response = json_decode($response_json, true);
        if ($response['status'] !== '000') {
            $result['status'] = false;

            $this->logger->error(sprintf('BNI VA API result error: %s', json_encode($response)));
        } else {
            $result['status'] = true;
            $result['data'] = $this->decrypt($response['data'], $this->bniClientId, $this->bniSecretKey);

            $this->logger->error(sprintf('BNI VA API result success: %s', json_encode([$response, $result['data']])));
        }

        date_default_timezone_set('Asia/Makassar');
        return $result;
    }

    public function handleCallback($data)
    {
        $data = json_decode($data, true);
        $result['status'] = false;
        if ($data) {
            if ($data['client_id'] === $this->bniClientId) {
                $response = $this->decrypt(
                    $data['data'],
                    $this->bniClientId,
                    $this->bniSecretKey
                );
        
                if ($response) {
                    $result['status'] = true;
                    $result['data'] = $response;
                }
            }
        }
        $this->logger->error(sprintf('BNI VA API callback: %s', json_encode([$data,$response])));
        date_default_timezone_set('Asia/Makassar');
        return $result;
    }

    function get_content($url, $post = '') {
        $header[] = 'Content-Type: application/json';
        $header[] = "Accept-Encoding: gzip, deflate";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Accept-Language: en-US,en;q=0.8,id;q=0.6";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        // curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36");
    
        if ($post)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
        $rs = curl_exec($ch);
    
        if(empty($rs)){
            dd($rs, curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $rs;
    }

	public static function encrypt(array $json_data, $cid, $secret) {
		return self::doubleEncrypt(strrev(time()) . '.' . json_encode($json_data), $cid, $secret);
	}

	public static function decrypt($hased_string, $cid, $secret) {
		$parsed_string = self::doubleDecrypt($hased_string, $cid, $secret);
		list($timestamp, $data) = array_pad(explode('.', $parsed_string, 2), 2, null);
		if (self::tsDiff(strrev($timestamp)) === true) {
			return json_decode($data, true);
		}
		return null;
	}

	private static function tsDiff($ts) {
		return abs($ts - time()) <= self::TIME_DIFF_LIMIT;
	}

	private static function doubleEncrypt($string, $cid, $secret) {
		$result = '';
		$result = self::enc($string, $cid);
		$result = self::enc($result, $secret);
		return strtr(rtrim(base64_encode($result), '='), '+/', '-_');
	}

	private static function enc($string, $key) {
		$result = '';
		$strls = strlen($string);
		$strlk = strlen($key);
		for($i = 0; $i < $strls; $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % $strlk) - 1, 1);
			$char = chr((ord($char) + ord($keychar)) % 128);
			$result .= $char;
		}
		return $result;
	}

	private static function doubleDecrypt($string, $cid, $secret) {
		$result = base64_decode(strtr(str_pad($string, ceil(strlen($string) / 4) * 4, '=', STR_PAD_RIGHT), '-_', '+/'));
		$result = self::dec($result, $cid);
		$result = self::dec($result, $secret);
		return $result;
	}

	private static function dec($string, $key) {
		$result = '';
		$strls = strlen($string);
		$strlk = strlen($key);
		for($i = 0; $i < $strls; $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % $strlk) - 1, 1);
			$char = chr(((ord($char) - ord($keychar)) + 256) % 128);
			$result .= $char;
		}
		return $result;
	}

    function changeFormatPhoneNumber($nohp) {
        // kadang ada penulisan no hp 0811 239 345
        $nohp = str_replace(" ","",$nohp);
        // kadang ada penulisan no hp (0274) 778787
        $nohp = str_replace("(","",$nohp);
        // kadang ada penulisan no hp (0274) 778787
        $nohp = str_replace(")","",$nohp);
        // kadang ada penulisan no hp 0811.239.345
        $nohp = str_replace(".","",$nohp);
    
        // cek apakah no hp mengandung karakter + dan 0-9
        if(!preg_match('/[^+0-9]/',trim($nohp))){
            // cek apakah no hp karakter 1-3 adalah +62
            if(substr(trim($nohp), 0, 3)=='+62'){
                $hp = '62'.substr(trim($nohp), 3);
            }
            // cek apakah no hp karakter 1 adalah 0
            elseif(substr(trim($nohp), 0, 1)=='0'){
                $hp = '62'.substr(trim($nohp), 1);
            }
        }
        return $hp;
    }
}
