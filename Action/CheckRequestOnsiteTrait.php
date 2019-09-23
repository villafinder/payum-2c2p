<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Request\GetHttpRequest;
use Villafinder\Payum2c2p\Api;

/**
 * @property Api $api
 */
trait CheckRequestOnsiteTrait
{
    protected function updateModelFromRequest(ArrayObject $model, GetHttpRequest $httpRequest)
    {
        // Model has already been updated (by Notify or Capture, first come first served), nothing more to do here
        if (isset($model['status'])) {
            return;
        }

        $response = $this->readFromRequest($httpRequest);

        // We check payment_status because in case of error, 2C2P can sometimes omit the amount in its response
        if (StatusOnsiteAction::STATUS_FAILED !== $response['status'] && $model['amount'] != $response['amt']) {
            throw new LogicException('Onsite request is invalid. Code 4');
        }

        $model->replace($response);
    }

    protected function readFromRequest(GetHttpRequest $httpRequest)
    {
        if ('POST' !== $httpRequest->method) {
            throw new LogicException('Onsite request is invalid. Code 1');
        }

        if (!isset($httpRequest->request['paymentResponse'])) {
            throw new LogicException('Onsite request is invalid. Code 2');
        }

        try {
            return $this->api->readOnsiteResponse($httpRequest->request['paymentResponse']);
        } catch (\Exception $e) {
            throw new LogicException('Onsite request is invalid. Code 3', $e->getCode(), $e);
        }
    }

    protected function isBackFrom2c2p(GetHttpRequest $httpRequest)
    {
        return isset($httpRequest->request['paymentResponse']);
    }
}
