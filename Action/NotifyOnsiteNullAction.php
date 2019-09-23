<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use Payum\Core\Security\TokenInterface;
use Villafinder\Payum2c2p\Api;

/**
 * @property Api $api
 */
class NotifyOnsiteNullAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use CheckRequestOnsiteTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        try {
            $response = $this->readFromRequest($httpRequest);
        } catch (LogicException $e) {
            throw new HttpResponse($e->getMessage(), 400);
        }

        $tokenIndex = sprintf('userDefined%d', CaptureOnsiteAction::USER_DEFINED_TOKEN);

        if (!isset($response[$tokenIndex])) {
            throw new HttpResponse('Token is missing from 2C2P response.', 400);
        }

        $this->gateway->execute($getToken = new GetToken($response[$tokenIndex]));

        if (!$getToken->getToken() instanceof TokenInterface) {
            throw new HttpResponse('Token does not exist.', 400);
        }

        $this->gateway->execute(new Notify($getToken->getToken()));

        throw new HttpResponse('OK', 200);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            null === $request->getModel()
        ;
    }
}
