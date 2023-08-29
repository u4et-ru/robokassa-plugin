<?php declare(strict_types=1);

namespace Plugin\Robokassa;

use App\Domain\AbstractExtension;
use App\Domain\Entities\Catalog\Order;
use App\Domain\Entities\User;
use App\Domain\Service\Catalog\Exception\OrderNotFoundException;

class RobokassaPluginTwigExt extends AbstractExtension
{
    public function getName()
    {
        return 'rb_plugin';
    }

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('rb_link', [$this, 'rb_link']),
        ];
    }

    public function rb_link(Order $order)
    {
        $login = $this->parameter('RobokassaPlugin_login', '');
        $password = $this->parameter('RobokassaPlugin_password_1', '');
        $invid = str_replace('-s', '', $order->getExternalId());

        $receipt = [
            'sno' => $this->parameter('RobokassaPlugin_sno', 'osn'),
            'items' => [],
        ];

        foreach ($order->getProducts() as $product) {
            if ($product->getPrice() > 0) {
                $receipt['items'][] = [
                    'name' => $product->getTitle(),
                    'quantity' => $product->getCount(),
                    'cost' => $product->getPrice(),
                    'sum' => $product->getCount() * $product->getPrice(),
                    'tax' => $this->parameter('RobokassaPlugin_tax', 'none'),
                ];
            }
        }

        $receipt = json_encode($receipt, JSON_UNESCAPED_UNICODE);

        return 'https://auth.robokassa.ru/Merchant/Index.aspx?' . implode('&', [
            'MerchantLogin=' . $login,
            'OutSum=' . $order->getTotalPrice(),
            'InvoiceID=' . $invid,
            'Description=' . $this->parameter('RobokassaPlugin_description', ''),
            'Receipt=' . $receipt,
            'SignatureValue=' . md5(implode(':', [$login, $order->getTotalPrice(), $invid, $receipt, $password])),
        ]);
    }
}
