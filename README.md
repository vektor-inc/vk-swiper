# VK Swiper

## 概要

Swiper を Composer で導入するためのライブラリ

`npm run update` で最新の Swiper が導入できます。


## 使い方

Composer の require に登録
```
composer require vektor-inc/vk-swiper
```

autoload.php を読み込み
```
require_once dirname( __FILE__ ) . '/vendor/autoload.php';
```

本体を読み込んで実行

```
use VektorInc\VK_Swiper\VkSwiper;
new VkSwiper();
VkSwiper::enqueue_swiper();
```

---

## Change log

* [ 仕様変更 ] Swiper を 11.2.10 から 12.2.0 にアップデート

* [ 開発環境 ] Node.js を 18.13.0 から 20.20.2、@wordpress/env を 10 から 11 にアップデート

= 0.3.6 =
* [ Fix ] WP_CONTENT_DIR / WP_CONTENT_URL / WP_PLUGIN_DIR 等をカスタマイズした環境でもリソースが正しく読み込まれるように get_directory_uri() を改善

* [ Update ] Update Swiper 11.1.5

* [ Update ] Update Swiper 11.1.4

= 0.3.4 =
* [ Update ] Update Swiper 11.0.2

= 0.3.3 =
* [ Bug fix ] fix css and js path //
