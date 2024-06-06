<?php declare(strict_types=1);

namespace Plugin\RobokassaPayment\Actions;

use App\Application\Actions\Cup\Catalog\CatalogAction;
use App\Domain\Service\Catalog\Exception\OrderNotFoundException;

class ErrorAction extends CatalogAction
{
    protected function action(): \Slim\Psr7\Response
    {
        $default = [
            'InvId' => 0,
        ];
        $data = array_merge($default, $this->request->getQueryParams());

        try {
            $order = $this->catalogOrderService->read(['serial' => $data['InvId']]);

            if ($order) {
                return $this->respondWithRedirect('/cart/done/' . $order->uuid);
            }
        } catch (OrderNotFoundException $e) {
            // nothing
        }

        return $this->respondWithRedirect('/');
    }
}
