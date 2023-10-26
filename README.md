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

= 0.3.4 =
* [ Update ] Update Swiper 11.0.2

= 0.3.3 =
* [ Bug fix ] fix css and js path //
