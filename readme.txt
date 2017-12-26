=== Easy CryptoCurrency Ticker ===
Contributors: urkekg
Donate link: https://urosevic.net/wordpress/donate/?donate_for=cc-ticker
Tags: bitcoin, litecoin, ethereum, cryptocurrency, coin, ticker, quote, cryptocompare
Requires at least: 4.4
Tested up to: 4.9.1
Stable tag: 1.0
Requires PHP: 5.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Easy add and display current cryptocurrency prices (bitcoin, ethereum, litecoin and/or one of 1800+ others).

== Description ==

Easy CryptoCurrency Ticker displays current cryptocurrency prices (bitcoin, ethereum, litecoin and/or others) on your WordPress website. You may select multiple cryptocurrencies (over 1800 available) to show prices in various currencies (BTC, USD, EUR, GBP or others)

Prices are fetched from [CryptoCompare.com](https://www.cryptocompare.com/) using their [API](https://www.cryptocompare.com/api/).

To improve performance and prevent your website to make too much requests to remote server, we cache prices in local WordPress database for two minutes.

= Features =

* Add one or more cryptocurrencies from the Coin List that contains over than 1800 coins.
* Add one or more national or crypto currencies to display cryptocurrency prices.
* Enable displaying of change amount and percentage below the price.
* Choose to link coin symbol to overview page on CryptoCompare.com
* Optionally hide logo for cryptocurrencies.
* Cache cryptocurrency logo locally.
* Cache prices in local WordPress for two minutes.
* Add referral link to your own [coinbase.com](https://www.coinbase.com/join/5a3578b6abbfc80226e411ec) Referral ID
* Insert widget by regular configurable WordPress widget or shortcode

= Known issues =

* Currencies are listed in alphabetic order, no matter how they are added to From field.
* Some cryptocurrencies are not supported by API even Coin List contain them, like CLOUT, EARTH, etc
* When new cryptocurrency is added for first time, loading of page can be a little bit slower for very first time because plugin have to download and store localle logo for that cryptocurrency.

= Shortcode =

Along to WordPress widget, you can use shortcode `[cryptocurrency_ticker]` with following parameters:

* `f` - Symbols of cryptocurrency coins. Multiple symbols have to be separated by comma. Default fallback is: 'BTC,ETC,XMR'
* `t` - Symbols of national or crypto currency. Multiple symbols have to be separated by comma. Default fallback is: 'USD',
* `noicon` - To hide coin logo, set this option to true`
* `nolink` - To insert coin symbol without link to overview page on CryptoCompare.com, set this option to `true`
* `coinbase` - If you wish to display link to your Coin Base Referral ID, set your referral ID to this parameter
* `showchange` - If you wish to display additional change amount and percentage below the price, set this option to `true`

= Customizations =

To customize how ticker looks like, you can use following CSS classes to target various ticker elements:

* `.cctw` - class of main table element
* `.currency` - cryptocurrency coin symbol
* `.currency.ico` - class targeting cryptocurrency icon
* `.amount` - element that contains price and change values
* `.price` - element that contain price value
* `.currency` - element that contain currency symbol as a part of `.price`
* `.change` - element that contains change value (amount and percentage)
* `.dellay` - element that contains info about dellayed quotes
* `.coinbase` - element that contains referral link to Coin Base

== Installation ==

1. Go to `Plugins` -> `Add New`
1. Search for `Easy CryptoCurrency Ticker` plugin
1. Install and activate `Easy CryptoCurrency Ticker`
1. Go to `Widgets` and insert `Easy CryptoCurrency Ticker` widget to preferred widget area.
1. Click button `Get Live` at the bottom of widget (have to be done only first time after plugin is installed).
1. Enter preferred cryptocurrency coins to `From Symbols` field. Multiple symbols separate by comma (example: `BTC,ETH,LTC,XMR,DASH`)
1. Enter preferred national or crypto currency to `To Symbols` field. Multiple symbols separate by comma (example: `USD,EUR,GBP`)
1. Optionally, if you wish to show your referral link for coinbase.com below the ticker table, add your Coin Base Referral ID to fhat field.
1. Click `Save` button to save widget changes.

== Frequently Asked Questions ==

= Ticker does not blend well to my custom theme. What I can do? =

You can freely override basic CSS styles with custom CSS. Add customization to `Additional CSS` control in theme `Customizer`, or you can use my free WordPress plugin [Head &amp; Footer Code](https://wordpress.org/plugins/head-footer-code/)

= Are those cryptocurrency prices live? =

Quotes are dellayed up to 2 minutes. To prevent overloaded requests to Crypto Compare API, we cache responses for two minutes in local WordPress.

= Can I use a Referrer ID from Coin Base? =

Absolutely. Simply enter your Referral ID to widget field. If you leave that field empty, no link will be displayed.

== Donations ==

Feel free to buy me some beer (or wine) for more cool free plugins.

* PayPal: [https://paypal.me/aurosevic](https://paypal.me/aurosevic)
* BTC: 1EhpAuGM4an7z4r8ACTQ66fsm5Gubmf3HW
* ETH: 0x13A850f14CB404C815248e24D6B6d61234Df538d
* LTC: LQPqqYRTX1mDxazTX566rEog7bohbGAhmT

== Screenshots ==

1. Widget settings in customizer and preview
2. Shortcode with custom parameters
3. Front-end preview of ticker inserted by shortcode to page

== Upgrade Notice ==

= 1.0 =

* This is initial release of fresh new plugin.

== ChangeLog ==

= 1.0 =

* Initial release.
