<?php declare(strict_types=1);

namespace Plugin\RobokassaPayment;

use App\Domain\Models\CatalogOrder;
use App\Domain\Models\CatalogProduct;
use App\Domain\Plugin\AbstractPaymentPlugin;
use Psr\Container\ContainerInterface;

class RobokassaPaymentPlugin extends AbstractPaymentPlugin
{
    const AUTHOR = 'Aleksey Ilyin';
    const AUTHOR_SITE = 'https://getwebspace.org';
    const NAME = 'RobokassaPaymentPlugin';
    const TITLE = 'RobokassaPayment';
    const DESCRIPTION = 'Возможность принимать безналичную оплату товаров и услуг';
    const VERSION = '2.0.0';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->addSettingsField([
            'label' => 'Логин',
            'type' => 'text',
            'name' => 'login',
        ]);

        $this->addSettingsField([
            'label' => 'Пароль #1',
            'type' => 'text',
            'name' => 'password_1',
        ]);

        $this->addSettingsField([
            'label' => 'Пароль #2',
            'type' => 'text',
            'name' => 'password_2',
        ]);

        $this->addSettingsField([
            'label' => 'Система налогообложения',
            'type' => 'select',
            'name' => 'sno',
            'args' => [
                'option' => [
                    'osn' => 'Общая СН',
                    'usn_income' => 'Упрощенная СН (доходы)',
                    'usn_income_outcome' => 'Упрощенная СН (доходы минус расходы)',
                    'esn' => 'Единый сельскохозяйственный налог',
                    'patent' => 'Патентная СН',
                ],
            ],
        ]);

        $this->addSettingsField([
            'label' => 'Налоговая ставка',
            'type' => 'select',
            'name' => 'tax',
            'args' => [
                'option' => [
                    'none' => 'Без НДС',
                    'vat0' => 'НДС чека по ставке 10%',
                    'vat10' => 'НДС чека по ставке 10%',
                    'vat110' => 'НДС чека по расчетной ставке 20/120',
                    'vat20' => 'НДС чека по расчетной ставке 20/120',
                    'vat120' => 'НДС чека по расчетной ставке 20/120',
                ],
            ],
        ]);

        $this->addSettingsField([
            'label' => 'Описание к оплате',
            'type' => 'text',
            'name' => 'description',
        ]);

        // успешная оплата
        $this
            ->map([
                'methods' => ['get', 'post'],
                'pattern' => '/cart/done/rb/success',
                'handler' => \Plugin\RobokassaPayment\Actions\SuccessAction::class,
            ])
            ->setName('common:rb:success');

        // не успешная оплата
        $this
            ->map([
                'methods' => ['get', 'post'],
                'pattern' => '/cart/done/rb/error',
                'handler' => \Plugin\RobokassaPayment\Actions\ErrorAction::class,
            ])
            ->setName('common:rb:error');

        // результат
        $this
            ->map([
                'methods' => ['get', 'post'],
                'pattern' => '/cart/done/rb/result',
                'handler' => \Plugin\RobokassaPayment\Actions\ResultAction::class,
            ])
            ->setName('common:rb:result');
    }

    public function getRedirectURL(CatalogOrder $order): string
    {
        $login = $this->parameter('RobokassaPlugin_login', '');
        $password = $this->parameter('RobokassaPlugin_password_1', '');

        $receipt = [
            'sno' => $this->parameter('RobokassaPlugin_sno', 'osn'),
            'items' => [],
        ];

        foreach ($order->products as $product) {
            if ($product->price() > 0) {
                $receipt['items'][] = [
                    'name' => $product->title,
                    'quantity' => $product->totalCount(),
                    'cost' => $product->totalPrice(),
                    'sum' => $product->totalSum(),
                    'tax' => $this->parameter('RobokassaPlugin_tax', 'none'),
                ];
            }
        }

        $receipt = json_encode($receipt, JSON_UNESCAPED_UNICODE);

        return 'https://auth.robokassa.ru/Merchant/Index.aspx?' . implode('&', [
                'MerchantLogin=' . $login,
                'OutSum=' . $order->totalSum(),
                'InvoiceID=' . $order->serial,
                'Description=' . $this->parameter('RobokassaPlugin_description', ''),
                'Receipt=' . $receipt,
                'SignatureValue=' . md5(implode(':', [$login, $order->totalSum(), $order->serial, $receipt, $password])),
            ]);
    }
}
