> ⚠️ **Status: untested.** This extension is provided as-is and has **not been tested in production**. Please feel free to fork, modify, improve, and open pull requests.
>
> Licensed under **GNU GPLv3** (see [LICENSE](LICENSE)).

# IP Block Protection - X-Cart 5 module

Screens storefront visitors against the [ip-block.com](https://www.ip-block.com)
IP-screening service and blocks flagged IP addresses.

- **Platform:** X-Cart 5 (built for the current stable line **5.6.0**)
- **Module id:** `IpBlock\Protection` (author `IpBlock`, name `Protection`)
- **Structure:** standard `classes/XLite/Module/IpBlock/Protection/`

## How it works

`Controller/Customer/ACustomer.php` is a decorator of the base **customer**
controller `\XLite\Controller\Customer\ACustomer`. Its `handleRequest()`
override runs the IP screen before any storefront controller logic. Because
only the customer-area base controller is decorated, the **admin back office
is never screened** - you cannot lock yourself out.

`Core/IpBlockChecker` (singleton):

1. Resolves the real client IP (optionally from `CF-Connecting-IP` /
   `X-Forwarded-For` when *Behind a proxy* is on).
2. Allows whitelisted IPs immediately (never sent to the service).
3. Looks the decision up in X-Cart's native Doctrine cache driver
   (`\XLite\Core\Database::getCacheDriver()`), key
   `ip_block_ + md5(ip|user_agent|referrer)`, honouring *Cache TTL*.
4. On a miss, `Core/IpBlockClient` `POST`s to the API (cURL, 1 second timeout).
5. On block: redirects to `https://www.ip-block.com/blocked.php` (default) or
   returns HTTP 403 with the configured message.
6. On any error/timeout the *Fail open* setting decides (default: allow).

## API contract

```
POST https://api.ip-block.com/v1/check
Content-Type: application/json
{"api_key":"...","site_id":"...","ip":"...","user_agent":"...","referrer":"..."}
```

Response `{"action":"allow"}` or `{"action":"block"}`. Only `"block"` blocks.

## Installation

1. Copy `classes/XLite/Module/IpBlock/Protection/` into your X-Cart install.
2. In the admin: **My addons / Modules**, find **IP Block Protection**, enable it.
3. Re-deploy the store (rebuild cache) when prompted.
4. Open the module **Settings**, fill in Site ID / API key, then tick *Enabled*.

## Settings (module config options)

| Setting | Default | Notes |
|---|---|---|
| enabled | off | Master switch |
| site_id | - | ip-block.com site id |
| api_key | - | password field |
| api_url | `https://api.ip-block.com/v1/check` | |
| fail_open | on | allow on error/timeout |
| cache_ttl | `300` | seconds; `0` = every request |
| behind_proxy | off | read real IP from CF-Connecting-IP / X-Forwarded-For |
| block_action | `redirect` | `redirect` or `message` (403) |
| block_message | `Access denied.` | used in 403 mode |
| whitelist | - | one IP per line, never blocked |
