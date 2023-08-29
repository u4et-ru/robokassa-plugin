<?php declare(strict_types=1);

namespace Plugin\Robokassa;

use App\Domain\AbstractPlugin;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class RobokassaPlugin extends AbstractPlugin
{
    const AUTHOR = 'ilshatkin';
    const NAME = 'RobokassaPlugin';
    const TITLE = 'Robokassa';
    const AUTHOR_SITE = 'https://u4et.ru';
    const VERSION = '1.0';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $self = $this;

        $this->addTwigExtension(\Plugin\Robokassa\RobokassaPluginTwigExt::class);

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
                'handler' => \Plugin\Robokassa\Actions\SuccessAction::class,
            ])
            ->setName('common:rb:success');

        // успешная оплата
        $this
            ->map([
                'methods' => ['get', 'post'],
                'pattern' => '/cart/done/rb/error',
                'handler' => \Plugin\Robokassa\Actions\ErrorAction::class,
            ])
            ->setName('common:rb:error');

        // успешная оплата
        $this
            ->map([
                'methods' => ['get', 'post'],
                'pattern' => '/cart/done/rb/result',
                'handler' => \Plugin\Robokassa\Actions\ResultAction::class,
            ])
            ->setName('common:rb:result');
    }
}
