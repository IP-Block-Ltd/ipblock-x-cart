<?php
/**
 * IP Block Protection - HTTP client for the ip-block.com screening API.
 */

namespace XLite\Module\IpBlock\Protection\Core;

/**
 * Thin client implementing the shared contract:
 *
 *   POST {apiUrl}
 *   Content-Type: application/json
 *   Body: {"api_key","site_id","ip","user_agent","referrer"}
 *   Response: {"action":"allow"|"block"}
 *
 * cURL is used directly so the 1-second timeout is enforced exactly. The
 * client NEVER throws: any error / timeout / bad response returns null so the
 * Checker can apply the configured fail mode.
 */
class IpBlockClient extends \XLite\Base\Singleton
{
    const TIMEOUT_SECONDS = 1;

    /**
     * @param string $apiUrl
     * @param string $apiKey
     * @param string $siteId
     * @param string $ip
     * @param string $userAgent
     * @param string $referrer
     *
     * @return bool|null true = block, false = allow, null = undecided (fail mode)
     */
    public function check($apiUrl, $apiKey, $siteId, $ip, $userAgent, $referrer)
    {
        $payload = json_encode(array(
            'api_key'    => $apiKey,
            'site_id'    => $siteId,
            'ip'         => $ip,
            'user_agent' => $userAgent,
            'referrer'   => $referrer,
        ));

        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
        ));

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno  = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            return null; // network error / timeout
        }

        if ($status < 200 || $status >= 300) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['action'])) {
            return null;
        }

        // Only the explicit "block" action blocks.
        return $data['action'] === 'block';
    }
}
