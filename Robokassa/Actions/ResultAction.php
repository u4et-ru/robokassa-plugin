<?php declare(strict_types=1);

namespace Plugin\Robokassa\Actions;

use App\Application\Actions\Cup\Catalog\CatalogAction;

class ResultAction extends CatalogAction
{
    protected function action(): \Slim\Psr7\Response
    {
        $default = [
            'OutSum' => 0,
            'InvId' => 0,
            'SignatureValue' => null,
        ];
        $secure = [
            'password' => $this->parameter('RobokassaPlugin_password_2', ''),
        ];
        $data = array_merge($default, $this->request->getQueryParams(), $secure);

        $order = $this->catalogOrderService->read(['external_id' => $data['InvId'] . '-s']);

        if ($order) {
            $check = strtoupper(md5(implode(':', [$data['OutSum'], $data['InvId'], $data['password']])));

            if ($check === strtoupper($data['SignatureValue'])) {
                $status = $this->catalogOrderStatusService->read(['title' => 'Оплачен']);

                $this->catalogOrderService->update($order, [
                    'status' => $status ?? null,
                    'system' => 'Заказ оплачен',
                ]);

                $this->container->get(\App\Application\PubSub::class)->publish('tm:order:oplata', $order);
            }

            return $this->respondWithRedirect('/cart/done/' . $order->getUuid()->toString());
        }

        return $this->respondWithRedirect('/');
    }
}
