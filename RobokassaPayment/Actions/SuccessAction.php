<?php declare(strict_types=1);

namespace Plugin\RobokassaPayment\Actions;

use App\Application\Actions\Cup\Catalog\CatalogAction;
use App\Domain\Service\Catalog\Exception\OrderNotFoundException;

class SuccessAction extends CatalogAction
{
    protected function action(): \Slim\Psr7\Response
    {
        $default = [
            'OutSum' => 0,
            'InvId' => 0,
            'SignatureValue' => null,
        ];
        $secure = [
            'password' => $this->parameter('RobokassaPlugin_password_1', ''),
        ];
        $data = array_merge($default, $this->request->getQueryParams(), $secure);

        try {
            $order = $this->catalogOrderService->read(['serial' => $data['InvId']]);

            if ($order) {
                $check = strtoupper(md5(implode(':', [$data['OutSum'], $data['InvId'], $data['password']])));

                if ($check === strtoupper($data['SignatureValue'])) {
                    $this->container->get(\App\Application\PubSub::class)->publish('plugin:order:payment', $order);
                }

                return $this->respondWithRedirect('/cart/done/' . $order->uuid);
            }
        } catch (OrderNotFoundException $e) {
            // nothing
        }

        return $this->respondWithRedirect('/');
    }
}
