# OpenWrtApi

Talk to your OpenWrt box from PHP — login once, call ubus, batch wifi stations in a single HTTP round-trip.

## Why this exists

OpenWrt already exposes a powerful **ubus** JSON-RPC API through LuCI (`/cgi-bin/luci/admin/ubus`). Most home-lab scripts still shell out to `ssh` + `ubus`, or reinvent curl + session cookies for every call.

**OpenWrtApi** is a small, dependency-light PHP client for that endpoint: session login, typed-feeling helpers, and a real **multi-RPC** batch so you do not hammer the router with one request per radio interface.

Built for home-labbers and appliance UIs — **not** an enterprise device-management platform.

## Who it is for

- You run OpenWrt (or an OpenWrt-based AP) and want **PHP** to read system info, wireless devices, or associated stations.
- You are wiring a LAN dashboard or home-automation panel and need a stable ubus client without dragging in a framework.
- You care about **fewer round-trips**: one MultiCall for many `iwinfo.assoclist` queries.

Not for: cloud MDM, non-OpenWrt firmwares, or people who only need the LuCI web UI.

## What it does

| Capability | How |
|---|---|
| **Session login** | `UbusLogin($user, $pass)` → keeps the ubus RPC session id |
| **Reuse a session** | `SetSessionId` / `GetSessionId` (cache across requests) |
| **One ubus call** | `UbusCall($path, $method, $args = array())` → returns the ubus result payload or `false` |
| **List objects** | `UbusList($args)` → ubus `list` |
| **Batch JSON-RPC** | `MultiAdd(...)` then `MultiCall()` → one POST, results keyed by request id |
| **Wifi stations helper** | `UbusListStations($ifnames)` → MultiAdd `iwinfo`/`assoclist` per iface, one MultiCall |
| **Errors** | `GetErrors()` → curl + ubus status codes/text |
| **Self-signed HTTPS** | curl peer/host verify off (typical LAN OpenWrt certs) |
| **OpenWrt ≥ 23.05** | empty `$args` are **omitted** from the payload (empty object breaks those builds) |

Transport: JSON-RPC 2.0 over HTTP(S) to `{base}/cgi-bin/luci/admin/ubus`. One class: [`src/owa.php`](src/owa.php). No Composer required.

Upstream ubus reference: [OpenWrt ubus](https://openwrt.org/docs/techref/ubus).

## Quick start

```php
require_once __DIR__ . '/src/owa.php';

$owa = new OpenWrtApi('https://192.168.1.1');  // or http://…
$owa->UbusLogin('root', 'your-password');

$board = $owa->UbusCall('system', 'board');
$info  = $owa->UbusCall('system', 'info');

// Batch: system.info + stations on two radios
$owa->MultiAdd('system', 'info');
$id0 = $owa->MultiAdd('iwinfo', 'assoclist', array('device' => 'wlan0'));
$id1 = $owa->MultiAdd('iwinfo', 'assoclist', array('device' => 'wlan1'));
$batch = $owa->MultiCall();  // keyed by id

// Or the helper:
$stations = $owa->UbusListStations(array('wlan0', 'wlan1'));
```

CLI smoke script: copy [`test.php`](test.php), set host/user/pass, run `php test.php`.

**Requirements:** **PHP 5.4+** (including 7.x and 8.x) with extensions **curl** and **json**. No Composer. OpenWrt with LuCI ubus RPC enabled (default on most images).

## Why MultiCall matters

A dual-band AP often has several wireless ifaces. Calling `assoclist` once per iface means N HTTP handshakes, N logins-or-session hits, and N× latency on a slow LAN link.

`MultiAdd` / `MultiCall` packs those ubus calls into **one** JSON-RPC array POST. That is what makes a live “who is on wifi?” panel feel snappy.

## Status

Small focused library (GPL-3.0). API surface is the public methods above; internals may stay pragmatic. Issues and PRs welcome on GitHub.

Star or fork if it saves you a night of curl archaeology: [github.com/soif/OpenWrtApi](https://github.com/soif/OpenWrtApi)
