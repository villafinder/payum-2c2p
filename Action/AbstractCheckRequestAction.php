<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Request\GetHttpRequest;
use Villafinder\Payum2c2p\Api;

abstract class AbstractCheckRequestAction
{
    protected function updateModelFromRequest(ArrayObject $model, GetHttpRequest $httpRequest)
    {
        if ('POST' !== $httpRequest->method) {
            throw new LogicException('Request is invalid. Code 1');
        }

        if (!$this->api->checkResponseHash($httpRequest->request, $model['currency'])) {
            throw new LogicException('Request is invalid. Code 2');
        }

        // We check payment_status because in case of error (999), 2C2P can sometimes omit the amount in its response
        if (StatusAction::STATUS_ERROR !== $httpRequest->request['payment_status'] && $model['amount'] != $httpRequest->request['amount']) {
            throw new LogicException('Request is invalid. Code 3');
        }

        $model->replace($httpRequest->request);
    }
}
