<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Villafinder\Payum2c2p\Api;

/**
 * @property Api $api
 */
class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        // Ensure payment currency is configured, Exception is thrown otherwise
        $this->api->getCurrencyConfigByCode($request->getFirstModel()->getCurrencyCode());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        // We are back from 2c2p
        if (isset($httpRequest->request['payment_status'])) {
            // Only if we trust user request, we can handle the request
            if ($this->api->trustUserRequest()) {
                try {
                    $notify = new Notify($model);
                    $this->gateway->execute($notify);
                } catch (HttpResponse $e) {
                    // Only non-HTTP200 responses are interesting here
                    if (200 !== $e->getStatusCode()) {
                        throw new LogicException($e->getContent(), $e->getStatusCode(), $e);
                    }
                }
            }

            return;
        }

        // User will come back to this URL
        if (empty($model['result_url_1']) && $request->getToken()) {
            $model['result_url_1'] = $request->getToken()->getTargetUrl();
        }

        // Server-to-server notification will be sent to this URL
        if (empty($model['result_url_2']) && $request->getToken() && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );

            $model['result_url_2'] = $notifyToken->getTargetUrl();
        }

        // We POST redirect to 2c2p
        throw new HttpPostRedirect(
            $this->api->getOffsiteUrl(),
            $this->api->prepareOffsitePayment($model->toUnsafeArray())
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
