<?php declare(strict_types=1);

namespace Plugin\Robokassa\Actions;

use App\Application\Actions\Cup\Catalog\CatalogAction;

class ErrorAction extends CatalogAction
{
    protected function action(): \Slim\Psr7\Response
    {
        $default = [
            'InvId' => 0,
        ];
        $data = array_merge($default, $this->request->getQueryParams());

        $order = $this->catalogOrderService->read(['external_id' => $data['InvId'] . '-s']);

        if ($order) {
            $this->catalogOrderService->update($order, [
                'system' => 'Заказ не был оплачен',
            ]);

            return $this->respondWithRedirect('/cart/done/' . $order->getUuid()->toString());
        }

        return $this->respondWithRedirect('/');
    }
}
