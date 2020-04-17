## Bunting Core (Magento 2)

This is if you have a custom attribute called `product_rrp` ONLY.

Installation instructions:

- Go to your linux box that has your m2 installation on and remove/uninstall any previous Bunting packages.
- Run `composer install bunting/personalisation-m2-rrp`
-- If you're on Docker (for instance if you installed [this](https://github.com/buntingsoftware/docker-magento2]) then run `docker exec -it docker-magento2_web_1 bash`
- Once done, flush your m2 caches then visit System > Web installation > Modules > Enable the Bunting plugins.   

Bunting Core (and all submodules) officially support PHP 5.5+ and PHP 7.x, with support for Magento version 2.0+
