<?php
/**
 * IP Block Protection - core screening logic.
 */

namespace XLite\Module\IpBlock\Protection\Core;

/**
 * Singleton that ties everything together:
 *   - resolve the real client IP (honouring behind_proxy)
 *   - honour the whitelist (never blocked, never sent to the API)
 *   - cache each decision by md5(ip|user_agent|referrer) via X-Cart's
 *     native Doctrine cache driver (TTL-aware)
 *   - apply the fail mode on any error
 *   - enforce the block (redirect or HTTP 403)
 */
class IpBlockChecker extends \XLite\Base\Singleton
{
    /**
     * Called from the customer-controller decorator on every storefront
     * request. Blocks (and terminates the request) when required.
     *
     * @return void
     */
    public function guard()
    {
        if (!$this->isEnabled()) {
            return;
        }

        if ($this->shouldBlock()) {
            $this->deny();
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isOn($this->cfg('enabled'));
    }

    /**
     * @return bool
     */
    protected function shouldBlock()
    {
        $ip = $this->resolveClientIp();
        if ($ip === '') {
            return false;
        }

        if ($this->isWhitelisted($ip)) {
            return false;
        }

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $referrer  = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';

        $failOpen = $this->isOn($this->cfg('fail_open'));
        $cacheTtl = (int) $this->cfg('cache_ttl');

        $cacheKey = 'ip_block_' . md5($ip . '|' . $userAgent . '|' . $referrer);
        $cache    = \XLite\Core\Database::getCacheDriver();

        // --- Cache lookup (skipped when TTL is 0). ---
        if ($cacheTtl > 0 && $cache && $cache->contains($cacheKey)) {
            return (bool) $cache->fetch($cacheKey);
        }

        // --- Ask the service. ---
        $apiUrl = (string) $this->cfg('api_url');
        if ($apiUrl === '') {
            $apiUrl = 'https://api.ip-block.com/v1/check';
        }

        $decision = IpBlockClient::getInstance()->check(
            $apiUrl,
            (string) $this->cfg('api_key'),
            (string) $this->cfg('site_id'),
            $ip,
            $userAgent,
            $referrer
        );

        // null => error/timeout: apply fail mode.
        $block = ($decision === null) ? !$failOpen : $decision;

        if ($cacheTtl > 0 && $cache) {
            $cache->save($cacheKey, $block, $cacheTtl);
        }

        return $block;
    }

    /**
     * Enforce the block: redirect (default) or HTTP 403 with a message.
     *
     * @return void
     */
    protected function deny()
    {
        if ($this->cfg('block_action') === 'message') {
            $message = (string) $this->cfg('block_message');
            if ($message === '') {
                $message = 'Access denied.';
            }

            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/plain; charset=utf-8');
            echo $message;
            exit;
        }

        // Default: redirect to the ip-block.com blocked page.
        header('Location: https://www.ip-block.com/blocked.php', true, 302);
        exit;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Read one module config option.
     *
     * @param string $name
     * @return mixed|null
     */
    protected function cfg($name)
    {
        // Options live under the "IpBlock\Protection" category; the backslash
        // forces the curly-brace dynamic-property syntax.
        $options = \XLite\Core\Config::getInstance()->{'IpBlock\Protection'};

        return (is_object($options) && isset($options->$name)) ? $options->$name : null;
    }

    /**
     * Normalise a checkbox/OnOff value to a boolean.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isOn($value)
    {
        return $value === 'Y' || $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Resolve the real client IP, honouring behind_proxy.
     *
     * @return string
     */
    protected function resolveClientIp()
    {
        if ($this->isOn($this->cfg('behind_proxy'))) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])
                && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)
            ) {
                return $_SERVER['HTTP_CF_CONNECTING_IP'];
            }

            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * @param string $ip
     * @return bool
     */
    protected function isWhitelisted($ip)
    {
        $raw = (string) $this->cfg('whitelist');
        if (trim($raw) === '') {
            return false;
        }

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            if (trim($line) === $ip) {
                return true;
            }
        }

        return false;
    }
}
