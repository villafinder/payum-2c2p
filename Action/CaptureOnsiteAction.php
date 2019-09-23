<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Exception\LogicException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\ObtainCreditCard;
use Villafinder\Payum2c2p\Api;

/**
 * @property Api $api
 */
class CaptureOnsiteAction extends CaptureAction
{
    use CheckRequestOnsiteTrait;

    const USER_DEFINED_TOKEN = 1;
    const USER_DEFINED_URL   = 2;

    protected function doExecute(Capture $request, GetHttpRequest $httpRequest, \ArrayAccess $model)
    {
        if ('POST' === $httpRequest->method) {
            // We POST redirect to 2c2p
            throw new HttpPostRedirect(
                $this->api->getOnsiteUrl(),
                $this->api->prepareOnsitePayment($model->toUnsafeArray(), $httpRequest->request, [
                    self::USER_DEFINED_TOKEN => $request->getToken()->getHash(),
                    self::USER_DEFINED_URL   => $request->getToken()->getTargetUrl(),
                ])
            );
        }

        try {
            $obtainCreditCard = new ObtainCreditCard($request->getToken());
            $obtainCreditCard->setModel($request->getFirstModel());
            $obtainCreditCard->setModel($request->getModel());
            $this->gateway->execute($obtainCreditCard);
        } catch (RequestNotSupportedException $e) {
            throw new LogicException('Credit card details has to be set explicitly or there has to be an action that supports ObtainCreditCard request.');
        }
    }
}
